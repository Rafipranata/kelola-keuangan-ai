<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $now = Carbon::now();
        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $budgetSummaries = [];
        $totalBatas = 0;
        $totalTerpakai = 0;

        foreach ($budgets as $budget) {
            $terpakai = (float) Transaction::where('user_id', $user->id)
                ->where('category_id', $budget->category_id)
                ->whereYear('tanggal_transaksi', $tahun)
                ->whereMonth('tanggal_transaksi', $bulan)
                ->sum('nominal');

            $batas = (float) $budget->batas_anggaran;
            $persentase = $batas > 0 ? round(($terpakai / $batas) * 100, 1) : 0;

            $totalBatas += $batas;
            $totalTerpakai += $terpakai;

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

        // Hanya kategori tipe expense yang bisa dibudgetkan
        $expenseCategories = Category::where('tipe', 'expense')
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->orderBy('nama')
            ->get();

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgetSummaries,
            'expense_categories' => $expenseCategories,
            'filter' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            'total_batas' => $totalBatas,
            'total_terpakai' => $totalTerpakai,
            'total_sisa' => max(0, $totalBatas - $totalTerpakai),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $user = $request->user();

        Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
            ],
            [
                'batas_anggaran' => $request->batas_anggaran,
            ]
        );

        return redirect()->back()->with('success', 'Anggaran berhasil disimpan.');
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $user = $request->user();

        if ($budget->user_id !== $user->id) {
            abort(403, 'Akses tidak sah');
        }

        $budget->delete();

        return redirect()->back()->with('success', 'Anggaran berhasil dihapus.');
    }
}
