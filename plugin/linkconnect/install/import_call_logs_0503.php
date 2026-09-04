<?php
/**
 * 0503 통화내역 CSV 일회 반영 (2026-08)
 *
 * 브라우저: /plugin/linkconnect/install/import_call_logs_0503.php?action=run&token=...
 * dry-run:  &dryRun=1
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');

$is_cli = php_sapi_name() === 'cli';
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
$dry_run = !empty($_REQUEST['dryRun']) || (isset($_REQUEST['dryRun']) && (string) $_REQUEST['dryRun'] === '1');
$skip_conversion = !isset($_REQUEST['skipConversion']) || (string) $_REQUEST['skipConversion'] !== '0';

/** 일회용 토큰 — 실행 후 스크립트/데이터 삭제 권장 */
$expected_token = 'callimport-421e6f77385790e4d26c00e9';
$given_token = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
$token_ok = ($given_token !== '' && hash_equals($expected_token, $given_token));
$admin_ok = function_exists('lc_is_super_admin') && lc_is_super_admin();

if (!$is_cli && !$token_ok && !$admin_ok) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => '권한이 없습니다.', 'code' => 'FORBIDDEN'), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action !== 'run' && !$is_cli) {
    echo json_encode(array(
        'ok' => true,
        'message' => 'action=run&token=... 으로 실행하세요. dryRun=1 미리보기, skipConversion=0 이면 전환 생성.',
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$done_flag = dirname(__DIR__) . '/install/data/call_import_0503_202608.done';
if (is_file($done_flag) && empty($_REQUEST['force'])) {
    echo json_encode(array(
        'ok' => false,
        'error' => '이미 반영된 임포트입니다. 재실행은 force=1',
        'code' => 'ALREADY_DONE',
        'doneAt' => trim((string) @file_get_contents($done_flag)),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$csv_path = dirname(__DIR__) . '/install/data/call_import_0503_202608.csv';
if (!is_readable($csv_path)) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'CSV 파일이 없습니다.', 'path' => $csv_path), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_call_logs_import_parse_file') || !function_exists('lc_call_logs_import_bulk')) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => '콜디비 import 함수를 로드할 수 없습니다.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$parsed = lc_call_logs_import_parse_file($csv_path, basename($csv_path));
if (empty($parsed['ok'])) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => (string) ($parsed['message'] ?? 'parse failed')), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($dry_run) {
    echo json_encode(array(
        'ok' => true,
        'dryRun' => true,
        'message' => $parsed['message'],
        'total' => count($parsed['rows'] ?? array()),
        'headers' => $parsed['headers'] ?? array(),
        'preview' => array_slice($parsed['rows'] ?? array(), 0, 5),
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$result = lc_call_logs_import_bulk($parsed['rows'] ?? array(), $skip_conversion);
if (!empty($result['ok'])) {
    @file_put_contents($done_flag, date('c') . ' total=' . (int) ($result['total'] ?? 0)
        . ' imported=' . (int) ($result['imported'] ?? 0)
        . ' duplicate=' . (int) ($result['duplicate'] ?? 0)
        . ' unmatched=' . (int) ($result['unmatched'] ?? 0)
        . PHP_EOL);
    if (function_exists('lc_admin_log_write')) {
        lc_admin_log_write('call_import_logs', 'call_log', 0, (string) ($result['message'] ?? '0503 CSV import'), array(
            'source' => 'import_call_logs_0503.php',
            'total' => (int) ($result['total'] ?? 0),
            'imported' => (int) ($result['imported'] ?? 0),
            'duplicate' => (int) ($result['duplicate'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
            'unmatched' => (int) ($result['unmatched'] ?? 0),
            'skipConversion' => $skip_conversion,
        ));
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
