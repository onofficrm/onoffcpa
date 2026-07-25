<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
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
    $asset_id = isset($_GET['assetId']) ? (int) $_GET['assetId'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    if ($asset_id <= 0) {
        lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
    }

    $asset = lc_campaign_promo_guide_asset_get_by_id($asset_id);
    if (!is_array($asset) || !lc_campaign_promo_guide_can_merchant_view_asset($mt_id, $asset)) {
        lc_api_error('이미지를 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    $serve = lc_campaign_promo_guide_serve_asset($asset);
    if (empty($serve['ok'])) {
        lc_api_error($serve['message'], 'NOT_FOUND', 404);
    }

    if (!headers_sent()) {
        header('Content-Type: ' . $serve['mime']);
        header('Content-Length: ' . (string) filesize($serve['path']));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
    }

    readfile($serve['path']);
    exit;
}

if ($method === 'POST') {
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
    $is_multipart = !empty($_FILES);
    $body = $is_multipart ? $_POST : lc_api_read_json_body();
    lc_campaign_promo_guide_api_require_post_csrf($body);

    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);
    $action = isset($body['action']) ? (string) $body['action'] : ($is_multipart ? 'upload' : '');
    $image_title = isset($body['imageTitle']) ? (string) $body['imageTitle'] : '';

    if ($action === 'upload' || ($is_multipart && $action === '')) {
        if ($cp_id <= 0) {
            lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
        }

        $file_key = isset($_FILES['file']) ? 'file' : (isset($_FILES['image']) ? 'image' : '');
        if ($file_key === '') {
            lc_api_error('업로드 파일이 필요합니다.', 'INVALID_FILE', 400);
        }

        $result = lc_campaign_promo_guide_upload_asset($mt_id, $cp_id, $_FILES[$file_key], $image_title);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPLOAD_FAILED', 400);
        }

        lc_api_success(array(
            'message'   => $result['message'],
            'asset'     => is_array($result['asset']) ? lc_campaign_promo_guide_asset_to_api($result['asset']) : null,
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'delete') {
        $asset_id = isset($body['assetId']) ? (int) $body['assetId'] : 0;
        if ($asset_id <= 0) {
            lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
        }

        $result = lc_campaign_promo_guide_delete_asset($mt_id, $asset_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        lc_api_success(array(
            'message'   => $result['message'],
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'update_title') {
        $asset_id = isset($body['assetId']) ? (int) $body['assetId'] : 0;
        $image_title = isset($body['imageTitle']) ? (string) $body['imageTitle'] : '';
        if ($asset_id <= 0) {
            lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
        }

        $result = lc_campaign_promo_guide_update_asset_title($mt_id, $asset_id, $image_title);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }

        lc_api_success(array(
            'message'   => $result['message'],
            'asset'     => is_array($result['asset']) ? lc_campaign_promo_guide_asset_to_api($result['asset']) : null,
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    if ($action === 'sort') {
        if ($cp_id <= 0) {
            lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
        }

        $asset_ids = array();
        if (isset($body['assetIds'])) {
            if (is_array($body['assetIds'])) {
                $asset_ids = $body['assetIds'];
            } elseif (is_string($body['assetIds'])) {
                $decoded = json_decode($body['assetIds'], true);
                if (is_array($decoded)) {
                    $asset_ids = $decoded;
                }
            }
        }

        $result = lc_campaign_promo_guide_sort_assets($mt_id, $cp_id, $asset_ids);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SORT_FAILED', 400);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        lc_api_success(array(
            'message'   => $result['message'],
            'guide'     => is_array($guide) ? lc_campaign_promo_guide_to_api($guide) : null,
            'csrfToken' => lc_campaign_promo_guide_csrf_token(),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
