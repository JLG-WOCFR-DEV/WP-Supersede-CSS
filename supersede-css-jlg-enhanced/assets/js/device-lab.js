(function() {
    'use strict';

    function sprintf(template, ...args) {
        if (typeof template !== 'string') {
            return '';
        }

        let index = 0;
        return template.replace(/%s/g, function() {
            const value = index < args.length ? args[index] : '';
            index += 1;
            return String(value);
        });
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function init() {
        const data = window.SSC_DEVICE_LAB || {};
        const devices = Array.isArray(data.devices) ? data.devices : [];
        const i18n = data.i18n || {};
        const defaultDevice = data.defaultDevice || (devices[0] ? devices[0].id : '');
        const defaultOrientation = data.defaultOrientation === 'landscape' ? 'landscape' : 'portrait';
        const defaultZoom = Number.isFinite(Number(data.defaultZoom)) ? Number(data.defaultZoom) : 100;
        const defaultUrl = typeof data.defaultUrl === 'string' ? data.defaultUrl : '';
        const inlineCss = typeof data.inlineCss === 'string' ? data.inlineCss : '';

        const deviceSelect = document.getElementById('ssc-device-lab-device');
        const orientationButtons = Array.prototype.slice.call(document.querySelectorAll('[data-orientation]'));
        const zoomInput = document.getElementById('ssc-device-lab-zoom');
        const zoomDisplay = document.getElementById('ssc-device-lab-zoom-display');
        const urlForm = document.getElementById('ssc-device-lab-url-form');
        const urlInput = document.getElementById('ssc-device-lab-url');
        const viewport = document.getElementById('ssc-device-lab-viewport');
        const viewportWrapper = document.getElementById('ssc-device-lab-viewport-wrapper');
        const frame = document.getElementById('ssc-device-lab-frame');
        const status = document.getElementById('ssc-device-lab-status');
        const liveRegion = document.getElementById('ssc-device-lab-live');
        const rotationHelpId = 'ssc-device-lab-rotation-help';

        if (!deviceSelect || !viewport || !viewportWrapper || !frame) {
            return;
        }

        let currentDeviceId = deviceSelect.value || defaultDevice;
        let currentOrientation = defaultOrientation;
        let currentZoom = clamp(Math.round(defaultZoom), 50, 150);
        let currentUrl = defaultUrl;
        let lastCssAnnouncement = '';

        function announce(message) {
            if (!liveRegion || typeof message !== 'string' || message.trim() === '') {
                return;
            }

            liveRegion.textContent = '';
            window.setTimeout(function() {
                liveRegion.textContent = message;
            }, 50);
        }

        function findDevice(deviceId) {
            return devices.find(function(device) {
                return device && device.id === deviceId;
            }) || null;
        }

        function formatDeviceDetails(device, width, height) {
            if (!device) {
                return '';
            }

            const ratio = Number.isFinite(Number(device.pixelRatio)) ? Number(device.pixelRatio) : 1;
            const category = typeof device.category === 'string' ? device.category : '';
            const fallbackCategory = category ? category.charAt(0).toUpperCase() + category.slice(1) : '';
            const categoryLabel = typeof device.categoryLabel === 'string' ? device.categoryLabel : fallbackCategory;
            const ratioLabel = ratio.toFixed(1).replace(/\.0$/, '');
            const summaryTemplate = typeof i18n.deviceSummary === 'string'
                ? i18n.deviceSummary
                : '%s — %s × %s px — DPR %s%s';
            const categoryTemplate = typeof i18n.deviceCategorySuffix === 'string'
                ? i18n.deviceCategorySuffix
                : ' — %s';
            const categorySuffix = categoryLabel ? sprintf(categoryTemplate, categoryLabel) : '';

            return sprintf(
                summaryTemplate,
                device.label || '',
                width,
                height,
                ratioLabel,
                categorySuffix
            );
        }

        function updateStatus(device, width, height) {
            if (!status) {
                return;
            }

            status.textContent = formatDeviceDetails(device, width, height);
        }

        function updateOrientationButtons(device, orientation) {
            orientationButtons.forEach(function(button) {
                const targetOrientation = button.getAttribute('data-orientation');
                const isActive = targetOrientation === orientation;
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                if (targetOrientation === 'landscape') {
                    const rotatable = Boolean(device && device.rotatable);
                    button.disabled = !rotatable;
                    if (!rotatable) {
                        button.setAttribute('aria-describedby', rotationHelpId);
                    } else {
                        button.removeAttribute('aria-describedby');
                    }
                }
            });
        }

        function applyDevice(device, announceSelection) {
            if (!device) {
                return;
            }

            let orientation = currentOrientation;
            if (orientation === 'landscape' && !device.rotatable) {
                orientation = 'portrait';
                currentOrientation = 'portrait';
                announce(i18n.orientationLocked || 'Rotation non disponible pour cet appareil.');
            }

            const width = orientation === 'landscape' ? device.height : device.width;
            const height = orientation === 'landscape' ? device.width : device.height;
            const normalizedWidth = Math.max(Number(width) || 0, 1);
            const normalizedHeight = Math.max(Number(height) || 0, 1);

            viewport.style.width = normalizedWidth + 'px';
            viewport.style.height = normalizedHeight + 'px';
            viewport.setAttribute('data-width', String(normalizedWidth));
            viewport.setAttribute('data-height', String(normalizedHeight));
            viewport.setAttribute('data-orientation', orientation);
            viewport.setAttribute('data-device', device.id || '');

            frame.setAttribute('width', String(normalizedWidth));
            frame.setAttribute('height', String(normalizedHeight));

            updateOrientationButtons(device, orientation);
            updateStatus(device, normalizedWidth, normalizedHeight);

            if (announceSelection) {
                announce(sprintf(i18n.deviceSelected || 'Appareil sélectionné : %s', device.label || ''));
            }
        }

        function applyZoom(zoomValue, shouldAnnounce) {
            const value = clamp(Math.round(zoomValue), 50, 150);
            currentZoom = value;
            viewportWrapper.style.transform = 'scale(' + (value / 100).toFixed(2) + ')';
            viewportWrapper.setAttribute('data-zoom', String(value));
            if (zoomDisplay) {
                zoomDisplay.textContent = value + '%';
            }

            if (shouldAnnounce) {
                announce(sprintf(i18n.zoomAnnouncement || 'Zoom défini sur %s %%', value));
            }
        }

        function injectCssIntoFrame() {
            if (!inlineCss) {
                return;
            }

            try {
                const doc = frame.contentDocument;
                if (!doc) {
                    return;
                }

                const head = doc.head || doc.getElementsByTagName('head')[0] || doc.documentElement;
                if (!head) {
                    return;
                }

                let style = doc.getElementById('ssc-device-lab-style');
                if (!style) {
                    style = doc.createElement('style');
                    style.id = 'ssc-device-lab-style';
                    style.type = 'text/css';
                    head.appendChild(style);
                }

                style.textContent = '/* Supersede CSS Device Lab */\n' + inlineCss;
                if (lastCssAnnouncement !== 'success') {
                    announce(i18n.cssApplied || 'CSS Supersede injecté dans la prévisualisation.');
                    lastCssAnnouncement = 'success';
                }
            } catch (error) {
                if (lastCssAnnouncement !== 'error') {
                    announce(i18n.cssFailed || 'Impossible d’injecter le CSS Supersede pour cette URL.');
                    lastCssAnnouncement = 'error';
                }
            }
        }

        function loadPreview(url, shouldAnnounce) {
            if (!url) {
                frame.removeAttribute('src');
                frame.srcdoc = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Device Lab</title></head><body><main style="display:flex;align-items:center;justify-content:center;height:100%;font-family:system-ui, sans-serif;background:#f8fafc;color:#0f172a;"><div><h1 style="font-size:1.5rem;margin-bottom:0.5rem;">Supersede Device Lab</h1><p style="max-width:28rem;line-height:1.6;">Chargez une URL pour visualiser votre site. Les styles Supersede actifs sont injectés automatiquement.</p></div></main></body></html>';
                currentUrl = '';
                lastCssAnnouncement = '';
                injectCssIntoFrame();
                return;
            }

            frame.removeAttribute('srcdoc');
            frame.setAttribute('src', url);
            frame.dataset.previewUrl = url;
            currentUrl = url;
            lastCssAnnouncement = '';

            if (shouldAnnounce) {
                announce(sprintf(i18n.urlLoaded || 'URL chargée : %s', url));
            }
        }

        function normalizeUrl(rawUrl) {
            if (typeof rawUrl !== 'string' || rawUrl.trim() === '') {
                return null;
            }

            try {
                return new URL(rawUrl.trim(), window.location.origin).toString();
            } catch (error) {
                return null;
            }
        }

        deviceSelect.addEventListener('change', function() {
            currentDeviceId = deviceSelect.value;
            const device = findDevice(currentDeviceId) || findDevice(defaultDevice);
            if (device) {
                applyDevice(device, true);
            }
        });

        orientationButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const orientation = button.getAttribute('data-orientation');
                if (!orientation || (orientation !== 'portrait' && orientation !== 'landscape')) {
                    return;
                }

                const device = findDevice(currentDeviceId);
                if (!device) {
                    return;
                }

                if (orientation === 'landscape' && !device.rotatable) {
                    announce(i18n.orientationLocked || 'Rotation non disponible pour cet appareil.');
                    return;
                }

                currentOrientation = orientation;
                applyDevice(device, false);
                if (orientation === 'landscape') {
                    announce(i18n.orientationLandscape || 'Orientation paysage');
                } else {
                    announce(i18n.orientationPortrait || 'Orientation portrait');
                }
            });
        });

        if (zoomInput) {
            zoomInput.value = String(currentZoom);
            zoomInput.addEventListener('input', function() {
                applyZoom(Number(zoomInput.value), false);
            });
            zoomInput.addEventListener('change', function() {
                applyZoom(Number(zoomInput.value), true);
            });
        }

        if (urlForm && urlInput) {
            urlForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const normalized = normalizeUrl(urlInput.value);
                if (!normalized) {
                    announce(i18n.urlInvalid || 'URL invalide. Veuillez saisir une adresse complète.');
                    return;
                }

                urlInput.value = normalized;
                loadPreview(normalized, true);
            });
        }

        frame.addEventListener('load', injectCssIntoFrame);

        const initialDevice = findDevice(currentDeviceId) || findDevice(defaultDevice) || devices[0] || null;
        if (initialDevice) {
            if (deviceSelect.value !== initialDevice.id) {
                deviceSelect.value = initialDevice.id;
            }
            currentDeviceId = initialDevice.id;
            applyDevice(initialDevice, false);
        }

        applyZoom(currentZoom, false);

        if (urlInput) {
            urlInput.value = currentUrl || defaultUrl || urlInput.value;
        }

        if (currentUrl) {
            const normalizedInitialUrl = normalizeUrl(currentUrl);
            if (normalizedInitialUrl) {
                loadPreview(normalizedInitialUrl, false);
            } else if (defaultUrl) {
                loadPreview(defaultUrl, false);
            } else {
                loadPreview('', false);
            }
        } else if (defaultUrl) {
            const normalizedDefault = normalizeUrl(defaultUrl);
            if (normalizedDefault) {
                loadPreview(normalizedDefault, false);
            } else {
                loadPreview('', false);
            }
        } else {
            loadPreview('', false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
