<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceItem extends Model
{
    use HasFactory;

    protected $fillable = ['clearance_id', 'department_id', 'status', 'remarks', 'signed_by', 'signed_at'];

    protected $casts = ['signed_at' => 'datetime'];

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
