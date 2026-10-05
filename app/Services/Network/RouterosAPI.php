<?php

namespace App\Services\Network;

/**
 * RouterOS API client class (Standalone, Zero External Dependencies)
 * Fully compatible with MikroTik RouterOS v6.x (CCR, RB, x86) and v7.x
 */
class RouterosAPI
{
    public bool $connected = false;
    public int $port = 18735;
    public bool $ssl = false;
    public int $timeout = 4;
    public int $attempts = 2;
    public int $delay = 1;
    public $socket = null;
    public ?string $error_str = null;
    public ?int $error_no = null;

    /**
     * Connect to RouterOS
     */
    public function connect(string $ip, string $login, string $password, ?int $port = null, ?bool $ssl = null): bool
    {
        if ($port !== null) {
            $this->port = $port;
        }
        if ($ssl !== null) {
            $this->ssl = $ssl;
        }

        $proto = $this->ssl ? 'ssl://' : '';
        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            $this->connected = false;
            $this->socket = @fsockopen($proto . $ip, $this->port, $this->error_no, $this->error_str, $this->timeout);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->connected = true;
                break;
            }
            if ($attempt < $this->attempts) {
                sleep($this->delay);
            }
        }

        if (!$this->connected) {
            $this->error_str = "Koneksi ke {$ip}:{$this->port} gagal (" . ($this->error_str ?: 'Connection timed out') . ")";
            return false;
        }

        // Login RouterOS (v6.43+ / v7 modern plain login)
        $this->write('/login');
        $this->write('=name=' . $login);
        $this->write('=password=' . $password, true);
        $res = $this->readSentence();

        if (!empty($res)) {
            if ($res[0] === '!done') {
                if (count($res) === 1) {
                    return true;
                }
                // Challenge / response (ROS <= 6.42 legacy)
                foreach ($res as $item) {
                    if (str_starts_with($item, '=ret=')) {
                        $challenge = substr($item, 5);
                        $md5Pass = md5(chr(0) . $password . pack('H*', $challenge));
                        $this->write('/login');
                        $this->write('=name=' . $login);
                        $this->write('=response=00' . $md5Pass, true);
                        $legacyRes = $this->readSentence();
                        if (!empty($legacyRes) && $legacyRes[0] === '!done') {
                            return true;
                        }
                    }
                }
            } elseif ($res[0] === '!trap') {
                $msg = 'Autentikasi gagal (username/password salah)';
                foreach ($res as $line) {
                    if (str_starts_with($line, '=message=')) {
                        $msg = substr($line, 9);
                        break;
                    }
                }
                $this->error_str = $msg;
                $this->disconnect();
                return false;
            }
        }

        $this->disconnect();
        $this->error_str = "Gagal login ke router {$ip}:{$this->port}";
        return false;
    }

    /**
     * Disconnect from RouterOS
     */
    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->connected = false;
    }

    /**
     * Write command word to socket
     */
    public function write(string $command, bool $endOfSentence = false): int|bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }

        $length = strlen($command);
        if ($length < 0x80) {
            $header = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $header = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $header = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $header = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            $header = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        $res = @fwrite($this->socket, $header . $command);
        if ($endOfSentence) {
            @fwrite($this->socket, chr(0));
        }
        return $res;
    }

    /**
     * Read a single sentence from socket
     */
    public function readSentence(): array
    {
        $sentence = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') {
                break;
            }
            $sentence[] = $word;
        }
        return $sentence;
    }

    /**
     * Read single word based on RouterOS length header
     */
    private function readWord(): string
    {
        if (!is_resource($this->socket)) {
            return '';
        }

        $byte = @fread($this->socket, 1);
        if ($byte === false || strlen($byte) === 0) {
            return '';
        }

        $length = ord($byte);
        if ($length & 0x80) {
            if (($length & 0xC0) === 0x80) {
                $length = (($length & 0x3F) << 8) + ord(@fread($this->socket, 1));
            } elseif (($length & 0xE0) === 0xC0) {
                $length = (($length & 0x1F) << 16) + (ord(@fread($this->socket, 1)) << 8) + ord(@fread($this->socket, 1));
            } elseif (($length & 0xF0) === 0xE0) {
                $length = (($length & 0x0F) << 24) + (ord(@fread($this->socket, 1)) << 16) + (ord(@fread($this->socket, 1)) << 8) + ord(@fread($this->socket, 1));
            } elseif (($length & 0xF8) === 0xF0) {
                $length = (ord(@fread($this->socket, 1)) << 24) + (ord(@fread($this->socket, 1)) << 16) + (ord(@fread($this->socket, 1)) << 8) + ord(@fread($this->socket, 1));
            }
        }

        if ($length === 0) {
            return '';
        }

        $word = '';
        $received = 0;
        while ($received < $length) {
            $chunk = @fread($this->socket, min(4096, $length - $received));
            if ($chunk === false || strlen($chunk) === 0) {
                break;
            }
            $word .= $chunk;
            $received += strlen($chunk);
        }

        return $word;
    }

    /**
     * Send command and parse full response array
     */
    public function comm(string $command, array $params = []): array
    {
        $this->write($command, empty($params));
        $count = count($params);
        $i = 0;
        foreach ($params as $key => $val) {
            $i++;
            $isLast = ($i === $count);
            if (is_numeric($key)) {
                $this->write($val, $isLast);
            } else {
                if (str_starts_with($key, '?') || str_starts_with($key, '=')) {
                    $this->write("{$key}{$val}", $isLast);
                } else {
                    $this->write("={$key}={$val}", $isLast);
                }
            }
        }

        $results = [];
        while (true) {
            $sentence = $this->readSentence();
            if (empty($sentence)) {
                break;
            }

            $type = $sentence[0] ?? '';
            if ($type === '!re') {
                $item = [];
                for ($s = 1; $s < count($sentence); $s++) {
                    $line = $sentence[$s];
                    if (str_starts_with($line, '=')) {
                        $eqPos = strpos($line, '=', 1);
                        if ($eqPos !== false) {
                            $k = substr($line, 1, $eqPos - 1);
                            $v = substr($line, $eqPos + 1);
                            $item[$k] = $v;
                        }
                    }
                }
                $results[] = $item;
            } elseif ($type === '!done') {
                break;
            } elseif ($type === '!trap' || $type === '!fatal') {
                $msg = 'Router error';
                foreach ($sentence as $line) {
                    if (str_starts_with($line, '=message=')) {
                        $msg = substr($line, 9);
                        break;
                    }
                }
                $results['!error'] = $msg;
                break;
            }
        }

        return $results;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
