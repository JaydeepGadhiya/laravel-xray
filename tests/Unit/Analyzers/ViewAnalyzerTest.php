<?php

namespace Jaydeep\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Analyzers\ViewAnalyzer;

class ViewAnalyzerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/Views';
    }

    public function test_analyzes_views(): void
    {
        $analyzer = new ViewAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $this->assertNotEmpty($results);
    }

    public function test_converts_to_dot_notation(): void
    {
        $analyzer = new ViewAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $names = array_column($results, 'name');
        $this->assertContains('welcome', $names);
        $this->assertContains('users.index', $names);
    }

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new ViewAnalyzer();
        $results = $analyzer->analyze('/nonexistent/path');
        $this->assertSame([], $results);
    }
}
