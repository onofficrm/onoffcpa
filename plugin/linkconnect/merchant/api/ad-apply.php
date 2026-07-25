<?php
require_once __DIR__ . '/_common.php';

lc_merchant_ad_apply_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
    $merchant = lc_api_require_active_merchant();
} else {
    lc_api_require_login();
    $merchant = lc_get_current_merchant();
}

if (!is_array($merchant)) {
    lc_api_error('광고주 등록이 필요합니다.', 'NOT_MERCHANT', 403);
}

if (function_exists('lc_merchant_contract_guard_api_or_fail')) {
    lc_merchant_contract_guard_api_or_fail($merchant);
}

$mt_id = (int) ($merchant['mt_id'] ?? 0);

if ($method === 'GET') {
    $draft = lc_merchant_ad_apply_ensure_draft($mt_id);
    if (!is_array($draft)) {
        lc_api_error('신청서를 준비하지 못했습니다.', 'CREATE_FAILED', 500);
    }
    lc_api_success(array(
        'application' => lc_merchant_ad_apply_to_api($draft, true),
    ));
}

if ($method === 'POST') {
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'save_draft';
    $maa_id = isset($body['maaId']) ? (int) $body['maaId'] : 0;

    if ($maa_id <= 0) {
        $draft = lc_merchant_ad_apply_ensure_draft($mt_id);
        $maa_id = is_array($draft) ? (int) ($draft['maa_id'] ?? 0) : 0;
    }

    if ($action === 'delete_asset') {
        $asset_id = isset($body['assetId']) ? (int) $body['assetId'] : 0;
        $result = lc_merchant_ad_apply_delete_asset($mt_id, $asset_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }
        $row = lc_merchant_ad_apply_get($maa_id);
        lc_api_success(array(
            'message'     => $result['message'],
            'application' => is_array($row) ? lc_merchant_ad_apply_to_api($row, true) : null,
        ));
    }

    $submit = ($action === 'submit');
    $result = lc_merchant_ad_apply_save($mt_id, $maa_id, $body, $submit);
    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    lc_api_success(array(
        'message'     => $result['message'],
        'application' => is_array($result['row']) ? lc_merchant_ad_apply_to_api($result['row'], true) : null,
    ));
}

lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
