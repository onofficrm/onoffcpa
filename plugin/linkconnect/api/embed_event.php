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
    // 추적 실패가 위젯 UX/브라우저 콘솔을 어지럽히지 않도록 soft-ok
    lc_api_success(array(
        'message' => 'skipped',
        'accepted' => false,
        'reason' => (string) ($result['message'] ?? '저장 실패'),
    ));
}

lc_api_success(array('message' => 'ok', 'accepted' => true));
