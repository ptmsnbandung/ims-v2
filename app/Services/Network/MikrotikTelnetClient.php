<?php

namespace App\Services\Network;

use Exception;

/**
 * Adapter Telnet dengan antarmuka mirip RouterosAPI (comm / disconnect),
 * sehingga MikrotikService dapat memakai Telnet tanpa mengubah logika bisnisnya.
 */
class MikrotikTelnetClient
{
    public function __construct(private MikrotikTelnet $telnet)
    {
    }

    public function comm(string $path, array $params = []): array
    {
        $words = array_values(array_filter(explode('/', $path), fn ($w) => $w !== ''));
        if (empty($words)) {
            throw new Exception('Perintah Telnet kosong.');
        }
        $action = array_pop($words);
        $base = '/' . implode(' ', $words);

        // Kasus khusus identity
        if ($base === '/system identity' && $action === 'print') {
            $name = $this->telnet->getIdentity();
            return [['name' => $name ?: 'MikroTik Router']];
        }

        $where = [];
        $sets = [];
        $target = null;
        foreach ($params as $key => $value) {
            if ($key === '=.id') {
                $target = (string)$value;
            } elseif (str_starts_with($key, '?')) {
                $prop = substr($key, 1);
                $valStr = (string)$value;
                if (in_array(strtolower($valStr), ['yes', 'no', 'true', 'false'])) {
                    $where[] = "{$prop}=" . strtolower($valStr);
                } else {
                    $where[] = "{$prop}=\"" . $this->esc($valStr) . '"';
                }
            } elseif (str_starts_with($key, '=')) {
                $prop = substr($key, 1);
                $valStr = (string)$value;
                if (in_array(strtolower($valStr), ['yes', 'no', 'true', 'false'])) {
                    $sets[] = "{$prop}=" . strtolower($valStr);
                } else {
                    $sets[] = "{$prop}=\"" . $this->esc($valStr) . '"';
                }
            }
        }

        if ($action === 'print') {
            $cmd = "{$base} print terse" . ($where ? ' where ' . implode(' ', $where) : '');
            $output = $this->telnet->exec($cmd);
            $items = $this->telnet->parseTerseOutput($output);

            // Fallback jika parseTerseOutput kosong padahal mencari spesifik ?name
            if (empty($items) && isset($params['?name'])) {
                $targetName = (string)$params['?name'];
                $escName = $this->esc($targetName);
                $chkOut = $this->telnet->exec(":put [:len [{$base} find name=\"{$escName}\"]]");
                $chkClean = trim(preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $chkOut));
                if (preg_match('/\b[1-9][0-9]*\b/', $chkClean)) {
                    $items[] = [
                        '.id' => 'name:' . $targetName,
                        'name' => $targetName,
                    ];
                }
            }

            foreach ($items as &$item) {
                if (isset($item['name'])) {
                    $item['.id'] = 'name:' . $item['name'];
                }
            }
            unset($item);
            return $items;
        }

        // set / remove / enable / disable
        if ($target === null) {
            throw new Exception('Target (.id) tidak ditentukan untuk perintah Telnet.');
        }
        $selector = str_starts_with($target, 'name:')
            ? '[find name="' . $this->esc(substr($target, 5)) . '"]'
            : $target;

        $cmd = "{$base} {$action} {$selector}" . ($sets ? ' ' . implode(' ', $sets) : '');
        $output = $this->telnet->exec($cmd);
        $lower = strtolower($output);
        if (str_contains($lower, 'failure') || str_contains($lower, 'no such item') || str_contains($lower, 'invalid') || str_contains($lower, 'bad command')) {
            throw new Exception('Telnet: ' . trim($output));
        }
        return [];
    }

    public function disconnect(): void
    {
        $this->telnet->disconnect();
    }

    private function esc($value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], (string)$value);
    }
}
