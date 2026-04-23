<?php

namespace Jaydeep\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Jaydeep\Xray\Analyzers\DependencyAnalyzer;
use Jaydeep\Xray\Support\ClassParser;

class DependencyAnalyzerTest extends TestCase
{
    public function test_builds_dependency_tree(): void
    {
        $controllerFile = __DIR__ . '/../../Fixtures/Controllers/SampleController.php';
        $serviceFile = __DIR__ . '/../../Fixtures/Services/SampleService.php';

        $controller = ClassParser::parse($controllerFile);
        $service = ClassParser::parse($serviceFile);

        $analyzer = new DependencyAnalyzer();
        $result = $analyzer->analyze([$controller, $service]);

        $this->assertArrayHasKey('trees', $result);
        $this->assertArrayHasKey('layers', $result);
    }

    public function test_detects_controller_layer(): void
    {
        $controllerFile = __DIR__ . '/../../Fixtures/Controllers/SampleController.php';
        $controller = ClassParser::parse($controllerFile);

        $analyzer = new DependencyAnalyzer();
        $result = $analyzer->analyze([$controller]);

        $this->assertContains('Controller', $result['layers']);
    }

    public function test_returns_empty_trees_for_no_controllers(): void
    {
        $serviceFile = __DIR__ . '/../../Fixtures/Services/SampleService.php';
        $service = ClassParser::parse($serviceFile);

        $analyzer = new DependencyAnalyzer();
        $result = $analyzer->analyze([$service]);

        $this->assertArrayHasKey('trees', $result);
        $this->assertEmpty($result['trees']);
    }
}
