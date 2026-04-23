<?php

namespace Larapack\Xray\Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use Larapack\Xray\Analyzers\ModelAnalyzer;

class ModelAnalyzerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/Models';
    }

    public function test_analyzes_models(): void
    {
        $analyzer = new ModelAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);
        $this->assertNotEmpty($results);
    }

    public function test_extracts_relationships(): void
    {
        $analyzer = new ModelAnalyzer();
        $results = $analyzer->analyze($this->fixturesPath);

        $model = null;
        foreach ($results as $result) {
            if ($result['class'] === 'SampleModel') {
                $model = $result;
                break;
            }
        }

        $this->assertNotNull($model);
        $this->assertArrayHasKey('relationships', $model);

        $relTypes = array_column($model['relationships'], 'type');
        $this->assertContains('hasMany', $relTypes);
        $this->assertContains('hasOne', $relTypes);
    }

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $analyzer = new ModelAnalyzer();
        $results = $analyzer->analyze('/nonexistent/path');
        $this->assertSame([], $results);
    }
}
