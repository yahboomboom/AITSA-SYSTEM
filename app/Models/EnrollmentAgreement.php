<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentAgreement extends Model
{
    protected $fillable = ['user_id', 'signature_path', 'ip_address', 'user_agent', 'agreement_hash', 'signed_at'];

    protected $casts = ['signed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A row only ever exists once the student has actually signed. */
    public static function hasSigned(User $user): bool
    {
        return static::where('user_id', $user->id)->exists();
    }
}
