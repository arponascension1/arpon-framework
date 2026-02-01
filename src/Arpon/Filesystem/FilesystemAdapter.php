<?php

namespace Arpon\Filesystem;

use RuntimeException;
use Arpon\Http\UploadedFile;

class FilesystemAdapter
{
    protected string $root;
    protected string $visibility = 'public';

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');

        if (!is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    /**
     * Get the full path for the given path.
     */
    protected function getFullPath(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    /**
     * Get the contents of a file.
     */
    public function get(string $path): string|false
    {
        return file_get_contents($this->getFullPath($path));
    }

    /**
     * Write the contents of a file.
     */
    public function put(string $path, $contents, array $options = []): int|false
    {
        $fullPath = $this->getFullPath($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($fullPath, $contents);
    }

    /**
     * Store an uploaded file.
     */
    public function putFile(string $path, UploadedFile|string $file, string $name = null): string|false
    {
        if (is_string($file)) {
            $file = new UploadedFile($file, basename($file), mime_content_type($file), filesize($file), UPLOAD_ERR_OK);
        }

        $name = $name ?? $file->hashName();
        $destination = $this->getFullPath($path . '/' . $name);
        $directory = dirname($destination);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($file->move($directory, basename($destination))) {
            return $path . '/' . $name;
        }

        return false;
    }

    /**
     * Store an uploaded file with a specific name.
     */
    public function putFileAs(string $path, UploadedFile|string $file, string $name): string|false
    {
        return $this->putFile($path, $file, $name);
    }

    /**
     * Delete a file.
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $success = true;

        foreach ($paths as $path) {
            $fullPath = $this->getFullPath($path);
            if (file_exists($fullPath)) {
                $success = unlink($fullPath) && $success;
            }
        }

        return $success;
    }

    /**
     * Copy a file to a new location.
     */
    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);

        $directory = dirname($toPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return copy($fromPath, $toPath);
    }

    /**
     * Move a file to a new location.
     */
    public function move(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);

        $directory = dirname($toPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return rename($fromPath, $toPath);
    }

    /**
     * Get the file size.
     */
    public function size(string $path): int|false
    {
        return filesize($this->getFullPath($path));
    }

    /**
     * Get the MIME type.
     */
    public function mimeType(string $path): string|false
    {
        return mime_content_type($this->getFullPath($path));
    }

    /**
     * Get the last modification time.
     */
    public function lastModified(string $path): int|false
    {
        return filemtime($this->getFullPath($path));
    }

    /**
     * Get an array of all files in a directory.
     */
    public function files(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getFullPath($directory);

        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $items = $recursive ? new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) : new \DirectoryIterator($fullPath);

        foreach ($items as $item) {
            if ($item->isFile()) {
                $files[] = str_replace($this->root . '/', '', $item->getPathname());
            }
        }

        return $files;
    }

    /**
     * Get all directories within a directory.
     */
    public function directories(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getFullPath($directory);

        if (!is_dir($fullPath)) {
            return [];
        }

        $directories = [];
        $items = $recursive ? new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) : new \DirectoryIterator($fullPath);

        foreach ($items as $item) {
            if ($item->isDir() && !in_array($item->getFilename(), ['.', '..'])) {
                $directories[] = str_replace($this->root . '/', '', $item->getPathname());
            }
        }

        return $directories;
    }

    /**
     * Create a directory.
     */
    public function makeDirectory(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        if (is_dir($fullPath)) {
            return true;
        }

        return mkdir($fullPath, 0755, true);
    }

    /**
     * Delete a directory.
     */
    public function deleteDirectory(string $directory): bool
    {
        $fullPath = $this->getFullPath($directory);

        if (!is_dir($fullPath)) {
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        return rmdir($fullPath);
    }

    /**
     * Get the URL for the file.
     */
    public function url(string $path): string
    {
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Get the root path.
     */
    public function path(string $path = ''): string
    {
        return $this->getFullPath($path);
    }
}
