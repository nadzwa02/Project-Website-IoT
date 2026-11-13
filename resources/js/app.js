import { initializeLiveCamera } from './camera.js';
import { initializeSensorMonitor } from './sensor.js';

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

    const navLinks = navigation.querySelectorAll('.nav-link');

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', String(!isOpen));
        navigation.classList.toggle('is-open', !isOpen);
    });

    navigation.addEventListener('click', (event) => {
        const clickedLink = event.target instanceof Element ? event.target.closest('.nav-link') : null;

        if (clickedLink) {
            navLinks.forEach((link) => link.classList.remove('is-active'));
            clickedLink.classList.add('is-active');
            toggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
        }
    });

    if ('IntersectionObserver' in window) {
        const sections = Array.from(navLinks)
            .map((link) => {
                const href = link.getAttribute('href');
                return href && href.startsWith('#') ? document.querySelector(href) : null;
            })
            .filter(Boolean);

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    const id = entry.target.getAttribute('id');
                    navLinks.forEach((link) => {
                        link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`);
                    });
                }
            },
            { rootMargin: '-35% 0px -55% 0px', threshold: 0 }
        );

        sections.forEach((section) => observer.observe(section));
    }
}

function getHotspotProperty(properties, keys) {
    for (const key of keys) {
        const value = properties?.[key];

        if (value !== null && value !== undefined && String(value).trim() !== '') {
            return String(value);
        }
    }

    return 'Tidak tersedia';
}

function isValidHotspotFeature(feature) {
    const coordinates = feature?.geometry?.coordinates;

    return feature?.geometry?.type === 'Point'
        && Array.isArray(coordinates)
        && coordinates.length >= 2
        && Number.isFinite(coordinates[0])
        && Number.isFinite(coordinates[1])
        && coordinates[0] >= -180
        && coordinates[0] <= 180
        && coordinates[1] >= -90
        && coordinates[1] <= 90;
}

function createHotspotPopup(properties) {
    const content = document.createElement('div');
    const title = document.createElement('strong');

    content.className = 'hotspot-popup';
    title.textContent = 'Hotspot SIPONGI';
    content.append(title);

    const fields = [
        ['Tanggal', getHotspotProperty(properties, ['date_hotspot', 'date_hotspot_ori', 'hs_id'])],
        ['Satelit', getHotspotProperty(properties, ['sumber', 'ori_sumber'])],
        ['Confidence', getHotspotProperty(properties, ['confidence_level', 'confidence'])],
    ];

    for (const [label, value] of fields) {
        const row = document.createElement('div');
        const fieldLabel = document.createElement('span');
        const fieldValue = document.createElement('span');

        row.className = 'hotspot-popup-row';
        fieldLabel.className = 'hotspot-popup-label';
        fieldLabel.textContent = label;
        fieldValue.className = 'hotspot-popup-value';
        fieldValue.textContent = value;
        row.append(fieldLabel, fieldValue);
        content.append(row);
    }

    return content;
}

function initializeHotspotMap() {
    const mapElement = document.querySelector('#hotspot-map');
    const statusElement = document.querySelector('#hotspot-map-status');
    const countElement = document.querySelector('#hotspot-count');
    const summaryElement = document.querySelector('#hotspot-summary');

    if (!mapElement || !statusElement || !countElement || !summaryElement) {
        return;
    }

    const showStatus = (message) => {
        statusElement.textContent = message;
        statusElement.hidden = false;
        summaryElement.textContent = message;
    };

    if (!window.L) {
        countElement.textContent = 'PETA TIDAK TERSEDIA';
        showStatus('Peta gagal dimuat. Silakan muat ulang halaman.');
        return;
    }

    const map = window.L.map(mapElement).setView([-2.5, 118], 5);

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    fetch('/api/sipongi-hotspots', {
        headers: {
            Accept: 'application/json',
        },
    })
        .then(async (response) => {
            const payload = await response.json();

            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || 'API SIPONGI tidak dapat mengambil data hotspot.');
            }

            const featureCollection = payload.data;

            if (featureCollection?.type !== 'FeatureCollection' || !Array.isArray(featureCollection.features)) {
                throw new Error('Format data hotspot SIPONGI tidak valid.');
            }

            const features = featureCollection.features.filter(isValidHotspotFeature);
            const hotspots = window.L.geoJSON({
                type: 'FeatureCollection',
                features,
            }, {
                pointToLayer: (_feature, latLng) => window.L.circleMarker(latLng, {
                    radius: 6,
                    color: '#8f211b',
                    weight: 1.5,
                    fillColor: '#c4332b',
                    fillOpacity: 0.9,
                }),
                onEachFeature: (feature, layer) => {
                    layer.bindPopup(createHotspotPopup(feature.properties));
                },
            }).addTo(map);

            countElement.textContent = `${new Intl.NumberFormat('id-ID').format(features.length)} HOTSPOT`;

            if (features.length === 0) {
                showStatus('Tidak ada hotspot valid pada data SIPONGI.');
                summaryElement.textContent = 'Tidak ada hotspot valid pada data SIPONGI.';
                return;
            }

            statusElement.hidden = true;
            summaryElement.textContent = `${new Intl.NumberFormat('id-ID').format(features.length)} hotspot dari SIPONGI`;

            const bounds = hotspots.getBounds();

            if (features.length === 1) {
                map.setView(bounds.getCenter(), 8);
            } else {
                map.fitBounds(bounds, { padding: [24, 24], maxZoom: 8 });
            }
        })
        .catch((error) => {
            const message = error instanceof Error ? error.message : 'Terjadi kesalahan yang tidak diketahui.';

            countElement.textContent = 'DATA GAGAL DIMUAT';
            showStatus(`Gagal memuat hotspot SIPONGI: ${message}`);
        });
}

// Inisialisasi komponen dashboard
updateDashboardClock();
window.setInterval(updateDashboardClock, 1000);
initializeDashboardNavigation();

// Inisialisasi modul kamera, sensor MQTT & live deteksi AI
initializeLiveCamera();
initializeSensorMonitor();
window.addEventListener('load', initializeHotspotMap, { once: true });