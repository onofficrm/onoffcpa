<?php
require_once __DIR__ . '/_common.php';

lc_db_run_migrations();
lc_campaign_thumbnail_ensure_storage();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
$cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : (isset($_GET['campaignId']) ? (int) $_GET['campaignId'] : 0);

if ($method === 'GET') {
    lc_api_require_admin();

    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    $serve = lc_campaign_thumbnail_serve($cp_id, false);
    if (empty($serve['ok']) || empty($serve['file'])) {
        lc_api_error($serve['message'] ?? '이미지를 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    header('Content-Type: ' . ($serve['mime'] ?? 'image/jpeg'));
    header('Cache-Control: private, max-age=300');
    readfile($serve['file']);
    exit;
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    $is_multipart = !empty($_FILES);
    $body = $is_multipart ? $_POST : lc_api_read_json_body();
    if ($cp_id <= 0) {
        $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);
    }

    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    $action = isset($body['action']) ? (string) $body['action'] : ($is_multipart ? 'upload' : '');

    if ($action === 'delete') {
        $result = lc_campaign_thumbnail_delete($cp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        lc_api_success(array('message' => $result['message']));
    }

    if ($action === 'upload' || $is_multipart) {
        $file_key = null;
        foreach (array('file', 'image', 'thumbnail') as $key) {
            if (!empty($_FILES[$key]) && is_array($_FILES[$key])) {
                $file_key = $key;
                break;
            }
        }

        if ($file_key === null) {
            lc_api_error('이미지 파일이 필요합니다.', 'INVALID_FILE', 400);
        }

        $result = lc_campaign_thumbnail_upload($cp_id, $_FILES[$file_key]);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPLOAD_FAILED', 400);
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        lc_api_success(array(
            'message'      => $result['message'],
            'thumbnailUrl' => $result['thumbnailUrl'] ?? lc_campaign_thumbnail_admin_url($cp_id),
            'campaign'   => is_array($campaign) ? lc_campaign_to_admin_api($campaign) : null,
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
