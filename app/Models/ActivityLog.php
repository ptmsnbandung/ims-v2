<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'customer_id',
        'action',
        'old_status',
        'new_status',
        'description',
        'router_response',
        'router_success',
    ];

    protected $casts = [
        'router_success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Helper to record an activity log entry conveniently
     */
    public static function record(array $data): self
    {
        $userId = $data['user_id'] ?? (auth()->user()?->username ?? auth()->user()?->nama ?? 'System');
        
        return self::create([
            'user_id' => $userId,
            'customer_id' => $data['customer_id'] ?? null,
            'action' => $data['action'] ?? 'unknown',
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? null,
            'description' => $data['description'] ?? null,
            'router_response' => isset($data['router_response']) && is_array($data['router_response']) 
                ? json_encode($data['router_response']) 
                : ($data['router_response'] ?? null),
            'router_success' => (bool)($data['router_success'] ?? false),
        ]);
    }

    /**
     * Action badge formatted label & color scheme
     */
    public function getActionBadgeAttribute(): array
    {
        return match (strtolower((string)$this->action)) {
            'activate', 'aktivasi', 'pppoe_activate' => [
                'label' => 'Aktivasi Layanan',
                'bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'icon' => 'check-circle'
            ],
            'suspend', 'isolir' => [
                'label' => 'Suspend / Isolir',
                'bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                'icon' => 'no-symbol'
            ],
            'kick', 'kick_session' => [
                'label' => 'Kick Session',
                'bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                'icon' => 'bolt'
            ],
            'reboot_onu', 'onu_reboot' => [
                'label' => 'Reboot ONU',
                'bg' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                'icon' => 'arrow-path'
            ],
            'test_connection', 'test_conn' => [
                'label' => 'Tes Koneksi Router',
                'bg' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',
                'icon' => 'signal'
            ],
            'sync_router', 'sync_customers' => [
                'label' => 'Sinkronisasi Router',
                'bg' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                'icon' => 'arrow-path-rounded-square'
            ],
            'create_router' => [
                'label' => 'Tambah Router',
                'bg' => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                'icon' => 'plus-circle'
            ],
            'update_router' => [
                'label' => 'Update Router',
                'bg' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                'icon' => 'pencil-square'
            ],
            'delete_router' => [
                'label' => 'Hapus Router',
                'bg' => 'bg-red-500/10 text-red-400 border-red-500/30',
                'icon' => 'trash'
            ],
            default => [
                'label' => ucfirst((string)$this->action),
                'bg' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                'icon' => 'command-line'
            ]
        };
    }
}
