<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
    $merchant = lc_api_require_active_merchant();
} else {
    lc_api_require_login();
    $merchant = lc_get_current_merchant();
}

if (!is_array($merchant)) {
    lc_api_error('광고주 등록이 필요합니다.', 'NOT_MERCHANT', 403);
}

$mt_id = (int) $merchant['mt_id'];
if ($mt_id <= 0) {
    lc_api_error('광고주 정보가 유효하지 않습니다.', 'NOT_MERCHANT', 403);
}

if ($method === 'GET') {
    $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : (isset($_GET['campaignId']) ? (int) $_GET['campaignId'] : 0);
    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
    }

    $view = lc_campaign_promo_guide_merchant_view($mt_id, $cp_id);
    if (empty($view['ok'])) {
        lc_api_error($view['message'], 'FORBIDDEN', 403);
    }

    lc_api_success($view['data']);
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    $body = lc_api_read_json_body();
    lc_campaign_promo_guide_api_require_post_csrf($body);

    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);
    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
    }

    $action = isset($body['action']) ? (string) $body['action'] : 'update';

    if ($action === 'create') {
        $result = lc_campaign_promo_guide_create($mt_id, $cp_id);
        if (empty($result['ok'])) {
            $code = isset($result['guide']) ? 'ALREADY_EXISTS' : 'CREATE_FAILED';
            lc_api_error($result['message'], $code, 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'save_draft') {
        $result = lc_campaign_promo_guide_save_content($mt_id, $cp_id, $body, true);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'VALIDATION', 400, array(
                'errors' => $result['errors'] ?? array(),
            ));
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'update') {
        $result = lc_campaign_promo_guide_save_content($mt_id, $cp_id, $body, false);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'VALIDATION', 400, array(
                'errors' => $result['errors'] ?? array(),
            ));
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'submit_review') {
        if (!empty($body['payload']) && is_array($body['payload'])) {
            $existing = lc_campaign_promo_guide_get_by_cp_id($cp_id);
            $published = is_array($existing) && (string) ($existing['cpg_status'] ?? '') === LC_CPG_STATUS_PUBLISHED;
            $save = lc_campaign_promo_guide_save_content($mt_id, $cp_id, $body['payload'], $published);
            if (empty($save['ok'])) {
                lc_api_error($save['message'], 'VALIDATION', 400, array(
                    'errors' => $save['errors'] ?? array(),
                ));
            }
        }

        if (lc_campaign_promo_guide_skip_review()) {
            $result = lc_campaign_promo_guide_merchant_publish($mt_id, $cp_id);
            if (empty($result['ok'])) {
                lc_api_error($result['message'], 'PUBLISH_FAILED', 400);
            }

            lc_api_success(array(
                'message' => '파트너에게 공개되었습니다.',
                'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
                'csrfToken' => lc_campaign_promo_guide_csrf_token(),
            ));
        }

        $result = lc_campaign_promo_guide_submit_review($mt_id, $cp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SUBMIT_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'publish') {
        if (!empty($body['payload']) && is_array($body['payload'])) {
            $save = lc_campaign_promo_guide_save_content($mt_id, $cp_id, $body['payload'], false);
            if (empty($save['ok'])) {
                lc_api_error($save['message'], 'VALIDATION', 400, array(
                    'errors' => $save['errors'] ?? array(),
                ));
            }
        }

        $result = lc_campaign_promo_guide_merchant_publish($mt_id, $cp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'PUBLISH_FAILED', 400);
        }

        lc_api_success(array(
            'message' => '파트너에게 공개되었습니다.',
            'guide'   => lc_campaign_promo_guide_to_api($result['guide']),
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'sort_images') {
        $asset_ids = isset($body['assetIds']) && is_array($body['assetIds']) ? $body['assetIds'] : array();
        $result = lc_campaign_promo_guide_sort_assets($mt_id, $cp_id, $asset_ids);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SORT_FAILED', 400);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => is_array($guide) ? lc_campaign_promo_guide_to_api($guide) : null,
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
