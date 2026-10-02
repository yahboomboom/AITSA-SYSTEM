<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Saves a link's preview picture (its og:image / twitter:image) so the
 * dashboard can show it for sites that refuse to be embedded, e.g. Facebook
 * posts. Fetched once at posting time and stored locally because Facebook's
 * image URLs expire. Never throws: a failure just means "no picture".
 */
class LinkPreviewImageFetcher
{
    // Link-preview crawlers get the og:image even when the page itself asks
    // visitors to log in (Facebook serves its preview tags to this agent).
    private const USER_AGENT = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    /** @var callable(string): (string[]|false) host -> IPv4 list */
    private $resolver;

    /** @var array<string, string> host => the public IP it was checked against */
    private array $checkedIps = [];

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver ?? fn (string $host) => gethostbynamel($host);
    }

    /** @return string|null stored path on the local disk */
    public function fetch(string $pageUrl): ?string
    {
        try {
            if (! $this->isPublicHttpUrl($pageUrl)) {
                return null;
            }

            $page = $this->client($pageUrl)->timeout(6)->get($pageUrl);
            if (! $page->successful()) {
                return null;
            }

            $imageUrl = $this->previewImageUrl($page->body(), $pageUrl);
            if (! $imageUrl || ! $this->isPublicHttpUrl($imageUrl)) {
                return null;
            }

            $image = $this->client($imageUrl)->timeout(8)->get($imageUrl);
            $type = strtolower(trim(explode(';', (string) $image->header('Content-Type'))[0]));
            if (! $image->successful() || ! isset(self::IMAGE_TYPES[$type]) || strlen($image->body()) > self::MAX_BYTES) {
                return null;
            }

            $path = 'announcements/link-' . Str::random(32) . '.' . self::IMAGE_TYPES[$type];
            \App\Support\Uploads::files()->put($path, $image->body());

            return $path;
        } catch (\Throwable $e) {
            Log::info('Link preview image not fetched for ' . $pageUrl . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * HTTP client that re-checks every redirect hop: a public URL must not be
     * able to bounce the server to localhost or a private network address.
     */
    private function client(string $url)
    {
        // Pin the connection to the IP that passed the public-address check,
        // so the hostname can't re-resolve to a private address in between.
        $curl = [];
        $host = (string) parse_url($url, PHP_URL_HOST);
        $ip = $this->checkedIps[$host] ?? null;
        if ($ip && ! filter_var($host, FILTER_VALIDATE_IP) && defined('CURLOPT_RESOLVE')) {
            $port = parse_url($url, PHP_URL_PORT) ?: (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);
            $curl = ['curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]]];
        }

        return Http::withHeaders(['User-Agent' => self::USER_AGENT])->withOptions($curl + [
            'allow_redirects' => [
                'max' => 3,
                'protocols' => ['http', 'https'],
                'on_redirect' => function ($request, $response, $uri) {
                    if (! $this->isPublicHttpUrl((string) $uri)) {
                        throw new \RuntimeException('Redirect to a non-public address blocked.');
                    }
                },
            ],
        ]);
    }

    private function previewImageUrl(string $html, string $pageUrl): ?string
    {
        foreach (['og:image:secure_url', 'og:image', 'twitter:image'] as $key) {
            $pattern = '/<meta[^>]+(?:property|name)=["\']' . preg_quote($key, '/') . '["\'][^>]*content=["\']([^"\']+)["\']/i';
            $patternReversed = '/<meta[^>]+content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\']' . preg_quote($key, '/') . '["\']/i';
            if (preg_match($pattern, $html, $m) || preg_match($patternReversed, $html, $m)) {
                $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
                if (str_starts_with($url, '//')) {
                    $url = 'https:' . $url;
                } elseif (str_starts_with($url, '/')) {
                    $url = parse_url($pageUrl, PHP_URL_SCHEME) . '://' . parse_url($pageUrl, PHP_URL_HOST) . $url;
                }

                return $url;
            }
        }

        return null;
    }

    /** Only public http(s) hosts — never localhost or private/reserved networks. */
    private function isPublicHttpUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || strtolower($host) === 'localhost') {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (($this->resolver)($host) ?: []);
        if ($ips === []) {
            return false;
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        $this->checkedIps[$host] = $ips[0];

        return true;
    }
}
