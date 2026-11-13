<?php

namespace App\Console\Commands;

use App\Services\SupabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class MqttListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen
                            {--host= : Override MQTT Broker Host}
                            {--port= : Override MQTT Broker Port}
                            {--topic= : Override MQTT Sensor Topic}
                            {--status-topic= : Override MQTT Status Topic}
                            {--username= : Override MQTT Username}
                            {--password= : Override MQTT Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to MQTT messages from EMQX, cache locally for dashboard, and store sensor readings in Supabase';

    private const MAX_HISTORY = 30;

    private const MAX_ALERTS = 15;

    private const LOCAL_ALERT_COOLDOWN_SECONDS = 60;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $lastSavedReading = null;

    private ?int $lastDbSaveTimestamp = null;

    /**
     * @var array<string, int>
     */
    private array $lastAlertTimestamps = [];

    /**
     * @var array<string, bool>
     */
    private array $activeAlertStates = [
        'flame' => false,
        'gas_smoke' => false,
        'extreme_temperature' => false,
    ];

    /**
     * @var array<string, string>
     */
    private array $deviceUuidCache = [];

    /**
     * Path to local sensor state JSON file consumed by the web dashboard API.
     */
    public static function stateFilePath(): string
    {
        return storage_path('app/mqtt_sensor_state.json');
    }

    /**
     * Execute the console command.
     */
    public function handle(SupabaseService $supabase): int
    {
        $host = (string) ($this->option('host') ?: config('mqtt.host', 'ufd1222d.ala.asia-southeast1.emqxsl.com'));
        $port = (int) ($this->option('port') ?: config('mqtt.port', 8883));
        $topic = (string) ($this->option('topic') ?: config('mqtt.topic', 'jalas/sensor/data'));
        $statusTopic = (string) ($this->option('status-topic') ?: config('mqtt.status_topic', 'jalas/status'));
        $clientId = (string) (config('mqtt.client_id', 'jalas-backend') . '-' . substr(uniqid(), -5));

        $username = $this->option('username') !== null ? (string) $this->option('username') : (string) config('mqtt.username', '');
        $password = $this->option('password') !== null ? (string) $this->option('password') : (string) config('mqtt.password', '');
        $useTls = (bool) config('mqtt.use_tls', $port === 8883);
        $qos = (int) config('mqtt.qos', 0);

        $this->info("Menghubungkan ke EMQX Broker [{$host}:{$port}] (TLS: " . ($useTls ? 'ON' : 'OFF') . ")...");
        $this->info("Topik sensor : [{$topic}]");
        $this->info("Topik status : [{$statusTopic}]");

        if ($username === '' || $password === '') {
            $this->warn("MQTT_USERNAME / MQTT_PASSWORD kosong di .env. EMQX Cloud biasanya membutuhkan kredensial.");
        }

        try {
            $mqtt = new MqttClient($host, $port, $clientId);

            $settings = (new ConnectionSettings())
                ->setKeepAliveInterval(60)
                ->setReconnectAutomatically(true)
                ->setMaxReconnectAttempts(10)
                ->setDelayBetweenReconnectAttempts(2000);

            if ($username !== '') {
                $settings = $settings->setUsername($username);
            }

            if ($password !== '') {
                $settings = $settings->setPassword($password);
            }

            if ($useTls) {
                $settings = $settings->setUseTls(true);
            }

            $mqtt->connect($settings, false);
            $this->info("✓ Berhasil terhubung ke EMQX! Menunggu pesan dari ESP32...");

            $mqtt->subscribe($topic, function (string $receivedTopic, string $message) use ($supabase) {
                $this->processSensorMessage($receivedTopic, $message, $supabase);
            }, $qos);

            if ($statusTopic !== '' && $statusTopic !== $topic) {
                $mqtt->subscribe($statusTopic, function (string $receivedTopic, string $message) {
                    $this->processStatusMessage($receivedTopic, $message);
                }, $qos);
            }

            // Main loop to keep listening
            $mqtt->loop(true);

            $mqtt->disconnect();
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("✗ Koneksi MQTT gagal: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Process incoming ESP32 sensor JSON payload.
     */
    protected function processSensorMessage(string $topic, string $message, SupabaseService $supabase): void
    {
        $now = now('Asia/Jakarta');
        $this->line("[" . $now->format('H:i:s') . "] [{$topic}] {$message}");

        $data = json_decode($message, true);
        if (!is_array($data)) {
            $this->warn("Format payload bukan JSON valid. Diabaikan.");
            return;
        }

        $suhu = $this->parseNullableFloat($data['suhu'] ?? $data['temperature'] ?? null);
        $kelembapan = $this->parseNullableFloat($data['kelembapan'] ?? $data['humidity'] ?? null);

        // ESP32 sends -1 when DHT sensor returns NaN
        if ($suhu !== null && $suhu < 0) {
            $suhu = null;
        }
        if ($kelembapan !== null && $kelembapan < 0) {
            $kelembapan = null;
        }

        $asapAnalog = isset($data['asap_analog']) ? (int) $data['asap_analog'] : (isset($data['gas_raw']) ? (int) $data['gas_raw'] : null);
        $asapDigital = isset($data['asap_digital']) ? (int) $data['asap_digital'] : null;
        $apiAnalog = isset($data['api_analog']) ? (int) $data['api_analog'] : null;
        $apiDigital = isset($data['api_digital']) ? (int) $data['api_digital'] : null;

        $bahayaAsap = (bool) ($data['bahaya_asap'] ?? false);
        $bahayaApi = (bool) ($data['bahaya_api'] ?? $data['flame_detected'] ?? false);
        $bahayaSuhu = (bool) ($data['bahaya_suhu'] ?? false);
        $status = strtoupper((string) ($data['status'] ?? (($bahayaAsap || $bahayaApi || $bahayaSuhu) ? 'BAHAYA' : 'AMAN')));

        $reading = [
            'device' => (string) ($data['device'] ?? 'JALAS-ESP32'),
            'suhu' => $suhu,
            'kelembapan' => $kelembapan,
            'asap_analog' => $asapAnalog,
            'asap_digital' => $asapDigital,
            'api_analog' => $apiAnalog,
            'api_digital' => $apiDigital,
            'bahaya_asap' => $bahayaAsap,
            'bahaya_api' => $bahayaApi,
            'bahaya_suhu' => $bahayaSuhu,
            'status' => $status,
            'buzzer' => (int) ($data['buzzer'] ?? ($status === 'BAHAYA' ? 1 : 0)),
            'uptime' => isset($data['uptime']) ? (int) $data['uptime'] : null,
            'risk_score' => $this->calculateRiskScore($suhu, $kelembapan, $asapAnalog, $bahayaAsap, $bahayaApi, $bahayaSuhu),
            'received_at' => $now->toIso8601String(),
            'received_at_wib' => $now->format('H:i:s') . ' WIB',
        ];

        $this->saveLocalSensorState($reading);
        $this->info("✓ Data sensor diperbarui di cache dashboard ({$reading['device']} | {$reading['status']})");

        // Optional: forward to Supabase if table/credentials are configured
        $this->persistToSupabase($reading, $data['device_id'] ?? null, $supabase);
    }

    /**
     * Process incoming ESP32 status message on jalas/status.
     */
    protected function processStatusMessage(string $topic, string $message): void
    {
        $now = now('Asia/Jakarta');
        $this->line("[" . $now->format('H:i:s') . "] [{$topic}] Status: {$message}");

        $decoded = json_decode($message, true);
        $statusText = is_array($decoded)
            ? (string) ($decoded['status'] ?? $message)
            : trim($message);

        $state = $this->readLocalState();
        $state['device_status'] = [
            'status' => $statusText,
            'device' => is_array($decoded) ? (string) ($decoded['device'] ?? 'JALAS-ESP32') : 'JALAS-ESP32',
            'received_at' => $now->toIso8601String(),
            'received_at_wib' => $now->format('H:i:s') . ' WIB',
        ];

        $this->writeLocalState($state);
    }

    /**
     * Calculate composite fire risk score (0-100) from telemetry.
     */
    protected function calculateRiskScore(
        ?float $suhu,
        ?float $kelembapan,
        ?int $asapAnalog,
        bool $bahayaAsap,
        bool $bahayaApi,
        bool $bahayaSuhu
    ): int {
        if ($bahayaApi) {
            return 95;
        }

        $score = 0;

        if ($suhu !== null) {
            if ($suhu >= 45) {
                $score += 35;
            } elseif ($suhu >= 35) {
                $score += 22;
            } elseif ($suhu >= 30) {
                $score += 12;
            } else {
                $score += 5;
            }
        }

        if ($kelembapan !== null) {
            if ($kelembapan <= 35) {
                $score += 25;
            } elseif ($kelembapan <= 55) {
                $score += 14;
            } else {
                $score += 5;
            }
        }

        if ($asapAnalog !== null) {
            // ESP32 ADC 12-bit (0-4095)
            $score += min(40, (int) round(($asapAnalog / 4095) * 40));
        }

        if ($bahayaAsap) {
            $score = max($score, 78);
        }

        if ($bahayaSuhu) {
            $score = max($score, 72);
        }

        return max(0, min(100, $score));
    }

    /**
     * Persist latest reading, history, and alert entries locally for instant dashboard polling.
     *
     * @param array<string, mixed> $reading
     */
    protected function saveLocalSensorState(array $reading): void
    {
        $state = $this->readLocalState();
        $state['latest'] = $reading;
        $state['device_status'] = [
            'status' => 'online',
            'device' => $reading['device'],
            'received_at' => $reading['received_at'],
            'received_at_wib' => $reading['received_at_wib'],
        ];

        $history = is_array($state['history'] ?? null) ? $state['history'] : [];
        $history[] = [
            'suhu' => $reading['suhu'],
            'kelembapan' => $reading['kelembapan'],
            'asap_analog' => $reading['asap_analog'],
            'risk_score' => $reading['risk_score'],
            'status' => $reading['status'],
            'time' => substr($reading['received_at_wib'], 0, 8),
            'received_at' => $reading['received_at'],
        ];
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }
        $state['history'] = $history;

        $alerts = is_array($state['alerts'] ?? null) ? $state['alerts'] : [];
        if ($reading['status'] === 'BAHAYA') {
            $reasons = [];
            if ($reading['bahaya_api']) {
                $reasons[] = 'Nyala api terdeteksi (IR Flame)';
            }
            if ($reading['bahaya_asap']) {
                $reasons[] = "Asap/gas tinggi ({$reading['asap_analog']} ADC)";
            }
            if ($reading['bahaya_suhu']) {
                $reasons[] = "Suhu tinggi ({$reading['suhu']} °C)";
            }
            $summary = $reasons !== [] ? implode(' · ', $reasons) : 'Kondisi bahaya terdeteksi oleh ESP32';

            $latestAlert = $alerts[0] ?? null;
            $shouldAppendLocalAlert = true;
            if (is_array($latestAlert) && !empty($latestAlert['received_at'])) {
                try {
                    $secondsSinceLast = now('Asia/Jakarta')->diffInSeconds(\Carbon\Carbon::parse((string) $latestAlert['received_at']), true);
                    if ($secondsSinceLast < self::LOCAL_ALERT_COOLDOWN_SECONDS && ($latestAlert['message'] ?? '') === $summary) {
                        $shouldAppendLocalAlert = false;
                    }
                } catch (Throwable) {
                    $shouldAppendLocalAlert = true;
                }
            }

            if ($shouldAppendLocalAlert) {
                array_unshift($alerts, [
                    'severity' => $reading['bahaya_api'] ? 'critical' : 'high',
                    'title' => 'Peringatan Bahaya Kebakaran',
                    'message' => $summary,
                    'time' => $reading['received_at_wib'],
                    'received_at' => $reading['received_at'],
                ]);
                $alerts = array_slice($alerts, 0, self::MAX_ALERTS);
            }
        }
        $state['alerts'] = $alerts;

        $this->writeLocalState($state);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readLocalState(): array
    {
        $path = self::stateFilePath();
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    protected function writeLocalState(array $state): void
    {
        $path = self::stateFilePath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Forward reading to Supabase with smart throttling so database storage is not exhausted.
     *
     * @param array<string, mixed> $reading
     */
    protected function persistToSupabase(array $reading, mixed $deviceId, SupabaseService $supabase): void
    {
        if (empty(config('supabase.url')) || (empty(config('supabase.anon_key')) && empty(config('supabase.service_role_key')))) {
            return;
        }

        if (!$this->shouldPersistToDatabase($reading)) {
            return;
        }

        try {
            $resolvedDeviceId = !empty($deviceId)
                ? (string) $deviceId
                : $this->resolveDeviceUuid($reading['device'], $supabase);

            $payload = [
                'temperature' => $reading['suhu'],
                'humidity' => $reading['kelembapan'],
                'gas_raw' => $reading['asap_analog'],
                'flame_detected' => $reading['bahaya_api'],
            ];

            if (!empty($resolvedDeviceId)) {
                $payload['device_id'] = $resolvedDeviceId;
                $supabase->update('devices', ['id' => 'eq.' . $resolvedDeviceId], [
                    'status' => 'online',
                    'last_seen' => now()->toIso8601String(),
                ]);
            }

            $inserted = $supabase->insert('sensor_readings', $payload);
            if (!empty($inserted)) {
                $this->lastSavedReading = $reading;
                $this->lastDbSaveTimestamp = time();

                $this->info('✓ Disimpan ke Supabase (sensor_readings)');
                $readingId = isset($inserted[0]['id']) ? (int) $inserted[0]['id'] : null;
                $this->checkAndCreateSupabaseAlerts($reading, $readingId, $supabase);
            }
        } catch (Throwable $e) {
            $this->warn('Supabase sync dilewati: ' . $e->getMessage());
        }
    }

    /**
     * Determine whether the current reading should be persisted to Supabase.
     * Saves immediately on hazard transitions or significant deltas; otherwise throttles by interval.
     *
     * @param array<string, mixed> $reading
     */
    protected function shouldPersistToDatabase(array $reading): bool
    {
        if ($this->lastSavedReading === null || $this->lastDbSaveTimestamp === null) {
            return true;
        }

        if (
            $reading['status'] !== $this->lastSavedReading['status']
            || $reading['bahaya_api'] !== $this->lastSavedReading['bahaya_api']
            || $reading['bahaya_asap'] !== $this->lastSavedReading['bahaya_asap']
            || $reading['bahaya_suhu'] !== $this->lastSavedReading['bahaya_suhu']
        ) {
            return true;
        }

        if (
            $reading['suhu'] !== null
            && $this->lastSavedReading['suhu'] !== null
            && abs($reading['suhu'] - $this->lastSavedReading['suhu']) >= 1.5
        ) {
            return true;
        }

        if (
            $reading['kelembapan'] !== null
            && $this->lastSavedReading['kelembapan'] !== null
            && abs($reading['kelembapan'] - $this->lastSavedReading['kelembapan']) >= 5.0
        ) {
            return true;
        }

        if (
            $reading['asap_analog'] !== null
            && $this->lastSavedReading['asap_analog'] !== null
            && abs($reading['asap_analog'] - $this->lastSavedReading['asap_analog']) >= 100
        ) {
            return true;
        }

        $interval = max(10, (int) config('mqtt.db_save_interval', 60));

        return (time() - $this->lastDbSaveTimestamp) >= $interval;
    }

    /**
     * Find or register the IoT device in Supabase devices table.
     */
    protected function resolveDeviceUuid(string $deviceName, SupabaseService $supabase): ?string
    {
        if (isset($this->deviceUuidCache[$deviceName])) {
            return $this->deviceUuidCache[$deviceName];
        }

        $existing = $supabase->from('devices', [
            'device_name' => 'eq.' . $deviceName,
            'select' => 'id',
            'limit' => 1,
        ]);

        if (!empty($existing[0]['id'])) {
            return $this->deviceUuidCache[$deviceName] = (string) $existing[0]['id'];
        }

        $created = $supabase->insert('devices', [
            'device_name' => $deviceName,
            'device_type' => 'esp32_sensor_node',
            'location' => 'Area Pemantauan Utama',
            'status' => 'online',
            'last_seen' => now()->toIso8601String(),
        ]);

        if (!empty($created[0]['id'])) {
            return $this->deviceUuidCache[$deviceName] = (string) $created[0]['id'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $reading
     */
    protected function checkAndCreateSupabaseAlerts(array $reading, ?int $readingId, SupabaseService $supabase): void
    {
        $nowTs = time();
        $cooldown = max(30, (int) config('mqtt.alert_cooldown', 300));

        $candidates = [
            'flame' => [
                'active' => !empty($reading['bahaya_api']),
                'severity' => 'critical',
                'message' => 'Sensor mendeteksi nyala api langsung di area pemantauan!',
            ],
            'gas_smoke' => [
                'active' => !empty($reading['bahaya_asap']),
                'severity' => 'high',
                'message' => "Konsentrasi gas/asap terdeteksi tinggi ({$reading['asap_analog']} ADC)!",
            ],
            'extreme_temperature' => [
                'active' => !empty($reading['bahaya_suhu']),
                'severity' => 'high',
                'message' => "Suhu lingkungan sangat tinggi ({$reading['suhu']} °C)!",
            ],
        ];

        foreach ($candidates as $type => $info) {
            $wasActive = $this->activeAlertStates[$type] ?? false;
            $isActive = $info['active'];
            $this->activeAlertStates[$type] = $isActive;

            if (!$isActive) {
                continue;
            }

            $lastSent = $this->lastAlertTimestamps[$type] ?? 0;
            if ($wasActive && ($nowTs - $lastSent) < $cooldown) {
                continue;
            }

            $inserted = $supabase->insert('alerts', [
                'sensor_reading_id' => $readingId,
                'alert_type' => $type,
                'severity' => $info['severity'],
                'message' => $info['message'],
                'status' => 'active',
            ]);

            if (!empty($inserted)) {
                $this->lastAlertTimestamps[$type] = $nowTs;
            }
        }
    }

    protected function parseNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
