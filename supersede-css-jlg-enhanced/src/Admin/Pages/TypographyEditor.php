<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class TypographyEditor {
    public function render(){ ?>
    <style>
        #ssc-typo-preview { transition: font-size 0.1s linear; }
        .ssc-typo-vp-slider-container { width: 100%; background: var(--ssc-bg); padding: 10px; border-radius: 8px; margin-top: 10px; }
    </style>
    <div class="ssc-app ssc-fullwidth">
        <h2>📏 Typographie Fluide (Clamp)</h2>
        <p>Générez du texte qui s'adapte parfaitement à toutes les tailles d'écran, sans "sauts" disgracieux.</p>
        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Paramètres de la Police (en pixels)</h3>
                <div class="ssc-two">
                    <div><label>Taille min. police</label><input type="number" id="ssc-typo-min-fs" value="16" class="small-text"></div>
                    <div><label>Taille max. police</label><input type="number" id="ssc-typo-max-fs" value="48" class="small-text"></div>
                    <div><label>Largeur min. écran</label><input type="number" id="ssc-typo-min-vp" value="375" class="small-text"></div>
                    <div><label>Largeur max. écran</label><input type="number" id="ssc-typo-max-vp" value="1280" class="small-text"></div>
                </div>
                <h3 style="margin-top:16px;">Code CSS Généré</h3>
                <pre id="ssc-typo-css" class="ssc-code"></pre>
                <div class="ssc-actions"><button id="ssc-typo-copy" class="button">Copier le CSS</button></div>
            </div>
            <div class="ssc-pane">
                 <h3>Aperçu en Direct</h3>
                 <p id="ssc-typo-preview">Ce texte grandit et rétrécit de manière fluide.</p>
                 <div class="ssc-typo-vp-slider-container">
                    <label>Simuler la largeur de l'écran : <span id="ssc-typo-vp-val">375px</span></label>
                    <input type="range" id="ssc-typo-vp-slider" min="320" max="1600" value="375" style="width: 100%;">
                 </div>
            </div>
        </div>
    </div>
    <?php }
}