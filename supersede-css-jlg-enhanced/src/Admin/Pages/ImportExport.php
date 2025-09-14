<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class ImportExport {
    public function render(){ ?>
    <div class="ssc-app ssc-fullwidth">
        <h2>Import / Export</h2>
        <div class="ssc-panel" style="margin-bottom: 16px;">
            <h3>Sauvegardez et restaurez votre configuration</h3>
            <ul>
                <li><strong>Exporter Config (.json) :</strong> Télécharge un fichier JSON contenant <strong>toutes</strong> vos configurations Supersede CSS (presets, tokens, etc.). Idéal pour sauvegarder votre travail ou le migrer vers un autre site.</li>
                <li><strong>Exporter CSS (.css) :</strong> Télécharge uniquement le code CSS final qui est appliqué sur votre site. Utile pour une utilisation externe ou une simple sauvegarde du style.</li>
                <li><strong>Importer (.json) :</strong> Restaure une configuration complète depuis un fichier JSON que vous avez précédemment exporté. <strong>Attention :</strong> cela remplacera vos configurations actuelles.</li>
            </ul>
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
    <?php }
}