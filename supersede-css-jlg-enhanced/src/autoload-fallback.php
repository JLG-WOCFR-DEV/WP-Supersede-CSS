<?php

declare(strict_types=1);

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'SSC\\';
        $baseDir = __DIR__ . '/';
        $length = strlen($prefix);

        if (strncmp($prefix, $class, $length) !== 0) {
            return;
        }

        $relativeClass = substr($class, $length);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_readable($file)) {
            require $file;
        }
    }
);
