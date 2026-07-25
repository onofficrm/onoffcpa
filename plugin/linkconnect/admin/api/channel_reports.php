<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();
    lc_api_success(array(
        'items'   => lc_channel_report_list_for_api(array(
            'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
        )),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    $cr_id = isset($body['crId']) ? (int) $body['crId'] : 0;

    if ($action === 'sanction' || $action === 'dismiss' || $action === 'review') {
        $map = array('sanction' => 'sanctioned', 'dismiss' => 'dismissed', 'review' => 'reviewing');
        $result = lc_channel_report_update_status($cr_id, $map[$action], trim((string) ($body['memo'] ?? '')));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }
        lc_api_success($result);
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
