<?php
/**
 * 통화로그 기간 내보내기 (일회)
 * 예: ?action=run&token=...&from=2026-09-01&to=2026-09-02
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');

$expected_token = 'callexport-a7f3c91e2b84d06e5f10';
$given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
$admin_ok = function_exists('lc_is_super_admin') && lc_is_super_admin();
$token_ok = ($given !== '' && hash_equals($expected_token, $given));
if (!$token_ok && !$admin_ok) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'FORBIDDEN'), JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'run') {
    echo json_encode(array('ok' => true, 'message' => 'action=run&token=...&from=YYYY-MM-DD&to=YYYY-MM-DD'), JSON_UNESCAPED_UNICODE);
    exit;
}

$from = isset($_REQUEST['from']) ? trim((string) $_REQUEST['from']) : '2026-09-01';
$to = isset($_REQUEST['to']) ? trim((string) $_REQUEST['to']) : '2026-09-02';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'INVALID_DATE'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_db_installed') || !lc_db_installed() || !lc_db_table_exists(lc_table('call_logs'))) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'NO_TABLE'), JSON_UNESCAPED_UNICODE);
    exit;
}

$clog = lc_table('call_logs');
$from_s = lc_sql_escape($from . ' 00:00:00');
$to_s = lc_sql_escape($to . ' 23:59:59');
$sql = " SELECT clog_caller, clog_virtual_number, clog_callee, clog_started_at, clog_duration, clog_recording_url, clog_result
         FROM `{$clog}`
         WHERE clog_started_at >= '{$from_s}' AND clog_started_at <= '{$to_s}'
         ORDER BY clog_started_at ASC, clog_id ASC ";
$result = lc_sql_query($sql, false);
$rows = array();
$csv_lines = array();
$csv_lines[] = '발신번호,가상번호,착신번호,통화일자,통화시작시간,통화시간(초),녹음파일,통화결과';

if ($result) {
    while ($row = sql_fetch_array($result)) {
        $started = (string) ($row['clog_started_at'] ?? '');
        $ts = $started !== '' ? strtotime($started) : false;
        $date = $ts ? date('Y-m-d', $ts) : '';
        $time = $ts ? date('H:i:s', $ts) : '';
        $duration = (int) ($row['clog_duration'] ?? 0);
        $duration_txt = $duration > 0 ? ($duration . '초') : '0초';
        $rec = trim((string) ($row['clog_recording_url'] ?? ''));
        $result_label = (string) ($row['clog_result'] ?? '');
        // API uses codes; map common ones back to Korean labels for re-import
        $map = array(
            'success' => '통화성공',
            'answered' => '통화성공',
            'missed' => '부재중',
            'busy' => '통화중',
            'fail' => '통화실패',
            'failed' => '통화실패',
        );
        $rl = strtolower($result_label);
        if (isset($map[$rl])) {
            $result_label = $map[$rl];
        }

        $caller = preg_replace('/\D+/', '', (string) ($row['clog_caller'] ?? ''));
        $virtual = preg_replace('/\D+/', '', (string) ($row['clog_virtual_number'] ?? ''));
        $callee = preg_replace('/\D+/', '', (string) ($row['clog_callee'] ?? ''));
        // strip leading 0 for virtual to match vendor format if 0503...
        if (strlen($virtual) === 12 && strpos($virtual, '0503') === 0) {
            $virtual = substr($virtual, 1);
        }

        $line = array($caller, $virtual, $callee, $date, $time, $duration_txt, $rec, $result_label);
        $rows[] = $line;
        $csv_lines[] = implod_csv($line);
    }
}

function implod_csv(array $cols)
{
    $out = array();
    foreach ($cols as $c) {
        $c = (string) $c;
        if (strpos($c, ',') !== false || strpos($c, '"') !== false) {
            $c = '"' . str_replace('"', '""', $c) . '"';
        }
        $out[] = $c;
    }

    return implode(',', $out);
}

$format = isset($_REQUEST['format']) ? (string) $_REQUEST['format'] : 'json';
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="call_logs_' . $from . '_' . $to . '.csv"');
    echo "\xEF\xBB\xBF" . implode("\n", $csv_lines) . "\n";
    exit;
}

echo json_encode(array(
    'ok' => true,
    'from' => $from,
    'to' => $to,
    'total' => count($rows),
    'csv' => implode("\n", $csv_lines),
    'preview' => array_slice($rows, 0, 5),
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
