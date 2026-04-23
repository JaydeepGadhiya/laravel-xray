<?php

namespace Jaydeep\Xray\Analyzers;

use Jaydeep\Xray\Support\ClassParser;
use Jaydeep\Xray\Support\Concerns\HasPhpFileScanner;

class ModelAnalyzer
{
    use HasPhpFileScanner;

    /**
     * Eloquent relationship method names to detect.
     */
    private const RELATIONSHIP_TYPES = [
        'hasOne',
        'hasMany',
        'belongsTo',
        'belongsToMany',
        'hasOneThrough',
        'hasManyThrough',
        'morphTo',
        'morphOne',
        'morphMany',
        'morphToMany',
        'morphedByMany',
    ];

    /**
     * Analyze all PHP files in the given models directory.
     *
     * Scans the directory recursively for .php files, parses each one with
     * ClassParser, and additionally extracts Eloquent relationship definitions
     * from method bodies.
     *
     * @param string $path Absolute path to the models directory.
     * @return array Array of parsed class data, each augmented with a 'relationships' key.
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
                $parsed['relationships'] = $this->extractRelationships($parsed['content']);
                $results[] = $parsed;
            }
        }

        return $results;
    }

    /**
     * Extract Eloquent relationship definitions from file content.
     *
     * Scans method bodies for calls to Eloquent relationship methods (hasOne,
     * hasMany, belongsTo, etc.) and extracts the relationship type, related
     * model, and the method name that defines the relationship.
     *
     * @param string $content The raw PHP file content.
     * @return array Array of relationship arrays with 'type', 'related', and 'method' keys.
     */
    private function extractRelationships(string $content): array
    {
        $relationships = [];
        $typePattern = implode('|', self::RELATIONSHIP_TYPES);

        // Pattern to find methods and relationship calls within them
        $methodPattern = '/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)[^{]*\{/';

        if (preg_match_all($methodPattern, $content, $methodMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($methodMatches[0] as $index => $match) {
                $methodName = $methodMatches[1][$index][0];
                $startPos = $match[1] + strlen($match[0]);

                // Find the matching closing brace for this method body
                $methodBody = $this->extractMethodBody($content, $startPos);

                if ($methodBody === '') {
                    continue;
                }

                // Look for relationship calls: $this->hasMany(Model::class, ...)
                $relationPattern = '/\$this\s*->\s*(' . $typePattern . ')\s*\(\s*([A-Z][A-Za-z0-9_\\\\]*)(::class)?/';

                if (preg_match($relationPattern, $methodBody, $relMatch)) {
                    $relatedModel = $relMatch[2];

                    // Clean up: if FQCN, get the basename
                    if (strpos($relatedModel, '\\') !== false) {
                        $parts = explode('\\', $relatedModel);
                        $relatedModel = end($parts);
                    }

                    $relationships[] = [
                        'type' => $relMatch[1],
                        'related' => $relatedModel,
                        'method' => $methodName,
                    ];
                }
            }
        }

        return $relationships;
    }

    /**
     * Extract a method body by counting matching braces from a starting position.
     *
     * @param string $content The full file content.
     * @param int $startPos The position immediately after the opening brace.
     * @return string The method body content (excluding the outer braces).
     */
    private function extractMethodBody(string $content, int $startPos): string
    {
        $length = strlen($content);
        $depth = 1;
        $pos = $startPos;

        while ($pos < $length && $depth > 0) {
            $char = $content[$pos];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            }

            $pos++;
        }

        if ($depth !== 0) {
            return '';
        }

        return substr($content, $startPos, $pos - $startPos - 1);
    }
}
