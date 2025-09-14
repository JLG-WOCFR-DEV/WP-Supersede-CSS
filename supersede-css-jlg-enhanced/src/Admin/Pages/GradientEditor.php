<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class GradientEditor {
    public function render(){ ?>
    <div class="ssc-app ssc-fullwidth"><h2>Visual Gradient Editor</h2><div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <div class="ssc-grad-controls">
                <div class="ssc-control-group"><label>Type</label><select id="ssc-grad-type"><option value="linear-gradient">Linéaire</option><option value="radial-gradient">Radial</option><option value="conic-gradient">Conique</option></select></div>
                <div id="ssc-grad-angle-control" class="ssc-control-group"><label>Angle</label><input type="range" id="ssc-grad-angle" min="0" max="360" value="90" step="1"><input type="number" id="ssc-grad-angle-num" min="0" max="360" value="90" class="small-text"> deg</div>
            </div>
            <div class="ssc-control-group"><label>Color Stops</label><div id="ssc-grad-stops-preview" class="ssc-grad-preview-bar"></div><div id="ssc-grad-stops-ui"></div></div>
            <div class="ssc-actions"><button id="ssc-grad-apply" class="button button-primary">Appliquer</button><button id="ssc-grad-copy" class="button">Copier CSS</button></div>
            <pre id="ssc-grad-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane"><h3>Preview</h3><div id="ssc-grad-preview" style="height:200px;border-radius:12px;border:1px solid var(--ssc-border);"></div></div>
    </div></div>
    <?php }
}