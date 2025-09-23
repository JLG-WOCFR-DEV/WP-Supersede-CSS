<?php
use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    exit;
}

$modules = Routes::getConfigModules();
?>
<div class="ssc-app ssc-fullwidth">
    <h2>Import / Export</h2>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3>Sauvegardez et restaurez votre configuration</h3>
        <ul>
            <li><strong>Exporter Config (.json) :</strong> Télécharge un fichier JSON contenant vos configurations Supersede CSS (presets, tokens, etc.). Utilisez les cases ci-dessous pour inclure uniquement les ensembles souhaités.</li>
            <li><strong>Exporter CSS (.css) :</strong> Télécharge uniquement le code CSS final qui est appliqué sur votre site. Utile pour une utilisation externe ou une simple sauvegarde du style.</li>
            <li><strong>Importer (.json) :</strong> Restaure une configuration complète depuis un fichier JSON que vous avez précédemment exporté. Seules les options associées aux ensembles cochés seront écrasées.</li>
        </ul>
    </div>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3>Choisissez les ensembles à transférer</h3>
        <p class="description">Les ensembles sélectionnés seront utilisés lors de l'export <strong>et</strong> de l'import.</p>
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
            <h3>Exporter</h3><p>Téléchargez vos configurations ou uniquement le CSS actif.</p>
            <div class="ssc-actions">
                <button id="ssc-export-config" class="button button-primary">Exporter Config (.json)</button>
                <button id="ssc-export-css" class="button">Exporter CSS (.css)</button>
            </div>
        </div>
        <div class="ssc-pane">
            <h3>Importer</h3><p>Importez un fichier de configuration (.json).</p>
            <input type="file" id="ssc-import-file" accept=".json">
            <button id="ssc-import-btn" class="button">Importer</button>
            <div id="ssc-import-msg" class="ssc-muted"></div>
        </div>
    </div>
</div>
