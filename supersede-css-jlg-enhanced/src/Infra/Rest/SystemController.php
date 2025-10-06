<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use Throwable;
use WP_REST_Response;

final class SystemController extends BaseController
{
    public const CACHE_KEY = 'ssc_health_check_payload';

    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/health', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'healthCheck'],
        ]);
    }

    public function healthCheck(): WP_REST_Response
    {
        $cachedPayload = get_transient(self::CACHE_KEY);

        if (
            is_array($cachedPayload)
            && isset($cachedPayload['meta'])
            && is_array($cachedPayload['meta'])
        ) {
            $cachedPayload['meta'] = $this->refreshMeta($cachedPayload['meta'], true);

            return new WP_REST_Response($cachedPayload, 200);
        }

        if ($cachedPayload !== false) {
            delete_transient(self::CACHE_KEY);
        }

        $ttl = $this->getCacheTtl();
        $payload = $this->buildResponseData();
        $payload['meta'] = $this->generateMeta($ttl, false, time());

        if ($ttl > 0) {
            set_transient(self::CACHE_KEY, $payload, $ttl);
        }

        return new WP_REST_Response($payload, 200);
    }

    private function checkClassAvailability(string $class, ?string $method = null): string
    {
        if (!class_exists($class)) {
            return 'Non trouvé';
        }

        if ($method !== null && !method_exists($class, $method)) {
            return 'Erreur: méthode manquante';
        }

        return 'OK';
    }

    private function checkFunctionAvailability(string $function): string
    {
        return function_exists($function) ? 'OK' : 'Non trouvé';
    }

    private function describeTokenRegistryStatus(): string
    {
        try {
            $registry = TokenRegistry::getRegistry();

            if (!is_array($registry)) {
                return 'Erreur: structure inattendue';
            }

            return sprintf('OK (%d tokens)', count($registry));
        } catch (Throwable $exception) {
            return 'Erreur: ' . $exception->getMessage();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponseData(): array
    {
        $assets_to_check = [
            'css/admin.css', 'css/ux.css', 'js/ux.js', 'js/utilities.js',
            'codemirror/lib/codemirror.js', 'codemirror/lib/codemirror.css'
        ];

        $asset_status = [];

        foreach ($assets_to_check as $asset) {
            $asset_status[$asset] = is_file(SSC_PLUGIN_DIR . 'assets/' . $asset) ? 'OK' : 'Manquant';
        }

        return [
            'plugin_version' => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'rest_api_status' => 'OK',
            'asset_files_exist' => $asset_status,
            'plugin_integrity' => [
                'classes' => [
                    TokenRegistry::class => $this->checkClassAvailability(TokenRegistry::class, 'getRegistry'),
                    CssSanitizer::class => $this->checkClassAvailability(CssSanitizer::class, 'sanitize'),
                    Routes::class => $this->checkClassAvailability(Routes::class, 'register'),
                ],
                'functions' => [
                    'ssc_get_cached_css' => $this->checkFunctionAvailability('ssc_get_cached_css'),
                    'ssc_invalidate_css_cache' => $this->checkFunctionAvailability('ssc_invalidate_css_cache'),
                ],
                'token_registry' => [
                    'status' => $this->describeTokenRegistryStatus(),
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function refreshMeta(array $meta, bool $cacheHit): array
    {
        $ttl = isset($meta['cache_ttl']) ? max(0, (int) $meta['cache_ttl']) : $this->getCacheTtl();
        $generatedTimestamp = isset($meta['generated_timestamp']) ? (int) $meta['generated_timestamp'] : time();
        $expiresTimestamp = isset($meta['expires_timestamp']) && $meta['expires_timestamp'] !== null
            ? (int) $meta['expires_timestamp']
            : ($ttl > 0 ? $generatedTimestamp + $ttl : null);

        return $this->generateMeta($ttl, $cacheHit, $generatedTimestamp, $expiresTimestamp);
    }

    private function getCacheTtl(): int
    {
        $defaultTtl = 5 * MINUTE_IN_SECONDS;
        $filtered = apply_filters('ssc_health_check_cache_ttl', $defaultTtl);

        if (!is_numeric($filtered)) {
            return 0;
        }

        $ttl = (int) $filtered;

        return $ttl > 0 ? $ttl : 0;
    }

    private function generateMeta(int $ttl, bool $cacheHit, int $generatedTimestamp, ?int $expiresTimestamp = null): array
    {
        if ($ttl < 0) {
            $ttl = 0;
        }

        if ($expiresTimestamp === null && $ttl > 0) {
            $expiresTimestamp = $generatedTimestamp + $ttl;
        }

        $meta = [
            'cache_ttl' => $ttl,
            'cache_hit' => $cacheHit,
            'generated_timestamp' => $generatedTimestamp,
            'generated_at' => gmdate('c', $generatedTimestamp),
            'generated_at_gmt' => gmdate('Y-m-d\TH:i:s\Z', $generatedTimestamp),
        ];

        if ($expiresTimestamp !== null) {
            $meta['expires_timestamp'] = $expiresTimestamp;
            $meta['cache_expires_at'] = gmdate('c', $expiresTimestamp);
            $meta['cache_expires_at_gmt'] = gmdate('Y-m-d\TH:i:s\Z', $expiresTimestamp);
            $meta['seconds_until_expiration'] = max(0, $expiresTimestamp - time());
        } else {
            $meta['expires_timestamp'] = null;
            $meta['cache_expires_at'] = null;
            $meta['cache_expires_at_gmt'] = null;
            $meta['seconds_until_expiration'] = null;
        }

        return $meta;
    }
}
