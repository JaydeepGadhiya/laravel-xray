<?php

namespace Larapack\Xray\Commands;

use Illuminate\Console\Command;
use Larapack\Xray\Reporters\ConsoleReporter;
use Larapack\Xray\Reporters\HtmlReporter;
use Larapack\Xray\Reporters\JsonReporter;
use Larapack\Xray\Reporters\MarkdownReporter;
use Larapack\Xray\Support\ProjectScanner;

class ScanCommand extends Command
{
    protected $signature = 'xray:scan
        {--json : Output results as JSON to console}
        {--save : Save reports to storage}
        {--path= : Override the base path for scanning}';

    protected $description = 'Scan the Laravel project and display a health overview';

    public function handle(): int
    {
        try {
            $this->info('Scanning project...');
            $this->newLine();

            $basePath = $this->option('path');
            $scanner = new ProjectScanner($basePath ?: null);
            $result = $scanner->scan();
 
            $consoleReporter = new ConsoleReporter();
            $consoleReporter->printHealthReport($result, $this->output);

            if ($this->option('json')) {
                $this->newLine();
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $savedPaths = [];

            if ($this->option('save')) {
                $outputPath = config('xray.output_path');

                if (! is_dir($outputPath)) {
                    mkdir($outputPath, 0755, true);
                }

                $jsonReporter = new JsonReporter();
                $savedPaths[] = $jsonReporter->generateFullReport($result, $outputPath);

                $markdownReporter = new MarkdownReporter();
                $savedPaths[] = $markdownReporter->generate($result, $outputPath);

                $htmlReporter = new HtmlReporter();
                $savedPaths[] = $htmlReporter->generate($result, $outputPath);
            }

            $this->newLine();
            $this->info('Scan complete!');

            if (! empty($savedPaths)) {
                $this->newLine();
                $this->info('Reports saved:');
                foreach ($savedPaths as $path) {
                    $this->line("  - {$path}");
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Scan failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
