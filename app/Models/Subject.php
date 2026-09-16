<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['program_id', 'code', 'title', 'units', 'year_level', 'semester', 'mode'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'subject_id', 'prerequisite_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
