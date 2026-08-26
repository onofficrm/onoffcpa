<?php
/**
 * READ-ONLY — list active partners for Content S2S credential mapping (operator).
 * Upload temporarily via provision workflow; deleted after use.
 */
header('Content-Type: application/json; charset=utf-8');

define('_GNUBOARD_', true);
require_once __DIR__ . '/plugin/linkconnect/_common.php';

if (!function_exists('lc_db_installed') || !lc_db_installed()) {
    echo json_encode(array('ok' => false, 'error' => 'DB not ready', 'code' => 'DB_NOT_READY'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_admin_partners_for_api')) {
    echo json_encode(array('ok' => false, 'error' => 'Partner API unavailable', 'code' => 'NOT_AVAILABLE'), JSON_UNESCAPED_UNICODE);
    exit;
}

$items = lc_admin_partners_for_api(array('status' => 'active'));
$out = array();
foreach ($items as $row) {
    if (!is_array($row)) {
        continue;
    }
    $out[] = array(
        'pt_id'      => (int) ($row['id'] ?? 0),
        'pt_code'    => (string) ($row['code'] ?? ''),
        'name'       => (string) ($row['name'] ?? ''),
        'statusCode' => (string) ($row['statusCode'] ?? ''),
    );
}

echo json_encode(
    array(
        'ok'    => true,
        'count' => count($out),
        'items' => $out,
    ),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
