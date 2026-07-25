<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();
lc_api_require_admin();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $cpg_id = isset($_GET['guideId']) ? (int) $_GET['guideId'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : (isset($_GET['campaignId']) ? (int) $_GET['campaignId'] : 0);
    $list = isset($_GET['list']) && (string) $_GET['list'] !== '0' && (string) $_GET['list'] !== '';

    if ($list) {
        $filters = array(
            'status' => isset($_GET['guideStatus']) ? (string) $_GET['guideStatus'] : (isset($_GET['status']) ? (string) $_GET['status'] : ''),
            'q'      => isset($_GET['q']) ? (string) $_GET['q'] : '',
        );
        $data = lc_campaign_promo_guide_admin_list_for_api($filters);
        lc_api_success($data);
    }

    if ($cpg_id > 0 && isset($_GET['logs'])) {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 30;
        lc_api_success(array(
            'items' => lc_campaign_promo_guide_logs_for_api($cpg_id, $limit),
        ));
    }

    $guide = null;
    if ($cpg_id > 0) {
        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
    } elseif ($cp_id > 0) {
        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
    } else {
        lc_api_error('가이드 ID 또는 광고상품 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    if (!is_array($guide)) {
        lc_api_success(array(
            'exists'     => false,
            'campaignId' => $cp_id > 0 ? $cp_id : null,
        ));
    }

    $api = lc_campaign_promo_guide_to_api($guide, null, true);
    $api['exists'] = true;

    $campaign = lc_campaign_get_by_id((int) ($guide['cpg_cp_id'] ?? 0));
    if (is_array($campaign)) {
        $api['campaignName'] = (string) ($campaign['cp_name'] ?? '');
        $api['campaignStatus'] = (string) ($campaign['cp_status'] ?? '');
    }

    lc_api_success($api);
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    $body = lc_api_read_json_body();

    $cpg_id = isset($body['guideId']) ? (int) $body['guideId'] : (isset($body['id']) ? (int) $body['id'] : 0);
    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);
    $action = isset($body['action']) ? (string) $body['action'] : 'save';

    if ($cpg_id <= 0 && $cp_id > 0) {
        $existing = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (is_array($existing)) {
            $cpg_id = (int) $existing['cpg_id'];
        }
    }

    if ($action === 'create' && $cp_id > 0) {
        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            lc_api_error('광고상품을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $mt_id = (int) ($campaign['mt_id'] ?? 0);
        $result = lc_campaign_promo_guide_create($mt_id, $cp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'CREATE_FAILED', 400);
        }
        $cpg_id = (int) $result['guide']['cpg_id'];
    }

    if ($cpg_id <= 0) {
        lc_api_error('가이드 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    if ($action === 'save' || $action === 'update') {
        $result = lc_campaign_promo_guide_admin_save($cpg_id, $body);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'VALIDATION', 400, array(
                'errors' => $result['errors'] ?? array(),
            ));
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'publish') {
        $result = lc_campaign_promo_guide_admin_update_status($cpg_id, LC_CPG_STATUS_PUBLISHED);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('promo_guide_publish', 'campaign_promo_guide', $cpg_id, '홍보 가이드 파트너 공개', array('cp_id' => $cp_id));
        }

        lc_api_success(array(
            'message' => '홍보 가이드가 공개되었습니다.',
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'hide') {
        $result = lc_campaign_promo_guide_admin_update_status($cpg_id, LC_CPG_STATUS_HIDDEN);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('promo_guide_hide', 'campaign_promo_guide', $cpg_id, '홍보 가이드 비공개 처리', array('cp_id' => $cp_id));
        }

        lc_api_success(array(
            'message' => '홍보 가이드가 비공개 처리되었습니다.',
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'request_revision') {
        $reason = isset($body['reason']) ? (string) $body['reason'] : (isset($body['revisionReason']) ? (string) $body['revisionReason'] : '');
        $result = lc_campaign_promo_guide_admin_request_revision($cpg_id, $reason);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'review') {
        $result = lc_campaign_promo_guide_admin_update_status($cpg_id, LC_CPG_STATUS_REVIEW);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => '검토 대기 상태로 변경되었습니다.',
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'draft') {
        $result = lc_campaign_promo_guide_admin_update_status($cpg_id, LC_CPG_STATUS_DRAFT);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message' => '작성 중 상태로 변경되었습니다.',
            'guide'   => lc_campaign_promo_guide_to_api($result['guide'], null, true),
        ));
    }

    if ($action === 'delete_asset') {
        $asset_id = isset($body['assetId']) ? (int) $body['assetId'] : 0;
        if ($asset_id <= 0) {
            lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
        }

        $result = lc_campaign_promo_guide_admin_delete_asset($asset_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => is_array($guide) ? lc_campaign_promo_guide_to_api($guide, null, true) : null,
        ));
    }

    if ($action === 'sort_images') {
        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($guide)) {
            lc_api_error('홍보 가이드를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }

        $mt_id = (int) ($guide['cpg_mt_id'] ?? 0);
        $cp_id = (int) ($guide['cpg_cp_id'] ?? 0);
        $asset_ids = isset($body['assetIds']) && is_array($body['assetIds']) ? $body['assetIds'] : array();
        $result = lc_campaign_promo_guide_sort_assets($mt_id, $cp_id, $asset_ids);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SORT_FAILED', 400);
        }

        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => is_array($guide) ? lc_campaign_promo_guide_to_api($guide, null, true) : null,
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
