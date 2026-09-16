<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'percent'];

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'discount_type_id');
    }
}
