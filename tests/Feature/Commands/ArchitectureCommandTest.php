<?php

namespace Jaydeep\Xray\Tests\Feature\Commands;

use Jaydeep\Xray\Tests\Feature\TestCase;

class ArchitectureCommandTest extends TestCase
{
    public function test_architecture_command_runs_successfully(): void
    {
        $this->artisan('xray:architecture')
             ->assertExitCode(0);
    }
}
