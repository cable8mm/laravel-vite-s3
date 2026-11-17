<?php

namespace Cable8mm\LaravelViteS3;

/**
 * Resolves the Cache-Control header for a file in the build output,
 * based on which top-level directory it lives in.
 */
final class CacheControl
{
    /**
     * Package defaults, merged per key when the published config is missing values.
     *
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'immutable_directories' => ['assets'],
        'immutable' => 'public, max-age=31536000, immutable',
        'default' => 'public, max-age=300',
    ];

    /**
     * @param  array<string, mixed>|false  $config  The `vite-s3.cache_control` config value.
     *                                              Pass `false` to disable headers entirely.
     */
    public function __construct(private readonly array|false $config) {}

    /**
     * Create an instance from the package configuration.
     *
     * Missing keys fall back to the package defaults, so a published config
     * with only some keys still produces sensible headers. Set the config
     * value to `false` to disable the Cache-Control header entirely.
     */
    public static function fromConfig(): self
    {
        $config = config('vite-s3.cache_control', self::DEFAULTS);

        if ($config === false) {
            return new self(false);
        }

        if (! is_array($config)) {
            return new self(self::DEFAULTS);
        }

        return new self(array_merge(self::DEFAULTS, $config));
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
