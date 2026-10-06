<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
            $this->host = $overrideConfig['host'] ?? $overrideConfig['ip_address'] ?? config('mikrotik.host', '103.161.206.19');
            $this->port = (int)($overrideConfig['port'] ?? config('mikrotik.port', 18735));
            $this->user = $overrideConfig['user'] ?? $overrideConfig['username'] ?? config('mikrotik.user', 'aplikasi');
            $this->pass = $overrideConfig['pass'] ?? $overrideConfig['password'] ?? config('mikrotik.pass', 'kayuagung2-9');
            $this->timeout = (int)($overrideConfig['timeout'] ?? config('mikrotik.timeout', 4));
            $this->ssl = (bool)($overrideConfig['ssl'] ?? config('mikrotik.ssl', false));
            return;
        }

        // 2. Cek koneksi router aktif dari tabel `routers`
        $router = null;
        if (Schema::hasTable('routers')) {
            $router = DB::table('routers')
                ->where('is_active', 1)
                ->first();
        }

        if ($router && !empty($router->host)) {
            $this->host = $router->host;
            $this->port = (int)($router->port ?: 18735);
            $this->user = $router->username ?: 'aplikasi';
            
            $pass = $router->password ?: 'kayuagung2-9';
            try {
                $this->pass = Crypt::decryptString($pass);
            } catch (Exception $e) {
                $this->pass = $pass;
            }
            $this->timeout = 4;
            $this->ssl = false;
        } else {
            // 3. Fallback
            $this->host = config('mikrotik.host', '103.161.206.19');
            $this->port = (int)config('mikrotik.port', 18735);
            $this->user = config('mikrotik.user', 'aplikasi');
            $this->pass = config('mikrotik.pass', 'kayuagung2-9');
            $this->timeout = 4;
            $this->ssl = false;
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
     * Dapatkan koneksi API client yang sudah terautentikasi
     */
    private function getClient(): RouterosAPI
    {
        $api = new RouterosAPI();
        $api->timeout = $this->timeout;
        $connected = $api->connect($this->host, $this->user, $this->pass, $this->port, $this->ssl);
        
        if (!$connected) {
            $detail = trim((string)($api->error_str ?? ''));
            if ($detail === '') {
                // Diagnosa: cek apakah port TCP terbuka untuk membedakan penyebab
                $errno = 0;
                $errstr = '';
                $sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
                if ($sock === false) {
                    $reason = $errstr !== ''
                        ? "port TCP tidak dapat dijangkau ({$errstr}, errno {$errno})"
                        : 'timeout / tidak ada respon (cek firewall, IP publik, NAT / port forward, atau service API MikroTik)';
                } else {
                    fclose($sock);
                    $reason = 'port terbuka tetapi login API gagal (cek username/password atau izin user API)';
                }
                $detail = "Tidak dapat terhubung ke MikroTik ({$this->host}:{$this->port}): {$reason}";
            } else {
                $detail = "{$detail} ({$this->host}:{$this->port})";
            }
            throw new Exception($detail);
        }

        return $api;
    }

    /**
     * Test koneksi dan ambil identity MikroTik
     */
    public function testConnection(): array
    {
        try {
            $client = $this->getClient();
            $response = $client->comm('/system/identity/print');
            $identity = $response[0]['name'] ?? 'MikroTik Router';

            $client->disconnect();

            return [
                'success' => true,
                'identity' => $identity,
                'message' => "Terhubung ke MikroTik ({$identity})",
            ];
        } catch (Throwable $e) {
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
            $client = $this->getClient();

            // 1. Cari user di /ppp/secret
            $secrets = $client->comm('/ppp/secret/print', [
                '?name' => $username,
            ]);

            if (empty($secrets) || !isset($secrets[0]['.id'])) {
                $client->disconnect();
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            $secretId = $secrets[0]['.id'];

            // 2. Set disabled=no
            $client->comm('/ppp/secret/set', [
                '=.id' => $secretId,
                '=disabled' => 'no',
            ]);

            $client->disconnect();

            return [
                'success' => true,
                'message' => "User PPPoE '{$username}' berhasil diaktifkan (disabled=no).",
            ];
        } catch (Throwable $e) {
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
            $client = $this->getClient();

            // 1. Cari user di /ppp/secret
            $secrets = $client->comm('/ppp/secret/print', [
                '?name' => $username,
            ]);

            if (empty($secrets) || !isset($secrets[0]['.id'])) {
                $client->disconnect();
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            $secretId = $secrets[0]['.id'];

            // 2. Set disabled=yes
            $client->comm('/ppp/secret/set', [
                '=.id' => $secretId,
                '=disabled' => 'yes',
            ]);

            $client->disconnect();

            return [
                'success' => true,
                'message' => "User PPPoE '{$username}' berhasil dinonaktifkan / diisolir (disabled=yes).",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kick Active Connection (/ppp/active/remove)
     * Memutus sesi aktif PPPoE sehingga pelanggan langsung reconnect dengan konfigurasi baru atau seketika terputus
     */
    public function kickActiveConnection(string $username): array
    {
        try {
            $client = $this->getClient();

            // 1. Cari koneksi aktif di /ppp/active
            $activeList = $client->comm('/ppp/active/print', [
                '?name' => $username,
            ]);

            if (empty($activeList) || !isset($activeList[0]['.id'])) {
                $client->disconnect();
                return [
                    'success' => true,
                    'kicked' => false,
                    'message' => "Tidak ada sesi aktif untuk user '{$username}' (User sedang offline/idle).",
                ];
            }

            // 2. Hapus sesi aktif
            foreach ($activeList as $act) {
                if (isset($act['.id'])) {
                    $client->comm('/ppp/active/remove', [
                        '=.id' => $act['.id'],
                    ]);
                }
            }

            $client->disconnect();

            return [
                'success' => true,
                'kicked' => true,
                'message' => "Sesi aktif user '{$username}' berhasil di-kick (koneksi diputus seketika).",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'kicked' => false,
                'message' => "Gagal kick sesi PPPoE: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil seluruh user PPPoE Secret
     */
    public function getUsers(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->comm('/ppp/secret/print');
            $client->disconnect();
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            Log::error('Mikrotik getUsers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil seluruh koneksi aktif PPPoE
     */
    public function getActiveConnections(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->comm('/ppp/active/print');
            $client->disconnect();
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            Log::error('Mikrotik getActiveConnections error: ' . $e->getMessage());
            return [];
        }
    }
}
