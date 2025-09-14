<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class ScopeBuilder {
    public function render(){ ?>
    <div class="ssc-app ssc-fullwidth">
        <h2>Scope Builder</h2>
        
        <div class="ssc-panel" style="margin-bottom: 16px;">
            <h3>Comment utiliser le Scope Builder ?</h3>
            <p>Cet outil vous permet d'appliquer rapidement des styles CSS à des éléments très spécifiques de votre site sans avoir à naviguer dans l'éditeur principal.</p>
            <ol style="margin-left: 20px;">
                <li><strong>Sélecteur(s) :</strong> C'est la cible de votre style. Entrez une classe (<code>.mon-bouton</code>), un ID (<code>#logo</code>) ou une combinaison plus complexe (<code>.card > a</code>).</li>
                <li><strong>Pseudo-classe :</strong> (Optionnel) Choisissez un état pour appliquer le style, comme lorsque l'utilisateur survole l'élément (<code>:hover</code>) ou clique dessus (<code>:focus</code>).</li>
                <li><strong>Propriétés CSS :</strong> Écrivez le code CSS à appliquer dans la zone de texte. Par exemple : <code>background-color: #e73c7e;<br>color: white;<br>border-radius: 50px;</code></li>
                <li><strong>Aperçu :</strong> Le résultat est visible en direct sur les éléments de démo à droite.</li>
                <li><strong>Appliquer :</strong> Ajoute le CSS généré à la feuille de style globale de votre site.</li>
            </ol>
        </div>

        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <label>Sélecteur(s)</label><input type="text" id="ssc-sel" class="large-text" placeholder=".btn, .card > a">
                <label>Pseudo-classe</label><select id="ssc-pseudo"><option value="">(aucune)</option><option value=":hover">:hover</option><option value=":focus">:focus</option></select>
                <label>Propriétés CSS</label><textarea id="ssc-css" rows="12" class="code"></textarea>
                <div class="ssc-actions"><button id="ssc-apply" class="button button-primary">Appliquer</button><button id="ssc-copy" class="button">Copier</button></div>
            </div>
            <div class="ssc-pane"><h3>Preview</h3><div id="ssc-scope-preview-container" style="border: 1px dashed #ccc; padding: 1em; border-radius: 8px;"><style id="ssc-scope-preview-style"></style><button class="btn demo">Bouton</button><a href="#" class="link demo">Lien</a></div></div>
        </div>
    </div>
    <?php }
}