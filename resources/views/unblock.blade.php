<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Desbloquea tu IP - Portal de Autoservicio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }

        .status-section.active {
            display: block;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-label {
            font-weight: 600;
            color: #333;
            width: 120px;
            flex-shrink: 0;
        }

        .status-value {
            color: #666;
            word-break: break-all;
            flex: 1;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-blocked {
            background: #fee;
            color: #c33;
        }

        .badge-clear {
            background: #efe;
            color: #3c3;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .message.active {
            display: block;
        }

        .message-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #1565c0;
        }

        .message-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .message-warning {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #e65100;
        }

        .message-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .turnstile-wrapper {
            margin-bottom: 20px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        button:hover:not(:disabled) {
            opacity: 0.9;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .reason-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff8e1;
            border-radius: 8px;
            border-left: 4px solid #fbc02d;
            display: none;
        }

        .reason-section.active {
            display: block;
        }

        .reason-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .reason-text {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .contact-support {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            color: #666;
            display: none;
        }

        .contact-support.active {
            display: block;
        }

        .contact-support strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .loading-state {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: 600;
            min-height: 100px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .unblock-section {
            display: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        .unblock-section.active {
            display: block;
        }

        .unblock-section h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px;
            }

            .header h1 {
                font-size: 24px;
            }

            button {
                padding: 14px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔓 Desbloquea tu IP</h1>
            <p>Portal de autoservicio para clientes del hosting</p>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="section active">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <span>Analizando tu dirección IP...</span>
            </div>
        </div>

        <!-- Status Section -->
        <div id="statusSection" class="section">
            <div id="statusMessage" class="message"></div>

            <div class="status-section active">
                <div class="status-item">
                    <div class="status-label">Tu IP:</div>
                    <div class="status-value" id="displayIp">-</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Estado:</div>
                    <div class="status-value">
                        <span id="statusBadge" class="badge">Verificando...</span>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Motivo:</div>
                    <div class="status-value" id="statusReason">-</div>
                </div>
            </div>

            <!-- Reason Section for Blocked IPs -->
            <div id="reasonSection" class="reason-section">
                <div class="reason-title">Razón del bloqueo:</div>
                <div class="reason-text" id="reasonText">-</div>
            </div>

            <!-- Unblock Section for Eligible IPs -->
            <div id="unblockSection" class="unblock-section">
                <h3>Desbloquear IP</h3>
                <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                    Para desbloquear tu IP, completa el desafío de seguridad de Cloudflare y haz clic en desbloquear.
                </p>
                <div class="turnstile-wrapper">
                    <div id="turnstileContainer" data-sitekey="{{ $turnstileSiteKey }}"></div>
                </div>
                <button id="unblockBtn" onclick="unblockIp()" type="submit">Desbloquear MI IP</button>
            </div>

            <!-- Contact Support -->
            <div id="contactSupport" class="contact-support">
                <strong>Necesitas ayuda adicional?</strong>
                <p>Contacta a nuestro equipo de soporte para más información.</p>
            </div>

            <button id="retryBtn" onclick="checkStatus()" style="margin-top: 20px; display: none;">
                Volver a verificar
            </button>
        </div>
    </div>

    <!-- Cloudflare Turnstile Script -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <script>
        let turnstileSiteKey = '{{ $turnstileSiteKey }}';
        let currentIp = null;
        let isBlocked = false;
        let eligibleForUnblock = false;
        let unblockInProgress = false;

        document.addEventListener('DOMContentLoaded', async () => {
            checkStatus();
        });

        async function checkStatus() {
            try {
                document.getElementById('loadingState').classList.add('active');
                document.getElementById('statusSection').classList.remove('active');

                const response = await fetch('/api/ip/status', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }

                const data = await response.json();
                displayStatus(data);

            } catch (error) {
                console.error('Error checking status:', error);
                showMessage('error', 'No pudimos verificar tu estado. Por favor, recarga la página.');
                document.getElementById('loadingState').classList.remove('active');
                document.getElementById('statusSection').classList.add('active');
            }
        }

        function displayStatus(data) {
            currentIp = data.ip;
            isBlocked = data.blocked;
            eligibleForUnblock = data.eligible_for_self_unblock;
            turnstileSiteKey = data.turnstile_site_key;

            // Update display
            document.getElementById('displayIp').textContent = currentIp;

            if (isBlocked) {
                document.getElementById('statusBadge').className = 'badge badge-blocked';
                document.getElementById('statusBadge').textContent = '🔒 Bloqueada';
                document.getElementById('statusReason').textContent = data.jail || 'Desconocido';

                if (data.friendly_reason) {
                    document.getElementById('reasonSection').classList.add('active');
                    document.getElementById('reasonText').textContent = data.friendly_reason;
                }

                if (eligibleForUnblock) {
                    document.getElementById('unblockSection').classList.add('active');
                    
                    // Initialize Turnstile
                    if (turnstileSiteKey) {
                        setTimeout(() => {
                            if (typeof turnstile !== 'undefined' && !document.getElementById('turnstile-container-exists')) {
                                turnstile.render('#turnstileContainer', {
                                    sitekey: turnstileSiteKey,
                                    theme: 'light',
                                });
                            }
                        }, 100);
                    }
                } else {
                    document.getElementById('contactSupport').classList.add('active');
                    showMessage('warning', 'Tu IP está bloqueada por motivos de seguridad. Por favor, contacta a soporte para más ayuda.');
                }

            } else {
                document.getElementById('statusBadge').className = 'badge badge-clear';
                document.getElementById('statusBadge').textContent = '✅ Desbloqueada';
                document.getElementById('statusReason').textContent = 'Tu IP no está bloqueada';
                showMessage('success', 'Tu dirección IP está desbloqueada y puedes acceder normalmente a tus servicios.');
            }

            document.getElementById('loadingState').classList.remove('active');
            document.getElementById('statusSection').classList.add('active');
        }

        async function unblockIp() {
            if (unblockInProgress) return;
            if (!turnstileSiteKey) {
                showMessage('error', 'Error de configuración. Por favor, recarga la página.');
                return;
            }

            unblockInProgress = true;
            const unblockBtn = document.getElementById('unblockBtn');
            const originalText = unblockBtn.textContent;
            unblockBtn.textContent = 'Desbloqueando...';
            unblockBtn.disabled = true;

            try {
                const token = turnstile.getResponse();
                if (!token) {
                    showMessage('warning', 'Por favor, completa el desafío de seguridad de Cloudflare.');
                    unblockBtn.textContent = originalText;
                    unblockBtn.disabled = false;
                    unblockInProgress = false;
                    return;
                }

                const response = await fetch('/api/ip/unblock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        turnstile_token: token,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', data.message);
                    document.getElementById('unblockSection').classList.remove('active');
                    setTimeout(() => {
                        checkStatus();
                    }, 2000);
                } else {
                    showMessage('error', data.message);
                    
                    // Reset turnstile
                    if (typeof turnstile !== 'undefined') {
                        turnstile.reset();
                    }
                }

            } catch (error) {
                console.error('Error during unblock:', error);
                showMessage('error', 'Ocurrió un error. Por favor, intenta de nuevo.');
            } finally {
                unblockBtn.textContent = originalText;
                unblockBtn.disabled = false;
                unblockInProgress = false;
            }
        }

        function showMessage(type, message) {
            const messageEl = document.getElementById('statusMessage');
            messageEl.className = `message message-${type} active`;
            messageEl.textContent = message;
            messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
</body>
</html>