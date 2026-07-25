<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
lc_api_require_login();

$merchant = lc_get_current_merchant();

lc_api_success(array(
    'auth'     => lc_auth_state(),
    'merchant' => is_array($merchant) ? lc_merchant_to_api($merchant) : null,
    'dbReady'  => lc_db_installed(),
));
