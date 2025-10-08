<?php declare(strict_types=1);

namespace SSC\Infra\Cli;

if (!defined('ABSPATH')) {
    exit;
}

final class CssCacheCommand
{
    /**
     * Registers the command output through WP-CLI if available.
     *
     * @param array<int, string> $args      Positional arguments (unused).
     * @param array<string, mixed> $assocArgs Associative arguments coming from WP-CLI.
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $result = $this->execute($assocArgs);

        if (!class_exists('\\WP_CLI')) {
            return;
        }

        $method = $result['status'] === 'success' ? 'success' : 'warning';
        \WP_CLI::$method($result['message']);
    }

    /**
     * @param array<string, mixed> $assocArgs
     *
     * @return array{
     *     status: 'success'|'warning',
     *     message: string,
     *     had_cache: bool,
     *     rebuilt: bool,
     *     size: int
     * }
     */
    public function execute(array $assocArgs = []): array
    {
        $rebuild = $this->shouldRebuild($assocArgs);
        $hadCache = $this->hasCache();

        if (function_exists('ssc_invalidate_css_cache')) {
            ssc_invalidate_css_cache();
        }

        $rebuilt = false;
        $size = 0;

        if ($rebuild && function_exists('ssc_get_cached_css')) {
            $css = ssc_get_cached_css();
            $rebuilt = $css !== '';
            $size = strlen($css);
        }

        $messages = [];
        $messages[] = $hadCache ? 'Cache CSS vidé.' : 'Cache CSS déjà vide.';

        if ($rebuild) {
            $messages[] = $rebuilt
                ? sprintf('Nouveau cache généré (%d caractères).', $size)
                : 'Aucun CSS actif à mettre en cache.';
        } else {
            $messages[] = 'Utilisez --rebuild pour régénérer le cache immédiatement.';
        }

        return [
            'status' => $rebuild && !$rebuilt ? 'warning' : 'success',
            'message' => implode(' ', $messages),
            'had_cache' => $hadCache,
            'rebuilt' => $rebuilt,
            'size' => $size,
        ];
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function shouldRebuild(array $assocArgs): bool
    {
        if (!array_key_exists('rebuild', $assocArgs)) {
            return false;
        }

        $value = $assocArgs['rebuild'];

        if ($value === null) {
            return true;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === '') {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }

            return true;
        }

        return (bool) $value;
    }

    private function hasCache(): bool
    {
        $cache = get_option('ssc_css_cache', false);

        if (is_string($cache) && trim($cache) !== '') {
            return true;
        }

        return get_option('ssc_css_cache_last_had_cache', false) !== false;
    }
}
