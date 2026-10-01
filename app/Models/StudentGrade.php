<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    // source: null for grades given in-system, 'credited' for subjects a
    // Chair approved from a transferee's/returnee's earlier records.
    protected $fillable = ['user_id', 'subject_code', 'status', 'final_grade', 'source'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
