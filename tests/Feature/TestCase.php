<?php

namespace Jaydeep\Xray\Tests\Feature;

use Jaydeep\Xray\LaravelXrayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelXrayServiceProvider::class];
    }
}
