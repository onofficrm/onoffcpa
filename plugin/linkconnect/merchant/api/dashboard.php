<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');

$merchant = null;
if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
    $merchant = lc_api_require_active_merchant();
} else {
    lc_api_require_login();
    $merchant = lc_get_current_merchant();
}

$mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;

if ($mt_id > 0) {
    $data = lc_merchant_dashboard_for_api($mt_id);
} else {
    $data = array(
        'balance'          => 0,
        'balanceFormatted' => '0',
        'summary'          => array(
            'pending'       => 0,
            'approved'      => 0,
            'rejected'      => 0,
            'needsAction'   => 0,
            'todayReceived' => 0,
            'todaySpend'    => 0,
        ),
        'wallet'           => array(
            'monthlyCharge'    => 0,
            'monthlySpend'     => 0,
            'availableBalance' => 0,
        ),
        'chart7d'          => array(),
        'recent'           => array(),
        'pendingAction'    => 0,
    );
}

lc_api_success($data);
