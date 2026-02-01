<?php

namespace Arpon\Http;

use RuntimeException;

class UploadedFile
{
    protected string $name;
    protected string $type;
    protected string $tmpName;
    protected int $error;
    protected int $size;

    public function __construct(string $tmpName, string $name, string $type, int $size, int $error)
    {
        $this->tmpName = $tmpName;
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * Create from $_FILES array entry.
     */
    public static function createFromArray(array $file): self
    {
        return new static(
            $file['tmp_name'],
            $file['name'],
            $file['type'] ?? '',
            $file['size'] ?? 0,
            $file['error'] ?? UPLOAD_ERR_OK
        );
    }

    /**
     * Store the uploaded file.
     */
    public function store(string $path, array $options = []): string|false
    {
        $disk = $options['disk'] ?? 'public';
        $name = $options['name'] ?? null;

        return app('filesystem')->disk($disk)->putFile($path, $this, $name);
    }

    /**
     * Store the uploaded file with a specific name.
     */
    public function storeAs(string $path, string $name, string|array $options = []): string|false
    {
        // Handle both string disk name and array options
        if (is_string($options)) {
            $options = ['disk' => $options];
        }
        
        $disk = $options['disk'] ?? 'public';

        return app('filesystem')->disk($disk)->putFileAs($path, $this, $name);
    }

    /**
     * Move the uploaded file to a new location.
     */
    public function move(string $directory, string $name = null): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }

        $name = $name ?? $this->hashName();
        $target = rtrim($directory, '/') . '/' . $name;

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (is_uploaded_file($this->tmpName)) {
            return move_uploaded_file($this->tmpName, $target);
        }

        return rename($this->tmpName, $target);
    }

    /**
     * Get the file's extension.
     */
    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * Get the file's extension from MIME type.
     */
    public function guessExtension(): string
    {
        return $this->extension();
    }

    /**
     * Get the file's extension (alias for Laravel compatibility).
     */
    public function getClientOriginalExtension(): string
    {
        return $this->extension();
    }

    /**
     * Get a filename with hash.
     */
    public function hashName(string $path = null): string
    {
        $hash = bin2hex(random_bytes(20));
        $extension = $this->extension();

        if ($path) {
            return $path . '/' . $hash . '.' . $extension;
        }

        return $hash . '.' . $extension;
    }

    /**
     * Get the original filename.
     */
    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    /**
     * Get the file's MIME type.
     */
    public function getClientMimeType(): string
    {
        return $this->type;
    }

    /**
     * Get the file size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the upload error code.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get the temporary file path.
     */
    public function getRealPath(): string
    {
        return $this->tmpName;
    }

    /**
     * Get the temporary file path (alias).
     */
    public function getPathname(): string
    {
        return $this->tmpName;
    }

    /**
     * Check if the upload was successful.
     */
    public function isValid(): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }

        // In testing or when file exists
        if (file_exists($this->tmpName)) {
            return true;
        }

        // For actual HTTP uploads
        return is_uploaded_file($this->tmpName);
    }

    /**
     * Get the file contents.
     */
    public function get(): string|false
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * Get as a string.
     */
    public function __toString(): string
    {
        return $this->getRealPath();
    }
}
