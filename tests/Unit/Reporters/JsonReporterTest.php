<?php

namespace Jaydeep\Xray\Tests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Reporters\JsonReporter;

class JsonReporterTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/xray-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputPath)) {
            $files = glob($this->outputPath . '/*');
            if ($files !== false) {
                array_map('unlink', $files);
            }
            rmdir($this->outputPath);
        }
    }

    public function test_generates_json_file(): void
    {
        $reporter = new JsonReporter();
        $path = $reporter->generate(['test' => 'data'], $this->outputPath, 'test.json');
        $this->assertFileExists($path);
    }

    public function test_json_is_valid(): void
    {
        $reporter = new JsonReporter();
        $data = ['controllers' => [], 'models' => []];
        $path = $reporter->generate($data, $this->outputPath, 'test.json');
        $decoded = json_decode(file_get_contents($path), true);
        $this->assertSame($data, $decoded);
    }

    public function test_creates_directory_if_not_exists(): void
    {
        $reporter = new JsonReporter();
        $nestedPath = $this->outputPath . '/nested/path';
        $reporter->generate([], $nestedPath, 'test.json');
        $this->assertDirectoryExists($nestedPath);
        // cleanup
        unlink($nestedPath . '/test.json');
        rmdir($nestedPath);
        rmdir($this->outputPath . '/nested');
    }

    public function test_generate_full_report_uses_scan_report_filename(): void
    {
        $reporter = new JsonReporter();
        $path = $reporter->generateFullReport(['summary' => []], $this->outputPath);
        $this->assertStringEndsWith('scan-report.json', $path);
        $this->assertFileExists($path);
    }

    public function test_generate_architecture_report_uses_architecture_filename(): void
    {
        $reporter = new JsonReporter();
        $path = $reporter->generateArchitectureReport(['trees' => [], 'layers' => []], $this->outputPath);
        $this->assertStringEndsWith('architecture.json', $path);
        $this->assertFileExists($path);
    }

    public function test_generate_dead_code_report_uses_deadcode_filename(): void
    {
        $reporter = new JsonReporter();
        $path = $reporter->generateDeadCodeReport(['controllers' => []], $this->outputPath);
        $this->assertStringEndsWith('deadcode.json', $path);
        $this->assertFileExists($path);
    }
}
