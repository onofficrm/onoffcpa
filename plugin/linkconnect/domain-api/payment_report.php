<?php
/**
 * DOMAIN S2S — report purchased-point payment for lifetime commission.
 * POST /plugin/linkconnect/domain-api/payment_report.php
 *
 * Body: { paymentId, customerMbId, amount, approvedAt? }
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_domain_s2s_require('domain.payment.report');
$body = $auth['body'];

$payment_id = trim((string) ($body['paymentId'] ?? $body['payment_id'] ?? ''));
$customer = trim((string) ($body['customerMbId'] ?? $body['customer_mb_id'] ?? ''));
$amount = (int) ($body['amount'] ?? 0);
$approved_at = trim((string) ($body['approvedAt'] ?? $body['approved_at'] ?? ''));

if ($payment_id === '' || $customer === '' || $amount <= 0) {
    lc_api_error('paymentId, customerMbId, amount required', 'INVALID_ARGS', 400);
}

$result = lc_onoff_core_record_purchased_point_payment(array(
    'payment_id'     => $payment_id,
    'customer_mb_id' => $customer,
    'amount'         => $amount,
    'source_service' => 'DOMAIN',
    'approved_at'    => $approved_at !== '' ? $approved_at : date('Y-m-d H:i:s'),
));

if (empty($result['ok'])) {
    lc_api_error((string) ($result['message'] ?? 'payment_report_failed'), 'PAYMENT_REPORT_FAILED', 400);
}

lc_api_success(array(
    'payment' => $result,
));
