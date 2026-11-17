<?php

namespace Cable8mm\LaravelViteS3\Tests;

use Illuminate\Support\Facades\Storage;

class CopyViteBuildTest extends TestCase
{
    public function test_it_copies_the_source_disk_to_the_target_disk(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');
        Storage::disk('build')->put('assets/app-A1b2C3d4.css', 'body{}');
        Storage::disk('build')->put('.vite/manifest.json', '{"file":"assets/app-A1b2C3d4.js"}');

        $this->artisan('s3:copy-vite-build')->assertExitCode(0);

        Storage::disk('s3')->assertExists('build/assets/app-A1b2C3d4.js');
        Storage::disk('s3')->assertExists('build/assets/app-A1b2C3d4.css');
        Storage::disk('s3')->assertExists('build/.vite/manifest.json');
        $this->assertSame('console.log(1);', Storage::disk('s3')->get('build/assets/app-A1b2C3d4.js'));
    }

    public function test_it_fails_when_the_source_disk_is_empty(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        $this->artisan('s3:copy-vite-build')->assertExitCode(1);

        Storage::disk('s3')->assertMissing('build');
    }

    public function test_it_fails_when_a_configured_disk_does_not_exist(): void
    {
        config()->set('vite-s3.source_disk', 'missing-disk');

        $this->artisan('s3:copy-vite-build')->assertExitCode(1);
    }

    public function test_it_respects_a_custom_target_path(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        config()->set('vite-s3.target_path', 'static-assets');
        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');

        $this->artisan('s3:copy-vite-build')->assertExitCode(0);

        Storage::disk('s3')->assertExists('static-assets/assets/app-A1b2C3d4.js');
    }

    public function test_it_respects_a_custom_source_disk(): void
    {
        Storage::fake('custom-source');
        Storage::fake('custom-target');

        config()->set('vite-s3.source_disk', 'custom-source');
        config()->set('vite-s3.target_disk', 'custom-target');

        Storage::disk('custom-source')->put('assets/app.js', 'console.log(1);');

        $this->artisan('s3:copy-vite-build')->assertExitCode(0);

        Storage::disk('custom-target')->assertExists('build/assets/app.js');
    }

    public function test_dry_run_does_not_write_anything(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');

        $this->artisan('s3:copy-vite-build', ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run] assets/app-A1b2C3d4.js -> build/assets/app-A1b2C3d4.js')
            ->assertExitCode(0);

        Storage::disk('s3')->assertMissing('build/assets/app-A1b2C3d4.js');
    }

    public function test_delete_removes_stale_files_under_the_target_path(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');

        Storage::disk('s3')->put('build/assets/app-A1b2C3d4.js', 'old content');
        Storage::disk('s3')->put('build/assets/old-B9z8Y7x6.js', 'stale');
        Storage::disk('s3')->put('uploads/keep.txt', 'unrelated');

        $this->artisan('s3:copy-vite-build', ['--delete' => true])->assertExitCode(0);

        Storage::disk('s3')->assertExists('build/assets/app-A1b2C3d4.js');
        Storage::disk('s3')->assertMissing('build/assets/old-B9z8Y7x6.js');
        // Files outside the target path must stay untouched.
        Storage::disk('s3')->assertExists('uploads/keep.txt');
    }

    public function test_dry_run_with_delete_does_not_delete_anything(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');
        Storage::disk('s3')->put('build/assets/stale.js', 'stale');

        // In dry-run mode the final message must say "would be deleted", not "deleted".
        $this->artisan('s3:copy-vite-build', ['--delete' => true, '--dry-run' => true])
            ->expectsOutputToContain('stale files would be deleted')
            ->assertExitCode(0);

        Storage::disk('s3')->assertExists('build/assets/stale.js');
        Storage::disk('s3')->assertMissing('build/assets/app-A1b2C3d4.js');
    }

    public function test_delete_requires_a_non_empty_target_path(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        config()->set('vite-s3.target_path', '');
        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');

        $this->artisan('s3:copy-vite-build', ['--delete' => true])->assertExitCode(1);
    }

    public function test_target_path_is_normalized(): void
    {
        Storage::fake('build');
        Storage::fake('s3');

        config()->set('vite-s3.target_path', '/static/');
        Storage::disk('build')->put('assets/app-A1b2C3d4.js', 'console.log(1);');

        $this->artisan('s3:copy-vite-build')->assertExitCode(0);

        Storage::disk('s3')->assertExists('static/assets/app-A1b2C3d4.js');
    }
}
