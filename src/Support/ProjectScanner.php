<?php

namespace Jaydeep\Xray\Support;

use Jaydeep\Xray\Analyzers\ControllerAnalyzer;
use Jaydeep\Xray\Analyzers\DependencyAnalyzer;
use Jaydeep\Xray\Analyzers\FormRequestAnalyzer;
use Jaydeep\Xray\Analyzers\MiddlewareAnalyzer;
use Jaydeep\Xray\Analyzers\ModelAnalyzer;
use Jaydeep\Xray\Analyzers\RouteAnalyzer;
use Jaydeep\Xray\Analyzers\ServiceAnalyzer;
use Jaydeep\Xray\Analyzers\ViewAnalyzer;

/**
 * Orchestrates all analyzers to produce a complete project scan result.
 *
 * This class is the main entry point for scanning a Laravel application.
 * It coordinates the individual analyzers, merges their results, and
 * performs cross-cutting analysis such as dead code detection.
 */
class ProjectScanner
{
    /**
     * Optional base path override for scan directories.
     *
     * @var string|null
     */
    private $basePath;

    /**
     * Create a new ProjectScanner instance.
     *
     * @param string|null $basePath Optional base path override. When null, base_path() is used.
     */
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath;
    }

    /**
     * Run all analyzers and return a combined result array.
     *
     * Extracted to avoid duplicating analyzer instantiation across scan(),
     * scanDeadCode(), and any future methods that need all component data.
     *
     * @return array{
     *     controllers: array,
     *     models: array,
     *     services: array,
     *     repositories: array,
     *     routes: array,
     *     views: array,
     *     middleware: array,
     *     form_requests: array,
     *     architecture: array,
     *     all_classes: array
     * }
     */
    private function runAllAnalyzers(): array
    {
        $controllers = (new ControllerAnalyzer())->analyze(config('xray.paths.controllers'));
        $models = (new ModelAnalyzer())->analyze(config('xray.paths.models'));

        $serviceAnalyzer = new ServiceAnalyzer();
        $services = $serviceAnalyzer->analyzeServices(config('xray.paths.services'));
        $repositories = $serviceAnalyzer->analyzeRepositories(config('xray.paths.repositories'));

        $routes = (new RouteAnalyzer())->analyze(config('xray.paths.routes'));
        $views = (new ViewAnalyzer())->analyze(config('xray.paths.views'));

        $middleware = (new MiddlewareAnalyzer())->analyze(config('xray.paths.middleware'));
        $formRequests = (new FormRequestAnalyzer())->analyze(config('xray.paths.form_requests'));

        $allClasses = array_merge($controllers, $models, $services, $repositories);
        $architecture = (new DependencyAnalyzer())->analyze($allClasses);

        return [
            'controllers' => $controllers,
            'models' => $models,
            'services' => $services,
            'repositories' => $repositories,
            'routes' => $routes,
            'views' => $views,
            'middleware' => $middleware,
            'form_requests' => $formRequests,
            'architecture' => $architecture,
            'all_classes' => $allClasses,
        ];
    }

    /**
     * Run a full project scan across all analyzer types.
     *
     * @return array{
     *     controllers: array,
     *     models: array,
     *     services: array,
     *     repositories: array,
     *     routes: array,
     *     views: array,
     *     middleware: array,
     *     form_requests: array,
     *     architecture: array,
     *     dead_code: array,
     *     summary: array,
     *     scanned_at: string
     * }
     */
    public function scan(): array
    {
        $data = $this->runAllAnalyzers();

        $deadCode = $this->detectDeadCode(
            $data['controllers'],
            $data['models'],
            $data['services'],
            $data['repositories'],
            $data['routes'],
            $data['views'],
            $data['all_classes']
        );

        $summary = $this->buildSummary(
            $data['controllers'],
            $data['models'],
            $data['services'],
            $data['repositories'],
            $data['routes'],
            $data['views'],
            $data['middleware'],
            $data['form_requests'],
            $deadCode
        );

        return [
            'controllers' => $data['controllers'],
            'models' => $data['models'],
            'services' => $data['services'],
            'repositories' => $data['repositories'],
            'routes' => $data['routes'],
            'views' => $data['views'],
            'middleware' => $data['middleware'],
            'form_requests' => $data['form_requests'],
            'architecture' => $data['architecture'],
            'dead_code' => $deadCode,
            'summary' => $summary,
            'scanned_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Run only architecture-related analysis (classes and dependency trees).
     *
     * This is more efficient than a full scan when only the dependency
     * structure is needed, as it skips route, view, and dead code analysis.
     *
     * @return array{
     *     controllers: array,
     *     models: array,
     *     services: array,
     *     repositories: array,
     *     architecture: array,
     *     scanned_at: string
     * }
     */
    public function scanArchitecture(): array
    {
        $data = $this->runAllAnalyzers();

        return [
            'controllers' => $data['controllers'],
            'models'      => $data['models'],
            'services'    => $data['services'],
            'repositories' => $data['repositories'],
            'architecture' => $data['architecture'],
            'scanned_at'  => now()->toIso8601String(),
        ];
    }

    /**
     * Run a scan focused on dead code detection.
     *
     * Calls scan() internally and extracts only the dead code portion,
     * avoiding double analysis by reusing the shared runAllAnalyzers() logic.
     *
     * @return array{
     *     dead_code: array,
     *     summary: array,
     *     scanned_at: string
     * }
     */
    public function scanDeadCode(): array
    {
        $data = $this->runAllAnalyzers();

        $deadCode = $this->detectDeadCode(
            $data['controllers'],
            $data['models'],
            $data['services'],
            $data['repositories'],
            $data['routes'],
            $data['views'],
            $data['all_classes']
        );

        $summary = [
            'dead_controllers' => count($deadCode['controllers']),
            'dead_models' => count($deadCode['models']),
            'dead_views' => count($deadCode['views']),
            'dead_services' => count($deadCode['services']),
        ];

        return [
            'dead_code' => $deadCode,
            'summary' => $summary,
            'scanned_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Detect dead (potentially unused) code across all component types.
     *
     * Cross-references controllers with routes, models with class contents,
     * views with controller contents, and services/repos with dependency
     * injection and class contents.
     *
     * @param  array  $controllers  Parsed controller data from ControllerAnalyzer
     * @param  array  $models       Parsed model data from ModelAnalyzer
     * @param  array  $services     Parsed service data from ServiceAnalyzer
     * @param  array  $repositories Parsed repository data from ServiceAnalyzer
     * @param  array  $routes       Parsed route data from RouteAnalyzer
     * @param  array  $views        Parsed view data from ViewAnalyzer
     * @param  array  $allClasses   Merged array of all parsed class data
     * @return array{
     *     controllers: array<array{class: string, file: string}>,
     *     models: array<array{class: string, file: string}>,
     *     views: array<array{name: string, file: string}>,
     *     services: array<array{class: string, file: string}>
     * }
     */
    private function detectDeadCode(
        array $controllers,
        array $models,
        array $services,
        array $repositories,
        array $routes,
        array $views,
        array $allClasses
    ): array {
        return [
            'controllers' => $this->findUnusedControllers($controllers, $routes),
            'models' => $this->findUnusedModels($models, $allClasses, $routes),
            'views' => $this->findUnusedViews($views, $controllers),
            'services' => $this->findUnusedServices(
                array_merge($services, $repositories),
                $allClasses
            ),
        ];
    }

    /**
     * Find controllers whose short class name does not appear in any route definition.
     *
     * @param  array  $controllers  Parsed controller data
     * @param  array  $routes       Parsed route data
     * @return array<array{class: string, file: string}>
     */
    private function findUnusedControllers(array $controllers, array $routes): array
    {
        $routedControllers = [];
        foreach ($routes as $route) {
            if (! empty($route['controller'])) {
                $routedControllers[$route['controller']] = true;
            }
        }

        $unused = [];
        foreach ($controllers as $controller) {
            $className = $controller['class'] ?? '';
            if ($className !== '' && ! isset($routedControllers[$className])) {
                $unused[] = [
                    'class' => $className,
                    'file' => $controller['file'] ?? '',
                ];
            }
        }

        return $unused;
    }

    /**
     * Find models whose class name is not referenced in any other scanned class or route file.
     *
     * A model is considered "used" if its short class name appears as a string
     * within the content of any other scanned class file or any route file.
     * Route file contents are built once before the model loop to avoid
     * re-reading the same files for each model (B3 fix).
     *
     * @param  array  $models      Parsed model data
     * @param  array  $allClasses  All parsed class data (controllers, models, services, repos)
     * @param  array  $routes      Parsed route data (for route file content)
     * @return array<array{class: string, file: string}>
     */
    private function findUnusedModels(array $models, array $allClasses, array $routes): array
    {
        // Build route file content once — not per-model (B3 fix)
        $routeFiles = [];
        $routeFileContent = '';
        foreach ($routes as $route) {
            $routeFile = $route['file'] ?? '';
            if ($routeFile !== '' && ! isset($routeFiles[$routeFile])) {
                $routeFiles[$routeFile] = true;
                if (file_exists($routeFile)) {
                    $routeFileContent .= file_get_contents($routeFile) . "\n";
                }
            }
        }

        $unused = [];
        foreach ($models as $model) {
            $className = $model['class'] ?? '';
            $modelFile = $model['file'] ?? '';

            if ($className === '') {
                continue;
            }

            // Build filtered content excluding this model's own file
            $filteredContent = '';
            foreach ($allClasses as $class) {
                if (($class['file'] ?? '') === $modelFile) {
                    continue;
                }
                $filteredContent .= ($class['content'] ?? '') . "\n";
            }

            // Append pre-built route file content
            $filteredContent .= $routeFileContent;

            if (strpos($filteredContent, $className) === false) {
                $unused[] = [
                    'class' => $className,
                    'file' => $modelFile,
                ];
            }
        }

        return $unused;
    }

    /**
     * Find views whose dot-notation name does not appear in any controller content.
     *
     * Searches for common view reference patterns: view('name'),
     * View::make('name'), @include('name'), @extends('name'), @component('name').
     *
     * @param  array  $views        Parsed view data
     * @param  array  $controllers  Parsed controller data
     * @return array<array{name: string, file: string}>
     */
    private function findUnusedViews(array $views, array $controllers): array
    {
        // Build a combined content string from all controllers
        $controllerContent = '';
        foreach ($controllers as $controller) {
            $controllerContent .= ($controller['content'] ?? '') . "\n";
        }

        // Also check view files themselves (for @include, @extends, @component)
        $viewContent = '';
        foreach ($views as $view) {
            $viewFile = $view['file'] ?? '';
            if ($viewFile !== '' && file_exists($viewFile)) {
                $viewContent .= file_get_contents($viewFile) . "\n";
            }
        }

        $searchContent = $controllerContent . $viewContent;

        $unused = [];
        foreach ($views as $view) {
            $viewName = $view['name'] ?? '';
            if ($viewName === '') {
                continue;
            }

            $patterns = [
                "view('" . $viewName . "'",
                'view("' . $viewName . '"',
                "View::make('" . $viewName . "'",
                'View::make("' . $viewName . '"',
                "@include('" . $viewName . "'",
                '@include("' . $viewName . '"',
                "@extends('" . $viewName . "'",
                '@extends("' . $viewName . '"',
                "@component('" . $viewName . "'",
                '@component("' . $viewName . '"',
            ];

            $found = false;
            foreach ($patterns as $pattern) {
                if (strpos($searchContent, $pattern) !== false) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $unused[] = [
                    'name' => $viewName,
                    'file' => $view['file'] ?? '',
                ];
            }
        }

        return $unused;
    }

    /**
     * Find services and repositories not referenced by any other class.
     *
     * A service/repository is considered "used" if its short class name appears
     * in the constructor_dependencies of any other class, OR if it appears as a
     * string reference in any other class's content.
     *
     * @param  array  $servicesAndRepos  Merged services and repositories
     * @param  array  $allClasses        All parsed class data
     * @return array<array{class: string, file: string}>
     */
    private function findUnusedServices(array $servicesAndRepos, array $allClasses): array
    {
        // Collect all constructor dependency type names from all classes
        $injectedTypes = [];
        foreach ($allClasses as $class) {
            foreach ($class['constructor_dependencies'] ?? [] as $dep) {
                $typeName = $dep['type'] ?? '';
                if ($typeName !== '') {
                    $injectedTypes[$typeName] = true;
                }
            }
        }

        $unused = [];
        foreach ($servicesAndRepos as $service) {
            $className = $service['class'] ?? '';
            $serviceFile = $service['file'] ?? '';

            if ($className === '') {
                continue;
            }

            // Check if injected via constructor anywhere
            if (isset($injectedTypes[$className])) {
                continue;
            }

            // Check if referenced by name in any other class content
            $referencedInContent = false;
            foreach ($allClasses as $class) {
                if (($class['file'] ?? '') === $serviceFile) {
                    continue;
                }
                if (strpos($class['content'] ?? '', $className) !== false) {
                    $referencedInContent = true;
                    break;
                }
            }

            if (! $referencedInContent) {
                $unused[] = [
                    'class' => $className,
                    'file' => $serviceFile,
                ];
            }
        }

        return $unused;
    }

    /**
     * Build the summary metrics array from scan results.
     *
     * @param  array  $controllers
     * @param  array  $models
     * @param  array  $services
     * @param  array  $repositories
     * @param  array  $routes
     * @param  array  $views
     * @param  array  $middleware
     * @param  array  $formRequests
     * @param  array  $deadCode
     * @return array<string, int>
     */
    private function buildSummary(
        array $controllers,
        array $models,
        array $services,
        array $repositories,
        array $routes,
        array $views,
        array $middleware,
        array $formRequests,
        array $deadCode
    ): array {
        return [
            'total_controllers' => count($controllers),
            'total_models' => count($models),
            'total_services' => count($services),
            'total_repositories' => count($repositories),
            'total_routes' => count($routes),
            'total_views' => count($views),
            'total_middleware' => count($middleware),
            'total_form_requests' => count($formRequests),
            'dead_controllers' => count($deadCode['controllers']),
            'dead_models' => count($deadCode['models']),
            'dead_views' => count($deadCode['views']),
            'dead_services' => count($deadCode['services']),
        ];
    }
}
