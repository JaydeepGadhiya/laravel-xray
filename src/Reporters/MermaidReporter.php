<?php

namespace Jaydeep\Xray\Reporters;

use RuntimeException;
use Jaydeep\Xray\Support\ClassParser;
use Jaydeep\Xray\Support\Concerns\EnsuresOutputDirectory;

/**
 * Generates Mermaid diagram syntax for architecture visualization.
 *
 * Produces a .mmd file containing both a flowchart (graph TD) and a class
 * diagram representation of the dependency tree. The two diagrams are
 * separated by a comment line and can be rendered by any Mermaid-compatible
 * viewer (GitHub, GitLab, Mermaid Live Editor, etc.).
 */
class MermaidReporter
{
    use EnsuresOutputDirectory;

    /**
     * Generate a Mermaid diagram file from architecture data.
     *
     * Creates the output directory if needed and writes both a flowchart
     * and a class diagram to architecture.mmd.
     *
     * @param  array   $architecture  Architecture data from DependencyAnalyzer::analyze()
     * @param  string  $outputPath    Directory path for output
     * @return string  The absolute path to the written file
     *
     * @throws RuntimeException If the directory cannot be created or the file cannot be written
     */
    public function generate(array $architecture, string $outputPath): string
    {
        $this->ensureDirectoryExists($outputPath);

        $filePath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'architecture.mmd';

        $content = $this->buildFlowchart($architecture)
            . "\n"
            . $this->buildClassDiagram($architecture);

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException("Failed to write Mermaid diagram to: {$filePath}");
        }

        return $filePath;
    }

    /**
     * Build the Mermaid flowchart (graph TD) from dependency trees.
     *
     * @param  array  $architecture
     * @return string
     */
    private function buildFlowchart(array $architecture): string
    {
        $lines = [];
        $lines[] = '%% Flowchart - Dependency Graph';
        $lines[] = 'graph TD';

        $edges = [];
        $trees = $architecture['trees'] ?? [];

        foreach ($trees as $rootFqcn => $dependencies) {
            $rootName = ClassParser::classBasename($rootFqcn);
            $this->collectEdges($rootName, $dependencies, $edges);
        }

        // Deduplicate edges to avoid rendering the same arrow twice
        $seen = [];
        foreach ($edges as $edge) {
            $key = $edge[0] . ' --> ' . $edge[1];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $lines[] = '    ' . $edge[0] . ' --> ' . $edge[1];
            }
        }

        if (empty($seen)) {
            $lines[] = '    NoDependencies[No dependencies detected]';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Build the Mermaid class diagram from dependency trees.
     *
     * @param  array  $architecture
     * @return string
     */
    private function buildClassDiagram(array $architecture): string
    {
        $lines = [];
        $lines[] = '%% Class Diagram - Dependency Structure';
        $lines[] = 'classDiagram';

        $edges = [];
        $trees = $architecture['trees'] ?? [];

        foreach ($trees as $rootFqcn => $dependencies) {
            $rootName = ClassParser::classBasename($rootFqcn);
            $this->collectEdges($rootName, $dependencies, $edges);
        }

        // Deduplicate edges
        $seen = [];
        foreach ($edges as $edge) {
            $key = $edge[0] . ' --> ' . $edge[1];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $lines[] = '    ' . $edge[0] . ' --> ' . $edge[1];
            }
        }

        if (empty($seen)) {
            $lines[] = '    class NoDependencies {';
            $lines[] = '        No dependencies detected';
            $lines[] = '    }';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Recursively collect parent-child edges from a dependency tree.
     *
     * Each edge is stored as a [parent, child] tuple. The method walks the
     * entire tree depth-first, producing one edge per dependency relationship.
     *
     * @param  string  $parentName  Short class name of the parent node
     * @param  array   $children    Nested dependency tree for this parent
     * @param  array   &$edges      Collected edges (passed by reference)
     * @return void
     */
    private function collectEdges(string $parentName, array $children, array &$edges): void
    {
        foreach ($children as $childFqcn => $grandChildren) {
            $childName = ClassParser::classBasename($childFqcn);
            $edges[] = [$parentName, $childName];

            if (is_array($grandChildren) && ! empty($grandChildren)) {
                $this->collectEdges($childName, $grandChildren, $edges);
            }
        }
    }
}
