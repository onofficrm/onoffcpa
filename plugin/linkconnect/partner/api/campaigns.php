<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');

if (LC_PARTNER_GUARD_ENABLED && lc_db_installed() && !lc_is_super_admin()) {
    lc_api_require_active_partner();
}

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$type = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : 'cpa';
if ($type === '') {
    $type = 'cpa';
}

$cpa_categories = array('전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타');
$cps_categories = function_exists('lc_campaign_cps_linkprice_categories')
    ? lc_campaign_cps_linkprice_categories()
    : array('전체', '여행/티켓', '종합쇼핑몰', '건강', '패션', '뷰티', '생활/인테리어', '기타');

$items = array();
$categories = $cpa_categories;
$pt_id = 0;

if ($type === 'cps' || $type === 'all') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) ($partner['pt_id'] ?? 0);
}

if ($type === 'cpa' || $type === 'all') {
    $cpa_items = lc_campaign_list_for_api(array(
        'category' => $category,
        'q'        => $q,
    ));
    foreach ($cpa_items as &$row) {
        if (is_array($row) && !isset($row['campaignType'])) {
            $row['campaignType'] = 'cpa';
            $row['type'] = isset($row['type']) && $row['type'] !== '' ? $row['type'] : 'cpa';
        }
    }
    unset($row);
    $items = array_merge($items, $cpa_items);
}

if (($type === 'cps' || $type === 'all') && $pt_id > 0 && function_exists('lc_campaign_cps_partner_for_api')) {
    $cps_items = lc_campaign_cps_partner_for_api($pt_id, array(
        'category' => $category,
        'q'        => $q,
    ));
    $items = $type === 'cps' ? $cps_items : array_merge($items, $cps_items);
}

if ($type === 'cps') {
    $categories = $cps_categories;
} elseif ($type === 'all') {
    $categories = array_values(array_unique(array_merge($cpa_categories, $cps_categories)));
}

lc_api_success(array(
    'items'      => $items,
    'categories' => $categories,
    'dbReady'    => lc_db_installed(),
    'counts'     => array(
        'cpa' => count(array_filter($items, function ($row) {
            return is_array($row) && ($row['campaignType'] ?? $row['type'] ?? '') === 'cpa';
        })),
        'cps' => count(array_filter($items, function ($row) {
            return is_array($row) && ($row['campaignType'] ?? $row['type'] ?? '') === 'cps';
        })),
    ),
));
