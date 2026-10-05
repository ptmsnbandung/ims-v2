<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MikrotikService
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private int $timeout;
    private bool $ssl;

    public function __construct(?array $overrideConfig = null)
    {
        // 1. Jika ada override config langsung
        if ($overrideConfig) {
            $this->host = $overrideConfig['host'] ?? $overrideConfig['ip_address'] ?? config('mikrotik.host', '103.161.206.163');
            $this->port = (int)($overrideConfig['port'] ?? config('mikrotik.port', 18735));
            $this->user = $overrideConfig['user'] ?? $overrideConfig['username'] ?? config('mikrotik.user', 'msn');
            $this->pass = $overrideConfig['pass'] ?? $overrideConfig['password'] ?? config('mikrotik.pass', 'kayuagung2-9');
            $this->timeout = (int)($overrideConfig['timeout'] ?? config('mikrotik.timeout', 5));
            $this->ssl = (bool)($overrideConfig['ssl'] ?? config('mikrotik.ssl', false));
            return;
        }

        // 2. Cek koneksi router aktif dari tabel terpisah: `routers`
        $router = null;
        if (Schema::hasTable('routers')) {
            $router = DB::table('routers')
                ->where('is_active', 1)
                ->first();
        }

        if ($router && !empty($router->host)) {
            $this->host = $router->host;
            $this->port = (int)($router->port ?: config('mikrotik.port', 18735));
            $this->user = $router->username ?: config('mikrotik.user', 'msn');
            
            $pass = $router->password ?: config('mikrotik.pass', 'kayuagung2-9');
            try {
                $this->pass = \Illuminate\Support\Facades\Crypt::decryptString($pass);
            } catch (Exception $e) {
                $this->pass = $pass;
            }
            $this->timeout = (int)config('mikrotik.timeout', 5);
            $this->ssl = (bool)config('mikrotik.ssl', false);
        } else {
            // 3. Fallback ke config mikrotik.php / .env
            $this->host = config('mikrotik.host', '103.161.206.163');
            $this->port = (int)config('mikrotik.port', 18735);
            $this->user = config('mikrotik.user', 'msn');
            $this->pass = config('mikrotik.pass', 'kayuagung2-9');
            $this->timeout = (int)config('mikrotik.timeout', 5);
            $this->ssl = (bool)config('mikrotik.ssl', false);
        }
    }

    /**
     * Set credentials from router DB / dynamic config
     */
    public function setRouter(array $config): self
    {
        if (!empty($config['host']) || !empty($config['ip_address'])) {
            $this->host = $config['host'] ?? $config['ip_address'];
        }
        if (!empty($config['port'])) {
            $this->port = (int)$config['port'];
        }
        if (!empty($config['username']) || !empty($config['user'])) {
            $this->user = $config['username'] ?? $config['user'];
        }
        if (isset($config['password']) || isset($config['pass'])) {
            $this->pass = $config['password'] ?? $config['pass'];
        }
        return $this;
    }

    /**
     * Buka koneksi socket TCP ke MikroTik API
     */
    private function connect()
    {
        $protocol = $this->ssl ? 'tls://' : '';
        $address = $protocol . $this->host;

        $socket = @fsockopen($address, $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            throw new Exception("Gagal terhubung ke MikroTik ({$this->host}:{$this->port}): " . ($errstr ?: 'Connection timed out'));
        }

        stream_set_timeout($socket, $this->timeout);
        return $socket;
    }

    /**
     * Encode word format MikroTik RouterOS API
     */
    private function encodeWord(string $word): string
    {
        $len = strlen($word);
        if ($len < 0x80) {
            return chr($len) . $word;
        } elseif ($len < 0x4000) {
            return chr(0x80 | ($len >> 8)) . chr($len & 0xFF) . $word;
        } else {
            return chr(0xC0 | ($len >> 24)) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF) . $word;
        }
    }

    /**
     * Baca tepat $length byte dari socket (fread bisa mengembalikan data parsial)
     */
    private function readBytes($socket, int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new Exception('Timeout menunggu respons dari MikroTik.');
                }
                if (feof($socket)) {
                    throw new Exception('Koneksi ke MikroTik terputus.');
                }
                continue;
            }
            $data .= $chunk;
        }
        return $data;
    }

    /**
     * Baca satu word (panjang terenkode + isi). Mengembalikan '' untuk akhir sentence.
     */
    private function readWord($socket): string
    {
        $byte = ord($this->readBytes($socket, 1));

        if ($byte < 0x80) {
            $len = $byte;
        } elseif (($byte & 0xC0) === 0x80) {
            $len = (($byte & 0x3F) << 8) | ord($this->readBytes($socket, 1));
        } elseif (($byte & 0xE0) === 0xC0) {
            $b = $this->readBytes($socket, 2);
            $len = (($byte & 0x1F) << 16) | (ord($b[0]) << 8) | ord($b[1]);
        } elseif (($byte & 0xF0) === 0xE0) {
            $b = $this->readBytes($socket, 3);
            $len = (($byte & 0x0F) << 24) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
        } else {
            $b = $this->readBytes($socket, 4);
            $len = (ord($b[0]) << 24) | (ord($b[1]) << 16) | (ord($b[2]) << 8) | ord($b[3]);
        }

        return $len > 0 ? $this->readBytes($socket, $len) : '';
    }

    /**
     * Kirim perintah dan baca seluruh respons (semua sentence sampai !done / !fatal)
     * Key diawali '?' dikirim sebagai query word (?name=value), selain itu sebagai attribute word (=key=value).
     */
    private function sendCommand($socket, string $command, array $params = []): string
    {
        $data = $this->encodeWord($command);
        foreach ($params as $key => $value) {
            if (str_starts_with((string)$key, '?')) {
                $data .= $this->encodeWord("{$key}={$value}");
            } else {
                $data .= $this->encodeWord("={$key}={$value}");
            }
        }
        $data .= chr(0);
        fwrite($socket, $data);

        $response = '';
        while (true) {
            $first = null;
            while (($word = $this->readWord($socket)) !== '') {
                if ($first === null) {
                    $first = $word;
                }
                $response .= $word . "\n";
            }

            if ($first === '!done' || $first === '!fatal') {
                break;
            }
        }

        return $response;
    }

    /**
     * Lakukan proses autentikasi (Login)
     * - RouterOS >= 6.43 (termasuk v7): login plaintext (name + password)
     * - RouterOS < 6.43: challenge-response MD5 (=ret=)
     */
    private function login($socket): void
    {
        $response = $this->sendCommand($socket, '/login', [
            'name' => $this->user,
            'password' => $this->pass,
        ]);

        if (strpos($response, '!trap') !== false || strpos($response, '!fatal') !== false) {
            $message = 'Username / Password salah';
            if (preg_match('/=message=([^\n]+)/', $response, $m)) {
                $message = trim($m[1]);
            }
            throw new Exception('Login ke MikroTik gagal: ' . $message);
        }

        // RouterOS lama: balasan berisi challenge =ret=
        if (preg_match('/=ret=([a-f0-9]+)/i', $response, $matches)) {
            $md5 = md5(chr(0) . $this->pass . pack('H*', $matches[1]));
            $authResp = $this->sendCommand($socket, '/login', [
                'name' => $this->user,
                'response' => '00' . $md5,
            ]);

            if (strpos($authResp, '!trap') !== false || strpos($authResp, '!done') === false) {
                throw new Exception('Login ke MikroTik gagal (Username / Password salah).');
            }
        }
    }

    /**
     * Test koneksi dan ambil identity MikroTik
     */
    public function testConnection(): array
    {
        try {
            $socket = $this->connect();
            $this->login($socket);

            $identityResp = $this->sendCommand($socket, '/system/identity/print');
            fclose($socket);

            $identity = 'MikroTik Router';
            if (preg_match('/=name=([^\n]+)/', $identityResp, $m)) {
                $identity = trim($m[1]);
            }

            return [
                'success' => true,
                'identity' => $identity,
                'message' => "Terhubung ke MikroTik ({$identity})",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'identity' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable PPPoE Secret (Aktifkan Layanan)
     */
    public function enableUser(string $username): array
    {
        try {
            $socket = $this->connect();
            $this->login($socket);

            // 1. Cari user di /ppp/secret
            $find = $this->sendCommand($socket, '/ppp/secret/print', ['?name' => $username]);
            if (strpos($find, '!re') === false) {
                fclose($socket);
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            preg_match('/\.id=([^,\n]+)/', $find, $matches);
            $id = $matches[1] ?? $username;

            // 2. Set disabled=no
            $setResp = $this->sendCommand($socket, '/ppp/secret/set', [
                '.id' => $id,
                'disabled' => 'no',
            ]);

            fclose($socket);

            return [
                'success' => true,
                'message' => "User PPPoE '{$username}' berhasil diaktifkan (disabled=no).",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disable PPPoE Secret (Suspend / Isolir Layanan)
     */
    public function disableUser(string $username): array
    {
        try {
            $socket = $this->connect();
            $this->login($socket);

            // 1. Cari user di /ppp/secret
            $find = $this->sendCommand($socket, '/ppp/secret/print', ['?name' => $username]);
            if (strpos($find, '!re') === false) {
                fclose($socket);
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            preg_match('/\.id=([^,\n]+)/', $find, $matches);
            $id = $matches[1] ?? $username;

            // 2. Set disabled=yes
            $setResp = $this->sendCommand($socket, '/ppp/secret/set', [
                '.id' => $id,
                'disabled' => 'yes',
            ]);

            fclose($socket);

            return [
                'success' => true,
                'message' => "User PPPoE '{$username}' berhasil dinonaktifkan / diisolir (disabled=yes).",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kick Active Connection (/ppp/active/remove)
     * Memutus sesi aktif PPPoE sehingga pelanggan langsung reconnect dengan konfigurasi baru atau terputus
     */
    public function kickActiveConnection(string $username): array
    {
        try {
            $socket = $this->connect();
            $this->login($socket);

            // 1. Cari koneksi aktif di /ppp/active
            $activeResp = $this->sendCommand($socket, '/ppp/active/print', ['?name' => $username]);

            if (strpos($activeResp, '!re') === false) {
                fclose($socket);
                return [
                    'success' => true,
                    'kicked' => false,
                    'message' => "Tidak ada sesi aktif untuk user '{$username}' (User sedang offline/idle).",
                ];
            }

            // 2. Ambil .id sesi aktif
            preg_match('/\.id=([^,\n]+)/', $activeResp, $matches);
            $activeId = $matches[1] ?? null;

            if ($activeId) {
                $this->sendCommand($socket, '/ppp/active/remove', ['.id' => $activeId]);
            }

            fclose($socket);

            return [
                'success' => true,
                'kicked' => true,
                'message' => "Sesi aktif user '{$username}' berhasil di-kick (koneksi diputus seketika).",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'kicked' => false,
                'message' => "Gagal kick sesi PPPoE: " . $e->getMessage(),
            ];
        }
    }
}
