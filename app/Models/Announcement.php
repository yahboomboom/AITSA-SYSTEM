<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'posted_by', 'is_active', 'attachment_path', 'attachment_name'];

    protected $casts = ['is_active' => 'boolean'];

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isImageAttachment(): bool
    {
        if (! $this->attachment_name) {
            return false;
        }

        $ext = strtolower(pathinfo($this->attachment_name, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
