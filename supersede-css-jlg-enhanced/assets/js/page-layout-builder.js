(function($) {

    const presets = {
        'holy-grail': {
            name: 'Holy Grail (Header, 3 colonnes, Footer)',
            containerClass: 'ssc-layout-holy-grail',
            desktop: {
                gridTemplateAreas: `
                    "header header header"
                    "nav    content aside"
                    "footer footer footer"
                `,
                gridTemplateRows: 'auto 1fr auto',
                gridTemplateColumns: '1fr 3fr 1fr'
            },
            mobile: {
                gridTemplateAreas: `
                    "header"
                    "nav"
                    "content"
                    "aside"
                    "footer"
                `,
                gridTemplateRows: 'auto auto 1fr auto auto',
                gridTemplateColumns: '1fr'
            },
            blocks: [
                { name: 'Header', area: 'header' },
                { name: 'Navigation', area: 'nav' },
                { name: 'Contenu Principal', area: 'content' },
                { name: 'Barre Latérale', area: 'aside' },
                { name: 'Pied de Page', area: 'footer' }
            ]
        },
        'sidebar-right': {
            name: 'Contenu + Sidebar à Droite',
            containerClass: 'ssc-layout-sidebar-right',
            desktop: {
                gridTemplateAreas: `
                    "content sidebar"
                `,
                gridTemplateColumns: '3fr 1fr'
            },
            mobile: {
                gridTemplateAreas: `
                    "content"
                    "sidebar"
                `,
                gridTemplateColumns: '1fr'
            },
            blocks: [
                { name: 'Contenu Principal', area: 'content' },
                { name: 'Barre Latérale', area: 'sidebar' }
            ]
        },
        'hero-features': {
            name: 'Section Héro + 3 Cartes',
            containerClass: 'ssc-layout-hero-features',
            desktop: {
                gridTemplateAreas: `
                    "hero hero hero"
                    "card1 card2 card3"
                `,
                gridTemplateRows: 'auto 1fr',
                gridTemplateColumns: '1fr 1fr 1fr'
            },
            mobile: {
                gridTemplateAreas: `
                    "hero"
                    "card1"
                    "card2"
                    "card3"
                `,
                gridTemplateColumns: '1fr'
            },
            blocks: [
                { name: 'Hero Banner', area: 'hero' },
                { name: 'Carte 1', area: 'card1' },
                { name: 'Carte 2', area: 'card2' },
                { name: 'Carte 3', area: 'card3' }
            ]
        },
        'dashboard': {
            name: 'Tableau de Bord Asymétrique',
            containerClass: 'ssc-layout-dashboard',
            desktop: {
                gridTemplateAreas: `
                    "menu header"
                    "menu main"
                    "menu stats"
                `,
                gridTemplateColumns: '240px 1fr',
                gridTemplateRows: 'auto 1fr auto'
            },
            mobile: {
                gridTemplateAreas: `
                    "header"
                    "menu"
                    "main"
                    "stats"
                `,
                gridTemplateColumns: '1fr'
            },
            blocks: [
                { name: 'Menu', area: 'menu' },
                { name: 'Header', area: 'header' },
                { name: 'Contenu Principal', area: 'main' },
                { name: 'Statistiques', area: 'stats' }
            ]
        }
    };

    function renderLayout() {
        const presetKey = $('#layout-preset').val();
        const preset = presets[presetKey];
        if (!preset) return;

        const gridDesktop = $('#layout-grid-desktop');
        const gridMobile = $('#layout-grid-mobile');

        // Vider les grilles
        gridDesktop.empty();
        gridMobile.empty();

        // Appliquer les styles aux conteneurs
        gridDesktop.css(preset.desktop);
        gridMobile.css(preset.mobile);

        // Ajouter les blocs
        preset.blocks.forEach((block, index) => {
            const blockHtml = $(`<div class="ssc-layout-block" style="grid-area: ${block.area};"><span>${index + 1}. ${block.name}</span></div>`);
            gridDesktop.append(blockHtml.clone());
            gridMobile.append(blockHtml.clone());
        });
        
        generateCSS(preset);
    }
    
    function generateCSS(preset) {
        // Fonction pour formater les propriétés CSS
        const formatCssProps = (props) => Object.entries(props)
            .map(([key, value]) => `  ${key.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`)}: ${value.trim().replace(/\n\s*/g, ' ')};`)
            .join('\n');

        const desktopStyles = formatCssProps(preset.desktop);
        const mobileStyles = formatCssProps(preset.mobile);
        const blockStyles = preset.blocks.map(block => `.${preset.containerClass} > .${block.area} { grid-area: ${block.area}; }`).join('\n');

        const css = `
/* Layout: ${preset.name} */
.${preset.containerClass} {
${desktopStyles}
}

${blockStyles}

@media (max-width: 782px) {
  .${preset.containerClass} {
${mobileStyles}
  }
}
        `;
        
        $('#layout-css').text(css.trim());
    }

    $(document).ready(function() {
        if (!$('#layout-preset').length) return;

        $('#layout-preset').on('change', renderLayout);
        
        const gridDesktop = $('#layout-grid-desktop');
        const gridMobile = $('#layout-grid-mobile');

        $('#view-desktop').on('click', function() {
            $(this).addClass('button-primary').siblings().removeClass('button-primary');
            gridDesktop.removeClass('ssc-hidden');
            gridMobile.addClass('ssc-hidden');
        });

        $('#view-mobile').on('click', function() {
            $(this).addClass('button-primary').siblings().removeClass('button-primary');
            gridDesktop.addClass('ssc-hidden');
            gridMobile.removeClass('ssc-hidden');
        });

        renderLayout();
    });

})(jQuery);