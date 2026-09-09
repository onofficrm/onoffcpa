<?php
/**
 * 매칭된 통화로그 콜디비 전환 생성 (임시)
 * ?action=diagnose|run&token=...&force=1&virtualNumber=050369821405
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
if (!in_array($action, array('run', 'diagnose'), true)) {
    echo json_encode(array('ok' => true, 'message' => 'action=diagnose|run&token=...&force=1'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_call_logs_backfill_conversions')) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'backfill missing'), JSON_UNESCAPED_UNICODE);
    exit;
}

$opts = array(
    'limit' => isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 5000,
    'cpId'  => isset($_REQUEST['cpId']) ? (int) $_REQUEST['cpId'] : 0,
    'mtId'  => isset($_REQUEST['mtId']) ? (int) $_REQUEST['mtId'] : 0,
    'virtualNumber' => isset($_REQUEST['virtualNumber']) ? (string) $_REQUEST['virtualNumber'] : '',
    'force' => !empty($_REQUEST['force']),
    'dryRun' => ($action === 'diagnose'),
);

// diagnose: also report linked counts for the filter
$extra = array();
if ($action === 'diagnose' && lc_db_table_exists(lc_table('call_logs'))) {
    $clog = lc_table('call_logs');
    $where = ' 1=1 ';
    if (!empty($opts['virtualNumber'])) {
        $vn = lc_call_number_normalize($opts['virtualNumber']);
        $where .= " AND clog_virtual_number = '" . lc_sql_escape($vn) . "' ";
    }
    $extra['totals'] = lc_sql_fetch(" SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN cv_id > 0 THEN 1 ELSE 0 END) AS withCv,
        SUM(CASE WHEN cv_id = 0 AND cp_id > 0 AND pt_id > 0 THEN 1 ELSE 0 END) AS matchedNoCv,
        SUM(CASE WHEN clog_result = 'success' THEN 1 ELSE 0 END) AS successCnt,
        SUM(CASE WHEN clog_result = 'missed' THEN 1 ELSE 0 END) AS missedCnt
        FROM `{$clog}` WHERE {$where} ");
}

$result = lc_call_logs_backfill_conversions($opts);
if (!empty($extra)) {
    $result['diagnose'] = $extra;
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
