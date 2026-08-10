<?php
require_once dirname(__DIR__) . '/_common.php';

if (function_exists('lc_api_handle_cors_preflight')) {
    lc_api_handle_cors_preflight();
}
if (function_exists('lc_api_allow_public_cors')) {
    lc_api_allow_public_cors();
}

lc_api_require_method('POST');

$body = lc_api_read_json_body();
if (!$body && $_POST) {
    $body = $_POST;
}
if (!is_array($body)) {
    $body = array();
}

if (!function_exists('lc_embed_events_record')) {
    lc_api_error('이벤트 기능을 사용할 수 없습니다.', 'UNAVAILABLE', 503);
}

$result = lc_embed_events_record($body);
if (empty($result['ok'])) {
    // 추적 실패가 위젯 UX를 깨지 않도록 소프트 실패(200)로도 가능하지만,
    // 잘못된 이벤트/키는 클라이언트가 알 수 있게 400 유지.
    $msg = (string) ($result['message'] ?? '저장 실패');
    $code = (strpos($msg, 'lkCode') !== false || strpos($msg, '이벤트') !== false || strpos($msg, '키') !== false)
        ? 400
        : 400;
    lc_api_error($msg, 'EVENT_FAILED', $code);
}

lc_api_success(array('message' => 'ok'));
