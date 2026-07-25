<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    $data = lc_notification_list_for_api('partner', $pt_id, array(
        'limit'      => isset($_GET['limit']) ? (int) $_GET['limit'] : 30,
        'unreadOnly' => isset($_GET['unread']) && $_GET['unread'] === '1',
    ));

    lc_api_success(array_merge($data, array('dbReady' => lc_db_installed())));
}

if ($method === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'read';

    if ($action === 'read') {
        $nf_id = isset($body['id']) ? (int) $body['id'] : 0;
        $result = lc_notification_mark_read('partner', $pt_id, $nf_id);
        lc_api_success(array('message' => $result['message']));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
