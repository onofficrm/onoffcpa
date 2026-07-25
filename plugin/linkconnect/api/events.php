<?php
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('GET');

$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
if ($code === '' && isset($_GET['id'])) {
    $code = trim((string) $_GET['id']);
}

if ($code !== '') {
    $detail = lc_event_public_detail($code);
    if (!$detail) {
        lc_api_error('이벤트를 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    lc_api_success($detail);
}

$filters = array(
    'q' => isset($_GET['q']) ? (string) $_GET['q'] : '',
);

lc_api_success(lc_events_public_payload($filters));
