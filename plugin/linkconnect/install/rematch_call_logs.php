<?php
/**
 * 미매칭 통화로그 → 가상번호 배정 재매칭 (일회)
 *
 * /plugin/linkconnect/install/rematch_call_logs.php?action=run&token=...
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');

$expected_token = 'callrematch-9f3c2a1b7e84d056';
$given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
$token_ok = ($given !== '' && hash_equals($expected_token, $given));
$admin_ok = function_exists('lc_is_super_admin') && lc_is_super_admin();
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

$diag = array(
    'assignedRequests' => 0,
    'unmatchedLogs' => 0,
    'poolNumbers' => 0,
    'unmatchedVirtuals' => array(),
);

if (lc_db_table_exists(lc_table('call_requests'))) {
    $r = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `" . lc_table('call_requests') . "` WHERE car_status = 'assigned' ");
    $diag['assignedRequests'] = (int) ($r['cnt'] ?? 0);
}
if (lc_db_table_exists(lc_table('call_logs'))) {
    $r = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `" . lc_table('call_logs') . "` WHERE pt_id = '0' ");
    $diag['unmatchedLogs'] = (int) ($r['cnt'] ?? 0);
    $res = lc_sql_query(" SELECT clog_virtual_number, COUNT(*) AS cnt FROM `" . lc_table('call_logs') . "`
        WHERE pt_id = '0' AND clog_virtual_number <> ''
        GROUP BY clog_virtual_number ORDER BY cnt DESC LIMIT 40 ", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            $vn = lc_call_number_normalize((string) ($row['clog_virtual_number'] ?? ''));
            $assigned = lc_call_assignment_by_number($vn);
            $diag['unmatchedVirtuals'][] = array(
                'virtualNumber' => $vn,
                'logs' => (int) ($row['cnt'] ?? 0),
                'hasAssignment' => $assigned ? true : false,
                'ptId' => $assigned ? (int) $assigned['pt_id'] : 0,
                'cpId' => $assigned ? (int) $assigned['cp_id'] : 0,
            );
        }
    }
}
if (lc_db_table_exists(lc_table('call_numbers'))) {
    $r = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `" . lc_table('call_numbers') . "` ");
    $diag['poolNumbers'] = (int) ($r['cnt'] ?? 0);
}

$pool = function_exists('lc_call_numbers_ensure_from_logs')
    ? lc_call_numbers_ensure_from_logs()
    : array('ok' => false, 'message' => 'no ensure fn', 'created' => 0, 'exists' => 0);

$rematch = lc_call_logs_rematch_unmatched(array('onlyUnmatched' => true, 'limit' => 5000));

echo json_encode(array(
    'ok' => !empty($rematch['ok']),
    'diagBefore' => $diag,
    'pool' => $pool,
    'rematch' => $rematch,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
