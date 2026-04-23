<?php

namespace Jaydeep\Xray\Analyzers;

use Jaydeep\Xray\Support\ClassParser;
use Jaydeep\Xray\Support\Concerns\HasPhpFileScanner;

class MiddlewareAnalyzer
{
    use HasPhpFileScanner;

    /**
     * Analyze all PHP files in the given middleware directory.
     *
     * Scans the directory recursively for .php files, parses each one with
     * ClassParser, and extracts the handle() method parameter signature.
     *
     * @param string $path Absolute path to the middleware directory.
     * @return array Array of parsed class data, each with a 'handle_parameters' key.
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
                $parsed['handle_parameters'] = $this->extractHandleParameters($parsed['content']);
                $results[] = $parsed;
            }
        }

        return $results;
    }

    /**
     * Extract the parameter variable names from the handle() method signature.
     *
     * @param string $content The raw PHP file content.
     * @return array Array of parameter variable names (without the $ prefix).
     */
    private function extractHandleParameters(string $content): array
    {
        if (preg_match('/function\s+handle\s*\(([^)]*)\)/', $content, $match)) {
            $params = [];
            preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $match[1], $paramMatches);
            return $paramMatches[1] ?? [];
        }

        return [];
    }
}
