<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectCreditItem extends Model
{
    protected $fillable = ['subject_credit_request_id', 'subject_code', 'subject_title', 'final_grade'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SubjectCreditRequest::class, 'subject_credit_request_id');
    }
}
