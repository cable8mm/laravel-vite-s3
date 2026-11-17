# Laravel Vite S3

[![Latest Stable Version](https://poser.pugx.org/cable8mm/laravel-vite-s3/v)](https://packagist.org/packages/cable8mm/laravel-vite-s3)
[![Total Downloads](https://poser.pugx.org/cable8mm/laravel-vite-s3/downloads)](https://packagist.org/packages/cable8mm/laravel-vite-s3)
[![License](https://poser.pugx.org/cable8mm/laravel-vite-s3/license)](https://packagist.org/packages/cable8mm/laravel-vite-s3)

Sync the [Vite](https://vitejs.dev) build output of a Laravel app to an S3 (or any Flysystem) disk with one artisan command — ideal for serving hashed assets from a CDN.

Extracted from a real-world Laravel application and packaged for reuse.

## Features

- `s3:copy-vite-build` command: recursive copy from the `build` source disk (a local disk rooted at `public/build`) to the `s3` target disk under `build/`
- Configurable `source_disk`, `target_disk` and `target_path`
- Cache-Control headers out of the box: hashed `assets/` get `public, max-age=31536000, immutable`, everything else (e.g. the manifest) gets `public, max-age=300`
- `--dry-run` to preview the copy/delete plan
- `--delete` to remove stale files in the target disk that no longer exist in the source
- Non-zero exit code on any failure, so deploy pipelines fail loudly

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12 or 13
- An S3 (or compatible) Flysystem disk configured in `config/filesystems.php` when targeting S3

## Installation

```bash
composer require cable8mm/laravel-vite-s3
```

Laravel will automatically discover the service provider.

Publish the configuration file when you want to customize it:

```bash
php artisan vendor:publish --tag=config
```

## Configuration

Add a local disk rooted at the Vite build output to `config/filesystems.php` if you don't have one yet (`vite build` writes to `public/build` by default):

```php
// config/filesystems.php
'disks' => [
    // ...
    'build' => [
        'driver' => 'local',
        'root' => public_path('build'),
    ],
],
```

And make sure your `s3` disk is configured, e.g.:

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=ap-northeast-2
AWS_BUCKET=your-bucket
AWS_URL=https://cdn.example.com
```

The package configuration (`config/vite-s3.php`) looks like this:

```php
return [
    // Disk containing the Vite build output
    'source_disk' => env('VITE_S3_SOURCE_DISK', 'build'),

    // Disk to copy the build output into
    'target_disk' => env('VITE_S3_TARGET_DISK', 's3'),

    // Directory prefix inside the target disk (empty string for the root)
    'target_path' => env('VITE_S3_TARGET_PATH', 'build'),

    // Cache-Control headers (set 'cache_control' to false to disable)
    'cache_control' => [
        'immutable_directories' => ['assets'],
        'immutable' => 'public, max-age=31536000, immutable',
        'default' => 'public, max-age=300',
    ],
];
```

## Usage

After `vite build`, run:

```bash
php artisan s3:copy-vite-build
```

Output:

```
Done. 42 files copied.
```

### Options

```bash
php artisan s3:copy-vite-build --dry-run
php artisan s3:copy-vite-build --delete
php artisan s3:copy-vite-build --dry-run --delete
```

- `--dry-run`: print what would be copied/deleted without writing anything.
- `--delete`: also delete the files in the target disk under `target_path` that no longer exist in the source disk. Files outside `target_path` are never touched. Requires a non-empty `target_path` (it refuses to scan the entire bucket).

### Exit codes

| Code | Meaning |
| --- | --- |
| `0` | All files copied successfully |
| `1` | The source disk is empty, a configured disk does not exist, `--delete` was used with an empty `target_path`, or at least one file failed |

### Cache-Control headers

Vite emits content-hashed assets under the `assets/` directory. These are safe to cache forever:

```
Cache-Control: public, max-age=31536000, immutable
```

Everything else — typically `manifest.json` — changes on every deployment and gets a short cache instead:

```
Cache-Control: public, max-age=300
```

The mapping is configured through `cache_control.immutable_directories` (first path segments) and the `immutable`/`default` values. Set `'cache_control' => false` to disable setting the header entirely.

Content types are detected automatically by the S3 adapter from the file contents.

### Deploying

Add the copy step to your deploy script, e.g. as a Composer script:

```json
{
    "scripts": {
        "vite-build": [
            "@php artisan s3:copy-vite-build"
        ]
    }
}
```

or from your deployment tool of choice after `npm run build`:

```bash
npm run build
php artisan s3:copy-vite-build --delete
```

## Development

```bash
composer install
composer test
composer lint
```

## Credits

- [Samgu Lee](https://github.com/cable8mm)

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
