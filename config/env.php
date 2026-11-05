<?php
// config/env.php - Lightweight, zero-dependency .env loader for PHP

if (!function_exists('load_env')) {
    /**
     * Parse and load an environment file into $_ENV, $_SERVER, and putenv().
     *
     * @param string $path Path to the .env file.
     * @param bool $override Whether to override existing environment variables.
     * @return bool True if loaded, false if file does not exist.
     */
    function load_env(string $path, bool $override = false): bool {
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comment lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Find key=value separator
            $equalPos = strpos($line, '=');
            if ($equalPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $equalPos));
            $val = trim(substr($line, $equalPos + 1));

            if ($key === '') {
                continue;
            }

            // Handle quoted values
            $len = strlen($val);
            if ($len >= 2) {
                $firstChar = $val[0];
                $lastChar = $val[$len - 1];

                if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                    $val = substr($val, 1, -1);
                    if ($firstChar === '"') {
                        // Unescape escaped characters in double-quoted strings
                        $val = str_replace(
                            ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                            ["\n", "\r", "\t", '"', '\\'],
                            $val
                        );
                    }
                } else {
                    // Strip inline comment if unquoted: e.g. FOO=bar # comment
                    if (($commentPos = strpos($val, ' #')) !== false) {
                        $val = trim(substr($val, 0, $commentPos));
                    }
                }
            } else {
                if (($commentPos = strpos($val, ' #')) !== false) {
                    $val = trim(substr($val, 0, $commentPos));
                }
            }

            // Do not override existing server environment variables unless requested
            if (!$override && (array_key_exists($key, $_ENV) || getenv($key) !== false)) {
                continue;
            }

            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable value with default fallback and type casting.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($val === false || $val === null) {
            return $default;
        }

        $lower = strtolower(trim((string)$val));
        switch ($lower) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $val;
    }
}

// Automatically load .env from root directory if present
load_env(dirname(__DIR__) . '/.env');

