<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'type'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }
}
