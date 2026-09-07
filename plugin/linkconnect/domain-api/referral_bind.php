<?php
/**
 * DOMAIN S2S — bind customer to Core referral code.
 * POST /plugin/linkconnect/domain-api/referral_bind.php
 *
 * Body: { customerMbId, referralCode, sourceReference? }
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_domain_s2s_require('domain.referral.bind');
$body = $auth['body'];

$customer = trim((string) ($body['customerMbId'] ?? $body['customer_mb_id'] ?? ''));
$code = trim((string) ($body['referralCode'] ?? $body['referral_code'] ?? ''));
$source_ref = trim((string) ($body['sourceReference'] ?? $body['source_reference'] ?? ''));

if ($customer === '' || $code === '') {
    lc_api_error('customerMbId and referralCode required', 'INVALID_ARGS', 400);
}

$result = lc_onoff_core_bind_customer($customer, $code, array(
    'source_service'   => 'DOMAIN',
    'source_reference' => $source_ref !== '' ? $source_ref : 'domain.icrm.co.kr',
));

if (empty($result['ok'])) {
    lc_api_error((string) ($result['message'] ?? 'bind_failed'), (string) ($result['code'] ?? 'BIND_FAILED'), 400);
}

lc_api_success(array(
    'bind' => $result,
));
