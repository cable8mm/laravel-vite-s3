<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source disk
    |--------------------------------------------------------------------------
    |
    | The disk where the Vite build output lives. By default this is a local
    | disk rooted at public/build, configured in config/filesystems.php:
    |
    |     'build' => [
    |         'driver' => 'local',
    |         'root' => public_path('build'),
    |     ],
    |
    */
    'source_disk' => env('VITE_S3_SOURCE_DISK', 'build'),

    /*
    |--------------------------------------------------------------------------
    | Target disk
    |--------------------------------------------------------------------------
    |
    | The disk the build output is copied to, usually an S3 bucket (or a
    | similar Flysystem driver such as R2, MinIO or Spaces).
    |
    */
    'target_disk' => env('VITE_S3_TARGET_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Target path
    |--------------------------------------------------------------------------
    |
    | The directory prefix inside the target disk where the files land. A
    | file "assets/app-abc123.js" in the source disk is copied to
    | "<target_path>/assets/app-abc123.js".
    |
    | Use an empty string to copy the files to the root of the target disk.
    | Note that --delete requires a non-empty target path, because it would
    | otherwise list and delete files in the entire disk.
    |
    */
    'target_path' => env('VITE_S3_TARGET_PATH', 'build'),

    /*
    |--------------------------------------------------------------------------
    | Cache-Control headers
    |--------------------------------------------------------------------------
    |
    | Vite emits content-hashed assets (e.g. assets/app-A1b2C3d4.js) which are
    | safe to cache forever. Everything else — notably the manifest files —
    | changes on every deployment, so it gets a short cache instead.
    |
    | Files whose first path segment is listed in "immutable_directories"
    | receive the "immutable" header. All other files receive "default".
    |
    | Any missing key falls back to the package default. Set 'cache_control'
    | to false to skip the Cache-Control header entirely.
    |
    */
    'cache_control' => [
        'immutable_directories' => ['assets'],
        'immutable' => 'public, max-age=31536000, immutable',
        'default' => 'public, max-age=300',
    ],

];
