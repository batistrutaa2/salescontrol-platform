<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Comercial - SalesControl</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7C3AED;
            --primary-light: #A78BFA;
            --primary-rgb: 124, 58, 237;
            --success: #10B981;
            --success-light: #34D399;
            --info: #06B6D4;
            --info-light: #22D3EE;
            --warning: #F59E0B;
            --warning-light: #FBBF24;
            --danger: #EF4444;
            --danger-light: #F87171;
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --bg-dark: #0B0E1A;
            --bg-card: rgba(255, 255, 255, 0.06);
            --bg-card-hover: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.08);
            --text-primary: #F9FAFB;
            --text-secondary: #D1D5DB;
            --text-muted: #9CA3AF;
            --glass-blur: 16px;
            --radius: 16px;
            --radius-sm: 12px;
            --radius-lg: 24px;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 0 60px rgba(124, 58, 237, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Animated background orbs */
        .bg-orbs {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, var(--primary), transparent);
            top: -10%; left: -5%;
            animation-duration: 25s;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, var(--info), transparent);
            bottom: -10%; right: -5%;
            animation-duration: 20s;
            animation-delay: -5s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, var(--success), transparent);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-duration: 30s;
            animation-delay: -10s;
            opacity: 0.15;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -40px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(40px, 30px) scale(1.02); }
        }

        /* Layout */
        .tv-container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 24px 32px;
        }

        /* Header */
        .tv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: var(--bg-card);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        .tv-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .tv-logo {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--primary-light), var(--info-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .tv-header-center {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .view-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }
        .view-dot.active {
            background: var(--primary-light);
            box-shadow: 0 0 12px rgba(var(--primary-rgb), 0.6);
        }
        .tv-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .tv-date {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .tv-clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* View container */
        .views-wrapper {
            flex: 1;
            position: relative;
            overflow: hidden;
            min-height: 0;
        }
        .view {
            position: absolute;
            inset: 0;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
            pointer-events: none;
            display: flex;
            flex-direction: column;
        }
        .view.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* === VIEW 1: METAS DIÁRIAS === */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        .kpi-card {
            background: var(--bg-card);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }
        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kpi-icon svg { width: 26px; height: 26px; stroke: #fff; }
        .kpi-icon.primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .kpi-icon.success { background: linear-gradient(135deg, var(--success), var(--success-light)); }
        .kpi-icon.info { background: linear-gradient(135deg, var(--info), var(--info-light)); }
        .kpi-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        /* Seller list */
        .sellers-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow: hidden;
        }
        .seller-row {
            display: grid;
            grid-template-columns: 60px 1fr 200px 100px;
            align-items: center;
            gap: 16px;
            background: var(--bg-card);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 20px;
            transition: var(--transition);
            opacity: 0;
            transform: translateX(-20px);
        }
        .seller-row.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .seller-pos {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
        }
        .seller-pos.pos-1 { color: var(--gold); }
        .seller-pos.pos-2 { color: var(--silver); }
        .seller-pos.pos-3 { color: var(--bronze); }
        .seller-name {
            font-size: 1.15rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .seller-progress-wrap {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .seller-progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            overflow: hidden;
        }
        .seller-progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--primary), var(--info));
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .seller-progress-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .seller-pct {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.3rem;
            font-weight: 700;
            text-align: right;
        }
        .pct-green { color: var(--success-light); }
        .pct-blue { color: var(--info-light); }
        .pct-amber { color: var(--warning-light); }
        .pct-red { color: var(--danger-light); }

        /* === VIEW 2: RANKING SEMANAL === */
        .ranking-header-info {
            text-align: center;
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        .ranking-title {
            font-size: 1.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--gold), var(--warning-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .ranking-period {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Podium */
        .podium-section {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 24px;
            flex-shrink: 0;
            min-height: 220px;
        }
        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            width: 220px;
        }
        .podium-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            position: relative;
        }
        .podium-item.first .podium-avatar {
            width: 80px;
            height: 80px;
            font-size: 1.8rem;
            animation: pulse-gold 2s infinite;
        }
        @keyframes pulse-gold {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(255, 215, 0, 0); }
        }
        .podium-avatar.gold { background: linear-gradient(135deg, #FFD700, #FFA500); }
        .podium-avatar.silver-bg { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); }
        .podium-avatar.bronze-bg { background: linear-gradient(135deg, #CD7F32, #A0522D); }
        .podium-name {
            font-size: 1.05rem;
            font-weight: 700;
            text-align: center;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .podium-score {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.8rem;
            font-weight: 700;
        }
        .podium-item.first .podium-score { color: var(--gold); font-size: 2.2rem; }
        .podium-item.second .podium-score { color: var(--silver); }
        .podium-item.third .podium-score { color: var(--bronze); }
        .podium-block {
            width: 100%;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.6);
        }
        .podium-item.first .podium-block {
            height: 100px;
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.3), rgba(255, 215, 0, 0.08));
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-bottom: none;
        }
        .podium-item.second .podium-block {
            height: 72px;
            background: linear-gradient(180deg, rgba(192, 192, 192, 0.25), rgba(192, 192, 192, 0.06));
            border: 1px solid rgba(192, 192, 192, 0.15);
            border-bottom: none;
        }
        .podium-item.third .podium-block {
            height: 52px;
            background: linear-gradient(180deg, rgba(205, 127, 50, 0.25), rgba(205, 127, 50, 0.06));
            border: 1px solid rgba(205, 127, 50, 0.15);
            border-bottom: none;
        }

        /* Ranking list (4th+) */
        .ranking-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow: hidden;
        }
        .ranking-row {
            display: grid;
            grid-template-columns: 60px 1fr 120px 100px;
            align-items: center;
            gap: 16px;
            background: var(--bg-card);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 20px;
            opacity: 0;
            transform: translateX(-20px);
            transition: var(--transition);
        }
        .ranking-row.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .ranking-pos {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            color: var(--text-muted);
        }
        .ranking-name {
            font-size: 1.1rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ranking-total {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            color: var(--primary-light);
        }
        .ranking-pct {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            text-align: right;
        }

        /* Footer */
        .tv-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: var(--bg-card);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-top: 16px;
            flex-shrink: 0;
            gap: 20px;
        }
        .footer-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .rotation-bar-wrap {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            overflow: hidden;
        }
        .rotation-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            border-radius: 2px;
            transition: width 0.3s linear;
        }
        .footer-update {
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* View title for daily */
        .view-title-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-shrink: 0;
        }
        .view-title {
            font-size: 1.4rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--primary-light), var(--info-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .view-date {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Loading & No data */
        .loading-screen {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-size: 1.3rem;
            color: var(--text-muted);
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: var(--primary-light);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .no-data-msg {
            text-align: center;
            color: var(--text-muted);
            font-size: 1.2rem;
            padding: 60px 20px;
        }
    </style>
</head>
<body>
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div id="loading-screen" class="loading-screen">
        <div class="loading-spinner"></div>
        Carregando dados...
    </div>

    <div id="tv-app" class="tv-container" style="display: none;">
        <!-- Header -->
        <div class="tv-header">
            <div class="tv-header-left">
                <span class="tv-logo">TV Comercial</span>
            </div>
            <div class="tv-header-center">
                <div class="view-dot active" id="dot-0"></div>
                <div class="view-dot" id="dot-1"></div>
            </div>
            <div class="tv-header-right">
                <span class="tv-date" id="tv-date"></span>
                <span class="tv-clock" id="tv-clock"></span>
            </div>
        </div>

        <!-- Views -->
        <div class="views-wrapper">
            <!-- View 1: Metas Diárias -->
            <div class="view active" id="view-diaria">
                <div class="view-title-bar">
                    <span class="view-title">Metas Diárias</span>
                    <span class="view-date" id="view-diaria-date"></span>
                </div>
                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Meta Total</div>
                            <div class="kpi-value" id="kpi-meta">0</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Realizado</div>
                            <div class="kpi-value" id="kpi-realizado">0</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">% Geral</div>
                            <div class="kpi-value" id="kpi-pct">0%</div>
                        </div>
                    </div>
                </div>
                <div class="sellers-list" id="sellers-list"></div>
            </div>

            <!-- View 2: Ranking Semanal -->
            <div class="view" id="view-ranking">
                <div class="ranking-header-info">
                    <div class="ranking-title">Ranking Semanal</div>
                    <div class="ranking-period" id="ranking-period"></div>
                </div>
                <div class="podium-section" id="podium-section"></div>
                <div class="ranking-list" id="ranking-list"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="tv-footer">
            <span class="footer-label" id="footer-view-label">Metas Diárias</span>
            <div class="rotation-bar-wrap">
                <div class="rotation-bar-fill" id="rotation-bar"></div>
            </div>
            <span class="footer-update">Atualizado: <span id="last-update">--:--:--</span></span>
        </div>
    </div>

    <script>
        const accessToken = @json($accessToken);
        const API_DIARIA = @json(route('tv-comercial.dados', ['token' => $accessToken]));
        const API_RANKING = @json(route('tv-comercial.ranking', ['token' => $accessToken]));
        const ROTATE_INTERVAL = 15000;
        const REFRESH_INTERVAL = 30000;

        let dadosDiaria = null;
        let dadosRanking = null;
        let currentView = 0;
        let views = ['diaria', 'ranking'];
        let rotationTimer = null;
        let rotationStart = 0;
        let rafId = null;

        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('tv-clock').textContent =
                now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const dateOpts = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
            const dateStr = now.toLocaleDateString('pt-BR', dateOpts);
            document.getElementById('tv-date').textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
        }

        // Fetch data
        async function fetchData() {
            try {
                const [resDiaria, resRanking] = await Promise.all([
                    fetch(API_DIARIA).then(r => r.json()),
                    fetch(API_RANKING).then(r => r.json())
                ]);

                if (!resDiaria.error) dadosDiaria = resDiaria;
                if (!resRanking.error) dadosRanking = resRanking;

                document.getElementById('last-update').textContent =
                    new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                renderCurrentView();
                document.getElementById('loading-screen').style.display = 'none';
                document.getElementById('tv-app').style.display = 'flex';
            } catch (e) {
                console.error('Erro ao carregar dados:', e);
            }
        }

        // Determine which views have data
        function getActiveViews() {
            const active = [];
            if (dadosDiaria && dadosDiaria.metas && dadosDiaria.metas.length > 0) active.push('diaria');
            if (dadosRanking && dadosRanking.ranking && dadosRanking.ranking.length > 0) active.push('ranking');
            return active;
        }

        // Render
        function renderCurrentView() {
            const active = getActiveViews();
            if (active.length === 0) return;

            // Update dots
            document.getElementById('dot-0').classList.toggle('active', active.includes('diaria') && views[currentView] === 'diaria');
            document.getElementById('dot-1').classList.toggle('active', active.includes('ranking') && views[currentView] === 'ranking');

            if (views[currentView] === 'diaria') {
                renderDiaria();
                document.getElementById('footer-view-label').textContent = 'Metas Diárias';
            } else {
                renderRanking();
                document.getElementById('footer-view-label').textContent = 'Ranking Semanal';
            }

            // Show/hide views
            document.getElementById('view-diaria').classList.toggle('active', views[currentView] === 'diaria');
            document.getElementById('view-ranking').classList.toggle('active', views[currentView] === 'ranking');
        }

        function renderDiaria() {
            if (!dadosDiaria) return;
            const { metas, totais, data_formatada } = dadosDiaria;

            document.getElementById('view-diaria-date').textContent = data_formatada;
            document.getElementById('kpi-meta').textContent = totais.total_meta;
            document.getElementById('kpi-realizado').textContent = totais.total_realizado;
            document.getElementById('kpi-pct').textContent = totais.percentual_geral + '%';

            const list = document.getElementById('sellers-list');
            list.innerHTML = '';

            metas.forEach((m, i) => {
                const pctClass = getPctClass(m.percentual_concluido);
                const posClass = i < 3 ? 'pos-' + (i + 1) : '';
                const pct = Math.min(m.percentual_concluido, 100);

                const row = document.createElement('div');
                row.className = 'seller-row';
                row.innerHTML = `
                    <div class="seller-pos ${posClass}">${String(i + 1).padStart(2, '0')}</div>
                    <div class="seller-name">${escapeHtml(m.vendedor)}</div>
                    <div class="seller-progress-wrap">
                        <div class="seller-progress-bar">
                            <div class="seller-progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="seller-progress-text">${m.cotacoes_realizadas} / ${m.meta_cotacoes}</div>
                    </div>
                    <div class="seller-pct ${pctClass}">${m.percentual_concluido.toFixed(1)}%</div>
                `;
                list.appendChild(row);

                // Animate in
                setTimeout(() => {
                    row.classList.add('visible');
                    row.querySelector('.seller-progress-fill').style.width = pct + '%';
                }, 80 * i);
            });
        }

        function renderRanking() {
            if (!dadosRanking) return;
            const { ranking, periodo } = dadosRanking;

            document.getElementById('ranking-period').textContent =
                periodo.data_inicio + ' — ' + periodo.data_fim;

            // Podium (top 3)
            const podiumEl = document.getElementById('podium-section');
            podiumEl.innerHTML = '';

            const podiumOrder = [1, 0, 2]; // 2nd, 1st, 3rd
            const podiumClasses = ['second', 'first', 'third'];
            const avatarClasses = ['silver-bg', 'gold', 'bronze-bg'];
            const medals = ['2', '1', '3'];

            podiumOrder.forEach((idx, displayIdx) => {
                if (!ranking[idx]) return;
                const r = ranking[idx];
                const initials = getInitials(r.vendedor);

                const item = document.createElement('div');
                item.className = 'podium-item ' + podiumClasses[displayIdx];
                item.innerHTML = `
                    <div class="podium-avatar ${avatarClasses[displayIdx]}">${initials}</div>
                    <div class="podium-name">${escapeHtml(r.vendedor)}</div>
                    <div class="podium-score">${r.total_realizadas}</div>
                    <div class="podium-block">${medals[displayIdx]}°</div>
                `;
                podiumEl.appendChild(item);
            });

            // Remaining (4th+)
            const listEl = document.getElementById('ranking-list');
            listEl.innerHTML = '';

            ranking.slice(3).forEach((r, i) => {
                const pctClass = getPctClass(r.percentual);
                const row = document.createElement('div');
                row.className = 'ranking-row';
                row.innerHTML = `
                    <div class="ranking-pos">${i + 4}°</div>
                    <div class="ranking-name">${escapeHtml(r.vendedor)}</div>
                    <div class="ranking-total">${r.total_realizadas}</div>
                    <div class="ranking-pct ${pctClass}">${r.percentual}%</div>
                `;
                listEl.appendChild(row);
                setTimeout(() => row.classList.add('visible'), 80 * i);
            });
        }

        // Rotation logic
        function startRotation() {
            const active = getActiveViews();
            if (active.length <= 1) {
                // Only one view with data, stay on it
                if (active.length === 1) {
                    currentView = views.indexOf(active[0]);
                    renderCurrentView();
                }
                document.getElementById('rotation-bar').style.width = '100%';
                return;
            }

            rotationStart = Date.now();
            cancelAnimationFrame(rafId);
            clearTimeout(rotationTimer);

            // Animate progress bar
            function animateBar() {
                const elapsed = Date.now() - rotationStart;
                const pct = Math.min((elapsed / ROTATE_INTERVAL) * 100, 100);
                document.getElementById('rotation-bar').style.width = pct + '%';
                if (pct < 100) rafId = requestAnimationFrame(animateBar);
            }
            rafId = requestAnimationFrame(animateBar);

            rotationTimer = setTimeout(() => {
                // Move to next view that has data
                const active = getActiveViews();
                if (active.length <= 1) return;

                let nextIdx = (currentView + 1) % views.length;
                while (!active.includes(views[nextIdx])) {
                    nextIdx = (nextIdx + 1) % views.length;
                }
                currentView = nextIdx;
                renderCurrentView();
                startRotation();
            }, ROTATE_INTERVAL);
        }

        // Helpers
        function getPctClass(pct) {
            const configuracao = dadosDiaria?.configuracao || dadosRanking?.configuracao;
            if (!configuracao) return 'pct-red';
            if (pct >= 100) return 'pct-green';
            if (pct >= configuracao.percentual_bom) return 'pct-blue';
            if (pct >= configuracao.percentual_atencao) return 'pct-amber';
            return 'pct-red';
        }

        function getInitials(name) {
            return name.split(' ').filter(p => p.length > 0).slice(0, 2).map(p => p[0]).join('').toUpperCase();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Init
        if (accessToken) {
            updateClock();
            setInterval(updateClock, 1000);

            fetchData().then(() => startRotation());
            setInterval(() => {
                fetchData().then(() => {
                    // Re-check active views in case data changed
                    const active = getActiveViews();
                    if (active.length <= 1 && active.length > 0) {
                        currentView = views.indexOf(active[0]);
                        renderCurrentView();
                    }
                });
            }, REFRESH_INTERVAL);
        }
    </script>
</body>
</html>
