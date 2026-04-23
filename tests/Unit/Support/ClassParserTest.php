<?php

namespace Larapack\Xray\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Larapack\Xray\Support\ClassParser;

class ClassParserTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures';
    }

    public function test_parses_class_name(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $this->assertSame('SampleController', $result['class']);
    }

    public function test_parses_namespace(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $this->assertSame('App\Http\Controllers', $result['namespace']);
    }

    public function test_parses_fqcn(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $this->assertSame('App\Http\Controllers\SampleController', $result['fqcn']);
    }

    public function test_parses_constructor_dependencies(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $this->assertCount(1, $result['constructor_dependencies']);
        $this->assertSame('UserService', $result['constructor_dependencies'][0]['type']);
    }

    public function test_parses_methods(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $methodNames = array_column($result['methods'], 'name');
        $this->assertContains('index', $methodNames);
        $this->assertContains('store', $methodNames);
    }

    public function test_parses_use_statements(): void
    {
        $result = ClassParser::parse($this->fixturesPath . '/Controllers/SampleController.php');
        $this->assertArrayHasKey('UserService', $result['uses']);
    }

    public function test_returns_empty_result_for_nonexistent_file(): void
    {
        $result = ClassParser::parse('/nonexistent/file.php');
        $this->assertSame('', $result['class']);
        $this->assertSame('', $result['namespace']);
    }

    public function test_classbasename_returns_short_name(): void
    {
        $this->assertSame('User', ClassParser::classBasename('App\Models\User'));
        $this->assertSame('UserController', ClassParser::classBasename('App\Http\Controllers\UserController'));
    }

    public function test_extract_use_statements_is_public(): void
    {
        $content = '<?php use App\Models\User; use App\Services\UserService;';
        $tokens = token_get_all($content);
        $uses = ClassParser::extractUseStatements($tokens);
        $this->assertArrayHasKey('User', $uses);
        $this->assertArrayHasKey('UserService', $uses);
    }
}
