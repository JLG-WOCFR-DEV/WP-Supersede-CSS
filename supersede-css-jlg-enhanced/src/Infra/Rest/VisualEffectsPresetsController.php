<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Support\CssSanitizer;
use WP_REST_Request;
use WP_REST_Response;

final class VisualEffectsPresetsController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/visual-effects-presets', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'listPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'savePreset'],
            ],
        ]);

        register_rest_route('ssc/v1', '/visual-effects-presets/(?P<id>[A-Za-z0-9_\-]+)', [
            'methods' => 'DELETE',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'deletePreset'],
            'args' => [
                'id' => [
                    'required' => true,
                ],
            ],
        ]);
    }

    public function listPresets(): WP_REST_Response
    {
        return new WP_REST_Response([
            'presets' => $this->getStoredPresets(),
        ], 200);
    }

    public function savePreset(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (!is_array($payload) || $payload === []) {
            $payload = $request->get_body_params();
        }

        if (!is_array($payload)) {
            $payload = [];
        }

        $name = isset($payload['name']) ? sanitize_text_field((string) wp_unslash($payload['name'])) : '';
        $name = $this->truncate($name, 180);
        $type = isset($payload['type']) ? sanitize_key((string) $payload['type']) : '';
        $rawSettings = $payload['settings'] ?? null;

        if (is_string($rawSettings)) {
            $decoded = json_decode($rawSettings, true);
            if (is_array($decoded)) {
                $rawSettings = $decoded;
            }
        }

        if ($name === '' || ($type !== 'stars' && $type !== 'gradient')) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid visual effect preset payload.', 'supersede-css-jlg'),
            ], 400);
        }

        $presets = $this->getStoredPresets();
        $id = '';
        if (isset($payload['id'])) {
            $id = $this->sanitizePresetId((string) $payload['id']);
        }

        if ($id === '') {
            $id = $this->generatePresetId($presets);
        }

        $candidate = [
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'settings' => $rawSettings,
        ];

        $sanitizedCandidate = CssSanitizer::sanitizeVisualEffectsPresets([$candidate]);
        if ($sanitizedCandidate === []) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Unable to sanitize preset settings.', 'supersede-css-jlg'),
            ], 400);
        }

        $preset = $sanitizedCandidate[0];

        $updated = [];
        $replaced = false;
        foreach ($presets as $existingPreset) {
            if (isset($existingPreset['id']) && $existingPreset['id'] === $preset['id']) {
                $updated[] = $preset;
                $replaced = true;
                continue;
            }

            $updated[] = $existingPreset;
        }

        if (!$replaced) {
            $updated[] = $preset;
        }

        $updated = CssSanitizer::sanitizeVisualEffectsPresets($updated);

        update_option('ssc_visual_effects_presets', $updated, false);

        return new WP_REST_Response([
            'ok' => true,
            'preset' => $preset,
            'presets' => $updated,
        ], 200);
    }

    public function deletePreset(WP_REST_Request $request): WP_REST_Response
    {
        $id = $this->sanitizePresetId((string) $request->get_param('id'));

        if ($id === '') {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid preset identifier.', 'supersede-css-jlg'),
            ], 400);
        }

        $presets = $this->getStoredPresets();
        $filtered = [];
        foreach ($presets as $preset) {
            if (!isset($preset['id']) || $preset['id'] !== $id) {
                $filtered[] = $preset;
            }
        }

        update_option('ssc_visual_effects_presets', $filtered, false);

        return new WP_REST_Response([
            'ok' => true,
            'deleted' => $id,
            'presets' => $filtered,
        ], 200);
    }

    private function getStoredPresets(): array
    {
        $stored = get_option('ssc_visual_effects_presets', []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return CssSanitizer::sanitizeVisualEffectsPresets($stored);
    }

    private function sanitizePresetId(string $id): string
    {
        $normalized = sanitize_key($id);
        if ($normalized === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            $normalized = mb_substr($normalized, 0, 80);
        } else {
            $normalized = substr($normalized, 0, 80);
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $presets
     */
    private function generatePresetId(array $presets): string
    {
        $existingIds = [];
        foreach ($presets as $preset) {
            if (isset($preset['id']) && is_string($preset['id'])) {
                $existingIds[$preset['id']] = true;
            }
        }

        do {
            $rawId = function_exists('wp_unique_id') ? wp_unique_id('ssc-ve-') : uniqid('ssc-ve-', false);
            $candidate = $this->sanitizePresetId((string) $rawId);
        } while ($candidate === '' || isset($existingIds[$candidate]));

        return $candidate;
    }

    private function truncate(string $value, int $length): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return trim((string) mb_substr($value, 0, $length));
        }

        return trim(substr($value, 0, $length));
    }
}
