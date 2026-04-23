<?php

namespace Larapack\Xray\Support\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait HasPhpFileScanner
{
    /**
     * Recursively discover all .php files in a directory.
     *
     * @param string $directory Absolute path to the directory to scan.
     * @return array Array of absolute file paths.
     */
    protected function getPhpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}
