<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificá tu IP - WebParaguay</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;900&family=Karla:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary:   #393185;
            --red:       #de091e;
            --dark:      #06031e;
            --gray-text: #9599a3;
            --light-gray:#d4d5d8;
        }

        body {
            font-family: 'Karla', sans-serif;
            min-height: 100vh;
            background: radial-gradient(ellipse at bottom, var(--primary) 0%, var(--dark) 55%, #000 110%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── LEFT COLUMN ── */
        .col-left {
            background: #f8f8f8;
            border-right: 1px solid #eeeeee;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .col-left img {
            height: 88px;
            width: auto;
            object-fit: contain;
            object-position: left;
            margin-bottom: 40px;
        }

        .ip-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-text);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .ip-number {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: var(--primary);
            line-height: 1.1;
            margin-bottom: 32px;
            word-break: break-all;
        }

        .ip-number.loading-ip {
            font-size: 20px;
            color: var(--light-gray);
        }

        .col-left h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #222;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .col-left p {
            font-size: 14px;
            color: #666;
            line-height: 1.7;
        }

        /* ── RIGHT COLUMN ── */
        .col-right {
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }

        /* ── STATUS ROWS ── */
        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .status-row:last-of-type { border-bottom: none; }
        .status-label {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #444;
            font-size: 13px;
        }
        .status-value { color: #555; word-break: break-all; text-align: right; }

        .badge {
            display: inline-block;
            padding: 3px 12px;
            font-size: 12px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        .badge-blocked { background: #fdecea; color: var(--red); }
        .badge-clear   { background: #e8f5e9; color: #2e7d32; }

        /* ── ALERTS ── */
        .alert {
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.5;
            display: none;
            border-left: 4px solid;
        }
        .alert.show { display: block; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-color: #2e7d32; }
        .alert-error   { background: #fdecea; color: #c62828; border-color: var(--red); }
        .alert-warning { background: #fff8e1; color: #e65100; border-color: #fbc02d; }

        /* ── REASON BOX ── */
        .reason-box {
            background: #fff8e1;
            border-left: 4px solid #fbc02d;
            padding: 12px 14px;
            display: none;
        }
        .reason-box.show { display: block; }
        .reason-box-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 12px;
            color: #333;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .reason-box-text { font-size: 13px; color: #666; line-height: 1.5; }

        /* ── UNBLOCK SECTION ── */
        .unblock-section { display: none; }
        .unblock-section.show { display: block; }
        .unblock-section p {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .turnstile-wrapper { margin-bottom: 12px; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover:not(:disabled) { opacity: 0.88; }
        .btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-size: 13px;
            padding: 10px;
        }
        .btn-outline:hover { background: var(--primary); color: #fff; }

        /* ── LOADING SPINNER ── */
        .spinner-row {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray-text);
            font-size: 13px;
            font-family: 'Montserrat', sans-serif;
        }
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid #e0e0e0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── SUPPORT ── */
        .support-section { display: none; }
        .support-section.show { display: block; }
        .support-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 12px;
            color: #444;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .support-buttons { display: flex; gap: 8px; }
        .btn-wa, .btn-mail {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-wa   { background: #25D366; }
        .btn-mail { background: var(--primary); }
        .btn-wa:hover, .btn-mail:hover { opacity: 0.88; }

        .divider {
            border: none;
            border-top: 1px solid #eeeeee;
        }

        /* ── SECTION TOGGLE ── */
        .section { display: none; }
        .section.show { display: block; }

        /* ── FOOTER ── */
        footer {
            margin-top: 24px;
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            font-family: 'Montserrat', sans-serif;
            text-align: center;
        }

        /* ── MOBILE ── */
        @media (max-width: 680px) {
            body { padding: 0; justify-content: flex-start; }
            .card { grid-template-columns: 1fr; max-width: 100%; min-height: 100vh; }
            .col-left { border-right: none; border-bottom: 1px solid #eeeeee; padding: 36px 24px; }
            .col-right { padding: 32px 24px 40px; }
            .ip-number { font-size: 26px; }
            .support-buttons { flex-direction: column; }
            footer { margin: 20px auto; }
        }
    </style>
</head>
<body>

    <div class="card">

        <!-- COLUMNA IZQUIERDA -->
        <div class="col-left">
            <img src="/img/logo.png" alt="WebParaguay">

            <div class="ip-label">Tu IP es</div>
            <div id="displayIp" class="ip-number loading-ip">Detectando...</div>

            <h1>Verificá el estado de tu IP</h1>
            <p>Si estás teniendo problemas para acceder al panel, webmail o correo, es posible que tu IP haya sido bloqueada por seguridad. Revisá tu estado y desbloqueate en segundos.</p>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-right">

            <!-- Loading -->
            <div id="loadingState" class="section show">
                <div class="spinner-row">
                    <div class="spinner"></div>
                    Verificando estado...
                </div>
            </div>

            <!-- Contenido -->
            <div id="statusSection" class="section">

                <!-- Estado y Jail -->
                <div class="status-row">
                    <div class="status-label">Estado</div>
                    <div class="status-value"><span id="statusBadge" class="badge">—</span></div>
                </div>
                <div class="status-row">
                    <div class="status-label">Jail</div>
                    <div class="status-value" id="statusJail">—</div>
                </div>

                <!-- Alerta de estado -->
                <div id="statusAlert" class="alert"></div>

                <!-- Motivo -->
                <div id="reasonBox" class="reason-box">
                    <div class="reason-box-title">Motivo del bloqueo</div>
                    <div class="reason-box-text" id="reasonText">—</div>
                </div>

                <!-- Desbloquear -->
                <div id="unblockSection" class="unblock-section">
                    <p>Completá el desafío de seguridad y solicitá el desbloqueo.</p>
                    <div class="turnstile-wrapper">
                        <div id="turnstileContainer" data-sitekey="{{ $turnstileSiteKey }}"></div>
                    </div>
                    <button id="unblockBtn" class="btn btn-primary" onclick="unblockIp()" type="button">
                        Desbloquear MI IP
                    </button>
                </div>

                <!-- Volver a verificar -->
                <button id="retryBtn" class="btn btn-outline" onclick="checkStatus()" style="display:none;">
                    Volver a verificar
                </button>

                <!-- Soporte - IP bloqueada / cooldown -->
                <div id="supportSection" class="support-section">
                    <hr class="divider" style="margin-bottom:16px;">
                    <div class="support-title">¿Necesitás acceso inmediato? Contactá a soporte.</div>
                    <div class="support-buttons">
                        <a id="whatsappBtn" href="#" target="_blank" class="btn-wa">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                        <a id="emailBtn" href="#" class="btn-mail">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                            Correo
                        </a>
                    </div>
                </div>

                <!-- Soporte - IP desbloqueada / sin bloqueo -->
                <div id="supportSectionOk" class="support-section">
                    <hr class="divider" style="margin-bottom:16px;">
                    <div class="support-title">¿Seguís con problemas para acceder? Contactá a soporte.</div>
                    <div class="support-buttons">
                        <a id="whatsappBtnOk" href="#" target="_blank" class="btn-wa">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                        <a id="emailBtnOk" href="#" class="btn-mail">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                            Correo
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer>&copy; {{ date('Y') }} WebParaguay &mdash; Portal de autoservicio</footer>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad" async defer></script>
    <script>
        let turnstileSiteKey = '{{ $turnstileSiteKey }}';
        let currentIp = null, currentJail = null, currentReason = null;
        let isBlocked = false, eligibleForUnblock = false, unblockInProgress = false;
        let turnstileReady = false, pendingRender = false;

        function onTurnstileLoad() {
            turnstileReady = true;
            if (pendingRender) renderTurnstile();
        }

        function renderTurnstile() {
            if (!turnstileSiteKey) return;
            if (!turnstileReady) { pendingRender = true; return; }
            if (document.querySelector('#turnstileContainer iframe')) return;
            turnstile.render('#turnstileContainer', { sitekey: turnstileSiteKey, theme: 'light' });
            pendingRender = false;
        }

        function buildContactLinks(ip, jail, reason) {
            const waMsg = `Hola, les contacto porque mi dirección IP *${ip}* está bloqueada.\nMotivo: *${reason}*\nJail: *${jail}*\n¿Podrían ayudarme a desbloquearla? Gracias.`;
            document.getElementById('whatsappBtn').href = `https://wa.me/595994282789?text=${encodeURIComponent(waMsg)}`;
            const subject = `Solicitud de desbloqueo de IP - ${ip}`;
            const body = `Hola,\n\nLes escribo porque mi dirección IP ${ip} se encuentra bloqueada.\n\nMotivo: ${reason}\nJail: ${jail}\n\nSolicito por favor el desbloqueo a la brevedad posible.\n\nGracias.`;
            document.getElementById('emailBtn').href = `mailto:soporte@webparaguay.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }

        function buildContactLinksOk(ip) {
            const waMsg = `Hola, estoy teniendo inconvenientes para acceder a mis servicios. Mi dirección IP es *${ip}* y según el portal no figura como bloqueada. ¿Podrían ayudarme a resolver el inconveniente? Gracias.`;
            document.getElementById('whatsappBtnOk').href = `https://wa.me/595994282789?text=${encodeURIComponent(waMsg)}`;
            const subject = `Problemas de acceso - IP ${ip} no bloqueada`;
            const body = `Hola,\n\nEstoy teniendo inconvenientes para acceder a mis servicios.\n\nMi dirección IP es ${ip} y según el portal de autoservicio no figura como bloqueada.\n\nSolicito asistencia para resolver el problema.\n\nGracias.`;
            document.getElementById('emailBtnOk').href = `mailto:soporte@webparaguay.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }

        document.addEventListener('DOMContentLoaded', checkStatus);

        async function checkStatus() {
            show('loadingState'); hide('statusSection');
            try {
                const res = await fetch('/api/ip/status', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                displayStatus(await res.json());
            } catch {
                hide('loadingState'); show('statusSection');
                showAlert('error', 'No pudimos verificar tu estado. Por favor, recargá la página.');
            }
        }

        function displayStatus(data) {
            currentIp     = data.ip;
            currentJail   = data.jail;
            currentReason = data.friendly_reason;
            isBlocked     = data.blocked;
            eligibleForUnblock = data.eligible_for_self_unblock;
            if (data.turnstile_site_key) turnstileSiteKey = data.turnstile_site_key;

            const ipEl = document.getElementById('displayIp');
            ipEl.textContent = currentIp;
            ipEl.classList.remove('loading-ip');

            document.getElementById('statusJail').textContent = currentJail || '—';

            if (isBlocked) {
                setBadge('blocked', '🔒 Bloqueada');
                if (currentReason) {
                    document.getElementById('reasonText').textContent = currentReason;
                    document.getElementById('reasonBox').classList.add('show');
                }
                if (eligibleForUnblock) {
                    show('unblockSection');
                    renderTurnstile();
                } else {
                    showAlert('warning', 'Tu IP está bloqueada por un motivo que requiere atención del equipo de soporte.');
                    showSupport();
                }
            } else {
                setBadge('clear', '✅ Desbloqueada');
                showAlert('success', 'Tu IP está desbloqueada. Podés acceder normalmente a tus servicios.');
                buildContactLinksOk(currentIp);
                document.getElementById('supportSectionOk').classList.add('show');
            }

            document.getElementById('retryBtn').style.display = 'flex';
            hide('loadingState'); show('statusSection');
        }

        async function unblockIp() {
            if (unblockInProgress) return;
            unblockInProgress = true;
            const btn = document.getElementById('unblockBtn');
            btn.textContent = 'Desbloqueando...';
            btn.disabled = true;
            try {
                const token = turnstile.getResponse();
                if (!token) { showAlert('warning', 'Por favor completá el desafío de seguridad de Cloudflare.'); return; }

                const res = await fetch('/api/ip/unblock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ turnstile_token: token }),
                });
                const data = await res.json();

                if (data.success) {
                    showAlert('success', data.message);
                    hide('unblockSection');
                    buildContactLinksOk(currentIp);
                    document.getElementById('supportSectionOk').classList.add('show');
                    setTimeout(checkStatus, 3000);
                } else {
                    showAlert('error', data.message);
                    if (res.status === 429 || data.message.includes('minuto')) showSupport();
                    if (typeof turnstile !== 'undefined') turnstile.reset();
                }
            } catch {
                showAlert('error', 'Ocurrió un error. Por favor, intentá de nuevo.');
            } finally {
                btn.textContent = 'Desbloquear MI IP';
                btn.disabled = false;
                unblockInProgress = false;
            }
        }

        function showSupport() {
            buildContactLinks(currentIp, currentJail, currentReason);
            document.getElementById('supportSection').classList.add('show');
        }

        function showAlert(type, msg) {
            const el = document.getElementById('statusAlert');
            el.className = `alert alert-${type} show`;
            el.textContent = msg;
        }

        function setBadge(type, text) {
            const b = document.getElementById('statusBadge');
            b.className = `badge badge-${type}`;
            b.textContent = text;
        }

        function show(id) { document.getElementById(id).classList.add('show'); }
        function hide(id) { document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>
