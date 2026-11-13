<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#122e20">
    <title>JALAS | Sistem Pemantauan Kebakaran Hutan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="dashboard-shell">
        <header class="topbar">
            <a class="brand" href="#dashboard" aria-label="JALAS, kembali ke dashboard">
                <span class="brand-mark" aria-hidden="true">J</span>
                <span>
                    <strong>JALAS<span class="brand-period">.</span></strong>
                    <small>Jaringan Analisis Lingkungan &amp; Sistem</small>
                </span>
            </a>

            <div class="topbar-meta">
                <span class="live-indicator"><span></span> MONITORING CENTER</span>
                <div class="topbar-clock">
                    <span class="topbar-date" data-current-date></span>
                    <span class="topbar-time" data-current-time></span>
                </div>
            </div>

            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                <span></span><span></span><span class="visually-hidden">Buka navigasi</span>
            </button>
        </header>

        <nav class="primary-nav" id="primary-nav" aria-label="Navigasi utama">
            <a class="nav-link is-active" href="#dashboard">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                </span>
                Dashboard
            </a>
            <a class="nav-link" href="#kamera">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                </span>
                Kamera AI
            </a>
            <a class="nav-link" href="#peta">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                </span>
                Peta Operasional
            </a>
            <a class="nav-link" href="#sensor">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </span>
                Telemetri Sensor
            </a>
            <a class="nav-link" href="#analisis">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                Analisis Risiko
            </a>
            <a class="nav-link" href="#peringatan">
                <span aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </span>
                Peringatan
            </a>
            <span class="nav-spacer"></span>
            <span class="nav-system" id="nav-system-status"><span></span> SISTEM AKTIF</span>
        </nav>

        <main class="dashboard" id="dashboard">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">PUSAT PEMANTAUAN LINGKUNGAN</p>
                    <h1>Indeks Risiko Kebakaran Hutan</h1>
                    <p class="heading-copy">Pantau kondisi lingkungan, telemetri sensor lapangan, dan deteksi dini potensi kebakaran hutan secara real-time.</p>
                </div>
                <div class="heading-location">
                    <span class="location-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span><strong>AREA PEMANTAUAN</strong><small>Wilayah Hutan Indonesia (SIPONGI)</small></span>
                </div>
            </section>

            <section class="system-banner" id="sensor-banner" aria-label="Status sistem">
                <span class="banner-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div class="banner-copy">
                    <strong id="sensor-banner-title">MENUNGGU DATA SENSOR LAPANGAN</strong>
                    <span id="sensor-banner-desc">Sistem siap memantau. Data telemetri suhu, kelembapan, dan gas akan diperbarui otomatis setelah perangkat IoT terhubung.</span>
                </div>
                <span class="banner-status" id="sensor-banner-status"><span></span> SIAGA</span>
            </section>

            <section class="stats-grid" aria-label="Ringkasan kondisi">
                <article class="stat-card stat-temperature">
                    <div class="stat-heading">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>
                        </span>
                        <span>SUHU LINGKUNGAN</span>
                    </div>
                    <p class="stat-value" id="stat-temp-value">--<span>°C</span></p>
                    <p class="stat-foot" id="stat-temp-foot"><span class="status-dot"></span> Menunggu pembacaan</p>
                </article>
                <article class="stat-card stat-humidity">
                    <div class="stat-heading">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        </span>
                        <span>KELEMBAPAN UDARA</span>
                    </div>
                    <p class="stat-value" id="stat-hum-value">--<span>%</span></p>
                    <p class="stat-foot" id="stat-hum-foot"><span class="status-dot"></span> Menunggu pembacaan</p>
                </article>
                <article class="stat-card stat-smoke">
                    <div class="stat-heading">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                        </span>
                        <span>DETEKSI GAS / ASAP</span>
                    </div>
                    <p class="stat-value" id="stat-smoke-value">--<span class="stat-unit">ADC</span></p>
                    <p class="stat-foot" id="stat-smoke-foot"><span class="status-dot"></span> Sensor MQ-2 belum aktif</p>
                </article>
                <article class="stat-card stat-camera">
                    <div class="stat-heading">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                        </span>
                        <span>STATUS KAMERA</span>
                    </div>
                    <p class="stat-value stat-value-text" id="stat-camera-status">BELUM AKTIF</p>
                    <p class="stat-foot" id="stat-camera-foot"><span class="status-dot status-dot-muted"></span> Menunggu koneksi</p>
                </article>
                <article class="stat-card stat-risk">
                    <div class="stat-heading">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </span>
                        <span>TINGKAT RISIKO</span>
                    </div>
                    <p class="stat-value stat-value-text" id="stat-risk-value">BELUM DINILAI</p>
                    <p class="stat-foot" id="stat-risk-foot"><span class="status-dot status-dot-muted"></span> Data belum tersedia</p>
                </article>
            </section>

            <section class="overview-grid">
                <!-- Panel Kamera (Live Stream di Kiri) -->
                <article class="panel camera-panel" id="kamera">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">PENGAWASAN VISUAL &amp; AI YOLO11</p>
                            <h2>Kamera Pemantauan Hutan</h2>
                        </div>
                        <div class="camera-controls">
                            <!-- Dropdown Pemilihan Kamera -->
                            <select id="camera-device-select" class="camera-select" title="Pilih sumber kamera">
                                <option value="">Pilih Kamera...</option>
                            </select>
                            <button id="btn-toggle-camera" type="button" class="btn-camera">
                                📷 Buka Kamera
                            </button>
                            <button id="btn-fullscreen-camera" type="button" class="btn-fullscreen" title="Tampilkan Layar Penuh">
                                ⛶ Layar Penuh
                            </button>
                            <span class="camera-state" id="camera-badge"><i></i> OFFLINE</span>
                        </div>
                    </div>
                    <div class="camera-scene" id="camera-container" role="region" aria-label="Area live stream kamera">
                        <img id="live-stream" class="camera-video-feed" alt="Live stream kamera hutan" hidden>
                        <video id="live-video" class="camera-video-feed" autoplay playsinline muted hidden></video>
                        <canvas id="live-canvas" class="camera-canvas-overlay" hidden></canvas>

                        <div id="camera-placeholder">
                            <svg viewBox="0 0 520 280" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                                <defs>
                                    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#1e3a2f"/><stop offset="1" stop-color="#355e4b"/></linearGradient>
                                    <linearGradient id="ridge" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#2b4c3b"/><stop offset="1" stop-color="#172d22"/></linearGradient>
                                    <linearGradient id="forest" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#1f3b2c"/><stop offset="1" stop-color="#0f2017"/></linearGradient>
                                </defs>
                                <rect width="520" height="280" fill="url(#sky)"/>
                                <circle cx="407" cy="57" r="24" fill="#a7f3d0" opacity=".2"/>
                                <path d="M0 152 77 91l53 44 72-77 78 87 70-61 67 65 54-41 49 37v135H0Z" fill="#2f5241" opacity=".55"/>
                                <path d="m0 173 88-64 61 51 77-84 91 89 55-55 65 50 49-41 34 29v132H0Z" fill="url(#ridge)"/>
                                <path d="M0 189q91-44 168-11t155-3 197 8v97H0Z" fill="#234131" opacity=".8"/>
                                <path d="M0 211q79-38 169-4t174-9 177 1v81H0Z" fill="url(#forest)"/>
                                <g fill="#102319">
                                    <path d="m27 222 18-61 18 61h-12l18 42H20l18-42Zm70 1 19-72 20 72h-13l18 42H80l18-42Zm278-13 22-70 23 70h-15l21 49h-56l20-49Zm81 4 18-61 20 61h-12l18 42h-50l18-42Z"/>
                                </g>
                                <path d="M0 263q106-19 194 0t178-3 148 0v20H0Z" fill="#09150e" opacity=".9"/>
                            </svg>
                            <div class="camera-message">
                                <span class="camera-message-icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                </span>
                                <strong>Kamera belum terhubung</strong>
                                <small>Pilih perangkat kamera pada menu dropdown lalu klik "Buka Kamera".</small>
                            </div>
                        </div>

                        <div class="scene-overlay">
                            <span class="scene-pill">
                                <span class="record-dot" id="live-rec-dot"></span>
                                <span id="live-feed-text">OFFLINE</span>
                            </span>
                            <span id="ai-status-badge" class="ai-badge">AI: STANDBY</span>
                            <span class="scene-cam-id">CAM-01</span>
                        </div>
                    </div>
                    <div class="camera-caption">
                        <span id="camera-footer-status"><i></i> Tidak ada sinyal kamera</span>
                        <span id="camera-last-update">TERAKHIR DIPERBARUI —</span>
                    </div>
                </article>

                <!-- Panel Peta (Peta Taktis di Kanan) -->
                <article class="panel map-panel" id="peta">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">PEMANTAUAN WILAYAH</p>
                            <h2>Peta Taktis Operasional</h2>
                        </div>
                        <span class="panel-tag"><span class="status-dot status-dot-muted"></span> <span id="hotspot-count">MEMUAT HOTSPOT</span></span>
                    </div>

                    <div class="map-canvas">
                        <div id="hotspot-map" class="hotspot-map" role="region" aria-label="Peta interaktif hotspot kebakaran hutan"></div>
                        <div id="hotspot-map-status" class="map-message" role="status" aria-live="polite">Memuat hotspot SIPONGI...</div>
                    </div>
                    <div class="panel-foot"><span id="hotspot-summary" aria-live="polite">Memuat data hotspot dari SIPONGI...</span><span class="foot-muted">SUMBER: SIPONGI KLHK</span></div>
                    <section class="map-detection-status is-idle" data-detection-status role="status" aria-live="polite">
                        <span class="detection-status-icon" data-detection-status-icon aria-hidden="true">!</span>
                        <div class="detection-status-copy">
                            <p class="eyebrow">HASIL DETEKSI KAMERA AI</p>
                            <h3 data-detection-status-title>Kamera belum aktif</h3>
                            <p data-detection-status-detail>Aktifkan kamera untuk memulai deteksi api dan asap otomatis.</p>
                        </div>
                        <span class="detection-status-time" data-detection-status-time>MENUNGGU</span>
                    </section>
                </article>
            </section>

            <section class="panel telemetry-panel" id="sensor">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">STATUS PERANGKAT LAPANGAN</p>
                        <h2>Telemetri Sensor &amp; Konektivitas</h2>
                    </div>
                    <span class="panel-tag" id="telemetry-status-tag"><span class="status-dot status-dot-muted"></span> MENUNGGU DATA</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>PERANGKAT</th><th>PARAMETER</th><th>PEMBACAAN</th><th>STATUS</th><th>PEMBARUAN TERAKHIR</th><th>KONEKSI</th></tr>
                        </thead>
                        <tbody>
                            <tr id="row-temp">
                                <td>
                                    <div class="device-cell">
                                        <span class="device-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>
                                        </span>
                                        <strong>DHT11 <small>SENSOR 01</small></strong>
                                    </div>
                                </td>
                                <td>Suhu lingkungan</td>
                                <td class="reading" data-reading>-- <small>°C</small></td>
                                <td><span class="table-badge badge-pending" data-badge>MENUNGGU DATA</span></td>
                                <td class="table-muted" data-updated>Belum tersedia</td>
                                <td><span class="connection" data-connection><i></i> Offline</span></td>
                            </tr>
                            <tr id="row-hum">
                                <td>
                                    <div class="device-cell">
                                        <span class="device-icon device-icon-blue">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                                        </span>
                                        <strong>DHT11 <small>SENSOR 01</small></strong>
                                    </div>
                                </td>
                                <td>Kelembapan udara</td>
                                <td class="reading" data-reading>-- <small>%</small></td>
                                <td><span class="table-badge badge-pending" data-badge>MENUNGGU DATA</span></td>
                                <td class="table-muted" data-updated>Belum tersedia</td>
                                <td><span class="connection" data-connection><i></i> Offline</span></td>
                            </tr>
                            <tr id="row-smoke">
                                <td>
                                    <div class="device-cell">
                                        <span class="device-icon device-icon-red">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                        </span>
                                        <strong>MQ-2 <small>SENSOR 02</small></strong>
                                    </div>
                                </td>
                                <td>Konsentrasi gas / asap</td>
                                <td class="reading" data-reading>-- <small>ADC</small></td>
                                <td><span class="table-badge badge-pending" data-badge>MENUNGGU DATA</span></td>
                                <td class="table-muted" data-updated>Belum tersedia</td>
                                <td><span class="connection" data-connection><i></i> Offline</span></td>
                            </tr>
                            <tr id="row-flame">
                                <td>
                                    <div class="device-cell">
                                        <span class="device-icon device-icon-red">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                                        </span>
                                        <strong>IR FLAME <small>SENSOR 03</small></strong>
                                    </div>
                                </td>
                                <td>Deteksi nyala api</td>
                                <td class="reading" data-reading>-- <small>ADC</small></td>
                                <td><span class="table-badge badge-pending" data-badge>MENUNGGU DATA</span></td>
                                <td class="table-muted" data-updated>Belum tersedia</td>
                                <td><span class="connection" data-connection><i></i> Offline</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-note"><span><i></i> Menampilkan 4 parameter perangkat terdaftar</span><span>Data diperbarui otomatis saat perangkat terhubung</span></div>
            </section>

            <section class="analysis-grid" id="analisis">
                <article class="panel chart-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">TREN KONDISI LINGKUNGAN</p>
                            <h2>Analisis Risiko Kebakaran</h2>
                        </div>
                        <span class="panel-tag">DATA REAL-TIME</span>
                    </div>
                    <div class="chart-legend">
                        <span><i class="chart-line chart-line-temperature"></i> Suhu (°C)</span>
                        <span><i class="chart-line chart-line-humidity"></i> Kelembapan (%)</span>
                        <span><i class="chart-line chart-line-risk"></i> Indeks Risiko</span>
                    </div>
                    <div class="chart-wrap" role="img" aria-label="Grafik tren kondisi lingkungan">
                        <svg class="risk-chart" id="risk-chart-svg" viewBox="0 0 760 220" preserveAspectRatio="none" aria-hidden="true">
                            <g class="chart-grid"><path d="M48 20H748M48 62H748M48 104H748M48 146H748M48 188H748"/><path d="M48 20V188M188 20V188M328 20V188M468 20V188M608 20V188M748 20V188"/></g>
                            <g class="chart-axis"><text x="8" y="24">100</text><text x="16" y="66">75</text><text x="16" y="108">50</text><text x="16" y="150">25</text><text x="24" y="192">0</text><text id="chart-x-0" x="48" y="212">00:00</text><text id="chart-x-1" x="210" y="212">06:00</text><text id="chart-x-2" x="380" y="212">12:00</text><text id="chart-x-3" x="550" y="212">18:00</text><text id="chart-x-4" x="702" y="212">24:00</text></g>
                            <path class="chart-placeholder" id="chart-placeholder-line" d="M48 104H748"/>
                            <text class="chart-empty" id="chart-empty-title" x="398" y="96" text-anchor="middle">Menunggu data sensor untuk menampilkan grafik tren</text>
                            <text class="chart-empty-sub" id="chart-empty-sub" x="398" y="118" text-anchor="middle">Grafik akan diperbarui otomatis saat telemetri perangkat tersedia</text>
                            <g id="chart-series"></g>
                        </svg>
                    </div>
                    <div class="chart-foot"><span>SUMBER: SENSOR LINGKUNGAN</span><span id="chart-period-label">PERIODE: REAL-TIME</span></div>
                </article>

                <aside class="analysis-aside">
                    <article class="panel risk-score">
                        <div class="panel-heading">
                            <div><p class="eyebrow">INDEKS SAAT INI</p><h2>Skor Risiko</h2></div>
                            <span class="info-mark" title="Skor dihitung otomatis dari suhu, kelembapan, asap, dan api">i</span>
                        </div>
                        <div class="score-content">
                            <div class="score-ring" id="risk-score-ring"><span id="risk-score-value">--</span><small>/ 100</small></div>
                            <div class="score-label"><strong id="risk-score-title">Belum dinilai</strong><span id="risk-score-desc">Menunggu data sensor lapangan</span></div>
                        </div>
                        <div class="score-bar" aria-hidden="true">
                            <span class="score-bar-low"></span>
                            <span class="score-bar-med"></span>
                            <span class="score-bar-high"></span>
                        </div>
                        <div class="score-scale"><span id="score-scale-low">RENDAH</span><span id="score-scale-med">SEDANG</span><span id="score-scale-high">TINGGI</span></div>
                    </article>
                    <article class="panel insight-card">
                        <div class="insight-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4l3-9 4 18 3-9h6"/></svg>
                        </div>
                        <div><p class="eyebrow">RINGKASAN KONDISI</p><strong id="insight-title">Belum ada analisis aktif</strong><p id="insight-desc">Hubungkan sensor suhu, kelembapan, dan gas untuk kalkulasi risiko area.</p></div>
                    </article>
                </aside>
            </section>

            <section class="updates-grid" id="peringatan">
                <article class="panel alerts-panel">
                    <div class="panel-heading">
                        <div><p class="eyebrow">AKTIVITAS SISTEM</p><h2>Log Peringatan Dini</h2></div>
                        <span class="panel-tag">HARI INI</span>
                    </div>
                    <div class="activity-list" id="sensor-activity-list">
                        <div class="activity-item"><span class="activity-icon activity-icon-green">✓</span><div><strong>Sistem pemantauan siap</strong><p>Dashboard berhasil dimuat dan siap menerima aliran data perangkat.</p></div><time>SEKARANG</time></div>
                        <div class="activity-item" id="activity-sensor-status"><span class="activity-icon activity-icon-amber">!</span><div><strong>Sensor belum mengirimkan data</strong><p>Pastikan catu daya perangkat dan koneksi jaringan sensor telah aktif.</p></div><time>MENUNGGU</time></div>
                        <div class="activity-item" id="activity-camera-item"><span class="activity-icon activity-icon-muted" id="activity-camera-icon">▣</span><div><strong id="activity-camera-title">Kamera belum terhubung</strong><p id="activity-camera-desc">Siaran kamera akan tampil saat perangkat tersedia.</p></div><time id="activity-camera-time">MENUNGGU</time></div>
                    </div>
                    <a class="text-link" href="#sensor">Lihat status seluruh perangkat <span aria-hidden="true">→</span></a>
                </article>

                <article class="panel news-panel">
                    <div class="panel-heading">
                        <div><p class="eyebrow">INFORMASI LAPANGAN</p><h2>Panduan &amp; Kesiapsiagaan</h2></div>
                        <span class="panel-tag">JALAS</span>
                    </div>
                    <div class="news-list">
                        <div class="news-item">
                            <span class="news-thumb news-thumb-forest" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                            </span>
                            <div><span class="news-category">PANDUAN SISTEM</span><strong>Pantau koneksi sensor lapangan</strong><p>Periksa catu daya, kabel, dan konektivitas mikrokontroler untuk memulai pemantauan.</p></div>
                        </div>
                        <div class="news-item">
                            <span class="news-thumb news-thumb-leaf" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            </span>
                            <div><span class="news-category">KESIAPSIAGAAN</span><strong>Kenali perubahan kondisi lingkungan</strong><p>Kenaikan suhu drastis dan penurunan kelembapan dapat memicu kerawanan titik api.</p></div>
                        </div>
                        <div class="news-item">
                            <span class="news-thumb news-thumb-earth" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                            </span>
                            <div><span class="news-category">PEMANTAUAN</span><strong>Pastikan lokasi perangkat terdata</strong><p>Penempatan kamera dan sensor pada titik strategis mendukung deteksi dini yang akurat.</p></div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="information-grid" aria-label="Informasi sistem">
                <article class="info-card">
                    <div class="info-card-heading"><span class="info-icon">i</span><h2>Tentang Sistem</h2></div>
                    <p>JALAS merupakan dashboard pemantauan lingkungan terpadu berbasis IoT dan Computer Vision (YOLO11) untuk deteksi dini kebakaran hutan.</p>
                    <a href="#dashboard" class="text-link">Kembali ke ringkasan utama <span aria-hidden="true">→</span></a>
                </article>
                <article class="info-card">
                    <div class="info-card-heading"><span class="info-icon info-icon-green">⌁</span><h2>Perangkat Terhubung</h2></div>
                    <div class="device-summary"><span>Sensor lingkungan aktif</span><strong id="connected-devices-count">0 <small>/ 4 aktif</small></strong></div>
                    <div class="device-progress"><span id="connected-devices-bar"></span></div>
                    <p>Perangkat yang aktif mengirimkan telemetri akan tercatat pada ringkasan ini.</p>
                </article>
                <article class="info-card info-card-notice">
                    <div class="info-card-heading"><span class="info-icon info-icon-red">!</span><h2>Catatan Pemantauan</h2></div>
                    <p id="monitor-note-desc">Data sensor dan indeks risiko belum tersedia. Pastikan perangkat terhubung sebelum menggunakan informasi sebagai acuan lapangan.</p>
                    <span class="notice-label" id="monitor-note-label">MENUNGGU DATA PERANGKAT</span>
                </article>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-brand"><strong>JALAS<span>.</span></strong><span>Forest Fire Early Warning &amp; Monitoring System</span></div>
            <p>Dashboard pemantauan potensi kebakaran hutan <span>·</span> Informasi ditampilkan secara real-time sesuai perangkat yang terhubung.</p>
            <a href="#dashboard" class="back-to-top">Kembali ke atas ↑</a>
        </footer>
    </div>
</body>
</html>