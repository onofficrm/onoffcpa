<?php
/**
 * 부재중 콜디비 과금 정책 적용 + 미생성 건 백필 (임시)
 * ?action=run&token=...
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');

$expected = 'callbackfill-8a1c3e5f7b9204d6';
$given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
$admin_ok = function_exists('lc_is_super_admin') && lc_is_super_admin();
$token_ok = ($given !== '' && hash_equals($expected, $given));
if (!$token_ok && !$admin_ok) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'FORBIDDEN'), JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'run') {
    echo json_encode(array('ok' => true, 'message' => 'action=run&token=...'), JSON_UNESCAPED_UNICODE);
    exit;
}

$setting = null;
if (function_exists('lc_settings_save')) {
    $setting = lc_settings_save(array('callCreateOnMissed' => '1'));
} elseif (function_exists('lc_settings_save_row')) {
    $setting = array('ok' => lc_settings_save_row('callCreateOnMissed', '1'));
}

$backfill = array('ok' => false, 'message' => 'backfill missing');
if (function_exists('lc_call_logs_backfill_conversions')) {
    $backfill = lc_call_logs_backfill_conversions(array(
        'limit' => isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 5000,
        'force' => true,
    ));
}

echo json_encode(array(
    'ok' => !empty($backfill['ok']),
    'setting' => $setting,
    'callCreateOnMissed' => function_exists('lc_settings_get_bool') ? lc_settings_get_bool('callCreateOnMissed', true) : null,
    'backfill' => $backfill,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
