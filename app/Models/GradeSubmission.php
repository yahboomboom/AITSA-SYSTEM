<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeSubmission extends Model
{
    protected $fillable = [
        'section_id', 'faculty_id', 'status', 'remarks', 'rejected_by',
        'chair_id', 'chair_at', 'registrar_id', 'registrar_at', 'submitted_at',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected $casts = [
        'chair_at' => 'datetime',
        'registrar_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradeSubmissionItem::class);
    }
}
