<?php

namespace Arpon\Foundation\Bootstrap;

use Arpon\Foundation\Application;

class LoadEnvironmentVariables
{
    public function bootstrap(Application $app)
    {
        $envFile = $app->basePath('.env');

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if (strpos($line, '#') === 0) {
                    continue;
                }

                if (strpos($line, 'export ') === 0) {
                    $line = trim(substr($line, 7));
                }

                $hashPos = null;
                $inSingle = false;
                $inDouble = false;
                $len = strlen($line);
                for ($i = 0; $i < $len; $i++) {
                    $ch = $line[$i];

                    if ($ch === "'" && !$inDouble) {
                        $inSingle = !$inSingle;
                        continue;
                    }

                    if ($ch === '"' && !$inSingle) {
                        $inDouble = !$inDouble;
                        continue;
                    }

                    if ($ch === '#' && !$inSingle && !$inDouble) {
                        $hashPos = $i;
                        break;
                    }
                }

                if ($hashPos !== null) {
                    $line = rtrim(substr($line, 0, $hashPos));
                }

                if ($line === '' || strpos($line, '=') === false) {
                    continue;
                }

                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);

                if ($key === '') {
                    continue;
                }

                $value = ltrim($value);

                if (strlen($value) >= 2) {
                    $first = $value[0];
                    $last = $value[strlen($value) - 1];

                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }

                $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($m) {
                    $v = $_ENV[$m[1]] ?? $_SERVER[$m[1]] ?? getenv($m[1]);
                    return $v === false ? '' : (string) $v;
                }, $value);

                $alreadySet = array_key_exists($key, $_SERVER) || array_key_exists($key, $_ENV);

                if (!$alreadySet) {
                    $envValue = getenv($key);
                    if ($envValue === false) {
                        if (function_exists('putenv')) {
                            putenv($key . '=' . $value);
                        }
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
    }
}