<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Predictor') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

        <style>
            :root {
                color-scheme: light;
                --bg: #f4efe4;
                --paper: rgba(255, 251, 243, 0.7);
                --card: rgba(255, 248, 237, 0.88);
                --line: rgba(61, 44, 28, 0.14);
                --text: #1e1712;
                --muted: #645447;
                --accent: #d96c2f;
                --accent-strong: #b74f1d;
                --teal: #1e7a78;
                --shadow: 0 30px 80px rgba(64, 38, 20, 0.12);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: 'Space Grotesk', sans-serif;
                color: var(--text);
                background:
                    radial-gradient(circle at top left, rgba(217, 108, 47, 0.18), transparent 32%),
                    radial-gradient(circle at top right, rgba(30, 122, 120, 0.16), transparent 28%),
                    linear-gradient(180deg, #f9f3ea 0%, var(--bg) 50%, #efe6d8 100%);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .shell {
                position: relative;
                overflow: hidden;
                min-height: 100vh;
                padding: 32px;
            }

            .shell::before,
            .shell::after {
                content: '';
                position: absolute;
                border-radius: 999px;
                filter: blur(14px);
                opacity: 0.6;
                pointer-events: none;
            }

            .shell::before {
                top: 80px;
                left: -80px;
                width: 240px;
                height: 240px;
                background: rgba(217, 108, 47, 0.18);
            }

            .shell::after {
                right: -60px;
                bottom: 120px;
                width: 220px;
                height: 220px;
                background: rgba(30, 122, 120, 0.14);
            }

            .frame {
                position: relative;
                z-index: 1;
                max-width: 1240px;
                margin: 0 auto;
                padding: 28px;
                border: 1px solid var(--line);
                border-radius: 32px;
                background: var(--paper);
                box-shadow: var(--shadow);
                backdrop-filter: blur(12px);
            }

            .topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                margin-bottom: 44px;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 14px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .brand-mark {
                display: grid;
                place-items: center;
                width: 46px;
                height: 46px;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--accent), #f2b46f);
                color: #fff8f1;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.28);
            }

            .top-meta {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.45);
                color: var(--muted);
                font-size: 0.82rem;
            }

            .dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                background: #2e9c68;
                box-shadow: 0 0 0 6px rgba(46, 156, 104, 0.14);
                animation: pulse 2.8s infinite;
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.9fr);
                gap: 28px;
                align-items: stretch;
            }

            .panel,
            .stack-card,
            .signal-card,
            .metric-card {
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--card);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.42);
            }

            .panel {
                padding: 36px;
                animation: rise 0.75s ease-out both;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(217, 108, 47, 0.1);
                color: var(--accent-strong);
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            h1 {
                margin: 20px 0 18px;
                max-width: 11ch;
                font-size: clamp(3rem, 9vw, 6.5rem);
                line-height: 0.92;
                letter-spacing: -0.06em;
            }

            .lead {
                max-width: 56ch;
                margin: 0;
                color: var(--muted);
                font-size: 1.05rem;
                line-height: 1.7;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin-top: 30px;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 14px 20px;
                border-radius: 999px;
                border: 1px solid transparent;
                font-weight: 700;
                transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
            }

            .button:hover {
                transform: translateY(-2px);
            }

            .button-primary {
                background: var(--text);
                color: #fff8f1;
                box-shadow: 0 16px 32px rgba(30, 23, 18, 0.16);
            }

            .button-secondary {
                border-color: var(--line);
                background: rgba(255, 255, 255, 0.5);
                color: var(--text);
            }

            .hero-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
                margin-top: 30px;
            }

            .metric-card {
                padding: 18px;
            }

            .metric-label {
                color: var(--muted);
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .metric-value {
                margin-top: 10px;
                font-size: 1.55rem;
                font-weight: 700;
                letter-spacing: -0.04em;
            }

            .metric-note {
                margin-top: 6px;
                color: var(--muted);
                font-size: 0.92rem;
            }

            .stack-card {
                position: relative;
                padding: 24px;
                overflow: hidden;
                animation: rise 0.9s ease-out both;
            }

            .stack-card::before {
                content: '';
                position: absolute;
                inset: 0;
                background:
                    linear-gradient(135deg, rgba(217, 108, 47, 0.1), transparent 40%),
                    linear-gradient(180deg, transparent, rgba(30, 122, 120, 0.08));
                pointer-events: none;
            }

            .stack-head,
            .stack-row,
            .signals {
                position: relative;
                z-index: 1;
            }

            .stack-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 18px;
            }

            .stack-title {
                font-size: 1.2rem;
                font-weight: 700;
            }

            .mono {
                font-family: 'IBM Plex Mono', monospace;
            }

            .stack-list {
                display: grid;
                gap: 12px;
            }

            .stack-row {
                display: grid;
                grid-template-columns: auto 1fr auto;
                gap: 12px;
                align-items: center;
                padding: 14px 16px;
                border: 1px solid rgba(61, 44, 28, 0.1);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.55);
            }

            .stack-icon {
                display: grid;
                place-items: center;
                width: 42px;
                height: 42px;
                border-radius: 14px;
                font-size: 1.1rem;
                font-weight: 700;
                color: #fff8f1;
            }

            .postgres { background: #32648f; }
            .redis { background: #b84d3f; }
            .queue { background: #1e7a78; }
            .scheduler { background: #9f6a18; }

            .stack-name {
                font-weight: 700;
            }

            .stack-copy {
                color: var(--muted);
                font-size: 0.92rem;
            }

            .badge {
                padding: 8px 10px;
                border-radius: 999px;
                background: rgba(30, 23, 18, 0.08);
                color: var(--text);
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .signals {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
                margin-top: 18px;
            }

            .signal-card {
                padding: 16px;
                background: rgba(255, 255, 255, 0.45);
            }

            .signal-label {
                color: var(--muted);
                font-size: 0.78rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .signal-value {
                margin-top: 8px;
                font-size: 1.15rem;
                font-weight: 700;
            }

            .bottom-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
                margin-top: 22px;
            }

            .section-card {
                padding: 24px;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: rgba(255, 252, 247, 0.72);
                animation: rise 1s ease-out both;
            }

            .section-title {
                margin: 0 0 10px;
                font-size: 1.25rem;
                font-weight: 700;
            }

            .section-copy {
                margin: 0;
                color: var(--muted);
                line-height: 1.7;
            }

            .section-list {
                display: grid;
                gap: 12px;
                margin-top: 18px;
            }

            .list-item {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 12px;
                align-items: start;
                padding-top: 12px;
                border-top: 1px solid rgba(61, 44, 28, 0.1);
            }

            .list-index {
                color: var(--accent-strong);
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.82rem;
                font-weight: 500;
            }

            .list-copy strong {
                display: block;
                margin-bottom: 4px;
            }

            .list-copy span {
                color: var(--muted);
                line-height: 1.6;
            }

            .footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                margin-top: 26px;
                padding-top: 18px;
                border-top: 1px solid rgba(61, 44, 28, 0.12);
                color: var(--muted);
                font-size: 0.92rem;
            }

            @keyframes rise {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.08);
                }
            }

            @media (max-width: 980px) {
                .hero,
                .bottom-grid {
                    grid-template-columns: 1fr;
                }

                .signals,
                .hero-grid {
                    grid-template-columns: 1fr;
                }

                h1 {
                    max-width: none;
                }
            }

            @media (max-width: 640px) {
                .shell {
                    padding: 16px;
                }

                .frame,
                .panel,
                .stack-card,
                .section-card {
                    padding: 20px;
                    border-radius: 22px;
                }

                .topbar,
                .footer {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .stack-row {
                    grid-template-columns: auto 1fr;
                }

                .badge {
                    grid-column: 1 / -1;
                    width: fit-content;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <div class="frame">
                <header class="topbar">
                    <div class="brand">
                        <div class="brand-mark">P</div>
                        <div>
                            <div>Predictor</div>
                            <div class="mono" style="font-size: 0.78rem; color: var(--muted);">No-show intelligence platform</div>
                        </div>
                    </div>

                    <div class="top-meta">
                        <span class="dot"></span>
                        <span>Stack online</span>
                        <span class="mono">Laravel {{ Illuminate\Foundation\Application::VERSION }}</span>
                    </div>
                </header>

                <main>
                    <section class="hero">
                        <article class="panel">
                            <div class="eyebrow">Operational overview</div>
                            <h1>Predict demand. Catch no-shows early.</h1>
                            <p class="lead">
                                Predictor turns operational data into a cleaner forecast loop: PostgreSQL for core records,
                                Redis for speed, queues for background work, and a scheduler that keeps recurring tasks moving
                                without manual intervention.
                            </p>

                            <div class="actions">
                                <a class="button button-primary" href="#stack">Inspect stack</a>
                                <a class="button button-secondary" href="#workflow">See workflow</a>
                            </div>

                            <div class="hero-grid">
                                <div class="metric-card">
                                    <div class="metric-label">Storage</div>
                                    <div class="metric-value">PostgreSQL</div>
                                    <div class="metric-note">Primary transactional source</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Speed layer</div>
                                    <div class="metric-value">Redis</div>
                                    <div class="metric-note">Cache, queue, and session runtime</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Automation</div>
                                    <div class="metric-value">24/7</div>
                                    <div class="metric-note">Workers and scheduled jobs</div>
                                </div>
                            </div>
                        </article>

                        <aside class="stack-card" id="stack">
                            <div class="stack-head">
                                <div>
                                    <div class="stack-title">Application stack</div>
                                    <div class="mono" style="color: var(--muted); font-size: 0.82rem;">docker-compose powered runtime</div>
                                </div>
                                <div class="badge">Healthy topology</div>
                            </div>

                            <div class="stack-list">
                                <div class="stack-row">
                                    <div class="stack-icon postgres">PG</div>
                                    <div>
                                        <div class="stack-name">PostgreSQL</div>
                                        <div class="stack-copy">Persistent operational data and migrations</div>
                                    </div>
                                    <div class="badge mono">:5432</div>
                                </div>

                                <div class="stack-row">
                                    <div class="stack-icon redis">R</div>
                                    <div>
                                        <div class="stack-name">Redis</div>
                                        <div class="stack-copy">Sessions, cache, and queue backend</div>
                                    </div>
                                    <div class="badge mono">:6379</div>
                                </div>

                                <div class="stack-row">
                                    <div class="stack-icon queue">Q</div>
                                    <div>
                                        <div class="stack-name">Queue worker</div>
                                        <div class="stack-copy">Async jobs processed in the background</div>
                                    </div>
                                    <div class="badge mono">queue:work</div>
                                </div>

                                <div class="stack-row">
                                    <div class="stack-icon scheduler">S</div>
                                    <div>
                                        <div class="stack-name">Scheduler</div>
                                        <div class="stack-copy">Recurring tasks executed on cadence</div>
                                    </div>
                                    <div class="badge mono">schedule:work</div>
                                </div>
                            </div>

                            <div class="signals">
                                <div class="signal-card">
                                    <div class="signal-label">Runtime</div>
                                    <div class="signal-value">PHP {{ PHP_VERSION }}</div>
                                </div>
                                <div class="signal-card">
                                    <div class="signal-label">App</div>
                                    <div class="signal-value">{{ config('app.env') }}</div>
                                </div>
                                <div class="signal-card">
                                    <div class="signal-label">Session</div>
                                    <div class="signal-value">{{ config('session.driver') }}</div>
                                </div>
                            </div>
                        </aside>
                    </section>

                    <section class="bottom-grid" id="workflow">
                        <article class="section-card">
                            <h2 class="section-title">What this screen reflects</h2>
                            <p class="section-copy">
                                This is not the default Laravel starter anymore. It mirrors the current local runtime
                                so the project opens with a page that actually describes the system behind it.
                            </p>

                            <div class="section-list">
                                <div class="list-item">
                                    <div class="list-index">01</div>
                                    <div class="list-copy">
                                        <strong>Data enters PostgreSQL</strong>
                                        <span>Core records, migrations, and relational queries live on the primary database service.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div class="list-index">02</div>
                                    <div class="list-copy">
                                        <strong>Transient work moves through Redis</strong>
                                        <span>Fast in-memory storage keeps sessions, queues, and cache traffic responsive.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div class="list-index">03</div>
                                    <div class="list-copy">
                                        <strong>Background execution stays isolated</strong>
                                        <span>Workers and scheduled tasks run in dedicated containers instead of sharing the web request lifecycle.</span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="section-card">
                            <h2 class="section-title">Immediate next moves</h2>
                            <p class="section-copy">
                                The infrastructure is in place. The next useful step is wiring this screen into real product
                                metrics, auth, or domain-specific actions rather than leaving it as a static placeholder.
                            </p>

                            <div class="section-list">
                                <div class="list-item">
                                    <div class="list-index">A</div>
                                    <div class="list-copy">
                                        <strong>Replace placeholders with live KPIs</strong>
                                        <span>Show booking volume, no-show rate, or pending jobs once those metrics exist.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div class="list-index">B</div>
                                    <div class="list-copy">
                                        <strong>Add authenticated routing</strong>
                                        <span>Use this as the public splash page and route signed-in users to the real dashboard.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div class="list-index">C</div>
                                    <div class="list-copy">
                                        <strong>Expose queue and schedule health</strong>
                                        <span>Surface actual worker status instead of static labels when observability is added.</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>
                </main>

                <footer class="footer">
                    <span>{{ config('app.name', 'Predictor') }} running on a custom Laravel landing page.</span>
                    <span class="mono">{{ now()->format('Y-m-d') }} · {{ config('app.timezone') }}</span>
                </footer>
            </div>
        </div>
    </body>
</html>
