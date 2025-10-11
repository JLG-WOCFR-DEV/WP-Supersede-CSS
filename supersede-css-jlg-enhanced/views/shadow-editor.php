<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Visual Shadow Editor', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <div id="ssc-shadow-layers-container"></div>
            <div class="ssc-actions ssc-mt-200">
                <button id="ssc-shadow-add-layer" class="button"><?php esc_html_e('Ajouter un calque', 'supersede-css-jlg'); ?></button>
            </div>
            <hr>
            <div class="ssc-actions">
                <button id="ssc-shadow-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-shadow-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>
            <pre id="ssc-shadow-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Preview', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-shadow-preview" class="ssc-shadow-preview">
                <?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?>
            </div>
        </div>
    </div>
</div>
