<?php

namespace Arpon\Support;

use DirectoryIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Filesystem
{
    /**
     * Determine if a file or directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws \Exception
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new \Exception("File not found at path: {$path}");
    }

    /**
     * Get the contents of a file with shared access.
     *
     * @param  string  $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Get the returned value of a file.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     */
    public function getRequire($path, array $data = [])
    {
        if ($this->isFile($path)) {
            extract($data, EXTR_SKIP);

            return require $path;
        }

        throw new \Exception("File not found at path: {$path}");
    }

    /**
     * Require the given file once.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     */
    public function requireOnce($path, array $data = [])
    {
        if ($this->isFile($path)) {
            extract($data, EXTR_SKIP);

            return require_once $path;
        }

        throw new \Exception("File not found at path: {$path}");
    }

    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int|bool
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Create a symbolic link to the target file or directory.
     *
     * @param  string  $target
     * @param  string  $link
     * @return bool
     */
    public function link($target, $link)
    {
        if (!is_dir(dirname($link))) {
            mkdir(dirname($link), 0755, true);
        }

        return symlink($target, $link);
    }

    /**
     * Create a relative symbolic link to the target file or directory.
     *
     * @param  string  $target
     * @param  string  $link
     * @return bool
     */
    public function relativeLink($target, $link)
    {
        if (!is_dir(dirname($link))) {
            mkdir(dirname($link), 0755, true);
        }

        if (file_exists($link) || is_link($link)) {
            @unlink($link);
        }

        return symlink($this->getRelativePath(dirname($link), $target), $link);
    }

    /**
     * Get the relative path from one directory to another.
     *
     * @param  string  $from
     * @param  string  $to
     * @return string
     */
    protected function getRelativePath($from, $to)
    {
        $from = str_replace('\\', '/', realpath($from));
        $to = str_replace('\\', '/', realpath($to));

        $from = explode('/', $from);
        $to = explode('/', $to);
        $relPath = $to;

        foreach ($from as $depth => $dir) {
            if ($dir === $to[$depth]) {
                array_shift($relPath);
            } else {
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }

        return implode('/', $relPath);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Delete a file or directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function delete($path)
    {
        if (is_link($path)) {
            return unlink($path);
        }

        if (is_file($path)) {
            return unlink($path);
        }

        if (is_dir($path)) {
            return rmdir($path);
        }

        return false;
    }
    
    /**
     * Get all of the directories within a given directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function directories($directory)
    {
        $directories = [];

        foreach (new DirectoryIterator($directory) as $item) {
            if ($item->isDir() && ! $item->isDot()) {
                $directories[] = $item->getPathname();
            }
        }

        return $directories;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool  $preserve
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and proceeding up the directories deleting them as we go along.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }
    
    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }
}
