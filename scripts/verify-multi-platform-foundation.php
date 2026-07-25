<?php
/**
 * Live verification helpers for multi-platform foundation (CLI only).
 * Does not enable the feature and does not touch remote LinkConnect.
 *
 * Usage:
 *   php scripts/verify-multi-platform-foundation.php
 */
if (!defined('_GNUBOARD_')) {
    define('_GNUBOARD_', true);
}
if (!defined('LC_MULTI_PLATFORM_ENABLED')) {
    define('LC_MULTI_PLATFORM_ENABLED', false);
}
if (!defined('LC_PLATFORM_CODE')) {
    define('LC_PLATFORM_CODE', 'ONOFFCPA');
}
if (!defined('LC_PLATFORM_LINKCONNECT')) {
    define('LC_PLATFORM_LINKCONNECT', 'LINKCONNECT');
}

$root = dirname(__DIR__);
require_once $root . '/plugin/linkconnect/inc/platform.php';
require_once $root . '/plugin/linkconnect/inc/platform_db.php';
require_once $root . '/plugin/linkconnect/inc/platform_policy.php';

$checks = array();
$checks['flag_off'] = (LC_MULTI_PLATFORM_ENABLED === false);
$checks['mp_enabled_false'] = (lc_mp_enabled() === false);
$checks['local_mgmt_passthrough'] = (lc_mp_local_is_management_for_mt(999999) === true);
$schema = lc_mp_db_ensure_schema();
$checks['schema_skipped'] = (!empty($schema['ok']) && empty($schema['created']));
$checks['platform_code'] = (lc_mp_local_platform_code() === 'ONOFFCPA');

$failed = array();
foreach ($checks as $k => $ok) {
    if (!$ok) {
        $failed[] = $k;
    }
}

echo json_encode(array(
    'ok' => count($failed) === 0,
    'checks' => $checks,
    'failed' => $failed,
    'schema' => $schema,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(count($failed) === 0 ? 0 : 1);
