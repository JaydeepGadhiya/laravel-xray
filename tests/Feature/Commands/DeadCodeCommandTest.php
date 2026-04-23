<?php

namespace Jaydeep\Xray\Tests\Feature\Commands;

use Jaydeep\Xray\Tests\Feature\TestCase;

class DeadCodeCommandTest extends TestCase
{
    public function test_deadcode_command_runs_successfully(): void
    {
        $this->artisan('xray:deadcode')
             ->assertExitCode(0);
    }
}
