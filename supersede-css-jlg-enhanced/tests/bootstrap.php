<?php declare(strict_types=1);

error_reporting(E_ALL);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (!function_exists('ssc_get_required_capability')) {
    function ssc_get_required_capability(): string
    {
        return 'manage_options';
    }
}
