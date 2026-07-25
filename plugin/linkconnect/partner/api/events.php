<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    lc_api_success(array(
        'ranking' => lc_event_ranking_for_api($pt_id),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'join';

    if ($action === 'join') {
        $code = trim((string) ($body['evCode'] ?? ($body['code'] ?? '')));
        $ev_id = isset($body['evId']) ? (int) $body['evId'] : 0;

        if ($code !== '') {
            $result = lc_event_participant_join_by_code($code, $pt_id);
        } elseif ($ev_id > 0) {
            $result = lc_event_participant_join($ev_id, $pt_id);
        } else {
            lc_api_error('이벤트 코드가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        if (!$result['ok']) {
            lc_api_error($result['message'], 'JOIN_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'joined'  => !empty($result['joined']),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
