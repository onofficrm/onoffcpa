<?php
require_once dirname(__DIR__) . '/_common.php';

lc_require_admin_access();

$mc_id = isset($_GET['mcId']) ? (int) $_GET['mcId'] : 0;
if ($mc_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('계약 ID가 필요합니다.');
}

$contract = lc_merchant_contract_get_by_id($mc_id);
if (!is_array($contract)) {
    header('HTTP/1.1 404 Not Found');
    exit('계약서를 찾을 수 없습니다.');
}

$mt_id = (int) ($contract['mc_mt_id'] ?? 0);
if ($mt_id > 0 && function_exists('lc_merchant_contract_custom_ensure_adv0008')) {
    $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
    $code = is_array($merchant) ? strtoupper((string) ($merchant['mt_code'] ?? '')) : '';
    if ($code === 'ADV-0008' || $mt_id === 8) {
        lc_merchant_contract_custom_ensure_adv0008(false);
        $refreshed = lc_merchant_contract_get_by_id($mc_id);
        if (is_array($refreshed)) {
            $contract = $refreshed;
        }
    }
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
    $html = lc_merchant_contract_append_addenda_to_html($html, $mc_id);
}

$styles = function_exists('lc_merchant_contract_document_styles') ? lc_merchant_contract_document_styles() : '';
$title = 'CPA 광고 제휴 계약서';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<meta name="robots" content="noindex,nofollow,noarchive">';
echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
echo '<style>body{margin:0;padding:24px;background:#f1f5f9;}' . $styles . '</style></head><body>';
echo $html;
echo '</body></html>';
exit;
