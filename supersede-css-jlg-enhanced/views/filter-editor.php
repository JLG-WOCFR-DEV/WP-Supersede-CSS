<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $preview_background */
?>
<style>
    #ssc-filter-preview-bg {
        background-image: url('<?php echo esc_url($preview_background); ?>');
        background-size: cover;
        border-radius: 12px;
        padding: 24px;
        display: grid;
        place-items: center;
    }
    #ssc-filter-preview-box {
        transition: all 0.2s ease-in-out;
        width: 80%;
        height: 250px;
        color: white;
        font-size: 24px;
        font-weight: bold;
        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        display: grid;
        place-items: center;
        border-radius: 16px;
    }
    .ssc-glassmorphism-preview {
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
</style>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🎨 Éditeur de Filtres & Effets de Verre', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Appliquez des filtres visuels à vos images et conteneurs, ou créez un effet "Glassmorphism" tendance.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php printf(wp_kses_post(__('Filtres CSS (%s)', 'supersede-css-jlg')), '<code>filter</code>'); ?></h3>
            <div class="ssc-two">
                <div><label><?php esc_html_e('Flou (Blur)', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="blur" min="0" max="20" value="0" step="1"> <span id="val-blur">0px</span></div>
                <div><label><?php esc_html_e('Luminosité', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="brightness" min="0" max="200" value="100" step="5"> <span id="val-brightness">100%</span></div>
                <div><label><?php esc_html_e('Contraste', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="contrast" min="0" max="200" value="100" step="5"> <span id="val-contrast">100%</span></div>
                <div><label><?php esc_html_e('Niveaux de gris', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="grayscale" min="0" max="100" value="0" step="5"> <span id="val-grayscale">0%</span></div>
                <div><label><?php esc_html_e('Rotation de teinte', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="hue-rotate" min="0" max="360" value="0" step="15"> <span id="val-hue-rotate">0deg</span></div>
                <div><label><?php esc_html_e('Saturation', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-filter-prop" data-prop="saturate" min="0" max="200" value="100" step="5"> <span id="val-saturate">100%</span></div>
            </div>
            <hr>
            <h3><?php printf(wp_kses_post(__('Effet Verre (%s)', 'supersede-css-jlg')), '<code>backdrop-filter</code>'); ?></h3>
            <label><input type="checkbox" id="ssc-glass-enable"> <strong><?php esc_html_e('Activer le Glassmorphism', 'supersede-css-jlg'); ?></strong></label>
            <pre id="ssc-filter-css" class="ssc-code" style="margin-top:16px;"></pre>
            <div class="ssc-actions"><button id="ssc-filter-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button></div>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-filter-preview-bg">
                <div id="ssc-filter-preview-box">
                    <?php esc_html_e('Votre Contenu Ici', 'supersede-css-jlg'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
