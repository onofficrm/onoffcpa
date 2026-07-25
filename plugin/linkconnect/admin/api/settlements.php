<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    if (isset($_GET['view']) && (string) $_GET['view'] === 'risk') {
        $st_id = isset($_GET['stId']) ? (int) $_GET['stId'] : 0;
        if ($st_id <= 0) {
            lc_api_error('정산 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }
        lc_api_success(array('risk' => lc_settlement_risk_check_for_api($st_id)));
    }

    $filters = array(
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'q'      => isset($_GET['q']) ? (string) $_GET['q'] : '',
    );

    lc_api_success(array(
        'items'   => array_map('lc_settlement_to_admin_api', lc_settlement_list_admin($filters)),
        'summary' => lc_settlement_admin_summary(),
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
    $st_id = isset($body['stId']) ? (int) $body['stId'] : 0;

    if ($st_id <= 0) {
        lc_api_error('정산 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    if ($action === 'approve' || $action === 'pay') {
        $risk = lc_settlement_risk_check_for_api($st_id);
        if (!empty($risk['blocked'])) {
            lc_api_error('정산 리스크가 높아 처리할 수 없습니다.', 'RISK_BLOCKED', 400, array('risk' => $risk));
        }
    }

    $result = lc_settlement_admin_update($st_id, $action, $body);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'UPDATE_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'settlement' => $result['settlement'],
        'summary'    => lc_settlement_admin_summary(),
    ));
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
