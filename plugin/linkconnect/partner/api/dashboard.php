<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
$partner = lc_api_require_active_partner();
$pt_id = (int) $partner['pt_id'];

lc_api_success(lc_partner_dashboard_for_api($pt_id));
