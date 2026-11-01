<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forest Fire Monitoring System — Sistem Deteksi Dini Kebakaran Hutan IoT</title>
    <meta name="description" content="Sistem monitoring dan deteksi dini potensi kebakaran hutan berbasis IoT secara real-time menggunakan sensor DHT11, MQ-2, dan LoRaWAN.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --ivory: #F5F0E8;
            --ivory-dark: #EDE7D4;
            --ivory-card: #FAF7F0;
            --cream: #F0EAD6;
            --green-dark: #1A3D2B;
            --green-mid: #2D5A3D;
            --green-light: #3A7A4F;
            --green-accent: #4CAF72;
            --yellow: #E8B400;
            --yellow-light: #F5C842;
            --orange: #D4621A;
            --orange-light: #E8793A;
            --red: #C0392B;
            --red-bright: #E74C3C;
            --text-primary: #1A2B1E;
            --text-secondary: #4A5E52;
            --text-muted: #7A9482;
            --border: #D4C9A8;
            --border-light: #E8DFC8;
            --shadow-sm: 0 1px 3px rgba(26, 61, 43, 0.08);
            --shadow-md: 0 4px 12px rgba(26, 61, 43, 0.12);
            --shadow-lg: 0 8px 24px rgba(26, 61, 43, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--ivory);
            color: var(--text-primary);
            min-height: 100vh;
            font-size: 13px;
            line-height: 1.5;
        }

        /* ===== HEADER ===== */
        .header {
            background: var(--green-dark);
            color: #fff;
            padding: 0 20px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid var(--yellow);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }
        .header-left { display: flex; align-items: center; gap: 14px; }
        .header-logo {
            display: flex; align-items: center; gap: 10px;
        }
        .header-logo svg { width: 32px; height: 32px; }
        .header-title {
            font-size: 15px; font-weight: 700; letter-spacing: 0.01em;
            line-height: 1.2;
        }
        .header-subtitle {
            font-size: 10px; font-weight: 400; color: #A8C4B0;
            letter-spacing: 0.04em; text-transform: uppercase;
        }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .sim-badge {
            background: var(--yellow);
            color: var(--green-dark);
            font-size: 9px; font-weight: 800;
            padding: 3px 8px; border-radius: 3px;
            letter-spacing: 0.08em; text-transform: uppercase;
            animation: pulse-badge 2s ease-in-out infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.75; }
        }
        .realtime-clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px; font-weight: 500;
            color: #D4F0DC;
            letter-spacing: 0.06em;
        }
        .conn-status {
            display: flex; align-items: center; gap: 6px;
            font-size: 11px; color: #A8C4B0;
        }
        .conn-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--green-accent);
            box-shadow: 0 0 6px var(--green-accent);
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.4;} }

        /* ===== LAYOUT ===== */
        .dashboard {
            max-width: 1600px;
            margin: 0 auto;
            padding: 16px 20px;
            display: grid;
            gap: 14px;
        }

        /* ===== CARD BASE ===== */
        .card {
            background: var(--ivory-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-mid) 100%);
            color: #fff;
            border-bottom: 2px solid var(--yellow);
        }
        .card-header-title {
            display: flex; align-items: center; gap: 8px;
        }
        .card-header h2 {
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.03em; text-transform: uppercase;
            color: #fff;
        }
        .card-header small {
            font-size: 10px; color: #A8C4B0; display: block;
            font-weight: 400; letter-spacing: 0.02em;
        }
        .card-body { padding: 14px; }
        .card-icon { font-size: 16px; }

        /* ===== SENSOR TABLE CARD ===== */
        .sensor-table-card { grid-column: 1/-1; }
        .table-meta {
            font-size: 10px; color: #A8C4B0;
        }
        .refresh-btn {
            display: flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff; font-size: 10px; font-weight: 600;
            padding: 4px 10px; border-radius: 4px;
            cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.04em; text-transform: uppercase;
        }
        .refresh-btn:hover { background: rgba(255,255,255,0.22); }
        .refresh-btn svg { width: 12px; height: 12px; }

        .sensor-table { width: 100%; border-collapse: collapse; }
        .sensor-table thead tr {
            background: var(--ivory-dark);
            border-bottom: 2px solid var(--border);
        }
        .sensor-table th {
            padding: 8px 12px; text-align: left;
            font-size: 10px; font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: 0.06em;
            white-space: nowrap;
        }
        .sensor-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            font-size: 12px;
        }
        .sensor-table tbody tr {
            transition: background 0.15s;
        }
        .sensor-table tbody tr:hover {
            background: var(--ivory-dark);
        }
        .sensor-table tbody tr:last-child td { border-bottom: none; }

        .sensor-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px; font-weight: 600;
            color: var(--green-dark);
        }
        .location-name { font-weight: 600; font-size: 12px; }
        .location-sub { font-size: 10px; color: var(--text-muted); }
        .temp-val { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .temp-critical { color: var(--red-bright); }
        .temp-warning { color: var(--orange); }
        .temp-normal { color: var(--green-dark); }
        .temp-delta {
            font-size: 10px; padding: 1px 4px;
            border-radius: 3px; margin-left: 4px;
        }
        .delta-up { background: #FFE8E8; color: var(--red); }
        .delta-neutral { background: #E8F5E9; color: var(--green-dark); }

        .status-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 4px;
            font-size: 9px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .status-online { background: #D4F5E4; color: #1A6B3A; }
        .status-offline { background: #FFE0D4; color: #8B2A10; }
        .status-online-dot, .status-offline-dot {
            width: 5px; height: 5px; border-radius: 50%;
        }
        .status-online-dot { background: var(--green-accent); }
        .status-offline-dot { background: var(--red); }

        .action-btn {
            display: inline-block; padding: 4px 8px;
            border-radius: 4px; font-size: 9px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase;
            cursor: pointer; border: none; font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        .action-isolasi { background: var(--red); color: #fff; }
        .action-ping { background: var(--yellow); color: var(--green-dark); }
        .action-rekalibrasi { background: var(--orange-light); color: #fff; }
        .action-diagnostik { background: var(--green-accent); color: #fff; }

        /* ===== ALERT BANNER ===== */
        .alert-banner {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px;
            border-radius: 8px; border: 1px solid;
            font-size: 12px; font-weight: 600;
            margin: 0;
        }
        .alert-normal { background:#D4F5E4; border-color:#A8E6C2; color:#1A6B3A; }
        .alert-warning { background:#FFF8D4; border-color:#F5D642; color:#7A5A00; }
        .alert-danger { background:#FFE0D4; border-color:#E87A5A; color:#8B2A10; }
        .alert-icon { font-size: 18px; }
        .alert-text { flex: 1; }
        .alert-label { font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }

        /* ===== SENSOR CARDS ROW ===== */
        .sensor-cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        @media (max-width: 1024px) { .sensor-cards-row { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .sensor-cards-row { grid-template-columns: 1fr; } }

        .sensor-card {
            background: var(--ivory-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            padding: 14px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .sensor-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .sensor-card-accent {
            position: absolute; top: 0; left: 0;
            width: 4px; height: 100%;
        }
        .accent-green { background: var(--green-accent); }
        .accent-blue { background: #3498DB; }
        .accent-orange { background: var(--orange); }
        .accent-red { background: var(--red-bright); }

        .sensor-card-label {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.08em; margin-bottom: 6px;
        }
        .sensor-card-icon {
            position: absolute; right: 14px; top: 14px;
            font-size: 22px; opacity: 0.15;
        }
        .sensor-card-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 28px; font-weight: 700;
            line-height: 1.1; margin-bottom: 4px;
        }
        .sensor-card-unit { font-size: 13px; font-weight: 400; color: var(--text-muted); }
        .sensor-card-sub { font-size: 10px; color: var(--text-muted); margin-top: 6px; }
        .sensor-card-status {
            display: inline-block; margin-top: 8px;
            padding: 3px 8px; border-radius: 4px;
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .status-kritis { background: #FFD4D4; color: var(--red); }
        .status-waspada { background: #FFF0C4; color: #7A5A00; }
        .status-normal-s { background: #D4F5E4; color: #1A6B3A; }

        /* ===== CHARTS ROW ===== */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 14px;
        }
        @media (max-width: 1024px) { .charts-row { grid-template-columns: 1fr; } }

        .chart-container { position: relative; height: 220px; }

        /* ===== RIGHT PANEL ===== */
        .right-panel {
            display: flex; flex-direction: column; gap: 14px;
        }
        .index-card { }
        .index-big {
            font-family: 'JetBrains Mono', monospace;
            font-size: 42px; font-weight: 800;
            line-height: 1; color: var(--red-bright);
        }
        .index-unit { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .index-label {
            font-size: 10px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 4px;
        }
        .fire-risk-meter {
            position: relative; margin: 10px 0;
        }
        .meter-bar {
            height: 10px; border-radius: 5px;
            background: linear-gradient(90deg, #4CAF72 0%, #E8B400 40%, #D4621A 70%, #C0392B 100%);
            position: relative;
        }
        .meter-indicator {
            position: absolute; top: -3px;
            width: 16px; height: 16px; border-radius: 50%;
            background: #fff; border: 3px solid var(--red-bright);
            transform: translateX(-50%);
            transition: left 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .meter-labels {
            display: flex; justify-content: space-between;
            font-size: 9px; color: var(--text-muted); margin-top: 4px;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .risk-tag {
            display: inline-block; padding: 4px 10px;
            border-radius: 4px; font-size: 11px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase;
            margin-top: 8px;
        }
        .risk-ekstrem { background: var(--red-bright); color: #fff; }
        .risk-tinggi { background: var(--orange); color: #fff; }
        .risk-sedang { background: var(--yellow); color: var(--green-dark); }
        .risk-rendah { background: var(--green-accent); color: #fff; }

        /* ===== BOTTOM GRID ===== */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 1024px) { .bottom-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 640px) { .bottom-grid { grid-template-columns: 1fr; } }

        /* ===== WEBCAM ===== */
        .webcam-area {
            background: #0D1F13;
            border-radius: 6px;
            aspect-ratio: 16/9;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 8px;
            position: relative; overflow: hidden;
        }
        .webcam-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 2px,
                rgba(0,255,50,0.02) 2px, rgba(0,255,50,0.02) 4px
            );
            pointer-events: none;
        }
        .webcam-icon { font-size: 32px; opacity: 0.4; }
        .webcam-status {
            font-size: 11px; color: #4CAF72; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .webcam-note { font-size: 10px; color: #3A7A4F; text-align: center; max-width: 180px; }
        .webcam-corner {
            position: absolute; width: 20px; height: 20px;
            border-color: #4CAF72; border-style: solid; opacity: 0.7;
        }
        .wc-tl { top: 8px; left: 8px; border-width: 2px 0 0 2px; }
        .wc-tr { top: 8px; right: 8px; border-width: 2px 2px 0 0; }
        .wc-bl { bottom: 8px; left: 8px; border-width: 0 0 2px 2px; }
        .wc-br { bottom: 8px; right: 8px; border-width: 0 2px 2px 0; }
        .webcam-rec {
            position: absolute; top: 10px; right: 30px;
            display: flex; align-items: center; gap: 5px;
            font-size: 9px; color: #ff4444; font-weight: 700;
            letter-spacing: 0.08em; text-transform: uppercase;
        }
        .rec-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #ff4444; animation: blink 1s ease-in-out infinite;
        }

        /* ===== HISTORY TABLE ===== */
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table thead tr { border-bottom: 2px solid var(--border); }
        .history-table th {
            padding: 6px 10px; text-align: left;
            font-size: 9px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .history-table td {
            padding: 7px 10px;
            border-bottom: 1px solid var(--border-light);
            font-size: 11px;
        }
        .history-table tbody tr:last-child td { border-bottom: none; }
        .hist-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px; color: var(--text-muted);
        }
        .hist-badge {
            display: inline-block; padding: 2px 6px;
            border-radius: 3px; font-size: 9px; font-weight: 700;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .hist-normal { background: #D4F5E4; color: #1A6B3A; }
        .hist-waspada { background: #FFF0C4; color: #7A5A00; }
        .hist-bahaya { background: #FFD4D4; color: #8B0000; }

        /* ===== ALERT PANEL ===== */
        .alert-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid var(--border-light);
        }
        .alert-item:last-child { border-bottom: none; }
        .alert-item-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
        .alert-item-text { flex: 1; }
        .alert-item-title { font-size: 12px; font-weight: 600; }
        .alert-item-desc { font-size: 10px; color: var(--text-muted); margin-top: 2px; }
        .alert-item-time { font-family: 'JetBrains Mono', monospace; font-size: 9px; color: var(--text-muted); }

        /* ===== PARTICLES/DUST ===== */
        .pm-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 36px; font-weight: 800;
            color: var(--orange);
            line-height: 1;
        }
        .pm-unit { font-size: 12px; color: var(--text-muted); }
        .pm-bar { height: 8px; background: var(--border); border-radius: 4px; margin: 8px 0; overflow: hidden; }
        .pm-fill { height: 100%; border-radius: 4px; transition: width 0.8s ease; }
        .pm-level-label { font-size: 10px; font-weight: 700; color: var(--orange); text-transform: uppercase; letter-spacing: 0.06em; }

        /* ===== MINI STATS ===== */
        .mini-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
        .mini-stat {
            background: var(--ivory-dark); border-radius: 6px;
            padding: 8px 10px; text-align: center;
        }
        .mini-stat-val { font-family: 'JetBrains Mono', monospace; font-size: 16px; font-weight: 700; }
        .mini-stat-lbl { font-size: 9px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }

        /* ===== GAUGE ===== */
        .gauge-wrapper { display: flex; align-items: center; gap: 14px; }
        .gauge-svg { width: 80px; height: 80px; }
        .gauge-info { flex: 1; }
        .gauge-big { font-family: 'JetBrains Mono', monospace; font-size: 22px; font-weight: 700; }
        .gauge-lbl { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--ivory); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ===== CROSSOVER LINE ===== */
        .crossover-label {
            background: rgba(232, 180, 0, 0.2);
            border: 1px solid var(--yellow);
            color: var(--yellow-light);
            font-size: 10px; font-weight: 700;
            padding: 3px 8px; border-radius: 4px;
            letter-spacing: 0.06em; text-transform: uppercase;
        }

        /* ===== TABLE SCROLL WRAPPER ===== */
        .table-scroll { overflow-x: auto; }

        .divider { height: 1px; background: var(--border-light); margin: 6px 0; }

        .badge-offline {
            background: rgba(192, 57, 43, 0.1);
            border: 1px solid var(--red);
            color: var(--red);
            font-size: 9px; font-weight: 700;
            padding: 2px 6px; border-radius: 3px;
            letter-spacing: 0.05em; text-transform: uppercase;
        }

        .offline-row { opacity: 0.65; }
        .offline-row td { color: var(--text-muted); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media(max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }

        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .legend-row { display: flex; align-items: center; gap: 6px; font-size: 10px; color: var(--text-secondary); }
        .chart-legend { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }

        /* Tab styles */
        .tab-bar { display: flex; gap: 4px; padding: 10px 14px 0; border-bottom: 1px solid var(--border); }
        .tab-btn {
            padding: 6px 12px; border-radius: 6px 6px 0 0;
            font-size: 11px; font-weight: 600; cursor: pointer;
            border: 1px solid transparent; border-bottom: none;
            background: transparent; color: var(--text-muted);
            font-family: 'Inter', sans-serif; transition: all 0.15s;
        }
        .tab-btn.active {
            background: var(--ivory-card); color: var(--text-primary);
            border-color: var(--border); border-bottom-color: var(--ivory-card);
            margin-bottom: -1px;
        }
        .tab-btn:hover:not(.active) { color: var(--text-secondary); background: var(--ivory-dark); }
        .tab-panel { display: none; padding: 14px; }
        .tab-panel.active { display: block; }

        /* Animated number */
        .animated-num { transition: all 0.3s ease; }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="header" id="site-header">
    <div class="header-left">
        <div class="header-logo">
            <!-- Fire/Forest Icon -->
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" rx="6" fill="rgba(255,255,255,0.1)"/>
                <path d="M16 4C16 4 10 9 10 15C10 17 11 18.5 12.5 19.5C12.5 19.5 12 18 13 17C13 17 14 20 16 21C18 20 19 17 19 17C20 18 19.5 19.5 19.5 19.5C21 18.5 22 17 22 15C22 9 16 4 16 4Z" fill="#FFD166"/>
                <path d="M16 14C16 14 13 17 13 19.5C13 21.4 14.3 23 16 23C17.7 23 19 21.4 19 19.5C19 17 16 14 16 14Z" fill="#FF6B35"/>
                <path d="M8 22C8 22 8 26 12 27H20C24 26 24 22 24 22" stroke="#4CAF72" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M10 24C10 24 12 23 14 24" stroke="#4CAF72" stroke-width="1" stroke-linecap="round"/>
                <path d="M18 24C18 24 20 23 22 24" stroke="#4CAF72" stroke-width="1" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <div class="header-title">Forest Fire Monitoring System</div>
            <div class="header-subtitle">IoT Real-Time Early Warning · LoRaWAN Network</div>
        </div>
        <div class="sim-badge">⚡ Simulasi</div>
    </div>
    <div class="header-right">
        <div class="realtime-clock" id="realtime-clock">--:--:--</div>
        <div class="conn-status">
            <div class="conn-dot"></div>
            <span>4/5 Node Online</span>
        </div>
        <div class="conn-status" style="color: #A8C4B0; font-size: 10px;">
            📡 LoRaWAN 915 MHz
        </div>
    </div>
</header>

<!-- ===== MAIN DASHBOARD ===== -->
<div class="dashboard">

    <!-- STATUS ALERT BANNER -->
    <div id="alert-banner" class="alert-banner alert-danger">
        <span class="alert-icon">🔥</span>
        <div class="alert-text">
            <div class="alert-label" id="alert-level">⚠ BAHAYA — Potensi Kebakaran Terdeteksi</div>
            <div id="alert-desc" style="font-size:11px; margin-top:2px;">Sensor JL-SN-101 (Lembah Merapi) mencatat suhu kanopi kritis 44.8°C dengan asap MQ-2 tinggi. Segera lakukan verifikasi lapangan.</div>
        </div>
        <div style="text-align:right; font-size:10px; color:inherit; opacity:0.7; white-space:nowrap;">
            <div>Update: <span id="last-update" style="font-family:'JetBrains Mono',monospace;">21:37:51</span></div>
            <div style="margin-top:2px;">Interval: 30 detik</div>
        </div>
    </div>

    <!-- ===== SENSOR CARDS ===== -->
    <div class="sensor-cards-row">
        <div class="sensor-card">
            <div class="sensor-card-accent accent-red"></div>
            <div class="sensor-card-icon">🌡️</div>
            <div class="sensor-card-label">Suhu Kanopi · DHT11</div>
            <div class="sensor-card-value temp-critical" id="card-temp">44.8<span class="sensor-card-unit">°C</span></div>
            <div class="sensor-card-sub">Node: JL-SN-101 · Lembah Merapi</div>
            <div class="sensor-card-sub">Rata-rata 7-hari: 32.1°C &nbsp;|&nbsp; Maks: 44.8°C</div>
            <span class="sensor-card-status status-kritis">KRITIS</span>
        </div>

        <div class="sensor-card">
            <div class="sensor-card-accent accent-blue"></div>
            <div class="sensor-card-icon">💧</div>
            <div class="sensor-card-label">Kelembapan Relatif · DHT11</div>
            <div class="sensor-card-value" style="color:#2980B9;" id="card-hum">11.2<span class="sensor-card-unit">%</span></div>
            <div class="sensor-card-sub">Node: JL-SN-101 · Lembah Merapi</div>
            <div class="sensor-card-sub">Threshold kritis: &lt;25% &nbsp;|&nbsp; Rata-rata: 42.8%</div>
            <span class="sensor-card-status status-kritis">KRITIS</span>
        </div>

        <div class="sensor-card">
            <div class="sensor-card-accent accent-orange"></div>
            <div class="sensor-card-icon">💨</div>
            <div class="sensor-card-label">Gas / Asap · Sensor MQ-2</div>
            <div class="sensor-card-value" style="color:var(--orange);" id="card-gas">842<span class="sensor-card-unit"> ppm</span></div>
            <div class="sensor-card-sub">Node: JL-SN-101 · Lembah Merapi</div>
            <div class="sensor-card-sub">Baseline: 120 ppm &nbsp;|&nbsp; Threshold: 500 ppm</div>
            <span class="sensor-card-status status-kritis">KRITIS</span>
        </div>

        <div class="sensor-card">
            <div class="sensor-card-accent accent-green"></div>
            <div class="sensor-card-icon">🛡️</div>
            <div class="sensor-card-label">Status Potensi Kebakaran</div>
            <div class="sensor-card-value temp-critical" style="font-size:20px; margin-top:4px;" id="card-fire-status">BAHAYA</div>
            <div class="sensor-card-sub" style="margin-top:4px;">Algoritma: Multi-sensor threshold fusion</div>
            <div class="sensor-card-sub">Model AI: <em style="color:var(--text-muted);">Belum aktif (siap integrasi)</em></div>
            <span class="sensor-card-status status-kritis" id="card-fire-badge">🔥 Kebakaran Potensial</span>
        </div>
    </div>

    <!-- ===== TELEMETRY TABLE ===== -->
    <div class="card sensor-table-card">
        <div class="card-header">
            <div class="card-header-title">
                <span class="card-icon">📡</span>
                <div>
                    <h2>Telemetri Sensor Lapangan &amp; Status Konektivitas LoRaWAN</h2>
                    <small>Data real-time 915 MHz dikumpulkan langsung dari stasiun pengawas kanopi dan permukaan lantai hutan.</small>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="table-meta">Tampilan: 5 dari 52 node</span>
                <button class="refresh-btn" onclick="refreshSimulation()" id="refresh-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Telemetri
                </button>
            </div>
        </div>
        <div class="table-scroll">
            <table class="sensor-table" id="sensor-table">
                <thead>
                    <tr>
                        <th>ID Sensor</th>
                        <th>Lokasi / Tophonimi</th>
                        <th>Koneksi &amp; Latensi</th>
                        <th>Suhu Kanopi</th>
                        <th>Kelembapan Relatif</th>
                        <th>Gas CO / Smoke</th>
                        <th>PM2.5 Density</th>
                        <th>Tegangan Solar/Baterai</th>
                        <th>Aksi Diagnostik</th>
                    </tr>
                </thead>
                <tbody id="sensor-tbody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== CHARTS + RIGHT PANEL ===== -->
    <div class="charts-row">

        <!-- MAIN CHART CARD (with tabs) -->
        <div class="card" style="overflow:visible;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:linear-gradient(135deg,#1A3D2B,#2D5A3D); border-bottom:2px solid var(--yellow); border-radius:8px 8px 0 0;">
                <div class="card-header-title">
                    <span class="card-icon">📈</span>
                    <div>
                        <h2 style="font-size:12px;font-weight:700;text-transform:uppercase;color:#fff;letter-spacing:.03em;">Korelasi Crossover Suhu vs Kelembapan Relatif</h2>
                        <small style="font-size:10px;color:#A8C4B0;">Saat kurva suhu memotong kurva kelembapan kanopi (&lt;25%), probabilitas kebakaran melonjak.</small>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="crossover-label" id="crossover-label">CROSSOVER @ 13:00 WIB</span>
                    <div style="display:flex;gap:4px;">
                        <button onclick="setChartRange(24)" class="refresh-btn" id="range-24" style="padding:3px 8px;font-size:9px;">24 Jam</button>
                        <button onclick="setChartRange(7)" class="refresh-btn" id="range-7" style="padding:3px 8px;font-size:9px;">7 Hari</button>
                        <button onclick="setChartRange(30)" class="refresh-btn" id="range-30" style="padding:3px 8px;font-size:9px;">30 Hari</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-legend">
                    <div class="legend-row"><div class="legend-dot" style="background:#C0392B;"></div><span>Suhu Kanopi (°C)</span></div>
                    <div class="legend-row"><div class="legend-dot" style="background:#2980B9;"></div><span>Kelembapan Relatif (%)</span></div>
                    <div class="legend-row"><div class="legend-dot" style="background:#E8B400;"></div><span>Indeks FFMC Fine Fuel</span></div>
                </div>
                <div class="chart-container" style="height:240px; margin-top:10px;">
                    <canvas id="main-chart"></canvas>
                </div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:6px;display:flex;justify-content:space-between;">
                    <span>Stasiun Referensi: AWS Menara 84 Lembah Merapi</span>
                    <span style="color:var(--orange);">Deviasi: +38% Kering dari Siklus 10-Tahun</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="right-panel">

            <!-- Fire Risk Index -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title">
                        <span class="card-icon">🔥</span>
                        <div>
                            <h2>Indeks Kemudahan Terbakar Serasah</h2>
                            <small>FFMC (Fine Fuel Moisture Code) — Real-time</small>
                        </div>
                    </div>
                    <span style="font-size:12px;cursor:pointer;color:#A8C4B0;">↻</span>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <!-- Circular gauge -->
                        <div style="position:relative;width:90px;height:90px;flex-shrink:0;">
                            <svg width="90" height="90" viewBox="0 0 90 90">
                                <circle cx="45" cy="45" r="38" fill="none" stroke="#E8DFC8" stroke-width="8"/>
                                <circle cx="45" cy="45" r="38" fill="none" stroke="#C0392B" stroke-width="8"
                                    stroke-dasharray="200 239"
                                    stroke-dashoffset="60"
                                    stroke-linecap="round"
                                    transform="rotate(-90 45 45)"
                                    id="ffmc-arc"/>
                                <text x="45" y="42" text-anchor="middle" font-family="JetBrains Mono" font-size="18" font-weight="800" fill="#C0392B" id="ffmc-val">92.4</text>
                                <text x="45" y="57" text-anchor="middle" font-family="Inter" font-size="9" font-weight="600" fill="#7A9482">FFMC</text>
                            </svg>
                        </div>
                        <div style="flex:1;">
                            <div style="background:#FFD4D4;border:1px solid #E87A5A;border-radius:5px;padding:6px 10px;margin-bottom:8px;">
                                <div style="font-size:10px;font-weight:800;color:#8B2A10;text-transform:uppercase;letter-spacing:.06em;">⚠ EKSTREM ARAS RF</div>
                                <div style="font-size:10px;color:#8B2A10;margin-top:3px;line-height:1.4;">Serasah daun pinus dan gambut kering mampu menyala dengan percikan api sekecil 0.05 Joule.</div>
                            </div>
                            <div style="font-size:10px;color:var(--text-muted);">
                                Laju rambat: <strong style="color:var(--red-bright);">1.8 m/menit</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PM2.5 -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title">
                        <span class="card-icon">💨</span>
                        <div>
                            <h2>Kerapatan Partikulat Asap (PM2.5)</h2>
                        </div>
                    </div>
                    <span style="font-size:12px;cursor:pointer;color:#A8C4B0;">≡</span>
                </div>
                <div class="card-body">
                    <div class="pm-value" id="pm-val">182 <span class="pm-unit">μg/m³</span></div>
                    <div class="pm-bar" style="margin-top:8px;">
                        <div class="pm-fill" id="pm-bar" style="width:72%;background:linear-gradient(90deg,#4CAF72,#E8B400,#D4621A,#C0392B);"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--text-muted);margin-bottom:6px;">
                        <span>0</span><span>50</span><span>100</span><span>150</span><span>250+</span>
                    </div>
                    <div class="pm-level-label">⚠ SANGAT TIDAK SEHAT (ISPU 248)</div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">Masker Respirator Disarankan</div>
                </div>
            </div>

        </div>
    </div>

    <!-- ===== BOTTOM GRID ===== -->
    <div class="bottom-grid">

        <!-- WEBCAM -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title">
                    <span class="card-icon">📷</span>
                    <div>
                        <h2>Monitoring Webcam — Zona Bahaya</h2>
                        <small>Live feed / snapshot lingkungan hutan — Integrasi siap</small>
                    </div>
                </div>
                <span style="font-size:10px;color:#A8C4B0;background:rgba(255,255,255,0.1);padding:3px 8px;border-radius:4px;">CAM-01 · JL-SN-101</span>
            </div>
            <div class="card-body">
                <div class="webcam-area">
                    <div class="webcam-overlay"></div>
                    <div class="webcam-corner wc-tl"></div>
                    <div class="webcam-corner wc-tr"></div>
                    <div class="webcam-corner wc-bl"></div>
                    <div class="webcam-corner wc-br"></div>
                    <div class="webcam-rec"><div class="rec-dot"></div>SIAP</div>
                    <div class="webcam-icon">📷</div>
                    <div class="webcam-status">Webcam Belum Terhubung</div>
                    <div class="webcam-note">Integrasi webcam/snapshot kamera IP tersedia. Tambahkan konfigurasi URL stream untuk mengaktifkan.</div>
                </div>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button class="refresh-btn" style="background:var(--green-mid);border-color:var(--green-accent);flex:1;justify-content:center;padding:6px;">📡 Hubungkan Kamera</button>
                    <button class="refresh-btn" style="flex:1;justify-content:center;padding:6px;">🖼️ Ambil Snapshot</button>
                </div>
                <div style="margin-top:8px;background:var(--ivory-dark);border-radius:6px;padding:8px 10px;font-size:10px;color:var(--text-muted);">
                    <strong>Integrasi AI Deteksi Api:</strong> Slot untuk model YOLO/CNN dari dataset Kaggle Forest Fire. Status: <em style="color:var(--orange);">Belum diimplementasi</em>
                </div>
            </div>
        </div>

        <!-- ALERT PANEL -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title">
                    <span class="card-icon">🚨</span>
                    <div>
                        <h2>Panel Peringatan Aktif</h2>
                        <small>Status berdasarkan aturan threshold yang dikonfigurasi</small>
                    </div>
                </div>
                <div id="main-status-badge" style="background:var(--red-bright);color:#fff;font-size:10px;font-weight:800;padding:4px 10px;border-radius:4px;letter-spacing:.06em;">BAHAYA</div>
            </div>
            <div class="card-body" id="alert-panel">
                <!-- populated by JS -->
            </div>
        </div>

        <!-- HISTORY -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title">
                    <span class="card-icon">📋</span>
                    <div>
                        <h2>Riwayat Pembacaan Sensor</h2>
                        <small>Catatan 10 pembacaan terakhir</small>
                    </div>
                </div>
            </div>
            <div class="table-scroll">
                <table class="history-table" id="history-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Node</th>
                            <th>Suhu</th>
                            <th>RH%</th>
                            <th>MQ-2</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody"></tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MQ-2 CHART (full width) -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title">
                <span class="card-icon">💨</span>
                <div>
                    <h2>Tren Gas &amp; Asap MQ-2 — Semua Node Aktif</h2>
                    <small>Pembacaan PPM sensor gas/asap per node dalam 24 jam terakhir</small>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:10px;color:#A8C4B0;">
                <span style="background:rgba(192,57,43,0.3);color:#ff9980;padding:3px 8px;border-radius:4px;font-weight:700;">Threshold BAHAYA: 500 ppm</span>
                <span style="background:rgba(232,180,0,0.2);color:#F5C842;padding:3px 8px;border-radius:4px;font-weight:700;">Threshold WASPADA: 250 ppm</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:180px;">
                <canvas id="gas-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- MINI STATS ROW -->
    <div class="sensor-cards-row">
        <div class="card">
            <div class="card-header"><div class="card-header-title"><span class="card-icon">⚡</span><h2>Tegangan Solar</h2></div></div>
            <div class="card-body">
                <div class="mini-stats">
                    <div class="mini-stat"><div class="mini-stat-val" style="color:var(--green-accent);">4.18V</div><div class="mini-stat-lbl">JL-SN-101</div></div>
                    <div class="mini-stat"><div class="mini-stat-val" style="color:var(--green-accent);">4.05V</div><div class="mini-stat-lbl">JL-SN-102</div></div>
                    <div class="mini-stat"><div class="mini-stat-val" style="color:var(--green-accent);">4.22V</div><div class="mini-stat-lbl">JL-SN-103</div></div>
                    <div class="mini-stat"><div class="mini-stat-val" style="color:var(--red);" id="volt-104">2.10V</div><div class="mini-stat-lbl">JL-SN-104</div></div>
                </div>
                <div style="margin-top:8px;font-size:10px;color:var(--orange);">⚠ JL-SN-104 baterai kritis — perlu penggantian</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-header-title"><span class="card-icon">🌿</span><h2>Kondisi Vegetasi</h2></div></div>
            <div class="card-body">
                <div style="font-size:11px;line-height:1.8;color:var(--text-secondary);">
                    <div>🌱 <strong>Tutupan Kanopi:</strong> 68.4% (Sedang)</div>
                    <div>💧 <strong>Kelembapan Tanah:</strong> 18.2% vol.</div>
                    <div>🍂 <strong>Akumulasi Serasah:</strong> 4.2 cm</div>
                    <div>💨 <strong>Kecepatan Angin:</strong> 2.3 m/s (BL)</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-header-title"><span class="card-icon">🗺️</span><h2>Zona Monitoring</h2></div></div>
            <div class="card-body">
                <div style="font-size:11px;line-height:1.8;color:var(--text-secondary);">
                    <div>📍 <strong>Lembah Merapi Bawah</strong> — BAHAYA</div>
                    <div>📍 <strong>Bukit Pinus Lereng Timur</strong> — Waspada</div>
                    <div>📍 <strong>Hutan Lindung Cekungan</strong> — Normal</div>
                    <div>📍 <strong>Lereng Barat Terjal</strong> — Offline</div>
                    <div>📍 <strong>Perbatasan Konsesi Kayu</strong> — Normal</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-header-title"><span class="card-icon">🤖</span><h2>Integrasi AI &amp; IoT</h2></div></div>
            <div class="card-body">
                <div style="font-size:11px;line-height:1.9;color:var(--text-secondary);">
                    <div>🔌 <strong>IoT LoRaWAN:</strong> <span style="color:var(--green-accent);">Siap terhubung</span></div>
                    <div>📊 <strong>Dataset Kaggle:</strong> <span style="color:var(--yellow);">Belum dimuat</span></div>
                    <div>🧠 <strong>Model AI (YOLO/CNN):</strong> <span style="color:var(--orange);">Belum diimplementasi</span></div>
                    <div>📡 <strong>MQTT Broker:</strong> <span style="color:var(--yellow);">Siap dikonfigurasi</span></div>
                    <div>💾 <strong>Database:</strong> <span style="color:var(--green-accent);">Laravel + MySQL</span></div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /dashboard -->

<script>
// ===== SIMULATION DATA =====
const SENSOR_NODES = [
    {
        id: 'JL-SN-101',
        location: 'Sektor D-12 Lembah Merapi',
        subloc: '(Ngurai Bawah)',
        online: true, latency: 24,
        temp: 44.8, tempDelta: +12.6,
        hum: 11.2, humLabel: 'KRITIS',
        gas: 842, pm25: 218,
        volt: '4.18V Solar', voltLabel: 'Peak',
        action: 'isolasi', actionLabel: 'ISOLASI SEKTOR',
        status: 'bahaya'
    },
    {
        id: 'JL-SN-102',
        location: 'Sektor C-04 Bukit Pinus',
        subloc: '(Lereng Timur)',
        online: true, latency: 15,
        temp: 31.2, tempDelta: null,
        hum: 38.4, humLabel: '',
        gas: 340, pm25: 32,
        volt: '4.05V Solar', voltLabel: '',
        action: 'ping', actionLabel: 'PING NODE',
        status: 'waspada'
    },
    {
        id: 'JL-SN-103',
        location: 'Sektor A-01 Hutan Lindung',
        subloc: '(Cekungan Hulu)',
        online: true, latency: 32,
        temp: 27.8, tempDelta: null,
        hum: 68.1, humLabel: '',
        gas: 210, pm25: 14,
        volt: '4.22V Solar', voltLabel: '',
        action: 'ping', actionLabel: 'PING NODE',
        status: 'normal'
    },
    {
        id: 'JL-SN-104',
        location: 'Sektor B-07 Lereng Barat',
        subloc: '(Punggangan Terjal)',
        online: false, latency: null,
        temp: null, tempDelta: null,
        hum: null, humLabel: '',
        gas: null, pm25: null,
        volt: '2.10V Kritis', voltLabel: 'Drop',
        action: 'diagnostik', actionLabel: 'DIAGNOSTIK',
        status: 'offline'
    },
    {
        id: 'JL-SN-109',
        location: 'Sektor D-08 Perbatasan Konsesi',
        subloc: 'Kayu',
        online: true, latency: 45,
        temp: 37.4, tempDelta: null,
        hum: 19.6, humLabel: '',
        gas: 490, pm25: 88,
        volt: '3.92V Regular', voltLabel: '',
        action: 'rekalibrasi', actionLabel: 'REKALIBRASI',
        status: 'waspada'
    }
];

let simulationData = {};
let mainChart, gasChart;
let chartRange = 24;

// ===== CLOCK =====
function updateClock() {
    const now = new Date();
    const wib = new Date(now.getTime() + (7 * 60 * 60 * 1000 - now.getTimezoneOffset() * 60 * 1000));
    const h = String(wib.getHours()).padStart(2,'0');
    const m = String(wib.getMinutes()).padStart(2,'0');
    const s = String(wib.getSeconds()).padStart(2,'0');
    document.getElementById('realtime-clock').textContent = `${h}:${m}:${s} WIB`;
    document.getElementById('last-update').textContent = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();

// ===== RENDER TABLE =====
function renderTable() {
    const tbody = document.getElementById('sensor-tbody');
    tbody.innerHTML = '';

    SENSOR_NODES.forEach(node => {
        const tr = document.createElement('tr');
        if (!node.online) tr.classList.add('offline-row');

        // Temperature display
        let tempHtml = '—.-°C <small style="color:var(--text-muted);font-size:10px;">(Last 32.1°)</small>';
        let tempClass = 'temp-normal';
        if (node.temp !== null) {
            tempClass = node.temp >= 40 ? 'temp-critical' : (node.temp >= 35 ? 'temp-warning' : 'temp-normal');
            const deltaHtml = node.tempDelta ? `<span class="temp-delta ${node.tempDelta > 0 ? 'delta-up' : 'delta-neutral'}">${node.tempDelta > 0 ? '+' : ''}${node.tempDelta}°</span>` : '';
            tempHtml = `<span class="${tempClass}" style="font-family:'JetBrains Mono',monospace;font-weight:600;">${node.temp.toFixed(1)}°C</span>${deltaHtml}`;
        }

        // Humidity
        let humHtml = '—.— % <small style="color:var(--text-muted);font-size:10px;">(Last 21%)</small>';
        if (node.hum !== null) {
            const humClass = node.hum < 25 ? 'temp-critical' : (node.hum < 40 ? 'temp-warning' : 'temp-normal');
            const humBadge = node.humLabel ? `<span style="background:#FFD4D4;color:#8B0000;font-size:9px;font-weight:700;padding:1px 5px;border-radius:3px;margin-left:4px;">${node.humLabel}</span>` : '';
            humHtml = `<span class="${humClass}" style="font-family:'JetBrains Mono',monospace;font-weight:600;">${node.hum.toFixed(1)}%</span>${humBadge}`;
        }

        // Gas
        let gasHtml = '— ppm';
        if (node.gas !== null) {
            const gasClass = node.gas >= 500 ? 'temp-critical' : (node.gas >= 250 ? 'temp-warning' : 'temp-normal');
            gasHtml = `<span class="${gasClass}" style="font-family:'JetBrains Mono',monospace;font-weight:600;">${node.gas} ppm</span>`;
        }

        // PM2.5
        let pmHtml = '— μg/m³';
        if (node.pm25 !== null) {
            const pmClass = node.pm25 >= 150 ? 'temp-critical' : (node.pm25 >= 55 ? 'temp-warning' : 'temp-normal');
            pmHtml = `<span class="${pmClass}" style="font-family:'JetBrains Mono',monospace;">${node.pm25} μg/m³</span>`;
        }

        // Voltage
        const voltClass = node.volt.includes('Kritis') ? 'style="color:var(--red);font-weight:600;"' : '';
        const voltLabel = node.voltLabel ? `<span style="font-size:9px;color:var(--text-muted);">${node.voltLabel}</span>` : '';

        // Action button
        const actionClass = {
            'isolasi': 'action-isolasi',
            'ping': 'action-ping',
            'rekalibrasi': 'action-rekalibrasi',
            'diagnostik': 'action-diagnostik'
        }[node.action] || 'action-ping';

        // Online badge
        const connBadge = node.online
            ? `<span class="status-badge status-online"><span class="status-online-dot"></span>ONLINE (${node.latency}ms)</span>`
            : `<span class="status-badge status-offline"><span class="status-offline-dot"></span>OFFLINE<br><span style="font-size:8px;letter-spacing:.03em;">TIMEOUT</span></span>`;

        tr.innerHTML = `
            <td>
                <div class="sensor-id">${node.id}</div>
            </td>
            <td>
                <div class="location-name">${node.location}</div>
                <div class="location-sub">${node.subloc}</div>
            </td>
            <td>${connBadge}</td>
            <td>${tempHtml}</td>
            <td>${humHtml}</td>
            <td>${gasHtml}</td>
            <td>${pmHtml}</td>
            <td>
                <span ${voltClass}>${node.volt}</span><br>
                ${voltLabel}
            </td>
            <td>
                <button class="action-btn ${actionClass}">${node.actionLabel}</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ===== GENERATE CHART DATA =====
function generateTimeLabels(hours) {
    const labels = [];
    const now = new Date();
    const points = hours === 24 ? 24 : (hours === 7 ? 7 : 30);
    const unit = hours === 24 ? 'hour' : 'day';
    for (let i = points - 1; i >= 0; i--) {
        if (unit === 'hour') {
            const d = new Date(now - i * 3600000);
            labels.push(d.getHours().toString().padStart(2,'0') + ':00');
        } else {
            const d = new Date(now - i * 86400000);
            labels.push((d.getDate()) + '/' + (d.getMonth()+1));
        }
    }
    return labels;
}

function generateSensorSeries(base, variance, points, trend = 0) {
    const data = [];
    let val = base;
    for (let i = 0; i < points; i++) {
        val += (Math.random() - 0.5) * variance + trend;
        val = Math.max(base * 0.5, Math.min(base * 1.8, val));
        data.push(Math.round(val * 10) / 10);
    }
    return data;
}

function buildMainChart() {
    const points = chartRange === 24 ? 24 : (chartRange === 7 ? 7 : 30);
    const labels = generateTimeLabels(chartRange);
    const tempData = generateSensorSeries(28, 4, points, 0.4);
    const humData = generateSensorSeries(45, 6, points, -0.5);
    const ffmcData = generateSensorSeries(65, 8, points, 0.6);

    // Make crossover visible: inject spike at ~60% of timeline
    const crossIdx = Math.floor(points * 0.6);
    tempData[crossIdx] = 44.8;
    humData[crossIdx] = 11.2;

    if (mainChart) mainChart.destroy();

    const ctx = document.getElementById('main-chart').getContext('2d');

    // Danger zone fill plugin
    const dangerPlugin = {
        id: 'danger-zone',
        beforeDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea) return;
            const y25 = scales.y.getPixelForValue(25);
            ctx.save();
            ctx.fillStyle = 'rgba(192, 57, 43, 0.06)';
            ctx.fillRect(chartArea.left, chartArea.top, chartArea.width, y25 - chartArea.top);
            ctx.strokeStyle = 'rgba(192, 57, 43, 0.3)';
            ctx.setLineDash([5, 4]);
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(chartArea.left, y25);
            ctx.lineTo(chartArea.right, y25);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.restore();
        }
    };

    mainChart = new Chart(ctx, {
        type: 'line',
        plugins: [dangerPlugin],
        data: {
            labels,
            datasets: [
                {
                    label: 'Suhu Kanopi (°C)',
                    data: tempData,
                    borderColor: '#C0392B',
                    backgroundColor: 'rgba(192,57,43,0.08)',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Kelembapan Relatif (%)',
                    data: humData,
                    borderColor: '#2980B9',
                    backgroundColor: 'rgba(41,128,185,0.06)',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Indeks FFMC Fine Fuel',
                    data: ffmcData,
                    borderColor: '#E8B400',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.4,
                    borderDash: [6, 3],
                    yAxisID: 'y2'
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1A3D2B',
                    titleColor: '#A8C4B0',
                    bodyColor: '#fff',
                    borderColor: '#E8B400',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(212,201,168,0.4)', lineWidth: 0.5 },
                    ticks: { font: { size: 9, family: 'JetBrains Mono' }, color: '#7A9482', maxRotation: 0 }
                },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(212,201,168,0.4)', lineWidth: 0.5 },
                    ticks: { font: { size: 9, family: 'JetBrains Mono' }, color: '#7A9482' },
                    title: { display: true, text: '°C / %', color: '#7A9482', font: { size: 9 } }
                },
                y2: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { font: { size: 9, family: 'JetBrains Mono' }, color: '#E8B400' },
                    title: { display: true, text: 'FFMC', color: '#E8B400', font: { size: 9 } }
                }
            }
        }
    });
}

function buildGasChart() {
    const labels = generateTimeLabels(24);
    const colors = ['#C0392B', '#2980B9', '#4CAF72', '#E8B400'];
    const nodes = ['JL-SN-101', 'JL-SN-102', 'JL-SN-103', 'JL-SN-109'];
    const bases = [700, 300, 180, 430];

    const datasets = nodes.map((n, i) => ({
        label: n,
        data: generateSensorSeries(bases[i], 80, 24, i === 0 ? 5 : -1),
        borderColor: colors[i],
        backgroundColor: colors[i] + '22',
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.4,
        fill: true
    }));

    if (gasChart) gasChart.destroy();
    const ctx2 = document.getElementById('gas-chart').getContext('2d');

    // Threshold plugin
    const threshPlugin = {
        id: 'thresholds',
        beforeDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea) return;
            [{ val: 500, color: 'rgba(192,57,43,0.6)', label: 'BAHAYA 500ppm' },
             { val: 250, color: 'rgba(232,180,0,0.5)', label: 'WASPADA 250ppm' }].forEach(t => {
                const y = scales.y.getPixelForValue(t.val);
                ctx.save();
                ctx.strokeStyle = t.color;
                ctx.setLineDash([6, 4]);
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(chartArea.left, y);
                ctx.lineTo(chartArea.right, y);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.restore();
            });
        }
    };

    gasChart = new Chart(ctx2, {
        type: 'line',
        plugins: [threshPlugin],
        data: { labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true, position: 'top',
                    labels: { font: { size: 10, family: 'JetBrains Mono' }, color: '#4A5E52', boxWidth: 12, boxHeight: 12 }
                },
                tooltip: {
                    backgroundColor: '#1A3D2B', titleColor: '#A8C4B0', bodyColor: '#fff',
                    borderColor: '#4CAF72', borderWidth: 1
                }
            },
            scales: {
                x: { grid: { color: 'rgba(212,201,168,0.4)' }, ticks: { font: { size: 9, family: 'JetBrains Mono' }, color: '#7A9482', maxRotation: 0 } },
                y: {
                    grid: { color: 'rgba(212,201,168,0.4)' },
                    ticks: { font: { size: 9, family: 'JetBrains Mono' }, color: '#7A9482' },
                    title: { display: true, text: 'PPM', color: '#7A9482', font: { size: 9 } }
                }
            }
        }
    });
}

// ===== RENDER ALERTS =====
function renderAlerts() {
    const alerts = [
        { icon: '🔥', title: 'BAHAYA — Potensi Kebakaran', desc: 'JL-SN-101: Suhu 44.8°C, Kelembapan 11.2%, Gas 842ppm — Semua threshold terlampaui', time: '21:34:12', level: 'bahaya' },
        { icon: '⚠️', title: 'WASPADA — Suhu Tinggi', desc: 'JL-SN-109: Suhu 37.4°C, MQ-2 490ppm mendekati batas BAHAYA', time: '21:30:05', level: 'waspada' },
        { icon: '🔴', title: 'Node Offline', desc: 'JL-SN-104: Koneksi terputus, tegangan baterai kritis 2.10V', time: '21:15:33', level: 'offline' },
        { icon: '💨', title: 'WASPADA — Gas Meningkat', desc: 'JL-SN-102: MQ-2 naik dari 180 → 340ppm dalam 15 menit', time: '21:10:48', level: 'waspada' },
        { icon: '✅', title: 'NORMAL — JL-SN-103', desc: 'Hutan Lindung Cekungan Hulu: semua parameter dalam batas aman', time: '21:00:00', level: 'normal' }
    ];

    const panel = document.getElementById('alert-panel');
    panel.innerHTML = alerts.map(a => {
        const col = a.level === 'bahaya' ? 'var(--red-bright)' : (a.level === 'waspada' ? 'var(--yellow)' : (a.level === 'normal' ? 'var(--green-accent)' : 'var(--text-muted)'));
        return `
        <div class="alert-item">
            <div class="alert-item-icon">${a.icon}</div>
            <div class="alert-item-text">
                <div class="alert-item-title" style="color:${col};">${a.title}</div>
                <div class="alert-item-desc">${a.desc}</div>
            </div>
            <div class="alert-item-time">${a.time}</div>
        </div>`;
    }).join('');
}

// ===== RENDER HISTORY =====
function renderHistory() {
    const tbody = document.getElementById('history-tbody');
    const now = new Date();
    const records = [];

    for (let i = 0; i < 10; i++) {
        const t = new Date(now.getTime() - i * 30000);
        const h = t.getHours().toString().padStart(2,'0');
        const m = t.getMinutes().toString().padStart(2,'0');
        const s = t.getSeconds().toString().padStart(2,'0');
        const node = SENSOR_NODES[i % SENSOR_NODES.length];
        const temp = node.temp ? (node.temp + (Math.random()-0.5) * 2).toFixed(1) : '—';
        const hum = node.hum ? (node.hum + (Math.random()-0.5) * 3).toFixed(1) : '—';
        const gas = node.gas ? Math.round(node.gas + (Math.random()-0.5) * 50) : '—';
        const status = node.status;
        const badgeClass = status === 'bahaya' ? 'hist-bahaya' : (status === 'waspada' ? 'hist-waspada' : 'hist-normal');
        const badgeLabel = status === 'bahaya' ? 'BAHAYA' : (status === 'waspada' ? 'WASPADA' : (status === 'offline' ? 'OFFLINE' : 'NORMAL'));

        records.push(`<tr>
            <td class="hist-time">${h}:${m}:${s}</td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:10px;">${node.id}</td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:11px;">${temp}°C</td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:11px;">${hum}%</td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:11px;">${gas} ppm</td>
            <td><span class="hist-badge ${badgeClass}">${badgeLabel}</span></td>
        </tr>`);
    }
    tbody.innerHTML = records.join('');
}

// ===== SIMULATE DATA UPDATES =====
function refreshSimulation() {
    const btn = document.getElementById('refresh-btn');
    btn.textContent = '⟳ Memperbarui...';
    btn.disabled = true;

    setTimeout(() => {
        // Slightly vary sensor data for simulation
        SENSOR_NODES.forEach(n => {
            if (n.online && n.temp !== null) {
                n.temp = Math.max(20, Math.min(50, n.temp + (Math.random()-0.4) * 1.5));
                n.temp = Math.round(n.temp * 10) / 10;
                n.hum = Math.max(5, Math.min(90, n.hum + (Math.random()-0.5) * 2));
                n.hum = Math.round(n.hum * 10) / 10;
                n.gas = Math.max(50, Math.min(1200, n.gas + (Math.random()-0.3) * 40));
                n.gas = Math.round(n.gas);
                n.pm25 = Math.max(0, Math.min(300, n.pm25 + (Math.random()-0.4) * 10));
                n.pm25 = Math.round(n.pm25);
            }
        });

        // Update main sensor cards from node 0
        const main = SENSOR_NODES[0];
        document.getElementById('card-temp').innerHTML = `${main.temp.toFixed(1)}<span class="sensor-card-unit">°C</span>`;
        document.getElementById('card-hum').innerHTML = `${main.hum.toFixed(1)}<span class="sensor-card-unit">%</span>`;
        document.getElementById('card-gas').innerHTML = `${main.gas}<span class="sensor-card-unit"> ppm</span>`;

        // Update PM2.5
        const pm = SENSOR_NODES[0].pm25;
        document.getElementById('pm-val').innerHTML = `${pm} <span class="pm-unit">μg/m³</span>`;
        const pmPct = Math.min(100, (pm / 300) * 100);
        document.getElementById('pm-bar').style.width = pmPct + '%';

        renderTable();
        renderHistory();
        buildMainChart();

        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:12px;height:12px;"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Refresh Telemetri';
        btn.disabled = false;
    }, 600);
}

// ===== RANGE BUTTONS =====
function setChartRange(r) {
    chartRange = r;
    ['range-24','range-7','range-30'].forEach(id => {
        document.getElementById(id).style.background = 'rgba(255,255,255,0.12)';
    });
    document.getElementById('range-' + r).style.background = 'var(--yellow)';
    document.getElementById('range-' + r).style.color = 'var(--green-dark)';
    buildMainChart();
}

// ===== AUTO REFRESH =====
let autoRefreshInterval;
function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        SENSOR_NODES.forEach(n => {
            if (n.online && n.temp !== null) {
                n.temp = Math.max(20, Math.min(50, n.temp + (Math.random()-0.4) * 0.8));
                n.temp = Math.round(n.temp * 10) / 10;
                n.hum = Math.max(5, Math.min(90, n.hum + (Math.random()-0.5) * 1));
                n.hum = Math.round(n.hum * 10) / 10;
                n.gas = Math.max(50, Math.min(1200, n.gas + (Math.random()-0.3) * 20));
                n.gas = Math.round(n.gas);
            }
        });

        const main = SENSOR_NODES[0];
        document.getElementById('card-temp').innerHTML = `${main.temp.toFixed(1)}<span class="sensor-card-unit">°C</span>`;
        document.getElementById('card-hum').innerHTML = `${main.hum.toFixed(1)}<span class="sensor-card-unit">%</span>`;
        document.getElementById('card-gas').innerHTML = `${main.gas}<span class="sensor-card-unit"> ppm</span>`;
        renderTable();
        renderHistory();
    }, 30000); // every 30 seconds
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    renderTable();
    renderAlerts();
    renderHistory();
    buildMainChart();
    buildGasChart();
    startAutoRefresh();

    // Set initial range button style
    document.getElementById('range-24').style.background = 'var(--yellow)';
    document.getElementById('range-24').style.color = 'var(--green-dark)';
});
</script>

</body>
</html>
