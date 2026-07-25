<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();
lc_api_require_admin();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $asset_id = isset($_GET['assetId']) ? (int) $_GET['assetId'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    if ($asset_id <= 0) {
        lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
    }

    $asset = lc_campaign_promo_guide_asset_get_by_id($asset_id);
    if (!is_array($asset) || (int) ($asset['cpga_is_active'] ?? 0) !== 1) {
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
    lc_api_require_method('POST');
    $is_multipart = !empty($_FILES);
    $body = $is_multipart ? $_POST : lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : ($is_multipart ? 'upload' : '');

    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);

    if ($action === 'upload' || ($is_multipart && $action === '')) {
        if ($cp_id <= 0) {
            lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            lc_api_error('광고상품을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }

        $mt_id = (int) ($campaign['mt_id'] ?? 0);
        $file_key = isset($_FILES['file']) ? 'file' : (isset($_FILES['image']) ? 'image' : '');
        if ($file_key === '') {
            lc_api_error('업로드 파일이 필요합니다.', 'INVALID_FILE', 400);
        }

        $image_title = isset($body['imageTitle']) ? (string) $body['imageTitle'] : '';
        $result = lc_campaign_promo_guide_upload_asset($mt_id, $cp_id, $_FILES[$file_key], $image_title, true);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPLOAD_FAILED', 400);
        }

        lc_api_success(array(
            'message' => $result['message'],
            'asset'   => is_array($result['asset']) ? lc_campaign_promo_guide_asset_to_api($result['asset']) : null,
        ));
    }

    if ($action === 'delete') {
        $asset_id = isset($body['assetId']) ? (int) $body['assetId'] : 0;
        if ($asset_id <= 0) {
            lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
        }

        $asset = lc_campaign_promo_guide_asset_get_by_id($asset_id);
        if (!is_array($asset)) {
            lc_api_error('이미지를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }

        $mt_id = (int) ($asset['cpga_mt_id'] ?? 0);
        $result = lc_campaign_promo_guide_delete_asset($mt_id, $asset_id, true);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        lc_api_success(array('message' => $result['message']));
    }

    if ($action === 'sort') {
        if ($cp_id <= 0) {
            lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            lc_api_error('광고상품을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }

        $mt_id = (int) ($campaign['mt_id'] ?? 0);
        $asset_ids = isset($body['assetIds']) && is_array($body['assetIds']) ? $body['assetIds'] : array();
        $result = lc_campaign_promo_guide_sort_assets($mt_id, $cp_id, $asset_ids, true);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SORT_FAILED', 400);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        lc_api_success(array(
            'message' => $result['message'],
            'guide'   => is_array($guide) ? lc_campaign_promo_guide_to_api($guide, null, true) : null,
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
