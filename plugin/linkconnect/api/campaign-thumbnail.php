<?php
/**
 * CPA 광고상품 썸네일 공개 제공 (운영중 상품만)
 */
require_once dirname(__DIR__) . '/_common.php';

lc_campaign_thumbnail_ensure_storage();

$cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : (isset($_GET['campaignId']) ? (int) $_GET['campaignId'] : 0);
if ($cp_id <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

$serve = lc_campaign_thumbnail_serve($cp_id, true);
if (empty($serve['ok']) || empty($serve['file'])) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: ' . ($serve['mime'] ?? 'image/jpeg'));
header('Cache-Control: public, max-age=86400');
readfile($serve['file']);
exit;
