<?php
/**
 * 빌더 홈 적용 점검 (CLI)
 * php scripts/verify-builder-home.php
 */
error_reporting(E_ALL & ~E_DEPRECATED);

$root = dirname(__DIR__);
define('_GNUBOARD_', true);
define('G5_PATH', $root);
define('G5_PLUGIN_PATH', $root . '/plugin');
define('G5_PLUGIN_URL', '/plugin');
define('G5_URL', 'https://example.com');
define('G5_IS_MOBILE', false);

include_once G5_PATH . '/_site.config.php';
include_once G5_PLUGIN_PATH . '/onoff-builder-bridge/bootstrap.php';

$projectId = 'linkconnect';
$fail = 0;

function check($label, $ok)
{
    global $fail;
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

check('home_builder_bridge_id', g5site_cfg('home_builder_bridge_id', '') === $projectId);
check('project_exists', onoff_builder_project_exists($projectId));
check('import_registered', onoff_builder_has_import($projectId));
check('import_via_get', onoff_builder_get_import($projectId) !== null);
check('import_enabled', !empty(onoff_builder_get_import($projectId)['enabled']));
check('home_enabled', onoff_builder_home_enabled());

$dir = onoff_builder_project_dir($projectId);
check('index.html', is_file($dir . '/index.html'));
check('assets/js', (bool) glob($dir . '/assets/*.js'));
check('assets/css', (bool) glob($dir . '/assets/*.css'));

$index = @file_get_contents($dir . '/index.html');
check('index_has_root', $index !== false && strpos($index, 'id="root"') !== false);
check('index_title', $index !== false && strpos($index, '<title>LinkConnect</title>') !== false);

$rewritten = onoff_builder_rewrite_asset_paths($index, $projectId, 'index.html');
check('asset_path_rewrite', strpos($rewritten, '/plugin/onoff-builder-bridge/imports/linkconnect/assets/') !== false);

$app = @file_get_contents($root . '/builder/linkconnect_source/src/App.tsx');
check('browser_router', $app !== false && strpos($app, 'BrowserRouter') !== false);

$spa_stub_dirs = array('partner', 'advertiser', 'admin', 'select-center', 'cpa-list', 'cps', 'events');
foreach ($spa_stub_dirs as $dir) {
    check('spa_stub_' . $dir, is_file($root . '/' . $dir . '/index.php') && is_file($root . '/' . $dir . '/.htaccess'));
}

$htaccess = @file_get_contents($root . '/.htaccess');
check('htaccess_spa_rule', $htaccess !== false && strpos($htaccess, 'partner|advertiser|admin') !== false);
check('extend_spa_rewrite', is_file($root . '/extend/linkconnect-spa.extend.php'));

$functions = @file_get_contents($root . '/plugin/onoff-builder-bridge/lib/functions.php');
check('auth_bootstrap_include', $functions !== false && strpos($functions, "include_once \$lc_auth") !== false);

exit($fail > 0 ? 1 : 0);
