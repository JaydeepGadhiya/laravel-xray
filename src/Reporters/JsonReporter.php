<?php

namespace Jaydeep\Xray\Reporters;

use RuntimeException;
use Jaydeep\Xray\Support\Concerns\EnsuresOutputDirectory;

/**
 * Generates JSON report files from scan results.
 *
 * Supports full scan reports, architecture-only reports, and dead code reports.
 * All output is written as pretty-printed JSON for human readability.
 */
class JsonReporter
{
    use EnsuresOutputDirectory;

    /**
     * Generate a JSON report file from the given data.
     *
     * Creates the output directory if it does not exist, then writes the data
     * as pretty-printed JSON to the specified filename within that directory.
     *
     * @param  array   $data        The data to serialize as JSON
     * @param  string  $outputPath  Directory path where the file will be written
     * @param  string  $filename    The output filename (default: scan-report.json)
     * @return string  The absolute path to the written file
     *
     * @throws RuntimeException If the directory cannot be created or the file cannot be written
     */
    public function generate(array $data, string $outputPath, string $filename = 'scan-report.json'): string
    {
        $this->ensureDirectoryExists($outputPath);

        $filePath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON: ' . json_last_error_msg());
        }

        if (file_put_contents($filePath, $json . "\n") === false) {
            throw new RuntimeException("Failed to write JSON report to: {$filePath}");
        }

        return $filePath;
    }

    /**
     * Generate a full scan report as JSON.
     *
     * Writes the complete scan result (all components, architecture,
     * dead code, and summary) to scan-report.json.
     *
     * @param  array   $scanResult  Complete scan result from ProjectScanner::scan()
     * @param  string  $outputPath  Directory path for output
     * @return string  The absolute path to the written file
     */
    public function generateFullReport(array $scanResult, string $outputPath): string
    {
        return $this->generate($scanResult, $outputPath, 'scan-report.json');
    }

    /**
     * Generate an architecture-only report as JSON.
     *
     * Writes dependency trees and detected layers to architecture.json.
     *
     * @param  array   $architecture  Architecture data from DependencyAnalyzer::analyze()
     * @param  string  $outputPath    Directory path for output
     * @return string  The absolute path to the written file
     */
    public function generateArchitectureReport(array $architecture, string $outputPath): string
    {
        return $this->generate($architecture, $outputPath, 'architecture.json');
    }

    /**
     * Generate a dead code report as JSON.
     *
     * Writes dead code findings (unused controllers, models, views, services)
     * to deadcode.json.
     *
     * @param  array   $deadCode    Dead code data from ProjectScanner dead code detection
     * @param  string  $outputPath  Directory path for output
     * @return string  The absolute path to the written file
     */
    public function generateDeadCodeReport(array $deadCode, string $outputPath): string
    {
        return $this->generate($deadCode, $outputPath, 'deadcode.json');
    }
}
