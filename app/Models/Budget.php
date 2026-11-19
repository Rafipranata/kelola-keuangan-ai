<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property float $batas_anggaran
 * @property int $bulan
 * @property int $tahun
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'batas_anggaran',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'batas_anggaran' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
        'user_id' => 'integer',
        'category_id' => 'integer',
    ];

    /** @return BelongsTo<User, Budget> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, Budget> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
