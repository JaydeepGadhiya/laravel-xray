<?php

namespace Larapack\Xray\Tests\Feature\Commands;

use Larapack\Xray\Tests\Feature\TestCase;

class ArchitectureCommandTest extends TestCase
{
    public function test_architecture_command_runs_successfully(): void
    {
        $this->artisan('xray:architecture')
             ->assertExitCode(0);
    }
}
