<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\GeminiFinanceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AiFinanceController extends Controller
{
    public function __construct(
        protected GeminiFinanceService $geminiService
    ) {}

    /**
     * Parse free text transaction with Gemini and save to transactions table.
     * Mendukung multi-transaksi sekaligus (misal: "beli bensin 100rb dan beli kopi 5rb").
     */
    public function parseTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $prompt = $request->input('prompt');

        // Ambil daftar kategori yang relevan untuk user
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        $categoryNames = $categories->pluck('nama')->toArray();

        try {
            $parsedItems = $this->geminiService->parseTransaction($prompt, $categoryNames);

            $savedTransactions = [];

            foreach ($parsedItems as $parsed) {
                // Cocokkan nama kategori dengan database
                $matchedCategory = $categories->first(function ($cat) use ($parsed) {
                    return strcasecmp($cat->nama, $parsed['kategori'] ?? '') === 0;
                });

                // Jika tidak cocok persis, cari berdasarkan substring atau tipe
                if (! $matchedCategory) {
                    $matchedCategory = $categories->first(function ($cat) use ($parsed) {
                        return stripos($cat->nama, $parsed['kategori'] ?? '') !== false
                            || stripos($parsed['kategori'] ?? '', $cat->nama) !== false;
                    });
                }

                // Fallback kategori berdasarkan tipe
                if (! $matchedCategory) {
                    $tipe = $parsed['tipe'] ?? 'expense';
                    $matchedCategory = $categories->firstWhere('tipe', $tipe) ?? $categories->first();
                }

                // Simpan transaksi
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'category_id' => $matchedCategory->id,
                    'nominal' => $parsed['nominal'],
                    'deskripsi' => $parsed['deskripsi'] ?? $prompt,
                    'tanggal_transaksi' => $parsed['tanggal_transaksi'] ?? Carbon::now()->format('Y-m-d'),
                    'metode_pembayaran' => $parsed['metode_pembayaran'] ?? 'Cash',
                    'is_ai_generated' => true,
                ]);

                $transaction->load('category');
                $savedTransactions[] = $transaction;
            }

            $count = count($savedTransactions);
            $message = $count > 1
                ? "{$count} transaksi berhasil dicatat otomatis oleh AI!"
                : 'Transaksi berhasil dicatat oleh AI!';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $savedTransactions[0] ?? null,
                'transactions' => $savedTransactions,
                'parsed' => $parsedItems,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memproses dengan AI.',
            ], 422);
        }
    }

    /**
     * Get financial advice from Gemini based on user's current month aggregate data.
     */
    public function getAdvice(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();
        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        // Agregat bulan berjalan
        $totalIncome = (float) Transaction::where('user_id', $user->id)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'income'))
            ->sum('nominal');

        $totalExpense = (float) Transaction::where('user_id', $user->id)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereHas('category', fn ($q) => $q->where('tipe', 'expense'))
            ->sum('nominal');

        $topExpenseCategories = Transaction::where('transactions.user_id', $user->id)
            ->whereYear('transactions.tanggal_transaksi', $tahun)
            ->whereMonth('transactions.tanggal_transaksi', $bulan)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.tipe', 'expense')
            ->selectRaw('categories.nama, SUM(transactions.nominal) as total')
            ->groupBy('categories.nama')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn ($c) => $c->nama.': Rp '.number_format((float) $c->total, 0, ',', '.'))
            ->toArray();

        // Budget status
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $budgetStatus = [];
        foreach ($budgets as $b) {
            $spent = (float) Transaction::where('user_id', $user->id)
                ->where('category_id', $b->category_id)
                ->whereYear('tanggal_transaksi', $tahun)
                ->whereMonth('tanggal_transaksi', $bulan)
                ->sum('nominal');

            $pct = $b->batas_anggaran > 0 ? round(($spent / $b->batas_anggaran) * 100) : 0;
            $budgetStatus[] = "{$b->category->nama}: Terpakai Rp ".number_format($spent, 0, ',', '.').' / Limit Rp '.number_format((float) $b->batas_anggaran, 0, ',', '.')." ({$pct}%)";
        }

        $summary = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_savings' => $totalIncome - $totalExpense,
            'top_expense_categories' => $topExpenseCategories,
            'budget_status' => $budgetStatus,
        ];

        try {
            $advice = $this->geminiService->generateFinancialAdvice($summary);

            return response()->json([
                'success' => true,
                'advice' => $advice,
                'summary' => $summary,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memproses nasihat AI.',
            ], 422);
        }
    }
}
