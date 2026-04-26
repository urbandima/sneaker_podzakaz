// WCAG 2.1 contrast ratio checker for design tokens
function hexToRgb(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    return [
        parseInt(hex.substring(0, 2), 16),
        parseInt(hex.substring(2, 4), 16),
        parseInt(hex.substring(4, 6), 16),
    ];
}

function linearize(c) {
    c /= 255;
    return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

function luminance(hex) {
    const [r, g, b] = hexToRgb(hex).map(linearize);
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(fg, bg) {
    const L1 = luminance(fg);
    const L2 = luminance(bg);
    const lighter = Math.max(L1, L2);
    const darker = Math.min(L1, L2);
    return (lighter + 0.05) / (darker + 0.05);
}

function grade(ratio, isLargeText = false) {
    const threshold = isLargeText ? 3 : 4.5;
    const aaaa = isLargeText ? 4.5 : 7;
    if (ratio >= aaaa) return 'AAA';
    if (ratio >= threshold) return 'AA';
    return 'FAIL';
}

// --- Light mode tokens ---
const light = {
    'surface-primary':   '#ffffff',
    'surface-secondary': '#f5f5f5',
    'surface-tertiary':  '#e5e5e5',
    'text-primary':      '#000000',
    'text-secondary':    '#666666',
    'text-muted':        '#999999',
    'text-inverse':      '#ffffff',
    'color-black':       '#000000',
    'color-dark-gray':   '#1a1a1a',
    'color-gray':        '#666666',
    'color-accent':      '#8b5cf6',
    'color-gray-500':    '#9e9e9e',
    'color-gray-600':    '#757575',
    'color-gray-300':    '#e0e0e0',
    'color-gray-900':    '#212121',
    'color-gray-400':    '#bdbdbd',
    'footer-bg':         '#1a1a1a',
    'footer-text':       '#ffffff',
    'footer-link':       '#e5e5e5',
};

// --- Dark mode tokens ---
const dark = {
    'surface-primary':   '#0f172a',
    'surface-secondary': '#1e293b',
    'surface-tertiary':  '#334155',
    'text-primary':      '#f8fafc',
    'text-secondary':    '#cbd5e1',
    'text-muted':        '#94a3b8',
    'text-inverse':      '#000000',
    'color-gray-300':    '#e0e0e0',
    'color-gray-400':    '#bdbdbd',
    'color-white':       '#ffffff',
    'color-black':       '#000000',
};

const pairs = [
    // Light mode
    { label: '[light] text-primary on surface-primary',   fg: light['text-primary'],   bg: light['surface-primary'] },
    { label: '[light] text-secondary on surface-primary', fg: light['text-secondary'], bg: light['surface-primary'] },
    { label: '[light] text-muted on surface-primary',     fg: light['text-muted'],     bg: light['surface-primary'] },
    { label: '[light] text-muted on surface-secondary',   fg: light['text-muted'],     bg: light['surface-secondary'] },
    { label: '[light] color-gray-500 on surface-primary', fg: light['color-gray-500'], bg: light['surface-primary'] },
    { label: '[light] color-gray-600 on surface-primary', fg: light['color-gray-600'], bg: light['surface-primary'] },
    { label: '[light] color-accent on surface-primary',   fg: light['color-accent'],   bg: light['surface-primary'] },
    { label: '[light] color-accent on surface-secondary', fg: light['color-accent'],   bg: light['surface-secondary'] },
    { label: '[light] text-inverse on color-black',       fg: light['text-inverse'],   bg: light['color-black'] },
    { label: '[light] footer-text on footer-bg',          fg: light['footer-text'],    bg: light['footer-bg'] },
    { label: '[light] footer-link on footer-bg',          fg: light['footer-link'],    bg: light['footer-bg'] },
    { label: '[light] color-gray-300 on surface-primary', fg: light['color-gray-300'], bg: light['surface-primary'] },
    { label: '[light] color-gray-900 on surface-primary', fg: light['color-gray-900'], bg: light['surface-primary'] },
    { label: '[light] color-gray-400 on surface-primary', fg: light['color-gray-400'], bg: light['surface-primary'] },
    // Dark mode
    { label: '[dark] text-primary on surface-primary',    fg: dark['text-primary'],   bg: dark['surface-primary'] },
    { label: '[dark] text-secondary on surface-primary',  fg: dark['text-secondary'], bg: dark['surface-primary'] },
    { label: '[dark] text-muted on surface-primary',      fg: dark['text-muted'],     bg: dark['surface-primary'] },
    { label: '[dark] text-muted on surface-secondary',    fg: dark['text-muted'],     bg: dark['surface-secondary'] },
    { label: '[dark] text-secondary on surface-secondary',fg: dark['text-secondary'], bg: dark['surface-secondary'] },
    { label: '[dark] text-primary on surface-secondary',  fg: dark['text-primary'],   bg: dark['surface-secondary'] },
    { label: '[dark] text-primary on surface-tertiary',   fg: dark['text-primary'],   bg: dark['surface-tertiary'] },
    { label: '[dark] text-secondary on surface-tertiary', fg: dark['text-secondary'], bg: dark['surface-tertiary'] },
    { label: '[dark] text-muted on surface-tertiary',     fg: dark['text-muted'],     bg: dark['surface-tertiary'] },
    { label: '[dark] color-white on surface-primary',     fg: dark['color-white'],    bg: dark['surface-primary'] },
    { label: '[dark] color-gray-300 on surface-primary',  fg: dark['color-gray-300'], bg: dark['surface-primary'] },
    { label: '[dark] color-gray-400 on surface-primary',  fg: dark['color-gray-400'], bg: dark['surface-primary'] },
    { label: '[dark] color-white on surface-secondary',   fg: dark['color-white'],    bg: dark['surface-secondary'] },
];

let failures = [];
console.log('\nWCAG AA Contrast Check (normal text ≥4.5:1)\n' + '='.repeat(72));
pairs.forEach(({ label, fg, bg }) => {
    const r = contrast(fg, bg).toFixed(2);
    const g = grade(parseFloat(r));
    const icon = g === 'FAIL' ? '❌' : '✅';
    console.log(`${icon} ${label.padEnd(50)} ${fg} on ${bg}  ${r}:1  [${g}]`);
    if (g === 'FAIL') failures.push({ label, fg, bg, ratio: parseFloat(r) });
});

console.log('\n' + '='.repeat(72));
if (failures.length === 0) {
    console.log('All pairs PASS WCAG AA ✅');
} else {
    console.log(`\n${failures.length} FAILURES:\n`);
    failures.forEach(f => {
        console.log(`  ❌ ${f.label}`);
        console.log(`     ${f.fg} on ${f.bg} → ${f.ratio}:1 (need 4.5:1)\n`);
    });
}
