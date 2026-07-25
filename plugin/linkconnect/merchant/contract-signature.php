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

$path = (string) ($contract['mc_signature_file_path'] ?? '');
if ($path === '') {
    header('HTTP/1.1 404 Not Found');
    exit('서명 파일이 없습니다.');
}

$absolute = strpos($path, '/') === 0 ? $path : (LC_PLUGIN_PATH . '/' . ltrim($path, '/'));
if (!is_file($absolute)) {
    header('HTTP/1.1 404 Not Found');
    exit('서명 파일을 찾을 수 없습니다.');
}

lc_merchant_contract_send_file_headers('image/png', 'inline', basename($absolute));
header('Content-Length: ' . (string) filesize($absolute));
readfile($absolute);
exit;
