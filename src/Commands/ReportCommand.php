<?php

namespace Larapack\Xray\Commands;

use Illuminate\Console\Command;
use Larapack\Xray\Reporters\HtmlReporter;
use Larapack\Xray\Reporters\JsonReporter;
use Larapack\Xray\Reporters\MarkdownReporter;
use Larapack\Xray\Reporters\MermaidReporter;
use Larapack\Xray\Support\ProjectScanner;

class ReportCommand extends Command
{
    protected $signature = 'xray:report
        {--format=all : Report format (json, markdown, mermaid, html, all)}
        {--path= : Override the base path for scanning}';

    protected $description = 'Generate comprehensive project reports and save to storage';

    public function handle(): int
    {
        try {
            $this->info('Generating project reports...');
            $this->newLine();

            $basePath = $this->option('path');
            $scanner = new ProjectScanner($basePath ?: null);
            $result = $scanner->scan();

            $outputPath = config('xray.output_path');

            if (! is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }

            $format = $this->option('format');
            $generatedFiles = [];

            $architecture = $result['architecture'] ?? [];
            $deadCode = $result['dead_code'] ?? [];

            if ($format === 'json' || $format === 'all') {
                $jsonReporter = new JsonReporter();
                $generatedFiles[] = $jsonReporter->generateFullReport($result, $outputPath);
                $generatedFiles[] = $jsonReporter->generateArchitectureReport($architecture, $outputPath);
                $generatedFiles[] = $jsonReporter->generateDeadCodeReport($deadCode, $outputPath);
            }

            if ($format === 'markdown' || $format === 'all') {
                $markdownReporter = new MarkdownReporter();
                $generatedFiles[] = $markdownReporter->generate($result, $outputPath);
            }

            if ($format === 'mermaid' || $format === 'all') {
                $mermaidReporter = new MermaidReporter();
                $generatedFiles[] = $mermaidReporter->generate($architecture, $outputPath);
            }

            if ($format === 'html' || $format === 'all') {
                $htmlReporter = new HtmlReporter();
                $generatedFiles[] = $htmlReporter->generate($result, $outputPath);
            }

            if (empty($generatedFiles)) {
                $this->warn("Unknown format: {$format}. Use json, markdown, mermaid, html, or all.");

                return self::FAILURE;
            }

            $this->info('Generated files:');
            foreach ($generatedFiles as $file) {
                $this->line("  - {$file}");
            }

            $this->newLine();
            $this->info("Reports generated successfully!");
            $this->line("  Output directory: {$outputPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Report generation failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
