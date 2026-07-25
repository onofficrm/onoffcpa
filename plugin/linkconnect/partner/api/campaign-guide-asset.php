<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();
lc_api_require_method('GET');

if (LC_PARTNER_GUARD_ENABLED && lc_db_installed() && !lc_is_super_admin()) {
    lc_api_require_active_partner();
} else {
    lc_api_require_login();
}

$asset_id = isset($_GET['assetId']) ? (int) $_GET['assetId'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
if ($asset_id <= 0) {
    lc_api_error('이미지 ID가 필요합니다.', 'INVALID_ASSET', 400);
}

$asset = lc_campaign_promo_guide_asset_get_by_id($asset_id);
if (!is_array($asset) || !lc_campaign_promo_guide_can_partner_view_asset($asset)) {
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
