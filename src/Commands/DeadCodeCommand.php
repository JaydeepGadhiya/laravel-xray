<?php

namespace Jaydeep\Xray\Commands;

use Illuminate\Console\Command;
use Jaydeep\Xray\Reporters\ConsoleReporter;
use Jaydeep\Xray\Reporters\JsonReporter;
use Jaydeep\Xray\Support\ProjectScanner;

class DeadCodeCommand extends Command
{
    protected $signature = 'xray:deadcode
        {--json : Output as JSON}
        {--save : Save report to storage}
        {--path= : Override the base path for scanning}';

    protected $description = 'Detect unused code in the project';

    public function handle(): int
    {
        try {
            $this->info('Scanning for dead code...');
            $this->newLine();

            $basePath = $this->option('path');
            $scanner = new ProjectScanner($basePath ?: null);
            $result = $scanner->scanDeadCode();

            $deadCode = $result['dead_code'] ?? [];
            $summary = $result['summary'] ?? [];

            $totalDead = ($summary['dead_controllers'] ?? 0)
                + ($summary['dead_models'] ?? 0)
                + ($summary['dead_views'] ?? 0)
                + ($summary['dead_services'] ?? 0);

            if ($totalDead === 0) {
                $this->info('No dead code detected!');
            } else {
                $consoleReporter = new ConsoleReporter();
                $consoleReporter->printDeadCode($deadCode, $this->output);
            }

            if ($this->option('json')) {
                $this->newLine();
                $this->line(json_encode($deadCode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $savedPaths = [];

            if ($this->option('save')) {
                $outputPath = config('xray.output_path');

                if (! is_dir($outputPath)) {
                    mkdir($outputPath, 0755, true);
                }

                $jsonReporter = new JsonReporter();
                $savedPaths[] = $jsonReporter->generateDeadCodeReport($deadCode, $outputPath);
            }

            $this->newLine();

            if ($totalDead > 0) {
                $this->warn("Total dead code items found: {$totalDead}");
                $this->line(sprintf(
                    '  Controllers: %d, Models: %d, Views: %d, Services: %d',
                    $summary['dead_controllers'] ?? 0,
                    $summary['dead_models'] ?? 0,
                    $summary['dead_views'] ?? 0,
                    $summary['dead_services'] ?? 0
                ));
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
            $this->error('Dead code scan failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
