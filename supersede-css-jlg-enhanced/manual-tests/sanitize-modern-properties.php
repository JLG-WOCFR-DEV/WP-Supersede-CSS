<?php declare(strict_types=1);

\define('ABSPATH', __DIR__);

if (!\function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html): string
    {
        return $string;
    }
}

if (!\function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

if (!\function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols)
    {
        return $string;
    }
}

require \dirname(__DIR__) . '/src/Support/CssSanitizer.php';

use SSC\Support\CssSanitizer;

$tests = [
    'grid declarations are preserved' => [
        'input' => '.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }',
        'expected' => '.grid {display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem}',
    ],
    'animation transform and filter survive' => [
        'input' => '.box { animation: spin 3s linear infinite; transform: rotate(0deg); filter: drop-shadow(0 0 10px rgba(0, 0, 0, 0.5)); }',
        'expected' => '.box {animation:spin 3s linear infinite; transform:rotate(0deg); filter:drop-shadow(0 0 10px rgba(0, 0, 0, 0.5))}',
    ],
    'unsafe protocols in URLs are stripped' => [
        'input' => '.img { filter: url("javascript:alert(1)"); }',
        'expected' => '.img',
    ],
];

foreach ($tests as $label => $test) {
    $sanitized = CssSanitizer::sanitize($test['input']);
    $status = $sanitized === $test['expected'] ? 'OK' : 'FAIL';

    echo $label . ':' . PHP_EOL;
    echo '  Input:     ' . $test['input'] . PHP_EOL;
    echo '  Sanitized: ' . $sanitized . PHP_EOL;
    echo '  Expected:  ' . $test['expected'] . PHP_EOL;
    echo '  Result:    ' . $status . PHP_EOL;
    echo str_repeat('-', 40) . PHP_EOL;

    if ($status === 'FAIL') {
        exit(1);
    }
}
