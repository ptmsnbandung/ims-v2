<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RouterOS\Client;
use RouterOS\Query;

// Pastikan autoloader untuk library RouterOS terdaftar
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'RouterOS\\')) {
        $file = __DIR__ . '/../../../vendor/evilfreelancer/routeros-api-php/src/' . str_replace('\\', '/', substr($class, 9)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

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
            $this->timeout = (int)($overrideConfig['timeout'] ?? config('mikrotik.timeout', 5));
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
                $this->pass = \Illuminate\Support\Facades\Crypt::decryptString($pass);
            } catch (Exception $e) {
                $this->pass = $pass;
            }
            $this->timeout = 5;
            $this->ssl = false;
        } else {
            // 3. Fallback
            $this->host = config('mikrotik.host', '103.161.206.19');
            $this->port = (int)config('mikrotik.port', 18735);
            $this->user = config('mikrotik.user', 'aplikasi');
            $this->pass = config('mikrotik.pass', 'kayuagung2-9');
            $this->timeout = 5;
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
     * Dapatkan instance RouterOS Client (kompatibel RouterOS v6 & v7)
     */
    private function getClient(): Client
    {
        return new Client([
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass,
            'timeout' => $this->timeout,
            'attempts' => 2,
            'delay' => 1,
            'ssl' => $this->ssl,
        ]);
    }

    /**
     * Test koneksi dan ambil identity MikroTik
     */
    public function testConnection(): array
    {
        try {
            $client = $this->getClient();
            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();

            $identity = $response[0]['name'] ?? 'MikroTik Router';

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
            $client = $this->getClient();

            // 1. Cari user di /ppp/secret
            $query = (new Query('/ppp/secret/print'))->where('name', $username);
            $secrets = $client->query($query)->read();

            if (empty($secrets) || !isset($secrets[0]['.id'])) {
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            $secretId = $secrets[0]['.id'];

            // 2. Set disabled=no
            $setQuery = (new Query('/ppp/secret/set'))
                ->equal('.id', $secretId)
                ->equal('disabled', 'no');
            $client->query($setQuery)->read();

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
            $client = $this->getClient();

            // 1. Cari user di /ppp/secret
            $query = (new Query('/ppp/secret/print'))->where('name', $username);
            $secrets = $client->query($query)->read();

            if (empty($secrets) || !isset($secrets[0]['.id'])) {
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$username}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            $secretId = $secrets[0]['.id'];

            // 2. Set disabled=yes
            $setQuery = (new Query('/ppp/secret/set'))
                ->equal('.id', $secretId)
                ->equal('disabled', 'yes');
            $client->query($setQuery)->read();

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
     * Memutus sesi aktif PPPoE sehingga pelanggan langsung reconnect dengan konfigurasi baru atau seketika terputus
     */
    public function kickActiveConnection(string $username): array
    {
        try {
            $client = $this->getClient();

            // 1. Cari koneksi aktif di /ppp/active
            $query = (new Query('/ppp/active/print'))->where('name', $username);
            $activeList = $client->query($query)->read();

            if (empty($activeList) || !isset($activeList[0]['.id'])) {
                return [
                    'success' => true,
                    'kicked' => false,
                    'message' => "Tidak ada sesi aktif untuk user '{$username}' (User sedang offline/idle).",
                ];
            }

            // 2. Hapus sesi aktif
            foreach ($activeList as $act) {
                if (isset($act['.id'])) {
                    $delQuery = (new Query('/ppp/active/remove'))->equal('.id', $act['.id']);
                    $client->query($delQuery)->read();
                }
            }

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

    /**
     * Ambil seluruh user PPPoE Secret
     */
    public function getUsers(): array
    {
        try {
            $client = $this->getClient();
            $query = new Query('/ppp/secret/print');
            return $client->query($query)->read();
        } catch (Exception $e) {
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
            $query = new Query('/ppp/active/print');
            return $client->query($query)->read();
        } catch (Exception $e) {
            Log::error('Mikrotik getActiveConnections error: ' . $e->getMessage());
            return [];
        }
    }
}
