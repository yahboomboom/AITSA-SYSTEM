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
        'chair_signed_by',
        'chair_signed_at',
        'cashier_signed_by',
        'cashier_signed_at',
        'registrar_signed_by',
        'registrar_signed_at',
    ];
    
    protected $casts = [
        'chair_signed_at' => 'datetime',
        'cashier_signed_at' => 'datetime',
        'registrar_signed_at' => 'datetime',
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
  
    public function chairSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_signed_by');
    }

    public function cashierSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_signed_by');
    }

    public function registrarSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_signed_by');
    }

    public static function initializeFor(int $userId, array $attributes = []): self
    {
        $existing = static::where('user_id', $userId)->first();
        if ($existing) {
            return $existing;
        }

        $clearance = static::create(array_merge(['user_id' => $userId], $attributes));

        foreach (Department::where('is_active', true)->get() as $department) {
            $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);
        }

        return $clearance;
    }
}
