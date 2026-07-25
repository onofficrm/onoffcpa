<?php
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('POST');

$body = lc_api_read_json_body();
if (!$body && $_POST) {
    $body = $_POST;
}

$lk_code = isset($body['lkCode']) ? trim((string) $body['lkCode']) : (isset($body['lk_code']) ? trim((string) $body['lk_code']) : '');
if ($lk_code === '') {
    lc_api_error('홍보 링크 코드가 필요합니다.', 'INVALID_LINK', 400);
}

$link = lc_link_get_with_campaign($lk_code);
if (!$link || $link['lk_status'] !== 'active' || $link['cp_status'] !== LC_STATUS_ACTIVE) {
    lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
}

$result = lc_conversion_create_from_link($link, array(
    'name'    => isset($body['name']) ? (string) $body['name'] : '',
    'phone'   => isset($body['phone']) ? (string) $body['phone'] : '',
    'email'   => isset($body['email']) ? (string) $body['email'] : '',
    'region'  => isset($body['region']) ? (string) $body['region'] : '',
    'inquiry' => isset($body['inquiry']) ? (string) $body['inquiry'] : '',
    'channel' => isset($body['channel']) ? (string) $body['channel'] : (isset($body['utm_source']) ? (string) $body['utm_source'] : ''),
    'sub_id'  => isset($body['sub_id']) ? (string) $body['sub_id'] : (isset($body['utm_campaign']) ? (string) $body['utm_campaign'] : ''),
));

if (!$result['ok']) {
    $err_code = isset($result['code']) ? (string) $result['code'] : 'CREATE_FAILED';
    if ($err_code === 'DUPLICATE_RECENT') {
        lc_api_success(array(
            'message'   => $result['message'],
            'duplicate' => true,
        ));
    }
    lc_api_error($result['message'], $err_code, 400);
}

lc_api_success(array(
    'message'    => $result['message'],
    'code'       => is_array($result['conversion']) ? (string) $result['conversion']['cv_code'] : '',
    'conversion' => is_array($result['conversion']) ? lc_conversion_to_api_merchant(
        array_merge($result['conversion'], array('cp_name' => $link['cp_name'], 'pt_code' => '')),
        false
    ) : null,
));
