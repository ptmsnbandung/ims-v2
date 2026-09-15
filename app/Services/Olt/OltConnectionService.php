<?php

namespace App\Services\Olt;

use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OltConnectionService
{
    /**
     * Test direct connectivity to OLT via Socket/Telnet/SSH
     */
    public function testConnection(array $config): array
    {
        $ip = trim($config['ip_address'] ?? '');
        $port = (int)($config['port'] ?? 23);
        $protocol = strtolower(trim($config['protocol'] ?? 'telnet'));
        $timeout = 3; // 3 seconds timeout

        if (empty($ip)) {
            return [
                'success' => false,
                'latency' => 0,
                'message' => 'IP Address OLT tidak boleh kosong.',
            ];
        }

        $startTime = microtime(true);

        try {
            // 1. Socket TCP Handshake (Level 1: Network & Port Connectivity)
            $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

            if (!$socket) {
                return [
                    'success' => false,
                    'latency' => 0,
                    'message' => "Gagal terhubung ke {$ip}:{$port}. " . ($errstr ? "({$errstr})" : "Host tidak merespon / timeout."),
                ];
            }

            stream_set_timeout($socket, $timeout);
            $latency = (int)round((microtime(true) - $startTime) * 1000);

            // 2. Read Initial Banner / Prompt (Level 2: Protocol Handshake)
            $banner = '';
            if ($protocol === 'telnet') {
                // Read up to 512 bytes of initial telnet banner
                $banner = @fread($socket, 512);
            }

            fclose($socket);

            return [
                'success' => true,
                'latency' => max(1, $latency),
                'protocol' => strtoupper($protocol),
                'port' => $port,
                'banner' => trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', $banner)),
                'message' => "Koneksi berhasil! Host {$ip}:{$port} aktif dan merespon dalam {$latency} ms.",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'latency' => 0,
                'message' => 'Terjadi kesalahan koneksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Live Port ONU Slot States from Physical OLT
     * Returns array of 1..128 slots with status, SN, and optical power
     */
    public function getLivePortSlots(object $olt, string $portName): array
    {
        $ip = $olt->ip_address ?? null;
        $port = $olt->port ?? 23;
        $username = $olt->username ?? null;
        $password = $this->decryptPassword($olt->password ?? null);
        $enablePassword = $this->decryptPassword($olt->enable_password ?? null);
        $brand = strtolower($olt->brand ?? 'zte');

        if (!$ip) {
            return [
                'success' => false,
                'message' => 'IP Address OLT belum dikonfigurasi.',
                'slots' => [],
            ];
        }

        try {
            // Normalisasikan format port untuk vendor
            // Contoh input: gpon-onu_1/1/1 atau 1/1/1
            $rawPort = preg_replace('/^gpon-onu_|^gpon-olt_/', '', $portName);

            // Execute CLI command via Telnet Session
            $cliOutput = $this->executeTelnetCommand($ip, $port, $username, $password, $enablePassword, [
                $this->getVendorShowStateCommand($brand, $rawPort)
            ]);

            // Parse output according to vendor
            $parsedOnus = $this->parseVendorOnuState($brand, $cliOutput, $rawPort);

            return [
                'success' => true,
                'message' => 'Berhasil sinkronisasi status live dari OLT.',
                'raw_output' => $cliOutput,
                'slots' => $parsedOnus,
            ];
        } catch (Exception $e) {
            Log::warning("OLT Live Sync Error for {$olt->name_olt}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal membaca data dari OLT: ' . $e->getMessage(),
                'slots' => [],
            ];
        }
    }

    /**
     * Scan Unconfigured (Unregistered / New) ONUs from OLT
     */
    public function scanUnconfiguredOnu(object $olt): array
    {
        $ip = $olt->ip_address ?? null;
        $port = $olt->port ?? 23;
        $username = $olt->username ?? null;
        $password = $this->decryptPassword($olt->password ?? null);
        $enablePassword = $this->decryptPassword($olt->enable_password ?? null);
        $brand = strtolower($olt->brand ?? 'zte');

        if (!$ip) {
            return [
                'success' => false,
                'message' => 'IP Address OLT belum dikonfigurasi.',
                'data' => [],
            ];
        }

        try {
            $command = str_contains($brand, 'huawei') ? 'display ont autofind all' : 'show gpon onu uncfg';

            $cliOutput = $this->executeTelnetCommand($ip, $port, $username, $password, $enablePassword, [$command]);

            $uncfgList = $this->parseUnconfiguredOnu($brand, $cliOutput);

            return [
                'success' => true,
                'message' => 'Scan ONU baru selesai.',
                'total_found' => count($uncfgList),
                'data' => $uncfgList,
                'raw' => $cliOutput,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal scan ONU: ' . $e->getMessage(),
                'total_found' => 0,
                'data' => [],
            ];
        }
    }

    /**
     * Execute Telnet Command Session and return CLI Output buffer
     */
    private function executeTelnetCommand(string $ip, int $port, ?string $username, ?string $password, ?string $enablePassword, array $commands): string
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
        if (!$socket) {
            throw new Exception("Tidak dapat membuka sesi Telnet ke {$ip}:{$port} ({$errstr})");
        }

        stream_set_timeout($socket, 3);
        $buffer = '';

        // Read initial prompt
        $buffer .= $this->readUntilPrompt($socket, ['Username:', 'login:', 'Password:', '>', '#', '%']);

        // Send Username if prompted
        if (str_contains(strtolower($buffer), 'user') || str_contains(strtolower($buffer), 'login')) {
            fwrite($socket, ($username ?? '') . "\r\n");
            $buffer .= $this->readUntilPrompt($socket, ['Password:', '>', '#']);
        }

        // Send Password if prompted
        if (str_contains(strtolower($buffer), 'pass')) {
            fwrite($socket, ($password ?? '') . "\r\n");
            $buffer .= $this->readUntilPrompt($socket, ['>', '#', '%', 'failed', 'invalid']);
        }

        // Check if login failed
        if (str_contains(strtolower($buffer), 'failed') || str_contains(strtolower($buffer), 'invalid')) {
            fclose($socket);
            throw new Exception('Autentikasi Login OLT Gagal (Username / Password salah).');
        }

        // Enter enable / privileged mode if needed and prompt is '>'
        if (str_ends_with(trim($buffer), '>') && !empty($enablePassword)) {
            fwrite($socket, "enable\r\n");
            $promptRes = $this->readUntilPrompt($socket, ['Password:', '#']);
            if (str_contains(strtolower($promptRes), 'pass')) {
                fwrite($socket, $enablePassword . "\r\n");
                $buffer .= $this->readUntilPrompt($socket, ['#']);
            }
        }

        // Disable CLI Paging (terminal length 0 / screen-length 0)
        fwrite($socket, "terminal length 0\r\n");
        usleep(100000);
        fwrite($socket, "screen-length 0 temporary\r\n");
        usleep(100000);

        // Execute commands
        $output = '';
        foreach ($commands as $cmd) {
            fwrite($socket, $cmd . "\r\n");
            usleep(200000); // 200ms pause for OLT response
            $cmdOutput = $this->readUntilPrompt($socket, ['#', '>', '%'], 5);
            $output .= $cmdOutput;
        }

        // Exit session
        fwrite($socket, "exit\r\n");
        fclose($socket);

        return $output;
    }

    /**
     * Read stream buffer until any of given prompt delimiters appears
     */
    private function readUntilPrompt($socket, array $prompts, int $maxWaitSeconds = 3): string
    {
        $buffer = '';
        $start = time();

        while (!feof($socket) && (time() - $start) < $maxWaitSeconds) {
            $char = fgetc($socket);
            if ($char === false) {
                usleep(50000);
                continue;
            }
            $buffer .= $char;

            foreach ($prompts as $prompt) {
                if (str_ends_with(trim($buffer), $prompt)) {
                    return $buffer;
                }
            }
        }

        return $buffer;
    }

    /**
     * Generate Vendor Specific Command for Port State
     */
    private function getVendorShowStateCommand(string $brand, string $port): string
    {
        if (str_contains($brand, 'huawei')) {
            // Huawei format: display ont info 0 1 1 all
            $parts = explode('/', $port);
            $frame = $parts[0] ?? '0';
            $slot = $parts[1] ?? '1';
            $pon = $parts[2] ?? '1';
            return "display ont info {$frame} {$slot} {$pon} all";
        }

        // ZTE default format: show gpon onu state gpon-olt_1/1/1
        return "show gpon onu state gpon-olt_{$port}";
    }

    /**
     * Parse Vendor CLI Output to standard ONU slot array [1..128]
     */
    private function parseVendorOnuState(string $brand, string $output, string $port): array
    {
        $slots = [];

        // Parse ZTE: e.g. "gpon-onu_1/1/1:1    ready    working" / "los" / "lost"
        if (str_contains($brand, 'zte') || !str_contains($brand, 'huawei')) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/gpon-onu_' . preg_quote($port, '/') . ':(\d+)\s+([a-zA-Z0-9_\-]+)\s+([a-zA-Z0-9_\-]+)/i', $line, $matches)) {
                    $onuId = (int)$matches[1];
                    $adminState = strtolower($matches[2]);
                    $omciState = strtolower($matches[3]);

                    $status = 'unknown';
                    if (in_array($omciState, ['working', 'ready', 'online'])) {
                        $status = 'online';
                    } elseif (in_array($omciState, ['los', 'lost', 'dyinggasp', 'poweroff', 'offline'])) {
                        $status = 'offline';
                    }

                    $slots[$onuId] = [
                        'slot_num' => $onuId,
                        'key' => "gpon-onu_{$port}:{$onuId}",
                        'status' => $status,
                        'admin_state' => $adminState,
                        'omci_state' => $omciState,
                        'raw_line' => $line,
                    ];
                }
            }
        }

        // Parse Huawei: e.g. "1   48575443...  online   match   initial"
        if (str_contains($brand, 'huawei')) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^(\d+)\s+([A-Z0-9]+)\s+([a-z0-9_\-]+)/i', $line, $matches)) {
                    $onuId = (int)$matches[1];
                    $sn = $matches[2];
                    $state = strtolower($matches[3]);

                    $slots[$onuId] = [
                        'slot_num' => $onuId,
                        'key' => "gpon-onu_{$port}:{$onuId}",
                        'sn' => $sn,
                        'status' => $state === 'online' ? 'online' : 'offline',
                        'omci_state' => $state,
                        'raw_line' => $line,
                    ];
                }
            }
        }

        return $slots;
    }

    /**
     * Parse Unconfigured ONUs from OLT output
     */
    private function parseUnconfiguredOnu(string $brand, string $output): array
    {
        $list = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            // ZTE format: gpon-onu_1/1/1:1 or OnuIndex: gpon-olt_1/1/1 SN: ZTEGC1234567
            if (preg_match('/(gpon-olt_\d+\/\d+\/\d+|\d+\/\d+\/\d+).*?(ZTEG[A-Z0-9]+|HWTC[A-Z0-9]+|[A-Z0-9]{12,16})/i', $line, $m)) {
                $list[] = [
                    'port' => $m[1],
                    'sn' => $m[2],
                    'raw' => $line,
                    'discovered_at' => now()->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $list;
    }

    /**
     * Safely decrypt password if encrypted, or return raw string
     */
    private function decryptPassword(?string $value): ?string
    {
        if (empty($value)) return null;

        try {
            return Crypt::decryptString($value);
        } catch (Exception $e) {
            return $value; // Fallback jika belum terenkripsi
        }
    }
}
