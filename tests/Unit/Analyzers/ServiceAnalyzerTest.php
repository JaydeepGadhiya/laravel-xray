<?php

namespace Jaydeep\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Analyzers\ServiceAnalyzer;

class ServiceAnalyzerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/Services';
    }

    public function test_analyzes_services(): void
    {
        $analyzer = new ServiceAnalyzer();
        $results = $analyzer->analyzeServices($this->fixturesPath);
        $this->assertNotEmpty($results);
        $classNames = array_column($results, 'class');
        $this->assertContains('SampleService', $classNames);
    }

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new ServiceAnalyzer();
        $results = $analyzer->analyzeServices('/nonexistent/path');
        $this->assertSame([], $results);
    }

    public function test_analyze_repositories_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new ServiceAnalyzer();
        $results = $analyzer->analyzeRepositories('/nonexistent/path');
        $this->assertSame([], $results);
    }
}
