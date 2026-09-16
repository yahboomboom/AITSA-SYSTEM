<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::where('key', $key)->value('value');
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }

    /**
     * Clear the request-scoped memoization cache. RefreshDatabase resets the database between
     * tests but not PHP static properties, so the test suite calls this between tests to avoid
     * one test's Setting values bleeding into another's via the in-memory cache.
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }
}
