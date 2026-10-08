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
            
            $pass = $router->password ?: config('mikrotik.pass', 'kayuagung2-9');
            try {
                $this->pass = Crypt::decryptString($pass);
            } catch (Throwable $e) {
                if (is_string($pass) && str_starts_with($pass, 'eyJ')) {
                    $this->pass = config('mikrotik.pass', 'kayuagung2-9');
                } else {
                    $this->pass = $pass;
                }
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

    public function getHost(): string
    {
        return $this->host ?? '';
    }

    /**
     * Cek apakah user PPPoE terdaftar di router ini (/ppp/secret)
     * Mengembalikan nama secret yang cocok jika ditemukan, atau false jika tidak ada.
     */
    public function userExists(string|array $username): string|false
    {
        $usernames = is_array($username) ? array_values(array_unique(array_filter($username))) : [$username];
        if (empty($usernames)) {
            return false;
        }

        try {
            $client = $this->getClient();
            $foundName = false;

            foreach ($usernames as $u) {
                $secrets = $client->comm('/ppp/secret/print', [
                    '?name' => $u,
                ]);
                if (!empty($secrets) && isset($secrets[0]['.id'])) {
                    $foundName = $u;
                    break;
                }
            }

            $client->disconnect();
            return $foundName;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Dapatkan koneksi client yang sudah terautentikasi (RouterosAPI atau MikrotikTelnetClient)
     */
    private function getClient(): object
    {
        // 1. Jika port 23 atau 2329, ini adalah port Telnet
        if ((int)$this->port === 23 || (int)$this->port === 2329) {
            try {
                return $this->getTelnetClient();
            } catch (Throwable $telnetErr) {
                try {
                    return $this->getApiClient();
                } catch (Throwable) {
                    throw $telnetErr;
                }
            }
        }

        // 2. Untuk port standar API (8728, 8729) dan port custom (18735, dll),
        // utamakan RouterosAPI terlebih dahulu karena merespon instan (0.1s - 0.5s)
        // dan menghindari delay 8 detik Telnet prompt negotiation.
        $apiException = null;
        try {
            return $this->getApiClient();
        } catch (Throwable $e) {
            $apiException = $e;
        }

        // 3. Fallback: jika API gagal, coba Telnet client
        try {
            return $this->getTelnetClient();
        } catch (Throwable $telnetException) {
            $apiReason = $apiException ? $apiException->getMessage() : 'gagal';
            throw new Exception("API ({$apiReason}) & Telnet ({$telnetException->getMessage()})");
        }
    }

    /**
     * Inisialisasi koneksi via Telnet client
     */
    private function getTelnetClient(): MikrotikTelnetClient
    {
        $telnet = new MikrotikTelnet($this->host, $this->port, $this->user, $this->pass, $this->timeout);
        if (!$telnet->connect()) {
            throw new Exception($telnet->error ?: "Gagal terhubung ke Telnet ({$this->host}:{$this->port})");
        }
        return new MikrotikTelnetClient($telnet);
    }

    /**
     * Inisialisasi koneksi via RouterosAPI client
     */
    private function getApiClient(): RouterosAPI
    {
        $api = new RouterosAPI();
        $api->timeout = $this->timeout;
        $connected = $api->connect($this->host, $this->user, $this->pass, $this->port, $this->ssl);
        
        if (!$connected) {
            $detail = trim((string)($api->error_str ?? ''));
            if ($detail === '') {
                $errno = 0;
                $errstr = '';
                $sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
                if ($sock === false) {
                    $reason = $errstr !== ''
                        ? "port TCP tidak dapat dijangkau ({$errstr}, errno {$errno})"
                        : 'timeout / port tertutup';
                } else {
                    fclose($sock);
                    $reason = 'port terbuka tetapi handshake/login API gagal (mungkin port ini adalah Telnet/SSH, bukan RouterOS API)';
                }
                $detail = "Gagal via API ({$this->host}:{$this->port}): {$reason}";
            } else {
                $detail = "API: {$detail} ({$this->host}:{$this->port})";
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
            $protocol = ($client instanceof MikrotikTelnetClient) ? 'Telnet' : 'API';

            $client->disconnect();

            return [
                'success' => true,
                'identity' => $identity,
                'message' => "Terhubung ke MikroTik via {$protocol} ({$identity})",
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
    public function enableUser(string|array $username): array
    {
        $usernames = is_array($username) ? array_values(array_unique(array_filter($username))) : [$username];
        if (empty($usernames)) {
            return ['success' => false, 'message' => 'Username PPPoE kosong.'];
        }

        try {
            $client = $this->getClient();
            $secretId = null;
            $matchedUser = null;

            foreach ($usernames as $u) {
                $secrets = $client->comm('/ppp/secret/print', [
                    '?name' => $u,
                ]);
                if (!empty($secrets) && isset($secrets[0]['.id'])) {
                    $secretId = $secrets[0]['.id'];
                    $matchedUser = $u;
                    break;
                }
            }

            if (!$secretId) {
                $client->disconnect();
                $tested = implode(' / ', $usernames);
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$tested}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            // 2. Set disabled=no
            $client->comm('/ppp/secret/set', [
                '=.id' => $secretId,
                '=disabled' => 'no',
            ]);

            $client->disconnect();

            return [
                'success' => true,
                'matched_user' => $matchedUser,
                'message' => "User PPPoE '{$matchedUser}' berhasil diaktifkan (disabled=no).",
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
    public function disableUser(string|array $username): array
    {
        $usernames = is_array($username) ? array_values(array_unique(array_filter($username))) : [$username];
        if (empty($usernames)) {
            return ['success' => false, 'message' => 'Username PPPoE kosong.'];
        }

        try {
            $client = $this->getClient();
            $secretId = null;
            $matchedUser = null;

            foreach ($usernames as $u) {
                $secrets = $client->comm('/ppp/secret/print', [
                    '?name' => $u,
                ]);
                if (!empty($secrets) && isset($secrets[0]['.id'])) {
                    $secretId = $secrets[0]['.id'];
                    $matchedUser = $u;
                    break;
                }
            }

            if (!$secretId) {
                $client->disconnect();
                $tested = implode(' / ', $usernames);
                return [
                    'success' => false,
                    'message' => "User PPPoE '{$tested}' tidak ditemukan di MikroTik ({$this->host}).",
                ];
            }

            // 2. Set disabled=yes
            $client->comm('/ppp/secret/set', [
                '=.id' => $secretId,
                '=disabled' => 'yes',
            ]);

            $client->disconnect();

            return [
                'success' => true,
                'matched_user' => $matchedUser,
                'message' => "User PPPoE '{$matchedUser}' berhasil dinonaktifkan / diisolir (disabled=yes).",
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
    public function kickActiveConnection(string|array $username): array
    {
        $usernames = is_array($username) ? array_values(array_unique(array_filter($username))) : [$username];
        if (empty($usernames)) {
            return ['success' => true, 'kicked' => false, 'message' => 'Username kosong.'];
        }

        try {
            $client = $this->getClient();
            $kickedCount = 0;

            foreach ($usernames as $u) {
                $activeList = $client->comm('/ppp/active/print', [
                    '?name' => $u,
                ]);

                if (!empty($activeList)) {
                    foreach ($activeList as $act) {
                        if (isset($act['.id'])) {
                            $client->comm('/ppp/active/remove', [
                                '=.id' => $act['.id'],
                            ]);
                            $kickedCount++;
                        }
                    }
                }
            }

            $client->disconnect();

            $primaryName = $usernames[0];
            if ($kickedCount === 0) {
                return [
                    'success' => true,
                    'kicked' => false,
                    'message' => "Tidak ada sesi aktif untuk user '{$primaryName}' (User sedang offline/idle).",
                ];
            }

            return [
                'success' => true,
                'kicked' => true,
                'message' => "Sesi aktif user '{$primaryName}' ({$kickedCount} koneksi) berhasil di-kick (koneksi diputus seketika).",
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
            throw new Exception("Gagal membaca secret dari MikroTik ({$this->host}): " . $e->getMessage(), 0, $e);
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

    /**
     * Ambil seluruh profile PPP dari MikroTik (/ppp/profile/print)
     */
    public function getPppProfiles(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->comm('/ppp/profile/print');
            $client->disconnect();
            if (!is_array($result)) return [];

            $profiles = [];
            foreach ($result as $p) {
                if (isset($p['name'])) {
                    $profiles[] = [
                        'name' => $p['name'],
                        'local_address' => $p['local-address'] ?? '',
                        'remote_address' => $p['remote-address'] ?? '',
                        'rate_limit' => $p['rate-limit'] ?? '',
                        'comment' => $p['comment'] ?? '',
                    ];
                }
            }
            return $profiles;
        } catch (Throwable $e) {
            Log::error('Mikrotik getPppProfiles error: ' . $e->getMessage());
            return [];
        }
    }
}
