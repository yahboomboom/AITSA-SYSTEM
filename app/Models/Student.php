<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    // Allow mass-assignment for these fields
    protected $fillable = [
        'user_id', // Foreign key connecting back to the users table
        'academic_level',
        'strand_id',
        'program_id',
        'year_level',
        'learning_modality',
    ];

    /**
     * Relationship: A student profile belongs to a core User account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: A student profile belongs to an SHS Strand.
     */
    public function strand(): BelongsTo
    {
        return $this->belongsTo(ShsStrand::class, 'strand_id');
    }
}