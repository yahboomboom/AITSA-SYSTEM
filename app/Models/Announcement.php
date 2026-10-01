<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'posted_by', 'is_active', 'attachment_path', 'attachment_name', 'link_url', 'link_image_path'];

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

    /**
     * How the dashboard should show this announcement's link:
     *  - embed: a URL the viewer frame can load (YouTube, Google Drive/Docs/
     *           Sheets/Slides previews, or a direct PDF)
     *  - image: a direct image link, shown in the zoomable image viewer
     *  - card:  anything else (most sites refuse to be embedded)
     *
     * @return array{type: 'embed'|'image'|'card', src: ?string, host: ?string}|null
     */
    public function linkPreview(): ?array
    {
        $url = $this->link_url;
        if (! $url) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)));
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $embed = fn (string $src) => ['type' => 'embed', 'src' => $src, 'host' => $host];

        // YouTube: watch?v=ID, youtu.be/ID, /shorts/ID, /embed/ID
        $videoId = null;
        if (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            $videoId = $query['v'] ?? (preg_match('#^/(?:shorts|embed|live)/([\w-]{6,})#', $path, $m) ? $m[1] : null);
        } elseif ($host === 'youtu.be') {
            $videoId = trim($path, '/') ?: null;
        }
        if ($videoId && preg_match('/^[\w-]{6,}$/', $videoId)) {
            return $embed('https://www.youtube.com/embed/' . $videoId);
        }

        // Google Drive file / Docs / Sheets / Slides -> their /preview page
        if ($host === 'drive.google.com' && preg_match('#^/file/d/([\w-]+)#', $path, $m)) {
            return $embed('https://drive.google.com/file/d/' . $m[1] . '/preview');
        }
        if ($host === 'docs.google.com' && preg_match('#^/(document|spreadsheets|presentation)/d/([\w-]+)#', $path, $m)) {
            return $embed('https://docs.google.com/' . $m[1] . '/d/' . $m[2] . '/preview');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return $embed($url);
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return ['type' => 'image', 'src' => $url, 'host' => $host];
        }

        return ['type' => 'card', 'src' => null, 'host' => $host];
    }
}
