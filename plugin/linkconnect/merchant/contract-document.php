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
$mt_code = strtoupper((string) ($merchant['mt_code'] ?? ''));
if (
    ($mt_code === 'ADV-0008' || $mt_id === 8)
    && function_exists('lc_merchant_contract_custom_ensure_adv0008')
) {
    lc_merchant_contract_custom_ensure_adv0008(false);
}

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

$form = lc_merchant_contract_form_from_row($contract);
$html = (string) ($contract['mc_contract_snapshot'] ?? '');
if ($html === '' && function_exists('lc_merchant_contract_render_html')) {
    $html = lc_merchant_contract_render_html(array(
        'company_name'        => $form['companyName'],
        'representative_name' => $form['representativeName'],
        'business_number'     => $form['businessNumber'],
        'company_address'     => $form['companyAddress'],
        'company_phone'       => $form['companyPhone'],
    ), array(
        'negotiatedTerms' => $form['negotiatedTerms'] ?? '',
        'specialClauses'  => $form['specialClauses'] ?? '',
    ));
}
if ($html !== '' && function_exists('lc_merchant_contract_append_addenda_to_html')) {
    $html = lc_merchant_contract_append_addenda_to_html($html, (int) ($contract['mc_id'] ?? 0));
}

$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'preview';
$styles = function_exists('lc_merchant_contract_document_styles') ? lc_merchant_contract_document_styles() : '';
$title = 'CPA 광고 제휴 계약서';

if ($mode === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="cpa-contract.html"');
    echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<style>' . $styles . ' @media print { body { margin: 0; } }</style></head><body>';
    echo $html;
    echo '<script>window.onload=function(){window.print();};</script></body></html>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<meta name="robots" content="noindex,nofollow,noarchive">';
echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
echo '<style>body{margin:0;padding:24px;background:#f1f5f9;}' . $styles . '</style></head><body>';
echo $html;
echo '</body></html>';
exit;
