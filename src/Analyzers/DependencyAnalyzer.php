<?php

namespace Jaydeep\Xray\Analyzers;

use Jaydeep\Xray\Support\ClassParser;

class DependencyAnalyzer
{
    /**
     * Map of fully qualified class names to their parsed class data.
     *
     * @var array<string, array>
     */
    private array $classMap = [];

    /**
     * Analyze all parsed classes to build dependency trees and detect layers.
     *
     * Builds a map of all known classes, then for each controller constructs
     * a recursive dependency tree by following constructor injections. Also
     * detects the architectural layers present in the codebase.
     *
     * @param array $allClasses Array of parsed class data from ClassParser::parse().
     * @return array Associative array with 'trees' and 'layers' keys.
     */
    public function analyze(array $allClasses): array
    {
        // Step 1: Build the class map indexed by FQCN
        $this->classMap = [];

        foreach ($allClasses as $classData) {
            if ($classData['fqcn'] !== '') {
                $this->classMap[$classData['fqcn']] = $classData;
            }
        }

        // Step 2: Build dependency trees for each controller
        $trees = [];

        foreach ($this->classMap as $fqcn => $classData) {
            if ($this->isController($fqcn)) {
                $trees[$fqcn] = $this->buildTree($classData);
            }
        }

        // Step 3: Detect architectural layers from the trees
        $layers = $this->detectLayers($trees);

        return [
            'trees' => $trees,
            'layers' => $layers,
        ];
    }

    /**
     * Recursively build a dependency tree for a given class.
     *
     * Resolves each constructor dependency type hint to a FQCN, looks it up
     * in the class map, and recursively builds sub-trees. Uses a visited set
     * to prevent infinite loops from circular dependencies.
     *
     * @param array $class The parsed class data.
     * @param array $visited Set of FQCNs already visited in this branch (prevents cycles).
     * @return array Nested associative array where keys are FQCNs and values are sub-trees.
     */
    public function buildTree(array $class, array $visited = []): array
    {
        $tree = [];
        $currentFqcn = $class['fqcn'];

        if (in_array($currentFqcn, $visited, true)) {
            return [];
        }

        $visited[] = $currentFqcn;

        foreach ($class['constructor_dependencies'] as $dependency) {
            $resolvedType = $this->resolveType(
                $dependency['type'],
                $class['uses'],
                $class['namespace']
            );

            if (isset($this->classMap[$resolvedType])) {
                $tree[$resolvedType] = $this->buildTree($this->classMap[$resolvedType], $visited);
            } else {
                // Dependency exists but is not in our scanned classes (e.g. framework class)
                $tree[$resolvedType] = [];
            }
        }

        return $tree;
    }

    /**
     * Resolve a type hint to a fully qualified class name.
     *
     * Resolution order:
     * 1. If the type already contains a backslash, treat it as an FQCN.
     * 2. If the type appears in the class's use statements, use that FQCN.
     * 3. Otherwise, assume the type is in the same namespace as the current class.
     *
     * @param string $type The type hint string (short name or FQCN).
     * @param array $uses The use statements map (shortName => fqcn).
     * @param string $namespace The namespace of the class containing the type hint.
     * @return string The resolved fully qualified class name.
     */
    public function resolveType(string $type, array $uses, string $namespace): string
    {
        // Already a FQCN (contains backslash)
        if (strpos($type, '\\') !== false) {
            return ltrim($type, '\\');
        }

        // Found in use statements
        if (isset($uses[$type])) {
            return $uses[$type];
        }

        // Assume same namespace
        if ($namespace !== '') {
            return $namespace . '\\' . $type;
        }

        return $type;
    }

    /**
     * Detect architectural layers from the dependency trees.
     *
     * Examines all FQCNs in the dependency trees and identifies common
     * architectural layer patterns (Controller, Service, Repository, Model, etc.).
     * Returns an ordered array reflecting the typical dependency flow.
     *
     * @param array $trees The dependency trees keyed by controller FQCN.
     * @return array Ordered array of detected layer names (e.g. ['Controller', 'Service', 'Repository', 'Model']).
     */
    public function detectLayers(array $trees): array
    {
        // Collect all FQCNs that appear in the trees (keys at all levels)
        $allFqcns = [];
        $this->collectFqcns($trees, $allFqcns);

        // Also add the tree root keys (controllers)
        foreach (array_keys($trees) as $fqcn) {
            $allFqcns[] = $fqcn;
        }

        // Define layer patterns in typical dependency order
        $layerPatterns = [
            'Controller' => '/Controller$/i',
            'Service' => '/Service$/i',
            'Repository' => '/Repository$/i',
            'Model' => '/^App\\\\Models\\\\/i',
            'Event' => '/Event$/i',
            'Job' => '/Job$/i',
            'Mail' => '/Mail$|Mailable$/i',
            'Notification' => '/Notification$/i',
            'Policy' => '/Policy$/i',
            'Middleware' => '/Middleware$/i',
        ];

        $detectedLayers = [];

        foreach ($layerPatterns as $layerName => $pattern) {
            foreach ($allFqcns as $fqcn) {
                if (preg_match($pattern, $fqcn)) {
                    $detectedLayers[] = $layerName;
                    break;
                }
            }
        }

        return $detectedLayers;
    }

    /**
     * Recursively collect all FQCNs from a nested dependency tree.
     *
     * @param array $tree The nested tree structure.
     * @param array $fqcns Reference to the collection array.
     */
    private function collectFqcns(array $tree, array &$fqcns): void
    {
        foreach ($tree as $fqcn => $children) {
            $fqcns[] = $fqcn;

            if (! empty($children)) {
                $this->collectFqcns($children, $fqcns);
            }
        }
    }

    /**
     * Determine if a FQCN represents a controller class.
     *
     * @param string $fqcn The fully qualified class name.
     * @return bool True if the class name ends with "Controller".
     */
    private function isController(string $fqcn): bool
    {
        return str_ends_with($fqcn, 'Controller');
    }
}
