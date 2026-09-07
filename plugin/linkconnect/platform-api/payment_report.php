<?php
/**
 * Platform S2S — report purchased-point / sale payment for Core commission.
 * POST /plugin/linkconnect/platform-api/payment_report.php
 *
 * Body: { paymentId, customerMbId, amount, sourceService, approvedAt?, campaignCode? }
 *
 * SEO GEO (CONTENT / CPS-SEO-GEO): report amount=300000 for fixed ₩300,000 commission.
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_platform_s2s_require('platform.payment.report');
$body = $auth['body'];
$client = isset($auth['client']) && is_array($auth['client']) ? $auth['client'] : array();

$payment_id = trim((string) ($body['paymentId'] ?? $body['payment_id'] ?? ''));
$customer = trim((string) ($body['customerMbId'] ?? $body['customer_mb_id'] ?? ''));
$amount = (int) ($body['amount'] ?? 0);
$approved_at = trim((string) ($body['approvedAt'] ?? $body['approved_at'] ?? ''));
$campaign_code = strtoupper(trim((string) ($body['campaignCode'] ?? $body['campaign_code'] ?? '')));
$source_service = lc_platform_s2s_normalize_source_service(
    (string) ($body['sourceService'] ?? $body['source_service'] ?? '')
);

if ($payment_id === '' || $customer === '' || $amount <= 0) {
    lc_api_error('paymentId, customerMbId, amount required', 'INVALID_ARGS', 400);
}
if (strlen($customer) > 20) {
    lc_api_error('customerMbId must be <= 20 chars (G5 mb_id)', 'INVALID_MB_ID', 400);
}
if ($source_service === '') {
    lc_api_error('sourceService required', 'INVALID_SOURCE', 400);
}
if (!lc_platform_s2s_client_allows_source($client, $source_service)) {
    lc_api_error('sourceService not allowed for this client', 'FORBIDDEN_SOURCE', 403);
}

// Fixed CPS-SEO-GEO payout: force amount to 300000 when campaign code says so.
if ($campaign_code === 'CPS-SEO-GEO' || ($source_service === 'CONTENT' && !empty($body['fixedSeoGeo']))) {
    $amount = 300000;
}

$result = lc_onoff_core_record_purchased_point_payment(array(
    'payment_id'     => $payment_id,
    'customer_mb_id' => $customer,
    'amount'         => $amount,
    'source_service' => $source_service,
    'approved_at'    => $approved_at !== '' ? $approved_at : date('Y-m-d H:i:s'),
));

if (empty($result['ok'])) {
    lc_api_error((string) ($result['message'] ?? 'payment_report_failed'), 'PAYMENT_REPORT_FAILED', 400);
}

lc_api_success(array(
    'payment'        => $result,
    'sourceService'  => $source_service,
    'amount'         => $amount,
    'campaignCode'   => $campaign_code,
));
