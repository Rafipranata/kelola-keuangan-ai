<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $now = Carbon::now();
        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        // All time total balance (Semua pemasukan - Semua pengeluaran)
        $allIncomes = Transaction::where('user_id', $user->id)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'income'))
            ->sum('nominal');

        $allExpenses = Transaction::where('user_id', $user->id)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'expense'))
            ->sum('nominal');

        $totalSaldo = (float) ($allIncomes - $allExpenses);

        // Bulan berjalan / terpilih
        $monthlyIncomes = (float) Transaction::where('user_id', $user->id)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'income'))
            ->sum('nominal');

        $monthlyExpenses = (float) Transaction::where('user_id', $user->id)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'expense'))
            ->sum('nominal');

        // Anggaran bulan terpilih
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $budgetSummaries = [];
        $totalBatasAnggaran = 0;
        $totalAnggaranTerpakai = 0;

        foreach ($budgets as $budget) {
            $terpakai = (float) Transaction::where('user_id', $user->id)
                ->where('category_id', $budget->category_id)
                ->whereYear('tanggal_transaksi', $tahun)
                ->whereMonth('tanggal_transaksi', $bulan)
                ->sum('nominal');

            $batas = (float) $budget->batas_anggaran;
            $persentase = $batas > 0 ? round(($terpakai / $batas) * 100, 1) : 0;

            $totalBatasAnggaran += $batas;
            $totalAnggaranTerpakai += $terpakai;

            $budgetSummaries[] = [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'kategori_nama' => $budget->category->nama,
                'kategori_ikon' => $budget->category->ikon,
                'kategori_warna' => $budget->category->warna,
                'batas_anggaran' => $batas,
                'terpakai' => $terpakai,
                'sisa' => max(0, $batas - $terpakai),
                'persentase' => $persentase,
            ];
        }

        $sisaAnggaranTotal = max(0, $totalBatasAnggaran - $totalAnggaranTerpakai);
        $persentaseAnggaranTotal = $totalBatasAnggaran > 0 ? round(($totalAnggaranTerpakai / $totalBatasAnggaran) * 100, 1) : 0;

        // Pengeluaran per kategori bulan terpilih (Chart / List)
        $expenseByCategory = Transaction::where('transactions.user_id', $user->id)
            ->whereYear('transactions.tanggal_transaksi', $tahun)
            ->whereMonth('transactions.tanggal_transaksi', $bulan)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.tipe', 'expense')
            ->selectRaw('categories.id, categories.nama, categories.ikon, categories.warna, SUM(transactions.nominal) as total')
            ->groupBy('categories.id', 'categories.nama', 'categories.ikon', 'categories.warna')
            ->orderByDesc('total')
            ->get();
        $results = Transaction::where('transactions.user_id', $user->id)
            ->whereYear('transactions.tanggal_transaksi', $tahun)
            ->whereMonth('transactions.tanggal_transaksi', $bulan)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.tipe', 'expense')
            ->selectRaw('categories.id, categories.nama, categories.ikon, categories.warna, SUM(transactions.nominal) as total')
            ->groupBy('categories.id', 'categories.nama', 'categories.ikon', 'categories.warna')
            ->orderByDesc('total')
            ->get();

        /** @var \Illuminate\Support\Collection<int, object> $results */
        $expenseByCategory = $results->map(fn (object $item) => [
            'id' => $item->id,
            'nama' => $item->nama,
            'ikon' => $item->ikon,
            'warna' => $item->warna,
            'total' => (float) $item->total,
            'persentase' => $monthlyExpenses > 0 ? round(((float) $item->total / $monthlyExpenses) * 100, 1) : 0,
        ]);

        // 10 Transaksi terbaru
        $recentTransactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Daftar kategori user & sistem untuk modal tambah cepat
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->orderBy('nama')->get();

        return Inertia::render('Dashboard', [
            'filter' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            'summary' => [
                'total_saldo' => $totalSaldo,
                'pemasukan_bulan_ini' => $monthlyIncomes,
                'pengeluaran_bulan_ini' => $monthlyExpenses,
                'total_batas_anggaran' => $totalBatasAnggaran,
                'total_anggaran_terpakai' => $totalAnggaranTerpakai,
                'sisa_anggaran_total' => $sisaAnggaranTotal,
                'persentase_anggaran_total' => $persentaseAnggaranTotal,
            ],
            'budgets' => $budgetSummaries,
            'expenses_by_category' => $expenseByCategory,
            'recent_transactions' => $recentTransactions,
            'categories' => $categories,
        ]);
    }
}
