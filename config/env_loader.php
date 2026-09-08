<?php
// Simple .env loader
function env($key, $default = null) {
    static $env = null;

    if ($env === null) {
        $env = [];

        $path = __DIR__ . '/../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (!strpos($line, '=')) continue;

                list($k, $v) = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }
    }

    return $env[$key] ?? $default;
}
