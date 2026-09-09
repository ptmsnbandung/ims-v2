<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Karyawan extends Model
{
    protected $table = 'tb_m_karyawan';
    protected $primaryKey = 'kode_karyawan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kode_karyawan',
        'nik',
        'nip',
        'nama_karyawan',
        'hp_karyawan',
        'email_karyawan',
        'email_msn',
        'foto',
        'status_aktif',
    ];

    public function pengguna(): HasOne
    {
        return $this->hasOne(Pengguna::class, 'kode_karyawan', 'kode_karyawan');
    }
}
