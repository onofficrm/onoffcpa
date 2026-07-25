<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();
    lc_api_success(array(
        'impersonate' => lc_impersonate_state_for_api(),
        'history'     => lc_impersonate_history_for_api(10),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    if (!lc_is_super_admin()) {
        lc_api_error('최고관리자만 이용할 수 있습니다.', 'FORBIDDEN', 403);
    }

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'exit') {
        $result = lc_impersonate_clear();
        lc_api_success(array(
            'message'     => $result['message'],
            'impersonate' => lc_impersonate_state_for_api(),
            'redirect'    => '/admin',
        ));
    }

    if ($action === 'view_partner') {
        $pt_id = isset($body['ptId']) ? (int) $body['ptId'] : 0;
        $result = lc_impersonate_start('partner', $pt_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'IMPERSONATE_FAILED', 400);
        }
        lc_api_success(array(
            'message'     => $result['message'],
            'impersonate' => lc_impersonate_state_for_api(),
            'redirect'    => (string) ($result['redirect'] ?? '/partner'),
        ));
    }

    if ($action === 'view_merchant') {
        $mt_id = isset($body['mtId']) ? (int) $body['mtId'] : 0;
        $result = lc_impersonate_start('merchant', $mt_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'IMPERSONATE_FAILED', 400);
        }
        lc_api_success(array(
            'message'     => $result['message'],
            'impersonate' => lc_impersonate_state_for_api(),
            'redirect'    => (string) ($result['redirect'] ?? '/advertiser'),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
