<?php

namespace App\Http\Controllers\Teknik;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeknikController extends Controller
{
    /**
     * Menu Dashboard Tiket
     * Hanya menampilkan dan menghitung tiket yang berstatus AKTIF/PENDING (Belum Terkonfirmasi).
     * Data yang sudah terkonfirmasi / selesai / closed tidak akan muncul.
     */
    public function tiket(Request $request): View
    {
        $user = $request->user();

        $counts = [
            // 1. Gangguan Layanan (kat_tiket not in [12, 13] dan status = 11 [Request/Belum Selesai])
            'gangguan' => Schema::hasTable('trx_tiket_gangguan')
                ? DB::table('trx_tiket_gangguan')
                    ->whereNotIn('kat_tiket', ['12', '13'])
                    ->where('status', '11')
                    ->count()
                : 0,

            // 2. Ubah Password (kat_tiket = 12 dan status = 11 [Request/Belum Selesai])
            'ubah_password' => Schema::hasTable('trx_tiket_gangguan')
                ? DB::table('trx_tiket_gangguan')
                    ->where('kat_tiket', '12')
                    ->where('status', '11')
                    ->count()
                : 0,

            // 3. Relokasi Layanan (kat_tiket = 13 dan status = 11 [Request/Belum Selesai])
            'relokasi' => Schema::hasTable('trx_tiket_gangguan')
                ? DB::table('trx_tiket_gangguan')
                    ->where('kat_tiket', '13')
                    ->where('status', '11')
                    ->count()
                : 0,

            // 4. Cek Coverage Area (Data request coverage yang belum diproses)
            'coverage' => Schema::hasTable('trx_coverage_area')
                ? DB::table('trx_coverage_area')
                    ->when(Schema::hasColumn('trx_coverage_area', 'status'), function ($q) {
                        return $q->where('status', '11');
                    })
                    ->count()
                : 0,

            // 5. Terminasi (status_terminasi = 11 [Request Terminasi Baru])
            'terminasi' => Schema::hasTable('trx_terminasi')
                ? DB::table('trx_terminasi')
                    ->where('status_terminasi', '11')
                    ->count()
                : 0,

            // 6. Suspend Layanan (status_suspend = 11 [Request Suspend] atau 18 [Req Unsuspend])
            'suspend' => Schema::hasTable('trx_suspend')
                ? DB::table('trx_suspend')
                    ->whereIn('status_suspend', ['11', '18'])
                    ->count()
                : 0,

            // 7. Pemasangan Baru (status_reg proses pendaftaran baru)
            'pemasangan_baru' => Schema::hasTable('trx_batchjob_register')
                ? DB::table('trx_batchjob_register')
                    ->whereIn('status_reg', ['11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '19', '19.1'])
                    ->count()
                : 0,

            // 8. Ubah Layanan (status_ubah_layanan = 11 [Request] atau 12 [On Schedule])
            'ubah_layanan' => Schema::hasTable('trx_ubah_layanan')
                ? DB::table('trx_ubah_layanan')
                    ->whereIn('status_ubah_layanan', ['11', '12'])
                    ->count()
                : 0,
        ];

        // Dynamic Role-Aware Destination URLs for all 8 cards
        $destinations = [
            'gangguan' => route('teknik.tiket.gangguan', ['kategori' => 'gangguan']),
            'ubah_password' => route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']),
            'relokasi' => route('teknik.tiket.gangguan', ['kategori' => 'relokasi']),
            'coverage' => route('teknik.coverage'),
            'terminasi' => ($user?->isNoc()) 
                ? route('noc.terminasi') 
                : (($user?->isFinance()) ? route('finance.permintaan.terminasi') : route('teknik.permintaan.terminasi')),
            'suspend' => ($user?->isNoc()) 
                ? route('noc.suspend') 
                : (($user?->isFinance()) ? route('finance.permintaan.suspend') : route('teknik.permintaan.suspend')),
            'pemasangan_baru' => ($user?->isNoc()) 
                ? route('noc.aktivasi') 
                : (($user?->isFinance()) ? route('finance.billing-registrasi') : route('teknik.pendaftaran')),
            'ubah_layanan' => ($user?->isFinance()) 
                ? route('finance.permintaan.up-downgrade') 
                : route('teknik.permintaan.up-downgrade'),
        ];

        return view('teknik.tiket', [
            'user' => $user,
            'counts' => $counts,
            'destinations' => $destinations,
        ]);
    }

    /**
     * Dashboard Tiket Gangguan & Ubah Password (Matching Screenshot IMS Layout)
     */
    public function tiketGangguan(Request $request): View
    {
        $search = $request->query('search');
        $kategori = $request->query('kategori'); // 'gangguan', 'ubah_password', or specific kat_tiket
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status'); // '11', '12', '13', '14'

        $this->ensureTiketGangguanColumns();
        $hasTable = Schema::hasTable('trx_tiket_gangguan');

        if ($hasTable) {
            $query = DB::table('trx_tiket_gangguan as t')
                ->leftJoin('view_batchjob as b', 't.nomor_internet', '=', 'b.nomor_internet')
                ->select([
                    't.*',
                    'b.nama_pelanggan',
                    'b.alamat_p',
                    'b.alamat_pasang',
                    'b.nama_kategori_bandwith',
                    'b.nomor_hp',
                    'b.nama_pop',
                    'b.kode_pop',
                ]);

            // Filter Kategori (Gangguan Layanan vs Ubah Password vs Relokasi)
            if ($kategori === 'gangguan') {
                if (Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                    $query->whereNotIn('t.kat_tiket', ['12', '13']);
                }
            } elseif ($kategori === 'ubah_password') {
                if (Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                    $query->where('t.kat_tiket', '12');
                }
            } elseif ($kategori === 'relokasi' || $kategori === '13') {
                if (Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                    $query->where('t.kat_tiket', '13');
                }
            } elseif (!empty($kategori)) {
                if (Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                    $query->where('t.kat_tiket', $kategori);
                }
            }

            // Filter Layanan / Bandwidth Category
            if ($layanan) {
                $query->where('b.nama_kategori_bandwith', $layanan);
            }

            // Filter Search (nomor_internet, nama_pelanggan, tiket, kode_trx_tiket, id_tiket, keluhan)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t.nomor_internet', 'like', "%{$search}%")
                      ->orWhere('b.nama_pelanggan', 'like', "%{$search}%");
                    if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
                        $q->orWhere('t.tiket', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                        $q->orWhere('t.kode_trx_tiket', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket')) {
                        $q->orWhere('t.id_tiket', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'keluhan')) {
                        $q->orWhere('t.keluhan', 'like', "%{$search}%");
                    }
                });
            }

            // Filter Wilayah
            if ($wilayah) {
                $query->where(function ($q) use ($wilayah) {
                    $q->where('b.alamat_p', 'like', "%{$wilayah}%")
                      ->orWhere('b.alamat_pasang', 'like', "%{$wilayah}%")
                      ->orWhere('b.nama_pop', 'like', "%{$wilayah}%");
                });
            }

            // Filter Status (11, 12, 13, 14)
            if ($status) {
                if (Schema::hasColumn('trx_tiket_gangguan', 'status')) {
                    if ($status === '13') {
                        $query->where(function ($q) {
                            $q->where('t.status', '13')->orWhere('t.status', 'Selesai');
                        });
                    } elseif ($status === '14') {
                        $query->where(function ($q) {
                            $q->where('t.status', '14')->orWhere('t.status', 'Cancel')->orWhere('t.status', 'Dibatalkan');
                        });
                    } else {
                        $query->where('t.status', $status);
                    }
                }
            }

            $orderCol = Schema::hasColumn('trx_tiket_gangguan', 'date_create') ? 't.date_create' : 't.created_at';
            $tikets = $query->orderBy($orderCol, 'desc')->paginate(10)->withQueryString();

            // Status Counters (KD11, KD12, KD13, KD14)
            $countQuery = DB::table('trx_tiket_gangguan');
            if ($kategori === 'gangguan' && Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                $countQuery->whereNotIn('kat_tiket', ['12', '13']);
            } elseif ($kategori === 'ubah_password' && Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                $countQuery->where('kat_tiket', '12');
            } elseif (($kategori === 'relokasi' || $kategori === '13') && Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                $countQuery->where('kat_tiket', '13');
            }

            $counts = $countQuery->selectRaw("
                COUNT(CASE WHEN status = '11' THEN 1 END) as c11,
                COUNT(CASE WHEN status = '12' THEN 1 END) as c12,
                COUNT(CASE WHEN status = '13' OR status = 'Selesai' THEN 1 END) as c13,
                COUNT(CASE WHEN status = '14' OR status = 'Cancel' OR status = 'Dibatalkan' THEN 1 END) as c14
            ")->first();

            $count11 = (int) ($counts->c11 ?? 0);
            $count12 = (int) ($counts->c12 ?? 0);
            $count13 = (int) ($counts->c13 ?? 0);
            $count14 = (int) ($counts->c14 ?? 0);
        } else {
            $tikets = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $count11 = 0;
            $count12 = 0;
            $count13 = 0;
            $count14 = 0;
        }

        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique()
            : collect();

        $karyawanTeknisi = Schema::hasTable('tb_m_karyawan')
            ? DB::table('tb_m_karyawan')->where('status_aktif', 1)->orderBy('nama_karyawan')->get(['kode_karyawan', 'nama_karyawan'])
            : collect();

        return view('teknik.tiket-gangguan', [
            'user' => $request->user(),
            'tikets' => $tikets,
            'search' => $search,
            'kategori' => $kategori,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'count11' => $count11,
            'count12' => $count12,
            'count13' => $count13,
            'count14' => $count14,
            'layananList' => $layananList,
            'karyawanTeknisi' => $karyawanTeknisi,
        ]);
    }

    /**
     * Export Tiket Gangguan ke format CSV
     */
    public function exportTiketGangguan(Request $request): StreamedResponse
    {
        $search = $request->query('search');
        $kategori = $request->query('kategori');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');

        $query = DB::table('trx_tiket_gangguan as t')
            ->leftJoin('view_batchjob as b', 't.nomor_internet', '=', 'b.nomor_internet')
            ->select([
                't.*',
                'b.nama_pelanggan',
                'b.alamat_p',
                'b.alamat_pasang',
                'b.nama_kategori_bandwith',
                'b.nomor_hp',
                'b.nama_pop',
                'b.kode_pop'
            ]);

        if ($kategori === 'gangguan') {
            $query->where('t.kat_tiket', '!=', '12');
        } elseif ($kategori === 'ubah_password') {
            $query->where('t.kat_tiket', '12');
        }

        if ($status) {
            $query->where('t.status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('t.nomor_internet', 'like', "%{$search}%");
                if (Schema::hasColumn('trx_tiket_gangguan', 'nama_pelanggan')) {
                    $q->orWhere('t.nama_pelanggan', 'like', "%{$search}%");
                }
            });
        }

        $filename = 'Export_Tiket_Gangguan_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No Tiket', 'Nomor Internet', 'Nama Pelanggan', 'Kategori Tiket', 'Status', 'Keluhan', 'Solusi', 'Teknisi', 'Jadwal', 'Tanggal Buat']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $r) {
                    $katLabel = match ($r->kat_tiket) {
                        '12' => 'Ubah Password',
                        '13' => 'Relokasi Layanan',
                        default => 'Gangguan Layanan',
                    };
                    $statusLabel = match ($r->status) {
                        '11' => 'Request',
                        '12' => 'On Schedule',
                        '13', 'Selesai' => 'Success / Selesai',
                        '14', 'Cancel', 'Dibatalkan' => 'Canceled',
                        default => $r->status ?? '-',
                    };

                    fputcsv($handle, [
                        $r->tiket ?? $r->kode_trx_tiket ?? $r->id_tiket ?? '-',
                        $r->nomor_internet ?? '-',
                        $r->nama_pelanggan ?? '-',
                        $katLabel,
                        $statusLabel,
                        $r->keluhan ?? '-',
                        $r->solusi ?? $r->penanganan ?? '-',
                        $r->team_teknisi ?? '-',
                        $r->date_schedule ? ($r->date_schedule . ' ' . ($r->time_schedule ?? '')) : '-',
                        $r->date_create ?? $r->created_at ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Check customer data by nomor_internet for ticket creation modal (AJAX)
     */
    public function checkCustomerForTiket(Request $request): JsonResponse
    {
        $nomorInternet = trim((string) $request->query('nomor_internet'));
        if (!$nomorInternet) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor internet tidak boleh kosong.',
            ], 422);
        }

        $customer = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->first();

        if (!$customer && Schema::hasTable('trx_batchjob_register')) {
            $customer = DB::table('trx_batchjob_register as r')
                ->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk')
                ->leftJoin('m_pop as pop', 'r.kode_pop', '=', 'pop.kode_pop')
                ->where('r.nomor_internet', $nomorInternet)
                ->select(
                    'r.nomor_internet',
                    'p.nama_penduduk as nama_pelanggan',
                    'r.alamat_pasang',
                    'p.alamat_ktp as alamat_p',
                    'pop.nama_pop'
                )
                ->first();
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => "Pelanggan dengan Nomor Internet '{$nomorInternet}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nomor_internet' => $customer->nomor_internet ?? $nomorInternet,
                'nama_pelanggan' => $customer->nama_pelanggan ?? ($customer->batch_nama ?? 'Pelanggan'),
                'alamat' => $customer->alamat_pasang ?? ($customer->alamat_p ?? '-'),
                'nama_pop' => $customer->nama_pop ?? '-',
                'media_akses' => $customer->media_akses ?? 'FTTH',
                'pass_pppoe' => $customer->pass_pppoe ?? ($customer->password ?? null),
            ],
        ]);
    }

    /**
     * Ensure table trx_tiket_gangguan and all required columns exist
     */
    protected function ensureTiketGangguanColumns(): void
    {
        try {
            if (!Schema::hasTable('trx_tiket_gangguan')) {
                Schema::create('trx_tiket_gangguan', function (Blueprint $table) {
                    $table->id('id_tiket');
                    $table->string('kode_trx_tiket', 50)->nullable()->index();
                    $table->string('nomor_internet', 50)->index();
                    $table->string('nama_pelanggan', 150)->nullable();
                    $table->string('kat_tiket', 20)->default('11');
                    $table->string('status', 20)->default('11');
                    $table->text('keluhan')->nullable();
                    $table->string('prioritas', 20)->default('Normal');
                    $table->string('team_teknisi', 100)->nullable();
                    $table->date('date_schedule')->nullable();
                    $table->string('time_schedule', 50)->nullable();
                    $table->text('solusi')->nullable();
                    $table->dateTime('date_create')->nullable();
                    $table->string('user_create', 100)->nullable();
                    $table->dateTime('date_update')->nullable();
                    $table->string('user_update', 100)->nullable();
                    $table->timestamps();
                });
            } else {
                Schema::table('trx_tiket_gangguan', function (Blueprint $table) {
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                        $table->string('kode_trx_tiket', 50)->nullable()->index();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'nama_pelanggan')) {
                        $table->string('nama_pelanggan', 150)->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'kat_tiket')) {
                        $table->string('kat_tiket', 20)->default('11');
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'status')) {
                        $table->string('status', 20)->default('11');
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'keluhan')) {
                        $table->text('keluhan')->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'prioritas')) {
                        $table->string('prioritas', 20)->default('Normal');
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'team_teknisi')) {
                        $table->string('team_teknisi', 100)->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'date_schedule')) {
                        $table->date('date_schedule')->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'time_schedule')) {
                        $table->string('time_schedule', 50)->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'solusi')) {
                        $table->text('solusi')->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'date_create')) {
                        $table->dateTime('date_create')->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'user_create')) {
                        $table->string('user_create', 100)->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'date_update')) {
                        $table->dateTime('date_update')->nullable();
                    }
                    if (!Schema::hasColumn('trx_tiket_gangguan', 'user_update')) {
                        $table->string('user_update', 100)->nullable();
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::warning("ensureTiketGangguanColumns notice: " . $e->getMessage());
        }
    }

    /**
     * Buat Tiket Baru (Ganti Password / Gangguan)
     */
    public function storeTiketGangguan(Request $request): RedirectResponse
    {
        $this->ensureTiketGangguanColumns();

        $request->validate([
            'nomor_internet' => 'required|string',
            'perubahan' => 'nullable|string',
            'keluhan' => 'nullable|string',
        ]);

        $nomorInternet = trim($request->nomor_internet);
        $kategori = $request->input('kat_tiket', $request->input('kategori', '11'));
        if ($kategori === 'ubah_password') {
            $kategori = '12';
        } elseif ($kategori === 'relokasi') {
            $kategori = '13';
        } elseif ($kategori === 'gangguan') {
            $kategori = '11';
        }

        if ($kategori === '13') {
            // Relokasi Format
            $jenisRelokasi = $request->input('jenis_relokasi', 'Eksternal');
            $alamatBaru = trim($request->input('alamat_baru', ''));
            $picBaru = trim($request->input('pic_baru', ''));
            $catatanRelokasi = trim($request->input('catatan_relokasi', $request->input('keluhan', '')));

            $keluhanParts = ["[PERMINTAAN RELOKASI LAYANAN]"];
            $keluhanParts[] = "• Jenis Relokasi : " . $jenisRelokasi;
            if (!empty($alamatBaru)) {
                $keluhanParts[] = "• Alamat Baru : " . $alamatBaru;
            }
            if (!empty($picBaru)) {
                $keluhanParts[] = "• Kontak PIC : " . $picBaru;
            }
            if (!empty($catatanRelokasi)) {
                $keluhanParts[] = "• Keterangan : " . $catatanRelokasi;
            }
            $keluhan = implode(" \n", $keluhanParts);
        } else {
            $keluhan = $request->filled('perubahan') ? $request->perubahan : ($request->keluhan ?: 'Permintaan Pelanggan');
        }
        $currentUser = auth()->user()->nama ?? auth()->user()->username ?? 'Staff';
        $now = now()->format('Y-m-d H:i:s');

        // Generate Ticket Code (e.g. 12 + Ymd + 3-digit sequence)
        $datePrefix = date('Ymd');
        $katCode = is_numeric($kategori) ? $kategori : '12';
        $prefix = $katCode . $datePrefix;
        
        $countToday = DB::table('trx_tiket_gangguan')
            ->where(function($q) use ($prefix) {
                if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
                    $q->where('tiket', 'like', "{$prefix}%");
                }
                if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                    $q->orWhere('kode_trx_tiket', 'like', "{$prefix}%");
                } elseif (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket')) {
                    $q->orWhere('id_tiket', 'like', "{$prefix}%");
                }
            })
            ->count();
        
        $generatedCode = $prefix . str_pad((string)($countToday + 1), 3, '0', STR_PAD_LEFT);

        $customer = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->first();

        $payload = [
            'nomor_internet' => $nomorInternet,
            'kat_tiket' => $kategori,
            'keluhan' => $keluhan,
            'status' => '11', // (KD11) Antrian / Request
            'date_create' => $now,
            'date_update' => $now,
        ];

        if (Schema::hasColumn('trx_tiket_gangguan', 'solusi')) {
            $payload['solusi'] = ($kategori == '12') ? 'tim customer care kami akan segera menghubungi anda' : null;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'penanganan')) {
            $payload['penanganan'] = ($kategori == '12') ? 'tim customer care kami akan segera menghubungi anda' : null;
        }
        if ($customer && Schema::hasColumn('trx_tiket_gangguan', 'nama_pelanggan')) {
            $payload['nama_pelanggan'] = $customer->nama_pelanggan ?? null;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
            $payload['tiket'] = $generatedCode;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
            $payload['kode_trx_tiket'] = $generatedCode;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket') && !Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
            $payload['id_tiket'] = $generatedCode;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'user_create')) {
            $payload['user_create'] = $currentUser;
        }
        if (Schema::hasColumn('trx_tiket_gangguan', 'user_update')) {
            $payload['user_update'] = $currentUser;
        }

        try {
            DB::table('trx_tiket_gangguan')->insert($payload);

            return redirect()->back()
                ->with('success', "Tiket #{$generatedCode} untuk nomor internet {$nomorInternet} berhasil dibuat!")
                ->with('tiket_created', true);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat tiket: ' . $e->getMessage());
        }
    }

    /**
     * Jadwalkan Penanganan Tiket Gangguan (KD12)
     */
    public function scheduleTiketGangguan(Request $request, string $id): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin'])) {
            abort(403, 'Role Anda hanya memiliki hak akses melihat data (View Only).');
        }

        $this->ensureTiketGangguanColumns();

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? auth()->user()->username ?? 'Teknisi';
        $team = is_array($request->team_teknisi) ? implode(', ', $request->team_teknisi) : ($request->team_teknisi ?? '');
        $noInternet = trim($request->input('nomor_internet', ''));
        $kodeTrx = trim($request->input('kode_trx_tiket', ''));

        try {
            $updateData = [
                'status' => '12', // (KD12) On Schedule / Diproses
            ];

            if (Schema::hasColumn('trx_tiket_gangguan', 'date_schedule')) {
                $updateData['date_schedule'] = $request->date_schedule ?: now()->format('Y-m-d');
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'time_schedule')) {
                $updateData['time_schedule'] = $request->time_schedule ?: '09:00 - 12:00 WIB';
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'team_teknisi')) {
                $updateData['team_teknisi'] = $team;
            }
            if ($request->filled('keluhan') && Schema::hasColumn('trx_tiket_gangguan', 'keluhan')) {
                $updateData['keluhan'] = $request->keluhan;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'date_update')) {
                $updateData['date_update'] = $now;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'user_update')) {
                $updateData['user_update'] = $currentUser;
            }

            $affected = DB::table('trx_tiket_gangguan')
                ->where(function($q) use ($id, $kodeTrx) {
                    $hasClause = false;
                    if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
                        $q->where('tiket', $id);
                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('tiket', $kodeTrx);
                        }
                        $hasClause = true;
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket')) {
                        if ($hasClause) $q->orWhere('id_tiket', $id);
                        else { $q->where('id_tiket', $id); $hasClause = true; }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                        if ($hasClause) $q->orWhere('kode_trx_tiket', $id);
                        else { $q->where('kode_trx_tiket', $id); $hasClause = true; }

                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('kode_trx_tiket', $kodeTrx);
                        }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id')) {
                        if ($hasClause) $q->orWhere('id', $id);
                        else { $q->where('id', $id); $hasClause = true; }
                    }
                })
                ->update($updateData);

            if ($affected === 0 && !empty($noInternet) && $noInternet !== '-') {
                DB::table('trx_tiket_gangguan')
                    ->where('nomor_internet', $noInternet)
                    ->where('status', '11')
                    ->orderBy(Schema::hasColumn('trx_tiket_gangguan', 'date_create') ? 'date_create' : (Schema::hasColumn('trx_tiket_gangguan', 'tiket') ? 'tiket' : 'id_tiket'), 'desc')
                    ->limit(1)
                    ->update($updateData);
            }

            return redirect()->back()->with('success', "Tiket #{$id} berhasil dijadwalkan ke status (KD12) On Schedule!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menjadwalkan tiket: ' . $e->getMessage());
        }
    }

    /**
     * Selesaikan Tiket Gangguan (KD13)
     */
    public function resolveTiketGangguan(Request $request, string $id): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin'])) {
            abort(403, 'Role Anda hanya memiliki hak akses melihat data (View Only).');
        }

        $this->ensureTiketGangguanColumns();

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? auth()->user()->username ?? 'Teknisi';
        $noInternet = trim($request->input('nomor_internet', ''));
        $kodeTrx = trim($request->input('kode_trx_tiket', ''));

        try {
            $updateData = [
                'status' => '13', // (KD13) Success / Selesai
            ];

            $solusiText = $request->solusi ?: 'Kendala gangguan telah diselesaikan oleh teknisi/NOC.';
            
            // Format Technical Report jika ada field teknis tambahan
            $reportExtra = [];
            if ($request->filled('odp_baru')) {
                $reportExtra[] = "ODP: " . trim($request->odp_baru);
            }
            if ($request->filled('redaman')) {
                $reportExtra[] = "Redaman: " . trim($request->redaman) . " dBm";
            }
            if ($request->filled('panjang_kabel')) {
                $reportExtra[] = "Kabel: " . trim($request->panjang_kabel) . "m";
            }
            if ($request->filled('sn_ont')) {
                $reportExtra[] = "SN ONT: " . trim($request->sn_ont);
            }
            if (!empty($reportExtra)) {
                $solusiText = implode(' | ', $reportExtra) . " \nCatatan: " . $solusiText;
            }

            if (Schema::hasColumn('trx_tiket_gangguan', 'solusi')) {
                $updateData['solusi'] = $solusiText;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'penanganan')) {
                $updateData['penanganan'] = $solusiText;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'date_update')) {
                $updateData['date_update'] = $now;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'user_update')) {
                $updateData['user_update'] = $currentUser;
            }

            $affected = DB::table('trx_tiket_gangguan')
                ->where(function($q) use ($id, $kodeTrx) {
                    $hasClause = false;
                    if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
                        $q->where('tiket', $id);
                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('tiket', $kodeTrx);
                        }
                        $hasClause = true;
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket')) {
                        if ($hasClause) $q->orWhere('id_tiket', $id);
                        else { $q->where('id_tiket', $id); $hasClause = true; }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                        if ($hasClause) $q->orWhere('kode_trx_tiket', $id);
                        else { $q->where('kode_trx_tiket', $id); $hasClause = true; }

                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('kode_trx_tiket', $kodeTrx);
                        }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id')) {
                        if ($hasClause) $q->orWhere('id', $id);
                        else { $q->where('id', $id); $hasClause = true; }
                    }
                })
                ->update($updateData);

            if ($affected === 0 && !empty($noInternet) && $noInternet !== '-') {
                DB::table('trx_tiket_gangguan')
                    ->where('nomor_internet', $noInternet)
                    ->whereIn('status', ['11', '12'])
                    ->orderBy(Schema::hasColumn('trx_tiket_gangguan', 'date_create') ? 'date_create' : (Schema::hasColumn('trx_tiket_gangguan', 'tiket') ? 'tiket' : 'id_tiket'), 'desc')
                    ->limit(1)
                    ->update($updateData);
            }

            return redirect()->back()->with('success', "Tiket #{$id} berhasil diselesaikan (KD13 Success)!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan tiket: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Tiket Gangguan (KD14)
     */
    public function cancelTiketGangguan(Request $request, string $id): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin'])) {
            abort(403, 'Role Anda hanya memiliki hak akses melihat data (View Only).');
        }

        $this->ensureTiketGangguanColumns();

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? auth()->user()->username ?? 'Operator';
        $noInternet = trim($request->input('nomor_internet', ''));
        $kodeTrx = trim($request->input('kode_trx_tiket', ''));

        try {
            $updateData = [
                'status' => '14', // (KD14) Canceled
            ];

            $cancelText = $request->note_cancel ? ('Dibatalkan: ' . $request->note_cancel) : 'Dibatalkan oleh operator';
            if (Schema::hasColumn('trx_tiket_gangguan', 'solusi')) {
                $updateData['solusi'] = $cancelText;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'penanganan')) {
                $updateData['penanganan'] = $cancelText;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'date_update')) {
                $updateData['date_update'] = $now;
            }
            if (Schema::hasColumn('trx_tiket_gangguan', 'user_update')) {
                $updateData['user_update'] = $currentUser;
            }

            $affected = DB::table('trx_tiket_gangguan')
                ->where(function($q) use ($id, $kodeTrx) {
                    $hasClause = false;
                    if (Schema::hasColumn('trx_tiket_gangguan', 'tiket')) {
                        $q->where('tiket', $id);
                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('tiket', $kodeTrx);
                        }
                        $hasClause = true;
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id_tiket')) {
                        if ($hasClause) $q->orWhere('id_tiket', $id);
                        else { $q->where('id_tiket', $id); $hasClause = true; }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'kode_trx_tiket')) {
                        if ($hasClause) $q->orWhere('kode_trx_tiket', $id);
                        else { $q->where('kode_trx_tiket', $id); $hasClause = true; }

                        if (!empty($kodeTrx) && $kodeTrx !== '-') {
                            $q->orWhere('kode_trx_tiket', $kodeTrx);
                        }
                    }
                    if (Schema::hasColumn('trx_tiket_gangguan', 'id')) {
                        if ($hasClause) $q->orWhere('id', $id);
                        else { $q->where('id', $id); $hasClause = true; }
                    }
                })
                ->update($updateData);

            if ($affected === 0 && !empty($noInternet) && $noInternet !== '-') {
                DB::table('trx_tiket_gangguan')
                    ->where('nomor_internet', $noInternet)
                    ->whereIn('status', ['11', '12'])
                    ->orderBy(Schema::hasColumn('trx_tiket_gangguan', 'date_create') ? 'date_create' : (Schema::hasColumn('trx_tiket_gangguan', 'tiket') ? 'tiket' : 'id_tiket'), 'desc')
                    ->limit(1)
                    ->update($updateData);
            }

            return redirect()->back()->with('success', "Tiket #{$id} berhasil dibatalkan (KD14 Canceled)!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan tiket: ' . $e->getMessage());
        }
    }

    /**
     * Menu Pendaftaran Pelanggan Baru (Registration)
     * Hanya menampilkan data saat pelanggan baru ditambahkan (dalam proses pendaftaran/instalasi).
     * Pelanggan yang sudah ada/terkonfirmasi (Aktif, Suspend, Terminasi) tampil di menu Pelanggan.
     */
    public function pendaftaran(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        // Status pendaftaran baru yang sedang berjalan (sebelum terkonfirmasi menjadi pelanggan tetap)
        $registrationStatuses = ['11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '19', '19.1'];

        $query = DB::table('view_batchjob')
            ->whereIn('status_reg', $registrationStatuses);

        // Filter: Layanan (kategori bandwith)
        if ($request->filled('layanan')) {
            $query->where('kode_kategori_bandwith', $request->layanan);
        }

        // Filter: Nama Pelanggan
        if ($request->filled('nama')) {
            $query->where('nama_pelanggan', 'like', '%' . $request->nama . '%');
        }

        // Filter: Alamat
        if ($request->filled('alamat')) {
            $query->where(function ($q) use ($request) {
                $q->where('alamat_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('alamat_p', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kelurahan_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kecamatan_pasang', 'like', '%' . $request->alamat . '%');
            });
        }

        // Filter: Status Registrasi Spesifik (dari pilihan status pendaftaran)
        if ($request->filled('status')) {
            $query->where('status_reg', $request->status);
        }

        // Filter: Wilayah
        if ($request->filled('wilayah')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_kota_pasang', 'like', '%' . $request->wilayah . '%')
                  ->orWhere('kode_wilayah_kota_pasang', 'like', '%' . $request->wilayah . '%');
            });
        }

        $registrasi = $query->orderBy('date_create', 'desc')
                            ->paginate($perPage)
                            ->withQueryString();

        // Master data dropdowns
        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->where('disable', 0)->get()
            : collect();

        $paketList = Schema::hasTable('m_bandwith')
            ? DB::table('m_bandwith')->where('disable', 0)->get()
            : collect();

        $bangunanList = Schema::hasTable('m_jns_bangunan')
            ? DB::table('m_jns_bangunan')->where('hide', '0')->get()
            : collect();

        $provinces = Schema::hasTable('m_wilayah')
            ? DB::table('m_wilayah')->select('kode_wilayah_provinsi', 'nama_provinsi')->distinct()->orderBy('nama_provinsi')->get()
            : collect();

        $statusList = Schema::hasTable('m_status_registrasi')
            ? DB::table('m_status_registrasi')->whereIn('status_reg', $registrationStatuses)->get()
            : collect();

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->get()
            : collect();

        // Karyawan Tim Teknisi Lapangan (divisi teknisi / aktif)
        $karyawanTeknisi = DB::table('tb_m_karyawan')
            ->leftJoin('tb_m_jabatan', 'tb_m_karyawan.kode_jabatan', '=', 'tb_m_jabatan.kode_jabatan')
            ->where('tb_m_karyawan.status_aktif', 1)
            ->where(function($q) {
                $q->where('tb_m_jabatan.kode_divisi', 'divisi31955')
                  ->orWhere('tb_m_karyawan.kode_jabatan', 'jabatan3383')
                  ->orWhereNull('tb_m_jabatan.kode_divisi');
            })
            ->select('tb_m_karyawan.kode_karyawan', 'tb_m_karyawan.nama_karyawan')
            ->orderBy('tb_m_karyawan.nama_karyawan', 'asc')
            ->get();

        if ($karyawanTeknisi->isEmpty()) {
            $karyawanTeknisi = DB::table('tb_m_karyawan')
                ->where('status_aktif', 1)
                ->select('kode_karyawan', 'nama_karyawan')
                ->orderBy('nama_karyawan', 'asc')
                ->get();
        }

        // Pilihan Waktu Pekerjaan (Time Job)
        $timeJobs = Schema::hasTable('m_time_job')
            ? DB::table('m_time_job')->where('hide', '0')->get()
            : collect();

        // Daftar Master Perangkat / Barang
        $barangList = Schema::hasTable('view_barang')
            ? DB::table('view_barang')->where('hide', '0')->get()
            : (Schema::hasTable('m_barang') ? DB::table('m_barang')->where('hide', '0')->get() : collect());

        // Master POP/ODN & OLT Slots for Workflow Scheduling
        $pops = Schema::hasTable('m_pop')
            ? DB::table('m_pop')->where('hide', '!=', '1')->orderBy('nama_pop')->get()
            : collect();

        $olts = Schema::hasTable('m_olt')
            ? DB::table('m_olt')->get()
            : collect();

        $indexOltData = $this->getIndexOltSlots();

        // Attach olt safely to $registrasi if missing
        if ($registrasi->isNotEmpty()) {
            $oltMap = DB::table('trx_batchjob_register')
                ->whereIn('nomor_internet', $registrasi->pluck('nomor_internet'))
                ->pluck('olt', 'nomor_internet');
            foreach ($registrasi as $r) {
                $r->olt = $oltMap[$r->nomor_internet] ?? 'O1';
            }
        }

        // Suggested new nomor_internet
        $suggestedNomorInternet = $this->generateNomorInternet();

        return view('teknik.pendaftaran', [
            'user' => $request->user(),
            'registrasi' => $registrasi,
            'layananList' => $layananList,
            'paketList' => $paketList,
            'bangunanList' => $bangunanList,
            'provinces' => $provinces,
            'statusList' => $statusList,
            'wilayahList' => $wilayahList,
            'karyawanTeknisi' => $karyawanTeknisi,
            'timeJobs' => $timeJobs,
            'barangList' => $barangList,
            'suggestedNomorInternet' => $suggestedNomorInternet,
            'pops' => $pops,
            'olts' => $olts,
            'indexOltSlots' => $indexOltData['slots'],
            'occupiedIndexOlts' => $indexOltData['occupied'],
            'allPorts' => $indexOltData['allPorts'],
            'portStats' => $indexOltData['portStats'],
            'filters' => $request->only(['layanan', 'nama', 'alamat', 'status', 'wilayah', 'per_page']),
        ]);
    }

    /**
     * AJAX API: Ambil Detail Billing Registrasi untuk Modal Popup
     */
    public function getBillingDetail(string $nomorInternet): JsonResponse
    {
        $batch = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->first();
        if (!$batch) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        // Check record in trx_billing_registrasi
        $billing = DB::table('trx_billing_registrasi')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        $items = [];
        $subtotal = 0;
        $tax = 0;
        $total = 0;
        $status = 'Draft Billing';
        $method = 'Midtrans';

        if ($billing) {
            $statusRow = DB::table('m_status_bill_reg')->where('status_bill_reg', $billing->status_bill_reg)->first();
            $status = $statusRow ? $statusRow->desc_bill_reg : ($billing->status_bill_reg == '14' ? 'Paid' : 'Draft Billing');
            $method = $billing->merchant_type ?: 'Midtrans';
            $total = (float) $billing->total_reg;

            $details = DB::table('trx_billing_registrasi_detail')
                ->where('kode_billing_registrasi', $billing->kode_billing_registrasi)
                ->get();

            if ($details->count() > 0) {
                foreach ($details as $d) {
                    $items[] = [
                        'komponen' => $d->komponen,
                        'qty' => $d->qty ?: 1,
                        'biaya' => (float) $d->biaya,
                    ];
                    $subtotal += ((float) $d->biaya) * ($d->qty ?: 1);
                }
            } else {
                $items[] = [
                    'komponen' => 'BIAYA REGISTRASI',
                    'qty' => 1,
                    'biaya' => $total,
                ];
                $subtotal = $total;
            }

            $tax = $subtotal * ($billing->ppn ?: 0.11);
        } else {
            // Default from batchjob
            $biayaReg = (float) ($batch->biaya_reg ?: 500000);
            $subtotal = $biayaReg;
            $tax = $biayaReg * 0.11; // 11% tax include
            $total = $biayaReg;

            $items[] = [
                'komponen' => 'BIAYA REGISTRASI',
                'qty' => 1,
                'biaya' => $biayaReg,
            ];
        }

        return response()->json([
            'nama_pelanggan' => $batch->nama_pelanggan,
            'nomor_internet' => $batch->nomor_internet,
            'status' => $status,
            'method' => $method,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'items' => $items,
        ]);
    }

    /**
     * AJAX API: Ambil Detail Pendaftaran Pelanggan untuk Form Edit
     */
    public function getPendaftaranDetail(string $nomorInternet): JsonResponse
    {
        $data = DB::table('view_batchjob')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        // Wilayah KTP hierarchy
        $wilayahKtp = null;
        if ($data->kode_wilayah_kelurahan_ktp) {
            $wilayahKtp = DB::table('m_wilayah')
                ->where('kode_wilayah_kelurahan', $data->kode_wilayah_kelurahan_ktp)
                ->first();
        }

        // Wilayah Pasang hierarchy
        $wilayahPasang = null;
        if ($data->kode_wilayah_kelurahan_pasang) {
            $wilayahPasang = DB::table('m_wilayah')
                ->where('kode_wilayah_kelurahan', $data->kode_wilayah_kelurahan_pasang)
                ->first();
        }

        return response()->json([
            'register' => $data,
            'wilayah_ktp' => $wilayahKtp,
            'wilayah_pasang' => $wilayahPasang,
        ]);
    }

    /**
     * AJAX API: Ambil Detail Data Survey & Instalasi, Tim Teknisi, dan Perangkat Terpasang
     */
    public function getSurveyInstalasiDetail(string $nomorInternet): JsonResponse
    {
        $batch = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->first();
        if (!$batch) {
            return response()->json(['error' => 'Data pendaftaran tidak ditemukan'], 404);
        }

        $instalasi = DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first();

        // Team survey & instalasi
        $teamSurvey = DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '10')
            ->pluck('kode_karyawan')
            ->toArray();

        $teamInstalasi = DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '11')
            ->pluck('kode_karyawan')
            ->toArray();

        // Perangkat yang digunakan
        $perangkat = DB::table('trx_instalasi_barang')
            ->leftJoin('view_barang', 'trx_instalasi_barang.kode_barang', '=', 'view_barang.kode_barang')
            ->where('trx_instalasi_barang.nomor_internet', $nomorInternet)
            ->select(
                'trx_instalasi_barang.kode_inst_barang',
                'trx_instalasi_barang.kode_barang',
                'trx_instalasi_barang.jumlah_barang',
                'view_barang.nama_jns_barang',
                'view_barang.nama_barang',
                'view_barang.tipe_barang',
                'view_barang.satuan'
            )
            ->get()
            ->map(function ($item) {
                $namaLengkap = trim(($item->nama_jns_barang ? $item->nama_jns_barang . ' ' : '') . ($item->nama_barang ? $item->nama_barang . ' ' : '') . ($item->tipe_barang ?: ''));
                return [
                    'kode_barang' => $item->kode_barang,
                    'nama_barang' => $namaLengkap ?: $item->kode_barang,
                    'jumlah' => $item->jumlah_barang,
                    'satuan' => $item->satuan ?: 'UNIT',
                ];
            });

        $trxBatch = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
        if ($trxBatch) {
            $batch->olt = $trxBatch->olt ?? 'O1';
            $batch->media_akses = $trxBatch->media_akses ?? ($batch->media_akses ?? 'FTTH');
            $batch->kode_pop = $trxBatch->kode_pop ?? ($batch->kode_pop ?? '');
            $batch->index_olt = $trxBatch->index_olt ?? ($batch->index_olt ?? '');
        }

        return response()->json([
            'register' => $batch,
            'instalasi' => $instalasi,
            'team_survey' => $teamSurvey,
            'team_instalasi' => $teamInstalasi,
            'perangkat' => $perangkat,
        ]);
    }

    /**
     * Simpan Jadwal Survey (Schedule Survey) -> Status #13
     */
    public function storeScheduleSurvey(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'survey_date_start' => 'required|date',
            'survey_time' => 'required|string',
            'survey_note' => 'required|string',
            'team_survey' => 'nullable|array',
            'foto_mapping' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // Upload foto mapping jika ada
        $fotoMappingName = null;
        if ($request->hasFile('foto_mapping')) {
            $fotoMappingName = 'mapping_' . $nomorInternet . '_' . time() . '.' . $request->file('foto_mapping')->getClientOriginalExtension();
            $request->file('foto_mapping')->move(public_path('uploads/registrasi'), $fotoMappingName);
        }

        // Get names of selected team
        $teamKaryawanIds = $request->input('team_survey', []);
        $teamNames = [];
        if (!empty($teamKaryawanIds)) {
            $teamNames = DB::table('tb_m_karyawan')
                ->whereIn('kode_karyawan', $teamKaryawanIds)
                ->pluck('nama_karyawan')
                ->toArray();
        }
        $teamString = implode(', ', $teamNames);

        // Update/Insert trx_instalasi
        $instalasiData = [
            'survey_date_start' => $request->survey_date_start,
            'survey_time' => $request->survey_time,
            'survey_note' => $request->survey_note,
            'survey_team' => $teamString,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($fotoMappingName) {
            $instalasiData['foto_peta'] = $fotoMappingName;
            $instalasiData['doc_survey'] = $fotoMappingName;
        }

        $existingInstalasi = DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first();
        if ($existingInstalasi) {
            DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->update($instalasiData);
        } else {
            $instalasiData['kode_instalasi'] = 'INS-' . $nomorInternet;
            $instalasiData['nomor_internet'] = $nomorInternet;
            $instalasiData['date_create'] = $now;
            $instalasiData['user_create'] = $currentUser;
            $instalasiData['hide'] = '0';
            DB::table('trx_instalasi')->insert($instalasiData);
        }

        // Simpan team survey ke trx_instalasi_team (kat_team = 10)
        DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '10')
            ->delete();

        if (!empty($teamKaryawanIds)) {
            $karyawans = DB::table('tb_m_karyawan')->whereIn('kode_karyawan', $teamKaryawanIds)->get();
            foreach ($karyawans as $k) {
                DB::table('trx_instalasi_team')->insert([
                    'kode_instalasi_team' => $nomorInternet . '-' . $k->kode_karyawan . '-10',
                    'nomor_internet' => $nomorInternet,
                    'kat_team' => '10',
                    'kode_karyawan' => $k->kode_karyawan,
                    'nama_karyawan' => $k->nama_karyawan,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                    'hide' => '0',
                ]);
            }
        }

        // Update status di trx_batchjob_register -> 13 (Jadwal Survey Terbit)
        $batchjobUpdate = [
            'status_reg' => '13',
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($request->filled('kode_pop')) {
            $batchjobUpdate['kode_pop'] = $request->kode_pop;
        }
        if ($request->filled('media_akses')) {
            $batchjobUpdate['media_akses'] = $request->media_akses;
            if ($request->media_akses === 'PTP FO') {
                $batchjobUpdate['index_olt'] = '';
            } elseif ($request->filled('index_olt')) {
                $batchjobUpdate['index_olt'] = $request->index_olt;
            }
        }
        if ($request->filled('olt')) {
            $batchjobUpdate['olt'] = $request->olt;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($batchjobUpdate);

        // Catat ke trx_batchjob_register_log
        DB::table('trx_batchjob_register_log')->insert([
            'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
            'nomor_internet' => $nomorInternet,
            'status_reg' => '13',
            'date_schedule' => $request->survey_date_start,
            'time_schedule' => $request->survey_time,
            'note_schedule' => $request->survey_note,
            'kat_log' => '10',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->route('teknik.pendaftaran')->with('success', "Jadwal Survey An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!");
    }

    /**
     * Simpan Laporan Hasil Survey (Report Survey) -> Status #16 / #13.1 / #14 + Simpan Perangkat
     */
    public function storeReportSurvey(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'is_reschedule' => 'nullable|in:1,0,true,false',
            'survey_date_finish' => 'nullable|date',
            'survey_note_finish' => 'nullable|string',
            'bisa_pasang' => 'nullable|in:YA,TIDAK',
            'team_survey' => 'nullable|array',
            'perangkat' => 'nullable|array',
            'update_foto_mapping' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';
        $isReschedule = filter_var($request->is_reschedule, FILTER_VALIDATE_BOOLEAN);

        // Upload foto mapping jika ada
        $fotoMappingName = null;
        if ($request->hasFile('update_foto_mapping')) {
            $fotoMappingName = 'mapping_' . $nomorInternet . '_' . time() . '.' . $request->file('update_foto_mapping')->getClientOriginalExtension();
            $request->file('update_foto_mapping')->move(public_path('uploads/registrasi'), $fotoMappingName);
        }

        // Get names of selected team
        $teamKaryawanIds = $request->input('team_survey', []);
        $teamNames = [];
        if (!empty($teamKaryawanIds)) {
            $teamNames = DB::table('tb_m_karyawan')
                ->whereIn('kode_karyawan', $teamKaryawanIds)
                ->pluck('nama_karyawan')
                ->toArray();
        }
        $teamString = implode(', ', $teamNames);

        // Update/Insert team survey
        DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '10')
            ->delete();

        if (!empty($teamKaryawanIds)) {
            $karyawans = DB::table('tb_m_karyawan')->whereIn('kode_karyawan', $teamKaryawanIds)->get();
            foreach ($karyawans as $k) {
                DB::table('trx_instalasi_team')->insert([
                    'kode_instalasi_team' => $nomorInternet . '-' . $k->kode_karyawan . '-10',
                    'nomor_internet' => $nomorInternet,
                    'kat_team' => '10',
                    'kode_karyawan' => $k->kode_karyawan,
                    'nama_karyawan' => $k->nama_karyawan,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                    'hide' => '0',
                ]);
            }
        }

        // Simpan perangkat yang digunakan
        $perangkatList = $request->input('perangkat', []);
        DB::table('trx_instalasi_barang')->where('nomor_internet', $nomorInternet)->delete();
        if (!empty($perangkatList)) {
            foreach ($perangkatList as $p) {
                if (!empty($p['kode_barang']) && !empty($p['jumlah'])) {
                    DB::table('trx_instalasi_barang')->insert([
                        'kode_inst_barang' => $nomorInternet . '-' . $p['kode_barang'],
                        'nomor_internet' => $nomorInternet,
                        'kode_barang' => $p['kode_barang'],
                        'jumlah_barang' => (int) $p['jumlah'],
                        'status_instalasi_barang' => '11',
                        'note_instalasi_barang' => null,
                        'date_create' => $now,
                        'user_create' => $currentUser,
                        'date_update' => $now,
                        'user_update' => $currentUser,
                        'hide' => '0',
                    ]);
                }
            }
        }

        // Tentukan Status Registrasi
        if ($isReschedule) {
            $newStatus = '13.1'; // Reschedule Survey
            $msg = "Permintaan Jadwal Ulang Survey An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!";
        } elseif ($request->bisa_pasang === 'TIDAK') {
            $newStatus = '14'; // Tidak Tercover Jaringan
            $msg = "Hasil Survey An/ {$request->nama_pelanggan} ({$nomorInternet}): Tidak Tercover Jaringan.";
        } else {
            $newStatus = '16'; // Selesai Survey
            $msg = "Laporan Selesai Survey An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!";
        }

        // Update trx_instalasi
        $instalasiPayload = [
            'survey_date_finish' => $request->survey_date_finish ?: now()->format('Y-m-d'),
            'survey_note_finish' => $request->survey_note_finish ?: '-',
            'survey_team' => $teamString,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($fotoMappingName) {
            $instalasiPayload['foto_peta'] = $fotoMappingName;
            $instalasiPayload['doc_survey'] = $fotoMappingName;
        }
        if ($newStatus === '14') {
            $instalasiPayload['batal_pasang_note'] = $request->survey_note_finish ?: 'Tidak tercover jaringan';
        }

        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                array_merge($instalasiPayload, [
                    'kode_instalasi' => 'INS-' . $nomorInternet,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'hide' => '0',
                ])
            );

        // Update status trx_batchjob_register
        $batchjobUpdate = [
            'status_reg' => $newStatus,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($request->filled('kode_pop')) {
            $batchjobUpdate['kode_pop'] = $request->kode_pop;
        }
        if ($request->filled('media_akses')) {
            $batchjobUpdate['media_akses'] = $request->media_akses;
            if ($request->media_akses === 'PTP FO') {
                $batchjobUpdate['index_olt'] = '';
            } elseif ($request->filled('index_olt')) {
                $batchjobUpdate['index_olt'] = $request->index_olt;
            }
        }
        if ($request->filled('olt')) {
            $batchjobUpdate['olt'] = $request->olt;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($batchjobUpdate);

        // Catat ke trx_batchjob_register_log
        DB::table('trx_batchjob_register_log')->insert([
            'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
            'nomor_internet' => $nomorInternet,
            'status_reg' => $newStatus,
            'date_schedule' => $request->survey_date_finish,
            'time_schedule' => null,
            'note_schedule' => $request->survey_note_finish,
            'kat_log' => '10',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->route('teknik.pendaftaran')->with('success', $msg);
    }

    /**
     * Simpan Jadwal Instalasi (Schedule Instalasi) -> Status #17
     */
    public function storeScheduleInstalasi(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'instalasi_date_start' => 'required|date',
            'instalasi_time' => 'required|string',
            'instalasi_note' => 'required|string',
            'team_instalasi' => 'nullable|array',
            'perangkat' => 'nullable|array',
            'update_foto_mapping' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // Upload foto mapping jika ada
        $fotoMappingName = null;
        if ($request->hasFile('update_foto_mapping')) {
            $fotoMappingName = 'mapping_' . $nomorInternet . '_' . time() . '.' . $request->file('update_foto_mapping')->getClientOriginalExtension();
            $request->file('update_foto_mapping')->move(public_path('uploads/registrasi'), $fotoMappingName);
        }

        // Get names of selected team
        $teamKaryawanIds = $request->input('team_instalasi', []);
        $teamNames = [];
        if (!empty($teamKaryawanIds)) {
            $teamNames = DB::table('tb_m_karyawan')
                ->whereIn('kode_karyawan', $teamKaryawanIds)
                ->pluck('nama_karyawan')
                ->toArray();
        }
        $teamString = implode(', ', $teamNames);

        // Update/Insert team instalasi
        DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '11')
            ->delete();

        if (!empty($teamKaryawanIds)) {
            $karyawans = DB::table('tb_m_karyawan')->whereIn('kode_karyawan', $teamKaryawanIds)->get();
            foreach ($karyawans as $k) {
                DB::table('trx_instalasi_team')->insert([
                    'kode_instalasi_team' => $nomorInternet . '-' . $k->kode_karyawan . '-11',
                    'nomor_internet' => $nomorInternet,
                    'kat_team' => '11',
                    'kode_karyawan' => $k->kode_karyawan,
                    'nama_karyawan' => $k->nama_karyawan,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                    'hide' => '0',
                ]);
            }
        }

        // Simpan / update perangkat
        $perangkatList = $request->input('perangkat', []);
        if (!empty($perangkatList)) {
            DB::table('trx_instalasi_barang')->where('nomor_internet', $nomorInternet)->delete();
            foreach ($perangkatList as $p) {
                if (!empty($p['kode_barang']) && !empty($p['jumlah'])) {
                    DB::table('trx_instalasi_barang')->insert([
                        'kode_inst_barang' => $nomorInternet . '-' . $p['kode_barang'],
                        'nomor_internet' => $nomorInternet,
                        'kode_barang' => $p['kode_barang'],
                        'jumlah_barang' => (int) $p['jumlah'],
                        'status_instalasi_barang' => '11',
                        'note_instalasi_barang' => null,
                        'date_create' => $now,
                        'user_create' => $currentUser,
                        'date_update' => $now,
                        'user_update' => $currentUser,
                        'hide' => '0',
                    ]);
                }
            }
        }

        // Update trx_instalasi
        $instalasiPayload = [
            'instalasi_date_start' => $request->instalasi_date_start,
            'instalasi_time' => $request->instalasi_time,
            'instalasi_note' => $request->instalasi_note,
            'instalasi_team' => $teamString,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($fotoMappingName) {
            $instalasiPayload['foto_peta'] = $fotoMappingName;
            $instalasiPayload['doc_instalasi'] = $fotoMappingName;
        }

        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                array_merge($instalasiPayload, [
                    'kode_instalasi' => 'INS-' . $nomorInternet,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'hide' => '0',
                ])
            );

        // Update status_reg -> 17 (Jadwal Instalasi Terbit)
        $batchjobUpdate = [
            'status_reg' => '17',
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($request->filled('kode_pop')) {
            $batchjobUpdate['kode_pop'] = $request->kode_pop;
        }
        if ($request->filled('media_akses')) {
            $batchjobUpdate['media_akses'] = $request->media_akses;
            if ($request->media_akses === 'PTP FO') {
                $batchjobUpdate['index_olt'] = '';
            } elseif ($request->filled('index_olt')) {
                $batchjobUpdate['index_olt'] = $request->index_olt;
            }
        }
        if ($request->filled('olt')) {
            $batchjobUpdate['olt'] = $request->olt;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($batchjobUpdate);

        // Catat ke trx_batchjob_register_log
        DB::table('trx_batchjob_register_log')->insert([
            'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
            'nomor_internet' => $nomorInternet,
            'status_reg' => '17',
            'date_schedule' => $request->instalasi_date_start,
            'time_schedule' => $request->instalasi_time,
            'note_schedule' => $request->instalasi_note,
            'kat_log' => '11',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->route('teknik.pendaftaran')->with('success', "Jadwal Instalasi An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!");
    }

    /**
     * Simpan Laporan Hasil Instalasi (Report Instalasi) -> Status #18 / #17.1 + Finalisasi Perangkat
     */
    public function storeReportInstalasi(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'is_reschedule' => 'nullable|in:1,0,true,false',
            'instalasi_date_finish' => 'nullable|date',
            'instalasi_note_finish' => 'nullable|string',
            'team_instalasi' => 'nullable|array',
            'perangkat' => 'nullable|array',
            'update_foto_mapping' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';
        $isReschedule = filter_var($request->is_reschedule, FILTER_VALIDATE_BOOLEAN);

        // Upload foto mapping/instalasi jika ada
        $fotoMappingName = null;
        if ($request->hasFile('update_foto_mapping')) {
            $fotoMappingName = 'instalasi_' . $nomorInternet . '_' . time() . '.' . $request->file('update_foto_mapping')->getClientOriginalExtension();
            $request->file('update_foto_mapping')->move(public_path('uploads/registrasi'), $fotoMappingName);
        }

        // Get names of selected team
        $teamKaryawanIds = $request->input('team_instalasi', []);
        $teamNames = [];
        if (!empty($teamKaryawanIds)) {
            $teamNames = DB::table('tb_m_karyawan')
                ->whereIn('kode_karyawan', $teamKaryawanIds)
                ->pluck('nama_karyawan')
                ->toArray();
        }
        $teamString = implode(', ', $teamNames);

        // Update team instalasi
        DB::table('trx_instalasi_team')
            ->where('nomor_internet', $nomorInternet)
            ->where('kat_team', '11')
            ->delete();

        if (!empty($teamKaryawanIds)) {
            $karyawans = DB::table('tb_m_karyawan')->whereIn('kode_karyawan', $teamKaryawanIds)->get();
            foreach ($karyawans as $k) {
                DB::table('trx_instalasi_team')->insert([
                    'kode_instalasi_team' => $nomorInternet . '-' . $k->kode_karyawan . '-11',
                    'nomor_internet' => $nomorInternet,
                    'kat_team' => '11',
                    'kode_karyawan' => $k->kode_karyawan,
                    'nama_karyawan' => $k->nama_karyawan,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                    'hide' => '0',
                ]);
            }
        }

        // Simpan / update perangkat terpasang
        $perangkatList = $request->input('perangkat', []);
        DB::table('trx_instalasi_barang')->where('nomor_internet', $nomorInternet)->delete();
        if (!empty($perangkatList)) {
            foreach ($perangkatList as $p) {
                if (!empty($p['kode_barang']) && !empty($p['jumlah'])) {
                    DB::table('trx_instalasi_barang')->insert([
                        'kode_inst_barang' => $nomorInternet . '-' . $p['kode_barang'],
                        'nomor_internet' => $nomorInternet,
                        'kode_barang' => $p['kode_barang'],
                        'jumlah_barang' => (int) $p['jumlah'],
                        'status_instalasi_barang' => '11',
                        'note_instalasi_barang' => null,
                        'date_create' => $now,
                        'user_create' => $currentUser,
                        'date_update' => $now,
                        'user_update' => $currentUser,
                        'hide' => '0',
                    ]);
                }
            }
        }

        // Status registrasi
        if ($isReschedule) {
            $newStatus = '17.1'; // Reschedule Instalasi
            $msg = "Permintaan Jadwal Ulang Instalasi An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!";
        } else {
            $newStatus = '18'; // Selesai Instalasi
            $msg = "Laporan Selesai Instalasi An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil disimpan!";
        }

        // Update trx_instalasi
        $instalasiPayload = [
            'instalasi_date_finish' => $request->instalasi_date_finish ?: now()->format('Y-m-d'),
            'instalasi_note_finish' => $request->instalasi_note_finish ?: '-',
            'instalasi_team' => $teamString,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($fotoMappingName) {
            $instalasiPayload['foto_peta'] = $fotoMappingName;
            $instalasiPayload['doc_instalasi'] = $fotoMappingName;
        }

        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                array_merge($instalasiPayload, [
                    'kode_instalasi' => 'INS-' . $nomorInternet,
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'hide' => '0',
                ])
            );

        // Update status di trx_batchjob_register
        $batchjobUpdate = [
            'status_reg' => $newStatus,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($request->filled('kode_pop')) {
            $batchjobUpdate['kode_pop'] = $request->kode_pop;
        }
        if ($request->filled('media_akses')) {
            $batchjobUpdate['media_akses'] = $request->media_akses;
            if ($request->media_akses === 'PTP FO') {
                $batchjobUpdate['index_olt'] = '';
            } elseif ($request->filled('index_olt')) {
                $batchjobUpdate['index_olt'] = $request->index_olt;
            }
        }
        if ($request->filled('olt')) {
            $batchjobUpdate['olt'] = $request->olt;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($batchjobUpdate);

        // Catat log
        DB::table('trx_batchjob_register_log')->insert([
            'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
            'nomor_internet' => $nomorInternet,
            'status_reg' => $newStatus,
            'date_schedule' => $request->instalasi_date_finish,
            'time_schedule' => null,
            'note_schedule' => $request->instalasi_note_finish,
            'kat_log' => '11',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->route('teknik.pendaftaran')->with('success', $msg);
    }

    /**
     * Kirim Request Aktivasi ke NOC -> Status #19
     */
    public function requestAktivasiNoc(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'catatan_aktivasi' => 'nullable|string',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // Update status_reg -> 19 (Jadwal Aktivasi Terbit / Siap Aktivasi NOC)
        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update([
                'status_reg' => '19',
                'date_update' => $now,
                'user_update' => $currentUser,
            ]);

        // Update trx_instalasi
        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                [
                    'kode_instalasi' => 'INS-' . $nomorInternet,
                    'aktivasi_date_start' => now()->format('Y-m-d'),
                    'aktivasi_time' => now()->format('H:i:s'),
                    'aktivasi_note' => $request->catatan_aktivasi ?: 'Request aktivasi layanan dari tim Teknik',
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]
            );

        // Catat log
        DB::table('trx_batchjob_register_log')->insert([
            'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
            'nomor_internet' => $nomorInternet,
            'status_reg' => '19',
            'date_schedule' => now()->format('Y-m-d'),
            'time_schedule' => now()->format('H:i:s'),
            'note_schedule' => $request->catatan_aktivasi ?: 'Permintaan aktivasi ke NOC',
            'kat_log' => '22',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->route('teknik.pendaftaran')->with('success', "Permintaan aktivasi jaringan An/ {$request->nama_pelanggan} ({$nomorInternet}) berhasil dikirim ke NOC!");
    }

    /**
     * Export Data Pendaftaran ke Excel / CSV
     */
    public function exportPendaftaran(Request $request): StreamedResponse
    {
        $registrationStatuses = ['11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '19', '19.1'];
        $query = DB::table('view_batchjob')->whereIn('status_reg', $registrationStatuses);

        if ($request->filled('layanan')) {
            $query->where('kode_kategori_bandwith', $request->layanan);
        }

        if ($request->filled('nama')) {
            $query->where('nama_pelanggan', 'like', '%' . $request->nama . '%');
        }

        if ($request->filled('alamat')) {
            $query->where(function ($q) use ($request) {
                $q->where('alamat_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('alamat_p', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kelurahan_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kecamatan_pasang', 'like', '%' . $request->alamat . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status_reg', $request->status);
        }

        if ($request->filled('wilayah')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_kota_pasang', 'like', '%' . $request->wilayah . '%')
                  ->orWhere('kode_wilayah_kota_pasang', 'like', '%' . $request->wilayah . '%');
            });
        }

        $filename = 'Pendaftaran_Baru_IMS_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Nomor Internet',
                'Nama Pelanggan',
                'Jenis Kelamin',
                'NIK Penduduk',
                'No HP',
                'Layanan',
                'Bandwidth',
                'Status Pendaftaran',
                'Jenis Bangunan',
                'Alamat Pemasangan',
                'Kota / Kabupaten',
                'Sales',
                'Tanggal SO / Registrasi',
            ]);

            $no = 1;
            $query->orderBy('date_create', 'desc')->chunk(500, function ($rows) use ($handle, &$no) {
                foreach ($rows as $r) {
                    $gender = $r->jenis_kelamin == 1 ? 'Laki-Laki' : ($r->jenis_kelamin == 2 ? 'Perempuan' : '-');
                    fputcsv($handle, [
                        $no++,
                        "\t" . ($r->nomor_internet ?: '-'),
                        $r->nama_pelanggan ?: '-',
                        $gender,
                        "\t" . ($r->nik_penduduk ?: '-'),
                        "\t" . ($r->nomor_hp ?: '-'),
                        $r->nama_kategori_bandwith ?: ($r->alias_nama_kategori ?: '-'),
                        $r->nominal_bandwith ? $r->nominal_bandwith . ' Mbps' : '-',
                        $r->desc_registrasi ?: 'Pendaftaran #' . $r->status_reg,
                        $r->jenis_bangunan ?: '-',
                        $r->alamat_p ?: ($r->alamat_pasang ?: '-'),
                        $r->nama_kota_pasang ?: '-',
                        $r->nama_sales ?: '-',
                        $r->date_create ?: '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Store Pendaftaran Pelanggan Baru (+ Registrasi)
     */
    public function storePendaftaran(Request $request): RedirectResponse
    {
        $request->validate([
            'nik_penduduk' => 'required|string|max:50',
            'nama_pelanggan' => 'required|string|max:200',
            'jenis_kelamin' => 'required|in:1,2',
            'tanggal_lahir' => 'nullable|date',
            'email' => 'nullable|email|max:100',
            'nomor_hp' => 'required|string|max:20',
            'nomor_hp_2' => 'nullable|string|max:20',
            'jenis_bangunan' => 'required|string',
            'nomor_bangunan' => 'nullable|string|max:10',
            'kode_kategori_bandwith' => 'required|string',
            'kode_bandwith' => 'required|string',
            'group_layanan' => 'nullable|string',
            'alamat_pasang' => 'required|string',
            'kode_wilayah_kelurahan_pasang' => 'nullable|string',
            'rt_pasang' => 'nullable|string|max:3',
            'rw_pasang' => 'nullable|string|max:3',
            'nama_sales' => 'required|string|max:50',
            'foto_ktp' => 'nullable|image|max:4096',
            'foto_rumah' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $this->generateNomorInternet();

        // Upload files if provided
        $fotoKtpName = null;
        $fotoRumahName = null;

        if ($request->hasFile('foto_ktp')) {
            $fotoKtpName = 'ktp_' . $nomorInternet . '_' . time() . '.' . $request->file('foto_ktp')->getClientOriginalExtension();
            $request->file('foto_ktp')->move(public_path('uploads/registrasi'), $fotoKtpName);
        }

        if ($request->hasFile('foto_rumah')) {
            $fotoRumahName = 'rumah_' . $nomorInternet . '_' . time() . '.' . $request->file('foto_rumah')->getClientOriginalExtension();
            $request->file('foto_rumah')->move(public_path('uploads/registrasi'), $fotoRumahName);
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // 1. Simpan / Perbarui m_pelanggan
        DB::table('m_pelanggan')->updateOrInsert(
            ['nik_penduduk' => $request->nik_penduduk],
            [
                'nama_penduduk' => $request->nama_pelanggan,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir ?: '1990-01-01',
                'pic' => $request->is_corporate ? $request->pic : null,
                'email' => $request->email ?: '-',
                'nomor_hp' => $request->nomor_hp,
                'nomor_hp_2' => $request->nomor_hp_2 ?: null,
                'kode_wilayah_kelurahan_ktp' => $request->kode_wilayah_kelurahan_ktp ?: $request->kode_wilayah_kelurahan_pasang,
                'rt_ktp' => str_pad($request->rt_ktp ?: '00', 2, '0', STR_PAD_LEFT),
                'rw_ktp' => str_pad($request->rw_ktp ?: '00', 2, '0', STR_PAD_LEFT),
                'alamat_ktp' => $request->alamat_ktp ?: $request->alamat_pasang,
                'user_create' => $currentUser,
                'date_create' => $now,
                'date_update' => $now,
                'user_update' => $currentUser,
                'hide' => '0',
            ]
        );

        // 2. Simpan ke trx_batchjob_register
        DB::table('trx_batchjob_register')->insert([
            'nomor_internet' => $nomorInternet,
            'nik_penduduk' => $request->nik_penduduk,
            'nama_pelanggan' => $request->nama_pelanggan,
            'rt_pasang' => str_pad($request->rt_pasang ?: '00', 2, '0', STR_PAD_LEFT),
            'rw_pasang' => str_pad($request->rw_pasang ?: '00', 2, '0', STR_PAD_LEFT),
            'nomor_bangunan' => $request->nomor_bangunan ?: '00',
            'alamat_pasang' => $request->alamat_pasang,
            'kode_wilayah_kelurahan_pasang' => $request->kode_wilayah_kelurahan_pasang,
            'jenis_bangunan' => $request->jenis_bangunan,
            'lon_lat' => $request->lon_lat ?: null,
            'loc_maps' => $request->loc_maps ?: null,
            'note_request' => $request->note_request ?: null,
            'kode_bandwith' => $request->kode_bandwith,
            'kode_pop' => $request->kode_pop ?: 'POP001',
            'status_reg' => '11', // Menunggu verifikasi
            'group_layanan' => $request->group_layanan ?: 'MEDIANET',
            'nama_sales' => $request->nama_sales,
            'islock' => '0',
            'prorate' => '0',
            'hide' => '0',
            'user_create' => $currentUser,
            'date_create' => $now,
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        // 3. Inisialisasi trx_instalasi (menyimpan foto KTP & foto Rumah)
        DB::table('trx_instalasi')->updateOrInsert(
            ['nomor_internet' => $nomorInternet],
            [
                'kode_instalasi' => 'INS-' . $nomorInternet,
                'foto_ktp' => $fotoKtpName,
                'foto_rumah' => $fotoRumahName,
                'user_create' => $currentUser,
                'date_create' => $now,
                'date_update' => $now,
                'user_update' => $currentUser,
                'hide' => '0',
            ]
        );

        return redirect()->route('teknik.pendaftaran')
            ->with('success', "Pendaftaran pelanggan baru '{$request->nama_pelanggan}' dengan Nomor Internet {$nomorInternet} berhasil disimpan!")
            ->with('nomor_internet_baru', $nomorInternet)
            ->with('nama_pelanggan_baru', $request->nama_pelanggan);
    }

    /**
     * Update Data Pendaftaran Pelanggan (Form Edit Registrasi)
     */
    public function updatePendaftaran(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'nik_penduduk' => 'required|string|max:50',
            'nama_pelanggan' => 'required|string|max:200',
            'jenis_kelamin' => 'required|in:1,2',
            'tanggal_lahir' => 'nullable|date',
            'email' => 'nullable|email|max:100',
            'nomor_hp' => 'required|string|max:20',
            'nomor_hp_2' => 'nullable|string|max:20',
            'jenis_bangunan' => 'required|string',
            'nomor_bangunan' => 'nullable|string|max:10',
            'kode_kategori_bandwith' => 'required|string',
            'kode_bandwith' => 'required|string',
            'group_layanan' => 'nullable|string',
            'alamat_pasang' => 'required|string',
            'kode_wilayah_kelurahan_pasang' => 'nullable|string',
            'rt_pasang' => 'nullable|string|max:3',
            'rw_pasang' => 'nullable|string|max:3',
            'nama_sales' => 'required|string|max:50',
            'foto_ktp' => 'nullable|image|max:4096',
            'foto_rumah' => 'nullable|image|max:4096',
        ]);

        $nomorInternet = $request->nomor_internet;
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // 1. Update m_pelanggan
        DB::table('m_pelanggan')->updateOrInsert(
            ['nik_penduduk' => $request->nik_penduduk],
            [
                'nama_penduduk' => $request->nama_pelanggan,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir ?: '1990-01-01',
                'pic' => $request->is_corporate ? $request->pic : null,
                'email' => $request->email ?: '-',
                'nomor_hp' => $request->nomor_hp,
                'nomor_hp_2' => $request->nomor_hp_2 ?: null,
                'kode_wilayah_kelurahan_ktp' => $request->kode_wilayah_kelurahan_ktp ?: $request->kode_wilayah_kelurahan_pasang,
                'rt_ktp' => str_pad($request->rt_ktp ?: '00', 2, '0', STR_PAD_LEFT),
                'rw_ktp' => str_pad($request->rw_ktp ?: '00', 2, '0', STR_PAD_LEFT),
                'alamat_ktp' => $request->alamat_ktp ?: $request->alamat_pasang,
                'date_update' => $now,
                'user_update' => $currentUser,
            ]
        );

        // 2. Prepare update payload for trx_batchjob_register
        $updatePayload = [
            'nik_penduduk' => $request->nik_penduduk,
            'nama_pelanggan' => $request->nama_pelanggan,
            'rt_pasang' => str_pad($request->rt_pasang ?: '00', 2, '0', STR_PAD_LEFT),
            'rw_pasang' => str_pad($request->rw_pasang ?: '00', 2, '0', STR_PAD_LEFT),
            'nomor_bangunan' => $request->nomor_bangunan ?: '00',
            'alamat_pasang' => $request->alamat_pasang,
            'kode_wilayah_kelurahan_pasang' => $request->kode_wilayah_kelurahan_pasang,
            'jenis_bangunan' => $request->jenis_bangunan,
            'lon_lat' => $request->lon_lat ?: null,
            'loc_maps' => $request->loc_maps ?: null,
            'note_request' => $request->note_request ?: null,
            'kode_bandwith' => $request->kode_bandwith,
            'group_layanan' => $request->group_layanan ?: 'MEDIANET',
            'nama_sales' => $request->nama_sales,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        $instalasiPayload = [
            'kode_instalasi' => 'INS-' . $nomorInternet,
            'date_update' => $now,
            'user_update' => $currentUser,
            'hide' => '0',
        ];

        if ($request->hasFile('foto_ktp')) {
            $fotoKtpName = 'ktp_' . $nomorInternet . '_' . time() . '.' . $request->file('foto_ktp')->getClientOriginalExtension();
            $request->file('foto_ktp')->move(public_path('uploads/registrasi'), $fotoKtpName);
            $instalasiPayload['foto_ktp'] = $fotoKtpName;
        }

        if ($request->hasFile('foto_rumah')) {
            $fotoRumahName = 'rumah_' . $nomorInternet . '_' . time() . '.' . $request->file('foto_rumah')->getClientOriginalExtension();
            $request->file('foto_rumah')->move(public_path('uploads/registrasi'), $fotoRumahName);
            $instalasiPayload['foto_rumah'] = $fotoRumahName;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($updatePayload);

        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                $instalasiPayload
            );

        return redirect()->route('teknik.pendaftaran')->with('success', "Data pendaftaran pelanggan '{$request->nama_pelanggan}' ({$nomorInternet}) berhasil diperbarui!");
    }

    /**
     * Update Status Batal Pasang Pendaftaran
     */
    public function batalPasang(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'kategori_batal' => 'required|in:14,15',
            'alasan_batal' => 'required|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        $statusDesc = $request->kategori_batal == '14' ? 'Tidak Terjangkau Jaringan' : 'Permintaan dari User (Batal Pasang)';

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $request->nomor_internet)
            ->update([
                'status_reg' => $request->kategori_batal,
                'note_request' => $request->alasan_batal,
                'date_update' => $now,
                'user_update' => $currentUser,
            ]);

        return redirect()->route('teknik.pendaftaran')->with('success', "Pemasangan An/ {$request->nama_pelanggan} ({$request->nomor_internet}) berhasil dibatalkan dengan alasan: {$statusDesc}.");
    }

    /**
     * Generate Nomor Internet Baru Secara Otomatis
     */
    private function generateNomorInternet(): string
    {
        $lastRow = DB::table('trx_batchjob_register')
            ->where('nomor_internet', 'like', '%' . date('y'))
            ->orWhere('nomor_internet', 'regexp', '^[0-9]+$')
            ->orderByRaw('CAST(nomor_internet AS UNSIGNED) DESC')
            ->first();

        if ($lastRow && is_numeric($lastRow->nomor_internet)) {
            $next = ((int) $lastRow->nomor_internet) + 1;
            return (string) $next;
        }

        return '1' . str_pad((string) (DB::table('trx_batchjob_register')->count() + 1), 6, '0', STR_PAD_LEFT) . date('y');
    }

    /**
     * AJAX API: Ambil Daftar Kota Berdasarkan Kode Provinsi
     */
    public function getKota(string $provinsi): JsonResponse
    {
        $kota = DB::table('m_wilayah')
            ->where('kode_wilayah_provinsi', $provinsi)
            ->select('kode_wilayah_kota', 'nama_kota')
            ->distinct()
            ->orderBy('nama_kota')
            ->get();

        return response()->json($kota);
    }

    /**
     * AJAX API: Ambil Daftar Kecamatan Berdasarkan Kode Kota
     */
    public function getKecamatan(string $kota): JsonResponse
    {
        $kecamatan = DB::table('m_wilayah')
            ->where('kode_wilayah_kota', $kota)
            ->select('kode_wilayah_kecamatan', 'nama_kecamatan')
            ->distinct()
            ->orderBy('nama_kecamatan')
            ->get();

        return response()->json($kecamatan);
    }

    /**
     * AJAX API: Ambil Daftar Kelurahan Berdasarkan Kode Kecamatan
     */
    public function getKelurahan(string $kecamatan): JsonResponse
    {
        $kelurahan = DB::table('m_wilayah')
            ->where('kode_wilayah_kecamatan', $kecamatan)
            ->select('kode_wilayah_kelurahan', 'nama_kelurahan')
            ->distinct()
            ->orderBy('nama_kelurahan')
            ->get();

        return response()->json($kelurahan);
    }

    /**
     * AJAX API: Ambil Daftar Paket Bandwidth Berdasarkan Kategori Layanan
     */
    public function getPaket(string $kategori): JsonResponse
    {
        $paket = DB::table('m_bandwith')
            ->where('kode_kategori_bandwith', $kategori)
            ->where('disable', 0)
            ->orderBy('nominal_bandwith')
            ->get();

        return response()->json($paket);
    }

    /**
     * Menu Data Pelanggan (Pelanggan Terkonfirmasi & Eksisting)
     * Menampilkan semua pelanggan yang sudah terkonfirmasi / aktif / suspend / terminasi
     * beserta rincian pengelompokan kategori bandwidth untuk Aktif, Terminasi, dan Suspend.
     */
    public function pelanggan(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        // Pelanggan yang sudah terkonfirmasi / aktif / suspend / terminasi
        $confirmedStatuses = ['20', '21', '21.1', '23', '23.1'];

        $query = DB::table('view_batchjob');

        if ($request->filled('status')) {
            if ($request->status == '21') {
                $query->whereIn('status_reg', ['21', '21.1']);
            } elseif ($request->status == '23') {
                $query->whereIn('status_reg', ['23', '23.1']);
            } else {
                $query->where('status_reg', $request->status);
            }
        } else {
            // Default menampilkan seluruh pelanggan terkonfirmasi
            $query->whereIn('status_reg', $confirmedStatuses);
        }

        // Filter: Layanan
        if ($request->filled('layanan')) {
            $query->where('kode_kategori_bandwith', $request->layanan);
        }

        // Filter: Nomor Internet / Nama Pelanggan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nomor_internet', 'like', '%' . $search . '%')
                  ->orWhere('nik_penduduk', 'like', '%' . $search . '%')
                  ->orWhere('nomor_hp', 'like', '%' . $search . '%');
            });
        }

        // Filter: Alamat
        if ($request->filled('alamat')) {
            $query->where(function ($q) use ($request) {
                $q->where('alamat_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('alamat_p', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kelurahan_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kecamatan_pasang', 'like', '%' . $request->alamat . '%');
            });
        }

        // Filter: Wilayah
        if ($request->filled('wilayah')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_kota_pasang', 'like', '%' . $request->wilayah . '%')
                  ->orWhere('kode_wilayah_kota_pasang', 'like', '%' . $request->wilayah . '%');
            });
        }

        $pelanggan = $query->orderBy('nomor_internet', 'desc')
                           ->paginate($perPage)
                           ->withQueryString();

        // Master data dropdowns
        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->where('disable', 0)->get()
            : collect();

        $statusList = Schema::hasTable('m_status_registrasi')
            ? DB::table('m_status_registrasi')->whereIn('status_reg', $confirmedStatuses)->get()
            : collect();

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->get()
            : collect();

        // Detailed Bandwidth Breakdown Counts
        $rawBandwidthCounts = DB::table('view_batchjob')
            ->select('status_reg', 'kode_kategori_bandwith', DB::raw('count(*) as total'))
            ->whereIn('status_reg', $confirmedStatuses)
            ->groupBy('status_reg', 'kode_kategori_bandwith')
            ->get();

        $bwCounts = [
            'aktif' => [],
            'terminasi' => [],
            'suspend' => [],
            'total_aktif' => 0,
            'total_terminasi' => 0,
            'total_suspend' => 0,
        ];

        foreach ($layananList as $l) {
            $bwCounts['aktif'][$l->kode_kategori_bandwith] = 0;
            $bwCounts['terminasi'][$l->kode_kategori_bandwith] = 0;
            $bwCounts['suspend'][$l->kode_kategori_bandwith] = 0;
        }

        foreach ($rawBandwidthCounts as $row) {
            $kat = $row->kode_kategori_bandwith;
            if ($row->status_reg == '20') {
                $bwCounts['aktif'][$kat] = ($bwCounts['aktif'][$kat] ?? 0) + $row->total;
                $bwCounts['total_aktif'] += $row->total;
            } elseif (in_array($row->status_reg, ['23', '23.1'])) {
                $bwCounts['terminasi'][$kat] = ($bwCounts['terminasi'][$kat] ?? 0) + $row->total;
                $bwCounts['total_terminasi'] += $row->total;
            } elseif (in_array($row->status_reg, ['21', '21.1'])) {
                $bwCounts['suspend'][$kat] = ($bwCounts['suspend'][$kat] ?? 0) + $row->total;
                $bwCounts['total_suspend'] += $row->total;
            }
        }

        return view('teknik.pelanggan', [
            'user' => $request->user(),
            'pelanggan' => $pelanggan,
            'layananList' => $layananList,
            'statusList' => $statusList,
            'wilayahList' => $wilayahList,
            'bwCounts' => $bwCounts,
            'filters' => $request->only(['layanan', 'search', 'alamat', 'status', 'wilayah', 'per_page']),
        ]);
    }

    /**
     * Export Data Pelanggan ke Excel / CSV
     */
    public function exportPelanggan(Request $request): StreamedResponse
    {
        $confirmedStatuses = ['20', '21', '21.1', '23', '23.1'];
        $query = DB::table('view_batchjob');

        if ($request->filled('status')) {
            if ($request->status == '21') {
                $query->whereIn('status_reg', ['21', '21.1']);
            } elseif ($request->status == '23') {
                $query->whereIn('status_reg', ['23', '23.1']);
            } else {
                $query->where('status_reg', $request->status);
            }
        } else {
            $query->whereIn('status_reg', $confirmedStatuses);
        }

        if ($request->filled('layanan')) {
            $query->where('kode_kategori_bandwith', $request->layanan);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nomor_internet', 'like', '%' . $search . '%')
                  ->orWhere('nik_penduduk', 'like', '%' . $search . '%')
                  ->orWhere('nomor_hp', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('alamat')) {
            $query->where(function ($q) use ($request) {
                $q->where('alamat_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('alamat_p', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kelurahan_pasang', 'like', '%' . $request->alamat . '%')
                  ->orWhere('nama_kecamatan_pasang', 'like', '%' . $request->alamat . '%');
            });
        }

        if ($request->filled('wilayah')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_kota_pasang', 'like', '%' . $request->wilayah . '%')
                  ->orWhere('kode_wilayah_kota_pasang', 'like', '%' . $request->wilayah . '%');
            });
        }

        $filename = 'Data_Pelanggan_IMS_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Nomor Internet',
                'Nama Pelanggan',
                'Jenis Kelamin',
                'NIK Penduduk',
                'No Handphone',
                'Email',
                'Layanan',
                'Bandwidth (Mbps)',
                'Group Layanan',
                'Jenis Bangunan',
                'Alamat Pemasangan',
                'RT / RW',
                'Kelurahan',
                'Kecamatan',
                'Kota / Kabupaten',
                'Status',
                'Nama Sales',
                'Tanggal Registrasi',
                'Terakhir Update',
            ]);

            $no = 1;
            $query->orderBy('nomor_internet', 'desc')->chunk(500, function ($rows) use ($handle, &$no) {
                foreach ($rows as $r) {
                    $gender = $r->jenis_kelamin == 1 ? 'Laki-Laki' : ($r->jenis_kelamin == 2 ? 'Perempuan' : '-');
                    
                    fputcsv($handle, [
                        $no++,
                        "\t" . ($r->nomor_internet ?: '-'),
                        $r->nama_pelanggan ?: '-',
                        $gender,
                        "\t" . ($r->nik_penduduk ?: '-'),
                        "\t" . ($r->nomor_hp ?: '-'),
                        $r->email ?: '-',
                        $r->nama_kategori_bandwith ?: ($r->alias_nama_kategori ?: '-'),
                        $r->nominal_bandwith ?: '-',
                        $r->group_layanan ?: 'MEDIANET',
                        $r->jenis_bangunan ?: '-',
                        $r->alamat_p ?: ($r->alamat_pasang ?: '-'),
                        ($r->rt_pasang ?: '00') . ' / ' . ($r->rw_pasang ?: '00'),
                        $r->nama_kelurahan_pasang ?: '-',
                        $r->nama_kecamatan_pasang ?: '-',
                        $r->nama_kota_pasang ?: '-',
                        $r->desc_registrasi ?: 'Status #' . $r->status_reg,
                        $r->nama_sales ?: '-',
                        $r->date_create ?: '-',
                        $r->date_update ?: '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Menu Profile Pelanggan (Detail Pelanggan Lengkap & Log Aktivitas)
     */
    public function profilePelanggan(Request $request, string $nomorInternet): View
    {
        $customer = DB::table('view_batchjob')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        if (!$customer) {
            abort(404, "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan.");
        }

        // Attach olt and ensure property exists
        $regRecord = Schema::hasTable('trx_batchjob_register') 
            ? DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first() 
            : null;
        $customer->olt = $regRecord->olt ?? ($customer->olt ?? null);

        // 1. Logs: dari trx_batchjob_register_log & trx_instalasi
        $logs = DB::table('trx_batchjob_register_log')
            ->leftJoin('m_status_registrasi', 'trx_batchjob_register_log.status_reg', '=', 'm_status_registrasi.status_reg')
            ->where('trx_batchjob_register_log.nomor_internet', $nomorInternet)
            ->select('trx_batchjob_register_log.*', 'm_status_registrasi.desc_registrasi')
            ->orderBy('trx_batchjob_register_log.date_create', 'desc')
            ->get();

        // 2. Instalasi & Survey Detail
        $instalasi = Schema::hasTable('trx_instalasi')
            ? DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first()
            : null;

        // 3. Tim Instalasi / Survey
        $teams = Schema::hasTable('trx_instalasi_team')
            ? DB::table('trx_instalasi_team')->where('nomor_internet', $nomorInternet)->get()
            : collect();

        // 4. Tagihan / Billings
        $billingReg = Schema::hasTable('trx_billing_registrasi')
            ? DB::table('trx_billing_registrasi')->where('nomor_internet', $nomorInternet)->get()
            : collect();

        $billingLayanan = Schema::hasTable('trx_billing_layanan')
            ? DB::table('trx_billing_layanan')->where('nomor_internet', $nomorInternet)->orderBy('tahun_tagihan', 'desc')->orderBy('bulan_tagihan', 'desc')->limit(12)->get()
            : collect();

        // 5. Suspend Records
        $suspendRecords = Schema::hasTable('trx_suspend')
            ? DB::table('trx_suspend')->where('nomor_internet', $nomorInternet)->orderBy('date_create', 'desc')->get()
            : collect();

        // 6. Tiket Gangguan / Pengaduan
        $tickets = Schema::hasTable('trx_tiket_gangguan')
            ? DB::table('trx_tiket_gangguan')->where('nomor_internet', $nomorInternet)->orderBy('date_create', 'desc')->get()
            : collect();

        // 7. Perangkat & Material Pelanggan (trx_instalasi_barang joined with view_barang)
        $perangkats = Schema::hasTable('trx_instalasi_barang')
            ? DB::table('trx_instalasi_barang')
                ->leftJoin('view_barang', 'trx_instalasi_barang.kode_barang', '=', 'view_barang.kode_barang')
                ->where('trx_instalasi_barang.nomor_internet', $nomorInternet)
                ->where('trx_instalasi_barang.hide', 0)
                ->select(
                    'trx_instalasi_barang.*',
                    'view_barang.nama_jns_barang',
                    'view_barang.satuan',
                    'view_barang.nama_barang',
                    'view_barang.tipe_barang'
                )
                ->get()
            : collect();

        // 8. Master Barang untuk Dropdown Tambah Perangkat
        $masterBarang = Schema::hasTable('view_barang')
            ? DB::table('view_barang')->where('hide', 0)->orderBy('nama_jns_barang')->get()
            : collect();

        // 9. Master POP/ODN & OLT Slots for Profile Config
        $pops = Schema::hasTable('m_pop')
            ? DB::table('m_pop')->where('hide', '!=', '1')->orderBy('nama_pop')->get()
            : collect();

        $olts = Schema::hasTable('m_olt')
            ? DB::table('m_olt')->get()
            : collect();

        $indexOltData = $this->getIndexOltSlots();

        return view('teknik.pelanggan-profile', [
            'user' => $request->user(),
            'customer' => $customer,
            'logs' => $logs,
            'instalasi' => $instalasi,
            'teams' => $teams,
            'billingReg' => $billingReg,
            'billingLayanan' => $billingLayanan,
            'suspendRecords' => $suspendRecords,
            'tickets' => $tickets,
            'perangkats' => $perangkats,
            'masterBarang' => $masterBarang,
            'pops' => $pops,
            'olts' => $olts,
            'indexOltSlots' => $indexOltData['slots'],
            'occupiedIndexOlts' => $indexOltData['occupied'],
            'allPorts' => $indexOltData['allPorts'],
            'portStats' => $indexOltData['portStats'],
        ]);
    }

    /**
     * Helper: Generate & check available Index OLT slots (1 to 128) across all GPON ports
     */
    private function getIndexOltSlots(): array
    {
        // 1. Get occupied index_olt from active/non-terminated customers
        $occupiedRaw = DB::table('trx_batchjob_register')
            ->whereNotNull('index_olt')
            ->where('index_olt', '!=', '')
            ->whereNotIn('status_reg', ['23', '23.1', '15']) // 23 = Terminasi, 15 = Batal Pasang
            ->pluck('index_olt');

        $occupiedMap = [];
        foreach ($occupiedRaw as $raw) {
            $clean = trim($raw);
            if (!str_starts_with($clean, 'gpon-onu_') && str_starts_with($clean, '1/')) {
                $clean = 'gpon-onu_' . $clean;
            }
            $occupiedMap[$clean] = true;
        }

        // 2. All GPON Ports (Slot 1: 1/1/1 s/d 1/1/16, Slot 2: 1/2/1 s/d 1/2/16)
        $allPorts = [
            'gpon-onu_1/1/1', 'gpon-onu_1/1/2', 'gpon-onu_1/1/3', 'gpon-onu_1/1/4',
            'gpon-onu_1/1/5', 'gpon-onu_1/1/6', 'gpon-onu_1/1/7', 'gpon-onu_1/1/8',
            'gpon-onu_1/1/9', 'gpon-onu_1/1/10', 'gpon-onu_1/1/11', 'gpon-onu_1/1/12',
            'gpon-onu_1/1/13', 'gpon-onu_1/1/14', 'gpon-onu_1/1/15', 'gpon-onu_1/1/16',
            'gpon-onu_1/2/1', 'gpon-onu_1/2/2', 'gpon-onu_1/2/3', 'gpon-onu_1/2/4',
            'gpon-onu_1/2/5', 'gpon-onu_1/2/6', 'gpon-onu_1/2/7', 'gpon-onu_1/2/8',
            'gpon-onu_1/2/9', 'gpon-onu_1/2/10', 'gpon-onu_1/2/11', 'gpon-onu_1/2/12',
            'gpon-onu_1/2/13', 'gpon-onu_1/2/14', 'gpon-onu_1/2/15', 'gpon-onu_1/2/16',
        ];

        $slots = [];
        $portStats = [];

        foreach ($allPorts as $port) {
            $usedCount = 0;
            for ($i = 1; $i <= 128; $i++) {
                $key = "{$port}:{$i}";
                $isOccupied = isset($occupiedMap[$key]);
                if ($isOccupied) $usedCount++;

                $slots[$port][] = [
                    'key' => $key,
                    'num' => $i,
                    'is_occupied' => $isOccupied,
                ];
            }
            $portStats[$port] = [
                'name' => $port,
                'used' => $usedCount,
                'free' => 128 - $usedCount,
                'available' => 128 - $usedCount,
                'total' => 128,
            ];
        }

        return [
            'slots' => $slots,
            'portStats' => $portStats,
            'allPorts' => $allPorts,
            'occupied' => array_keys($occupiedMap),
        ];
    }

    /**
     * Tambah Perangkat / Material Pelanggan
     */
    public function storePerangkat(Request $request, string $nomorInternet): RedirectResponse
    {
        $request->validate([
            'kode_barang' => 'required|string',
            'jumlah_barang' => 'required|numeric|min:1',
            'note_instalasi_barang' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';
        $kodeInstBarang = $nomorInternet . '-' . $request->kode_barang . '-' . time();

        DB::table('trx_instalasi_barang')->insert([
            'kode_inst_barang' => $kodeInstBarang,
            'nomor_internet' => $nomorInternet,
            'kode_barang' => $request->kode_barang,
            'jumlah_barang' => $request->jumlah_barang,
            'status_instalasi_barang' => '11',
            'note_instalasi_barang' => $request->note_instalasi_barang ?? 'Penambahan perangkat/material',
            'date_create' => $now,
            'user_create' => $currentUser,
            'date_update' => $now,
            'user_update' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->back()->with('success', 'Perangkat / material berhasil ditambahkan ke pelanggan!');
    }

    /**
     * Hapus Perangkat / Material Pelanggan
     */
    public function deletePerangkat(Request $request, string $nomorInternet, string $kodeInstBarang): RedirectResponse
    {
        DB::table('trx_instalasi_barang')
            ->where('kode_inst_barang', $kodeInstBarang)
            ->where('nomor_internet', $nomorInternet)
            ->update([
                'hide' => '1',
                'date_update' => now()->format('Y-m-d H:i:s'),
                'user_update' => auth()->user()->nama ?? 'TEKNIK',
            ]);

        return redirect()->back()->with('success', 'Perangkat / material berhasil dihapus dari pelanggan!');
    }

    /**
     * Update Kredensial PPPoE, POP/ODN, Media Akses, Index OLT & Catatan
     */
    public function updatePppoe(Request $request, string $nomorInternet): RedirectResponse
    {
        $request->validate([
            'ont_us' => 'nullable|string|max:100',
            'ont_ps' => 'nullable|string|max:100',
            'kode_pop' => 'nullable|string|max:50',
            'media_akses' => 'nullable|string|max:50',
            'index_olt' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        $updateData = [
            'ont_us' => $request->ont_us,
            'ont_ps' => $request->ont_ps,
            'kode_pop' => $request->kode_pop,
            'media_akses' => $request->media_akses,
            'index_olt' => $request->index_olt,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        if ($request->filled('olt')) {
            $updateData['olt'] = $request->olt;
        }

        if ($request->filled('catatan')) {
            $updateData['note_request'] = $request->catatan;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($updateData);

        // Update trx_instalasi
        if (Schema::hasTable('trx_instalasi')) {
            DB::table('trx_instalasi')
                ->updateOrInsert(
                    ['nomor_internet' => $nomorInternet],
                    [
                        'kode_instalasi' => 'INS-' . $nomorInternet,
                        'aktivasi_note' => $request->catatan,
                        'aktivasi_note_finish' => $request->catatan,
                        'date_update' => $now,
                        'user_update' => $currentUser,
                    ]
                );
        }

        return redirect()->back()->with('success', 'Data ID PPOE, POP/ODN, Media Akses, Index OLT, dan Catatan berhasil diperbarui!');
    }

    /**
     * Upload Scan Dokumen Legalitas / Berkas Pelanggan
     */
    public function uploadDocArsip(Request $request, string $nomorInternet): RedirectResponse
    {
        $request->validate([
            'tipe_dokumen' => 'required|in:doc_berlangganan,doc_survey,doc_instalasi,doc_aktivasi,doc_terminasi,foto_ktp,foto_rumah,foto_peta',
            'file_dokumen' => 'required|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);

        $file = $request->file('file_dokumen');
        $fileName = $request->tipe_dokumen . '_' . $nomorInternet . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        if (!file_exists(public_path('uploads/registrasi'))) {
            mkdir(public_path('uploads/registrasi'), 0777, true);
        }
        
        $file->move(public_path('uploads/registrasi'), $fileName);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'TEKNIK';

        // Update or insert into trx_instalasi
        DB::table('trx_instalasi')->updateOrInsert(
            ['nomor_internet' => $nomorInternet],
            [
                $request->tipe_dokumen => $fileName,
                'date_update' => $now,
                'user_update' => $currentUser,
            ]
        );

        if (in_array($request->tipe_dokumen, ['foto_ktp', 'foto_rumah'])) {
            DB::table('trx_batchjob_register')
                ->where('nomor_internet', $nomorInternet)
                ->update([
                    $request->tipe_dokumen => $fileName,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]);
        }

        return redirect()->back()->with('success', 'Dokumen / Berkas scan legalisir berhasil diunggah!');
    }

    /**
     * Tampilan Master Dokumen Formulir Berlangganan Per-User / Pelanggan
     * Menghasilkan dokumen resmi Form Berlangganan PT Media Solusi Network
     */
    public function dokumenLangganan(Request $request, string $nomorInternet): View
    {
        // 1. Ambil dari view_batchjob jika sudah ada
        $customer = DB::table('view_batchjob')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        // 2. Jika belum ada di view_batchjob (misal registrasi baru), query langsung tabel terkait
        if (!$customer) {
            $customer = DB::table('trx_batchjob_register')
                ->leftJoin('m_pelanggan', 'trx_batchjob_register.nik_penduduk', '=', 'm_pelanggan.nik_penduduk')
                ->leftJoin('m_bandwith', 'trx_batchjob_register.kode_bandwith', '=', 'm_bandwith.kode_bandwith')
                ->leftJoin('m_kategori_bandwith', 'm_bandwith.kode_kategori_bandwith', '=', 'm_kategori_bandwith.kode_kategori_bandwith')
                ->leftJoin('m_pop', 'trx_batchjob_register.kode_pop', '=', 'm_pop.kode_pop')
                ->where('trx_batchjob_register.nomor_internet', $nomorInternet)
                ->select(
                    'trx_batchjob_register.*',
                    'm_pelanggan.nama_penduduk',
                    'm_pelanggan.jenis_kelamin',
                    'm_pelanggan.tanggal_lahir',
                    'm_pelanggan.pic',
                    'm_pelanggan.email',
                    'm_pelanggan.nomor_hp',
                    'm_pelanggan.nomor_hp_2',
                    'm_pelanggan.alamat_ktp',
                    'm_pelanggan.rt_ktp',
                    'm_pelanggan.rw_ktp',
                    'm_bandwith.nominal_bandwith',
                    'm_bandwith.harga_bandwith',
                    'm_kategori_bandwith.nama_kategori_bandwith',
                    'm_kategori_bandwith.alias_nama_kategori',
                    'm_kategori_bandwith.biaya_reg',
                    'm_pop.nama_pop'
                )
                ->first();
        }

        if (!$customer) {
            abort(404, "Dokumen Form Berlangganan untuk nomor internet {$nomorInternet} tidak ditemukan.");
        }

        // Perangkat / Material jika ada
        $perangkats = Schema::hasTable('trx_instalasi_barang')
            ? DB::table('trx_instalasi_barang')
                ->leftJoin('view_barang', 'trx_instalasi_barang.kode_barang', '=', 'view_barang.kode_barang')
                ->where('trx_instalasi_barang.nomor_internet', $nomorInternet)
                ->where('trx_instalasi_barang.hide', 0)
                ->select('trx_instalasi_barang.*', 'view_barang.nama_barang', 'view_barang.tipe_barang')
                ->get()
            : collect();

        // Data instalasi
        $instalasi = Schema::hasTable('trx_instalasi')
            ? DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first()
            : null;

        return view('teknik.dokumen.langganan', [
            'user' => $request->user(),
            'customer' => $customer,
            'perangkats' => $perangkats,
            'instalasi' => $instalasi,
        ]);
    }

    /**
     * Tampilan Master Dokumen Surat Tugas Survey Per-User / Pelanggan
     * Menghasilkan dokumen resmi Surat Tugas Survey PT Media Solusi Network
     */
    public function dokumenSurvey(Request $request, string $nomorInternet): View
    {
        // 1. Ambil dari view_batchjob jika sudah ada
        $customer = DB::table('view_batchjob')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        // 2. Jika belum ada di view_batchjob (misal registrasi baru), query langsung tabel terkait
        if (!$customer) {
            $customer = DB::table('trx_batchjob_register')
                ->leftJoin('m_pelanggan', 'trx_batchjob_register.nik_penduduk', '=', 'm_pelanggan.nik_penduduk')
                ->leftJoin('m_bandwith', 'trx_batchjob_register.kode_bandwith', '=', 'm_bandwith.kode_bandwith')
                ->leftJoin('m_kategori_bandwith', 'm_bandwith.kode_kategori_bandwith', '=', 'm_kategori_bandwith.kode_kategori_bandwith')
                ->leftJoin('m_pop', 'trx_batchjob_register.kode_pop', '=', 'm_pop.kode_pop')
                ->where('trx_batchjob_register.nomor_internet', $nomorInternet)
                ->select(
                    'trx_batchjob_register.*',
                    'm_pelanggan.nama_penduduk',
                    'm_pelanggan.jenis_kelamin',
                    'm_pelanggan.tanggal_lahir',
                    'm_pelanggan.pic',
                    'm_pelanggan.email',
                    'm_pelanggan.nomor_hp',
                    'm_pelanggan.nomor_hp_2',
                    'm_pelanggan.alamat_ktp',
                    'm_pelanggan.rt_ktp',
                    'm_pelanggan.rw_ktp',
                    'm_bandwith.nominal_bandwith',
                    'm_bandwith.harga_bandwith',
                    'm_kategori_bandwith.nama_kategori_bandwith',
                    'm_kategori_bandwith.alias_nama_kategori',
                    'm_kategori_bandwith.biaya_reg',
                    'm_pop.nama_pop'
                )
                ->first();
        }

        if (!$customer) {
            abort(404, "Dokumen Surat Tugas Survey untuk nomor internet {$nomorInternet} tidak ditemukan.");
        }

        // Data instalasi & survey
        $instalasi = Schema::hasTable('trx_instalasi')
            ? DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first()
            : null;

        // Tim Teknisi Survey dari trx_instalasi_team (kat_team = 10)
        $teamSurvey = Schema::hasTable('trx_instalasi_team')
            ? DB::table('trx_instalasi_team')
                ->where('nomor_internet', $nomorInternet)
                ->where('kat_team', '10')
                ->get()
            : collect();

        return view('teknik.dokumen.survey', [
            'user' => $request->user(),
            'customer' => $customer,
            'instalasi' => $instalasi,
            'teamSurvey' => $teamSurvey,
        ]);
    }

    /**
     * Tampilan Master Dokumen Surat Tugas Instalasi Per-User / Pelanggan
     * Menghasilkan dokumen resmi Surat Tugas Instalasi PT Media Solusi Network
     */
    public function dokumenInstalasi(Request $request, string $nomorInternet): View
    {
        // 1. Ambil dari view_batchjob jika sudah ada
        $customer = DB::table('view_batchjob')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        // 2. Jika belum ada di view_batchjob (misal registrasi baru), query langsung tabel terkait
        if (!$customer) {
            $customer = DB::table('trx_batchjob_register')
                ->leftJoin('m_pelanggan', 'trx_batchjob_register.nik_penduduk', '=', 'm_pelanggan.nik_penduduk')
                ->leftJoin('m_bandwith', 'trx_batchjob_register.kode_bandwith', '=', 'm_bandwith.kode_bandwith')
                ->leftJoin('m_kategori_bandwith', 'm_bandwith.kode_kategori_bandwith', '=', 'm_kategori_bandwith.kode_kategori_bandwith')
                ->leftJoin('m_pop', 'trx_batchjob_register.kode_pop', '=', 'm_pop.kode_pop')
                ->where('trx_batchjob_register.nomor_internet', $nomorInternet)
                ->select(
                    'trx_batchjob_register.*',
                    'm_pelanggan.nama_penduduk',
                    'm_pelanggan.jenis_kelamin',
                    'm_pelanggan.tanggal_lahir',
                    'm_pelanggan.pic',
                    'm_pelanggan.email',
                    'm_pelanggan.nomor_hp',
                    'm_pelanggan.nomor_hp_2',
                    'm_pelanggan.alamat_ktp',
                    'm_pelanggan.rt_ktp',
                    'm_pelanggan.rw_ktp',
                    'm_bandwith.nominal_bandwith',
                    'm_bandwith.harga_bandwith',
                    'm_kategori_bandwith.nama_kategori_bandwith',
                    'm_kategori_bandwith.alias_nama_kategori',
                    'm_kategori_bandwith.biaya_reg',
                    'm_pop.nama_pop'
                )
                ->first();
        }

        if (!$customer) {
            abort(404, "Dokumen Surat Tugas Instalasi untuk nomor internet {$nomorInternet} tidak ditemukan.");
        }

        // Data instalasi dari trx_instalasi
        $instalasi = Schema::hasTable('trx_instalasi')
            ? DB::table('trx_instalasi')->where('nomor_internet', $nomorInternet)->first()
            : null;

        // Tim Teknisi Instalasi dari trx_instalasi_team (kat_team = 11)
        $teamInstalasi = Schema::hasTable('trx_instalasi_team')
            ? DB::table('trx_instalasi_team')
                ->where('nomor_internet', $nomorInternet)
                ->where('kat_team', '11')
                ->get()
            : collect();

        // Perangkat / Material instalasi jika ada
        $perangkats = Schema::hasTable('trx_instalasi_barang')
            ? DB::table('trx_instalasi_barang')
                ->leftJoin('view_barang', 'trx_instalasi_barang.kode_barang', '=', 'view_barang.kode_barang')
                ->where('trx_instalasi_barang.nomor_internet', $nomorInternet)
                ->where('trx_instalasi_barang.hide', 0)
                ->select('trx_instalasi_barang.*', 'view_barang.nama_barang', 'view_barang.tipe_barang')
                ->get()
            : collect();

        return view('teknik.dokumen.instalasi', [
            'user' => $request->user(),
            'customer' => $customer,
            'instalasi' => $instalasi,
            'teamInstalasi' => $teamInstalasi,
            'perangkats' => $perangkats,
        ]);
    }

    /**
     * Permintaan: UP / Downgrade Layanan (Ubah Layanan)
     */
    public function upDowngrade(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');

        $query = DB::table('view_ubah_layanan as u')
            ->leftJoin('view_batchjob as b', 'u.nomor_internet', '=', 'b.nomor_internet')
            ->select(
                'u.*',
                'b.alamat_p',
                'b.alamat_pasang',
                'b.jenis_bangunan',
                'b.jenis_kelamin',
                'b.status_reg as status_pelanggan'
            );

        if ($layanan) {
            $query->where(function($q) use ($layanan) {
                $q->where('u.nama_kategori_bandwith_baru', $layanan)
                  ->orWhere('u.nama_kategori_bandwith_lama', $layanan);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u.kode_trx_ubah_layanan', 'like', "%{$search}%")
                  ->orWhere('u.nomor_internet', 'like', "%{$search}%")
                  ->orWhere('u.nama_pelanggan', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('b.alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('u.status_ubah_layanan', $status);
        }

        $ubahLayanans = $query->orderBy('u.date_create', 'desc')->paginate(10)->withQueryString();

        // HIGH PERFORMANCE: Single aggregated query for UP/Downgrade KPIs
        $counts = DB::table('trx_ubah_layanan')
            ->selectRaw("
                COUNT(CASE WHEN status_ubah_layanan = '11' THEN 1 END) as c11,
                COUNT(CASE WHEN status_ubah_layanan = '12' THEN 1 END) as c12,
                COUNT(CASE WHEN status_ubah_layanan = '13' THEN 1 END) as c13,
                COUNT(CASE WHEN status_ubah_layanan = '14' THEN 1 END) as c14
            ")
            ->first();

        $count11 = (int) ($counts->c11 ?? 0);
        $count12 = (int) ($counts->c12 ?? 0);
        $count13 = (int) ($counts->c13 ?? 0);
        $count14 = (int) ($counts->c14 ?? 0);

        // 1. Kategori Layanan (m_bandwith_kategori)
        $layananKategoriList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')
                ->where(function($q) {
                    $q->where('disable', 0)->orWhereNull('disable');
                })
                ->orderBy('nama_kategori_bandwith', 'asc')
                ->get()
            : collect();

        if ($layananKategoriList->isEmpty()) {
            $layananKategoriList = collect([
                (object)['kode_kategori_bandwith' => 'BROADBAND', 'nama_kategori_bandwith' => 'BROADBAND', 'alias_nama_kategori' => 'BROADBAND'],
                (object)['kode_kategori_bandwith' => 'DEDICATED', 'nama_kategori_bandwith' => 'DEDICATED', 'alias_nama_kategori' => 'DEDICATED'],
                (object)['kode_kategori_bandwith' => 'SOHO', 'nama_kategori_bandwith' => 'SOHO', 'alias_nama_kategori' => 'SOHO'],
                (object)['kode_kategori_bandwith' => 'CORPORATE', 'nama_kategori_bandwith' => 'CORPORATE', 'alias_nama_kategori' => 'CORPORATE'],
            ]);
        }

        // 2. Daftar Paket Lengkap (m_bandwith)
        $paketList = Schema::hasTable('m_bandwith')
            ? DB::table('m_bandwith as b')
                ->leftJoin('m_bandwith_kategori as k', 'b.kode_kategori_bandwith', '=', 'k.kode_kategori_bandwith')
                ->where(function($q) {
                    $q->where('b.disable', 0)->orWhereNull('b.disable');
                })
                ->where(function($q) {
                    $q->where('b.hide', '0')->orWhereNull('b.hide');
                })
                ->select(
                    'b.kode_bandwith',
                    'b.kode_kategori_bandwith',
                    'b.nama_bandwith',
                    'b.nominal_bandwith',
                    'b.harga_bandwith',
                    'k.nama_kategori_bandwith',
                    'k.alias_nama_kategori'
                )
                ->orderBy('b.nominal_bandwith', 'asc')
                ->get()
            : collect();

        if ($paketList->isEmpty()) {
            $paketList = collect([
                (object)['kode_bandwith' => 'BB10', 'kode_kategori_bandwith' => 'BROADBAND', 'nama_bandwith' => 'BROADBAND 10 Mbps', 'nominal_bandwith' => 10, 'harga_bandwith' => 150000, 'nama_kategori_bandwith' => 'BROADBAND'],
                (object)['kode_bandwith' => 'BB20', 'kode_kategori_bandwith' => 'BROADBAND', 'nama_bandwith' => 'BROADBAND 20 Mbps', 'nominal_bandwith' => 20, 'harga_bandwith' => 200000, 'nama_kategori_bandwith' => 'BROADBAND'],
                (object)['kode_bandwith' => 'BB30', 'kode_kategori_bandwith' => 'BROADBAND', 'nama_bandwith' => 'BROADBAND 30 Mbps', 'nominal_bandwith' => 30, 'harga_bandwith' => 250000, 'nama_kategori_bandwith' => 'BROADBAND'],
                (object)['kode_bandwith' => 'BB50', 'kode_kategori_bandwith' => 'BROADBAND', 'nama_bandwith' => 'BROADBAND 50 Mbps', 'nominal_bandwith' => 50, 'harga_bandwith' => 350000, 'nama_kategori_bandwith' => 'BROADBAND'],
                (object)['kode_bandwith' => 'BB100', 'kode_kategori_bandwith' => 'BROADBAND', 'nama_bandwith' => 'BROADBAND 100 Mbps', 'nominal_bandwith' => 100, 'harga_bandwith' => 500000, 'nama_kategori_bandwith' => 'BROADBAND'],
                (object)['kode_bandwith' => 'DED50', 'kode_kategori_bandwith' => 'DEDICATED', 'nama_bandwith' => 'DEDICATED 50 Mbps', 'nominal_bandwith' => 50, 'harga_bandwith' => 1500000, 'nama_kategori_bandwith' => 'DEDICATED'],
                (object)['kode_bandwith' => 'DED100', 'kode_kategori_bandwith' => 'DEDICATED', 'nama_bandwith' => 'DEDICATED 100 Mbps', 'nominal_bandwith' => 100, 'harga_bandwith' => 2500000, 'nama_kategori_bandwith' => 'DEDICATED'],
                (object)['kode_bandwith' => 'SOHO30', 'kode_kategori_bandwith' => 'SOHO', 'nama_bandwith' => 'SOHO 30 Mbps', 'nominal_bandwith' => 30, 'harga_bandwith' => 400000, 'nama_kategori_bandwith' => 'SOHO'],
                (object)['kode_bandwith' => 'SOHO50', 'kode_kategori_bandwith' => 'SOHO', 'nama_bandwith' => 'SOHO 50 Mbps', 'nominal_bandwith' => 50, 'harga_bandwith' => 600000, 'nama_kategori_bandwith' => 'SOHO'],
                (object)['kode_bandwith' => 'CORP100', 'kode_kategori_bandwith' => 'CORPORATE', 'nama_bandwith' => 'CORPORATE 100 Mbps', 'nominal_bandwith' => 100, 'harga_bandwith' => 3000000, 'nama_kategori_bandwith' => 'CORPORATE'],
            ]);
        }

        $layananList = $layananKategoriList->pluck('nama_kategori_bandwith')->filter()->unique();

        return view('teknik.permintaan.up-downgrade', [
            'user' => $request->user(),
            'ubahLayanans' => $ubahLayanans,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'count11' => $count11,
            'count12' => $count12,
            'count13' => $count13,
            'count14' => $count14,
            'layananList' => $layananList,
            'layananKategoriList' => $layananKategoriList,
            'paketList' => $paketList,
        ]);
    }

    /**
     * Schedule UP / Downgrade Layanan
     */
    public function scheduleUpDowngrade(Request $request, string $kodeTrx): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['noc', 'direktur', 'admin'])) {
            abort(403, 'Role Teknik hanya memiliki hak akses melihat data (View Only).');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        DB::table('trx_ubah_layanan')->where('kode_trx_ubah_layanan', $kodeTrx)->update([
            'status_ubah_layanan' => '12', // On Schedule
            'date_schedule' => $request->date_schedule ?: now()->format('Y-m-d'),
            'note_schedule' => $request->note_schedule ?? 'Jadwal eksekusi ubah profil bandwidth',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan ubah layanan {$kodeTrx} telah dijadwalkan (On Schedule)!");
    }

    /**
     * Eksekusi UP / Downgrade Layanan (KD13 Success) & Pilihan Paket Baru
     */
    public function executeUpDowngrade(Request $request, string $kodeTrx): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['noc', 'direktur', 'admin'])) {
            abort(403, 'Role Teknik hanya memiliki hak akses melihat data (View Only).');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $trx = DB::table('trx_ubah_layanan')->where('kode_trx_ubah_layanan', $kodeTrx)->first();
        if (!$trx) {
            return redirect()->back()->with('error', "Data transaksi {$kodeTrx} tidak ditemukan.");
        }

        $kodeBandwithBaru = $request->input('kode_bandwith_baru', $trx->kode_bandwith_baru ?? null);
        $groupLayanan = $request->input('group_layanan');
        $paketData = null;

        if ($kodeBandwithBaru && Schema::hasTable('m_bandwith')) {
            $paketData = DB::table('m_bandwith as b')
                ->leftJoin('m_bandwith_kategori as k', 'b.kode_kategori_bandwith', '=', 'k.kode_kategori_bandwith')
                ->where('b.kode_bandwith', $kodeBandwithBaru)
                ->select('b.*', 'k.nama_kategori_bandwith', 'k.alias_nama_kategori')
                ->first();
        }

        $updateTrx = [
            'status_ubah_layanan' => '13', // KD13 Success
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        if (Schema::hasColumn('trx_ubah_layanan', 'date_closing')) {
            $updateTrx['date_closing'] = $request->date_eksekusi ?: now()->format('Y-m-d');
        }
        if (Schema::hasColumn('trx_ubah_layanan', 'note_closing')) {
            $updateTrx['note_closing'] = $request->note_eksekusi ?? 'Eksekusi UP/Downgrade bandwidth profil pelanggan berhasil diselesaikan.';
        }

        if ($paketData) {
            if (Schema::hasColumn('trx_ubah_layanan', 'kode_bandwith_baru')) {
                $updateTrx['kode_bandwith_baru'] = $paketData->kode_bandwith;
            }
            if (Schema::hasColumn('trx_ubah_layanan', 'nama_kategori_bandwith_baru')) {
                $updateTrx['nama_kategori_bandwith_baru'] = $paketData->nama_kategori_bandwith ?? $paketData->alias_nama_kategori;
            }
            if (Schema::hasColumn('trx_ubah_layanan', 'nominal_bandwith_baru')) {
                $updateTrx['nominal_bandwith_baru'] = $paketData->nominal_bandwith;
            }
        } elseif ($kodeBandwithBaru && Schema::hasColumn('trx_ubah_layanan', 'kode_bandwith_baru')) {
            $updateTrx['kode_bandwith_baru'] = $kodeBandwithBaru;
        }

        if ($groupLayanan && Schema::hasColumn('trx_ubah_layanan', 'group_layanan')) {
            $updateTrx['group_layanan'] = $groupLayanan;
        }

        DB::table('trx_ubah_layanan')->where('kode_trx_ubah_layanan', $kodeTrx)->update($updateTrx);

        // Update active package in customer record (trx_batchjob_register)
        if ($trx->nomor_internet && Schema::hasTable('trx_batchjob_register')) {
            $custUpdate = [
                'date_update' => $now,
                'user_update' => $currentUser,
            ];
            if ($paketData) {
                $custUpdate['kode_bandwith'] = $paketData->kode_bandwith;
                $custUpdate['kode_kategori_bandwith'] = $paketData->kode_kategori_bandwith;
            }
            if ($groupLayanan && Schema::hasColumn('trx_batchjob_register', 'group_layanan')) {
                $custUpdate['group_layanan'] = $groupLayanan;
            }
            DB::table('trx_batchjob_register')
                ->where('nomor_internet', $trx->nomor_internet)
                ->update($custUpdate);
        }

        // Update in trx_pelanggan if exists
        if ($trx->nomor_internet && Schema::hasTable('trx_pelanggan')) {
            $pelangganUpdate = [];
            if ($paketData && Schema::hasColumn('trx_pelanggan', 'kode_bandwith')) {
                $pelangganUpdate['kode_bandwith'] = $paketData->kode_bandwith;
            }
            if ($groupLayanan && Schema::hasColumn('trx_pelanggan', 'group_layanan')) {
                $pelangganUpdate['group_layanan'] = $groupLayanan;
            }
            if (!empty($pelangganUpdate)) {
                DB::table('trx_pelanggan')
                    ->where('nomor_internet', $trx->nomor_internet)
                    ->update($pelangganUpdate);
            }
        }

        $paketName = $paketData ? ($paketData->nama_bandwith ?? (($paketData->nama_kategori_bandwith ?? 'Paket') . ' ' . ($paketData->nominal_bandwith ?? '') . ' Mbps')) : ($trx->nama_kategori_bandwith_baru ?? 'Paket Baru');
        return redirect()->back()->with('success', "Layanan pelanggan {$trx->nomor_internet} berhasil di-UP/Downgrade ke {$paketName} (KD13 Success)!");
    }

    /**
     * Cancel UP / Downgrade Layanan
     */
    public function cancelUpDowngrade(Request $request, string $kodeTrx): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['noc', 'direktur', 'admin'])) {
            abort(403, 'Role Teknik hanya memiliki hak akses melihat data (View Only).');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        DB::table('trx_ubah_layanan')->where('kode_trx_ubah_layanan', $kodeTrx)->update([
            'status_ubah_layanan' => '14', // Canceled
            'date_cancel' => now()->format('Y-m-d'),
            'note_cancel' => $request->note_cancel ?? 'Dibatalkan oleh operator',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan ubah layanan {$kodeTrx} telah dibatalkan (Canceled)!");
    }

    /**
     * Permintaan: Terminasi
     */
    public function terminasi(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        $query = DB::table('view_terminasi');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode_trx_terminasi', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('status_terminasi', $status);
        }

        if ($bulan) {
            $query->whereMonth('date_create', $bulan);
        }

        if ($tahun) {
            $query->whereYear('date_create', $tahun);
        }

        $terminasis = $query->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

        // HIGH PERFORMANCE: Single aggregated query for Terminasi KPIs
        $counts = DB::table('trx_terminasi')
            ->selectRaw("
                COUNT(CASE WHEN status_terminasi = '11' THEN 1 END) as c11,
                COUNT(CASE WHEN status_terminasi = '12' THEN 1 END) as c12,
                COUNT(CASE WHEN status_terminasi = '12.1' THEN 1 END) as c12_1,
                COUNT(CASE WHEN status_terminasi = '13' THEN 1 END) as c13,
                COUNT(CASE WHEN status_terminasi = '14' THEN 1 END) as c14,
                COUNT(CASE WHEN status_terminasi = '15' THEN 1 END) as c15,
                COUNT(CASE WHEN status_terminasi = '16' THEN 1 END) as c16,
                COUNT(CASE WHEN status_terminasi = '17' THEN 1 END) as c17
            ")
            ->first();

        $count11 = (int) ($counts->c11 ?? 0);
        $count12 = (int) ($counts->c12 ?? 0);
        $count12_1 = (int) ($counts->c12_1 ?? 0);
        $count13 = (int) ($counts->c13 ?? 0);
        $count14 = (int) ($counts->c14 ?? 0);
        $count15 = (int) ($counts->c15 ?? 0);
        $count16 = (int) ($counts->c16 ?? 0);
        $count17 = (int) ($counts->c17 ?? 0);

        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();
        $karyawans = DB::table('tb_m_karyawan')->where('status_aktif', 1)->orderBy('nama_karyawan')->get();

        return view('teknik.permintaan.terminasi', [
            'user' => $request->user(),
            'terminasis' => $terminasis,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'count11' => $count11,
            'count12' => $count12,
            'count12_1' => $count12_1,
            'count13' => $count13,
            'count14' => $count14,
            'count15' => $count15,
            'count16' => $count16,
            'count17' => $count17,
            'layananList' => $layananList,
            'karyawans' => $karyawans,
        ]);
    }

    /**
     * Schedule Collect Perangkat Terminasi
     */
    public function scheduleCollect(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Teknik';

        $team = is_array($request->team) ? implode(', ', $request->team) : ($request->team ?? $request->team_collect ?? '');
        $dateSchedule = $request->date_schedule ?: $request->date_collect_start ?: now()->format('Y-m-d');
        $waktu = $request->waktu ?: $request->time_collect_start ?: '09:00 - 12:00 WIB';
        $note = $request->note ?: $request->note_collect_start;

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '12', // (KD12) Collecting
            'date_collect_start' => $dateSchedule,
            'time_collect_start' => $waktu,
            'team_collect' => $team,
            'note_collect_start' => $note,
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Jadwal penarikan perangkat (Schedule Collect) {$kodeTrx} berhasil disimpan!");
    }

    /**
     * Cancel Permintaan Terminasi
     */
    public function cancelTerminasi(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Teknik';

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '16', // Cancel Terminasi
            'note_termin_cancel' => $request->note_cancel ?? 'Dibatalkan oleh operator',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan terminasi {$kodeTrx} berhasil dibatalkan!");
    }

    /**
     * Permintaan: Suspend
     */
    public function suspend(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('view_suspend');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('desc_suspend', 'like', "%{$search}%");
            });
        }
        $suspends = $query->orderBy('date_create', 'desc')->paginate(15)->withQueryString();

        return view('teknik.permintaan.suspend', [
            'user' => $request->user(),
            'suspends' => $suspends,
            'search' => $search,
        ]);
    }

    // =========================================================================
    // GIS NETWORK BUILDER & PETA JARINGAN FTTH
    // =========================================================================

    /**
     * Halaman Peta Jaringan & Jalur FTTH (GIS Network Builder)
     */
    public function petaJaringan(Request $request): View
    {
        $projects = DB::table('gis_projects')->orderBy('id', 'asc')->get();

        // If no project exists, create default one
        if ($projects->isEmpty()) {
            $defaultId = DB::table('gis_projects')->insertGetId([
                'code' => 'PRJ-DEFAULT',
                'name' => 'Jaringan Utama FTTH',
                'description' => 'Proyek pemetaan jaringan fiber optik utama',
                'created_by' => auth()->user()->nama ?? 'SYSTEM',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $projects = DB::table('gis_projects')->get();
        }

        $projectId = $request->query('project_id') ?: ($projects->first()->id ?? 1);
        $currentProject = $projects->firstWhere('id', (int)$projectId) ?: $projects->first();

        // Get elements for current project
        $elements = DB::table('gis_elements')
            ->where('project_id', $currentProject->id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($el) {
                $el->coordinates = $el->coordinates ? json_decode($el->coordinates, true) : [];
                $el->metadata = $el->metadata ? json_decode($el->metadata, true) : [];
                $el->latitude = $el->latitude !== null ? (float)$el->latitude : null;
                $el->longitude = $el->longitude !== null ? (float)$el->longitude : null;
                $el->length_meters = $el->length_meters !== null ? (float)$el->length_meters : 0;
                $el->line_width = $el->line_width !== null ? (float)$el->line_width : 3.0;
                return $el;
            });

        // Add elements count to each project
        $elementCounts = DB::table('gis_elements')
            ->select('project_id', DB::raw('count(*) as total'))
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        foreach ($projects as $prj) {
            $prj->elements_count = $elementCounts[$prj->id] ?? 0;
        }

        // Get master ODP data from database
        $allOdps = DB::table('m_odp')->get()->map(function ($odp) {
            // Check if coordinates exist in note_odp or default
            $lat = null;
            $lng = null;
            if (!empty($odp->note_odp) && preg_match('/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/', $odp->note_odp, $matches)) {
                $lat = (float)$matches[1];
                $lng = (float)$matches[2];
            }
            return [
                'kode_odp' => $odp->kode_odp,
                'name_odp' => $odp->name_odp,
                'kode_pon' => $odp->kode_pon,
                'capacity_odp' => $odp->capacity_odp,
                'note_odp' => $odp->note_odp,
                'latitude' => $lat,
                'longitude' => $lng,
            ];
        });

        return view('teknik.peta-jaringan', [
            'user' => $request->user(),
            'allProjects' => $projects,
            'currentProject' => $currentProject,
            'customElements' => $elements,
            'allOdps' => $allOdps,
        ]);
    }

    /**
     * Store New GIS Project
     */
    public function storeGisProject(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $code = 'PRJ-' . strtoupper(\Illuminate\Support\Str::random(6));
        $now = now();

        $id = DB::table('gis_projects')->insertGetId([
            'code' => $code,
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => auth()->user()->nama ?? 'Teknik',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $project = DB::table('gis_projects')->where('id', $id)->first();
        $project->elements_count = 0;

        return response()->json([
            'success' => true,
            'message' => 'Proyek GIS berhasil dibuat',
            'project' => $project,
        ]);
    }

    /**
     * Delete GIS Project
     */
    public function deleteGisProject(Request $request, $id): JsonResponse
    {
        $project = DB::table('gis_projects')->where('id', $id)->first();
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Proyek tidak ditemukan'], 404);
        }

        if ($project->code === 'PRJ-DEFAULT') {
            return response()->json(['success' => false, 'message' => 'Proyek default tidak dapat dihapus'], 422);
        }

        DB::table('gis_elements')->where('project_id', $id)->delete();
        DB::table('gis_projects')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Proyek {$project->name} berhasil dihapus",
        ]);
    }

    /**
     * Save / Update GIS Element (Marker or Line)
     */
    public function saveGisElement(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $projectId = $request->input('project_id', 1);
        $category = $request->input('category', 'marker');
        $elementType = $request->input('element_type', 'pole');
        $name = $request->input('name', 'Elemen GIS');
        $color = $request->input('color');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $coordinates = $request->input('coordinates');
        $lengthMeters = $request->input('length_meters');
        $lineWidth = $request->input('line_width', 3.0);
        $lineDash = $request->input('line_dash', 'solid');
        $metadata = $request->input('metadata');

        $now = now();
        $user = auth()->user()->nama ?? 'Teknik';

        $data = [
            'project_id' => $projectId,
            'category' => $category,
            'element_type' => $elementType,
            'name' => $name,
            'color' => $color,
            'latitude' => $latitude !== null ? (float)$latitude : null,
            'longitude' => $longitude !== null ? (float)$longitude : null,
            'coordinates' => is_array($coordinates) ? json_encode($coordinates) : (is_string($coordinates) ? $coordinates : null),
            'length_meters' => $lengthMeters !== null ? (float)$lengthMeters : null,
            'line_width' => $lineWidth !== null ? (float)$lineWidth : 3.0,
            'line_dash' => $lineDash ?: 'solid',
            'metadata' => is_array($metadata) ? json_encode($metadata) : (is_string($metadata) ? $metadata : null),
            'updated_at' => $now,
        ];

        if ($id && DB::table('gis_elements')->where('id', $id)->exists()) {
            DB::table('gis_elements')->where('id', $id)->update($data);
            $elementId = $id;
        } else {
            $data['created_by'] = $user;
            $data['created_at'] = $now;
            $elementId = DB::table('gis_elements')->insertGetId($data);
        }

        $saved = DB::table('gis_elements')->where('id', $elementId)->first();
        $saved->coordinates = $saved->coordinates ? json_decode($saved->coordinates, true) : [];
        $saved->metadata = $saved->metadata ? json_decode($saved->metadata, true) : [];
        $saved->latitude = $saved->latitude !== null ? (float)$saved->latitude : null;
        $saved->longitude = $saved->longitude !== null ? (float)$saved->longitude : null;
        $saved->length_meters = $saved->length_meters !== null ? (float)$saved->length_meters : 0;
        $saved->line_width = $saved->line_width !== null ? (float)$saved->line_width : 3.0;

        return response()->json([
            'success' => true,
            'message' => 'Elemen GIS berhasil disimpan',
            'element' => $saved,
        ]);
    }

    /**
     * Delete GIS Element
     */
    public function deleteGisElement(Request $request, $id): JsonResponse
    {
        $deleted = DB::table('gis_elements')->where('id', $id)->delete();

        return response()->json([
            'success' => (bool)$deleted,
            'message' => $deleted ? 'Elemen berhasil dihapus' : 'Elemen tidak ditemukan',
        ]);
    }

    /**
     * Upload GIS Photo Dokumentasi
     */
    public function uploadGisPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp,heic|max:15360',
        ]);

        $file = $request->file('photo');
        $fileName = 'gis_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/gis_photos');

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $file->move($targetDir, $fileName);
        $url = asset('uploads/gis_photos/' . $fileName);

        return response()->json([
            'success' => true,
            'url' => $url,
            'fileName' => $fileName,
            'originalName' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Import KMZ / KML File
     */
    public function importKmzKml(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:30720',
            'project_id' => 'nullable|integer',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $projectId = $request->input('project_id') ?: DB::table('gis_projects')->value('id') ?: 1;

        $kmlContent = '';

        if ($ext === 'kmz') {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (str_ends_with(strtolower($filename), '.kml')) {
                        $kmlContent = $zip->getFromIndex($i);
                        break;
                    }
                }
                $zip->close();
            }
        } else {
            $kmlContent = file_get_contents($file->getRealPath());
        }

        if (empty($kmlContent)) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca isi berkas KML/KMZ'], 422);
        }

        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($kmlContent);
        if (!$xml) {
            return response()->json(['success' => false, 'message' => 'Format KML/KMZ tidak valid'], 422);
        }

        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $xml->registerXPathNamespace('gx', 'http://www.google.com/kml/ext/2.2');

        $placemarks = $xml->xpath('//kml:Placemark | //Placemark');
        $importedCount = 0;
        $now = now();
        $user = auth()->user()->nama ?? 'Teknik Import';

        foreach ($placemarks as $pm) {
            $name = (string)($pm->name ?? 'Objek KML');
            $desc = (string)($pm->description ?? '');
            
            // 1. Check if Point
            $point = $pm->Point ?? $pm->children('http://www.opengis.net/kml/2.2')->Point;
            if ($point && !empty($point->coordinates)) {
                $coordStr = trim((string)$point->coordinates);
                $parts = explode(',', $coordStr);
                if (count($parts) >= 2) {
                    $lng = (float)trim($parts[0]);
                    $lat = (float)trim($parts[1]);

                    // Infer element type from name
                    $elType = 'pole';
                    $lowerName = strtolower($name);
                    if (str_contains($lowerName, 'odc') || str_contains($lowerName, 'fdt')) {
                        $elType = 'odc';
                    } elseif (str_contains($lowerName, 'joint') || str_contains($lowerName, 'jb') || str_contains($lowerName, 'closure')) {
                        $elType = 'joint_box';
                    } elseif (str_contains($lowerName, 'olt') || str_contains($lowerName, 'server') || str_contains($lowerName, 'pop')) {
                        $elType = 'olt';
                    } elseif (str_contains($lowerName, 'pelanggan') || str_contains($lowerName, 'ont') || str_contains($lowerName, 'rumah')) {
                        $elType = 'customer';
                    }

                    DB::table('gis_elements')->insert([
                        'project_id' => $projectId,
                        'category' => 'marker',
                        'element_type' => $elType,
                        'name' => $name,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'metadata' => json_encode(['description' => $desc, 'imported_from' => $file->getClientOriginalName()]),
                        'created_by' => $user,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $importedCount++;
                }
            }

            // 2. Check if LineString
            $lineString = $pm->LineString ?? $pm->children('http://www.opengis.net/kml/2.2')->LineString;
            if ($lineString && !empty($lineString->coordinates)) {
                $coordText = trim((string)$lineString->coordinates);
                $tuples = preg_split('/\s+/', $coordText);
                $coords = [];
                foreach ($tuples as $tuple) {
                    $p = explode(',', trim($tuple));
                    if (count($p) >= 2) {
                        $coords[] = [(float)trim($p[1]), (float)trim($p[0])]; // [lat, lng]
                    }
                }

                if (count($coords) >= 2) {
                    $elType = 'distribution';
                    $lowerName = strtolower($name);
                    if (str_contains($lowerName, 'feeder') || str_contains($lowerName, 'backbone')) {
                        $elType = 'feeder';
                    } elseif (str_contains($lowerName, 'drop') || str_contains($lowerName, 'dropcore')) {
                        $elType = 'dropcore';
                    }

                    // Calculate distance in meters
                    $dist = 0;
                    for ($i = 0; $i < count($coords) - 1; $i++) {
                        $dist += $this->calculateHaversineDistance($coords[$i][0], $coords[$i][1], $coords[$i+1][0], $coords[$i+1][1]);
                    }

                    DB::table('gis_elements')->insert([
                        'project_id' => $projectId,
                        'category' => 'line',
                        'element_type' => $elType,
                        'name' => $name,
                        'coordinates' => json_encode($coords),
                        'length_meters' => round($dist, 2),
                        'line_width' => $elType === 'feeder' ? 4.5 : ($elType === 'distribution' ? 3.5 : 2.5),
                        'line_dash' => $elType === 'dropcore' ? 'dashed' : 'solid',
                        'metadata' => json_encode(['description' => $desc, 'imported_from' => $file->getClientOriginalName()]),
                        'created_by' => $user,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $importedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengimpor {$importedCount} objek GIS dari {$file->getClientOriginalName()}",
            'importedCount' => $importedCount,
        ]);
    }

    /**
     * Export Project Elements as Google Earth KML
     */
    public function exportKml(Request $request, $projectId = null): Response
    {
        $projectId = $projectId ?: DB::table('gis_projects')->value('id') ?: 1;
        $project = DB::table('gis_projects')->where('id', $projectId)->first();
        $projectName = $project ? $project->name : 'FTTH_Network';

        $elements = DB::table('gis_elements')->where('project_id', $projectId)->get();

        $kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
        $kml .= '  <Document>' . "\n";
        $kml .= '    <name>' . htmlspecialchars($projectName) . '</name>' . "\n";
        $kml .= '    <description>IMS Router - GIS Network Builder Export</description>' . "\n";

        // Styles
        $kml .= '    <Style id="feederLine"><LineStyle><color>ff1e1eb4</color><width>4</width></LineStyle></Style>' . "\n";
        $kml .= '    <Style id="distLine"><LineStyle><color>ffe57808</color><width>3</width></LineStyle></Style>' . "\n";
        $kml .= '    <Style id="dropLine"><LineStyle><color>ff0b9ef5</color><width>2</width></LineStyle></Style>' . "\n";

        // Markers Folder
        $kml .= '    <Folder>' . "\n";
        $kml .= '      <name>Titik & Node Jaringan</name>' . "\n";
        foreach ($elements->where('category', 'marker') as $el) {
            $kml .= '      <Placemark>' . "\n";
            $kml .= '        <name>' . htmlspecialchars($el->name) . '</name>' . "\n";
            $kml .= '        <description>' . htmlspecialchars("Tipe: {$el->element_type}") . '</description>' . "\n";
            $kml .= '        <Point>' . "\n";
            $kml .= "          <coordinates>{$el->longitude},{$el->latitude},0</coordinates>\n";
            $kml .= '        </Point>' . "\n";
            $kml .= '      </Placemark>' . "\n";
        }
        $kml .= '    </Folder>' . "\n";

        // Lines Folder
        $kml .= '    <Folder>' . "\n";
        $kml .= '      <name>Jalur Kabel Fiber</name>' . "\n";
        foreach ($elements->where('category', 'line') as $el) {
            $coords = $el->coordinates ? json_decode($el->coordinates, true) : [];
            if (!empty($coords)) {
                $coordStrList = [];
                foreach ($coords as $c) {
                    $coordStrList[] = "{$c[1]},{$c[0]},0";
                }
                $styleId = $el->element_type === 'feeder' ? '#feederLine' : ($el->element_type === 'dropcore' ? '#dropLine' : '#distLine');
                $kml .= '      <Placemark>' . "\n";
                $kml .= '        <name>' . htmlspecialchars($el->name) . '</name>' . "\n";
                $kml .= '        <styleUrl>' . $styleId . '</styleUrl>' . "\n";
                $kml .= '        <description>' . htmlspecialchars("Panjang: ~{$el->length_meters} meter") . '</description>' . "\n";
                $kml .= '        <LineString>' . "\n";
                $kml .= '          <tessellate>1</tessellate>' . "\n";
                $kml .= '          <coordinates>' . implode(' ', $coordStrList) . '</coordinates>' . "\n";
                $kml .= '        </LineString>' . "\n";
                $kml .= '      </Placemark>' . "\n";
            }
        }
        $kml .= '    </Folder>' . "\n";

        $kml .= '  </Document>' . "\n";
        $kml .= '</kml>';

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $projectName);
        $fileName = "FTTH_Export_{$safeName}_" . date('Ymd_His') . ".kml";

        return response($kml, 200, [
            'Content-Type' => 'application/vnd.google-earth.kml+xml',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Helper to calculate Haversine distance between 2 coordinates in meters
     */
    private function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Halaman Cek Coverage Lokasi ke ODP Terdekat (GIS Dropcore Routing)
     */
    public function coverage(Request $request): View
    {
        $allOdpsFromDb = DB::table('m_odp')->get();
        $odps = $allOdpsFromDb->map(function ($odp, $idx) {
            $lat = !empty($odp->latitude) ? (float) $odp->latitude : null;
            $lng = !empty($odp->longitude) ? (float) $odp->longitude : null;

            // Extract coordinates from note_odp if latitude/longitude is null
            if (($lat === null || $lng === null) && !empty($odp->note_odp) && preg_match('/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/', $odp->note_odp, $matches)) {
                $lat = (float) $matches[1];
                $lng = (float) $matches[2];
            }

            // Fallback coordinate if still empty
            if ($lat === null || $lng === null) {
                $baseLat = -6.936988;
                $baseLng = 107.5904512;
                $lat = $baseLat + (($idx % 4) * 0.002 - 0.003);
                $lng = $baseLng + ((floor($idx / 4) % 4) * 0.002 - 0.003);
            }

            $used = (int) ($odp->used_ports ?? 0);
            $max = (int) ($odp->capacity_odp ?: 16);
            $name = $odp->name_odp ?: $odp->kode_odp;
            $code = $odp->kode_odp ?: '-';

            return [
                'kode_odp' => $code,
                'name_odp' => $name,
                'code' => $code,
                'name' => $name,
                'display_name' => "{$name} ({$code})",
                'kode_pon' => $odp->kode_pon ?? '-',
                'pon_name' => $odp->kode_pon ?? '-',
                'capacity_odp' => $max,
                'total_ports' => $max,
                'used_ports' => $used,
                'has_slot' => $used < $max,
                'latitude' => $lat,
                'longitude' => $lng,
                'lat' => $lat,
                'lng' => $lng,
                'note_odp' => $odp->note_odp ?? '',
                'note' => $odp->note_odp ?? '',
                'status' => $odp->status ?? 'active',
            ];
        });

        $tickets = Schema::hasTable('trx_coverage_area')
            ? DB::table('trx_coverage_area')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
            : collect();

        $initialCoord = $request->query('coord', '-6.936988, 107.5904512');
        $selectedTicketId = $request->query('ticket_id');

        return view('teknik.coverage', [
            'user' => $request->user(),
            'odps' => $odps,
            'tickets' => $tickets,
            'initialCoord' => $initialCoord,
            'selectedTicketId' => $selectedTicketId,
        ]);
    }

    /**
     * Update status tiket permintaan coverage dari WhatsApp / Pelanggan
     */
    public function updateCoverageTicketStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id_message' => 'required|string',
            'status' => 'required|string',
            'note' => 'nullable|string',
        ]);

        if (!Schema::hasTable('trx_coverage_area')) {
            return response()->json(['success' => false, 'message' => 'Tabel trx_coverage_area tidak ditemukan'], 404);
        }

        $ticket = DB::table('trx_coverage_area')->where('id_message', $request->id_message)->first();
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
        }

        DB::table('trx_coverage_area')->where('id_message', $request->id_message)->update([
            'status' => $request->status,
            'note' => $request->note ?? $ticket->note,
            'user_update' => auth()->user()?->nama ?? 'Teknisi',
            'date_update' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status tiket {$request->id_message} berhasil diperbarui menjadi {$request->status}!",
        ]);
    }
}

