const POLL_INTERVAL_MS = 2000;

function setFootStatus(element, dotClass, text) {
    if (!element) {
        return;
    }

    const dot = document.createElement('span');
    dot.className = `status-dot ${dotClass}`.trim();
    element.replaceChildren(dot, document.createTextNode(` ${text}`));
}

function updateRow(rowId, readingHtml, badgeText, badgeClass, updatedText, isOnline) {
    const row = document.getElementById(rowId);
    if (!row) {
        return;
    }

    const readingEl = row.querySelector('[data-reading]');
    const badgeEl = row.querySelector('[data-badge]');
    const updatedEl = row.querySelector('[data-updated]');
    const connEl = row.querySelector('[data-connection]');

    if (readingEl) {
        readingEl.innerHTML = readingHtml;
    }
    if (badgeEl) {
        badgeEl.className = `table-badge ${badgeClass}`;
        badgeEl.textContent = badgeText;
    }
    if (updatedEl) {
        updatedEl.textContent = updatedText;
    }
    if (connEl) {
        connEl.className = isOnline ? 'connection is-online' : 'connection';
        connEl.innerHTML = `<i></i> ${isOnline ? 'Online' : 'Offline'}`;
    }
}

function renderTrendChart(history) {
    const seriesGroup = document.getElementById('chart-series');
    const placeholderLine = document.getElementById('chart-placeholder-line');
    const emptyTitle = document.getElementById('chart-empty-title');
    const emptySub = document.getElementById('chart-empty-sub');
    const periodLabel = document.getElementById('chart-period-label');

    if (!seriesGroup) {
        return;
    }

    if (!Array.isArray(history) || history.length === 0) {
        seriesGroup.replaceChildren();
        if (placeholderLine) placeholderLine.style.display = '';
        if (emptyTitle) emptyTitle.style.display = '';
        if (emptySub) emptySub.style.display = '';
        return;
    }

    if (placeholderLine) placeholderLine.style.display = 'none';
    if (emptyTitle) emptyTitle.style.display = 'none';
    if (emptySub) emptySub.style.display = 'none';
    if (periodLabel) {
        periodLabel.textContent = `PERIODE: ${history.length} PEMBACAAN TERAKHIR`;
    }

    const xMin = 48;
    const xMax = 748;
    const yMin = 20;
    const yMax = 188;

    const toY = (val) => {
        const clamped = Math.max(0, Math.min(100, Number(val) || 0));
        return yMax - (clamped / 100) * (yMax - yMin);
    };

    const toX = (index, total) => {
        if (total <= 1) {
            return (xMin + xMax) / 2;
        }
        return xMin + (index / (total - 1)) * (xMax - xMin);
    };

    const buildPolyline = (points, stroke) => {
        if (points.length === 0) {
            return null;
        }
        if (points.length === 1) {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', points[0][0].toFixed(1));
            circle.setAttribute('cy', points[0][1].toFixed(1));
            circle.setAttribute('r', '4');
            circle.setAttribute('fill', stroke);
            return circle;
        }

        const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        polyline.setAttribute('fill', 'none');
        polyline.setAttribute('stroke', stroke);
        polyline.setAttribute('stroke-width', '2.5');
        polyline.setAttribute('stroke-linejoin', 'round');
        polyline.setAttribute('stroke-linecap', 'round');
        polyline.setAttribute(
            'points',
            points.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join(' ')
        );
        return polyline;
    };

    const tempPoints = [];
    const humPoints = [];
    const riskPoints = [];

    history.forEach((item, idx) => {
        const x = toX(idx, history.length);
        if (item.suhu !== null && item.suhu !== undefined) {
            tempPoints.push([x, toY(item.suhu)]);
        }
        if (item.kelembapan !== null && item.kelembapan !== undefined) {
            humPoints.push([x, toY(item.kelembapan)]);
        }
        if (item.risk_score !== null && item.risk_score !== undefined) {
            riskPoints.push([x, toY(item.risk_score)]);
        }
    });

    const nodes = [
        buildPolyline(humPoints, '#0284c7'),
        buildPolyline(tempPoints, '#ea580c'),
        buildPolyline(riskPoints, '#dc2626'),
    ].filter(Boolean);

    const addEndMarker = (points, color, label) => {
        if (points.length === 0) return;
        const [cx, cy] = points[points.length - 1];
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', cx.toFixed(1));
        dot.setAttribute('cy', cy.toFixed(1));
        dot.setAttribute('r', '4.5');
        dot.setAttribute('fill', color);
        dot.setAttribute('stroke', '#ffffff');
        dot.setAttribute('stroke-width', '1.5');
        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.textContent = label;
        dot.appendChild(title);
        nodes.push(dot);
    };

    const latestItem = history[history.length - 1];
    if (latestItem) {
        if (latestItem.kelembapan !== null && latestItem.kelembapan !== undefined) {
            addEndMarker(humPoints, '#0284c7', `Kelembapan: ${latestItem.kelembapan}%`);
        }
        if (latestItem.suhu !== null && latestItem.suhu !== undefined) {
            addEndMarker(tempPoints, '#ea580c', `Suhu: ${latestItem.suhu} °C`);
        }
        if (latestItem.risk_score !== null && latestItem.risk_score !== undefined) {
            addEndMarker(riskPoints, '#dc2626', `Risiko: ${latestItem.risk_score}/100`);
        }
    }

    seriesGroup.replaceChildren(...nodes);

    // Update X-axis time labels from actual history timestamps
    for (let i = 0; i < 5; i += 1) {
        const labelEl = document.getElementById(`chart-x-${i}`);
        if (!labelEl) continue;

        if (history.length === 1) {
            labelEl.textContent = i === 2 ? (history[0].time || '--:--:--') : '';
        } else {
            const idx = Math.min(history.length - 1, Math.round((i / 4) * (history.length - 1)));
            labelEl.textContent = history[idx]?.time || '';
        }
    }
}

function updateActivityAlerts(latest, isOnline, alerts) {
    const list = document.getElementById('sensor-activity-list');
    const sensorStatusItem = document.getElementById('activity-sensor-status');
    if (!list || !sensorStatusItem) {
        return;
    }

    const icon = sensorStatusItem.querySelector('.activity-icon');
    const title = sensorStatusItem.querySelector('strong');
    const desc = sensorStatusItem.querySelector('p');
    const time = sensorStatusItem.querySelector('time');

    if (latest) {
        const isDanger = latest.status === 'BAHAYA';
        if (icon) {
            icon.className = `activity-icon ${isDanger ? 'activity-icon-red' : isOnline ? 'activity-icon-green' : 'activity-icon-amber'}`;
            icon.textContent = isDanger ? '!' : isOnline ? '✓' : '!';
        }
        if (title) {
            title.textContent = isDanger
                ? `BAHAYA TERDETEKSI (${latest.device})`
                : isOnline
                    ? `Telemetri ${latest.device} aktif`
                    : `Koneksi ${latest.device} terputus (>30 dtk)`;
        }
        if (desc) {
            const tempText = latest.suhu !== null ? `${latest.suhu} °C` : '-- °C';
            const humText = latest.kelembapan !== null ? `${latest.kelembapan}%` : '--%';
            desc.textContent = `Suhu ${tempText}, Kelembapan ${humText}, Asap ${latest.asap_analog ?? '--'} ADC, Api ${latest.bahaya_api ? 'TERDETEKSI' : 'Aman'}.`;
        }
        if (time) {
            time.textContent = latest.received_at_wib || 'BARU SAJA';
        }
    }

    // Remove previous dynamic alert items before re-rendering
    list.querySelectorAll('[data-dynamic-alert]').forEach((el) => el.remove());

    if (Array.isArray(alerts) && alerts.length > 0) {
        const cameraItem = document.getElementById('activity-camera-item');
        alerts.slice(0, 3).forEach((alert) => {
            const item = document.createElement('div');
            item.className = 'activity-item';
            item.setAttribute('data-dynamic-alert', 'true');

            const badge = document.createElement('span');
            badge.className = 'activity-icon activity-icon-red';
            badge.textContent = '!';

            const body = document.createElement('div');
            const strong = document.createElement('strong');
            strong.textContent = alert.title || 'Peringatan Bahaya';
            const p = document.createElement('p');
            p.textContent = alert.message || '';
            body.append(strong, p);

            const t = document.createElement('time');
            t.textContent = alert.time || '';

            item.append(badge, body, t);
            if (cameraItem) {
                list.insertBefore(item, cameraItem);
            } else {
                list.append(item);
            }
        });
    }
}

function applySensorState(payload) {
    const { has_data: hasData, is_online: isOnline, latest, history, alerts } = payload;
    if (!hasData || !latest) {
        return;
    }

    const isDanger = latest.status === 'BAHAYA';
    const updatedLabel = latest.received_at_wib || 'Baru saja';

    // 1. Nav System Status & System Banner
    const navSystem = document.getElementById('nav-system-status');
    if (navSystem) {
        navSystem.classList.remove('is-danger', 'is-offline');
        if (isDanger) {
            navSystem.classList.add('is-danger');
            navSystem.innerHTML = '<span></span> BAHAYA TERDETEKSI';
        } else if (isOnline) {
            navSystem.innerHTML = '<span></span> SENSOR ONLINE';
        } else {
            navSystem.classList.add('is-offline');
            navSystem.innerHTML = '<span></span> SENSOR OFFLINE';
        }
    }

    const banner = document.getElementById('sensor-banner');
    const bannerTitle = document.getElementById('sensor-banner-title');
    const bannerDesc = document.getElementById('sensor-banner-desc');
    const bannerStatus = document.getElementById('sensor-banner-status');

    if (banner) {
        banner.classList.remove('is-safe', 'is-danger');
        if (isDanger) {
            banner.classList.add('is-danger');
        } else if (isOnline) {
            banner.classList.add('is-safe');
        }
    }
    if (bannerTitle) {
        bannerTitle.textContent = isDanger
            ? `PERINGATAN: BAHAYA KEBAKARAN TERDETEKSI (${latest.device})`
            : isOnline
                ? `PEMANTAUAN AKTIF — KONDISI AMAN (${latest.device})`
                : `PERANGKAT OFFLINE — DATA TERAKHIR (${latest.device})`;
    }
    if (bannerDesc) {
        bannerDesc.textContent = isDanger
            ? `Sensor mendeteksi anomali! Api: ${latest.bahaya_api ? 'YA' : 'Tidak'}, Asap: ${latest.bahaya_asap ? 'TINGGI' : 'Normal'}, Suhu: ${latest.bahaya_suhu ? 'PANAS' : 'Normal'} · Buzzer: ${latest.buzzer ? 'ON' : 'OFF'}.`
            : `Pembaruan terakhir pada ${updatedLabel} · Uptime ESP32: ${latest.uptime ?? 0} detik.`;
    }
    if (bannerStatus) {
        bannerStatus.innerHTML = `<span></span> ${isDanger ? 'BAHAYA' : isOnline ? 'AMAN' : 'OFFLINE'}`;
    }

    // 2. Stat Cards
    const tempVal = document.getElementById('stat-temp-value');
    const tempFoot = document.getElementById('stat-temp-foot');
    if (tempVal) {
        tempVal.innerHTML = `${latest.suhu !== null ? latest.suhu.toFixed(1) : '--'}<span>°C</span>`;
    }
    setFootStatus(
        tempFoot,
        latest.bahaya_suhu ? 'status-dot-red' : latest.suhu !== null ? 'status-dot-green' : 'status-dot-muted',
        latest.suhu === null ? 'Sensor DHT11 tidak terbaca' : latest.bahaya_suhu ? 'Suhu tinggi terdeteksi!' : 'Normal · DHT11 aktif'
    );

    const humVal = document.getElementById('stat-hum-value');
    const humFoot = document.getElementById('stat-hum-foot');
    if (humVal) {
        humVal.innerHTML = `${latest.kelembapan !== null ? latest.kelembapan.toFixed(0) : '--'}<span>%</span>`;
    }
    setFootStatus(
        humFoot,
        latest.kelembapan !== null ? 'status-dot-green' : 'status-dot-muted',
        latest.kelembapan === null ? 'Sensor DHT11 tidak terbaca' : 'Kelembapan udara · DHT11'
    );

    const smokeVal = document.getElementById('stat-smoke-value');
    const smokeFoot = document.getElementById('stat-smoke-foot');
    if (smokeVal) {
        smokeVal.innerHTML = `${latest.asap_analog ?? '--'}<span class="stat-unit">ADC</span>`;
    }
    setFootStatus(
        smokeFoot,
        latest.bahaya_asap ? 'status-dot-red' : 'status-dot-green',
        latest.bahaya_asap ? 'Konsentrasi asap tinggi!' : 'Sensor MQ-2 normal'
    );

    const riskVal = document.getElementById('stat-risk-value');
    const riskFoot = document.getElementById('stat-risk-foot');
    const score = Number(latest.risk_score ?? 0);
    const riskLevel = isDanger || score >= 70 ? 'TINGGI' : score >= 40 ? 'SEDANG' : 'RENDAH';
    if (riskVal) {
        riskVal.textContent = riskLevel;
    }
    setFootStatus(
        riskFoot,
        riskLevel === 'TINGGI' ? 'status-dot-red' : riskLevel === 'SEDANG' ? '' : 'status-dot-green',
        latest.bahaya_api ? 'Nyala api terdeteksi (IR Flame)!' : `Skor ${score}/100 · Buzzer ${latest.buzzer ? 'ON' : 'OFF'}`
    );

    // 3. Telemetry Table
    const telemetryTag = document.getElementById('telemetry-status-tag');
    if (telemetryTag) {
        const dotClass = isDanger ? 'status-dot-red' : isOnline ? 'status-dot-green' : 'status-dot-muted';
        telemetryTag.innerHTML = `<span class="status-dot ${dotClass}"></span> ${isOnline ? 'TERHUBUNG' : 'OFFLINE'}`;
    }

    updateRow(
        'row-temp',
        `${latest.suhu !== null ? latest.suhu.toFixed(1) : '--'} <small>°C</small>`,
        latest.suhu === null ? 'TIDAK TERBACA' : latest.bahaya_suhu ? 'BAHAYA' : 'NORMAL',
        latest.suhu === null ? 'badge-pending' : latest.bahaya_suhu ? 'badge-danger' : 'badge-safe',
        updatedLabel,
        isOnline
    );

    updateRow(
        'row-hum',
        `${latest.kelembapan !== null ? latest.kelembapan.toFixed(0) : '--'} <small>%</small>`,
        latest.kelembapan === null ? 'TIDAK TERBACA' : 'NORMAL',
        latest.kelembapan === null ? 'badge-pending' : 'badge-safe',
        updatedLabel,
        isOnline
    );

    updateRow(
        'row-smoke',
        `${latest.asap_analog ?? '--'} <small>ADC</small>`,
        latest.bahaya_asap ? 'BAHAYA ASAP' : 'AMAN',
        latest.bahaya_asap ? 'badge-danger' : 'badge-safe',
        updatedLabel,
        isOnline
    );

    updateRow(
        'row-flame',
        `${latest.api_analog ?? '--'} <small>ADC</small>`,
        latest.bahaya_api ? 'API TERDETEKSI' : 'AMAN',
        latest.bahaya_api ? 'badge-danger' : 'badge-safe',
        updatedLabel,
        isOnline
    );

    // 4. Risk Score & Insight
    const scoreRingEl = document.getElementById('risk-score-ring');
    const scoreValueEl = document.getElementById('risk-score-value');
    const scoreTitleEl = document.getElementById('risk-score-title');
    const scoreDescEl = document.getElementById('risk-score-desc');
    const scaleLowEl = document.getElementById('score-scale-low');
    const scaleMedEl = document.getElementById('score-scale-med');
    const scaleHighEl = document.getElementById('score-scale-high');
    const insightTitleEl = document.getElementById('insight-title');
    const insightDescEl = document.getElementById('insight-desc');

    if (scoreRingEl) {
        const clampedScore = Math.max(0, Math.min(100, score));
        const deg = (clampedScore / 100) * 360;
        const ringColor = riskLevel === 'TINGGI' ? '#dc2626' : riskLevel === 'SEDANG' ? '#eab308' : '#16a34a';
        scoreRingEl.style.setProperty('--score-deg', `${deg}deg`);
        scoreRingEl.style.setProperty('--score-color', ringColor);
    }
    if (scaleLowEl) scaleLowEl.classList.toggle('is-active', riskLevel === 'RENDAH');
    if (scaleMedEl) scaleMedEl.classList.toggle('is-active', riskLevel === 'SEDANG');
    if (scaleHighEl) scaleHighEl.classList.toggle('is-active', riskLevel === 'TINGGI');

    if (scoreValueEl) scoreValueEl.textContent = String(score);
    if (scoreTitleEl) scoreTitleEl.textContent = `Risiko ${riskLevel}`;
    if (scoreDescEl) {
        scoreDescEl.textContent = isDanger
            ? 'Terdeteksi indikator kebakaran aktif!'
            : 'Kondisi area pemantauan terkendali.';
    }
    if (insightTitleEl) {
        insightTitleEl.textContent = isDanger
            ? 'Tindakan Cepat Diperlukan'
            : 'Lingkungan Dalam Kondisi Aman';
    }
    if (insightDescEl) {
        insightDescEl.textContent = isDanger
            ? `Indikator bahaya aktif: ${[
                latest.bahaya_api ? 'Nyala Api' : null,
                latest.bahaya_asap ? 'Asap Pekat' : null,
                latest.bahaya_suhu ? 'Suhu Ekstrem' : null,
            ].filter(Boolean).join(', ') || 'Sensor'}.`
            : `Suhu ${latest.suhu ?? '--'} °C, kelembapan ${latest.kelembapan ?? '--'}%, kadar asap ${latest.asap_analog ?? '--'} ADC, dan tidak ada nyala api.`;
    }

    // 5. Chart & Alerts
    renderTrendChart(history);
    updateActivityAlerts(latest, isOnline, alerts);

    // 6. Info Grid
    const activeCount = isOnline ? 4 : 0;
    const countEl = document.getElementById('connected-devices-count');
    const barEl = document.getElementById('connected-devices-bar');
    const noteDescEl = document.getElementById('monitor-note-desc');
    const noteLabelEl = document.getElementById('monitor-note-label');

    if (countEl) {
        countEl.innerHTML = `${activeCount} <small>/ 4 aktif</small>`;
    }
    if (barEl) {
        barEl.style.width = isOnline ? '100%' : '0%';
    }
    if (noteDescEl) {
        noteDescEl.textContent = isOnline
            ? `Perangkat ${latest.device} aktif mengirim telemetri via EMQX MQTT (${updatedLabel}).`
            : `Data terakhir diterima pada ${updatedLabel}. Periksa koneksi WiFi/MQTT pada perangkat ESP32.`;
    }
    if (noteLabelEl) {
        noteLabelEl.textContent = isOnline ? 'TELEMETRI REAL-TIME AKTIF' : 'PERANGKAT SEDANG OFFLINE';
    }
}

async function fetchLatestSensorData() {
    try {
        const response = await fetch('/api/sensor/latest', {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        if (payload?.success) {
            applySensorState(payload);
        }
    } catch {
        // Ignore transient network errors during polling
    }
}

export function initializeSensorMonitor() {
    fetchLatestSensorData();
    window.setInterval(fetchLatestSensorData, POLL_INTERVAL_MS);
}

