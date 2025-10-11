<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $preview_background */
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🎨 Éditeur de Filtres & Effets de Verre', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Appliquez des filtres visuels à vos images et conteneurs, ou créez un effet "Glassmorphism" tendance.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <div class="ssc-stack ssc-filter-section">
                <h3><?php printf(wp_kses_post(__('Filtres CSS (%s)', 'supersede-css-jlg')), '<code>filter</code>'); ?></h3>
                <div class="ssc-filter-controls">
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-blur"><?php esc_html_e('Flou (Blur)', 'supersede-css-jlg'); ?></label>
                            <span id="val-blur" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('0px', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-blur" class="ssc-filter-prop" data-prop="blur" min="0" max="20" value="0" step="1">
                    </div>
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-brightness"><?php esc_html_e('Luminosité', 'supersede-css-jlg'); ?></label>
                            <span id="val-brightness" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('100%', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-brightness" class="ssc-filter-prop" data-prop="brightness" min="0" max="200" value="100" step="5">
                    </div>
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-contrast"><?php esc_html_e('Contraste', 'supersede-css-jlg'); ?></label>
                            <span id="val-contrast" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('100%', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-contrast" class="ssc-filter-prop" data-prop="contrast" min="0" max="200" value="100" step="5">
                    </div>
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-grayscale"><?php esc_html_e('Niveaux de gris', 'supersede-css-jlg'); ?></label>
                            <span id="val-grayscale" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('0%', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-grayscale" class="ssc-filter-prop" data-prop="grayscale" min="0" max="100" value="0" step="5">
                    </div>
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-hue"><?php esc_html_e('Rotation de teinte', 'supersede-css-jlg'); ?></label>
                            <span id="val-hue-rotate" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('0deg', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-hue" class="ssc-filter-prop" data-prop="hue-rotate" min="0" max="360" value="0" step="15">
                    </div>
                    <div class="ssc-form-field">
                        <div class="ssc-form-label-row">
                            <label class="ssc-form-label" for="ssc-filter-saturate"><?php esc_html_e('Saturation', 'supersede-css-jlg'); ?></label>
                            <span id="val-saturate" class="ssc-range-output" aria-live="polite" aria-atomic="true"><?php echo esc_html__('100%', 'supersede-css-jlg'); ?></span>
                        </div>
                        <input type="range" id="ssc-filter-saturate" class="ssc-filter-prop" data-prop="saturate" min="0" max="200" value="100" step="5">
                    </div>
                </div>
            </div>
            <div class="ssc-stack ssc-filter-section ssc-divider-top">
                <h3><?php printf(wp_kses_post(__('Effet Verre (%s)', 'supersede-css-jlg')), '<code>backdrop-filter</code>'); ?></h3>
                <label class="ssc-filter-glass-toggle" for="ssc-glass-enable">
                    <input type="checkbox" id="ssc-glass-enable">
                    <span><?php esc_html_e('Activer le Glassmorphism', 'supersede-css-jlg'); ?></span>
                </label>
            </div>
            <pre id="ssc-filter-css" class="ssc-code ssc-mt-200" aria-live="polite"></pre>
            <div class="ssc-actions">
                <button id="ssc-filter-copy" class="button" type="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <div class="ssc-pane">
            <div class="ssc-stack ssc-filter-section">
                <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-filter-preview-bg" class="ssc-filter-preview" data-preview-bg="<?php echo esc_attr($preview_background); ?>">
                    <div id="ssc-filter-preview-box" class="ssc-filter-preview-box">
                        <?php esc_html_e('Votre Contenu Ici', 'supersede-css-jlg'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
