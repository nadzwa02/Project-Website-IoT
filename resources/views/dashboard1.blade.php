<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f0d7">
    <title>JALAS | Sistem Pemantauan Kebakaran Hutan</title>
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
                <span class="topbar-date" data-current-date></span>
                <span class="topbar-time" data-current-time></span>
            </div>

            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                <span></span><span></span><span class="visually-hidden">Buka navigasi</span>
            </button>
        </header>

        <nav class="primary-nav" id="primary-nav" aria-label="Navigasi utama">
            <a class="nav-link is-active" href="#dashboard"><span aria-hidden="true">⌂</span> Dashboard</a>
            <a class="nav-link" href="#peta"><span aria-hidden="true">⌖</span> Peta operasional</a>
            <a class="nav-link" href="#kamera"><span aria-hidden="true">▣</span> Kamera</a>
            <a class="nav-link" href="#sensor"><span aria-hidden="true">⌁</span> Telemetri sensor</a>
            <a class="nav-link" href="#analisis"><span aria-hidden="true">⌁</span> Analisis risiko</a>
            <a class="nav-link" href="#peringatan"><span aria-hidden="true">⚑</span> Peringatan</a>
            <span class="nav-spacer"></span>
            <span class="nav-system"><span></span> SISTEM AKTIF</span>
        </nav>

        <main class="dashboard" id="dashboard">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">PUSAT PEMANTAUAN LINGKUNGAN</p>
                    <h1>Indeks Risiko Kebakaran Hutan</h1>
                    <p class="heading-copy">Pantau kondisi lingkungan, telemetri sensor, dan potensi kebakaran hutan dalam satu dashboard.</p>
                </div>
                <div class="heading-location">
                    <span class="location-icon" aria-hidden="true">⌖</span>
                    <span><strong>AREA PEMANTAUAN</strong><small>Lokasi belum ditentukan</small></span>
                </div>
            </section>

            <section class="system-banner" aria-label="Status sistem">
                <span class="banner-icon" aria-hidden="true">!</span>
                <div class="banner-copy">
                    <strong>MENUNGGU DATA SENSOR</strong>
                    <span>Sistem siap memantau. Data sensor akan ditampilkan setelah perangkat terhubung.</span>
                </div>
                <span class="banner-status"><span></span> SIAGA</span>
            </section>

            <section class="stats-grid" aria-label="Ringkasan kondisi">
                <article class="stat-card stat-temperature">
                    <div class="stat-heading"><span class="stat-icon" aria-hidden="true">°</span><span>SUHU LINGKUNGAN</span></div>
                    <p class="stat-value">--<span>°C</span></p>
                    <p class="stat-foot"><span class="status-dot"></span> Menunggu pembacaan</p>
                </article>
                <article class="stat-card stat-humidity">
                    <div class="stat-heading"><span class="stat-icon" aria-hidden="true">◌</span><span>KELEMBAPAN UDARA</span></div>
                    <p class="stat-value">--<span>%</span></p>
                    <p class="stat-foot"><span class="status-dot"></span> Menunggu pembacaan</p>
                </article>
                <article class="stat-card stat-smoke">
                    <div class="stat-heading"><span class="stat-icon" aria-hidden="true">≋</span><span>DETEKSI GAS / ASAP</span></div>
                    <p class="stat-value">--<span class="stat-unit">ADC</span></p>
                    <p class="stat-foot"><span class="status-dot"></span> Sensor MQ-2 belum aktif</p>
                </article>
                <article class="stat-card stat-camera">
                    <div class="stat-heading"><span class="stat-icon" aria-hidden="true">▣</span><span>STATUS KAMERA</span></div>
                    <p class="stat-value stat-value-text" id="stat-camera-status">BELUM AKTIF</p>
                    <p class="stat-foot" id="stat-camera-foot"><span class="status-dot status-dot-muted"></span> Menunggu koneksi</p>
                </article>
                <article class="stat-card stat-risk">
                    <div class="stat-heading"><span class="stat-icon" aria-hidden="true">◇</span><span>TINGKAT RISIKO</span></div>
                    <p class="stat-value stat-value-text">BELUM DINILAI</p>
                    <p class="stat-foot"><span class="status-dot status-dot-muted"></span> Data belum tersedia</p>
                </article>
            </section>

            <section class="overview-grid">
                <!-- Panel Kamera (Live Stream di Kiri) -->
                <article class="panel camera-panel" id="kamera">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">PENGAWASAN VISUAL</p>
                            <h2>Kamera Hutan</h2>
                        </div>
<<<<<<< HEAD
                        <span class="camera-state" data-camera-state><i></i><span data-camera-state-label>OFFLINE</span></span>
                    </div>
                    <div class="camera-controls">
                        <label for="camera-device">Kamera laptop / USB</label>
                        <select id="camera-device" data-camera-device>
                            <option value="">Kamera default browser</option>
                        </select>
                        <button class="camera-toggle" type="button" data-camera-toggle>Aktifkan kamera</button>
                    </div>
                    <p class="camera-feedback" data-camera-feedback role="status" aria-live="polite">
                        Pilih kamera, lalu tekan Aktifkan kamera dan izinkan akses saat diminta browser.
                    </p>
                    <div class="camera-scene" role="group" aria-label="Pratinjau kamera hutan">
                        <video class="camera-video" data-camera-video aria-label="Tayangan langsung kamera hutan" autoplay muted playsinline hidden></video>
                        <canvas class="camera-detections" data-camera-detections aria-hidden="true" hidden></canvas>
                        <svg data-camera-placeholder viewBox="0 0 520 280" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                            <defs>
                                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#b9c9ad"/><stop offset="1" stop-color="#e4d9ac"/></linearGradient>
                                <linearGradient id="ridge" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#687a54"/><stop offset="1" stop-color="#344b38"/></linearGradient>
                                <linearGradient id="forest" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#617148"/><stop offset="1" stop-color="#243d2b"/></linearGradient>
                            </defs>
                            <rect width="520" height="280" fill="url(#sky)"/>
                            <circle cx="407" cy="57" r="25" fill="#efe4b9" opacity=".8"/>
                            <path d="M0 152 77 91l53 44 72-77 78 87 70-61 67 65 54-41 49 37v135H0Z" fill="#889173" opacity=".7"/>
                            <path d="m0 173 88-64 61 51 77-84 91 89 55-55 65 50 49-41 34 29v132H0Z" fill="url(#ridge)"/>
                            <path d="M0 189q91-44 168-11t155-3 197 8v97H0Z" fill="#9a9564"/>
                            <path d="M0 211q79-38 169-4t174-9 177 1v81H0Z" fill="url(#forest)"/>
                            <g fill="#263d2d">
                                <path d="m27 222 18-61 18 61h-12l18 42H20l18-42Zm70 1 19-72 20 72h-13l18 42H80l18-42Zm278-13 22-70 23 70h-15l21 49h-56l20-49Zm81 4 18-61 20 61h-12l18 42h-50l18-42Z"/>
                            </g>
                            <path d="M0 263q106-19 194 0t178-3 148 0v20H0Z" fill="#1e3426" opacity=".8"/>
                        </svg>
                        <div class="scene-overlay" data-camera-overlay hidden><span class="record-dot"></span> LIVE FEED <span>CAM-01</span></div>
                        <div class="camera-message" data-camera-placeholder-message>
                            <span class="camera-message-icon" aria-hidden="true">▣</span>
                            <strong>Kamera belum terhubung</strong>
                            <small>Gunakan kamera laptop atau kamera USB yang terhubung ke perangkat ini.</small>
                        </div>
                    </div>
                    <div class="camera-caption" data-camera-caption>
                        <span data-camera-caption-status><i></i> Tidak ada sinyal kamera</span>
                        <span data-camera-updated>TERAKHIR DIPERBARUI —</span>
=======
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
                        <video id="live-video" class="camera-video-feed" autoplay playsinline muted></video>
                        <canvas id="live-canvas" class="camera-canvas-overlay"></canvas>

                        <div id="camera-placeholder">
                            <svg viewBox="0 0 520 280" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                                <defs>
                                    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#b9c9ad"/><stop offset="1" stop-color="#e4d9ac"/></linearGradient>
                                    <linearGradient id="ridge" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#687a54"/><stop offset="1" stop-color="#344b38"/></linearGradient>
                                    <linearGradient id="forest" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#617148"/><stop offset="1" stop-color="#243d2b"/></linearGradient>
                                </defs>
                                <rect width="520" height="280" fill="url(#sky)"/>
                                <circle cx="407" cy="57" r="25" fill="#efe4b9" opacity=".8"/>
                                <path d="M0 152 77 91l53 44 72-77 78 87 70-61 67 65 54-41 49 37v135H0Z" fill="#889173" opacity=".7"/>
                                <path d="m0 173 88-64 61 51 77-84 91 89 55-55 65 50 49-41 34 29v132H0Z" fill="url(#ridge)"/>
                                <path d="M0 189q91-44 168-11t155-3 197 8v97H0Z" fill="#9a9564"/>
                                <path d="M0 211q79-38 169-4t174-9 177 1v81H0Z" fill="url(#forest)"/>
                                <g fill="#263d2d">
                                    <path d="m27 222 18-61 18 61h-12l18 42H20l18-42Zm70 1 19-72 20 72h-13l18 42H80l18-42Zm278-13 22-70 23 70h-15l21 49h-56l20-49Zm81 4 18-61 20 61h-12l18 42h-50l18-42Z"/>
                                </g>
                                <path d="M0 263q106-19 194 0t178-3 148 0v20H0Z" fill="#1e3426" opacity=".8"/>
                            </svg>
                            <div class="camera-message">
                                <span class="camera-message-icon" aria-hidden="true">▣</span>
                                <strong>Kamera belum terhubung</strong>
                                <small>Pilih kamera pada menu dropdown lalu klik "Buka Kamera".</small>
                            </div>
                        </div>

                        <div class="scene-overlay">
                            <span class="record-dot" id="live-rec-dot"></span>
                            <span id="live-feed-text">OFFLINE</span>
                            <span id="ai-status-badge" class="ai-badge">AI: STANDBY</span>
                            <span>CAM-01</span>
                        </div>
                    </div>
                    <div class="camera-caption">
                        <span id="camera-footer-status"><i></i> Tidak ada sinyal kamera</span>
                        <span id="camera-last-update">TERAKHIR DIPERBARUI —</span>
>>>>>>> d23f08c4ea981de28795f8809ed1d91216b3a7a3
                    </div>
                </article>

                <!-- Panel Peta (Peta Taktis di Kanan) -->
                <article class="panel map-panel" id="peta">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">PEMANTAUAN WILAYAH</p>
                            <h2>Peta Taktis Operasional</h2>
                        </div>
                        <span class="panel-tag"><span class="status-dot status-dot-muted"></span> 03 TITIK</span>
                    </div>

                    <div class="map-canvas" role="img" aria-label="Ilustrasi area pemantauan dengan tiga titik sensor">
                        <div class="map-label map-label-top">AREA PEMANTAUAN · ILUSTRASI</div>
                        <svg class="map-art" viewBox="0 0 760 310" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                            <path class="map-contour" d="M-20 62C95 13 140 107 249 65S431 6 537 64s158 31 246-4M-32 119c104-49 171 40 270 3s165-67 258-8 168 41 290-9M-14 189c110-54 159 32 262-4s178-61 268-7 160 50 260 2M-18 252c114-49 184 28 277-9s174-49 268-4 168 37 248-3" />
                            <path class="map-water" d="M-15 265c95-18 140-60 225-43s122 42 195 12 111-72 190-67 108 33 190 6" />
                            <path class="map-road road-main" d="M43 40c94 45 113 68 204 91s128 28 196 69 102 57 178 63 107 1 152-21" />
                            <path class="map-road road-secondary" d="M91 290c35-66 77-89 149-119s113-31 173-61 83-53 137-84" />
                            <path class="map-road road-secondary" d="M4 155c73 2 118-10 176-45s81-61 114-107" />
                            <g class="map-pin" transform="translate(252 131)"><circle r="16"/><circle class="pin-center" r="5"/><text y="34">SENSOR 01</text></g>
                            <g class="map-pin map-pin-alert" transform="translate(472 202)"><circle r="19"/><circle class="pin-center" r="6"/><text y="38">TITIK PANTAU</text></g>
                            <g class="map-pin" transform="translate(586 95)"><circle r="16"/><circle class="pin-center" r="5"/><text y="34">SENSOR 02</text></g>
                            <g class="map-compass" transform="translate(708 49)"><text x="0" y="-13">U</text><path d="M0-8 6 7 0 4-6 7Z"/></g>
                        </svg>
                        <div class="map-scale"><span></span><small>Ilustrasi · lokasi belum terhubung</small></div>
                        <div class="map-legend"><span><i class="legend-sensor"></i> Sensor</span><span><i class="legend-center"></i> Titik pantau</span></div>
                    </div>
                    <section class="map-detection-status is-idle" data-detection-status role="status" aria-live="polite">
                        <span class="detection-status-icon" data-detection-status-icon aria-hidden="true">!</span>
                        <div class="detection-status-copy">
                            <p class="eyebrow">HASIL DETEKSI KAMERA</p>
                            <h3 data-detection-status-title>Kamera belum aktif</h3>
                            <p data-detection-status-detail>Aktifkan kamera untuk memulai deteksi api dan asap otomatis.</p>
                        </div>
                        <span class="detection-status-time" data-detection-status-time>MENUNGGU</span>
                    </section>
                    <div class="panel-foot"><span>⌖ Lokasi perangkat akan muncul saat sensor terhubung</span><span class="foot-muted">PETA ILUSTRATIF</span></div>
                </article>
            </section>

            <section class="panel telemetry-panel" id="sensor">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">STATUS PERANGKAT LAPANGAN</p>
                        <h2>Telemetri Sensor &amp; Konektivitas</h2>
                    </div>
                    <span class="panel-tag"><span class="status-dot status-dot-muted"></span> MENUNGGU DATA</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>PERANGKAT</th><th>PARAMETER</th><th>PEMBACAAN</th><th>STATUS</th><th>PEMBARUAN TERAKHIR</th><th>KONEKSI</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="device-icon">T</span><strong>DHT11 <small>SENSOR 01</small></strong></td>
                                <td>Suhu lingkungan</td><td class="reading">-- <small>°C</small></td>
                                <td><span class="table-badge badge-pending">MENUNGGU DATA</span></td><td class="table-muted">Belum tersedia</td><td><span class="connection"><i></i> Offline</span></td>
                            </tr>
                            <tr>
                                <td><span class="device-icon">H</span><strong>DHT11 <small>SENSOR 01</small></strong></td>
                                <td>Kelembapan udara</td><td class="reading">-- <small>%</small></td>
                                <td><span class="table-badge badge-pending">MENUNGGU DATA</span></td><td class="table-muted">Belum tersedia</td><td><span class="connection"><i></i> Offline</span></td>
                            </tr>
                            <tr>
                                <td><span class="device-icon device-icon-red">M</span><strong>MQ-2 <small>SENSOR 02</small></strong></td>
                                <td>Konsentrasi gas / asap</td><td class="reading">-- <small>ADC</small></td>
                                <td><span class="table-badge badge-pending">MENUNGGU DATA</span></td><td class="table-muted">Belum tersedia</td><td><span class="connection"><i></i> Offline</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-note"><span><i></i> Menampilkan 3 perangkat terdaftar</span><span>Data diperbarui otomatis saat perangkat terhubung</span></div>
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
                    <div class="chart-legend"><span><i class="chart-line chart-line-temperature"></i> Suhu</span><span><i class="chart-line chart-line-humidity"></i> Kelembapan</span><span><i class="chart-line chart-line-risk"></i> Indeks risiko</span></div>
                    <div class="chart-wrap" role="img" aria-label="Grafik tren belum tersedia karena sensor belum mengirimkan data">
                        <svg class="risk-chart" viewBox="0 0 760 220" preserveAspectRatio="none" aria-hidden="true">
                            <g class="chart-grid"><path d="M48 20H748M48 62H748M48 104H748M48 146H748M48 188H748"/><path d="M48 20V188M188 20V188M328 20V188M468 20V188M608 20V188M748 20V188"/></g>
                            <g class="chart-axis"><text x="4" y="24">100</text><text x="14" y="66">75</text><text x="14" y="108">50</text><text x="14" y="150">25</text><text x="27" y="192">0</text><text x="48" y="211">00:00</text><text x="188" y="211">06:00</text><text x="328" y="211">12:00</text><text x="468" y="211">18:00</text><text x="712" y="211">24:00</text></g>
                            <path class="chart-placeholder" d="M48 104H748"/>
                            <text class="chart-empty" x="398" y="96" text-anchor="middle">Menunggu data sensor untuk menampilkan grafik</text>
                            <text class="chart-empty-sub" x="398" y="116" text-anchor="middle">Grafik akan diperbarui otomatis saat data tersedia</text>
                        </svg>
                    </div>
                    <div class="chart-foot"><span>SUMBER: SENSOR LINGKUNGAN</span><span>PERIODE: 24 JAM</span></div>
                </article>

                <aside class="analysis-aside">
                    <article class="panel risk-score">
                        <div class="panel-heading">
                            <div><p class="eyebrow">INDEKS SAAT INI</p><h2>Skor Risiko</h2></div>
                            <span class="info-mark" title="Skor dihitung setelah data sensor tersedia">i</span>
                        </div>
                        <div class="score-content">
                            <div class="score-ring"><span>--</span><small>/ 100</small></div>
                            <div class="score-label"><strong>Belum dinilai</strong><span>Menunggu data sensor</span></div>
                        </div>
                        <div class="score-scale"><span>RENDAH</span><span>SEDANG</span><span>TINGGI</span></div>
                    </article>
                    <article class="panel insight-card">
                        <div class="insight-icon" aria-hidden="true">⌁</div>
                        <div><p class="eyebrow">RINGKASAN KONDISI</p><strong>Belum ada analisis</strong><p>Hubungkan sensor untuk mendapatkan ringkasan kondisi area.</p></div>
                    </article>
                </aside>
            </section>

            <section class="updates-grid" id="peringatan">
                <article class="panel alerts-panel">
                    <div class="panel-heading">
                        <div><p class="eyebrow">AKTIVITAS SISTEM</p><h2>Log Peringatan Dini</h2></div>
                        <span class="panel-tag">HARI INI</span>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item"><span class="activity-icon activity-icon-green">✓</span><div><strong>Sistem pemantauan siap</strong><p>Dashboard berhasil dimuat dan siap menerima data.</p></div><time>SEKARANG</time></div>
                        <div class="activity-item"><span class="activity-icon activity-icon-amber">⌁</span><div><strong>Sensor belum mengirimkan data</strong><p>Pastikan perangkat dan koneksi sensor telah aktif.</p></div><time>MENUNGGU</time></div>
                        <div class="activity-item"><span class="activity-icon activity-icon-muted">▣</span><div><strong>Kamera belum terhubung</strong><p>Siaran kamera akan tampil saat perangkat tersedia.</p></div><time>MENUNGGU</time></div>
                    </div>
                    <a class="text-link" href="#sensor">Lihat status seluruh perangkat <span aria-hidden="true">→</span></a>
                </article>

                <article class="panel news-panel">
                    <div class="panel-heading">
                        <div><p class="eyebrow">INFORMASI LAPANGAN</p><h2>Pembaruan &amp; Informasi</h2></div>
                        <span class="panel-tag">JALAS</span>
                    </div>
                    <div class="news-list">
                        <div class="news-item"><span class="news-thumb news-thumb-forest" aria-hidden="true">♧</span><div><span class="news-category">PANDUAN SISTEM</span><strong>Pantau koneksi sensor lapangan</strong><p>Periksa daya, kabel, dan konektivitas perangkat untuk memulai pemantauan.</p></div></div>
                        <div class="news-item"><span class="news-thumb news-thumb-leaf" aria-hidden="true">♧</span><div><span class="news-category">KESIAPSIAGAAN</span><strong>Kenali perubahan kondisi lingkungan</strong><p>Data sensor membantu memberikan gambaran kondisi area pantau.</p></div></div>
                        <div class="news-item"><span class="news-thumb news-thumb-earth" aria-hidden="true">⌖</span><div><span class="news-category">PEMANTAUAN</span><strong>Pastikan lokasi perangkat terdata</strong><p>Penempatan sensor yang tepat mendukung pemantauan yang lebih baik.</p></div></div>
                    </div>
                </article>
            </section>

            <section class="information-grid" aria-label="Informasi sistem">
                <article class="info-card">
                    <div class="info-card-heading"><span class="info-icon">i</span><h2>Tentang Sistem</h2></div>
                    <p>JALAS merupakan dashboard pemantauan lingkungan untuk membantu pengawasan dini potensi kebakaran hutan.</p>
                    <a href="#dashboard" class="text-link">Informasi sistem <span aria-hidden="true">→</span></a>
                </article>
                <article class="info-card">
                    <div class="info-card-heading"><span class="info-icon info-icon-green">⌁</span><h2>Perangkat Terhubung</h2></div>
                    <div class="device-summary"><span>Sensor lingkungan</span><strong>0 <small>/ 3 aktif</small></strong></div>
                    <div class="device-progress"><span></span></div>
                    <p>Perangkat yang aktif akan tercatat di ringkasan ini.</p>
                </article>
                <article class="info-card info-card-notice">
                    <div class="info-card-heading"><span class="info-icon info-icon-red">!</span><h2>Catatan Pemantauan</h2></div>
                    <p>Data dan tingkat risiko belum tersedia. Pastikan perangkat sensor terhubung sebelum menggunakan informasi untuk pemantauan lapangan.</p>
                    <span class="notice-label">MENUNGGU DATA PERANGKAT</span>
                </article>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-brand"><strong>JALAS<span>.</span></strong><span>Forest Fire Early Warning &amp; Monitoring System</span></div>
            <p>Dashboard pemantauan potensi kebakaran hutan <span>·</span> Informasi ditampilkan sesuai data perangkat yang tersedia.</p>
            <a href="#dashboard" class="back-to-top">Kembali ke atas ↑</a>
        </footer>
    </div>
</body>
</html>