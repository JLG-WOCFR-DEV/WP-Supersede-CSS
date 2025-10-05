<?php
use SSC\Infra\Rest\ImportExportController;

if (!defined('ABSPATH')) {
    exit;
}

$modules = ImportExportController::getConfigModules();

if (function_exists('wp_set_script_translations')) {
    wp_set_script_translations('ssc-import-export', 'supersede-css-jlg', SSC_PLUGIN_DIR . 'languages');
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Import / Export', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3><?php esc_html_e('Sauvegardez et restaurez votre configuration', 'supersede-css-jlg'); ?></h3>
        <ul>
            <li><?php echo wp_kses_post(__('<strong>Exporter Config (.json) :</strong> Télécharge un fichier JSON contenant vos configurations Supersede CSS (presets, tokens, etc.). Utilisez les cases ci-dessous pour inclure uniquement les ensembles souhaités.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Exporter CSS (.css) :</strong> Télécharge uniquement le code CSS final qui est appliqué sur votre site. Utile pour une utilisation externe ou une simple sauvegarde du style.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Importer (.json) :</strong> Restaure une configuration complète depuis un fichier JSON que vous avez précédemment exporté. Seules les options associées aux ensembles cochés seront écrasées.', 'supersede-css-jlg')); ?></li>
        </ul>
    </div>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3><?php esc_html_e('Choisissez les ensembles à transférer', 'supersede-css-jlg'); ?></h3>
        <p class="description"><?php printf(wp_kses_post(__('Les ensembles sélectionnés seront utilisés lors de l\'export %s de l\'import.', 'supersede-css-jlg')), '<strong>' . esc_html__('et', 'supersede-css-jlg') . '</strong>'); ?></p>
        <div class="ssc-module-grid" style="display: flex; flex-wrap: wrap; gap: 12px;">
            <?php foreach ($modules as $slug => $module) : ?>
                <label class="ssc-module-option" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 4px; background: #fff;">
                    <input type="checkbox" name="ssc-modules[]" value="<?php echo esc_attr($slug); ?>" checked>
                    <span><?php echo esc_html($module['label']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="ssc-two">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Exporter', 'supersede-css-jlg'); ?></h3><p><?php esc_html_e('Téléchargez vos configurations ou uniquement le CSS actif.', 'supersede-css-jlg'); ?></p>
            <div class="ssc-actions">
                <button id="ssc-export-config" class="button button-primary"><?php esc_html_e('Exporter Config (.json)', 'supersede-css-jlg'); ?></button>
                <button id="ssc-export-css" class="button"><?php esc_html_e('Exporter CSS (.css)', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Importer', 'supersede-css-jlg'); ?></h3><p><?php esc_html_e('Importez un fichier de configuration (.json).', 'supersede-css-jlg'); ?></p>
            <input type="file" id="ssc-import-file" accept=".json">
            <button id="ssc-import-btn" class="button"><?php esc_html_e('Importer', 'supersede-css-jlg'); ?></button>
            <div id="ssc-import-msg" class="ssc-muted"></div>
        </div>
    </div>
</div>
