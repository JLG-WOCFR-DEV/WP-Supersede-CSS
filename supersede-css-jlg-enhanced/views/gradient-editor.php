<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Visual Gradient Editor', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <div class="ssc-stack">
                <h3><?php esc_html_e('Paramètres du dégradé', 'supersede-css-jlg'); ?></h3>
                <div class="ssc-form-field">
                    <label class="ssc-form-label" for="ssc-grad-type"><?php esc_html_e('Type', 'supersede-css-jlg'); ?></label>
                    <select id="ssc-grad-type">
                        <option value="linear-gradient"><?php esc_html_e('Linéaire', 'supersede-css-jlg'); ?></option>
                        <option value="radial-gradient"><?php esc_html_e('Radial', 'supersede-css-jlg'); ?></option>
                        <option value="conic-gradient"><?php esc_html_e('Conique', 'supersede-css-jlg'); ?></option>
                    </select>
                </div>
                <div id="ssc-grad-angle-control" class="ssc-form-field">
                    <div class="ssc-form-label-row">
                        <label class="ssc-form-label" for="ssc-grad-angle"><?php esc_html_e('Angle', 'supersede-css-jlg'); ?></label>
                    </div>
                    <div class="ssc-form-control-row">
                        <input type="range" id="ssc-grad-angle" min="0" max="360" value="90" step="1">
                        <div class="ssc-form-control-row__suffix">
                            <input type="number" id="ssc-grad-angle-num" min="0" max="360" value="90" class="small-text">
                            <span aria-hidden="true"><?php esc_html_e('deg', 'supersede-css-jlg'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="ssc-form-field">
                    <label class="ssc-form-label" for="ssc-grad-stops-preview"><?php esc_html_e('Color Stops', 'supersede-css-jlg'); ?></label>
                    <div id="ssc-grad-stops-preview" class="ssc-grad-preview-bar"></div>
                    <div id="ssc-grad-stops-ui" class="ssc-grad-stops"></div>
                </div>
                <div class="ssc-form-actions">
                    <button id="ssc-grad-apply" class="button button-primary" type="button"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                    <button id="ssc-grad-copy" class="button" type="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
                </div>
                <pre id="ssc-grad-css" class="ssc-code"></pre>
            </div>
        </div>
        <div class="ssc-pane">
            <div class="ssc-stack">
                <h3><?php esc_html_e('Preview', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-grad-preview" class="ssc-grad-preview ssc-preview-surface"></div>
            </div>
        </div>
    </div>
</div>
