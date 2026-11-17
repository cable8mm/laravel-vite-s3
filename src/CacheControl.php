<?php

namespace Cable8mm\LaravelViteS3;

/**
 * Resolves the Cache-Control header for a file in the build output,
 * based on which top-level directory it lives in.
 */
final class CacheControl
{
    /**
     * @param  array<string, mixed>|false  $config  The `vite-s3.cache_control` config value.
     *                                              Pass `false` to disable headers entirely.
     */
    public function __construct(private readonly array|false $config) {}

    /**
     * Create an instance from the package configuration.
     */
    public static function fromConfig(): self
    {
        $config = config('vite-s3.cache_control', []);

        return new self(is_array($config) ? $config : ($config === false ? false : []));
    }

    /**
     * Get the Cache-Control header value for a file, or null when the
     * header should not be set.
     *
     * @param  string  $relativePath  Path of the file relative to the source disk root.
     */
    public function forPath(string $relativePath): ?string
    {
        if ($this->config === false) {
            return null;
        }

        $immutableDirectories = (array) ($this->config['immutable_directories'] ?? ['assets']);
        $firstSegment = explode('/', ltrim($relativePath, '/'))[0] ?? '';

        if ($firstSegment !== '' && in_array($firstSegment, $immutableDirectories, true)) {
            return $this->config['immutable'] ?? null;
        }

        return $this->config['default'] ?? null;
    }

    /**
     * Get the put() options for a file. Empty when headers are disabled.
     *
     * @return array<string, string>
     */
    public function optionsFor(string $relativePath): array
    {
        $cacheControl = $this->forPath($relativePath);

        return $cacheControl === null ? [] : ['CacheControl' => $cacheControl];
    }
}
