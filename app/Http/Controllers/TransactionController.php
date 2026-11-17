<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $tipe = $request->input('tipe'); // 'income', 'expense', or null
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $query = Transaction::with('category')
            ->where('user_id', $user->id);

        if (! empty($bulan)) {
            $query->whereMonth('tanggal_transaksi', (int) $bulan);
        }

        if (! empty($tahun)) {
            $query->whereYear('tanggal_transaksi', (int) $tahun);
        }

        if (! empty($tipe)) {
            $query->whereHas('category', function ($q) use ($tipe) {
                $q->where('tipe', $tipe);
            });
        }

        if (! empty($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi', 'like', "%{$search}%")
                  ->orWhere('metode_pembayaran', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->orderBy('nama')->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'tipe' => $tipe,
                'category_id' => $categoryId,
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user = $request->user();

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'nominal' => $request->nominal,
            'deskripsi' => $request->deskripsi,
            'tanggal_transaksi' => $request->tanggal_transaksi,
            'metode_pembayaran' => $request->metode_pembayaran ?? 'Cash',
            'is_ai_generated' => false,
        ]);

        return redirect()->back()->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();

        if ($transaction->user_id !== $user->id) {
            abort(403, 'Akses tidak sah');
        }

        $transaction->update([
            'category_id' => $request->category_id,
            'nominal' => $request->nominal,
            'deskripsi' => $request->deskripsi,
            'tanggal_transaksi' => $request->tanggal_transaksi,
            'metode_pembayaran' => $request->metode_pembayaran ?? 'Cash',
        ]);

        return redirect()->back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();

        if ($transaction->user_id !== $user->id) {
            abort(403, 'Akses tidak sah');
        }

        $transaction->delete();

        return redirect()->back()->with('success', 'Transaksi berhasil dihapus.');
    }
}
