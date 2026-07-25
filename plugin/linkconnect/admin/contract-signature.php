<?php
require_once dirname(__DIR__) . '/_common.php';

lc_require_admin_access();

$mc_id = isset($_GET['mcId']) ? (int) $_GET['mcId'] : 0;
if ($mc_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('계약 ID가 필요합니다.');
}

$file = lc_merchant_contract_admin_serve_file($mc_id, 'signature');
if (empty($file['ok'])) {
    header('HTTP/1.1 404 Not Found');
    exit($file['message']);
}

$absolute = (string) $file['absolute'];
lc_merchant_contract_send_file_headers('image/png', 'inline', basename($absolute));
header('Content-Length: ' . (string) filesize($absolute));
readfile($absolute);
exit;
