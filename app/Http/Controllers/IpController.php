<?php

namespace App\Http\Controllers;

use App\IpUnblockLog;
use App\Services\PleskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class IpController extends Controller
{
    private PleskService $pleskService;
    private const JAIL_FRIENDLY_NAMES = [
        // Plesk
        'plesk-panel'          => 'Demasiados intentos fallidos de acceso al panel de administración',
        'plesk-dovecot'        => 'Demasiados intentos fallidos de acceso al correo entrante',
        'plesk-postfix'        => 'Demasiados intentos fallidos de autenticación de correo saliente',
        'plesk-one-week-ban'   => 'Demasiados intentos fallidos de autenticación',
        'plesk-apache'         => 'Demasiados intentos fallidos de acceso al servidor web',
        'plesk-apache-badbot'  => 'Acceso bloqueado por comportamiento de bot malicioso',
        'plesk-modsecurity'    => 'Acceso bloqueado por el firewall de aplicaciones web (WAF)',
        'plesk-wordpress'      => 'Demasiados intentos fallidos de acceso a WordPress',
        'plesk-proftpd'        => 'Demasiados intentos fallidos de acceso FTP',
        'plesk-roundcube'      => 'Demasiados intentos fallidos de acceso al webmail',
        'plesk-horde'          => 'Demasiados intentos fallidos de acceso al webmail',
        // Apache
        'apache-auth'          => 'Demasiados intentos fallidos de autenticación en el servidor web',
        'apache-badbots'       => 'Acceso bloqueado por comportamiento de bot malicioso',
        'apache-modsecurity'   => 'Acceso bloqueado por el firewall de aplicaciones web (WAF)',
        'apache-noscript'      => 'Acceso bloqueado por intentos de explotar scripts inexistentes',
        'apache-overflows'     => 'Acceso bloqueado por solicitudes maliciosas al servidor web',
        'apache-shellshock'    => 'Acceso bloqueado por intento de explotación crítica del servidor',
        // Nginx
        'nginx-http-auth'      => 'Demasiados intentos fallidos de autenticación web',
        'nginx-limit-req'      => 'Acceso bloqueado por exceso de solicitudes al servidor',
        'nginx-botsearch'      => 'Acceso bloqueado por comportamiento de bot malicioso',
        'nginx-bad-request'    => 'Acceso bloqueado por solicitudes inválidas al servidor',
        'nginx-forbidden'      => 'Acceso bloqueado por múltiples intentos de acceso no autorizado',
        // SSH y acceso remoto
        'sshd'                 => 'Demasiados intentos fallidos de acceso SSH',
        'ssh'                  => 'Demasiados intentos fallidos de acceso SSH',
        // Correo
        'dovecot'              => 'Demasiados intentos fallidos de acceso al correo entrante',
        'postfix'              => 'Demasiados intentos fallidos de autenticación de correo saliente',
        'postfix-sasl'         => 'Demasiados intentos fallidos de autenticación de correo saliente',
        // FTP
        'proftpd'              => 'Demasiados intentos fallidos de acceso FTP',
        'pure-ftpd'            => 'Demasiados intentos fallidos de acceso FTP',
        'vsftpd'               => 'Demasiados intentos fallidos de acceso FTP',
        // Reincidencia
        'recidive'             => 'La IP fue bloqueada nuevamente por repetición de eventos sospechosos',
    ];

    public function __construct(PleskService $pleskService)
    {
        $this->pleskService = $pleskService;
    }

    /**
     * Get the status of the current visitor's IP.
     */
    public function status(Request $request)
    {
        $ip = $this->getClientIp($request);
        
        // Check if IP is banned
        $jail = $this->pleskService->isIpBanned($ip);
        $isBlocked = $jail !== null;
        
        // Determine if eligible for self-unblock
        $allowedJails = $this->getAllowedJails();
        $eligibleForSelfUnblock = $isBlocked && in_array($jail, $allowedJails);
        
        return response()->json([
            'ip' => $ip,
            'blocked' => $isBlocked,
            'jail' => $jail,
            'friendly_reason' => $this->getFriendlyReason($jail),
            'eligible_for_self_unblock' => $eligibleForSelfUnblock,
            'turnstile_site_key' => config('services.turnstile.site_key'),
        ]);
    }

    /**
     * Unblock the current visitor's IP if eligible.
     */
    public function unblock(Request $request)
    {
        $request->validate([
            'turnstile_token' => 'required|string',
        ]);

        $ip = $this->getClientIp($request);
        
        // Rate limiting - check if IP has too many unblock attempts
        $rateLimitKey = "unblock_attempts:{$ip}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, config('ip-unblock.rate_limit_attempts', 5))) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            IpUnblockLog::create([
                'ip' => $ip,
                'jail' => null,
                'was_blocked' => false,
                'turnstile_valid' => false,
                'unblocked' => false,
                'reason' => 'Rate limit exceeded',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Intenta de nuevo en {$retryAfter} segundos.",
            ], 429);
        }

        // Validate Turnstile token using server-side validation
        $turnstileValid = $this->validateTurnstile($request->input('turnstile_token'));
        if (!$turnstileValid) {
            RateLimiter::hit($rateLimitKey, config('ip-unblock.rate_limit_decay_minutes', 15) * 60);
            
            IpUnblockLog::create([
                'ip' => $ip,
                'jail' => null,
                'was_blocked' => false,
                'turnstile_valid' => false,
                'unblocked' => false,
                'reason' => 'Turnstile validation failed',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validación de seguridad fallida. Intenta de nuevo.',
            ], 403);
        }

        // Check if IP is currently banned
        $jail = $this->pleskService->isIpBanned($ip);
        if ($jail === null) {
            IpUnblockLog::create([
                'ip' => $ip,
                'jail' => null,
                'was_blocked' => false,
                'turnstile_valid' => true,
                'unblocked' => false,
                'reason' => 'IP not currently banned',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tu IP no está actualmente bloqueada.',
            ]);
        }

        // Check if the jail is allowed for self-unblock
        $allowedJails = $this->getAllowedJails();
        if (!in_array($jail, $allowedJails)) {
            IpUnblockLog::create([
                'ip' => $ip,
                'jail' => $jail,
                'was_blocked' => true,
                'turnstile_valid' => true,
                'unblocked' => false,
                'reason' => 'Jail not allowed for self-unblock',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tu IP está bloqueada por una razón que requiere contactar a soporte. Por favor, comunícate con nuestro equipo.',
                'blocked_by' => $this->getFriendlyReason($jail),
            ]);
        }

        // Check cooldown - if this IP was recently unblocked
        $lastUnblock = IpUnblockLog::where('ip', $ip)
            ->where('unblocked', true)
            ->latest()
            ->first();

        if ($lastUnblock && $lastUnblock->created_at->diffInMinutes(now()) < config('ip-unblock.cooldown_minutes', 10)) {
            $minutesLeft = (int) ceil(config('ip-unblock.cooldown_minutes', 10) - $lastUnblock->created_at->diffInRealMinutes(now()));
            
            IpUnblockLog::create([
                'ip' => $ip,
                'jail' => $jail,
                'was_blocked' => true,
                'turnstile_valid' => true,
                'unblocked' => false,
                'reason' => 'Cooldown period not elapsed',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => "Ya fue desbloqueada recientemente. Intenta de nuevo en {$minutesLeft} minutos.",
            ]);
        }

        // Attempt to unblock the IP
        $unblockSuccess = $this->pleskService->unbanIp($ip, $jail);
        
        IpUnblockLog::create([
            'ip' => $ip,
            'jail' => $jail,
            'was_blocked' => true,
            'turnstile_valid' => true,
            'unblocked' => $unblockSuccess,
            'reason' => $unblockSuccess ? 'Successfully unblocked' : 'Unblock failed',
        ]);

        if ($unblockSuccess) {
            RateLimiter::clear($rateLimitKey);
            
            return response()->json([
                'success' => true,
                'message' => 'Tu IP ha sido desbloqueada exitosamente. Intenta acceder nuevamente a tus servicios.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, config('ip-unblock.rate_limit_decay_minutes', 15) * 60);
        
        return response()->json([
            'success' => false,
            'message' => 'No pudimos desbloquear tu IP. Por favor, intenta más tarde o contacta a soporte.',
        ], 500);
    }

    /**
     * Get the client's IP address, considering trusted proxies.
     */
    private function getClientIp(Request $request): string
    {
        $trustedProxies = collect(explode(',', config('ip-unblock.trusted_proxies', '')))->map('trim')->filter()->all();

        $remoteAddr = $request->server('REMOTE_ADDR', $request->ip());

        if (!empty($trustedProxies) && $this->isInTrustedProxies($remoteAddr, $trustedProxies)) {
            $cfIp = $request->header('CF-Connecting-IP');
            if ($cfIp && filter_var(trim($cfIp), FILTER_VALIDATE_IP)) {
                return trim($cfIp);
            }

            $xForwardedFor = $request->header('X-Forwarded-For');
            if ($xForwardedFor) {
                $ips = array_reverse(array_map('trim', explode(',', $xForwardedFor)));
                foreach ($ips as $candidateIp) {
                    if (filter_var($candidateIp, FILTER_VALIDATE_IP) && !$this->isInTrustedProxies($candidateIp, $trustedProxies)) {
                        return $candidateIp;
                    }
                }
            }
        }

        $ip = $request->ip();

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Log::warning('Could not determine a valid client IP', ['raw' => $ip]);
            abort(400, 'No se pudo determinar una dirección IP válida.');
        }

        return $ip;
    }

    private function isInTrustedProxies(string $ip, array $proxies): bool
    {
        foreach ($proxies as $proxy) {
            if (str_contains($proxy, '/')) {
                [$subnet, $bits] = explode('/', $proxy);
                $mask = -1 << (32 - (int)$bits);
                if ((ip2long($ip) & $mask) === (ip2long($subnet) & $mask)) {
                    return true;
                }
            } elseif ($ip === $proxy) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate Turnstile token via server-side Siteverify.
     */
    private function validateTurnstile(string $token): bool
    {
        $secretKey = config('services.turnstile.secret_key');
        if (!$secretKey) {
            Log::error('Turnstile secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => $secretKey,
                    'response' => $token,
                ]
            );

            if (!$response->successful()) {
                Log::error('Turnstile API request failed', ['status' => $response->status()]);
                return false;
            }

            $data = $response->json();
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Turnstile validation exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get the list of jails allowed for self-unblock.
     */
    private function getAllowedJails(): array
    {
        $allowed = config('ip-unblock.allowed_jails', 'plesk-panel,dovecot,postfix');
        return array_map('trim', explode(',', $allowed));
    }

    /**
     * Get a friendly message for a given jail.
     */
    private function getFriendlyReason(?string $jail): ?string
    {
        if ($jail === null) {
            return null;
        }

        return self::JAIL_FRIENDLY_NAMES[$jail] ?? "Tu IP está bloqueada por motivos de seguridad ({$jail})";
    }
}
