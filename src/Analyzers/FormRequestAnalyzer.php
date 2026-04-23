<?php

namespace Jaydeep\Xray\Analyzers;

use Jaydeep\Xray\Support\ClassParser;
use Jaydeep\Xray\Support\Concerns\HasPhpFileScanner;

class FormRequestAnalyzer
{
    use HasPhpFileScanner;

    /**
     * Analyze all PHP files in the given Form Requests directory.
     *
     * Scans the directory recursively for .php files, parses each one with
     * ClassParser, and extracts the validation rules from the rules() method.
     *
     * @param string $path Absolute path to the requests directory.
     * @return array Array of parsed class data, each with a 'rules' key.
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
                $parsed['rules'] = $this->extractRules($parsed['content']);
                $results[] = $parsed;
            }
        }

        return $results;
    }

    /**
     * Extract field names from the rules() method body.
     *
     * Parses the rules() method using brace counting for accuracy, then
     * extracts array keys (field names) with a regex.
     *
     * @param string $content The raw PHP file content.
     * @return array Array of unique field names defined in the rules() method.
     */
    private function extractRules(string $content): array
    {
        if (!preg_match('/function\s+rules\s*\([^)]*\)[^{]*\{/s', $content, $match, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $startPos = $match[0][1] + strlen($match[0][0]);
        $depth = 1;
        $pos = $startPos;
        $length = strlen($content);

        while ($pos < $length && $depth > 0) {
            if ($content[$pos] === '{') {
                $depth++;
            } elseif ($content[$pos] === '}') {
                $depth--;
            }
            $pos++;
        }

        $body = substr($content, $startPos, $pos - $startPos - 1);

        preg_match_all('/[\'"]([a-zA-Z_][a-zA-Z0-9_.*]*)[\'"]\\s*=>/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
