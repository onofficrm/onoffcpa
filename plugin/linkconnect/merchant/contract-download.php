<?php
require_once dirname(__DIR__) . '/_common.php';

if (!lc_is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('로그인이 필요합니다.');
}

if (lc_is_super_admin() && !(function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant'))) {
    header('HTTP/1.1 403 Forbidden');
    exit('광고주 계정으로 로그인해 주세요.');
}

$merchant = lc_get_current_merchant();
if (!is_array($merchant)) {
    header('HTTP/1.1 403 Forbidden');
    exit('광고주만 접근할 수 있습니다.');
}

$mt_id = (int) $merchant['mt_id'];
$version_param = isset($_GET['version']) ? trim((string) $_GET['version']) : '';
$version = $version_param !== ''
    ? lc_merchant_contract_sanitize_version($version_param)
    : lc_merchant_contract_current_version();
if ($version === '') {
    header('HTTP/1.1 400 Bad Request');
    exit('유효하지 않은 계약서 버전입니다.');
}

$contract = lc_merchant_contract_get($mt_id, $version);
if (!is_array($contract) || !lc_merchant_contract_assert_merchant_owns($contract, $mt_id)) {
    header('HTTP/1.1 404 Not Found');
    exit('계약서를 찾을 수 없습니다.');
}

if (!lc_merchant_contract_is_row_fully_signed($contract)) {
    header('HTTP/1.1 404 Not Found');
    exit('체결된 계약서가 없습니다.');
}

$relative = (string) ($contract['mc_contract_pdf_path'] ?? '');
$absolute = lc_merchant_contract_absolute_storage_path($relative);
if ($absolute === '' || !is_file($absolute)) {
    header('HTTP/1.1 404 Not Found');
    exit('PDF 파일을 찾을 수 없습니다.');
}

$expected_hash = (string) ($contract['mc_contract_file_hash'] ?? '');
if ($expected_hash !== '') {
    $actual_hash = hash_file('sha256', $absolute);
    if (!hash_equals($expected_hash, $actual_hash)) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('계약서 파일 무결성 검증에 실패했습니다.');
    }
}

$filename = basename($absolute);
lc_merchant_contract_send_file_headers('application/pdf', 'attachment', $filename);
header('Content-Length: ' . (string) filesize($absolute));
readfile($absolute);
exit;
