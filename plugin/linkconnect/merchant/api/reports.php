<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
$merchant = lc_api_require_active_merchant();
$mt_id = (int) $merchant['mt_id'];

lc_api_success(array_merge(
    lc_conversion_merchant_report_for_api($mt_id),
    array('dbReady' => lc_db_installed())
));
