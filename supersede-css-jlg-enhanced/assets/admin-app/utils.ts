export type Token = {
    name: string;
    value: string;
    type: string;
    description: string;
    group: string;
};

export type TokenTypeMap = Record<string, { label: string; input: string } | undefined>;

export type DuplicateResponse = {
    canonicalKeys: string[];
    labels: string[];
} | null;

export function normalizeName(value: string): string {
    if (typeof value !== 'string') {
        return '';
    }
    let name = value.trim();
    if (!name) {
        return '';
    }
    if (!name.startsWith('--')) {
        name = '--' + name.replace(/^-+/, '');
    }
    return name.replace(/[^a-zA-Z0-9_\-]/g, '-');
}

export function canonicalName(value: string): string {
    const normalized = normalizeName(value);
    return normalized ? normalized.toLowerCase() : '';
}

export function generateCss(tokens: Token[]): string {
    if (!Array.isArray(tokens) || tokens.length === 0) {
        return ':root {\n}\n';
    }
    const lines = tokens
        .filter((token) => token && typeof token.name === 'string' && token.name.trim() !== '')
        .map((token) => `    ${token.name}: ${token.value};`);
    return `:root {\n${lines.join('\n')}\n}`;
}

export function calculateDuplicateCanonical(tokens: Token[]): string[] {
    const seen: Record<string, number> = Object.create(null);
    const duplicates: string[] = [];
    tokens.forEach((token) => {
        if (!token || typeof token !== 'object') {
            return;
        }
        const canonical = canonicalName(token.name);
        if (!canonical) {
            return;
        }
        const rawValue = token.value == null ? '' : String(token.value).trim();
        if (!rawValue) {
            return;
        }
        if (seen[canonical]) {
            if (!duplicates.includes(canonical)) {
                duplicates.push(canonical);
            }
        } else {
            seen[canonical] = 1;
        }
    });
    return duplicates;
}

export function buildDuplicateLabels(tokens: Token[], canonicalKeys: string[]): string[] {
    if (!Array.isArray(canonicalKeys) || !canonicalKeys.length) {
        return [];
    }
    const canonicalSet = new Set(
        canonicalKeys
            .map((key) => (typeof key === 'string' ? key.toLowerCase() : ''))
            .filter((key) => key !== '')
    );
    if (!canonicalSet.size) {
        return [];
    }
    const labels: string[] = [];
    tokens.forEach((token) => {
        if (!token || typeof token.name !== 'string') {
            return;
        }
        const normalized = normalizeName(token.name);
        const canonical = normalized ? normalized.toLowerCase() : '';
        if (!canonical || !canonicalSet.has(canonical)) {
            return;
        }
        if (normalized && !labels.includes(normalized)) {
            labels.push(normalized);
        }
    });
    return labels;
}

export function parseServerDuplicateResponse(response: unknown): DuplicateResponse {
    if (
        !response ||
        typeof response !== 'object' ||
        !Array.isArray((response as { duplicates?: unknown }).duplicates) ||
        !(response as { duplicates: unknown[] }).duplicates.length
    ) {
        return null;
    }

    const canonicalKeys: string[] = [];
    const labels: string[] = [];

    (response as { duplicates: Array<Record<string, unknown>> }).duplicates.forEach((item) => {
        if (!item || typeof item !== 'object') {
            return;
        }

        const rawCanonical = typeof item.canonical === 'string' ? item.canonical : '';
        const canonical = rawCanonical ? rawCanonical.toLowerCase() : '';
        if (canonical && !canonicalKeys.includes(canonical)) {
            canonicalKeys.push(canonical);
        }

        const registerLabel = (value: unknown) => {
            if (typeof value !== 'string') {
                return;
            }
            const trimmed = value.trim();
            if (trimmed !== '' && !labels.includes(trimmed)) {
                labels.push(trimmed);
            }
        };

        if (Array.isArray(item.variants)) {
            item.variants.forEach(registerLabel);
        }

        if (Array.isArray(item.conflicts)) {
            item.conflicts.forEach((conflict) => {
                if (!conflict || typeof conflict !== 'object') {
                    return;
                }
                registerLabel((conflict as { name?: unknown }).name);
            });
        } else if (typeof rawCanonical === 'string') {
            registerLabel(rawCanonical);
        }
    });

    if (!canonicalKeys.length && !labels.length) {
        return null;
    }

    return { canonicalKeys, labels };
}

export async function copyToClipboard(text: string): Promise<void> {
    if (typeof navigator !== 'undefined' && navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('SSC clipboard copy failed', error);
    }
    document.body.removeChild(textArea);
}

export function defaultToken(types: TokenTypeMap | undefined): Token {
    const hasColor = !!types?.color;
    return {
        name: '--nouveau-token',
        value: hasColor ? '#ffffff' : '',
        type: hasColor ? 'color' : 'text',
        description: '',
        group: 'Général',
    };
}

export function getGroupName(raw: string | undefined): string {
    const trimmed = (raw ?? '').trim();
    return trimmed === '' ? 'Général' : trimmed;
}
