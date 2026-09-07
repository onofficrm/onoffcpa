<?php
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('GET');

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
// CPS 미취급(LC_CPS_ENABLED=false)
if (strtolower($type) === 'cps' && (!function_exists('lc_cps_enabled') || !lc_cps_enabled())) {
    lc_api_error('CPS 캠페인은 제공하지 않습니다.', 'CPS_DISABLED', 404);
}

if (strtolower($type) === 'cps' && function_exists('lc_campaign_ensure_domain_cps')) {
    lc_campaign_ensure_domain_cps(array('activate' => true));
}
if (strtolower($type) === 'cps' && function_exists('lc_campaign_ensure_seo_geo_cps')) {
    lc_campaign_ensure_seo_geo_cps(array('activate' => true));
}
if (strtolower($type) === 'cps' && function_exists('lc_campaign_ensure_traffic_cps')) {
    lc_campaign_ensure_traffic_cps(array('activate' => true));
}
if (strtolower($type) === 'cps' && function_exists('lc_campaign_ensure_backlink_cps')) {
    lc_campaign_ensure_backlink_cps(array('activate' => true));
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';

$items = lc_campaign_list_for_api(array(
    'category' => $category,
    'q'        => $q,
    'type'     => $type,
    'id'       => $id,
    'code'     => $code,
));

$categories = $type === 'cps'
    ? array('전체', '디지털상품', '교육', '도메인', '여행/티켓', '종합쇼핑몰', '건강', '패션', '뷰티', '생활/인테리어', '기타')
    : array('전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타');
if ($type === 'cps' && function_exists('lc_campaign_cps_linkprice_categories')) {
    $lp_cats = lc_campaign_cps_linkprice_categories();
    if (is_array($lp_cats) && count($lp_cats) > 0) {
        $categories = array_values(array_unique(array_merge($categories, $lp_cats)));
    }
}

lc_api_success(array(
    'items'      => $items,
    'categories' => $categories,
    'dbReady'    => lc_db_installed(),
));
