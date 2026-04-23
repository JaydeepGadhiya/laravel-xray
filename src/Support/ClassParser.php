<?php

namespace Jaydeep\Xray\Support;

class ClassParser
{
    /**
     * Parse a PHP file using token_get_all() to extract class metadata
     * without executing the file.
     *
     * @param string $filePath Absolute path to the PHP file.
     * @return array Parsed class data including namespace, class name, use statements,
     *               constructor dependencies, methods, and raw content.
     */
    public static function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return self::emptyResult($filePath);
        }

        $tokens = token_get_all($content);

        $namespace = self::extractNamespace($tokens);
        $className = self::extractClassName($tokens);
        $uses = self::extractUseStatements($tokens);
        $methods = self::extractMethods($tokens);
        $constructorDeps = self::extractConstructorDependencies($content);

        $fqcn = '';
        if ($className !== '') {
            $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;
        }

        return [
            'file' => $filePath,
            'namespace' => $namespace,
            'class' => $className,
            'fqcn' => $fqcn,
            'uses' => $uses,
            'constructor_dependencies' => $constructorDeps,
            'methods' => $methods,
            'content' => $content,
        ];
    }

    /**
     * Extract the namespace declaration from a tokenized PHP file.
     *
     * @param array $tokens The token array from token_get_all().
     * @return string The namespace string, or empty string if none found.
     */
    private static function extractNamespace(array $tokens): string
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] !== T_NAMESPACE) {
                continue;
            }

            $namespace = '';
            $i++;

            // Skip whitespace after 'namespace' keyword
            while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                $i++;
            }

            // Collect namespace parts until semicolon or opening brace
            while ($i < $count) {
                if (is_array($tokens[$i])) {
                    $tokenId = $tokens[$i][0];

                    // PHP 8.0+ uses T_NAME_QUALIFIED for multi-part namespaces
                    if (defined('T_NAME_QUALIFIED') && $tokenId === T_NAME_QUALIFIED) {
                        $namespace .= $tokens[$i][1];
                    } elseif ($tokenId === T_STRING || $tokenId === T_NS_SEPARATOR) {
                        $namespace .= $tokens[$i][1];
                    } elseif ($tokenId === T_WHITESPACE) {
                        // skip
                    } else {
                        break;
                    }
                } else {
                    // Hit semicolon or brace
                    break;
                }

                $i++;
            }

            return trim($namespace);
        }

        return '';
    }

    /**
     * Extract the class name from a tokenized PHP file.
     *
     * Handles regular classes, abstract classes, interfaces, and traits.
     * Skips ::class constant access to avoid false positives.
     *
     * @param array $tokens The token array from token_get_all().
     * @return string The class name, or empty string if none found.
     */
    private static function extractClassName(array $tokens): string
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] !== T_CLASS) {
                continue;
            }

            // Skip ::class constant access — check if the previous non-whitespace token is ':'
            // which would be part of the '::' operator (T_DOUBLE_COLON)
            $prevIndex = $i - 1;
            while ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_WHITESPACE) {
                $prevIndex--;
            }

            if ($prevIndex >= 0) {
                if (is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_DOUBLE_COLON) {
                    continue;
                }
            }

            // Move forward past whitespace to find the class name
            $i++;
            while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                $i++;
            }

            if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }

        return '';
    }

    /**
     * Extract use (import) statements from a tokenized PHP file.
     *
     * Only collects use statements that appear before the first class/interface/trait
     * declaration (to avoid picking up trait "use" inside class bodies). Handles
     * "as" aliases and skips "use function" / "use const".
     *
     * @param array $tokens The token array from token_get_all().
     * @return array Associative array mapping short class name (or alias) to FQCN.
     */
    public static function extractUseStatements(array $tokens): array
    {
        $uses = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }

            // Stop at the first class/interface/trait/enum declaration
            if (in_array($tokens[$i][0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                // Make sure T_CLASS isn't ::class
                if ($tokens[$i][0] === T_CLASS) {
                    $prevIndex = $i - 1;
                    while ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_WHITESPACE) {
                        $prevIndex--;
                    }
                    if ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_DOUBLE_COLON) {
                        continue;
                    }
                }
                break;
            }

            if (defined('T_ENUM') && $tokens[$i][0] === T_ENUM) {
                break;
            }

            if ($tokens[$i][0] !== T_USE) {
                continue;
            }

            // Peek ahead: skip "use function" and "use const"
            $peekIndex = $i + 1;
            while ($peekIndex < $count && is_array($tokens[$peekIndex]) && $tokens[$peekIndex][0] === T_WHITESPACE) {
                $peekIndex++;
            }

            if ($peekIndex < $count && is_array($tokens[$peekIndex])) {
                if ($tokens[$peekIndex][0] === T_FUNCTION || $tokens[$peekIndex][0] === T_CONST) {
                    continue;
                }
            }

            // Collect the full use statement until semicolon
            $i++;
            $fqcn = '';
            $alias = '';
            $hasAlias = false;

            while ($i < $count) {
                if (is_array($tokens[$i])) {
                    $tokenId = $tokens[$i][0];

                    if ($tokenId === T_AS) {
                        $hasAlias = true;
                        $i++;
                        // Skip whitespace
                        while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                            $i++;
                        }
                        if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                            $alias = $tokens[$i][1];
                        }
                    } elseif (! $hasAlias) {
                        if ($tokenId === T_STRING || $tokenId === T_NS_SEPARATOR) {
                            $fqcn .= $tokens[$i][1];
                        } elseif (defined('T_NAME_QUALIFIED') && $tokenId === T_NAME_QUALIFIED) {
                            $fqcn .= $tokens[$i][1];
                        } elseif (defined('T_NAME_FULLY_QUALIFIED') && $tokenId === T_NAME_FULLY_QUALIFIED) {
                            $fqcn .= $tokens[$i][1];
                        }
                    }
                } else {
                    // Semicolon or other delimiter
                    if ($tokens[$i] === ';') {
                        break;
                    }
                }

                $i++;
            }

            $fqcn = ltrim(trim($fqcn), '\\');

            if ($fqcn === '') {
                continue;
            }

            $shortName = $hasAlias ? $alias : self::classBasename($fqcn);
            $uses[$shortName] = $fqcn;
        }

        return $uses;
    }

    /**
     * Extract constructor dependencies using regex on the raw file content.
     *
     * Supports PHP 8 constructor property promotion (public, protected, private, readonly).
     *
     * @param string $content The raw PHP file content.
     * @return array Array of dependency arrays, each with 'type' and 'name' keys.
     */
    private static function extractConstructorDependencies(string $content): array
    {
        $dependencies = [];

        if (preg_match('/function\s+__construct\s*\((.*?)\)/s', $content, $match)) {
            $params = $match[1];

            preg_match_all(
                '/(?:(?:private|protected|public|readonly)\s+)*([A-Z][A-Za-z0-9_\\\\]+)\s+\$([a-zA-Z0-9_]+)/',
                $params,
                $paramMatches,
                PREG_SET_ORDER
            );

            foreach ($paramMatches as $paramMatch) {
                $dependencies[] = [
                    'type' => $paramMatch[1],
                    'name' => $paramMatch[2],
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Extract all methods from a tokenized PHP file, along with their visibility.
     *
     * Tracks the most recent visibility modifier (public, protected, private) before
     * each function declaration. Defaults to 'public' if none is specified.
     *
     * @param array $tokens The token array from token_get_all().
     * @return array Array of method arrays, each with 'name' and 'visibility' keys.
     */
    private static function extractMethods(array $tokens): array
    {
        $methods = [];
        $count = count($tokens);
        $currentVisibility = null;
        $insideClass = false;
        $braceDepth = 0;
        $classBraceDepth = 0;

        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i])) {
                $tokenId = $tokens[$i][0];

                // Detect class/interface/trait entry
                if (in_array($tokenId, [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                    // Skip ::class
                    if ($tokenId === T_CLASS) {
                        $prevIndex = $i - 1;
                        while ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_WHITESPACE) {
                            $prevIndex--;
                        }
                        if ($prevIndex >= 0 && is_array($tokens[$prevIndex]) && $tokens[$prevIndex][0] === T_DOUBLE_COLON) {
                            continue;
                        }
                    }

                    if (! $insideClass) {
                        // Find the opening brace for this class
                        $j = $i + 1;
                        while ($j < $count && $tokens[$j] !== '{') {
                            $j++;
                        }
                        if ($j < $count) {
                            $insideClass = true;
                            $classBraceDepth = $braceDepth + 1;
                        }
                    }
                }

                if (defined('T_ENUM') && $tokenId === T_ENUM && ! $insideClass) {
                    $j = $i + 1;
                    while ($j < $count && $tokens[$j] !== '{') {
                        $j++;
                    }
                    if ($j < $count) {
                        $insideClass = true;
                        $classBraceDepth = $braceDepth + 1;
                    }
                }

                // Track visibility modifiers
                if ($tokenId === T_PUBLIC) {
                    $currentVisibility = 'public';
                } elseif ($tokenId === T_PROTECTED) {
                    $currentVisibility = 'protected';
                } elseif ($tokenId === T_PRIVATE) {
                    $currentVisibility = 'private';
                }

                // Detect function declaration inside a class
                if ($tokenId === T_FUNCTION && $insideClass) {
                    $j = $i + 1;

                    // Skip whitespace
                    while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        $j++;
                    }

                    // The next token should be the method name (T_STRING)
                    if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $methods[] = [
                            'name' => $tokens[$j][1],
                            'visibility' => $currentVisibility ?? 'public',
                        ];
                    }

                    $currentVisibility = null;
                }

                // Reset visibility if we hit something that isn't part of a method signature
                $visibilityTokens = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL, T_FUNCTION, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
                if (defined('T_READONLY')) {
                    $visibilityTokens[] = T_READONLY;
                }
                if (! in_array($tokenId, $visibilityTokens, true)) {
                    $currentVisibility = null;
                }
            } else {
                // Track brace depth
                if ($tokens[$i] === '{') {
                    $braceDepth++;
                } elseif ($tokens[$i] === '}') {
                    $braceDepth--;
                    if ($insideClass && $braceDepth < $classBraceDepth) {
                        $insideClass = false;
                    }
                }
            }
        }

        return $methods;
    }

    /**
     * Return the short class name from a fully qualified class name.
     *
     * @param string $fqcn A fully qualified class name (e.g. "App\Models\User").
     * @return string The short class name (e.g. "User").
     */
    public static function classBasename(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Return an empty parse result for a given file path.
     *
     * @param string $filePath The file path to include in the result.
     * @return array An empty parse result structure.
     */
    private static function emptyResult(string $filePath): array
    {
        return [
            'file' => $filePath,
            'namespace' => '',
            'class' => '',
            'fqcn' => '',
            'uses' => [],
            'constructor_dependencies' => [],
            'methods' => [],
            'content' => '',
        ];
    }
}
