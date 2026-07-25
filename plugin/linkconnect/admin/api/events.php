<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    $view = isset($_GET['view']) ? (string) $_GET['view'] : '';

    if ($view === 'rewards') {
        lc_api_success(array(
            'items'   => lc_event_rewards_admin_for_api(array(
                'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
                'evId'   => isset($_GET['evId']) ? (int) $_GET['evId'] : 0,
            )),
            'dbReady' => lc_db_installed(),
        ));
    }

    if ($view === 'participants') {
        $ev_id = isset($_GET['evId']) ? (int) $_GET['evId'] : 0;
        if ($ev_id <= 0) {
            lc_api_error('이벤트 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        lc_api_success(array(
            'items'   => lc_event_participants_admin_for_api($ev_id),
            'dbReady' => lc_db_installed(),
        ));
    }

    if ($view === 'roi') {
        $data = lc_event_roi_for_api(isset($_GET['evId']) ? (int) $_GET['evId'] : 0);
        lc_api_success(array_merge($data, array('dbReady' => lc_db_installed())));
    }

    $filters = array(
        'q'      => isset($_GET['q']) ? (string) $_GET['q'] : '',
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
    );

    lc_api_success(array(
        'items'   => lc_event_admin_for_api($filters),
        'summary' => lc_event_admin_summary(),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    if (!lc_db_table_exists(lc_event_table())) {
        lc_api_error('이벤트 테이블이 없습니다. DB 마이그레이션을 실행해주세요.', 'DB_NOT_READY', 400);
    }

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'save';
    $ev_id = isset($body['evId']) ? (int) $body['evId'] : 0;

    if ($action === 'save' || $action === 'create' || $action === 'update') {
        if ($action === 'update' && $ev_id <= 0) {
            lc_api_error('이벤트 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_event_save($body, $action === 'create' ? 0 : $ev_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SAVE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'event'   => $result['event'],
            'summary' => lc_event_admin_summary(),
        ));
    }

    $status_map = array(
        'activate' => LC_EVENT_ACTIVE,
        'closing'  => LC_EVENT_CLOSING,
        'end'      => LC_EVENT_ENDED,
        'schedule' => LC_EVENT_SCHEDULED,
    );

    if (isset($status_map[$action])) {
        if ($ev_id <= 0) {
            lc_api_error('이벤트 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_event_update_status($ev_id, $status_map[$action]);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'event'   => $result['event'],
            'summary' => lc_event_admin_summary(),
        ));
    }

    if ($action === 'create_reward') {
        $result = lc_event_reward_create($body);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'CREATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'id'      => (int) ($result['id'] ?? 0),
        ));
    }

    if ($action === 'pay_reward' || $action === 'reject_reward') {
        $er_id = isset($body['erId']) ? (int) $body['erId'] : 0;
        if ($er_id <= 0) {
            lc_api_error('리워드 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $status = $action === 'pay_reward' ? 'paid' : 'rejected';
        $memo = trim((string) ($body['memo'] ?? ''));
        $result = lc_event_reward_update_status($er_id, $status, $memo);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
        ));
    }

    if ($action === 'auto_ranking_rewards') {
        $period = isset($body['period']) ? trim((string) $body['period']) : '';
        $result = lc_event_auto_ranking_rewards($period);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'CREATE_FAILED', 400);
        }

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('event_auto_ranking', 'event', 0, $result['message'], array('created' => (int) ($result['created'] ?? 0)));
        }

        lc_api_success(array(
            'message' => $result['message'],
            'created' => (int) ($result['created'] ?? 0),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
