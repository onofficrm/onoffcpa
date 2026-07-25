<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    // hasugu_cpa 랜딩은 배포됐지만 광고상품 행이 없을 수 있음 → 목록 조회 시 1회 보정
    if (lc_db_installed() && function_exists('lc_campaign_ensure_hasugu_cpa')) {
        lc_campaign_ensure_hasugu_cpa();
    }

    $filters = array(
        'status'   => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'category' => isset($_GET['category']) ? (string) $_GET['category'] : '',
        'q'        => isset($_GET['q']) ? (string) $_GET['q'] : '',
    );

    lc_api_success(array(
        'items'   => lc_campaign_admin_for_api($filters),
        'summary' => lc_campaign_admin_summary(),
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
    $action = isset($body['action']) ? (string) $body['action'] : 'save';
    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : 0;

    if ($action === 'save' || $action === 'create' || $action === 'update') {
        if ($action === 'update' && $cp_id <= 0) {
            lc_api_error('캠페인 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_campaign_save($body, $action === 'create' ? 0 : $cp_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SAVE_FAILED', 400);
        }

        lc_api_success(array(
            'message'  => $result['message'],
            'campaign' => $result['campaign'],
        ));
    }

    $status_map = array(
        'activate' => LC_STATUS_ACTIVE,
        'pause'    => 'paused',
        'end'      => 'ended',
        'draft'    => LC_STATUS_DRAFT,
    );

    if (isset($status_map[$action])) {
        if ($cp_id <= 0) {
            lc_api_error('캠페인 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_campaign_update_status($cp_id, $status_map[$action]);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message'  => $result['message'],
            'campaign' => $result['campaign'],
        ));
    }

    if ($action === 'delete') {
        if (!lc_is_super_admin()) {
            lc_api_error('삭제 권한이 없습니다.', 'FORBIDDEN', 403);
        }

        if ($cp_id <= 0) {
            lc_api_error('캠페인 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $confirm = isset($body['confirm']) ? trim((string) $body['confirm']) : '';
        if ($confirm !== '삭제') {
            lc_api_error('삭제를 확인하려면 "삭제"를 입력해주세요.', 'CONFIRM_REQUIRED', 400);
        }

        $result = lc_campaign_delete($cp_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
