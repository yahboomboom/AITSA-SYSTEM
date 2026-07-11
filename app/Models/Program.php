<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'level', 'years', 'is_enrollable'];

    protected $casts = ['is_enrollable' => 'boolean'];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
