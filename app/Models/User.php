<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login_id',
        'email',
        'password',
        'role',
        'major',
        'year_level',
        'section',
        'contact_number',
        'date_of_birth',
        'sex',
        'address',
        'last_school',
        'year_graduated',
        'applicant_type',
        'program_level',
        'applicant_remarks',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Ensures Laravel auto-hashes password changes and checks hashes safely
    ];

    /**
     * Get the clearance record associated with the user.
     * Establishes a 1-to-1 relationship with the clearances table.
     */
    public function clearance(): HasOne
    {
        return $this->hasOne(Clearance::class, 'user_id');
    }

    /**
     * Get the specialized student profile data (SHS/College fields).
     * Establishes a 1-to-1 relationship with the students table.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }
}