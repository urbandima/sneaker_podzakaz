<?php

/**
 * CMP-413: строит полный реестр HTTP-маршрутов (контроллеры + public action*)
 * для frontend/backend-модулей/api. Console-контроллеры (CLI, не HTTP) исключены.
 *
 * Использование: php scripts/route-inventory.php > docs/route-audit/CMP-413-inventory.csv
 */

$root = dirname(__DIR__);
$scanDirs = [
    $root . '/frontend/controllers',
    $root . '/backend/modules',
    $root . '/api/controllers',
];

$urlRulesFile = $root . '/infrastructure/config/web.php';

function kebab(string $s): string
{
    $s = preg_replace('/(?<!^)[A-Z]/', '-$0', $s);
    return strtolower($s);
}

/** @return array<string,string> route-pattern => controller/action id (explicit rules only) */
function loadExplicitRules(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    // extract only the literal 'rules' => [ ... ] array without executing app bootstrap.
    $contents = file_get_contents($file);
    if (!preg_match('/[\'"]rules[\'"]\s*=>\s*\[(.*?)\n\s*\],\n\s*\],\n\s*[\'"]authManager/s', $contents, $m)) {
        return [];
    }
    $body = $m[1];
    $rules = [];
    // match 'lhs' => 'rhs' pairs (skip ones with array rhs like <controller:...>)
    if (preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $pair) {
            $rules[$pair[2]] = $pair[1]; // controller/action id => url pattern
        }
    }
    return $rules;
}

$explicitRules = loadExplicitRules($urlRulesFile);

function findControllers(array $dirs): array
{
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), 'Controller.php')) {
                $files[] = $f->getPathname();
            }
        }
    }
    sort($files);
    return $files;
}

function moduleForPath(string $path, string $root): string
{
    $rel = str_replace($root . '/', '', $path);
    if (str_starts_with($rel, 'frontend/controllers/')) {
        return '(default)';
    }
    if (str_starts_with($rel, 'api/controllers/')) {
        return 'api';
    }
    if (preg_match('#^backend/modules/([a-zA-Z0-9_]+)/#', $rel, $m)) {
        // returns/Module alias check
        $map = [
            'returns' => 'returns',
            'compare' => 'compare',
        ];
        return $map[$m[1]] ?? $m[1];
    }
    return '?';
}

$controllers = findControllers($scanDirs);

$rows = [];
foreach ($controllers as $path) {
    $rel = str_replace($root . '/', '', $path);
    $module = moduleForPath($path, $root);
    $basename = basename($path, '.php'); // e.g. CatalogController
    $controllerId = kebab(str_replace('Controller', '', $basename));

    $src = file_get_contents($path);

    // Skip abstract/base controllers with no route (never instantiated directly by URL manager)
    $isAbstract = (bool) preg_match('/\babstract\s+class\b/', $src);

    if (!preg_match_all('/^\s*(?:public\s+)?function\s+action([A-Za-z0-9_]+)\s*\(/m', $src, $m)) {
        continue;
    }

    foreach ($m[1] as $actionMethod) {
        $actionId = kebab($actionMethod);
        $ctrlAction = ($module === '(default)' ? '' : $module . '/') . $controllerId . '/' . $actionId;
        $prettyUrl = $explicitRules[$ctrlAction] ?? null;
        $guessedUrl = '/' . $ctrlAction;
        $rows[] = [
            'module' => $module,
            'controller_file' => $rel,
            'controller_id' => $controllerId,
            'action' => $actionId,
            'internal_route' => $ctrlAction,
            'url' => $prettyUrl ?? $guessedUrl,
            'url_source' => $prettyUrl ? 'explicit_rule' : 'default_pattern_guess',
            'abstract_base' => $isAbstract ? 'yes' : 'no',
        ];
    }
}

// CSV output
$out = fopen('php://stdout', 'w');
fputcsv($out, ['module', 'controller_file', 'controller_id', 'action', 'internal_route', 'url', 'url_source', 'abstract_base', 'method', 'status_before', 'status_after', 'notes'], ',', '"', '\\');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['module'], $r['controller_file'], $r['controller_id'], $r['action'],
        $r['internal_route'], $r['url'], $r['url_source'], $r['abstract_base'],
        '', '', '', '',
    ], ',', '"', '\\');
}
fclose($out);

fwrite(STDERR, "Всего контроллеров: " . count($controllers) . "\n");
fwrite(STDERR, "Всего action-методов: " . count($rows) . "\n");
