<?php

namespace Jaydeep\Xray\Analyzers;

use Jaydeep\Xray\Support\ClassParser;
use Jaydeep\Xray\Support\Concerns\HasPhpFileScanner;

class ServiceAnalyzer
{
    use HasPhpFileScanner;

    /**
     * Analyze all PHP files in the given services directory.
     *
     * Scans the directory recursively for .php files and parses each one
     * with ClassParser. Filters out files in the xray.ignore config.
     *
     * @param string $path Absolute path to the services directory.
     * @return array Array of parsed class data from ClassParser::parse().
     */
    public function analyzeServices(string $path): array
    {
        return $this->analyzeDirectory($path);
    }

    /**
     * Analyze all PHP files in the given repositories directory.
     *
     * Scans the directory recursively for .php files and parses each one
     * with ClassParser. Filters out files in the xray.ignore config.
     *
     * @param string $path Absolute path to the repositories directory.
     * @return array Array of parsed class data from ClassParser::parse().
     */
    public function analyzeRepositories(string $path): array
    {
        return $this->analyzeDirectory($path);
    }

    /**
     * Scan a directory and parse all PHP class files within it.
     *
     * @param string $path Absolute path to the directory to scan.
     * @return array Array of parsed class data from ClassParser::parse().
     */
    private function analyzeDirectory(string $path): array
    {
        $files = $this->getPhpFiles($path);
        $ignored = config('xray.ignore', []);
        $results = [];

        foreach ($files as $file) {
            if (in_array(basename($file), $ignored, true)) {
                continue;
            }

            $parsed = ClassParser::parse($file);

            if ($parsed['class'] !== '') {
                $results[] = $parsed;
            }
        }

        return $results;
    }
}
