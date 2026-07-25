<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    $filters = array(
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'q'      => isset($_GET['q']) ? (string) $_GET['q'] : '',
    );

    lc_api_success(array(
        'items'   => array_map('lc_conversion_to_inspection_api', lc_conversion_list_for_inspection($filters)),
        'summary' => lc_conversion_inspection_summary(),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    if (!lc_db_installed()) {
        lc_api_error('DB가 설치되지 않았습니다.', 'DB_NOT_READY', 400);
    }

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    $cv_id = isset($body['cvId']) ? (int) $body['cvId'] : 0;

    if ($cv_id <= 0) {
        lc_api_error('디비 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    $result = lc_conversion_admin_review($cv_id, $action, isset($body['memo']) ? (string) $body['memo'] : '');
    if (!$result['ok']) {
        lc_api_error($result['message'], 'REVIEW_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'conversion' => $result['conversion'],
        'summary'    => lc_conversion_inspection_summary(),
    ));
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
