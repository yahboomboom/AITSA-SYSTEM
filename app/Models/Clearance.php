<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clearance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'admission_status',
        'chair_status',
        'cashier_status',
        'registrar_status',
        'remarks',
    ];

    /**
     * Connect back to the student user.
     * Establishes the inverse 1-to-1 relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClearanceItem::class);
    }

    public function allItemsApproved(): bool
    {
        return $this->items->isEmpty() || $this->items->every(fn (ClearanceItem $item) => $item->status === 'Approved');
    }
}
