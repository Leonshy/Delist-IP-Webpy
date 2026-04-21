<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PleskService
{
    private string $sshUser;
    private string $sshHost;
    private int $sshPort;
    private string $sshKey;

    public function __construct()
    {
        $this->sshUser = config('ip-unblock.plesk_ssh_user', 'root');
        $this->sshHost = config('ip-unblock.plesk_ssh_host');
        $this->sshPort = (int) config('ip-unblock.plesk_ssh_port', 22);
        $this->sshKey  = config('ip-unblock.plesk_ssh_key');
    }

    private function ssh(string $remoteArg): array
    {
        $host    = escapeshellarg($this->sshHost);
        $user    = escapeshellarg($this->sshUser);
        $keyFile = escapeshellarg($this->sshKey);
        $port    = (int) $this->sshPort;
        $arg     = escapeshellarg($remoteArg);

        $command = "ssh -i {$keyFile} -o StrictHostKeyChecking=yes -o BatchMode=yes -o ConnectTimeout=10 -p {$port} {$user}@{$host} -- {$arg} 2>&1";

        $output    = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        return [$output, $returnVar];
    }

    public function getBannedIps(): array
    {
        [$output, $returnVar] = $this->ssh('--banned');

        if ($returnVar !== 0) {
            Log::error('PleskService: failed to get banned IPs', ['output' => $output]);
            return [];
        }

        $banned = [];
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2 && filter_var($parts[0], FILTER_VALIDATE_IP)) {
                $banned[$parts[0]] = $parts[1];
            }
        }

        return $banned;
    }

    public function isIpBanned(string $ip): ?string
    {
        $banned = $this->getBannedIps();
        return $banned[$ip] ?? null;
    }

    public function unbanIp(string $ip, string $jail): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Log::error('PleskService: invalid IP format', ['ip' => $ip]);
            return false;
        }

        [$output, $returnVar] = $this->ssh("--unban {$ip},{$jail}");

        if ($returnVar !== 0) {
            Log::error('PleskService: failed to unban IP', ['ip' => $ip, 'jail' => $jail, 'output' => $output]);
            return false;
        }

        return true;
    }
}
