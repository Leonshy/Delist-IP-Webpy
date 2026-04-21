<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PleskService
{
    /**
     * Get all banned IPs with their jails.
     *
     * @return array [ip => jail]
     */
    public function getBannedIps(): array
    {
        $command = 'plesk bin ip_ban --banned';
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error('Failed to execute plesk bin ip_ban --banned', ['output' => $output, 'returnVar' => $returnVar]);
            return [];
        }

        $banned = [];
        foreach ($output as $line) {
            $parts = explode(' ', trim($line));
            if (count($parts) >= 2) {
                $ip = $parts[0];
                $jail = $parts[1];
                $banned[$ip] = $jail;
            }
        }

        return $banned;
    }

    /**
     * Check if an IP is banned and return the jail.
     *
     * @param string $ip
     * @return string|null jail name or null if not banned
     */
    public function isIpBanned(string $ip): ?string
    {
        $banned = $this->getBannedIps();
        return $banned[$ip] ?? null;
    }

    /**
     * Unban an IP from a specific jail.
     *
     * @param string $ip
     * @param string $jail
     * @return bool success
     */
    public function unbanIp(string $ip, string $jail): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Log::error('Invalid IP format for unban', ['ip' => $ip]);
            return false;
        }

        $arg = escapeshellarg("{$ip},{$jail}");
        $command = "plesk bin ip_ban --unban {$arg}";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error('Failed to unban IP', ['ip' => $ip, 'jail' => $jail, 'output' => $output, 'returnVar' => $returnVar]);
            return false;
        }

        return true;
    }
}