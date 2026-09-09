<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LevelPengguna extends Model
{
    protected $table = 'tb_m_level_pengguna';
    protected $primaryKey = 'kode_level';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kode_level',
        'level',
        'nama_level',
        'desc_level',
        'date_create',
        'user_create',
        'user_update',
        'date_update',
    ];

    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'kode_level', 'kode_level');
    }
}
