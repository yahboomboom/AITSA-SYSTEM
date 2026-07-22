<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'discount_type_id',
        'department_id',
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

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function taughtSections(): HasMany
    {
        return $this->hasMany(Section::class, 'faculty_id');
    }

    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isIrregularStudent(): bool
    {
        return $this->grades()->where('status', 'Failed')->exists();
    }

    public function yearNumber(): int
    {
        return ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4][$this->year_level] ?? 1;
    }

    public function program(): ?Program
    {
        return Program::where('code', $this->major)->first();
    }

    /** @return string[] */
    public function passedSubjectCodes(): array
    {
        return $this->grades()->where('status', 'Passed')->pluck('subject_code')->all();
    }
}