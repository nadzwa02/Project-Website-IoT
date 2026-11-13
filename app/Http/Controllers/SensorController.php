<?php

namespace App\Http\Controllers;

use App\Console\Commands\MqttListenCommand;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class SensorController extends Controller
{
    private const ONLINE_TIMEOUT_SECONDS = 30;

    public function __construct(public SupabaseService $supabase) {}

    /**
     * Return latest MQTT sensor telemetry, history, and alert state.
     */
    public function latest(): JsonResponse
    {
        $path = MqttListenCommand::stateFilePath();

        if (!File::exists($path)) {
            return $this->fallbackFromSupabase();
        }

        $state = json_decode((string) File::get($path), true);
        if (!is_array($state)) {
            $state = [];
        }

        $latest = is_array($state['latest'] ?? null) ? $state['latest'] : null;
        $deviceStatus = is_array($state['device_status'] ?? null) ? $state['device_status'] : null;
        $isOnline = $this->determineOnlineStatus($latest, $deviceStatus);

        return response()->json([
            'success' => true,
            'has_data' => $latest !== null,
            'is_online' => $isOnline,
            'latest' => $latest,
            'device_status' => $deviceStatus,
            'history' => is_array($state['history'] ?? null) ? $state['history'] : [],
            'alerts' => is_array($state['alerts'] ?? null) ? $state['alerts'] : [],
        ]);
    }

    /**
     * Fallback to Supabase history when local MQTT cache file does not exist yet.
     */
    protected function fallbackFromSupabase(): JsonResponse
    {
        if (empty(config('supabase.url')) || (empty(config('supabase.anon_key')) && empty(config('supabase.service_role_key')))) {
            return response()->json([
                'success' => true,
                'has_data' => false,
                'is_online' => false,
                'latest' => null,
                'device_status' => null,
                'history' => [],
                'alerts' => [],
            ]);
        }

        $rows = $this->supabase->from('sensor_readings', [
            'select' => 'id,temperature,humidity,gas_raw,flame_detected,recorded_at',
            'order' => 'recorded_at.desc',
            'limit' => 30,
        ]);

        if ($rows === []) {
            return response()->json([
                'success' => true,
                'has_data' => false,
                'is_online' => false,
                'latest' => null,
                'device_status' => null,
                'history' => [],
                'alerts' => [],
            ]);
        }

        $chronological = array_reverse($rows);
        $history = [];
        $latest = null;

        foreach ($chronological as $row) {
            $suhu = isset($row['temperature']) ? (float) $row['temperature'] : null;
            $hum = isset($row['humidity']) ? (float) $row['humidity'] : null;
            $gas = isset($row['gas_raw']) ? (int) $row['gas_raw'] : null;
            $flame = (bool) ($row['flame_detected'] ?? false);
            $bahayaAsap = $gas !== null && $gas >= 1500;
            $bahayaSuhu = $suhu !== null && $suhu >= 45;
            $isDanger = $flame || $bahayaAsap || $bahayaSuhu;
            $recordedAt = Carbon::parse((string) ($row['recorded_at'] ?? now()))->timezone('Asia/Jakarta');

            $score = $flame ? 95 : min(100, (int) round((($suhu ?? 0) * 0.6) + (($gas ?? 0) / 4095 * 40)));

            $item = [
                'device' => 'JALAS-ESP32',
                'suhu' => $suhu,
                'kelembapan' => $hum,
                'asap_analog' => $gas,
                'api_analog' => null,
                'bahaya_asap' => $bahayaAsap,
                'bahaya_api' => $flame,
                'bahaya_suhu' => $bahayaSuhu,
                'status' => $isDanger ? 'BAHAYA' : 'AMAN',
                'buzzer' => $isDanger ? 1 : 0,
                'uptime' => null,
                'risk_score' => $score,
                'time' => $recordedAt->format('H:i:s'),
                'received_at' => $recordedAt->toIso8601String(),
                'received_at_wib' => $recordedAt->format('H:i:s') . ' WIB',
            ];

            $history[] = [
                'suhu' => $item['suhu'],
                'kelembapan' => $item['kelembapan'],
                'asap_analog' => $item['asap_analog'],
                'risk_score' => $item['risk_score'],
                'status' => $item['status'],
                'time' => $item['time'],
                'received_at' => $item['received_at'],
            ];

            $latest = $item;
        }

        $supabaseAlerts = $this->supabase->from('alerts', [
            'select' => 'alert_type,severity,message,recorded_at',
            'order' => 'recorded_at.desc',
            'limit' => 5,
        ]);

        $alerts = array_map(static function (array $alert): array {
            $ts = Carbon::parse((string) ($alert['recorded_at'] ?? now()))->timezone('Asia/Jakarta');

            return [
                'severity' => (string) ($alert['severity'] ?? 'high'),
                'title' => 'Peringatan Bahaya Kebakaran',
                'message' => (string) ($alert['message'] ?? ''),
                'time' => $ts->format('H:i:s') . ' WIB',
                'received_at' => $ts->toIso8601String(),
            ];
        }, $supabaseAlerts);

        $isOnline = $this->determineOnlineStatus($latest, null);

        return response()->json([
            'success' => true,
            'has_data' => $latest !== null,
            'is_online' => $isOnline,
            'latest' => $latest,
            'device_status' => null,
            'history' => $history,
            'alerts' => $alerts,
        ]);
    }

    /**
     * @param array<string, mixed>|null $latest
     * @param array<string, mixed>|null $deviceStatus
     */
    protected function determineOnlineStatus(?array $latest, ?array $deviceStatus): bool
    {
        if (isset($deviceStatus['status']) && strtolower((string) $deviceStatus['status']) === 'offline') {
            return false;
        }

        $timestamp = $latest['received_at'] ?? $deviceStatus['received_at'] ?? null;
        if (empty($timestamp)) {
            return false;
        }

        try {
            return Carbon::parse((string) $timestamp)->diffInSeconds(now()) <= self::ONLINE_TIMEOUT_SECONDS;
        } catch (\Throwable) {
            return false;
        }
    }
}
