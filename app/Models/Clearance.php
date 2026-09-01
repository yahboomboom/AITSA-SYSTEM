<?php

namespace App\Models;

use App\Notifications\ClearanceApprovalNeededNotification;
use App\Notifications\ClearancePendingNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clearance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'school_year',
        'semester',
        'admission_status',
        'chair_status',
        'cashier_status',
        'registrar_status',
        'remarks',
        'chair_signed_by',
        'chair_signed_at',
        'cashier_signed_by',
        'cashier_signed_at',
        'registrar_signed_by',
        'registrar_signed_at',
    ];
    
    protected $casts = [
        'chair_signed_at' => 'datetime',
        'cashier_signed_at' => 'datetime',
        'registrar_signed_at' => 'datetime',
    ];

    /**
     * Connect back to the student user.
     * Establishes the inverse 1-to-1 relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClearanceItem::class);
    }

    public function allItemsApproved(): bool
    {
        return $this->items->isEmpty() || $this->items->every(fn (ClearanceItem $item) => $item->status === 'Approved');
    }
  
    public function chairSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_signed_by');
    }

    public function cashierSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_signed_by');
    }

    public function registrarSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_signed_by');
    }

    public static function initializeFor(int $userId, string $schoolYear, int $semester, array $attributes = []): self
    {
        $existing = static::where('user_id', $userId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->first();
        if ($existing) {
            return $existing;
        }

        $clearance = static::create(array_merge(
            ['user_id' => $userId, 'school_year' => $schoolYear, 'semester' => $semester],
            $attributes
        ));

        foreach (Department::where('is_active', true)->get() as $department) {
            $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

            // Let every officer of this department (not students who happen to share
            // the same department_id) know a new item just landed in their queue.
            foreach ($department->officers()->where('role', 'department_officer')->get() as $officer) {
                \App\Support\SafeNotify::send($officer, new ClearanceApprovalNeededNotification($item, 'Department Clearance Queue'));
            }
        }

        // Let the student know their clearance process has started.
        $clearance->load('user');
        \App\Support\SafeNotify::send($clearance->user, new ClearancePendingNotification($clearance));

        return $clearance;
    }

    public static function currentFor(User $user): ?self
    {
        $current = static::where('user_id', $user->id)
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->first();

        if ($current) {
            return $current;
        }

        // TESDA students are never rolled forward by the Registrar's
        // College-only term-rollover action, so they'd otherwise have no
        // "current term" row at all after a rollover — fall back to their
        // most recent clearance instead of null, so staff actions/queues
        // keep working for them.
        if (strtoupper((string) $user->program_level) === 'TESDA') {
            return static::where('user_id', $user->id)->latest('id')->first();
        }

        return null;
    }
}
