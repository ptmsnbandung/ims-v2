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

                // Hitung statistik invoice bulanan (High performance single query)
                if (DB::getSchemaBuilder()->hasTable('trx_billing_layanan')) {
                    $kpi = DB::table('trx_billing_layanan')
                        ->where('bulan_tagihan', $currentMonth)
                        ->where('tahun_tagihan', $currentYear)
                        ->selectRaw("
                            COUNT(CASE WHEN status_bill_lay IN ('11', '12') THEN 1 END) as draft_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay IN ('11', '12') THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as draft_amount,
                            COUNT(CASE WHEN status_bill_lay = '13' THEN 1 END) as publish_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay = '13' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as publish_amount,
                            COUNT(CASE WHEN status_bill_lay = '15' THEN 1 END) as paid_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay = '15' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as paid_amount
                        ")
                        ->first();

                    $draftCount = (int) ($kpi->draft_count ?? 0);
                    $draftAmount = (float) ($kpi->draft_amount ?? 0);
                    $publishCount = (int) ($kpi->publish_count ?? 0);
                    $publishAmount = (float) ($kpi->publish_amount ?? 0);
                    $paidCount = (int) ($kpi->paid_count ?? 0);
                    $paidAmount = (float) ($kpi->paid_amount ?? 0);

                    $viewTable = DB::getSchemaBuilder()->hasTable('view_billing_layanan') ? 'view_billing_layanan' : 'trx_billing_layanan';
                    $recentInvoices = DB::table($viewTable)
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

