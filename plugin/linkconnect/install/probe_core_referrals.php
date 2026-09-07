<?php
/**
 * Debug Core member_referral rows for E2E customers.
 * ?action=run&customer=e2e_traffic_20260906a
 */
require_once dirname(__DIR__) . '/_common.php';

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'run') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "usage: ?action=run&customer=...\n";
    exit;
}

$customer = trim((string) ($_REQUEST['customer'] ?? ''));
$customers = $customer !== ''
    ? array($customer)
    : array('e2e_seogeo_20260906a', 'e2e_traffic_20260906a', 'e2e_backlink_20260906a');

lc_onoff_core_ensure_ready();
$tbl = class_exists('OnoffCore_ReferralSchema', false)
    ? OnoffCore_ReferralSchema::tableMemberReferral()
    : '(no schema)';

$rows = array();
foreach ($customers as $c) {
    $esc = function_exists('sql_escape_string') ? sql_escape_string($c) : addslashes($c);
    $row = function_exists('sql_fetch')
        ? sql_fetch("SELECT * FROM `{$tbl}` WHERE customer_mb_id = '{$esc}' LIMIT 1", false)
        : null;
    $via = null;
    if (function_exists('lc_onoff_core_referral_service')) {
        $svc = lc_onoff_core_referral_service();
        if ($svc) {
            $via = $svc->getMemberReferral($c);
        }
    }
    $rows[] = array(
        'customer' => $c,
        'table'    => $tbl,
        'sqlRow'   => $row,
        'svc'      => $via,
    );
}

// also last 10 referrals
$recent = array();
if (function_exists('sql_query')) {
    $res = sql_query("SELECT customer_mb_id, referrer_mb_id, referral_code, campaign_id, status, source_service, registered_at FROM `{$tbl}` ORDER BY registered_at DESC LIMIT 10", false);
    if ($res) {
        while ($r = sql_fetch_array($res)) {
            $recent[] = $r;
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'ok' => true,
    'table' => $tbl,
    'rows' => $rows,
    'recent' => $recent,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
