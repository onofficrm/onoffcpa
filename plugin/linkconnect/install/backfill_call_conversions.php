<?php
/**
 * 매칭된 통화로그에 콜디비 전환 일회 생성
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

if (!function_exists('lc_call_logs_backfill_conversions')) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'backfill missing'), JSON_UNESCAPED_UNICODE);
    exit;
}

$result = lc_call_logs_backfill_conversions(array(
    'limit' => isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 5000,
    'cpId'  => isset($_REQUEST['cpId']) ? (int) $_REQUEST['cpId'] : 0,
    'mtId'  => isset($_REQUEST['mtId']) ? (int) $_REQUEST['mtId'] : 0,
));
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
