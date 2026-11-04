/**
 * Live Camera & YOLO11 Object Detection Module (Optimized + Fullscreen)
 */
export function initializeLiveCamera() {
    const video = document.getElementById('live-video');
    const canvas = document.getElementById('live-canvas');
    const toggleBtn = document.getElementById('btn-toggle-camera');
    const fullscreenBtn = document.getElementById('btn-fullscreen-camera');
    const cameraContainer = document.getElementById('camera-container');
    const cameraSelect = document.getElementById('camera-device-select');
    const placeholder = document.getElementById('camera-placeholder');
    const cameraBadge = document.getElementById('camera-badge');
    const recDot = document.getElementById('live-rec-dot');
    const feedText = document.getElementById('live-feed-text');
    const aiStatusBadge = document.getElementById('ai-status-badge');
    const footerStatus = document.getElementById('camera-footer-status');
    const lastUpdate = document.getElementById('camera-last-update');
    const statCameraStatus = document.getElementById('stat-camera-status');
    const statCameraFoot = document.getElementById('stat-camera-foot');

    if (!video || !toggleBtn) return;

    let mediaStream = null;
    let isPredicting = false;
    let isCameraRunning = false;
    let offscreenCanvas = null;
    let offscreenCtx = null;
    const FLASK_API_URL = 'http://127.0.0.1:5000/api/predict';

    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    });

    // Fitur Fullscreen
    if (fullscreenBtn && cameraContainer) {
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                if (cameraContainer.requestFullscreen) {
                    cameraContainer.requestFullscreen();
                } else if (cameraContainer.webkitRequestFullscreen) {
                    cameraContainer.webkitRequestFullscreen();
                } else if (cameraContainer.msRequestFullscreen) {
                    cameraContainer.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });

        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                fullscreenBtn.textContent = '⛶ Keluar Fullscreen';
            } else {
                fullscreenBtn.textContent = '⛶ Layar Penuh';
            }
        });
    }

    // Populate daftar perangkat kamera yang tersedia
    async function loadCameraDevices() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices || !cameraSelect) {
            return;
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');

            const currentSelected = cameraSelect.value;
            cameraSelect.innerHTML = '';

            if (videoDevices.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Tidak ada kamera ditemukan';
                cameraSelect.appendChild(opt);
                return;
            }

            videoDevices.forEach((device, index) => {
                const opt = document.createElement('option');
                opt.value = device.deviceId;
                opt.textContent = device.label || `Kamera ${index + 1}`;
                if (device.deviceId === currentSelected) {
                    opt.selected = true;
                }
                cameraSelect.appendChild(opt);
            });
        } catch (err) {
            console.warn('Gagal memuat daftar perangkat kamera:', err);
        }
    }

    if (cameraSelect) {
        cameraSelect.addEventListener('change', async () => {
            if (mediaStream) {
                stopCameraTracksOnly();
                await startCamera(cameraSelect.value);
            }
        });
    }

    toggleBtn.addEventListener('click', async () => {
        if (isCameraRunning) {
            stopCamera();
        } else {
            await startCamera(cameraSelect ? cameraSelect.value : null);
        }
    });

    async function startCamera(deviceId = null) {
        try {
            toggleBtn.disabled = true;
            toggleBtn.textContent = 'Menghubungkan...';

            const videoConstraints = {
                width: { ideal: 640 },
                height: { ideal: 480 }
            };

            if (deviceId) {
                videoConstraints.deviceId = { exact: deviceId };
            }

            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: videoConstraints,
                audio: false
            });

            video.srcObject = mediaStream;
            await video.play();

            isCameraRunning = true;

            offscreenCanvas = document.createElement('canvas');
            offscreenCanvas.width = 320;
            offscreenCanvas.height = 240;
            offscreenCtx = offscreenCanvas.getContext('2d');

            await loadCameraDevices();

            video.classList.remove('hidden');
            video.style.display = 'block';
            canvas.classList.remove('hidden');
            canvas.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';

            toggleBtn.disabled = false;
            toggleBtn.textContent = '⏹ Matikan Kamera';
            toggleBtn.classList.add('is-active');

            if (cameraBadge) {
                cameraBadge.innerHTML = '<i></i> ONLINE';
                cameraBadge.classList.add('camera-badge-online');
            }

            if (recDot) recDot.classList.add('record-dot-active');
            if (feedText) feedText.textContent = 'LIVE FEED';
            if (footerStatus) footerStatus.innerHTML = '<i></i> Kamera terhubung (Live)';
            if (statCameraStatus) statCameraStatus.textContent = 'ONLINE';
            if (statCameraFoot) {
                statCameraFoot.innerHTML = '<span class="status-dot status-dot-green"></span> Live Stream Aktif';
            }

            scheduleNextPrediction();

        } catch (err) {
            console.error('Gagal mengakses kamera:', err);
            alert('Tidak dapat mengakses kamera: ' + err.message);
            stopCamera();
            toggleBtn.disabled = false;
            toggleBtn.textContent = '📷 Buka Kamera';
        }
    }

    function stopCameraTracksOnly() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
    }

    function stopCamera() {
        isCameraRunning = false;
        stopCameraTracksOnly();

        video.pause();
        video.srcObject = null;
        video.style.display = 'none';
        canvas.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';

        toggleBtn.disabled = false;
        toggleBtn.textContent = '📷 Buka Kamera';
        toggleBtn.classList.remove('is-active');

        if (cameraBadge) {
            cameraBadge.innerHTML = '<i></i> OFFLINE';
            cameraBadge.classList.remove('camera-badge-online');
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

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function scheduleNextPrediction() {
        if (!isCameraRunning) return;
        setTimeout(async () => {
            if (!isCameraRunning) return;
            await captureAndPredict();
            scheduleNextPrediction();
        }, 150);
    }

    async function captureAndPredict() {
        if (isPredicting || !isCameraRunning || video.videoWidth === 0) return;
        isPredicting = true;

        try {
            if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
            }

            offscreenCtx.drawImage(video, 0, 0, offscreenCanvas.width, offscreenCanvas.height);

            const blob = await new Promise(resolve => offscreenCanvas.toBlob(resolve, 'image/jpeg', 0.65));
            if (!blob) {
                isPredicting = false;
                return;
            }

            const formData = new FormData();
            formData.append('image', blob, 'frame.jpg');
            formData.append('confidence', '0.30');
            formData.append('imgsz', '320');

            const response = await fetch(FLASK_API_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }

            const data = await response.json();
            drawDetections(data);

            if (lastUpdate) {
                const now = new Date();
                lastUpdate.textContent = `TERAKHIR DIPERBARUI — ${timeFormatter.format(now)} WIB`;
            }

        } catch (err) {
            if (aiStatusBadge) {
                aiStatusBadge.className = 'ai-badge ai-badge-error';
                aiStatusBadge.textContent = 'AI: OFFLINE (Flask?)';
            }
        } finally {
            isPredicting = false;
        }
    }

    function drawDetections(data) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const detections = data.detections || [];
        const status = data.status || 'safe';

        if (aiStatusBadge) {
            if (status === 'fire') {
                aiStatusBadge.className = 'ai-badge ai-badge-fire';
                aiStatusBadge.textContent = `🔥 API: FIRE (${data.summary?.fire || 1})`;
            } else if (status === 'smoke') {
                aiStatusBadge.className = 'ai-badge ai-badge-smoke';
                aiStatusBadge.textContent = `💨 API: SMOKE (${data.summary?.smoke || 1})`;
            } else {
                aiStatusBadge.className = 'ai-badge ai-badge-safe';
                aiStatusBadge.textContent = 'AI: NORMAL (AMAN)';
            }
        }

        const scaleX = video.videoWidth / offscreenCanvas.width;
        const scaleY = video.videoHeight / offscreenCanvas.height;

        detections.forEach(det => {
            const x1 = det.bbox.x1 * scaleX;
            const y1 = det.bbox.y1 * scaleY;
            const x2 = det.bbox.x2 * scaleX;
            const y2 = det.bbox.y2 * scaleY;
            const width = x2 - x1;
            const height = y2 - y1;
            const isFire = det.class_name.toLowerCase().includes('fire');

            const color = isFire ? '#ff2a00' : '#ffa500';

            ctx.lineWidth = 3;
            ctx.strokeStyle = color;
            ctx.strokeRect(x1, y1, width, height);

            const label = `${det.class_name.toUpperCase()} ${(det.confidence * 100).toFixed(0)}%`;
            ctx.font = 'bold 14px Inter, sans-serif';
            const textWidth = ctx.measureText(label).width;

            ctx.fillStyle = color;
            ctx.fillRect(x1, Math.max(0, y1 - 22), textWidth + 10, 22);

            ctx.fillStyle = '#ffffff';
            ctx.fillText(label, x1 + 5, Math.max(16, y1 - 6));
        });
    }

    loadCameraDevices();
}
