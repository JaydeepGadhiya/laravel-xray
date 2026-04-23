<?php

namespace Jaydeep\Xray\Tests\Feature\Commands;

use Jaydeep\Xray\Tests\Feature\TestCase;

class ScanCommandTest extends TestCase
{
    public function test_scan_command_runs_successfully(): void
    {
        $this->artisan('xray:scan')
             ->assertExitCode(0);
    }

    public function test_scan_command_outputs_summary(): void
    {
        $this->artisan('xray:scan')
             ->expectsOutputToContain('Scan complete')
             ->assertExitCode(0);
    }
}
