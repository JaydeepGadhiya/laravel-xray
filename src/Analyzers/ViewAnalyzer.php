<?php

namespace Jaydeep\Xray\Analyzers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ViewAnalyzer
{
    /**
     * Analyze the views directory to catalog all Blade and PHP view files.
     *
     * Scans the given directory recursively for *.blade.php and *.php files,
     * then converts each file path to Laravel dot-notation view names.
     *
     * @param string $path Absolute path to the views directory (e.g. resources/views).
     * @return array Array of view arrays, each with 'name', 'file', and 'relative' keys.
     */
    public function analyze(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $views = [];
        $basePath = rtrim(str_replace('\\', '/', $path), '/');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = $file->getExtension();
            $filename = $file->getFilename();

            // Only include .blade.php and .php files
            $isBlade = str_ends_with($filename, '.blade.php');
            $isPhp = $extension === 'php';

            if (! $isBlade && ! $isPhp) {
                continue;
            }

            $absolutePath = $file->getRealPath();
            $normalizedPath = str_replace('\\', '/', $absolutePath);

            // Calculate the relative path from the views base directory
            $relativePath = $this->getRelativePath($basePath, $normalizedPath);

            // Convert to Laravel dot-notation view name
            $viewName = $this->pathToViewName($relativePath);

            $views[] = [
                'name' => $viewName,
                'file' => $absolutePath,
                'relative' => $relativePath,
            ];
        }

        // Sort by view name for consistent output
        usort($views, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return $views;
    }

    /**
     * Get the relative path of a file from a base directory.
     *
     * @param string $basePath The base directory path (forward slashes).
     * @param string $filePath The absolute file path (forward slashes).
     * @return string The relative file path.
     */
    private function getRelativePath(string $basePath, string $filePath): string
    {
        $relative = $filePath;

        if (str_starts_with($filePath, $basePath . '/')) {
            $relative = substr($filePath, strlen($basePath) + 1);
        }

        return $relative;
    }

    /**
     * Convert a relative file path to a Laravel dot-notation view name.
     *
     * Examples:
     *   "welcome.blade.php"           -> "welcome"
     *   "users/index.blade.php"       -> "users.index"
     *   "admin/users/show.blade.php"  -> "admin.users.show"
     *   "emails/invoice.php"          -> "emails.invoice"
     *
     * @param string $relativePath The relative path from the views directory.
     * @return string The dot-notation view name.
     */
    private function pathToViewName(string $relativePath): string
    {
        // Normalize to forward slashes
        $path = str_replace('\\', '/', $relativePath);

        // Remove .blade.php first (before .php, since .blade.php also ends with .php)
        if (str_ends_with($path, '.blade.php')) {
            $path = substr($path, 0, -10);
        } elseif (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        // Convert directory separators to dots
        return str_replace('/', '.', $path);
    }
}
