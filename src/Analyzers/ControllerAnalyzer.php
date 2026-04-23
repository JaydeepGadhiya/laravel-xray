<?php

namespace Larapack\Xray\Analyzers;

use Larapack\Xray\Support\ClassParser;
use Larapack\Xray\Support\Concerns\HasPhpFileScanner;

class ControllerAnalyzer
{
    use HasPhpFileScanner;

    /**
     * Analyze all PHP files in the given controllers directory.
     *
     * Scans the directory recursively for .php files, parses each one with
     * ClassParser, and filters out files that appear in the xray.ignore config.
     * Also adds complexity metrics: method_count and loc (lines of code).
     *
     * @param string $path Absolute path to the controllers directory.
     * @return array Array of parsed class data from ClassParser::parse().
     */
    public function analyze(string $path): array
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
                $parsed['method_count'] = count($parsed['methods']);
                $parsed['loc'] = substr_count($parsed['content'], "\n") + 1;
                $results[] = $parsed;
            }
        }

        return $results;
    }
}
