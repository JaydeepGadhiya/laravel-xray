<?php

namespace Jaydeep\Xray\Support\Concerns;

use RuntimeException;

trait EnsuresOutputDirectory
{
    /**
     * Ensure the output directory exists, creating it recursively if necessary.
     *
     * @param string $path The directory path to ensure.
     * @return void
     *
     * @throws RuntimeException If the directory cannot be created.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Failed to create output directory: {$path}");
        }
    }
}
