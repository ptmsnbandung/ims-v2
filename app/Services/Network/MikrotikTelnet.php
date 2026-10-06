<?php

namespace App\Services\Network;

/**
 * MikroTik Telnet Client (Standalone, Zero External Dependencies)
 * Kompatibel dengan MikroTik RouterOS v6.x dan v7.x via Telnet protocol
 * 
 * Menggunakan raw PHP socket (fsockopen) untuk mengirim CLI command
 * ke MikroTik dan mem-parse output text-nya.
 */
class MikrotikTelnet
{
    private $socket = null;
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private int $timeout;
    private bool $connected = false;

    /** Prompt CLI MikroTik, contoh: [admin@MikroTik] > atau > */
    private const PROMPT_REGEX = '/(\[[^\]\r\n]+\]\s*[>#])|((^|\n)\s*>\s*$)/';

    public ?string $error = null;
    public ?string $lastBuffer = '';

    public function __construct(string $host, int $port = 23, string $user = 'admin', string $pass = '', int $timeout = 7)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->timeout = max($timeout, 7);
    }

    /**
     * Connect & Login ke MikroTik via Telnet
     */
    public function connect(): bool
    {
        $this->error = null;
        $this->lastBuffer = '';

        // 1. Open TCP connection
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            $this->error = "Tidak dapat terhubung ke {$this->host}:{$this->port} ({$errstr})";
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);
        stream_set_blocking($this->socket, true);

        // 2. Read initial banner / negotiation (Telnet IAC commands + MikroTik login prompt)
        $banner = $this->readUntil(['Login:', 'login:', 'Username:', 'username:'], max($this->timeout, 8));

        if ($banner === false) {
            $bufferSample = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', (string)$this->lastBuffer));
            $this->error = "Timeout menunggu login prompt dari {$this->host}:{$this->port}" . ($bufferSample ? " (buffer: {$bufferSample})" : "");
            $this->disconnect();
            return false;
        }

        // 3. Send username (+ct = tanpa warna ANSI & cegah probe terminal detection MikroTik yang membuat hang)
        $loginUser = str_contains($this->user, '+') ? $this->user : ($this->user . '+ct');
        $this->writeLine($loginUser);

        // 4. Wait for password prompt
        $pwPrompt = $this->readUntil(['Password:', 'password:'], 6);
        if ($pwPrompt === false) {
            $bufferSample = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', (string)$this->lastBuffer));
            $this->error = "Timeout menunggu password prompt dari {$this->host}:{$this->port}" . ($bufferSample ? " (buffer: {$bufferSample})" : "");
            $this->disconnect();
            return false;
        }

        // 5. Send password
        $this->writeLine($this->pass);

        // 6. Wait for CLI prompt (e.g. [admin@MikroTik] > ) atau pesan gagal login / license
        $loginResult = $this->readUntil(
            ['login failed', 'login failure', 'incorrect', 'invalid user', 'failure', 'software license', 'new password'],
            max($this->timeout, 8),
            self::PROMPT_REGEX
        );

        if ($loginResult === false) {
            $bufferSample = trim(preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', (string)$this->lastBuffer));
            $bufferSample = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', $bufferSample));
            $bufferSample = preg_replace('/\s+/', ' ', $bufferSample);
            $extra = $bufferSample !== '' ? " (respon router: " . substr($bufferSample, 0, 120) . ")" : " (tidak ada teks dari router)";
            $this->error = "Timeout setelah login ke {$this->host}:{$this->port}{$extra}";
            $this->disconnect();
            return false;
        }

        // Handle license prompt jika router meminta persetujuan software license
        if (stripos($loginResult, 'software license') !== false || stripos($loginResult, '[y/n]') !== false) {
            $this->writeLine('n');
            usleep(200000);
            $loginResult = $this->readUntil([], 4, self::PROMPT_REGEX);
        }

        // Check login failure
        $cleanResult = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', (string)$loginResult);
        if (stripos($cleanResult, 'login failed') !== false || stripos($cleanResult, 'incorrect') !== false || stripos($cleanResult, 'invalid user') !== false) {
            $this->error = "Autentikasi Telnet gagal ke {$this->host}:{$this->port} (username/password salah)";
            $this->disconnect();
            return false;
        }

        if (stripos($cleanResult, 'new password') !== false) {
            $this->error = "Router {$this->host}:{$this->port} meminta penggantian password baru (login pertama). Harap setel password permanen via Winbox.";
            $this->disconnect();
            return false;
        }

        if (!preg_match(self::PROMPT_REGEX, $cleanResult)) {
            $this->error = "Autentikasi Telnet gagal atau prompt tidak dikenali ke {$this->host}:{$this->port}";
            $this->disconnect();
            return false;
        }

        $this->connected = true;
        return true;
    }

    /**
     * Disconnect from MikroTik
     */
    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            // Try to send quit command
            @fwrite($this->socket, "/quit\r\n");
            usleep(100000); // 100ms
            @fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    /**
     * Execute CLI command and return output string
     */
    public function exec(string $command): string
    {
        if (!$this->connected || !is_resource($this->socket)) {
            $this->error = "Tidak terhubung ke router";
            return '';
        }

        // Send command
        $this->writeLine($command);

        // Read output until we see the CLI prompt again
        $output = $this->readUntil([], $this->timeout + 5, self::PROMPT_REGEX);
        if ($output === false) {
            return '';
        }

        // Clean output: remove ANSI escape codes, remove echoed command and prompt lines
        $clean = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $output);
        $lines = explode("\n", $clean);
        $cleaned = [];
        $skipFirst = true;
        foreach ($lines as $line) {
            $trimmed = trim($line, "\r\n\0 ");
            // Skip the echoed command line
            if ($skipFirst && str_contains($trimmed, trim($command))) {
                $skipFirst = false;
                continue;
            }
            // Skip empty lines from telnet negotiation and prompt lines
            if (preg_match('/^\[.*@.*\]\s*[>#]\s*$/', $trimmed) || $trimmed === '>') {
                continue;
            }
            $cleaned[] = $trimmed;
        }

        return implode("\n", $cleaned);
    }

    /**
     * Get system identity
     */
    public function getIdentity(): ?string
    {
        $output = $this->exec('/system identity print');
        // Output format: "name: MSN-KADASA" or just the name
        if (preg_match('/name:\s*(.+)/i', $output, $m)) {
            return trim($m[1]);
        }
        // Try simpler parsing
        $lines = array_filter(explode("\n", trim($output)));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '[') && !str_contains($line, 'identity')) {
                return $line;
            }
        }
        return null;
    }

    /**
     * Get system resource info
     */
    public function getResource(): array
    {
        $output = $this->exec('/system resource print');
        return $this->parseKeyValueOutput($output);
    }

    /**
     * Get all PPPoE secrets
     */
    public function getPppSecrets(): array
    {
        $output = $this->exec('/ppp secret print terse');
        return $this->parseTerseOutput($output);
    }

    /**
     * Get PPPoE secret by name
     */
    public function findPppSecret(string $name): ?array
    {
        $output = $this->exec("/ppp secret print terse where name=\"{$name}\"");
        $items = $this->parseTerseOutput($output);
        return $items[0] ?? null;
    }

    /**
     * Enable PPPoE secret (set disabled=no)
     */
    public function enablePppSecret(string $name): bool
    {
        $output = $this->exec("/ppp secret set [find name=\"{$name}\"] disabled=no");
        return !str_contains(strtolower($output), 'error') && !str_contains(strtolower($output), 'failure');
    }

    /**
     * Disable PPPoE secret (set disabled=yes)
     */
    public function disablePppSecret(string $name): bool
    {
        $output = $this->exec("/ppp secret set [find name=\"{$name}\"] disabled=yes");
        return !str_contains(strtolower($output), 'error') && !str_contains(strtolower($output), 'failure');
    }

    /**
     * Kick active PPPoE connection
     */
    public function kickPppActive(string $name): bool
    {
        $output = $this->exec("/ppp active remove [find name=\"{$name}\"]");
        return true; // Remove returns nothing on success
    }

    /**
     * Get active PPPoE connections
     */
    public function getPppActive(): array
    {
        $output = $this->exec('/ppp active print terse');
        return $this->parseTerseOutput($output);
    }

    // ========================================================================
    // PRIVATE HELPERS
    // ========================================================================

    /**
     * Write a line to the socket
     */
    private function writeLine(string $data): void
    {
        if (is_resource($this->socket)) {
            @fwrite($this->socket, $data . "\r\n");
            usleep(50000); // 50ms delay for router processing
        }
    }

    /**
     * Read from socket until one of the needle strings is found
     * Returns accumulated text or false on timeout
     */
    private function readUntil(array $needles, int $timeout, ?string $endRegex = null): string|false
    {
        $buffer = '';
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            $meta = stream_get_meta_data($this->socket);
            if ($meta['timed_out']) {
                break;
            }

            $byte = @fread($this->socket, 1);
            if ($byte === false || strlen($byte) === 0) {
                // Check if socket is still alive
                if (feof($this->socket)) {
                    break;
                }
                usleep(10000); // 10ms
                continue;
            }

            $ord = ord($byte);

            // Handle Telnet IAC (Interpret As Command) negotiation
            if ($ord === 255) { // IAC
                $cmd = @fread($this->socket, 1);
                if ($cmd === false) continue;
                $cmdOrd = ord($cmd);

                if ($cmdOrd >= 251 && $cmdOrd <= 254) {
                    // WILL(251)/WONT(252)/DO(253)/DONT(254) + option byte
                    $opt = @fread($this->socket, 1);
                    if ($opt === false) continue;

                    // Respond: refuse all options
                    if ($cmdOrd === 251 || $cmdOrd === 252) {
                        // WILL/WONT -> reply DONT
                        @fwrite($this->socket, chr(255) . chr(254) . $opt);
                    } elseif ($cmdOrd === 253 || $cmdOrd === 254) {
                        // DO/DONT -> reply WONT
                        @fwrite($this->socket, chr(255) . chr(252) . $opt);
                    }
                } elseif ($cmdOrd === 250) {
                    // SB (subnegotiation) - read until IAC SE (255 240)
                    while (true) {
                        $sb = @fread($this->socket, 1);
                        if ($sb === false || ord($sb) === 240) break;
                        if (ord($sb) === 255) {
                            $next = @fread($this->socket, 1);
                            if ($next !== false && ord($next) === 240) break;
                        }
                    }
                }
                continue;
            }

            // Skip null bytes and other control chars (except CR/LF)
            if ($ord < 32 && $ord !== 10 && $ord !== 13) {
                continue;
            }

            $buffer .= $byte;
            $this->lastBuffer = $buffer;

            $clean = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $buffer);

            // Check if any needle is found (case-insensitive)
            foreach ($needles as $needle) {
                if (stripos($clean, $needle) !== false) {
                    return $buffer;
                }
            }

            if ($endRegex !== null && preg_match($endRegex, $clean)) {
                return $buffer;
            }
        }

        // Timeout fallback check
        if (!empty($buffer)) {
            $this->lastBuffer = $buffer;
            $clean = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $buffer);
            foreach ($needles as $needle) {
                if (stripos($clean, $needle) !== false) {
                    return $buffer;
                }
            }
            if ($endRegex !== null && preg_match($endRegex, $clean)) {
                return $buffer;
            }
        }
        return false;
    }

    /**
     * Parse "key: value" style output from MikroTik print commands
     */
    private function parseKeyValueOutput(string $output): array
    {
        $result = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (preg_match('/^\s*([a-zA-Z0-9_-]+):\s*(.*)$/', $line, $m)) {
                $result[trim($m[1])] = trim($m[2]);
            }
        }
        return $result;
    }

    /**
     * Parse "terse" output format from MikroTik
     * Format: " 0 name=xxx service=pppoe password=yyy ..."
     */
    public function parseTerseOutput(string $output): array
    {
        $items = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // Skip header lines and prompt lines
            if (str_starts_with($line, '#') || str_starts_with($line, 'Flags:') || preg_match('/^\[.*@/', $line)) continue;

            $item = [];
            // Extract key=value pairs
            if (preg_match_all('/([a-zA-Z0-9._-]+)=("(?:[^"\\\\]|\\\\.)*"|[^\s]+)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $val = trim($match[2], '"');
                    $item[$key] = $val;
                }
            }

            // Extract .id if present
            if (preg_match('/^\s*(\d+)\s/', $line, $m)) {
                $item['.id'] = '*' . $m[1];
            }

            if (!empty($item)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function isConnected(): bool
    {
        return $this->connected && is_resource($this->socket);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
