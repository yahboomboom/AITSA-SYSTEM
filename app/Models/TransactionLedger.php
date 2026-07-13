<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLedger extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reference_no',
        'amount',
        'status',
        'gateway',
        'checkout_session_id',
        'paid_at',
        'processed_by',
        'remarks'
    ];

    protected $casts = ['paid_at' => 'datetime'];

    /**
     * Connect back to the student user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Connect to the Staff / Cashier who processed this ledger item.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}