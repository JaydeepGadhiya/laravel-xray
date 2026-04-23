<?php

namespace Larapack\Xray\Reporters;

use RuntimeException;
use Larapack\Xray\Support\ClassParser;
use Larapack\Xray\Support\Concerns\EnsuresOutputDirectory;

/**
 * Generates a comprehensive Markdown report from scan results.
 *
 * The report includes a summary table, architecture dependency trees,
 * detected layers, and dead code findings in a human-readable format.
 */
class MarkdownReporter
{
    use EnsuresOutputDirectory;

    /**
     * Generate a Markdown report from a full scan result.
     *
     * Creates the output directory if needed and writes the report
     * to scan-report.md within that directory.
     *
     * @param  array   $scanResult  Complete scan result from ProjectScanner::scan()
     * @param  string  $outputPath  Directory path for output
     * @return string  The absolute path to the written file
     *
     * @throws RuntimeException If the directory cannot be created or the file cannot be written
     */
    public function generate(array $scanResult, string $outputPath): string
    {
        $this->ensureDirectoryExists($outputPath);

        $filePath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'scan-report.md';

        $markdown = $this->buildReport($scanResult);

        if (file_put_contents($filePath, $markdown) === false) {
            throw new RuntimeException("Failed to write Markdown report to: {$filePath}");
        }

        return $filePath;
    }

    /**
     * Build the full Markdown report string.
     *
     * @param  array  $scanResult  Complete scan result
     * @return string
     */
    private function buildReport(array $scanResult): string
    {
        $lines = [];

        $lines[] = '# Project X-Ray Report';
        $lines[] = '';
        $lines[] = 'Generated: ' . ($scanResult['scanned_at'] ?? date('c'));
        $lines[] = '';

        $lines[] = $this->buildSummarySection($scanResult['summary'] ?? []);
        $lines[] = $this->buildArchitectureSection($scanResult['architecture'] ?? []);
        $lines[] = $this->buildDeadCodeSection($scanResult['dead_code'] ?? []);
        $lines[] = $this->buildComponentDetailSection('Controllers', $scanResult['controllers'] ?? []);
        $lines[] = $this->buildModelDetailSection($scanResult['models'] ?? []);
        $lines[] = $this->buildRouteSection($scanResult['routes'] ?? []);

        return implode("\n", $lines);
    }

    /**
     * Build the summary metrics table.
     *
     * @param  array  $summary
     * @return string
     */
    private function buildSummarySection(array $summary): string
    {
        $lines = [];
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|--------|-------|';
        $lines[] = '| Controllers | ' . ($summary['total_controllers'] ?? 0) . ' |';
        $lines[] = '| Models | ' . ($summary['total_models'] ?? 0) . ' |';
        $lines[] = '| Services | ' . ($summary['total_services'] ?? 0) . ' |';
        $lines[] = '| Repositories | ' . ($summary['total_repositories'] ?? 0) . ' |';
        $lines[] = '| Routes | ' . ($summary['total_routes'] ?? 0) . ' |';
        $lines[] = '| Views | ' . ($summary['total_views'] ?? 0) . ' |';
        $lines[] = '| Unused Controllers | ' . ($summary['dead_controllers'] ?? 0) . ' |';
        $lines[] = '| Unused Models | ' . ($summary['dead_models'] ?? 0) . ' |';
        $lines[] = '| Unused Views | ' . ($summary['dead_views'] ?? 0) . ' |';
        $lines[] = '| Unused Services | ' . ($summary['dead_services'] ?? 0) . ' |';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Build the architecture section with dependency trees and layer detection.
     *
     * @param  array  $architecture
     * @return string
     */
    private function buildArchitectureSection(array $architecture): string
    {
        $lines = [];
        $lines[] = '## Architecture';
        $lines[] = '';

        // Dependency Trees
        $trees = $architecture['trees'] ?? [];
        if (! empty($trees)) {
            $lines[] = '### Dependency Trees';
            $lines[] = '';
            $lines[] = '```';

            foreach ($trees as $rootFqcn => $dependencies) {
                $rootName = ClassParser::classBasename($rootFqcn);
                $lines[] = $rootName;
                $this->renderTreeLines($dependencies, '', $lines);
                $lines[] = '';
            }

            $lines[] = '```';
            $lines[] = '';
        }

        // Detected Layers
        $layers = $architecture['layers'] ?? [];
        if (! empty($layers)) {
            $lines[] = '### Detected Layers';
            $lines[] = '';
            $lines[] = implode(' -> ', $layers);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Recursively render dependency tree lines with box-drawing indentation.
     *
     * @param  array   $children  The nested dependency tree
     * @param  string  $prefix    Current indentation prefix
     * @param  array   &$lines    Output lines array (passed by reference)
     * @return void
     */
    private function renderTreeLines(array $children, string $prefix, array &$lines): void
    {
        $keys = array_keys($children);
        $total = count($keys);

        foreach ($keys as $index => $fqcn) {
            $isLast = ($index === $total - 1);
            $connector = $isLast ? '└── ' : '├── ';
            $childPrefix = $isLast ? '    ' : '│   ';

            $shortName = ClassParser::classBasename($fqcn);
            $lines[] = $prefix . $connector . $shortName;

            $subChildren = $children[$fqcn];
            if (is_array($subChildren) && ! empty($subChildren)) {
                $this->renderTreeLines($subChildren, $prefix . $childPrefix, $lines);
            }
        }
    }

    /**
     * Build the dead code findings section.
     *
     * @param  array  $deadCode
     * @return string
     */
    private function buildDeadCodeSection(array $deadCode): string
    {
        $lines = [];
        $lines[] = '## Dead Code';
        $lines[] = '';

        $hasDeadCode = false;

        // Unused Controllers
        $unusedControllers = $deadCode['controllers'] ?? [];
        if (! empty($unusedControllers)) {
            $hasDeadCode = true;
            $lines[] = '### Unused Controllers';
            $lines[] = '';
            foreach ($unusedControllers as $item) {
                $lines[] = '- ' . $item['class'] . ' (`' . $item['file'] . '`)';
            }
            $lines[] = '';
        }

        // Unused Models
        $unusedModels = $deadCode['models'] ?? [];
        if (! empty($unusedModels)) {
            $hasDeadCode = true;
            $lines[] = '### Unused Models';
            $lines[] = '';
            foreach ($unusedModels as $item) {
                $lines[] = '- ' . $item['class'] . ' (`' . $item['file'] . '`)';
            }
            $lines[] = '';
        }

        // Unused Views
        $unusedViews = $deadCode['views'] ?? [];
        if (! empty($unusedViews)) {
            $hasDeadCode = true;
            $lines[] = '### Unused Views';
            $lines[] = '';
            foreach ($unusedViews as $item) {
                $lines[] = '- ' . $item['name'] . ' (`' . $item['file'] . '`)';
            }
            $lines[] = '';
        }

        // Unused Services
        $unusedServices = $deadCode['services'] ?? [];
        if (! empty($unusedServices)) {
            $hasDeadCode = true;
            $lines[] = '### Unused Services';
            $lines[] = '';
            foreach ($unusedServices as $item) {
                $lines[] = '- ' . $item['class'] . ' (`' . $item['file'] . '`)';
            }
            $lines[] = '';
        }

        if (! $hasDeadCode) {
            $lines[] = 'No dead code detected.';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Build a generic component detail section showing class names and methods.
     *
     * @param  string  $title    Section heading
     * @param  array   $classes  Parsed class data
     * @return string
     */
    private function buildComponentDetailSection(string $title, array $classes): string
    {
        if (empty($classes)) {
            return '';
        }

        $lines = [];
        $lines[] = '## ' . $title;
        $lines[] = '';

        foreach ($classes as $class) {
            $lines[] = '### ' . ($class['class'] ?? 'Unknown');
            $lines[] = '';
            $lines[] = '- **Namespace:** ' . ($class['namespace'] ?? 'N/A');
            $lines[] = '- **File:** `' . ($class['file'] ?? '') . '`';

            $deps = $class['constructor_dependencies'] ?? [];
            if (! empty($deps)) {
                $depNames = array_map(function ($d) { return $d['type'] ?? ''; }, $deps);
                $lines[] = '- **Dependencies:** ' . implode(', ', $depNames);
            }

            $methods = $class['methods'] ?? [];
            if (! empty($methods)) {
                $methodNames = array_map(function ($m) { return '`' . $m['name'] . '()`'; }, $methods);
                $lines[] = '- **Methods:** ' . implode(', ', $methodNames);
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Build a model detail section including relationships.
     *
     * @param  array  $models  Parsed model data
     * @return string
     */
    private function buildModelDetailSection(array $models): string
    {
        if (empty($models)) {
            return '';
        }

        $lines = [];
        $lines[] = '## Models';
        $lines[] = '';

        foreach ($models as $model) {
            $lines[] = '### ' . ($model['class'] ?? 'Unknown');
            $lines[] = '';
            $lines[] = '- **Namespace:** ' . ($model['namespace'] ?? 'N/A');
            $lines[] = '- **File:** `' . ($model['file'] ?? '') . '`';

            $relationships = $model['relationships'] ?? [];
            if (! empty($relationships)) {
                $lines[] = '- **Relationships:**';
                foreach ($relationships as $rel) {
                    $lines[] = '  - `' . ($rel['method'] ?? '') . '()` - '
                        . ($rel['type'] ?? '') . ' -> ' . ($rel['related'] ?? '');
                }
            }

            $methods = $model['methods'] ?? [];
            if (! empty($methods)) {
                $methodNames = array_map(function ($m) { return '`' . $m['name'] . '()`'; }, $methods);
                $lines[] = '- **Methods:** ' . implode(', ', $methodNames);
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Build the routes listing section.
     *
     * @param  array  $routes  Parsed route data
     * @return string
     */
    private function buildRouteSection(array $routes): string
    {
        if (empty($routes)) {
            return '';
        }

        $lines = [];
        $lines[] = '## Routes';
        $lines[] = '';
        $lines[] = '| Method | URI | Controller | Action | Name |';
        $lines[] = '|--------|-----|------------|--------|------|';

        foreach ($routes as $route) {
            $method = $route['method'] ?? '';
            $uri = $route['uri'] ?? '';
            $controller = $route['controller'] ?? '';
            $action = $route['action'] ?? '';
            $name = $route['name'] ?? '';
            $lines[] = "| {$method} | {$uri} | {$controller} | {$action} | {$name} |";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
