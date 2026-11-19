<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property float $nominal
 * @property string $deskripsi
 * @property Carbon $tanggal_transaksi
 * @property string $metode_pembayaran
 * @property bool $is_ai_generated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'nominal',
        'deskripsi',
        'tanggal_transaksi',
        'metode_pembayaran',
        'is_ai_generated',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_transaksi' => 'date:Y-m-d',
        'is_ai_generated' => 'boolean',
        'user_id' => 'integer',
        'category_id' => 'integer',
    ];

    /** @return BelongsTo<User, Transaction> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, Transaction> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
