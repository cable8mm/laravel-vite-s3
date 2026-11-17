<?php

namespace Cable8mm\LaravelViteS3\Tests;

use Cable8mm\LaravelViteS3\CacheControl;

class CacheControlTest extends TestCase
{
    public function test_files_under_immutable_directories_get_the_immutable_header(): void
    {
        $cacheControl = new CacheControl([
            'immutable_directories' => ['assets'],
            'immutable' => 'public, max-age=31536000, immutable',
            'default' => 'public, max-age=300',
        ]);

        $this->assertSame('public, max-age=31536000, immutable', $cacheControl->forPath('assets/app-A1b2C3d4.js'));
        $this->assertSame('public, max-age=31536000, immutable', $cacheControl->forPath('assets/css/app-A1b2C3d4.css'));
        $this->assertSame('public, max-age=300', $cacheControl->forPath('.vite/manifest.json'));
        $this->assertSame('public, max-age=300', $cacheControl->forPath('manifest.json'));
        $this->assertSame('public, max-age=300', $cacheControl->forPath('other/nested/file.txt'));
    }

    public function test_headers_can_be_disabled_with_false(): void
    {
        $cacheControl = new CacheControl(false);

        $this->assertNull($cacheControl->forPath('assets/app.js'));
        $this->assertSame([], $cacheControl->optionsFor('assets/app.js'));
    }

    public function test_immutable_header_is_null_when_not_configured(): void
    {
        $cacheControl = new CacheControl(['immutable_directories' => ['assets']]);

        $this->assertNull($cacheControl->forPath('assets/app.js'));
    }

    public function test_leading_slashes_are_ignored(): void
    {
        $cacheControl = new CacheControl([
            'immutable_directories' => ['assets'],
            'immutable' => 'immutable',
            'default' => 'short',
        ]);

        $this->assertSame('immutable', $cacheControl->forPath('/assets/app.js'));
    }
}
