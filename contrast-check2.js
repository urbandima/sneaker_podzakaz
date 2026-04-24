// WCAG 2.1 contrast ratio checker — from css/core/design-tokens.css
function hexToRgb(hex) {
    hex = hex.replace('#', '');
    return [parseInt(hex.slice(0,2),16), parseInt(hex.slice(2,4),16), parseInt(hex.slice(4,6),16)];
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
    const [L1, L2] = [luminance(fg), luminance(bg)];
    return (Math.max(L1,L2) + 0.05) / (Math.min(L1,L2) + 0.05);
}
function grade(r, large=false) {
    if (r >= (large?4.5:7)) return 'AAA';
    if (r >= (large?3:4.5)) return 'AA';
    return 'FAIL';
}

// === LIGHT MODE === from :root in design-tokens.css
const L = {
    bg:       '#FFFFFF',  // --c-white = --color-bg-primary
    bgSec:    '#F7F7F7',  // --c-gray-50 = --color-bg-secondary
    bgTer:    '#F0F0F0',  // --c-gray-100 = --color-bg-tertiary
    textPri:  '#0F0F0F',  // --c-black = --color-text-primary
    textSec:  '#374151',  // --c-gray-700 = --color-text-secondary
    textMut:  '#6B7280',  // --c-gray-500 = --color-text-muted
    textInv:  '#FFFFFF',  // --c-white = --color-text-inverse
    accent:   '#0F0F0F',  // --c-black = --color-accent (B&W system)
    gray400:  '#9CA3AF',  // --c-gray-400 = --text-disabled
    sale:     '#DC2626',  // --color-sale
    saleBg:   '#FEE2E2',  // --color-sale-bg
    success:  '#16A34A',  // --color-success
    danger:   '#DC2626',  // --color-danger
    warning:  '#D97706',  // --color-warning
    info:     '#0369A1',  // --color-info
};

// === DARK MODE === from [data-theme="dark"] in design-tokens.css
const D = {
    bg:       '#0F0F0F',  // --color-bg-primary
    bgSec:    '#1A1A1A',  // --color-bg-secondary
    bgTer:    '#222222',  // --color-bg-tertiary
    textPri:  '#F7F7F7',  // --color-text-primary
    textSec:  '#BBBBBB',  // --c-gray-700 in dark
    textMut:  '#999999',  // --c-gray-500 in dark
    gray400:  '#777777',  // --c-gray-400 in dark
    accent:   '#F7F7F7',  // --color-accent in dark
};

const pairs = [
    // Light
    { l:'[L] text-primary on bg',         fg:L.textPri,  bg:L.bg },
    { l:'[L] text-secondary on bg',        fg:L.textSec,  bg:L.bg },
    { l:'[L] text-muted on bg',            fg:L.textMut,  bg:L.bg },
    { l:'[L] text-muted on bg-secondary',  fg:L.textMut,  bg:L.bgSec },
    { l:'[L] text-muted on bg-tertiary',   fg:L.textMut,  bg:L.bgTer },
    { l:'[L] gray-400 (disabled) on bg',   fg:L.gray400,  bg:L.bg },
    { l:'[L] sale (#DC2626) on bg',        fg:L.sale,     bg:L.bg },
    { l:'[L] sale on sale-bg',             fg:L.sale,     bg:L.saleBg },
    { l:'[L] success on bg',               fg:L.success,  bg:L.bg },
    { l:'[L] danger on bg',                fg:L.danger,   bg:L.bg },
    { l:'[L] warning on bg',               fg:L.warning,  bg:L.bg },
    { l:'[L] info on bg',                  fg:L.info,     bg:L.bg },
    { l:'[L] text-inverse on text-primary',fg:L.textInv,  bg:L.textPri },
    // Dark
    { l:'[D] text-primary on bg',          fg:D.textPri,  bg:D.bg },
    { l:'[D] text-secondary on bg',        fg:D.textSec,  bg:D.bg },
    { l:'[D] text-muted on bg',            fg:D.textMut,  bg:D.bg },
    { l:'[D] text-muted on bg-secondary',  fg:D.textMut,  bg:D.bgSec },
    { l:'[D] text-muted on bg-tertiary',   fg:D.textMut,  bg:D.bgTer },
    { l:'[D] gray-400 on bg',              fg:D.gray400,  bg:D.bg },
    { l:'[D] gray-400 on bg-secondary',    fg:D.gray400,  bg:D.bgSec },
    { l:'[D] gray-400 on bg-tertiary',     fg:D.gray400,  bg:D.bgTer },
    { l:'[D] accent on bg',                fg:D.accent,   bg:D.bg },
];

let fails = [];
console.log('\nWCAG AA (normal text ≥4.5:1)\n' + '='.repeat(70));
pairs.forEach(({l, fg, bg}) => {
    const r = contrast(fg, bg);
    const g = grade(r);
    const icon = g==='FAIL' ? '❌' : '✅';
    console.log(`${icon} ${l.padEnd(45)} ${fg} / ${bg}  ${r.toFixed(2)}:1 [${g}]`);
    if (g==='FAIL') fails.push({l, fg, bg, r: r.toFixed(2)});
});

console.log('\n' + '='.repeat(70));
if (!fails.length) { console.log('ALL PASS ✅'); }
else {
    console.log(`\n${fails.length} failures:\n`);
    fails.forEach(f => console.log(`  ❌ ${f.l}\n     ${f.fg} / ${f.bg} → ${f.r}:1\n`));
}
