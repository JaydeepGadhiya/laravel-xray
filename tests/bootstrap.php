<?php

/**
 * Test bootstrap for Unit tests (no Laravel).
 *
 * Provides stub implementations of Laravel helper functions used by the
 * analyzers and reporters so that unit tests can run without a full
 * Laravel application container.
 *
 * Also provides PHP 8.0+ polyfills so the test suite runs on PHP 7.4.
 */

// PHP 8.0+ string function polyfills for PHP 7.4 compatibility
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || (strlen($haystack) >= strlen($needle) && substr($haystack, -strlen($needle)) === $needle);
    }
}

if (!function_exists('config')) {
    /**
     * Stub for Laravel's config() helper.
     * Returns the default value for all config keys in the unit test context.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        return $default;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app/' . ltrim($path, '/'));
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return base_path('resources/' . ltrim($path, '/'));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('now')) {
    function now(): object
    {
        return new class {
            public function toIso8601String(): string
            {
                return date('c');
            }
        };
    }
}
