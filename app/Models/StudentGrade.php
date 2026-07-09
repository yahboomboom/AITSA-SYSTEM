<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    protected $fillable = ['user_id', 'subject_code', 'status', 'final_grade'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
