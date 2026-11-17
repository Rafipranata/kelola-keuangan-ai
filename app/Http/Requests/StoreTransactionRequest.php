<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'tanggal_transaksi' => ['required', 'date'],
            'metode_pembayaran' => ['nullable', 'string', 'max:50'],
        ];
    }
}
