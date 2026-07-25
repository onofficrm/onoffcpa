<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');

if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
    $merchant = lc_api_require_active_merchant();
} else {
    lc_api_require_login();
    $merchant = lc_get_current_merchant();
}

$mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
$status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

$items = array();
if ($mt_id > 0) {
    $items = array_map(
        'lc_campaign_merchant_to_api',
        lc_campaign_list_for_merchant($mt_id, array('status' => $status))
    );
}

lc_api_success(array(
    'items'   => $items,
    'summary' => $mt_id > 0 ? lc_campaign_merchant_summary($mt_id) : array('total' => 0, 'active' => 0, 'pending' => 0, 'ended' => 0),
    'dbReady' => lc_db_installed(),
));
