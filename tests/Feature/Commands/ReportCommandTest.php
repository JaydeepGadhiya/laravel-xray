<?php

namespace Jaydeep\Xray\Tests\Feature\Commands;

use Jaydeep\Xray\Tests\Feature\TestCase;

class ReportCommandTest extends TestCase
{
    public function test_report_command_generates_files(): void
    {
        $outputPath = sys_get_temp_dir() . '/xray-report-test-' . uniqid('', true);

        config(['xray.output_path' => $outputPath]);

        $this->artisan('xray:report', ['--format' => 'json'])
             ->assertExitCode(0);

        $this->assertFileExists($outputPath . DIRECTORY_SEPARATOR . 'scan-report.json');

        // cleanup
        $files = glob($outputPath . '/*');
        if ($files !== false) {
            array_map('unlink', $files);
        }
        rmdir($outputPath);
    }
}
