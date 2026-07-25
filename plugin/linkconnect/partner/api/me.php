<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
lc_api_require_login();

$partner = lc_get_current_partner();

lc_api_success(array(
    'auth'    => lc_auth_state(),
    'partner' => is_array($partner) ? lc_partner_to_api($partner) : null,
    'dbReady' => lc_db_installed(),
));
