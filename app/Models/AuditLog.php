<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id', 'actor_name', 'action',
        'target_type', 'target_id', 'description', 'ip_address',
    ];

    // Call this anywhere to record an action
    public static function record(string $action, string $description, string $targetType = null, int $targetId = null): void
    {
        $actor = \Illuminate\Support\Facades\Auth::user();
        static::create([
            'actor_id'    => $actor?->id,
            'actor_name'  => $actor?->name ?? 'System',
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }
}
