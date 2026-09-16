<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSubmissionItem extends Model
{
    protected $fillable = ['grade_submission_id', 'user_id', 'final_grade', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
