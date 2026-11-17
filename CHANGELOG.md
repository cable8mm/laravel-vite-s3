# Changelog

All notable changes to `cable8mm/laravel-vite-s3` will be documented in this file.

## 1.0.0 - 2026-11-17

Initial release.

- `s3:copy-vite-build` command: copies the Vite build output from the `build` source disk to the `s3` target disk under `build/`.
- Configurable `source_disk`, `target_disk` and `target_path` via the `vite-s3` config (publishable with `php artisan vendor:publish --tag=config`).
- Cache-Control headers: content-hashed assets (top-level `assets/` directory) get an immutable one-year cache; everything else (e.g. the manifest) gets a short 5-minute cache. Can be disabled with `'cache_control' => false`.
- `--dry-run` option prints the copy/delete plan without writing anything.
- `--delete` option removes files in the target disk under the target path that no longer exist in the source disk. Requires a non-empty `target_path`.
- Fails with a non-zero exit code when the source disk is empty, a configured disk is missing, or any file fails to copy.
