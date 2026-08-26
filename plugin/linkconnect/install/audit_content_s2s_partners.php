<?php
/**
 * READ-ONLY — list active partners for Content S2S credential mapping (operator).
 * Uploaded temporarily via provision workflow; deleted after use.
 */
header('Content-Type: application/json; charset=utf-8');

define('_GNUBOARD_', true);
require_once __DIR__ . '/plugin/linkconnect/_common.php';

if (!function_exists('lc_db_installed') || !lc_db_installed()) {
    echo json_encode(array('ok' => false, 'error' => 'DB not ready', 'code' => 'DB_NOT_READY'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_table') || !function_exists('lc_sql_query') || !function_exists('lc_sql_escape')) {
    echo json_encode(array('ok' => false, 'error' => 'DB helpers unavailable', 'code' => 'NOT_AVAILABLE'), JSON_UNESCAPED_UNICODE);
    exit;
}

$table = lc_table('partners');
$active = lc_sql_escape(LC_PARTNER_STATUS_ACTIVE);
$sql = " SELECT pt_id, pt_code, pt_name, pt_status
    FROM `{$table}`
    WHERE pt_status = '{$active}'
    ORDER BY pt_id ASC ";

$out = array();
$result = lc_sql_query($sql, false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $out[] = array(
            'pt_id'      => (int) ($row['pt_id'] ?? 0),
            'pt_code'    => (string) ($row['pt_code'] ?? ''),
            'name'       => (string) ($row['pt_name'] ?? ''),
            'statusCode' => (string) ($row['pt_status'] ?? ''),
        );
    }
}

echo json_encode(
    array(
        'ok'    => true,
        'count' => count($out),
        'items' => $out,
    ),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
