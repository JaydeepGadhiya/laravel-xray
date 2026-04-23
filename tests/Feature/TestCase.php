<?php

namespace Larapack\Xray\Tests\Feature;

use Larapack\Xray\LaravelXrayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelXrayServiceProvider::class];
    }
}
