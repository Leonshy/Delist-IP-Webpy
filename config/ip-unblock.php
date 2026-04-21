<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | If your Laravel application is behind a reverse proxy or load balancer,
    | you can specify trusted proxy IPs here (comma-separated).
    | The X-Forwarded-For header will only be used if the request comes from
    | a trusted proxy.
    |
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    /*
    |--------------------------------------------------------------------------
    | Allowed Jails for Self-Unblock
    |--------------------------------------------------------------------------
    |
    | List of Fail2Ban jails that users can self-unblock via this portal.
    | Comma-separated values.
    |
    | Critical jails like 'ssh' and 'recidive' should NOT be included here.
    |
    */
    'allowed_jails' => env('ALLOWED_JAILS', 'plesk-panel,dovecot,postfix'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of unblock attempts allowed per IP in the decay period.
    |
    */
    'rate_limit_attempts' => env('RATE_LIMIT_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Decay Period
    |--------------------------------------------------------------------------
    |
    | The time window (in minutes) during which rate_limit_attempts is enforced.
    |
    */
    'rate_limit_decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Unblock Cooldown
    |--------------------------------------------------------------------------
    |
    | Minimum time (in minutes) between consecutive unblock operations for the
    | same IP. Prevents abuse of the unblock functionality.
    |
    */
    'cooldown_minutes' => env('UNBLOCK_COOLDOWN_MINUTES', 60),
];