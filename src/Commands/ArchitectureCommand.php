<?php

namespace Larapack\Xray\Commands;

use Illuminate\Console\Command;
use Larapack\Xray\Reporters\ConsoleReporter;
use Larapack\Xray\Reporters\JsonReporter;
use Larapack\Xray\Reporters\MermaidReporter;
use Larapack\Xray\Support\ProjectScanner;

class ArchitectureCommand extends Command
{
    protected $signature = 'xray:architecture
        {--json : Output as JSON}
        {--mermaid : Generate Mermaid diagram}
        {--save : Save reports to storage}
        {--path= : Override the base path for scanning}';

    protected $description = 'Analyze and visualize the project architecture';

    public function handle(): int
    {
        try {
            $this->info('Analyzing architecture...');
            $this->newLine();

            $basePath = $this->option('path');
            $scanner = new ProjectScanner($basePath ?: null);
            $result = $scanner->scanArchitecture();

            $architecture = $result['architecture'] ?? [];

            $consoleReporter = new ConsoleReporter();
            $consoleReporter->printArchitecture($architecture, $this->output);

            if ($this->option('json')) {
                $this->newLine();
                $this->line(json_encode($architecture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $savedPaths = [];

            if ($this->option('mermaid') || $this->option('save')) {
                $outputPath = config('xray.output_path');

                if (! is_dir($outputPath)) {
                    mkdir($outputPath, 0755, true);
                }

                if ($this->option('mermaid')) {
                    $mermaidReporter = new MermaidReporter();
                    $mermaidPath = $mermaidReporter->generate($architecture, $outputPath);

                    $this->newLine();
                    $this->info('Mermaid diagram:');
                    $this->newLine();
                    $this->line(file_get_contents($mermaidPath));

                    if ($this->option('save')) {
                        $savedPaths[] = $mermaidPath;
                    }
                }

                if ($this->option('save')) {
                    $jsonReporter = new JsonReporter();
                    $savedPaths[] = $jsonReporter->generateArchitectureReport($architecture, $outputPath);

                    if (! $this->option('mermaid')) {
                        $mermaidReporter = new MermaidReporter();
                        $savedPaths[] = $mermaidReporter->generate($architecture, $outputPath);
                    }
                }
            }

            $this->newLine();

            $this->info('Architecture summary:');
            $this->line(sprintf(
                '  Controllers: %d, Models: %d, Services: %d, Repositories: %d',
                count($result['controllers'] ?? []),
                count($result['models'] ?? []),
                count($result['services'] ?? []),
                count($result['repositories'] ?? [])
            ));

            $layers = $architecture['layers'] ?? [];
            if (! empty($layers)) {
                $this->line('  Detected layers: ' . implode(' -> ', $layers));
            }

            if (! empty($savedPaths)) {
                $this->newLine();
                $this->info('Reports saved:');
                foreach ($savedPaths as $path) {
                    $this->line("  - {$path}");
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Architecture analysis failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
