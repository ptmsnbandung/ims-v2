<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view.
     */
    public function index(Request $request): View
    {
        /** @var Pengguna $user */
        $user = $request->user();
        $user->loadMissing(['level', 'karyawan']);

        $financeStats = [
            'currentMonth' => date('m'),
            'currentYear' => (string) date('Y'),
            'draftCount' => 0,
            'draftAmount' => 0,
            'publishCount' => 0,
            'publishAmount' => 0,
            'paidCount' => 0,
            'paidAmount' => 0,
            'totalRegPending' => 0,
            'recentInvoices' => collect([]),
        ];

        if ($user->isFinance() || $user->isDirektur()) {
            try {
                $currentMonth = date('m');
                $currentYear = (string) date('Y');

                $draftCount = 0;
                $draftAmount = 0;
                $publishCount = 0;
                $publishAmount = 0;
                $paidCount = 0;
                $paidAmount = 0;
                $totalRegPending = 0;
                $recentInvoices = collect([]);

                // Hitung statistik invoice bulanan
                if (DB::getSchemaBuilder()->hasTable('view_billing_layanan') || DB::getSchemaBuilder()->hasTable('trx_billing_layanan')) {
                    $table = DB::getSchemaBuilder()->hasTable('view_billing_layanan') ? 'view_billing_layanan' : 'trx_billing_layanan';
                    
                    $kpiQuery = DB::table($table);
                    if (DB::getSchemaBuilder()->hasColumn($table, 'bulan_tagihan')) {
                        $kpiQuery->where('bulan_tagihan', $currentMonth);
                    }
                    if (DB::getSchemaBuilder()->hasColumn($table, 'tahun_tagihan')) {
                        $kpiQuery->where('tahun_tagihan', $currentYear);
                    }

                    $draftCount = (clone $kpiQuery)->whereIn('status_bill_lay', ['11', '12'])->count();
                    $draftAmount = (clone $kpiQuery)->whereIn('status_bill_lay', ['11', '12'])->sum('total_layanan');

                    $publishCount = (clone $kpiQuery)->where('status_bill_lay', '13')->count();
                    $publishAmount = (clone $kpiQuery)->where('status_bill_lay', '13')->sum('total_layanan');

                    $paidCount = (clone $kpiQuery)->where('status_bill_lay', '15')->count();
                    $paidAmount = (clone $kpiQuery)->where('status_bill_lay', '15')->sum('total_layanan');

                    $recentInvoices = DB::table($table)
                        ->orderBy('date_create', 'desc')
                        ->limit(5)
                        ->get();
                }

                if (DB::getSchemaBuilder()->hasTable('view_billing_reg') || DB::getSchemaBuilder()->hasTable('trx_billing_reg')) {
                    $regTable = DB::getSchemaBuilder()->hasTable('view_billing_reg') ? 'view_billing_reg' : 'trx_billing_reg';
                    $totalRegPending = DB::table($regTable)->whereIn('status_bill_reg', ['11', '12', '13'])->count();
                }

                $financeStats = [
                    'currentMonth' => $currentMonth,
                    'currentYear' => $currentYear,
                    'draftCount' => $draftCount,
                    'draftAmount' => $draftAmount,
                    'publishCount' => $publishCount,
                    'publishAmount' => $publishAmount,
                    'paidCount' => $paidCount,
                    'paidAmount' => $paidAmount,
                    'totalRegPending' => $totalRegPending,
                    'recentInvoices' => $recentInvoices,
                ];
            } catch (\Throwable $e) {
                // Fail-safe default if database views are not present
            }
        }

        return view('dashboard', [
            'user' => $user,
            'financeStats' => $financeStats,
        ]);
    }
}

