<?php

namespace Larapack\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Larapack\Xray\Analyzers\RouteAnalyzer;

class RouteAnalyzerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/Routes';
    }

    public function test_extracts_routes(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $this->assertNotEmpty($results);
    }

    public function test_extracts_controller_reference(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $controllers = array_unique(array_column($results, 'controller'));
        $this->assertContains('SampleController', $controllers);
    }

    public function test_extracts_route_names(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $names = array_column($results, 'name');
        $this->assertContains('users.index', $names);
    }

    public function test_expands_resource_routes(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $actions = array_column($results, 'action');
        $this->assertContains('index', $actions);
        $this->assertContains('store', $actions);
        $this->assertContains('destroy', $actions);
    }

    public function test_all_routes_have_name_key(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        foreach ($results as $route) {
            $this->assertArrayHasKey('name', $route);
        }
    }

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new RouteAnalyzer();
        $results = $analyzer->analyze('/nonexistent/path');
        $this->assertSame([], $results);
    }
}
