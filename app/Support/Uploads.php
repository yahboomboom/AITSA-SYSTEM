<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Where user uploads live. Railway containers have no persistent disk, so
 * production sets UPLOADS_DISK=s3; locally everything stays on disk.
 *
 * - files(): student documents, announcement attachments + link images
 * - signatures(): drawn signatures (staff sign-offs, enrollment agreements)
 */
class Uploads
{
    public static function files(): Filesystem
    {
        return Storage::disk(config('filesystems.uploads_disk'));
    }

    public static function filesDisk(): string
    {
        return config('filesystems.uploads_disk');
    }

    public static function signatures(): Filesystem
    {
        return Storage::disk(config('filesystems.signatures_disk'));
    }

    /**
     * A signature PNG as a data: URI, so PDFs (dompdf) and pages can show it
     * whichever disk it's on. Null if there's no path or the file is gone.
     */
    public static function signatureDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            $binary = static::signatures()->get($path);
        } catch (\Throwable $e) {
            return null;
        }

        return $binary === null ? null : 'data:image/png;base64,' . base64_encode($binary);
    }
}
