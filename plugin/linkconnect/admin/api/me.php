<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
lc_api_require_admin();

lc_api_success(array(
    'auth'    => lc_auth_state(),
    'dbReady' => lc_db_installed(),
));
