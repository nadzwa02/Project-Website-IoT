import { initializeLiveCamera } from './camera.js';

const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
});

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    timeZone: 'Asia/Jakarta',
});

function updateDashboardClock() {
    const now = new Date();
    const time = document.querySelector('[data-current-time]');
    const date = document.querySelector('[data-current-date]');

    if (time) {
        time.textContent = `${timeFormatter.format(now)} WIB`;
    }

    if (date) {
        date.textContent = dateFormatter.format(now);
    }
}

function initializeDashboardNavigation() {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#primary-nav');

    if (!toggle || !navigation) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', String(!isOpen));
        navigation.classList.toggle('is-open', !isOpen);
    });

    navigation.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('.nav-link')) {
            toggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
        }
    });
}

<<<<<<< HEAD
function initializeForestCamera() {
    const cameraState = document.querySelector('[data-camera-state]');
    const cameraStateLabel = document.querySelector('[data-camera-state-label]');
    const cameraDevice = document.querySelector('[data-camera-device]');
    const cameraToggle = document.querySelector('[data-camera-toggle]');
    const cameraFeedback = document.querySelector('[data-camera-feedback]');
    const cameraVideo = document.querySelector('[data-camera-video]');
    const cameraDetections = document.querySelector('[data-camera-detections]');
    const detectionStatus = document.querySelector('[data-detection-status]');
    const detectionStatusIcon = document.querySelector('[data-detection-status-icon]');
    const detectionStatusTitle = document.querySelector('[data-detection-status-title]');
    const detectionStatusDetail = document.querySelector('[data-detection-status-detail]');
    const detectionStatusTime = document.querySelector('[data-detection-status-time]');
    const cameraPlaceholder = document.querySelector('[data-camera-placeholder]');
    const cameraPlaceholderMessage = document.querySelector('[data-camera-placeholder-message]');
    const cameraOverlay = document.querySelector('[data-camera-overlay]');
    const cameraCaption = document.querySelector('[data-camera-caption]');
    const cameraCaptionStatus = document.querySelector('[data-camera-caption-status]');
    const cameraUpdated = document.querySelector('[data-camera-updated]');
    const detectionApiBaseUrl = 'http://127.0.0.1:8001';
    const detectionConfidence = 0.25;

    if (
        !cameraState
        || !cameraStateLabel
        || !cameraDevice
        || !cameraToggle
        || !cameraFeedback
        || !cameraVideo
        || !cameraDetections
        || !detectionStatus
        || !detectionStatusIcon
        || !detectionStatusTitle
        || !detectionStatusDetail
        || !detectionStatusTime
        || !cameraPlaceholder
        || !cameraPlaceholderMessage
        || !cameraOverlay
        || !cameraCaption
        || !cameraCaptionStatus
        || !cameraUpdated
    ) {
        return;
    }

    const cameraApi = navigator.mediaDevices;
    let activeStream = null;
    let detectionEnabled = false;
    let detectionTimer = null;
    let detectionController = null;
    const detectionInterval = 1000;
    const detectionRetryInterval = 3000;

    function setDetectionStatus(state, title, detail) {
        detectionStatus.className = `map-detection-status is-${state}`;
        detectionStatusIcon.textContent = {
            idle: '!',
            checking: '…',
            clear: '✓',
            smoke: '!',
            fire: '!',
            error: '×',
        }[state] || '!';
        detectionStatusTitle.textContent = title;
        detectionStatusDetail.textContent = detail;
        detectionStatusTime.textContent = state === 'idle'
            ? 'MENUNGGU'
            : `DIPERBARUI ${new Intl.DateTimeFormat('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Jakarta',
            }).format(new Date())} WIB`;
    }

    function clearDetectionOverlay() {
        const context = cameraDetections.getContext('2d');

        if (context) {
            context.clearRect(0, 0, cameraDetections.width, cameraDetections.height);
        }

        cameraDetections.hidden = true;
    }

    function stopDetection() {
        detectionEnabled = false;
        window.clearTimeout(detectionTimer);
        detectionTimer = null;
        detectionController?.abort();
        detectionController = null;
        clearDetectionOverlay();
        setDetectionStatus(
            'idle',
            'Kamera belum aktif',
            'Aktifkan kamera untuk memulai deteksi api dan asap otomatis.',
        );
    }

    function setFeedback(message, isError = false) {
        cameraFeedback.textContent = message;
        cameraFeedback.classList.toggle('is-error', isError);
    }

    function setCameraActive(isActive, deviceName = '') {
        cameraState.classList.toggle('is-active', isActive);
        cameraStateLabel.textContent = isActive ? 'ONLINE' : 'OFFLINE';
        cameraToggle.textContent = isActive ? 'Matikan kamera' : 'Aktifkan kamera';
        cameraPlaceholder.toggleAttribute('hidden', isActive);
        cameraPlaceholderMessage.hidden = isActive;
        cameraVideo.hidden = !isActive;
        cameraOverlay.hidden = !isActive;
        cameraCaption.classList.toggle('is-active', isActive);
        cameraCaptionStatus.replaceChildren();

        const indicator = document.createElement('i');
        indicator.setAttribute('aria-hidden', 'true');
        cameraCaptionStatus.append(indicator, document.createTextNode(
            isActive ? `Kamera aktif${deviceName ? ` · ${deviceName}` : ''}` : 'Tidak ada sinyal kamera',
        ));

        if (isActive) {
            const time = new Intl.DateTimeFormat('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Jakarta',
            }).format(new Date());
            cameraUpdated.textContent = `AKTIF SEJAK ${time} WIB`;
        } else {
            cameraUpdated.textContent = 'TERAKHIR DIPERBARUI —';
        }
    }

    function stopCamera() {
        stopDetection();

        if (activeStream) {
            activeStream.getTracks().forEach((track) => track.stop());
            activeStream = null;
        }

        cameraVideo.srcObject = null;
        setCameraActive(false);
    }

    function cameraErrorMessage(error) {
        if (!(error instanceof DOMException)) {
            return `Kamera gagal diaktifkan: ${error instanceof Error ? error.message : 'kesalahan tidak diketahui'}.`;
        }

        switch (error.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                return 'Akses kamera ditolak. Izinkan kamera untuk situs ini di pengaturan browser.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                return 'Kamera tidak ditemukan. Sambungkan kamera laptop atau USB, lalu coba lagi.';
            case 'NotReadableError':
            case 'TrackStartError':
                return 'Kamera tidak dapat digunakan. Pastikan kamera tidak sedang dipakai aplikasi lain.';
            case 'OverconstrainedError':
                return 'Kamera yang dipilih tidak tersedia. Pilih perangkat lain lalu coba lagi.';
            case 'SecurityError':
                return 'Akses kamera diblokir oleh browser. Buka situs melalui localhost atau HTTPS.';
            default:
                return `Kamera gagal diaktifkan: ${error.message || error.name}.`;
        }
    }

    async function refreshCameraDevices(selectedDeviceId = cameraDevice.value) {
        const devices = await cameraApi.enumerateDevices();
        const cameras = devices.filter((device) => device.kind === 'videoinput');
        const defaultOption = new Option('Kamera default browser', '');

        cameraDevice.replaceChildren(defaultOption);

        cameras.forEach((device, index) => {
            cameraDevice.add(new Option(device.label || `Kamera ${index + 1}`, device.deviceId));
        });

        cameraDevice.value = cameras.some((device) => device.deviceId === selectedDeviceId)
            ? selectedDeviceId
            : '';
        cameraDevice.disabled = cameras.length === 0;

        return cameras;
    }

    if (!cameraApi || typeof cameraApi.getUserMedia !== 'function') {
        cameraDevice.disabled = true;
        cameraToggle.disabled = true;
        setFeedback('Browser ini tidak mendukung kamera. Gunakan browser modern melalui localhost atau HTTPS.', true);
        return;
    }

    cameraToggle.addEventListener('click', async () => {
        if (activeStream) {
            stopCamera();
            setFeedback('Kamera dimatikan.');
            return;
        }

        const selectedDeviceId = cameraDevice.value;
        cameraToggle.disabled = true;
        cameraDevice.disabled = true;
        cameraStateLabel.textContent = 'MENGHUBUNGKAN';
        setFeedback('Meminta izin dan menghubungkan kamera…');

        let requestedStream = null;

        try {
            requestedStream = await cameraApi.getUserMedia({
                video: selectedDeviceId ? { deviceId: { exact: selectedDeviceId } } : true,
                audio: false,
            });
            cameraVideo.srcObject = requestedStream;
            await cameraVideo.play();
            activeStream = requestedStream;

            const videoTrack = activeStream.getVideoTracks()[0];
            const deviceName = videoTrack?.label || cameraDevice.selectedOptions[0]?.textContent || '';

            setCameraActive(true, deviceName);
            setFeedback('Kamera aktif. Deteksi api dan asap berjalan otomatis.');
            startDetection();

            try {
                await refreshCameraDevices(videoTrack?.getSettings().deviceId || selectedDeviceId);
            } catch (error) {
                setFeedback(`Kamera aktif, tetapi daftar perangkat gagal dimuat: ${cameraErrorMessage(error)}`, true);
            }

            videoTrack?.addEventListener('ended', () => {
                if (activeStream === requestedStream) {
                    stopCamera();
                    setFeedback('Sambungan kamera terputus. Periksa perangkat lalu aktifkan kembali.', true);
                }
            }, { once: true });
        } catch (error) {
            requestedStream?.getTracks().forEach((track) => track.stop());
            cameraVideo.srcObject = null;
            setCameraActive(false);
            stopDetection();
            setFeedback(cameraErrorMessage(error), true);
        } finally {
            cameraToggle.disabled = false;
            cameraDevice.disabled = false;
        }
    });

    cameraDevice.addEventListener('change', () => {
        if (activeStream) {
            stopCamera();
            setFeedback('Kamera dimatikan. Tekan Aktifkan kamera untuk menggunakan perangkat yang dipilih.');
        }
    });

    function drawDetections(detections, frameWidth, frameHeight) {
        const context = cameraDetections.getContext('2d');

        if (!context || !frameWidth || !frameHeight || !cameraVideo.videoWidth) {
            throw new Error('Tidak dapat menggambar kotak deteksi di pratinjau kamera.');
        }

        const bounds = cameraVideo.getBoundingClientRect();
        const pixelRatio = window.devicePixelRatio || 1;
        cameraDetections.width = Math.round(bounds.width * pixelRatio);
        cameraDetections.height = Math.round(bounds.height * pixelRatio);
        context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
        context.clearRect(0, 0, bounds.width, bounds.height);

        const sourceWidth = cameraVideo.videoWidth;
        const sourceHeight = cameraVideo.videoHeight;
        const scale = Math.max(bounds.width / sourceWidth, bounds.height / sourceHeight);
        const offsetX = (bounds.width - sourceWidth * scale) / 2;
        const offsetY = (bounds.height - sourceHeight * scale) / 2;
        const frameScaleX = sourceWidth / frameWidth;
        const frameScaleY = sourceHeight / frameHeight;

        detections.forEach((detection) => {
            const x = detection.x1 * frameScaleX * scale + offsetX;
            const y = detection.y1 * frameScaleY * scale + offsetY;
            const width = (detection.x2 - detection.x1) * frameScaleX * scale;
            const height = (detection.y2 - detection.y1) * frameScaleY * scale;
            const isFire = /fire|flame/i.test(detection.class_name);
            const color = isFire ? '#ef5143' : '#f0b547';
            const label = `${detection.class_name} ${(detection.confidence * 100).toFixed(0)}%`;

            context.strokeStyle = color;
            context.lineWidth = 3;
            context.strokeRect(x, y, width, height);
            context.font = 'bold 12px sans-serif';

            const labelWidth = context.measureText(label).width + 12;
            const labelY = Math.max(0, y - 22);
            context.fillStyle = color;
            context.fillRect(x, labelY, labelWidth, 20);
            context.fillStyle = '#fff';
            context.fillText(label, x + 6, labelY + 14);
        });

        cameraDetections.hidden = detections.length === 0;
    }

    async function detectCameraFrame() {
        if (!detectionEnabled || !activeStream) {
            return;
        }

        try {
            if (cameraVideo.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                throw new Error('Kamera belum menghasilkan gambar yang dapat dianalisis.');
            }

            const frame = document.createElement('canvas');
            const maxWidth = 960;
            const scale = Math.min(1, maxWidth / cameraVideo.videoWidth);
            frame.width = Math.round(cameraVideo.videoWidth * scale);
            frame.height = Math.round(cameraVideo.videoHeight * scale);
            const frameContext = frame.getContext('2d');

            if (!frameContext) {
                throw new Error('Browser tidak dapat membaca frame kamera.');
            }

            frameContext.drawImage(cameraVideo, 0, 0, frame.width, frame.height);

            const image = await new Promise((resolve, reject) => {
                frame.toBlob((blob) => {
                    if (blob) {
                        resolve(blob);
                        return;
                    }

                    reject(new Error('Gagal menyiapkan frame kamera untuk deteksi.'));
                }, 'image/jpeg', 0.78);
            });

            detectionController = new AbortController();
            const formData = new FormData();
            formData.append('file', image, 'camera-frame.jpg');

            const response = await fetch(
                `${detectionApiBaseUrl}/api/v1/cameras/cam-01/detect?confidence=${detectionConfidence}`,
                {
                    method: 'POST',
                    body: formData,
                    signal: detectionController.signal,
                },
            );

            if (!response.ok) {
                const errorResponse = await response.json().catch(() => null);
                const detail = typeof errorResponse?.detail === 'string'
                    ? errorResponse.detail
                    : `Server merespons HTTP ${response.status}.`;

                throw new Error(detail);
            }

            const prediction = await response.json();
            drawDetections(prediction.detections, prediction.width, prediction.height);

            if (prediction.has_fire && prediction.has_smoke) {
                setDetectionStatus(
                    'fire',
                    'API DAN ASAP TERDETEKSI',
                    `Model menemukan indikasi api dan asap pada ${prediction.detections.length} objek. Periksa kondisi lapangan; hasil ini perlu diverifikasi.`,
                );
            } else if (prediction.has_fire) {
                setDetectionStatus(
                    'fire',
                    'API TERDETEKSI',
                    `Model menemukan indikasi api pada ${prediction.detections.length} objek. Periksa kondisi lapangan; hasil ini perlu diverifikasi.`,
                );
            } else if (prediction.has_smoke) {
                setDetectionStatus(
                    'smoke',
                    'ASAP TERDETEKSI',
                    `Model menemukan indikasi asap pada ${prediction.detections.length} objek. Periksa kondisi lapangan; hasil ini perlu diverifikasi.`,
                );
            } else {
                setDetectionStatus(
                    'clear',
                    'API / ASAP TIDAK TERDETEKSI',
                    'Tidak ada indikasi api atau asap pada frame ini. Hasil ini bukan jaminan area aman.',
                );
            }
        } catch (error) {
            if (!detectionEnabled || error.name === 'AbortError') {
                return;
            }

            setDetectionStatus(
                'error',
                'DETEKSI TIDAK TERHUBUNG',
                `${error instanceof Error ? error.message : 'Kesalahan tidak diketahui'}. Sistem akan mencoba kembali otomatis.`,
            );
        } finally {
            detectionController = null;
        }

        if (detectionEnabled) {
            const retryDelay = detectionStatus.classList.contains('is-error')
                ? detectionRetryInterval
                : detectionInterval;
            detectionTimer = window.setTimeout(detectCameraFrame, retryDelay);
        }
    }

    function startDetection() {
        detectionEnabled = true;
        setDetectionStatus('checking', 'MENYIAPKAN DETEKSI', 'Menganalisis gambar kamera untuk mencari indikasi api atau asap.');
        detectCameraFrame();
    }

    cameraApi.enumerateDevices()
        .then(() => refreshCameraDevices())
        .catch((error) => {
            setFeedback(`Daftar kamera tidak dapat dimuat: ${cameraErrorMessage(error)}`, true);
        });

    window.addEventListener('pagehide', stopCamera, { once: true });
}

updateDashboardClock();
window.setInterval(updateDashboardClock, 1000);
initializeDashboardNavigation();
initializeForestCamera();
=======
// Inisialisasi komponen dashboard
updateDashboardClock();
window.setInterval(updateDashboardClock, 1000);
initializeDashboardNavigation();

// Inisialisasi modul kamera & live deteksi AI
initializeLiveCamera();
>>>>>>> d23f08c4ea981de28795f8809ed1d91216b3a7a3
