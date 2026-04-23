<?php

namespace Jaydeep\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Analyzers\ControllerAnalyzer;

class ControllerAnalyzerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/Controllers';
    }

    public function test_analyzes_controllers_in_directory(): void
    {
        $analyzer = new ControllerAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $this->assertNotEmpty($results);
    }

    public function test_returns_class_names(): void
    {
        $analyzer = new ControllerAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $classNames = array_column($results, 'class');
        $this->assertContains('SampleController', $classNames);
    }

    public function test_includes_method_count(): void
    {
        $analyzer = new ControllerAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);

        $sample = null;
        foreach ($results as $result) {
            if ($result['class'] === 'SampleController') {
                $sample = $result;
                break;
            }
        }

        $this->assertNotNull($sample);
        $this->assertArrayHasKey('method_count', $sample);
        $this->assertGreaterThan(0, $sample['method_count']);
    }

    public function test_includes_loc(): void
    {
        $analyzer = new ControllerAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);

        $sample = null;
        foreach ($results as $result) {
            if ($result['class'] === 'SampleController') {
                $sample = $result;
                break;
            }
        }

        $this->assertNotNull($sample);
        $this->assertArrayHasKey('loc', $sample);
        $this->assertGreaterThan(0, $sample['loc']);
    }

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new ControllerAnalyzer();
        $results = $analyzer->analyze('/nonexistent/path');
        $this->assertSame([], $results);
    }
}
