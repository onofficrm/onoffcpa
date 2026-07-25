<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    lc_api_success(array(
        'summary'  => lc_settlement_partner_summary($pt_id),
        'items'    => array_map('lc_settlement_to_partner_api', lc_settlement_list_for_partner($pt_id)),
        'dbReady'  => lc_db_installed(),
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $amount = isset($body['amount']) ? (int) $body['amount'] : 0;
    $memo = isset($body['memo']) ? trim((string) $body['memo']) : '';

    $result = lc_settlement_request($pt_id, $amount, array(
        'bankName'    => isset($body['bankName']) ? (string) $body['bankName'] : '',
        'bankAccount' => isset($body['bankAccount']) ? (string) $body['bankAccount'] : '',
        'bankHolder'  => isset($body['bankHolder']) ? (string) $body['bankHolder'] : '',
    ), $memo);

    if (!$result['ok']) {
        lc_api_error($result['message'], 'SETTLEMENT_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'settlement' => $result['settlement'],
        'summary'    => lc_settlement_partner_summary($pt_id),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
