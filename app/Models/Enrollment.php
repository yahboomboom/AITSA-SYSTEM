<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'school_year', 'semester', 'type', 'status', 'block_label', 'remarks',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'enrollment_subjects');
    }
}
