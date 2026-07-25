<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    $data = lc_admin_log_list_for_api(array(
        'q'      => isset($_GET['q']) ? (string) $_GET['q'] : '',
        'action' => isset($_GET['action']) ? (string) $_GET['action'] : '',
        'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
    ));

    lc_api_success(array_merge($data, array('dbReady' => lc_db_installed())));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
