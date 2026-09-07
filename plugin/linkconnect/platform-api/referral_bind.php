<?php
/**
 * Platform S2S — bind customer to Core referral code.
 * POST /plugin/linkconnect/platform-api/referral_bind.php
 *
 * Body: { customerMbId, referralCode, sourceService, sourceReference? }
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_platform_s2s_require('platform.referral.bind');
$body = $auth['body'];
$client = isset($auth['client']) && is_array($auth['client']) ? $auth['client'] : array();

$customer = trim((string) ($body['customerMbId'] ?? $body['customer_mb_id'] ?? ''));
$code = trim((string) ($body['referralCode'] ?? $body['referral_code'] ?? ''));
$source_ref = trim((string) ($body['sourceReference'] ?? $body['source_reference'] ?? ''));
$source_service = lc_platform_s2s_normalize_source_service(
    (string) ($body['sourceService'] ?? $body['source_service'] ?? '')
);

if ($customer === '' || $code === '') {
    lc_api_error('customerMbId and referralCode required', 'INVALID_ARGS', 400);
}
if (strlen($customer) > 20) {
    lc_api_error('customerMbId must be <= 20 chars (G5 mb_id)', 'INVALID_MB_ID', 400);
}
if ($source_service === '') {
    lc_api_error('sourceService required (TRAFFIC|BACKLINK|CONTENT|DOMAIN|GEO|ONOFFCPA)', 'INVALID_SOURCE', 400);
}
if (!lc_platform_s2s_client_allows_source($client, $source_service)) {
    lc_api_error('sourceService not allowed for this client', 'FORBIDDEN_SOURCE', 403);
}

$result = lc_onoff_core_bind_customer($customer, $code, array(
    'source_service'   => $source_service,
    'source_reference' => $source_ref !== '' ? $source_ref : strtolower($source_service) . '.icrm.co.kr',
));

if (empty($result['ok'])) {
    lc_api_error((string) ($result['message'] ?? 'bind_failed'), (string) ($result['code'] ?? 'BIND_FAILED'), 400);
}

lc_api_success(array(
    'bind'           => $result,
    'sourceService'  => $source_service,
));
