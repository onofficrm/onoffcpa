<?php
/**
 * 다중 플랫폼 — 피어 광고주 잔액 조회
 *
 * POST /plugin/linkconnect/api/platform/wallet_balance.php
 * 인증: X-LC-Platform-Token (발신 플랫폼 outbound_token)
 */
require_once dirname(__DIR__, 2) . '/_common.php';

lc_mp_require_enabled();
lc_api_require_method('POST');

$body = lc_api_read_json_body();

$source_code = isset($body['sourcePlatform']) ? strtoupper(trim((string) $body['sourcePlatform'])) : '';
if ($source_code === '' && isset($_SERVER['HTTP_X_LC_PLATFORM_CODE'])) {
    $source_code = strtoupper(trim((string) $_SERVER['HTTP_X_LC_PLATFORM_CODE']));
}
if ($source_code === '') {
    lc_api_error('sourcePlatform required', 'INVALID_SOURCE', 400);
}

$platform = lc_mp_get_platform_by_code($source_code);
if (!$platform) {
    lc_api_error('unknown platform', 'UNKNOWN_PLATFORM', 404);
}

$expected = trim((string) ($platform['outbound_token'] ?? ''));
if ($expected === '') {
    $expected = trim((string) ($platform['webhook_secret'] ?? ''));
}
$got = isset($_SERVER['HTTP_X_LC_PLATFORM_TOKEN']) ? (string) $_SERVER['HTTP_X_LC_PLATFORM_TOKEN'] : '';
if ($expected === '' || $got === '' || !hash_equals($expected, $got)) {
    lc_api_error('invalid token', 'UNAUTHORIZED', 401);
}

$lookup = array(
    'groupCode'          => isset($body['groupCode']) ? trim((string) $body['groupCode']) : '',
    'externalMerchantId' => isset($body['externalMerchantId']) ? trim((string) $body['externalMerchantId']) : '',
    'mtId'               => isset($body['mtId']) ? (int) $body['mtId'] : 0,
);

$resolved = lc_mp_resolve_local_mt_for_balance_lookup($lookup);
if (empty($resolved['ok'])) {
    lc_api_error((string) ($resolved['message'] ?? 'merchant not found'), 'NOT_FOUND', 404);
}

$mt_id = (int) $resolved['mt_id'];
$balance = function_exists('lc_wallet_get_balance') ? lc_wallet_get_balance($mt_id) : 0;

lc_api_success(array(
    'mtId'         => $mt_id,
    'balance'      => (int) $balance,
    'platformCode' => lc_mp_local_platform_code(),
));
