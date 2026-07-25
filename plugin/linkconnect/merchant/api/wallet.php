<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
        $merchant = lc_api_require_active_merchant();
    } else {
        lc_api_require_login();
        $merchant = lc_get_current_merchant();
    }

    $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;

    lc_api_success(array(
        'balance'          => $mt_id > 0 ? lc_wallet_get_balance($mt_id) : 0,
        'balanceFormatted' => number_format($mt_id > 0 ? lc_wallet_get_balance($mt_id) : 0),
        'summary'          => $mt_id > 0 ? lc_wallet_merchant_summary($mt_id) : array(
            'balance'          => 0,
            'monthlyCharge'    => 0,
            'monthlySpend'     => 0,
            'availableBalance' => 0,
        ),
        'items'            => lc_wallet_list_for_api($mt_id),
        'dbReady'          => lc_db_installed(),
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    $body = lc_api_read_json_body();
    $amount = isset($body['amount']) ? (int) $body['amount'] : 0;
    $memo = isset($body['memo']) ? trim((string) $body['memo']) : '';

    $result = lc_wallet_request_charge($mt_id, $amount, $memo);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'CHARGE_FAILED', 400);
    }

    lc_api_success(array('message' => $result['message']));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
