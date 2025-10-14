<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class DeviceLab extends AbstractPage
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDevicePresets(): array
    {
        return [
            [
                'id' => 'iphone-14',
                'label' => __('iPhone 14', 'supersede-css-jlg'),
                'width' => 390,
                'height' => 844,
                'pixelRatio' => 3,
                'category' => 'mobile',
                'categoryLabel' => __('Mobile', 'supersede-css-jlg'),
                'rotatable' => true,
            ],
            [
                'id' => 'pixel-8',
                'label' => __('Pixel 8', 'supersede-css-jlg'),
                'width' => 412,
                'height' => 915,
                'pixelRatio' => 3,
                'category' => 'mobile',
                'categoryLabel' => __('Mobile', 'supersede-css-jlg'),
                'rotatable' => true,
            ],
            [
                'id' => 'ipad-air',
                'label' => __('iPad Air', 'supersede-css-jlg'),
                'width' => 820,
                'height' => 1180,
                'pixelRatio' => 2,
                'category' => 'tablet',
                'categoryLabel' => __('Tablette', 'supersede-css-jlg'),
                'rotatable' => true,
            ],
            [
                'id' => 'surface-pro',
                'label' => __('Surface Pro 9', 'supersede-css-jlg'),
                'width' => 960,
                'height' => 1440,
                'pixelRatio' => 2,
                'category' => 'tablet',
                'categoryLabel' => __('Tablette', 'supersede-css-jlg'),
                'rotatable' => true,
            ],
            [
                'id' => 'macbook-air',
                'label' => __('MacBook Air 13"', 'supersede-css-jlg'),
                'width' => 1440,
                'height' => 900,
                'pixelRatio' => 2,
                'category' => 'laptop',
                'categoryLabel' => __('Ordinateur portable', 'supersede-css-jlg'),
                'rotatable' => false,
            ],
            [
                'id' => 'desktop-1440',
                'label' => __('Desktop 1440p', 'supersede-css-jlg'),
                'width' => 2560,
                'height' => 1440,
                'pixelRatio' => 1,
                'category' => 'desktop',
                'categoryLabel' => __('Bureau', 'supersede-css-jlg'),
                'rotatable' => false,
            ],
        ];
    }

    public function render(): void
    {
        $devices = $this->getDevicePresets();
        $default_device = $devices[0]['id'] ?? '';
        $default_url = home_url('/');
        if (!is_string($default_url)) {
            $default_url = '';
        }

        $inline_css = '';
        if (function_exists('ssc_prepare_inline_css_for_output')) {
            $inline_css = ssc_prepare_inline_css_for_output('device-lab', true);
        }

        if (function_exists('wp_localize_script')) {
            wp_localize_script('ssc-device-lab', 'SSC_DEVICE_LAB', [
                'devices' => $devices,
                'defaultDevice' => $default_device,
                'defaultOrientation' => 'portrait',
                'defaultZoom' => 100,
                'defaultUrl' => $default_url,
                'inlineCss' => $inline_css,
                'i18n' => [
                    'deviceSelected' => __('Appareil sélectionné : %s', 'supersede-css-jlg'),
                    'orientationPortrait' => __('Orientation portrait', 'supersede-css-jlg'),
                    'orientationLandscape' => __('Orientation paysage', 'supersede-css-jlg'),
                    'orientationLocked' => __('Rotation non disponible pour cet appareil.', 'supersede-css-jlg'),
                    'zoomAnnouncement' => __('Zoom défini sur %s %%', 'supersede-css-jlg'),
                    'urlLoaded' => __('URL chargée : %s', 'supersede-css-jlg'),
                    'urlInvalid' => __('URL invalide. Veuillez saisir une adresse complète.', 'supersede-css-jlg'),
                    'cssApplied' => __('CSS Supersede injecté dans la prévisualisation.', 'supersede-css-jlg'),
                    'cssFailed' => __('Impossible d’injecter le CSS Supersede pour cette URL.', 'supersede-css-jlg'),
                    'deviceSummary' => __('%s — %s × %s px — DPR %s%s', 'supersede-css-jlg'),
                    'deviceCategorySuffix' => __(' — %s', 'supersede-css-jlg'),
                ],
            ]);
        }

        $this->render_view('device-lab', [
            'devices' => $devices,
            'default_device' => $default_device,
            'default_url' => $default_url,
        ]);
    }
}
