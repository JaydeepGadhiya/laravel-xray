<?php

namespace Jaydeep\Xray\Tests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Reporters\MarkdownReporter;

class MarkdownReporterTest extends TestCase
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

    public function test_generates_markdown_file(): void
    {
        $reporter = new MarkdownReporter();
        $scanResult = [
            'summary' => [
                'total_controllers' => 2,
                'total_models' => 1,
                'total_services' => 0,
                'total_repositories' => 0,
                'total_routes' => 5,
                'total_views' => 3,
                'dead_controllers' => 0,
                'dead_models' => 0,
                'dead_views' => 0,
                'dead_services' => 0,
            ],
            'architecture' => ['trees' => [], 'layers' => []],
            'dead_code' => ['controllers' => [], 'models' => [], 'views' => [], 'services' => []],
            'controllers' => [],
            'models' => [],
            'routes' => [],
            'scanned_at' => date('c'),
        ];
        $path = $reporter->generate($scanResult, $this->outputPath);
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('# Project X-Ray Report', $content);
        $this->assertStringContainsString('## Summary', $content);
    }

    public function test_output_file_is_named_scan_report_md(): void
    {
        $reporter = new MarkdownReporter();
        $scanResult = [
            'summary' => [],
            'architecture' => [],
            'dead_code' => [],
            'controllers' => [],
            'models' => [],
            'routes' => [],
            'scanned_at' => date('c'),
        ];
        $path = $reporter->generate($scanResult, $this->outputPath);
        $this->assertStringEndsWith('scan-report.md', $path);
    }

    public function test_creates_directory_if_not_exists(): void
    {
        $reporter = new MarkdownReporter();
        $nestedPath = $this->outputPath . '/nested';
        $scanResult = [
            'summary' => [],
            'architecture' => [],
            'dead_code' => [],
            'controllers' => [],
            'models' => [],
            'routes' => [],
            'scanned_at' => date('c'),
        ];
        $reporter->generate($scanResult, $nestedPath);
        $this->assertDirectoryExists($nestedPath);
    }
}
