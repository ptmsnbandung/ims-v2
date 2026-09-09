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

        $financeStats = [];
        if ($user->isFinance() || $user->isDirektur()) {
            $currentMonth = date('m');
            $currentYear = (string) date('Y');

            // Hitung statistik invoice bulan ini
            $kpiQuery = DB::table('view_billing_layanan')
                ->where('bulan_tagihan', $currentMonth)
                ->where('tahun_tagihan', $currentYear);

            $draftCount = (clone $kpiQuery)->whereIn('status_bill_lay', ['11', '12'])->count();
            $draftAmount = (clone $kpiQuery)->whereIn('status_bill_lay', ['11', '12'])->sum('total_layanan');

            $publishCount = (clone $kpiQuery)->where('status_bill_lay', '13')->count();
            $publishAmount = (clone $kpiQuery)->where('status_bill_lay', '13')->sum('total_layanan');

            $paidCount = (clone $kpiQuery)->where('status_bill_lay', '15')->count();
            $paidAmount = (clone $kpiQuery)->where('status_bill_lay', '15')->sum('total_layanan');

            $totalRegPending = DB::table('view_billing_reg')->whereIn('status_bill_reg', ['11', '12', '13'])->count();

            $recentInvoices = DB::table('view_billing_layanan')
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

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
        }

        return view('dashboard', [
            'user' => $user,
            'financeStats' => $financeStats,
        ]);
    }
}

