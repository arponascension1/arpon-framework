<?php

/**
 * Get the storage path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return app()->storagePath($path);
    }
}
