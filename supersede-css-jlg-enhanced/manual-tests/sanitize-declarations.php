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
    'content pseudo-element with semicolon' => [
        'input' => 'div::after { content: ";"; }',
        'expected' => 'div::after {content:";"}',
    ],
    'multiple declarations stay separated' => [
        'input' => '.example { color: red; background-color: blue; }',
        'expected' => '.example {color:red; background-color:blue}',
    ],
    'parentheses inside values are preserved' => [
        'input' => '.example { transform: translate(10px, 20px); color: red; }',
        'expected' => '.example {transform:translate(10px, 20px); color:red}',
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
