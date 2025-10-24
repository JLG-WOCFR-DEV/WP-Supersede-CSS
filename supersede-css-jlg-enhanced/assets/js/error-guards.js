(function() {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }

    if (window.__sscErrorGuardsInitialized) {
        return;
    }

    window.__sscErrorGuardsInitialized = true;

    const suppressedMessages = [
        'a listener indicated an asynchronous response by returning true, but the message channel closed before a response was received',
    ];

    const normalizedPatterns = suppressedMessages
        .map((pattern) => (typeof pattern === 'string' ? pattern.toLowerCase() : ''))
        .filter((pattern) => pattern.length > 0);

    const matchesSuppressedMessage = (message) => {
        if (typeof message !== 'string' || message.trim() === '') {
            return false;
        }

        const normalized = message.toLowerCase();
        return normalizedPatterns.some((pattern) => normalized.indexOf(pattern) !== -1);
    };

    const extractReasonMessage = (reason) => {
        if (!reason) {
            return '';
        }

        if (typeof reason === 'string') {
            return reason;
        }

        if (typeof reason.message === 'string') {
            return reason.message;
        }

        return '';
    };

    const handleSuppressedEvent = (event, message) => {
        if (!matchesSuppressedMessage(message)) {
            return false;
        }

        if (typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        } else if (typeof event.stopPropagation === 'function') {
            event.stopPropagation();
        }

        const consoleObject = window.console || {};
        const logger = consoleObject.debug || consoleObject.info || consoleObject.log;
        if (typeof logger === 'function') {
            logger.call(consoleObject, '[Supersede CSS] Ignored browser warning:', message);
        }

        return true;
    };

    window.addEventListener('unhandledrejection', (event) => {
        if (!event) {
            return;
        }

        const message = extractReasonMessage(event.reason);
        handleSuppressedEvent(event, message);
    });

    window.addEventListener('error', (event) => {
        if (!event) {
            return;
        }

        const message = typeof event.message === 'string' ? event.message : '';
        handleSuppressedEvent(event, message);
    }, true);
})();

