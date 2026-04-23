<?php

namespace Larapack\Xray\Reporters;

use RuntimeException;
use Larapack\Xray\Support\ClassParser;
use Larapack\Xray\Support\Concerns\EnsuresOutputDirectory;

/**
 * Generates a self-contained HTML dashboard from scan results.
 *
 * Uses Bootstrap 5 from CDN. No local assets required.
 * Produces a single xray-report.html file with summary cards,
 * routes table, dead code sections, architecture tree, and model relationships.
 */
class HtmlReporter
{
    use EnsuresOutputDirectory;

    /**
     * Generate an HTML report file from a full scan result.
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

        $filePath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'xray-report.html';

        $html = $this->buildHtml($scanResult);

        if (file_put_contents($filePath, $html) === false) {
            throw new RuntimeException("Failed to write HTML report to: {$filePath}");
        }

        return $filePath;
    }

    /**
     * Build the full HTML document string.
     *
     * @param  array  $scanResult
     * @return string
     */
    private function buildHtml(array $scanResult): string
    {
        $summary = $scanResult['summary'] ?? [];
        $routes = $scanResult['routes'] ?? [];
        $deadCode = $scanResult['dead_code'] ?? [];
        $architecture = $scanResult['architecture'] ?? [];
        $models = $scanResult['models'] ?? [];
        $scannedAt = $scanResult['scanned_at'] ?? date('c');

        $totalControllers = $summary['total_controllers'] ?? 0;
        $totalModels = $summary['total_models'] ?? 0;
        $totalRoutes = $summary['total_routes'] ?? 0;
        $totalViews = $summary['total_views'] ?? 0;
        $totalServices = $summary['total_services'] ?? 0;
        $totalMiddleware = $summary['total_middleware'] ?? 0;
        $totalFormRequests = $summary['total_form_requests'] ?? 0;
        $deadControllers = $summary['dead_controllers'] ?? 0;
        $deadModels = $summary['dead_models'] ?? 0;
        $deadViews = $summary['dead_views'] ?? 0;
        $deadServices = $summary['dead_services'] ?? 0;
        $totalDead = $deadControllers + $deadModels + $deadViews + $deadServices;

        $routesHtml = $this->buildRoutesTable($routes);
        $deadCodeHtml = $this->buildDeadCodeSection($deadCode);
        $architectureHtml = $this->buildArchitectureSection($architecture);
        $modelsHtml = $this->buildModelsSection($models);

        $deadBadgeClass = $totalDead > 0 ? 'bg-danger' : 'bg-success';
        $deadBadgeText = $totalDead > 0 ? $totalDead . ' issues' : 'Clean';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel X-Ray Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-stat { border-left: 4px solid; }
        .card-stat.controllers { border-color: #0d6efd; }
        .card-stat.models { border-color: #198754; }
        .card-stat.routes { border-color: #ffc107; }
        .card-stat.views { border-color: #0dcaf0; }
        .card-stat.services { border-color: #6f42c1; }
        .card-stat.dead { border-color: #dc3545; }
        .tree-list { font-family: monospace; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        pre { background: #1e1e1e; color: #d4d4d4; border-radius: 6px; padding: 1rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">Laravel X-Ray</span>
        <span class="text-muted small">Scanned at: {$scannedAt}</span>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat controllers h-100">
                <div class="card-body">
                    <div class="text-muted small">Controllers</div>
                    <div class="fs-3 fw-bold text-primary">{$totalControllers}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat models h-100">
                <div class="card-body">
                    <div class="text-muted small">Models</div>
                    <div class="fs-3 fw-bold text-success">{$totalModels}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat routes h-100">
                <div class="card-body">
                    <div class="text-muted small">Routes</div>
                    <div class="fs-3 fw-bold text-warning">{$totalRoutes}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat views h-100">
                <div class="card-body">
                    <div class="text-muted small">Views</div>
                    <div class="fs-3 fw-bold text-info">{$totalViews}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat services h-100">
                <div class="card-body">
                    <div class="text-muted small">Services</div>
                    <div class="fs-3 fw-bold text-purple">{$totalServices}</div>
                    <div class="text-muted small mt-1">Middleware: {$totalMiddleware} &bull; Requests: {$totalFormRequests}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
            <div class="card card-stat dead h-100">
                <div class="card-body">
                    <div class="text-muted small">Dead Code</div>
                    <div class="fs-3 fw-bold text-danger">{$totalDead}</div>
                    <span class="badge {$deadBadgeClass}">{$deadBadgeText}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dead Code Summary -->
    <div class="row g-3 mb-4">
        <div class="col-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Dead Controllers</div><div class="fw-bold text-danger">{$deadControllers}</div></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Dead Models</div><div class="fw-bold text-danger">{$deadModels}</div></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Dead Views</div><div class="fw-bold text-danger">{$deadViews}</div></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body text-center"><div class="text-muted small">Dead Services</div><div class="fw-bold text-danger">{$deadServices}</div></div></div></div>
    </div>

    <div class="row g-4">

        <!-- Routes -->
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-bold">Routes ({$totalRoutes})</div>
                <div class="card-body p-0">
                    {$routesHtml}
                </div>
            </div>
        </div>

        <!-- Dead Code -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header fw-bold text-danger">Dead Code</div>
                <div class="card-body">
                    {$deadCodeHtml}
                </div>
            </div>
        </div>

        <!-- Architecture -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header fw-bold">Architecture</div>
                <div class="card-body">
                    {$architectureHtml}
                </div>
            </div>
        </div>

        <!-- Models -->
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-bold">Model Relationships</div>
                <div class="card-body">
                    {$modelsHtml}
                </div>
            </div>
        </div>

    </div>

    <div class="text-center text-muted small my-4">
        Generated by <strong>Laravel X-Ray</strong> &mdash; {$scannedAt}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
    }

    /**
     * Build the routes HTML table.
     *
     * @param  array  $routes
     * @return string
     */
    private function buildRoutesTable(array $routes): string
    {
        if (empty($routes)) {
            return '<p class="text-muted p-3">No routes detected.</p>';
        }

        $rows = '';
        foreach ($routes as $route) {
            $method = htmlspecialchars($route['method'] ?? '');
            $uri = htmlspecialchars($route['uri'] ?? '');
            $controller = htmlspecialchars($route['controller'] ?? '');
            $action = htmlspecialchars($route['action'] ?? '');
            $name = htmlspecialchars($route['name'] ?? '');

            $methodClass = $this->methodBadgeClass($method);
            $rows .= "<tr><td><span class=\"badge {$methodClass}\">{$method}</span></td>"
                . "<td><code>{$uri}</code></td>"
                . "<td>{$controller}</td>"
                . "<td>{$action}</td>"
                . "<td><small class=\"text-muted\">{$name}</small></td></tr>";
        }

        return '<div class="table-responsive"><table class="table table-sm table-hover mb-0">'
            . '<thead class="table-light"><tr>'
            . '<th>Method</th><th>URI</th><th>Controller</th><th>Action</th><th>Name</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
    }

    /**
     * Build the dead code HTML section.
     *
     * @param  array  $deadCode
     * @return string
     */
    private function buildDeadCodeSection(array $deadCode): string
    {
        $html = '';

        $sections = [
            'Unused Controllers' => $deadCode['controllers'] ?? [],
            'Unused Models' => $deadCode['models'] ?? [],
            'Unused Views' => $deadCode['views'] ?? [],
            'Unused Services' => $deadCode['services'] ?? [],
        ];

        $nameKeys = [
            'Unused Controllers' => 'class',
            'Unused Models' => 'class',
            'Unused Views' => 'name',
            'Unused Services' => 'class',
        ];

        $hasAny = false;

        foreach ($sections as $title => $items) {
            if (empty($items)) {
                continue;
            }

            $hasAny = true;
            $html .= '<h6 class="text-danger">' . htmlspecialchars($title) . '</h6><ul class="list-group list-group-flush mb-3">';

            $key = $nameKeys[$title];
            foreach ($items as $item) {
                $name = htmlspecialchars($item[$key] ?? 'Unknown');
                $file = htmlspecialchars($item['file'] ?? '');
                $html .= "<li class=\"list-group-item list-group-item-danger py-1\">"
                    . "<strong>{$name}</strong>"
                    . ($file !== '' ? "<br><small class=\"text-muted\">{$file}</small>" : '')
                    . '</li>';
            }

            $html .= '</ul>';
        }

        if (! $hasAny) {
            $html = '<div class="alert alert-success mb-0">No dead code detected. Your project is clean!</div>';
        }

        return $html;
    }

    /**
     * Build the architecture dependency tree HTML.
     *
     * @param  array  $architecture
     * @return string
     */
    private function buildArchitectureSection(array $architecture): string
    {
        $trees = $architecture['trees'] ?? [];
        $layers = $architecture['layers'] ?? [];

        if (empty($trees) && empty($layers)) {
            return '<p class="text-muted">No architecture data available.</p>';
        }

        $html = '';

        if (! empty($layers)) {
            $html .= '<div class="mb-3">'
                . '<strong>Detected Layers:</strong> '
                . '<span class="text-primary">' . htmlspecialchars(implode(' &rarr; ', $layers)) . '</span>'
                . '</div>';
        }

        if (! empty($trees)) {
            $html .= '<div class="tree-list">';
            foreach ($trees as $rootFqcn => $dependencies) {
                $rootName = htmlspecialchars(ClassParser::classBasename($rootFqcn));
                $html .= '<ul class="list-unstyled mb-3">'
                    . "<li><strong>{$rootName}</strong>"
                    . $this->buildTreeHtml($dependencies)
                    . '</li></ul>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Recursively build an HTML nested list for a dependency tree.
     *
     * @param  array  $children
     * @return string
     */
    private function buildTreeHtml(array $children): string
    {
        if (empty($children)) {
            return '';
        }

        $html = '<ul>';
        foreach ($children as $fqcn => $subChildren) {
            $name = htmlspecialchars(ClassParser::classBasename($fqcn));
            $html .= "<li>{$name}" . $this->buildTreeHtml($subChildren) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Build the models relationships HTML table.
     *
     * @param  array  $models
     * @return string
     */
    private function buildModelsSection(array $models): string
    {
        if (empty($models)) {
            return '<p class="text-muted">No models detected.</p>';
        }

        $rows = '';
        foreach ($models as $model) {
            $className = htmlspecialchars($model['class'] ?? '');
            $namespace = htmlspecialchars($model['namespace'] ?? '');
            $relationships = $model['relationships'] ?? [];

            if (empty($relationships)) {
                $relHtml = '<span class="text-muted">None</span>';
            } else {
                $relParts = [];
                foreach ($relationships as $rel) {
                    $type = htmlspecialchars($rel['type'] ?? '');
                    $related = htmlspecialchars($rel['related'] ?? '');
                    $method = htmlspecialchars($rel['method'] ?? '');
                    $relParts[] = "<span class=\"badge bg-secondary\">{$type}</span> {$method}() &rarr; {$related}";
                }
                $relHtml = implode('<br>', $relParts);
            }

            $rows .= "<tr><td><strong>{$className}</strong><br><small class=\"text-muted\">{$namespace}</small></td>"
                . "<td>{$relHtml}</td></tr>";
        }

        return '<div class="table-responsive"><table class="table table-sm table-hover mb-0">'
            . '<thead class="table-light"><tr><th>Model</th><th>Relationships</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table></div>';
    }

    /**
     * Return the Bootstrap badge CSS class for an HTTP method.
     *
     * @param  string  $method
     * @return string
     */
    private function methodBadgeClass(string $method): string
    {
        $map = [
            'GET' => 'bg-success',
            'POST' => 'bg-primary',
            'PUT' => 'bg-warning text-dark',
            'PATCH' => 'bg-warning text-dark',
            'DELETE' => 'bg-danger',
            'OPTIONS' => 'bg-secondary',
            'ANY' => 'bg-dark',
        ];

        return $map[strtoupper($method)] ?? 'bg-secondary';
    }
}
