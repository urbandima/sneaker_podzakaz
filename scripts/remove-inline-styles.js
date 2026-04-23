#!/usr/bin/env node
/**
 * Inline style → utility class converter for PHP admin views.
 * Replaces known single-property inline styles with utility classes.
 * Processes line-by-line; skips lines with PHP in style value.
 */

const fs = require('fs');
const path = require('path');

// Map: normalized style value → utility class
const STYLE_MAP = {
  'display:none':                       'd-none',
  'display: none':                      'd-none',
  'text-align:right':                   'text-right',
  'text-align: right':                  'text-right',
  'text-align:center':                  'text-center',
  'text-align: center':                 'text-center',
  'width:100%':                         'w-100',
  'width: 100%':                        'w-100',
  'font-weight:600':                    'fw-600',
  'font-weight: 600':                   'fw-600',
  'padding:0':                          'p-0',
  'padding: 0':                         'p-0',
  'margin:0':                           'm-0',
  'margin: 0':                          'm-0',
  'margin-bottom:24px':                 'mb-5',
  'margin-bottom: 24px':                'mb-5',
  'margin-bottom:1.5rem':               'mb-5',
  'margin-bottom: 1.5rem':              'mb-5',
  'margin-bottom:0.75rem':              'mb-3',
  'margin-bottom: 0.75rem':             'mb-3',
  'margin-top:1.5rem':                  'mt-5',
  'margin-top: 1.5rem':                 'mt-5',
  'margin-top:24px':                    'mt-5',
  'margin-top: 24px':                   'mt-5',
  'margin-top:1rem':                    'mt-4',
  'margin-top: 1rem':                   'mt-4',
  'margin-top:10px':                    'mt-10px',
  'margin-top: 10px':                   'mt-10px',
  'margin-top:8px':                     'mt-2',
  'margin-top: 8px':                    'mt-2',
  'margin-top:0.5rem':                  'mt-2',
  'margin-top: 0.5rem':                 'mt-2',
  'color:var(--admin-text-secondary)':  'text-muted',
  'color: var(--admin-text-secondary)': 'text-muted',
  'color:var(--admin-danger)':          'text-danger',
  'color: var(--admin-danger)':         'text-danger',
  'font-size:0.875rem':                 'fs-sm',
  'font-size: 0.875rem':                'fs-sm',
  'font-size:12px':                     'fs-xs',
  'font-size: 12px':                    'fs-xs',
  'position:relative':                  'pos-relative',
  'position: relative':                 'pos-relative',

  // Multi-property combos → single utility class
  'color:var(--admin-text-secondary);font-size:12px':                                                              'text-muted-sm',
  'color:var(--admin-text-secondary);font-size:0.75rem':                                                          'text-muted-sm',
  'color: var(--admin-text-secondary); font-size: 0.875rem; display: block; margin-bottom: 0.25rem':              'form-hint',
  'display:flex;justify-content:space-between;align-items:center':                                                 'flex-between',
  'display: flex; justify-content: space-between; align-items: center':                                           'flex-between',
  'grid-column: 1 / -1':                                                                                          'grid-full',
  'grid-column:1 / -1':                                                                                           'grid-full',

  // Multi-property combos → multiple utility classes (space-separated)
  'display:flex;align-items:center;gap:8px;cursor:pointer':       'd-flex align-center gap-2 cursor-pointer',
  'display:flex;gap:8px;flex-wrap:wrap':                          'd-flex flex-wrap gap-2',
  'display: flex; align-items: center; gap: 0.5rem':              'd-flex align-center gap-1',
  'display: flex; align-items: center; gap: 0.5rem;':             'd-flex align-center gap-1',
  'text-align: right; font-weight: 600':                          'text-right fw-600',
  'color:var(--admin-accent);font-weight:600':                    'text-accent fw-600',
  'color: var(--admin-success)':                                  'text-success',
  'color: var(--admin-success);':                                 'text-success',
  'margin-top:10px;font-size:13px':                               'mt-10px fs-xs',
  'pointer-events: none':                                         'no-events',
  'pointer-events: none;':                                        'no-events',
  'pointer-events:none':                                          'no-events',
  'overflow-x: auto':                                             'overflow-x-auto',
  'overflow-x: auto;':                                            'overflow-x-auto',
  'overflow-x:auto':                                              'overflow-x-auto',
  'margin-top:12px':                                              'mt-3',
  'margin-top: 12px':                                             'mt-3',
  'margin-top:12px;':                                             'mt-3',
  'margin-top: 12px;':                                            'mt-3',
  'margin-bottom:12px':                                           'mb-3',
  'margin-bottom: 12px':                                          'mb-3',
  'margin-bottom:12px;':                                          'mb-3',
  'margin-bottom: 12px;':                                         'mb-3',
  'font-size:14px':                                               'fs-sm',
  'font-size: 14px':                                              'fs-sm',
  'font-size:13px':                                               'fs-xs',
  'font-size: 13px':                                              'fs-xs',
  'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem':    'grid-2col mb-4',
  'display:flex;align-items:center;gap:0.5rem':                   'd-flex align-center gap-1',
  'display: flex; align-items: center; gap: 1rem':                'd-flex align-center gap-4',
  'margin-top:20px':                                              'mt-20px',
  'margin-top: 20px':                                             'mt-20px',
  'margin-top:20px;':                                             'mt-20px',
  'margin-top: 20px;':                                            'mt-20px',
  'margin-bottom:0.5rem':                                         'mb-2',
  'margin-bottom: 0.5rem':                                        'mb-2',
  'margin-bottom:0.5rem;':                                        'mb-2',
  'margin-bottom: 0.5rem;':                                       'mb-2',
  'cursor:help':                                                  'cursor-help',
  'cursor: help':                                                  'cursor-help',
  'cursor:help;':                                                  'cursor-help',
  'cursor: help;':                                                  'cursor-help',
  'display: flex; flex-direction: column; gap: 1rem':             'flex-col gap-4',
  'display:flex;flex-direction:column;gap:1rem':                  'flex-col gap-4',
  'margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)': 'sub-text',
  'display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem': 'grid-auto-fit-lg',
  'display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem':  'grid-auto-fit-md',
};

function normalize(val) {
  return val.trim().replace(/;+$/, '');
}

/**
 * Given a line of HTML, replace a matched style attribute with a utility class.
 * If an existing class="..." is on the same line, appends to it.
 * Otherwise inserts class="UTIL" before the removed style attr position.
 */
function replaceLine(line, styleMatch, utilityClass) {
  // styleMatch: the full `style="VALUE"` token with any leading space
  // First remove the style attribute
  let newLine = line.replace(styleMatch, '\x00MARK\x00');

  // Try to find existing class=" in the same line
  const classRe = /(class=")([^"]*?)(")/;
  const hasClass = classRe.test(newLine);

  if (hasClass) {
    // Append util class to existing class, remove marker
    newLine = newLine
      .replace(classRe, (_, q1, cls, q3) => q1 + cls.trim() + ' ' + utilityClass + q3)
      .replace(/\s*\x00MARK\x00/, '');
  } else {
    // Replace marker with class="util"
    newLine = newLine.replace(/\s*\x00MARK\x00/, ' class="' + utilityClass + '"');
  }

  return newLine;
}

let totalReplaced = 0;
let filesChanged = 0;

function processFile(filePath) {
  const original = fs.readFileSync(filePath, 'utf8');
  const lines = original.split('\n');
  let fileReplaced = 0;

  const result = lines.map(line => {
    // Skip lines that look like pure PHP (no HTML)
    if (line.trimStart().startsWith('<?php') || line.trimStart().startsWith('//')) {
      return line;
    }

    // Match style="VALUE" — value must not contain PHP interpolation
    const styleRe = /(\s*)style="([^"<>?]*)"/g;
    let m;
    let modified = line;

    while ((m = styleRe.exec(line)) !== null) {
      const fullMatch = m[0];        // e.g. ` style="display: none;"`
      const styleVal = m[2];

      // Skip if PHP inside style value
      if (styleVal.includes('<?') || styleVal.includes('?>')) continue;

      const norm = normalize(styleVal);
      const utilityClass = STYLE_MAP[norm];
      if (!utilityClass) continue;

      modified = replaceLine(modified, fullMatch, utilityClass);
      fileReplaced++;
      totalReplaced++;

      // Reset regex since we modified the string
      break;
    }

    // Handle remaining style attrs on same line if any (rare but possible)
    // Re-run once more for second style on same line
    const styleRe2 = /(\s*)style="([^"<>?]*)"/g;
    let m2;
    while ((m2 = styleRe2.exec(modified)) !== null) {
      const fullMatch = m2[0];
      const styleVal = m2[2];
      if (styleVal.includes('<?') || styleVal.includes('?>')) continue;
      const norm = normalize(styleVal);
      const utilityClass = STYLE_MAP[norm];
      if (!utilityClass) continue;
      modified = replaceLine(modified, fullMatch, utilityClass);
      fileReplaced++;
      totalReplaced++;
      break;
    }

    return modified;
  });

  if (fileReplaced === 0) return;

  const newContent = result.join('\n');
  if (newContent !== original) {
    fs.writeFileSync(filePath, newContent, 'utf8');
    filesChanged++;
    console.log(`  [+${fileReplaced}] ${path.relative(process.cwd(), filePath)}`);
  }
}

function walkDir(dir, extensions) {
  let entries;
  try { entries = fs.readdirSync(dir, { withFileTypes: true }); }
  catch (e) { return; }
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walkDir(fullPath, extensions);
    } else if (extensions.some(ext => entry.name.endsWith(ext))) {
      processFile(fullPath);
    }
  }
}

const args = process.argv.slice(2);
const targetDir = args[0] || path.join(__dirname, '../backend/modules/admin/views');
console.log(`Scanning: ${targetDir}\n`);
walkDir(targetDir, ['.php']);

console.log(`\nTotal: ${totalReplaced} replacements across ${filesChanged} files.`);
