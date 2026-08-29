<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSubmission extends Model
{
    use HasFactory;

    public const TYPES = [
        'form137' => 'Form 137 — Permanent Record / Senior HS Report Card',
        'form138' => 'Form 138 — Report Card',
        'birth_cert' => 'PSA Birth Certificate',
        'good_moral' => 'Certificate of Good Moral Character',
        'id_photo_2x2' => '2x2 ID Photo',
        'other' => 'Other Supporting Document',
    ];

    protected $fillable = [
        'user_id', 'document_type', 'notes', 'file_path', 'original_name',
'mime_type', 'size', 'status', 'remarks', 'reviewed_by', 'reviewed_at', 'signed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type] ?? $this->document_type;
    }
}
