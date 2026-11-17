<?php

namespace Cable8mm\LaravelViteS3\Tests;

use Cable8mm\LaravelViteS3\ViteS3ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ViteS3ServiceProvider::class,
        ];
    }
}
