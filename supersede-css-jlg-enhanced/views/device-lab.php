<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array<int, array<string, mixed>> $devices */
/** @var string $default_device */
/** @var string $default_url */
?>
<div class="ssc-app ssc-device-lab">
    <div class="ssc-panel">
        <h2><?php esc_html_e('🧪 Device Lab', 'supersede-css-jlg'); ?></h2>
        <p><?php esc_html_e('Testez vos styles Supersede sur une sélection d’appareils réalistes, ajustez l’orientation, le zoom et chargez n’importe quelle URL pour valider vos expériences.', 'supersede-css-jlg'); ?></p>
    </div>

    <div class="ssc-device-lab__layout">
        <section class="ssc-device-lab__controls" aria-labelledby="ssc-device-lab-controls-heading">
            <h3 id="ssc-device-lab-controls-heading"><?php esc_html_e('Commandes du laboratoire', 'supersede-css-jlg'); ?></h3>

            <div class="ssc-device-lab__field">
                <label for="ssc-device-lab-device"><?php esc_html_e('Appareil', 'supersede-css-jlg'); ?></label>
                <select id="ssc-device-lab-device" class="ssc-device-lab__select">
                    <?php foreach ($devices as $device) :
                        $device_id = isset($device['id']) ? (string) $device['id'] : '';
                        $device_label = isset($device['label']) ? (string) $device['label'] : '';
                        if ($device_id === '' || $device_label === '') {
                            continue;
                        }
                        $is_default = ($device_id === $default_device);
                        ?>
                        <option value="<?php echo esc_attr($device_id); ?>" <?php selected($is_default); ?>>
                            <?php echo esc_html($device_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description" id="ssc-device-lab-status">
                    <?php esc_html_e('Sélectionnez un appareil pour afficher sa résolution, son ratio de pixels et son orientation.', 'supersede-css-jlg'); ?>
                </p>
            </div>

            <div class="ssc-device-lab__field" role="group" aria-labelledby="ssc-device-lab-orientation-label">
                <div id="ssc-device-lab-orientation-label" class="ssc-device-lab__field-label"><?php esc_html_e('Orientation', 'supersede-css-jlg'); ?></div>
                <div class="ssc-device-lab__orientation">
                    <button type="button" class="button button-secondary" data-orientation="portrait" aria-pressed="true">
                        <?php esc_html_e('Portrait', 'supersede-css-jlg'); ?>
                    </button>
                    <button type="button" class="button button-secondary" data-orientation="landscape" aria-pressed="false">
                        <?php esc_html_e('Paysage', 'supersede-css-jlg'); ?>
                    </button>
                </div>
                <p class="screen-reader-text" id="ssc-device-lab-rotation-help"><?php esc_html_e('Rotation verrouillée pour cet appareil.', 'supersede-css-jlg'); ?></p>
            </div>

            <div class="ssc-device-lab__field">
                <label for="ssc-device-lab-zoom"><?php esc_html_e('Zoom', 'supersede-css-jlg'); ?></label>
                <div class="ssc-device-lab__slider">
                    <input type="range" id="ssc-device-lab-zoom" min="50" max="150" step="10" value="100" aria-describedby="ssc-device-lab-zoom-display">
                    <span id="ssc-device-lab-zoom-display" class="ssc-device-lab__slider-value">100%</span>
                </div>
            </div>

            <div class="ssc-device-lab__field">
                <form id="ssc-device-lab-url-form" class="ssc-device-lab__url-form">
                    <label for="ssc-device-lab-url"><?php esc_html_e('URL de prévisualisation', 'supersede-css-jlg'); ?></label>
                    <div class="ssc-device-lab__url-input">
                        <input type="url" id="ssc-device-lab-url" value="<?php echo esc_attr($default_url); ?>" placeholder="https://exemple.com" aria-describedby="ssc-device-lab-url-help">
                        <button type="submit" class="button button-primary" id="ssc-device-lab-load"><?php esc_html_e('Charger', 'supersede-css-jlg'); ?></button>
                    </div>
                </form>
                <p class="description" id="ssc-device-lab-url-help"><?php esc_html_e('Saisissez une URL interne ou externe. Certaines pages externes peuvent refuser le chargement en iframe.', 'supersede-css-jlg'); ?></p>
            </div>
        </section>

        <section class="ssc-device-lab__preview" aria-labelledby="ssc-device-lab-preview-heading">
            <h3 id="ssc-device-lab-preview-heading"><?php esc_html_e('Prévisualisation en direct', 'supersede-css-jlg'); ?></h3>
            <div class="ssc-device-lab__viewport-shell">
                <div class="ssc-device-lab__viewport-wrapper" id="ssc-device-lab-viewport-wrapper" data-zoom="100">
                    <div class="ssc-device-lab__viewport" id="ssc-device-lab-viewport" data-device="<?php echo esc_attr($default_device); ?>" data-orientation="portrait" data-width="0" data-height="0">
                        <iframe
                            id="ssc-device-lab-frame"
                            title="<?php esc_attr_e('Aperçu de l’appareil sélectionné', 'supersede-css-jlg'); ?>"
                            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals"
                            src="<?php echo esc_url($default_url); ?>"
                            loading="lazy"
                        ></iframe>
                    </div>
                </div>
            </div>
            <p class="screen-reader-text" id="ssc-device-lab-live" aria-live="polite"></p>
            <noscript>
                <p><?php esc_html_e('Activez JavaScript pour utiliser le laboratoire d’appareils.', 'supersede-css-jlg'); ?></p>
            </noscript>
        </section>
    </div>
</div>
