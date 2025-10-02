import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader, Flex, FlexBlock, FlexItem, RangeControl, SelectControl, TabPanel } from '@wordpress/components';

type ToastOptions = {
    politeness?: 'assertive' | 'polite';
    role?: string;
};

type RestInfo = {
    root: string;
    nonce: string;
};

declare global {
    interface Window {
        SSC?: {
            rest?: Partial<RestInfo>;
        };
        sscToast?: (message: string, options?: ToastOptions) => void;
        wp?: {
            media?: (options: Record<string, unknown>) => any;
        };
    }
}

function useRestInfo(): RestInfo {
    const root = window.SSC?.rest?.root ?? '';
    const nonce = window.SSC?.rest?.nonce ?? '';
    return { root, nonce };
}

function toast(message: string, options: ToastOptions = {}) {
    if (typeof window.sscToast === 'function') {
        window.sscToast(message, options);
    }
}

function ensureStyleElement(id: string): HTMLStyleElement {
    let style = document.getElementById(id) as HTMLStyleElement | null;
    if (!style) {
        style = document.createElement('style');
        style.id = id;
        document.head.appendChild(style);
    }
    return style;
}

function removeStyleElement(id: string) {
    const element = document.getElementById(id);
    if (element?.parentElement) {
        element.parentElement.removeChild(element);
    }
}

type BackgroundType = 'stars' | 'gradient';

const ECG_PATHS: Record<string, string> = {
    stable: 'M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30',
    fast: 'M0,30 L60,30 L70,8 L80,52 L90,18 L100,30 L160,30 L170,12 L180,48 L190,22 L200,30 L400,30',
    critical: 'M0,30 L40,30 L50,5 L60,55 L70,15 L80,30 L120,30 L130,2 L140,58 L150,12 L160,30 L400,30',
};

async function postCss(rest: RestInfo, css: string, { append = true }: { append?: boolean } = {}) {
    if (!rest.root) {
        return;
    }
    const response = await fetch(`${rest.root}save-css`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': rest.nonce,
        },
        body: JSON.stringify({ css, append }),
    });
    if (!response.ok) {
        throw new Error('Failed to save CSS');
    }
}

function generateStarsCss(color: string, count: number): { css: string } {
    const keyframes = '@keyframes ssc-stars-anim { from { transform: translateY(0); } to { transform: translateY(-2000px); } }';
    const shadows = Array.from({ length: count }).map(
        () => `${Math.random() * 2000}px ${Math.random() * 2000}px ${color}`
    );
    const css = `${keyframes}\n.ssc-bg-stars {\n  background: #000000;\n  position: relative;\n  overflow: hidden;\n}\n.ssc-bg-stars::after {\n  content: '';\n  position: absolute;\n  top: 0;\n  left: 0;\n  width: 1px;\n  height: 1px;\n  background: transparent;\n  box-shadow: ${shadows.join(', ')};\n  animation: ssc-stars-anim 50s linear infinite;\n}`;
    return { css };
}

function generateGradientCss(speed: number): string {
    const keyframes = '@keyframes ssc-gradient-anim { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }';
    return `${keyframes}\n.ssc-bg-gradient {\n  background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);\n  background-size: 400% 400%;\n  animation: ssc-gradient-anim ${speed}s ease infinite;\n}`;
}

type BackgroundState = {
    type: BackgroundType;
    starColor: string;
    starCount: number;
    gradientSpeed: number;
};

const INITIAL_BACKGROUND: BackgroundState = {
    type: 'stars',
    starColor: '#FFFFFF',
    starCount: 200,
    gradientSpeed: 10,
};

const INITIAL_ECG_STATE = {
    preset: 'stable',
    color: '#00ff00',
    top: 50,
    zIndex: 1,
    logoSize: 100,
    logoUrl: '',
};

type EcgState = typeof INITIAL_ECG_STATE;

function useAnimationFrame(callback: () => void, enabled: boolean) {
    const frame = useRef<number | null>(null);
    const loop = useCallback(() => {
        callback();
        frame.current = window.requestAnimationFrame(loop);
    }, [callback]);
    useEffect(() => {
        if (!enabled) {
            return undefined;
        }
        frame.current = window.requestAnimationFrame(loop);
        return () => {
            if (frame.current) {
                window.cancelAnimationFrame(frame.current);
            }
        };
    }, [enabled, loop]);
}

export function VisualEffectsApp(): JSX.Element | null {
    const rest = useRestInfo();
    const [activeTab, setActiveTab] = useState<string>('backgrounds');
    const [backgroundState, setBackgroundState] = useState<BackgroundState>(INITIAL_BACKGROUND);
    const [backgroundCss, setBackgroundCss] = useState('');
    const backgroundPreviewRef = useRef<HTMLDivElement>(null);
    const [isApplyingBackground, setIsApplyingBackground] = useState(false);

    const [ecgState, setEcgState] = useState<EcgState>(INITIAL_ECG_STATE);
    const [ecgCss, setEcgCss] = useState('');
    const ecgPathRef = useRef<SVGPathElement>(null);
    const ecgSvgRef = useRef<SVGSVGElement>(null);
    const [isApplyingEcg, setIsApplyingEcg] = useState(false);

    const crtCanvasRef = useRef<HTMLCanvasElement>(null);
    const [crtSettings, setCrtSettings] = useState({
        scanlineColor: '#00ff00',
        scanlineOpacity: 0.4,
        scanlineSpeed: 0.5,
        noiseIntensity: 0.1,
        chromaticAberration: 1,
    });

    useEffect(() => {
        if (backgroundState.type === 'stars') {
            const { css } = generateStarsCss(backgroundState.starColor, backgroundState.starCount);
            setBackgroundCss(css.trim());
            const style = ensureStyleElement('ssc-stars-preview-style');
            style.textContent = css;
            removeStyleElement('ssc-gradient-anim-style');
            if (backgroundPreviewRef.current) {
                backgroundPreviewRef.current.className = 'ssc-ve-preview-box ssc-bg-stars';
                backgroundPreviewRef.current.removeAttribute('style');
            }
        } else {
            const css = generateGradientCss(backgroundState.gradientSpeed);
            setBackgroundCss(css.trim());
            const style = ensureStyleElement('ssc-gradient-anim-style');
            style.textContent = css;
            removeStyleElement('ssc-stars-preview-style');
            if (backgroundPreviewRef.current) {
                backgroundPreviewRef.current.className = 'ssc-ve-preview-box ssc-bg-gradient';
                backgroundPreviewRef.current.style.background = 'linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab)';
                backgroundPreviewRef.current.style.backgroundSize = '400% 400%';
                backgroundPreviewRef.current.style.animation = `ssc-gradient-anim ${backgroundState.gradientSpeed}s ease infinite`;
                backgroundPreviewRef.current.removeAttribute('data-stars-shadows');
            }
        }
    }, [backgroundState]);

    const handleBackgroundApply = useCallback(async () => {
        if (!backgroundCss) {
            return;
        }
        setIsApplyingBackground(true);
        try {
            await postCss(rest, backgroundCss, { append: true });
            toast(__('Fond animé appliqué !', 'supersede-css-jlg'));
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('SSC background apply failed', error);
            toast(__('Échec de l\'enregistrement du fond animé.', 'supersede-css-jlg'), { politeness: 'assertive' });
        } finally {
            setIsApplyingBackground(false);
        }
    }, [backgroundCss, rest]);

    useEffect(() => {
        const { preset, color, top, zIndex, logoSize, logoUrl } = ecgState;
        const speed = preset === 'fast' ? '1.2s' : preset === 'critical' ? '0.8s' : '2s';
        const css = `@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}\n.ssc-ecg-container { position: relative; }\n.ssc-ecg-line-svg { position: absolute; top: ${top}%; left: 0; width: 100%; height: auto; transform: translateY(-50%); z-index: ${zIndex}; }\n.ssc-ecg-path-animated { fill:none; stroke:${color}; stroke-width:2; stroke-dasharray:1000; stroke-dashoffset:1000; animation:ssc-ecg-line ${speed} linear infinite; filter:drop-shadow(0 0 5px ${color}) }`;
        setEcgCss(css.trim());
        const style = ensureStyleElement('ssc-ecg-anim');
        style.textContent = '@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}';
        if (ecgPathRef.current) {
            ecgPathRef.current.setAttribute('d', ECG_PATHS[preset]);
            ecgPathRef.current.style.stroke = color;
            ecgPathRef.current.style.strokeDasharray = '1000';
            ecgPathRef.current.style.strokeDashoffset = '1000';
            ecgPathRef.current.style.animation = `ssc-ecg-line ${speed} linear infinite`;
        }
        if (ecgSvgRef.current) {
            ecgSvgRef.current.style.top = `${top}%`;
            ecgSvgRef.current.style.transform = 'translateY(-50%)';
            ecgSvgRef.current.style.zIndex = String(zIndex);
        }
        const logo = document.getElementById('ssc-ecg-logo-preview') as HTMLImageElement | null;
        if (logo) {
            if (logoUrl) {
                logo.src = logoUrl;
                logo.style.display = 'block';
            } else {
                logo.removeAttribute('src');
                logo.style.display = 'none';
            }
            logo.style.maxWidth = `${logoSize}px`;
            logo.style.maxHeight = `${logoSize}px`;
        }
    }, [ecgState]);

    const handleEcgApply = useCallback(async () => {
        if (!ecgCss) {
            return;
        }
        setIsApplyingEcg(true);
        try {
            await postCss(rest, ecgCss, { append: true });
            toast(__('Effet ECG appliqué !', 'supersede-css-jlg'));
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('SSC ECG apply failed', error);
            toast(__('Impossible d\'appliquer l\'effet ECG.', 'supersede-css-jlg'), { politeness: 'assertive' });
        } finally {
            setIsApplyingEcg(false);
        }
    }, [ecgCss, rest]);

    useAnimationFrame(() => {
        const canvas = crtCanvasRef.current;
        if (!canvas) {
            return;
        }
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }
        const { scanlineColor, scanlineOpacity, scanlineSpeed, noiseIntensity, chromaticAberration } = crtSettings;
        const width = canvas.offsetWidth || 400;
        const height = canvas.offsetHeight || 200;
        if (canvas.width !== width) {
            canvas.width = width;
        }
        if (canvas.height !== height) {
            canvas.height = height;
        }
        const imageData = ctx.createImageData(width, height);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const noise = Math.random() * noiseIntensity * 255;
            data[i] = data[i + 1] = data[i + 2] = noise;
            data[i + 3] = 255;
        }
        ctx.putImageData(imageData, 0, 0);
        const channels = [
            { color: `rgba(255, 0, 0, ${scanlineOpacity / 2})`, offset: chromaticAberration },
            { color: `rgba(${parseInt(scanlineColor.slice(1, 3), 16)}, ${parseInt(scanlineColor.slice(3, 5), 16)}, ${parseInt(scanlineColor.slice(5, 7), 16)}, ${scanlineOpacity})`, offset: 0 },
            { color: `rgba(0, 0, 255, ${scanlineOpacity / 2})`, offset: -chromaticAberration },
        ];
        const time = Date.now() / 1000;
        channels.forEach(({ color, offset }) => {
            ctx.fillStyle = color;
            const scanlineOffset = ((time * scanlineSpeed * 10) % 4);
            for (let y = scanlineOffset; y < height; y += 4) {
                ctx.fillRect(offset, y, width, 2);
            }
        });
    }, activeTab === 'crt');

    const openMediaFrame = useCallback(() => {
        if (!window.wp?.media) {
            return;
        }
        const frame = window.wp.media({ title: __('Choisir une image', 'supersede-css-jlg'), multiple: false });
        frame.on('select', () => {
            const selection = frame.state().get('selection');
            const first = selection.first();
            if (!first) {
                return;
            }
            const url = first.toJSON().url as string;
            setEcgState((current) => ({ ...current, logoUrl: url }));
        });
        frame.open();
    }, []);

    const tabItems = useMemo<{ name: string; title: string }[]>(
        () => [
            {
                name: 'backgrounds',
                title: __('🌌 Fonds Animés', 'supersede-css-jlg'),
            },
            {
                name: 'ecg',
                title: __('❤️ ECG / Battement de Cœur', 'supersede-css-jlg'),
            },
            {
                name: 'crt',
                title: __('📺 Effet CRT (Scanline)', 'supersede-css-jlg'),
            },
        ],
        []
    );

    return (
        <div className="ssc-visual-effects-app" data-component="visual-effects">
            <TabPanel
                className="ssc-ve-tabs"
                activeClass="active"
                tabs={tabItems}
                onSelect={(tabName) => setActiveTab(tabName as string)}
            >
                {(tab) => {
                    if (tab.name === 'backgrounds') {
                        return (
                            <Card key="backgrounds" id="ssc-ve-panel-backgrounds" className="ssc-ve-panel active">
                                <CardHeader>
                                    <h3>{__('Paramètres du Fond', 'supersede-css-jlg')}</h3>
                                </CardHeader>
                                <CardBody>
                                    <Flex align="flex-start" gap={16} wrap>
                                        <FlexBlock>
                                            <SelectControl
                                                label={__('Type d\'arrière-plan', 'supersede-css-jlg')}
                                                value={backgroundState.type}
                                                options={[
                                                    { label: __('Étoiles', 'supersede-css-jlg'), value: 'stars' },
                                                    { label: __('Dégradé', 'supersede-css-jlg'), value: 'gradient' },
                                                ]}
                                                onChange={(value) =>
                                                    setBackgroundState((current) => ({ ...current, type: value as BackgroundType }))
                                                }
                                            />
                                            {backgroundState.type === 'stars' ? (
                                                <>
                                                    <label htmlFor="starColor" className="ssc-ve-label">
                                                        {__('Couleur des étoiles', 'supersede-css-jlg')}
                                                    </label>
                                                    <input
                                                        id="starColor"
                                                        type="color"
                                                        value={backgroundState.starColor}
                                                        onChange={(event) =>
                                                            setBackgroundState((current) => ({
                                                                ...current,
                                                                starColor: event.target.value,
                                                            }))
                                                        }
                                                    />
                                                    <RangeControl
                                                        label={__('Nombre d\'étoiles', 'supersede-css-jlg')}
                                                        min={50}
                                                        max={500}
                                                        step={10}
                                                        value={backgroundState.starCount}
                                                        onChange={(value) =>
                                                            setBackgroundState((current) => ({
                                                                ...current,
                                                                starCount: typeof value === 'number' ? value : current.starCount,
                                                            }))
                                                        }
                                                    />
                                                </>
                                            ) : (
                                                <RangeControl
                                                    label={__('Vitesse du dégradé', 'supersede-css-jlg')}
                                                    min={2}
                                                    max={20}
                                                    step={1}
                                                    value={backgroundState.gradientSpeed}
                                                    onChange={(value) =>
                                                        setBackgroundState((current) => ({
                                                            ...current,
                                                            gradientSpeed:
                                                                typeof value === 'number' ? value : current.gradientSpeed,
                                                        }))
                                                    }
                                                />
                                            )}
                                            <pre id="ssc-bg-css" className="ssc-code">
                                                {backgroundCss}
                                            </pre>
                                            <Button
                                                id="ssc-bg-apply"
                                                variant="primary"
                                                onClick={handleBackgroundApply}
                                                disabled={isApplyingBackground}
                                                isBusy={isApplyingBackground}
                                            >
                                                {isApplyingBackground
                                                    ? __('Application…', 'supersede-css-jlg')
                                                    : __('Appliquer', 'supersede-css-jlg')}
                                            </Button>
                                        </FlexBlock>
                                        <FlexItem>
                                            <h3>{__('Aperçu', 'supersede-css-jlg')}</h3>
                                            <div id="ssc-bg-preview" ref={backgroundPreviewRef} className="ssc-ve-preview-box" />
                                        </FlexItem>
                                    </Flex>
                                </CardBody>
                            </Card>
                        );
                    }
                    if (tab.name === 'ecg') {
                        return (
                            <Card key="ecg" id="ssc-ve-panel-ecg" className="ssc-ve-panel active">
                                <CardHeader>
                                    <h3>{__('Paramètres de l\'ECG', 'supersede-css-jlg')}</h3>
                                </CardHeader>
                                <CardBody>
                                    <Flex align="flex-start" gap={16} wrap>
                                        <FlexBlock>
                                            <SelectControl
                                                label={__('Preset de Rythme', 'supersede-css-jlg')}
                                                value={ecgState.preset}
                                                options={[
                                                    { label: __('Stable', 'supersede-css-jlg'), value: 'stable' },
                                                    { label: __('Rapide', 'supersede-css-jlg'), value: 'fast' },
                                                    { label: __('Critique', 'supersede-css-jlg'), value: 'critical' },
                                                ]}
                                                onChange={(value) => setEcgState((current) => ({ ...current, preset: value }))}
                                            />
                                            <label htmlFor="ssc-ecg-color" className="ssc-ve-label">
                                                {__('Couleur de la ligne', 'supersede-css-jlg')}
                                            </label>
                                            <input
                                                id="ssc-ecg-color"
                                                type="color"
                                                value={ecgState.color}
                                                onChange={(event) =>
                                                    setEcgState((current) => ({ ...current, color: event.target.value }))
                                                }
                                            />
                                            <RangeControl
                                                label={__('Positionnement (top)', 'supersede-css-jlg')}
                                                min={0}
                                                max={100}
                                                step={1}
                                                value={ecgState.top}
                                                onChange={(value) =>
                                                    setEcgState((current) => ({
                                                        ...current,
                                                        top: typeof value === 'number' ? value : current.top,
                                                    }))
                                                }
                                            />
                                            <RangeControl
                                                label={__('Superposition (z-index)', 'supersede-css-jlg')}
                                                min={-10}
                                                max={10}
                                                step={1}
                                                value={ecgState.zIndex}
                                                onChange={(value) =>
                                                    setEcgState((current) => ({
                                                        ...current,
                                                        zIndex: typeof value === 'number' ? value : current.zIndex,
                                                    }))
                                                }
                                            />
                                            <Button id="ssc-ecg-upload-btn" variant="secondary" onClick={openMediaFrame}>
                                                {__('Choisir une image', 'supersede-css-jlg')}
                                            </Button>
                                            <RangeControl
                                                label={__('Taille du logo', 'supersede-css-jlg')}
                                                min={20}
                                                max={200}
                                                step={1}
                                                value={ecgState.logoSize}
                                                onChange={(value) =>
                                                    setEcgState((current) => ({
                                                        ...current,
                                                        logoSize: typeof value === 'number' ? value : current.logoSize,
                                                    }))
                                                }
                                            />
                                            <pre id="ssc-ecg-css" className="ssc-code ssc-code-small">
                                                {ecgCss}
                                            </pre>
                                            <Button
                                                id="ssc-ecg-apply"
                                                variant="primary"
                                                onClick={handleEcgApply}
                                                disabled={isApplyingEcg}
                                                isBusy={isApplyingEcg}
                                            >
                                                {isApplyingEcg
                                                    ? __('Application…', 'supersede-css-jlg')
                                                    : __('Appliquer l\'Effet', 'supersede-css-jlg')}
                                            </Button>
                                        </FlexBlock>
                                        <FlexItem>
                                            <h3>{__('Aperçu', 'supersede-css-jlg')}</h3>
                                            <div id="ssc-ecg-preview-container" className="ssc-ve-preview-box">
                                                <img id="ssc-ecg-logo-preview" alt={__('Logo Preview', 'supersede-css-jlg')} style={{ display: 'none' }} />
                                                <svg
                                                    id="ssc-ecg-preview-svg"
                                                    ref={ecgSvgRef}
                                                    viewBox="0 0 400 60"
                                                    preserveAspectRatio="none"
                                                    className="ssc-ecg-line-svg"
                                                >
                                                    <path
                                                        id="ssc-ecg-preview-path"
                                                        ref={ecgPathRef}
                                                        className="ssc-ecg-path"
                                                        d={ECG_PATHS[ecgState.preset]}
                                                    />
                                                </svg>
                                            </div>
                                        </FlexItem>
                                    </Flex>
                                </CardBody>
                            </Card>
                        );
                    }
                    return (
                        <Card key="crt" id="ssc-ve-panel-crt" className="ssc-ve-panel active">
                            <CardHeader>
                                <h3>{__('Paramètres de l\'effet CRT', 'supersede-css-jlg')}</h3>
                            </CardHeader>
                            <CardBody>
                                <Flex align="flex-start" gap={16} wrap>
                                    <FlexBlock>
                                        <label htmlFor="scanlineColor" className="ssc-ve-label">
                                            {__('Couleur Scanline', 'supersede-css-jlg')}
                                        </label>
                                        <input
                                            id="scanlineColor"
                                            className="ssc-crt-control"
                                            type="color"
                                            value={crtSettings.scanlineColor}
                                            onChange={(event) =>
                                                setCrtSettings((current) => ({
                                                    ...current,
                                                    scanlineColor: event.target.value,
                                                }))
                                            }
                                        />
                                        <RangeControl
                                            label={__('Opacité Scanline', 'supersede-css-jlg')}
                                            min={0}
                                            max={1}
                                            step={0.05}
                                            value={crtSettings.scanlineOpacity}
                                            onChange={(value) =>
                                                setCrtSettings((current) => ({
                                                    ...current,
                                                    scanlineOpacity:
                                                        typeof value === 'number' ? value : current.scanlineOpacity,
                                                }))
                                            }
                                        />
                                        <RangeControl
                                            label={__('Vitesse Scanline', 'supersede-css-jlg')}
                                            min={0.1}
                                            max={2}
                                            step={0.1}
                                            value={crtSettings.scanlineSpeed}
                                            onChange={(value) =>
                                                setCrtSettings((current) => ({
                                                    ...current,
                                                    scanlineSpeed:
                                                        typeof value === 'number' ? value : current.scanlineSpeed,
                                                }))
                                            }
                                        />
                                        <RangeControl
                                            label={__('Intensité Bruit', 'supersede-css-jlg')}
                                            min={0}
                                            max={0.5}
                                            step={0.02}
                                            value={crtSettings.noiseIntensity}
                                            onChange={(value) =>
                                                setCrtSettings((current) => ({
                                                    ...current,
                                                    noiseIntensity:
                                                        typeof value === 'number' ? value : current.noiseIntensity,
                                                }))
                                            }
                                        />
                                        <RangeControl
                                            label={__('Aberration Chromatique', 'supersede-css-jlg')}
                                            min={0}
                                            max={5}
                                            step={0.5}
                                            value={crtSettings.chromaticAberration}
                                            onChange={(value) =>
                                                setCrtSettings((current) => ({
                                                    ...current,
                                                    chromaticAberration:
                                                        typeof value === 'number' ? value : current.chromaticAberration,
                                                }))
                                            }
                                        />
                                    </FlexBlock>
                                    <FlexItem>
                                        <h3>{__('Aperçu', 'supersede-css-jlg')}</h3>
                                        <div className="ssc-ve-preview-box">
                                            <canvas id="ssc-crt-canvas" ref={crtCanvasRef} />
                                        </div>
                                    </FlexItem>
                                </Flex>
                            </CardBody>
                        </Card>
                    );
                }}
            </TabPanel>
        </div>
    );
}

export default VisualEffectsApp;
