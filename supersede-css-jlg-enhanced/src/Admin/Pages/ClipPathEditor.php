<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class ClipPathEditor {
    public function render(){ ?>
     <style>
        #ssc-clip-preview-wrapper {
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--ssc-bg);
            border-radius: 8px;
        }
        #ssc-clip-preview { 
            background-image: url('<?php echo esc_url(SSC_PLUGIN_URL . 'assets/images/preview-bg.jpg'); ?>'); 
            background-size: cover; 
            background-position: center;
            height: 300px; 
            width: 300px; 
            transition: all 0.3s ease;
        }
    </style>
    <div class="ssc-app ssc-fullwidth">
        <h2>✂️ Générateur de Formes (Clip-Path)</h2>
        <p>Découpez vos conteneurs et images dans des formes géométriques pour des designs plus dynamiques.</p>
        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Formes Prédéfinies</h3>
                <select id="ssc-clip-preset">
                    <option value="none">Aucune (Rectangle)</option>
                    <option value="circle(50% at 50% 50%)">Cercle</option>
                    <option value="ellipse(50% 30% at 50% 50%)">Ellipse</option>
                    <option value="polygon(50% 0%, 0% 100%, 100% 100%)">Triangle</option>
                    <option value="polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)">Hexagone</option>
                    <option value="polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)">Étoile</option>
                    <option value="polygon(0 15%, 15% 15%, 15% 0, 85% 0, 85% 15%, 100% 15%, 100% 85%, 85% 85%, 85% 100%, 15% 100%, 15% 85%, 0 85%)">Croix</option>
                </select>
                <label style="margin-top:16px; display:block;"><strong>Taille de l'aperçu: <span id="ssc-clip-size-val">300px</span></strong></label>
                <input type="range" id="ssc-clip-preview-size" min="100" max="500" value="300" step="10" style="width:100%;">
                <h3 style="margin-top:16px;">Code CSS Généré</h3>
                <pre id="ssc-clip-css" class="ssc-code"></pre>
                <div class="ssc-actions"><button id="ssc-clip-copy" class="button">Copier le CSS</button></div>
            </div>
            <div class="ssc-pane">
                 <h3>Aperçu</h3>
                 <div id="ssc-clip-preview-wrapper">
                    <div id="ssc-clip-preview"></div>
                 </div>
            </div>
        </div>
    </div>
    <?php }
}