<?php

namespace Jaydeep\Xray\Tests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Reporters\MermaidReporter;

class MermaidReporterTest extends TestCase
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

    public function test_generates_mermaid_file(): void
    {
        $reporter = new MermaidReporter();
        $architecture = ['trees' => [], 'layers' => []];
        $path = $reporter->generate($architecture, $this->outputPath);
        $this->assertFileExists($path);
    }

    public function test_mermaid_file_contains_graph_syntax(): void
    {
        $reporter = new MermaidReporter();
        $architecture = ['trees' => [], 'layers' => []];
        $path = $reporter->generate($architecture, $this->outputPath);
        $content = file_get_contents($path);
        $this->assertStringContainsString('graph TD', $content);
        $this->assertStringContainsString('classDiagram', $content);
    }

    public function test_output_file_is_named_architecture_mmd(): void
    {
        $reporter = new MermaidReporter();
        $architecture = ['trees' => [], 'layers' => []];
        $path = $reporter->generate($architecture, $this->outputPath);
        $this->assertStringEndsWith('architecture.mmd', $path);
    }

    public function test_creates_directory_if_not_exists(): void
    {
        $reporter = new MermaidReporter();
        $nestedPath = $this->outputPath . '/nested';
        $reporter->generate(['trees' => [], 'layers' => []], $nestedPath);
        $this->assertDirectoryExists($nestedPath);
    }
}
