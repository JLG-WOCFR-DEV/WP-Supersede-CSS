<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Visual Gradient Editor', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <div class="ssc-grad-controls">
                <div class="ssc-control-group">
                    <label><?php esc_html_e('Type', 'supersede-css-jlg'); ?></label>
                    <select id="ssc-grad-type">
                        <option value="linear-gradient"><?php esc_html_e('Linéaire', 'supersede-css-jlg'); ?></option>
                        <option value="radial-gradient"><?php esc_html_e('Radial', 'supersede-css-jlg'); ?></option>
                        <option value="conic-gradient"><?php esc_html_e('Conique', 'supersede-css-jlg'); ?></option>
                    </select>
                </div>
                <div id="ssc-grad-angle-control" class="ssc-control-group">
                    <label><?php esc_html_e('Angle', 'supersede-css-jlg'); ?></label>
                    <input type="range" id="ssc-grad-angle" min="0" max="360" value="90" step="1">
                    <input type="number" id="ssc-grad-angle-num" min="0" max="360" value="90" class="small-text"> <?php esc_html_e('deg', 'supersede-css-jlg'); ?>
                </div>
            </div>
            <div class="ssc-control-group">
                <label><?php esc_html_e('Color Stops', 'supersede-css-jlg'); ?></label>
                <div id="ssc-grad-stops-preview" class="ssc-grad-preview-bar"></div>
                <div id="ssc-grad-stops-ui"></div>
            </div>
            <div class="ssc-actions">
                <button id="ssc-grad-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-grad-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>
            <pre id="ssc-grad-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Preview', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-grad-preview" style="height:200px;border-radius:12px;border:1px solid var(--ssc-border);"></div>
        </div>
    </div>
</div>
