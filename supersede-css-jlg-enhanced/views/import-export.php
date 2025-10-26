<?php
use SSC\Infra\Rest\ImportExportController;

if (!defined('ABSPATH')) {
    exit;
}

$modules = ImportExportController::getConfigModules();
$moduleDescriptions = [
    'css' => __('Inclut le CSS actif et les variantes Desktop/Tablette/Mobile.', 'supersede-css-jlg'),
    'tokens' => __('Inclut les Design Tokens (CSS custom properties) et leur registre JSON.', 'supersede-css-jlg'),
    'presets' => __('Inclut les presets Supersede CSS que vous avez créés.', 'supersede-css-jlg'),
    'visual_effects' => __('Inclut les presets dédiés aux effets visuels.', 'supersede-css-jlg'),
    'avatar' => __('Inclut les presets Avatar Glow.', 'supersede-css-jlg'),
    'settings' => __('Inclut les paramètres généraux et les modules activés.', 'supersede-css-jlg'),
    'logs' => __('Inclut le journal d’administration Supersede CSS.', 'supersede-css-jlg'),
];

if (function_exists('wp_set_script_translations')) {
    wp_set_script_translations('ssc-import-export', 'supersede-css-jlg', SSC_PLUGIN_DIR . 'languages');
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Import / Export', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-panel ssc-transfer-panel">
        <h3><?php esc_html_e('Sauvegardez et restaurez votre configuration', 'supersede-css-jlg'); ?></h3>
        <ul>
            <li><?php echo wp_kses_post(__('<strong>Exporter Config (.json) :</strong> Télécharge un fichier JSON contenant vos configurations Supersede CSS (presets, tokens, etc.). Utilisez les cases ci-dessous pour inclure uniquement les ensembles souhaités.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Exporter CSS (.css) :</strong> Télécharge uniquement le code CSS final qui est appliqué sur votre site. Utile pour une utilisation externe ou une simple sauvegarde du style.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Importer (.json) :</strong> Restaure une configuration complète depuis un fichier JSON que vous avez précédemment exporté. Seules les options associées aux ensembles cochés seront écrasées.', 'supersede-css-jlg')); ?></li>
        </ul>
    </div>
    <div class="ssc-panel ssc-transfer-panel">
        <fieldset class="ssc-module-fieldset">
            <legend><?php esc_html_e('Choisissez les ensembles à transférer', 'supersede-css-jlg'); ?></legend>
            <p id="ssc-modules-description" class="description"><?php printf(wp_kses_post(__('Les ensembles sélectionnés seront utilisés lors de l\'export %s de l\'import.', 'supersede-css-jlg')), '<strong>' . esc_html__('et', 'supersede-css-jlg') . '</strong>'); ?></p>
            <div class="ssc-module-actions">
                <div class="ssc-module-action-buttons">
                    <button type="button" id="ssc-modules-select-all" class="button button-secondary"><?php esc_html_e('Tout sélectionner', 'supersede-css-jlg'); ?></button>
                    <button type="button" id="ssc-modules-select-none" class="button button-link"><?php esc_html_e('Tout désélectionner', 'supersede-css-jlg'); ?></button>
                </div>
                <p id="ssc-modules-summary" class="ssc-module-summary" role="status" aria-live="polite"></p>
            </div>
            <div class="ssc-module-grid" role="group" aria-describedby="ssc-modules-description">
                <?php foreach ($modules as $slug => $module) :
                    $inputId = 'ssc-module-' . sanitize_html_class((string) $slug);
                    $helperId = $inputId . '-helper';
                    $optionCount = isset($module['options']) && is_array($module['options']) ? count($module['options']) : 0;
                    $baseDescription = $moduleDescriptions[$slug] ?? __('Inclut les options Supersede CSS associées à ce module.', 'supersede-css-jlg');
                    $helperText = $optionCount > 0
                        ? sprintf(
                            _n('%1$s (%2$d option).', '%1$s (%2$d options).', $optionCount, 'supersede-css-jlg'),
                            $baseDescription,
                            $optionCount
                        )
                        : $baseDescription;
                ?>
                    <div class="ssc-module-option">
                        <input
                            type="checkbox"
                            name="ssc-modules[]"
                            value="<?php echo esc_attr((string) $slug); ?>"
                            id="<?php echo esc_attr($inputId); ?>"
                            checked
                            aria-describedby="<?php echo esc_attr($helperId); ?>"
                        >
                        <label for="<?php echo esc_attr($inputId); ?>" class="ssc-module-option__label">
                            <span class="ssc-module-option__title"><?php echo esc_html($module['label']); ?></span>
                            <span class="ssc-module-option__helper" id="<?php echo esc_attr($helperId); ?>"><?php echo esc_html($helperText); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
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
            <label for="ssc-import-file" class="screen-reader-text">
                <?php echo esc_html__('Importer un fichier de configuration Supersede CSS', 'supersede-css-jlg'); ?>
            </label>
            <input type="file" id="ssc-import-file" accept=".json">
            <button id="ssc-import-btn" class="button"><?php esc_html_e('Importer', 'supersede-css-jlg'); ?></button>
            <div id="ssc-import-msg" class="ssc-muted"></div>
        </div>
    </div>
</div>
