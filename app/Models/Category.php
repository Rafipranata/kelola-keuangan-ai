<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $nama
 * @property string $tipe
 * @property string $ikon
 * @property string $warna
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'tipe',
        'ikon',
        'warna',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /** @return BelongsTo<User, Category> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Transaction, Category> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Budget, Category> */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
