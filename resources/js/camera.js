/**
 * Shared Live Stream & YOLO11 Object Detection Module
 * Semua pengunjung melihat live stream dan status deteksi yang sama dari server Python.
 */
export function initializeLiveCamera() {
    const streamImg = document.getElementById('live-stream');
    const video = document.getElementById('live-video');
    const canvas = document.getElementById('live-canvas');
    const toggleBtn = document.getElementById('btn-toggle-camera');
    const fullscreenBtn = document.getElementById('btn-fullscreen-camera');
    const cameraContainer = document.getElementById('camera-container');
    const cameraSelect = document.getElementById('camera-device-select');
    const placeholder = document.getElementById('camera-placeholder');
    const cameraBadge = document.getElementById('camera-badge');
    const cameraCaption = document.querySelector('.camera-caption');
    const recDot = document.getElementById('live-rec-dot');
    const feedText = document.getElementById('live-feed-text');
    const aiStatusBadge = document.getElementById('ai-status-badge');
    const footerStatus = document.getElementById('camera-footer-status');
    const lastUpdate = document.getElementById('camera-last-update');
    const statCameraStatus = document.getElementById('stat-camera-status');
    const statCameraFoot = document.getElementById('stat-camera-foot');

    // Elemen Hasil Deteksi di Bawah Maps
    const detectionStatus = document.querySelector('[data-detection-status]');
    const detectionStatusIcon = document.querySelector('[data-detection-status-icon]');
    const detectionStatusTitle = document.querySelector('[data-detection-status-title]');
    const detectionStatusDetail = document.querySelector('[data-detection-status-detail]');
    const detectionStatusTime = document.querySelector('[data-detection-status-time]');

    // Elemen Log Peringatan Dini
    const activityCameraIcon = document.getElementById('activity-camera-icon');
    const activityCameraTitle = document.getElementById('activity-camera-title');
    const activityCameraDesc = document.getElementById('activity-camera-desc');
    const activityCameraTime = document.getElementById('activity-camera-time');

    if (!streamImg || !toggleBtn) return;

    const host = window.location.hostname || '127.0.0.1';
    const API_BASE = `http://${host}:5000`;

    let isStreamActive = false;
    let currentCameraIndex = 0;
    let isToggling = false;

    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    });

    // Pastikan video/canvas lama disembunyikan agar tidak merusak tampilan awal
    if (video) video.hidden = true;
    if (canvas) canvas.hidden = true;

    function updateMapDetectionStatusBar(state, title, detail, timeText = null) {
        if (!detectionStatus) return;

        detectionStatus.className = `map-detection-status is-${state}`;
        if (detectionStatusIcon) {
            detectionStatusIcon.textContent = {
                idle: '!',
                checking: '…',
                clear: '✓',
                smoke: '!',
                fire: '!',
                error: '×',
            }[state] || '!';
        }

        if (detectionStatusTitle) detectionStatusTitle.textContent = title;
        if (detectionStatusDetail) detectionStatusDetail.textContent = detail;
        if (detectionStatusTime) {
            if (timeText) {
                detectionStatusTime.textContent = timeText;
            } else {
                detectionStatusTime.textContent = state === 'idle'
                    ? 'MENUNGGU'
                    : `DIPERBARUI ${timeFormatter.format(new Date())} WIB`;
            }
        }
    }

    function updateActivityLog(state, fireCount = 0, smokeCount = 0) {
        if (!activityCameraTitle || !activityCameraDesc || !activityCameraIcon || !activityCameraTime) {
            return;
        }

        if (state === 'offline') {
            activityCameraIcon.className = 'activity-icon activity-icon-muted';
            activityCameraIcon.textContent = '▣';
            activityCameraTitle.textContent = 'Kamera belum terhubung';
            activityCameraDesc.textContent = 'Siaran kamera akan tampil saat perangkat tersedia.';
            activityCameraTime.textContent = 'MENUNGGU';
        } else if (state === 'fire') {
            activityCameraIcon.className = 'activity-icon activity-icon-amber';
            activityCameraIcon.textContent = '!';
            activityCameraTitle.textContent = `Peringatan: ${fireCount} Titik Api Terdeteksi`;
            activityCameraDesc.textContent = 'Kamera mendeteksi adanya titik api pada area pemantauan.';
            activityCameraTime.textContent = 'DARURAT';
        } else if (state === 'smoke') {
            activityCameraIcon.className = 'activity-icon activity-icon-amber';
            activityCameraIcon.textContent = '!';
            activityCameraTitle.textContent = `Waspada: ${smokeCount} Kepulan Asap Terdeteksi`;
            activityCameraDesc.textContent = 'Kamera mendeteksi indikasi asap pada area pemantauan.';
            activityCameraTime.textContent = 'SIAGA';
        } else {
            activityCameraIcon.className = 'activity-icon activity-icon-green';
            activityCameraIcon.textContent = '✓';
            activityCameraTitle.textContent = 'Kamera pemantauan aktif (Live)';
            activityCameraDesc.textContent = 'Siaran langsung dan deteksi AI berjalan normal.';
            activityCameraTime.textContent = 'AKTIF';
        }
    }

    // Fitur Fullscreen
    if (fullscreenBtn && cameraContainer) {
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                if (cameraContainer.requestFullscreen) {
                    cameraContainer.requestFullscreen();
                } else if (cameraContainer.webkitRequestFullscreen) {
                    cameraContainer.webkitRequestFullscreen();
                }
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        });

        document.addEventListener('fullscreenchange', () => {
            fullscreenBtn.textContent = document.fullscreenElement
                ? '⛶ Keluar Fullscreen'
                : '⛶ Layar Penuh';
        });
    }

    // Muat daftar kamera yang terhubung ke Server Python
    async function loadServerCameras() {
        if (!cameraSelect) return;

        try {
            const res = await fetch(`${API_BASE}/api/cameras`);
            if (!res.ok) throw new Error('Gagal memuat kamera');
            const data = await res.json();
            const cameras = data.cameras || [];

            cameraSelect.innerHTML = '';
            cameras.forEach((cam) => {
                const opt = document.createElement('option');
                opt.value = String(cam.index);
                opt.textContent = cam.label;
                if (Number(cam.index) === Number(data.active_camera)) {
                    opt.selected = true;
                }
                cameraSelect.appendChild(opt);
            });
        } catch {
            if (cameraSelect.options.length <= 1) {
                cameraSelect.innerHTML = `
                    <option value="0">Kamera 0 - Default / Internal</option>
                    <option value="1">Kamera 1 - USB / Eksternal</option>
                `;
            }
        }
    }

    // Ganti kamera saat dropdown diubah
    if (cameraSelect) {
        cameraSelect.addEventListener('change', async () => {
            const selectedIdx = Number(cameraSelect.value || 0);
            if (isStreamActive) {
                await startSharedStream(selectedIdx);
            }
        });
    }

    // Tombol Buka / Matikan Kamera
    toggleBtn.addEventListener('click', async () => {
        if (isToggling) return;
        if (isStreamActive) {
            await stopSharedStream();
        } else {
            const selectedIdx = cameraSelect ? Number(cameraSelect.value || 0) : 0;
            await startSharedStream(selectedIdx);
        }
    });

    async function startSharedStream(cameraIndex = 0) {
        try {
            isToggling = true;
            toggleBtn.disabled = true;
            toggleBtn.textContent = 'Menghubungkan...';
            updateMapDetectionStatusBar('checking', 'Menghubungkan kamera...', 'Membuka live stream dari server AI YOLO11...');

            const res = await fetch(`${API_BASE}/api/stream/start`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ camera_index: cameraIndex }),
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal membuka kamera di server.');
            }

            applyStreamState(data, true);
        } catch (err) {
            console.error('Gagal memulai stream:', err);
            alert('Gagal mengaktifkan kamera: ' + (err.message || 'Pastikan server Python Flask berjalan di port 5000.'));
            updateMapDetectionStatusBar(
                'error',
                'Gagal Membuka Kamera',
                err.message || 'Pastikan python_api/app.py sudah dijalankan.'
            );
        } finally {
            isToggling = false;
            toggleBtn.disabled = false;
        }
    }

    async function stopSharedStream() {
        try {
            isToggling = true;
            toggleBtn.disabled = true;
            await fetch(`${API_BASE}/api/stream/stop`, { method: 'POST' });
            applyOfflineUI();
        } catch (err) {
            console.error('Gagal menghentikan stream:', err);
        } finally {
            isToggling = false;
            toggleBtn.disabled = false;
        }
    }

    function applyOfflineUI() {
        isStreamActive = false;

        streamImg.removeAttribute('src');
        streamImg.hidden = true;
        if (placeholder) placeholder.hidden = false;

        toggleBtn.textContent = '📷 Buka Kamera';
        toggleBtn.classList.remove('is-active');

        if (cameraBadge) {
            cameraBadge.innerHTML = '<i></i> OFFLINE';
            cameraBadge.classList.remove('camera-badge-online', 'is-active');
        }
        if (cameraCaption) {
            cameraCaption.classList.remove('is-active');
        }

        if (recDot) recDot.classList.remove('record-dot-active');
        if (feedText) feedText.textContent = 'OFFLINE';
        if (aiStatusBadge) {
            aiStatusBadge.className = 'ai-badge';
            aiStatusBadge.textContent = 'AI: STANDBY';
        }
        if (footerStatus) footerStatus.innerHTML = '<i></i> Tidak ada sinyal kamera';
        if (statCameraStatus) statCameraStatus.textContent = 'BELUM AKTIF';
        if (statCameraFoot) {
            statCameraFoot.innerHTML = '<span class="status-dot status-dot-muted"></span> Menunggu koneksi';
        }

        updateMapDetectionStatusBar(
            'idle',
            'Kamera belum aktif',
            'Aktifkan kamera untuk memulai deteksi api dan asap otomatis.'
        );
        updateActivityLog('offline');
    }

    function applyStreamState(data, forceReloadStream = false) {
        if (!data.running) {
            if (isStreamActive) {
                applyOfflineUI();
            }
            return;
        }

        const switchedCamera = currentCameraIndex !== Number(data.camera_index);
        currentCameraIndex = Number(data.camera_index || 0);

        if (cameraSelect && String(cameraSelect.value) !== String(currentCameraIndex)) {
            cameraSelect.value = String(currentCameraIndex);
        }

        // Tampilkan MJPEG live stream jika baru aktif atau ganti kamera
        if (!isStreamActive || forceReloadStream || switchedCamera) {
            isStreamActive = true;
            if (placeholder) placeholder.hidden = true;
            streamImg.hidden = false;
            streamImg.src = `${API_BASE}/api/stream?t=${Date.now()}`;
        }

        toggleBtn.textContent = '⏹ Matikan Kamera';
        toggleBtn.classList.add('is-active');

        if (cameraBadge) {
            cameraBadge.innerHTML = '<i></i> ONLINE';
            cameraBadge.classList.add('camera-badge-online', 'is-active');
        }
        if (cameraCaption) {
            cameraCaption.classList.add('is-active');
        }

        if (recDot) recDot.classList.add('record-dot-active');
        if (feedText) feedText.textContent = 'LIVE STREAM';
        if (footerStatus) footerStatus.innerHTML = '<i></i> Kamera terhubung (Live Stream Bersama)';
        if (statCameraStatus) statCameraStatus.textContent = 'ONLINE';
        if (statCameraFoot) {
            statCameraFoot.innerHTML = '<span class="status-dot status-dot-green"></span> Live Stream Aktif';
        }

        const timeStr = data.updated_at ? `${data.updated_at} WIB` : `${timeFormatter.format(new Date())} WIB`;
        if (lastUpdate) {
            lastUpdate.textContent = `TERAKHIR DIPERBARUI — ${timeStr}`;
        }

        const status = data.status || 'safe';
        const fireCount = data.summary?.fire || 0;
        const smokeCount = data.summary?.smoke || 0;

        if (aiStatusBadge) {
            if (status === 'fire') {
                aiStatusBadge.className = 'ai-badge ai-badge-fire';
                aiStatusBadge.textContent = `🔥 API: FIRE (${fireCount})`;
            } else if (status === 'smoke') {
                aiStatusBadge.className = 'ai-badge ai-badge-smoke';
                aiStatusBadge.textContent = `💨 API: SMOKE (${smokeCount})`;
            } else {
                aiStatusBadge.className = 'ai-badge ai-badge-safe';
                aiStatusBadge.textContent = 'AI: NORMAL (AMAN)';
            }
        }

        if (status === 'fire') {
            updateMapDetectionStatusBar(
                'fire',
                '🔥 Terdeteksi Titik Api!',
                `Kamera mendeteksi ${fireCount} titik api secara real-time melalui model YOLO11.`,
                `DIPERBARUI ${timeStr}`
            );
            updateActivityLog('fire', fireCount, smokeCount);
        } else if (status === 'smoke') {
            updateMapDetectionStatusBar(
                'smoke',
                '💨 Terdeteksi Kepulan Asap!',
                `Kamera mendeteksi ${smokeCount} indikasi kepulan asap pada area pantau.`,
                `DIPERBARUI ${timeStr}`
            );
            updateActivityLog('smoke', fireCount, smokeCount);
        } else {
            updateMapDetectionStatusBar(
                'clear',
                '✓ Area Terpantau Aman (Kamera Aktif)',
                'Live stream kamera berjalan normal. Tidak terdeteksi api maupun kepulan asap.',
                `DIPERBARUI ${timeStr}`
            );
            updateActivityLog('safe');
        }
    }

    async function pollStreamStatus() {
        if (isToggling) return;

        try {
            const res = await fetch(`${API_BASE}/api/stream/status`);
            if (!res.ok) throw new Error('Status error');
            const data = await res.json();
            applyStreamState(data, false);
        } catch {
            // Jika server Flask belum menyala
            if (isStreamActive) {
                applyOfflineUI();
            }
        }
    }

    // Jika stream gambar terputus sementara, coba muat ulang jika status masih running
    streamImg.addEventListener('error', () => {
        if (isStreamActive) {
            setTimeout(() => {
                if (isStreamActive) {
                    streamImg.src = `${API_BASE}/api/stream?t=${Date.now()}`;
                }
            }, 1500);
        }
    });

    // Inisialisasi awal
    applyOfflineUI();
    loadServerCameras();
    pollStreamStatus();
    window.setInterval(pollStreamStatus, 1000);
}
