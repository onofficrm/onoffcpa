<?php
/**
 * One-shot: ensure Core referral tables + global 10% rule + sample partner code.
 *
 * Usage (admin session or CLI with G5 bootstrap):
 *   https://onoffcpa.icrm.co.kr/plugin/linkconnect/install/apply_onoff_core_referral.php
 */
require_once dirname(__DIR__) . '/_common.php';

if (PHP_SAPI !== 'cli') {
    if (!function_exists('lc_require_admin') && !function_exists('is_admin')) {
        // fall through — _common may expose admin helpers
    }
    $is_admin = false;
    if (function_exists('lc_is_admin')) {
        $is_admin = (bool) lc_is_admin();
    } elseif (isset($is_admin) && $is_admin) {
        $is_admin = true;
    } elseif (function_exists('is_admin') && isset($member['mb_id'])) {
        $is_admin = (bool) is_admin($member['mb_id']);
    }
    // Allow token for ops install
    $token = isset($_GET['token']) ? (string) $_GET['token'] : '';
    $expect = getenv('LC_CORE_INSTALL_TOKEN');
    if (!$is_admin && (!is_string($expect) || $expect === '' || !hash_equals($expect, $token))) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(array('ok' => false, 'error' => 'FORBIDDEN'), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

$ready = lc_onoff_core_ensure_ready();

$seed_code = null;
$camp = function_exists('lc_sql_fetch')
    ? lc_sql_fetch("SELECT * FROM `" . lc_table('campaigns') . "` WHERE cp_code='CPS-DOMAIN' LIMIT 1")
    : null;
$pt = function_exists('lc_sql_fetch')
    ? lc_sql_fetch("SELECT * FROM `" . lc_table('partners') . "` WHERE pt_status='active' ORDER BY pt_id ASC LIMIT 1")
    : null;

if ($camp && $pt && !empty($ready['tables'])) {
    $seed_code = lc_onoff_core_get_or_create_referral_code((int) $pt['pt_id'], (int) $camp['cp_id'], $camp);
}

echo json_encode(array(
    'ok'       => !empty($ready['ok']),
    'ready'    => $ready,
    'seedCode' => $seed_code,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
