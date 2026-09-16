<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatriculationChangeItem extends Model
{
    protected $fillable = ['matriculation_change_id', 'action', 'section_id', 'replaced_section_id'];

    public function change(): BelongsTo
    {
        return $this->belongsTo(MatriculationChange::class, 'matriculation_change_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function replacedSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'replaced_section_id');
    }
}
