<?php
/**
 * GET /plugin/linkconnect/content-api/campaigns.php
 *
 * Scope: content.campaigns.read
 * Reuses lc_campaign_list_for_api (same as public api/campaigns.php).
 * CPS remains disabled at runtime.
 */
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');

$auth = lc_content_s2s_require('content.campaigns.read');
$pt_id = (int) $auth['pt_id'];

$override = lc_content_s2s_reject_client_pt_override($_GET, $pt_id);
if (empty($override['ok'])) {
    lc_api_error($override['message'], $override['code'], 403);
}

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';

if (strtolower($type) === 'cps' && (!function_exists('lc_cps_enabled') || !lc_cps_enabled())) {
    lc_api_error('CPS campaigns are not available.', 'CPS_DISABLED', 404);
}

// Production available: CPA only unless CPS flag is enabled.
if ($type === '') {
    $type = 'cpa';
}

$items = lc_campaign_list_for_api(array(
    'category' => $category,
    'q'        => $q,
    'type'     => $type,
    'id'       => $id,
    'code'     => $code,
));

$categories = array('전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타');

lc_api_success(array(
    'items'      => $items,
    'categories' => $categories,
    'dbReady'    => lc_db_installed(),
    'partnerId'  => $pt_id,
));
