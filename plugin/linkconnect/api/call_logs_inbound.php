<?php
/**
 * 자매 사이트 통화내역 동기화 수신 (onoffcpa → linkconnect/trendhub)
 * POST JSON + X-LC-Call-Sync-Secret
 *
 * Body: { rows: [...], skipConversion?: 0|1, source?: string }
 */
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('POST');

if (defined('LC_CALL_LOG_SYNC_ENABLED') && !LC_CALL_LOG_SYNC_ENABLED) {
    lc_api_error('통화내역 동기화가 비활성화되어 있습니다.', 'SYNC_DISABLED', 404);
}

$secret = '';
if (!empty($_SERVER['HTTP_X_LC_CALL_SYNC_SECRET'])) {
    $secret = (string) $_SERVER['HTTP_X_LC_CALL_SYNC_SECRET'];
}

$body = lc_api_read_json_body();
if ($secret === '' && isset($body['secret'])) {
    $secret = (string) $body['secret'];
}

if (!function_exists('lc_call_log_sync_secret_ok') || !lc_call_log_sync_secret_ok($secret)) {
    lc_api_error('동기화 비밀키가 올바르지 않습니다.', 'UNAUTHORIZED', 401);
}

$rows = isset($body['rows']) && is_array($body['rows']) ? $body['rows'] : array();
if (!$rows) {
    lc_api_error('rows 가 필요합니다.', 'NO_ROWS', 400);
}

// 수신측은 재전파하지 않음 (루프 방지) — sync 함수가 onoffcpa 호스트에서만 push
$skip_conversion = !empty($body['skipConversion'])
    || (isset($body['skipConversion']) && (string) $body['skipConversion'] === '1');

if (!function_exists('lc_call_logs_import_bulk')) {
    lc_api_error('콜디비 import 함수를 사용할 수 없습니다.', 'NO_IMPORT', 500);
}

$result = lc_call_logs_import_bulk($rows, $skip_conversion);
if (function_exists('lc_admin_log_write')) {
    lc_admin_log_write('call_import_logs_sync', 'call_log', 0, (string) ($result['message'] ?? 'peer sync'), array(
        'source'    => (string) ($body['source'] ?? ''),
        'total'     => (int) ($result['total'] ?? 0),
        'imported'  => (int) ($result['imported'] ?? 0),
        'duplicate' => (int) ($result['duplicate'] ?? 0),
        'failed'    => (int) ($result['failed'] ?? 0),
        'unmatched' => (int) ($result['unmatched'] ?? 0),
        'skipConversion' => $skip_conversion,
    ));
}

if (!empty($result['ok'])) {
    // items 는 대량일 수 있어 응답에서 축소
    if (isset($result['items']) && count($result['items']) > 20) {
        $result['items'] = array_slice($result['items'], 0, 20);
        $result['itemsTruncated'] = true;
    }
    lc_api_success($result);
}

lc_api_error((string) ($result['message'] ?? 'import failed'), 'IMPORT_FAILED', 400);
