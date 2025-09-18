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

if (!\function_exists('safecss_filter_attr')) {
    function safecss_filter_attr(string $css): string
    {
        return $css;
    }
}

require \dirname(__DIR__) . '/src/Support/CssSanitizer.php';

use SSC\Support\CssSanitizer;

$tests = [
    'parentheses inside quoted URL survive' => [
        'input' => 'div { background: url("https://example.com/image(1).png"); }',
        'expected' => 'div {background:url("https://example.com/image(1).png")}',
    ],
    'unsafe protocol is removed' => [
        'input' => 'div { background: url("javascript:alert(1)"); }',
        'expected' => 'div',
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
