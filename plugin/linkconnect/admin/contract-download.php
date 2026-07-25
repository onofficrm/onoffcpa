<?php
require_once dirname(__DIR__) . '/_common.php';

if (!function_exists('lc_require_admin_access')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('관리자 모듈이 로드되지 않았습니다.');
}

lc_require_admin_access();

$mc_id = isset($_GET['mcId']) ? (int) $_GET['mcId'] : 0;
if ($mc_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('계약 ID가 필요합니다.');
}

$file = lc_merchant_contract_admin_serve_file($mc_id, 'pdf');
if (empty($file['ok'])) {
    header('HTTP/1.1 404 Not Found');
    exit($file['message']);
}

$absolute = (string) $file['absolute'];
$expected_hash = (string) ($file['hash'] ?? '');
if ($expected_hash !== '') {
    $actual_hash = hash_file('sha256', $absolute);
    if (!hash_equals($expected_hash, $actual_hash)) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('계약서 파일 무결성 검증에 실패했습니다.');
    }
}

$filename = (string) ($file['filename'] ?? basename($absolute));
lc_merchant_contract_send_file_headers('application/pdf', 'attachment', $filename);
header('Content-Length: ' . (string) filesize($absolute));
readfile($absolute);
exit;
