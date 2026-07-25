<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
$partner = lc_api_require_active_partner();
$pt_id = (int) $partner['pt_id'];

lc_api_success(array_merge(
    lc_conversion_partner_report_for_api($pt_id),
    array('dbReady' => lc_db_installed())
));
