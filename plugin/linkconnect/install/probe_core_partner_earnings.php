<?php
/**
 * Read-only Core earnings probe for partner pt_id=1 (admin).
 * Web: ?action=run
 */
require_once dirname(__DIR__) . '/_common.php';

$is_cli = (php_sapi_name() === 'cli');
$running_as_script = (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__))
    || ($is_cli && isset($_SERVER['argv'][0]) && realpath($_SERVER['argv'][0]) === realpath(__FILE__));

if (!$running_as_script) {
    return;
}

if (!$is_cli) {
    $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
    if ($action !== 'run') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "usage: ?action=run\n";
        exit;
    }
}

$pt_id = isset($_REQUEST['ptId']) ? (int) $_REQUEST['ptId'] : 1;
$dash = function_exists('lc_onoff_core_partner_dashboard')
    ? lc_onoff_core_partner_dashboard($pt_id)
    : null;
$earn = function_exists('lc_onoff_core_partner_earnings')
    ? lc_onoff_core_partner_earnings($pt_id)
    : null;

$out = array(
    'ok' => true,
    'ptId' => $pt_id,
    'dashboard' => $dash,
    'earnings' => $earn,
);

if ($is_cli) {
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
