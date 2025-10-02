import { render } from '@wordpress/element';
import TokensApp from './tokens';
import VisualEffectsApp from './visual-effects';

declare global {
    interface Window {
        sscAdminAppUnmount?: () => void;
    }
}

function mountApp(element: Element, Component: () => JSX.Element | null) {
    render(<Component />, element);
}

const tokensRoot = document.getElementById('ssc-token-app-root');
if (tokensRoot) {
    mountApp(tokensRoot, TokensApp);
}

const visualEffectsRoot = document.getElementById('ssc-visual-effects-app-root');
if (visualEffectsRoot) {
    mountApp(visualEffectsRoot, VisualEffectsApp);
}
