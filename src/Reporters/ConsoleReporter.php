<?php

namespace Larapack\Xray\Reporters;

use Larapack\Xray\Support\ClassParser;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Formats scan results for Artisan console output.
 *
 * This reporter does not write files. It writes directly to a Symfony
 * Console OutputInterface using styled tags for colored terminal output:
 * <info> for counts/highlights, <comment> for section headers, and
 * <error> for warnings and dead code findings.
 */
class ConsoleReporter
{
    /**
     * Print the project health summary table.
     *
     * Displays a table of all component counts and dead code counts
     * in a formatted console output.
     *
     * @param  array            $scanResult  Complete scan result from ProjectScanner::scan()
     * @param  OutputInterface  $output      Symfony console output
     * @return void
     */
    public function printSummary(array $scanResult, OutputInterface $output): void
    {
        $summary = $scanResult['summary'] ?? [];

        $output->writeln('');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('<comment>  Project X-Ray Summary</comment>');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('');

        $output->writeln('  <comment>Components</comment>');
        $output->writeln('  ---------------------------------');
        $output->writeln('  Controllers:   <info>' . ($summary['total_controllers'] ?? 0) . '</info>');
        $output->writeln('  Models:        <info>' . ($summary['total_models'] ?? 0) . '</info>');
        $output->writeln('  Services:      <info>' . ($summary['total_services'] ?? 0) . '</info>');
        $output->writeln('  Repositories:  <info>' . ($summary['total_repositories'] ?? 0) . '</info>');
        $output->writeln('  Routes:        <info>' . ($summary['total_routes'] ?? 0) . '</info>');
        $output->writeln('  Views:         <info>' . ($summary['total_views'] ?? 0) . '</info>');
        $output->writeln('');

        $deadControllers = $summary['dead_controllers'] ?? 0;
        $deadModels = $summary['dead_models'] ?? 0;
        $deadViews = $summary['dead_views'] ?? 0;
        $deadServices = $summary['dead_services'] ?? 0;
        $totalDead = $deadControllers + $deadModels + $deadViews + $deadServices;

        $output->writeln('  <comment>Dead Code</comment>');
        $output->writeln('  ---------------------------------');

        $this->printDeadCodeCount($output, 'Controllers', $deadControllers);
        $this->printDeadCodeCount($output, 'Models', $deadModels);
        $this->printDeadCodeCount($output, 'Views', $deadViews);
        $this->printDeadCodeCount($output, 'Services', $deadServices);

        $output->writeln('');

        if ($totalDead === 0) {
            $output->writeln('  <info>No dead code detected.</info>');
        } else {
            $output->writeln('  <error> ' . $totalDead . ' potential dead code item(s) found. </error>');
        }

        $output->writeln('');
        $output->writeln('  Scanned at: ' . ($scanResult['scanned_at'] ?? 'N/A'));
        $output->writeln('');
    }

    /**
     * Print architecture dependency trees.
     *
     * Renders each dependency tree using box-drawing characters for
     * proper visual indentation in the terminal.
     *
     * @param  array            $architecture  Architecture data from DependencyAnalyzer::analyze()
     * @param  OutputInterface  $output        Symfony console output
     * @return void
     */
    public function printArchitecture(array $architecture, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('<comment>  Architecture - Dependency Trees</comment>');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('');

        $trees = $architecture['trees'] ?? [];

        if (empty($trees)) {
            $output->writeln('  No dependency trees detected.');
            $output->writeln('');
            return;
        }

        foreach ($trees as $rootFqcn => $dependencies) {
            $rootName = ClassParser::classBasename($rootFqcn);
            $output->writeln('  <info>' . $rootName . '</info>');
            $this->printTreeBranches($dependencies, '  ', $output);
            $output->writeln('');
        }

        // Print detected layers
        $layers = $architecture['layers'] ?? [];
        if (! empty($layers)) {
            $output->writeln('<comment>  Detected Layers</comment>');
            $output->writeln('  ---------------------------------');
            $output->writeln('  ' . implode(' -> ', $layers));
            $output->writeln('');
        }
    }

    /**
     * Print dead code findings grouped by type.
     *
     * Each category (controllers, models, views, services) is printed
     * as a separate section with class names and file paths.
     *
     * @param  array            $deadCode  Dead code data from ProjectScanner
     * @param  OutputInterface  $output    Symfony console output
     * @return void
     */
    public function printDeadCode(array $deadCode, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('<comment>  Dead Code Analysis</comment>');
        $output->writeln('<comment>===================================</comment>');
        $output->writeln('');

        $hasDeadCode = false;

        $hasDeadCode = $this->printDeadCodeGroup(
            $output,
            'Unused Controllers',
            $deadCode['controllers'] ?? [],
            'class'
        ) || $hasDeadCode;

        $hasDeadCode = $this->printDeadCodeGroup(
            $output,
            'Unused Models',
            $deadCode['models'] ?? [],
            'class'
        ) || $hasDeadCode;

        $hasDeadCode = $this->printDeadCodeGroup(
            $output,
            'Unused Views',
            $deadCode['views'] ?? [],
            'name'
        ) || $hasDeadCode;

        $hasDeadCode = $this->printDeadCodeGroup(
            $output,
            'Unused Services',
            $deadCode['services'] ?? [],
            'class'
        ) || $hasDeadCode;

        if (! $hasDeadCode) {
            $output->writeln('  <info>No dead code detected. Your project is clean!</info>');
            $output->writeln('');
        }
    }

    /**
     * Print a full health report combining summary, architecture layers, and dead code.
     *
     * This is a convenience method that prints the most important information
     * from each section in a single, compact output.
     *
     * @param  array            $scanResult  Complete scan result from ProjectScanner::scan()
     * @param  OutputInterface  $output      Symfony console output
     * @return void
     */
    public function printHealthReport(array $scanResult, OutputInterface $output): void
    {
        $this->printSummary($scanResult, $output);

        $architecture = $scanResult['architecture'] ?? [];
        $layers = $architecture['layers'] ?? [];
        if (! empty($layers)) {
            $output->writeln('<comment>  Architecture Layers</comment>');
            $output->writeln('  ---------------------------------');
            $output->writeln('  ' . implode(' -> ', $layers));
            $output->writeln('');
        }

        $deadCode = $scanResult['dead_code'] ?? [];
        $totalDead = count($deadCode['controllers'] ?? [])
            + count($deadCode['models'] ?? [])
            + count($deadCode['views'] ?? [])
            + count($deadCode['services'] ?? []);

        if ($totalDead > 0) {
            $this->printDeadCode($deadCode, $output);
        }
    }

    /**
     * Recursively print tree branches using box-drawing characters.
     *
     * Handles last-item detection to use the correct connector character:
     * - Non-last items use "├── " with a "│   " continuation prefix
     * - Last items use "└── " with a "    " continuation prefix
     *
     * @param  array            $children  Nested dependency tree
     * @param  string           $prefix    Current indentation prefix
     * @param  OutputInterface  $output    Console output
     * @return void
     */
    private function printTreeBranches(array $children, string $prefix, OutputInterface $output): void
    {
        $keys = array_keys($children);
        $total = count($keys);

        foreach ($keys as $index => $fqcn) {
            $isLast = ($index === $total - 1);
            $connector = $isLast ? '└── ' : '├── ';
            $childPrefix = $isLast ? '    ' : '│   ';

            $shortName = ClassParser::classBasename($fqcn);
            $output->writeln($prefix . $connector . '<info>' . $shortName . '</info>');

            $subChildren = $children[$fqcn];
            if (is_array($subChildren) && ! empty($subChildren)) {
                $this->printTreeBranches($subChildren, $prefix . $childPrefix, $output);
            }
        }
    }

    /**
     * Print a single dead code count line, using error styling when count is non-zero.
     *
     * @param  OutputInterface  $output
     * @param  string           $label
     * @param  int              $count
     * @return void
     */
    private function printDeadCodeCount(OutputInterface $output, string $label, int $count): void
    {
        $padding = str_repeat(' ', max(0, 15 - strlen($label)));

        if ($count > 0) {
            $output->writeln('  ' . $label . ':' . $padding . '<error> ' . $count . ' </error>');
        } else {
            $output->writeln('  ' . $label . ':' . $padding . '<info>' . $count . '</info>');
        }
    }

    /**
     * Print a group of dead code items with a section header.
     *
     * @param  OutputInterface  $output
     * @param  string           $title     Section heading
     * @param  array            $items     Array of dead code items
     * @param  string           $nameKey   Key to use for the display name ('class' or 'name')
     * @return bool  True if items were printed, false if the group was empty
     */
    private function printDeadCodeGroup(
        OutputInterface $output,
        string $title,
        array $items,
        string $nameKey
    ): bool {
        if (empty($items)) {
            return false;
        }

        $output->writeln('  <comment>' . $title . '</comment>');
        $output->writeln('  ---------------------------------');

        foreach ($items as $item) {
            $name = $item[$nameKey] ?? 'Unknown';
            $file = $item['file'] ?? '';
            $output->writeln('  <error> ! </error> ' . $name . ($file !== '' ? ' (' . $file . ')' : ''));
        }

        $output->writeln('');

        return true;
    }

}
