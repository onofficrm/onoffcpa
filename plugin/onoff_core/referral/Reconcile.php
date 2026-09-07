<?php
require_once __DIR__ . '/Schema.php';
require_once dirname(__DIR__) . '/payment/Schema.php';
require_once dirname(__DIR__) . '/payment/PaymentStatus.php';

if (!class_exists('OnoffCore_CommissionReconcile', false)) {
    final class OnoffCore_CommissionReconcile
    {
        /**
         * @param string $payment_id
         * @return array
         */
        public static function checkPayment($payment_id)
        {
            if (!function_exists('sql_fetch')) {
                return array('ok' => false, 'status' => 'MISMATCH', 'error' => 'db_unavailable');
            }
            $ptbl = OnoffCore_PaymentSchema::tablePayment();
            $ctbl = OnoffCore_ReferralSchema::tableCommission();
            $rtbl = OnoffCore_ReferralSchema::tableMemberReferral();
            $pid = sql_escape_string((string) $payment_id);
            $pay = sql_fetch("SELECT * FROM `{$ptbl}` WHERE payment_id = '{$pid}' LIMIT 1", false);
            if (!$pay || (string) $pay['status'] !== OnoffCore_PaymentCoreStatus::SUCCESS) {
                return array('ok' => false, 'status' => 'MISMATCH', 'error' => 'payment_not_success', 'payment_id' => $payment_id);
            }
            if ((string) $pay['economic_kind'] !== OnoffCore_PaymentEconomicKind::PURCHASED_POINT) {
                return array('ok' => true, 'status' => 'MATCH', 'reason' => 'not_commissionable');
            }
            $mb = sql_escape_string((string) $pay['mb_id']);
            $ref = sql_fetch("SELECT * FROM `{$rtbl}` WHERE customer_mb_id = '{$mb}' LIMIT 1", false);
            $com = sql_fetch("SELECT * FROM `{$ctbl}` WHERE payment_id = '{$pid}' LIMIT 1", false);
            if (!$ref) {
                return array(
                    'ok' => true,
                    'status' => 'MATCH',
                    'payment_id' => $payment_id,
                    'customer' => $pay['mb_id'],
                    'referrer' => '',
                    'paid_amount' => (int) $pay['amount'],
                    'expected_commission' => 0,
                    'actual_commission' => 0,
                    'difference' => 0,
                    'reason' => 'no_referral',
                );
            }
            if (!$com) {
                return array(
                    'ok' => false,
                    'status' => 'MISMATCH',
                    'payment_id' => $payment_id,
                    'customer' => $pay['mb_id'],
                    'referrer' => $ref['referrer_mb_id'],
                    'paid_amount' => (int) $pay['amount'],
                    'expected_commission' => null,
                    'actual_commission' => 0,
                    'difference' => null,
                    'reason' => 'missing_commission',
                );
            }
            $expected = (int) floor((int) $pay['amount'] * (float) $com['commission_rate']);
            $actual = (int) $com['commission_amount'];
            $diff = $actual - $expected;
            $status = ($diff === 0) ? 'MATCH' : 'MISMATCH';
            return array(
                'ok' => $status === 'MATCH',
                'status' => $status,
                'payment_id' => $payment_id,
                'customer' => $pay['mb_id'],
                'referrer' => $ref['referrer_mb_id'],
                'paid_amount' => (int) $pay['amount'],
                'expected_commission' => $expected,
                'actual_commission' => $actual,
                'difference' => $diff,
            );
        }

        /**
         * Memory fixture reconcile.
         */
        public static function checkPaymentMemory(array $payment, $memberReferral, $commission)
        {
            if ((string) $payment['status'] !== OnoffCore_PaymentCoreStatus::SUCCESS) {
                return array('ok' => false, 'status' => 'MISMATCH');
            }
            if ((string) $payment['economic_kind'] !== OnoffCore_PaymentEconomicKind::PURCHASED_POINT) {
                return array('ok' => true, 'status' => 'MATCH', 'reason' => 'not_commissionable');
            }
            if (!$memberReferral) {
                return array('ok' => true, 'status' => 'MATCH', 'reason' => 'no_referral', 'expected_commission' => 0, 'actual_commission' => 0);
            }
            if (!$commission) {
                return array('ok' => false, 'status' => 'MISMATCH', 'reason' => 'missing_commission');
            }
            $expected = (int) floor((int) $payment['amount'] * (float) $commission['commission_rate']);
            $actual = (int) $commission['commission_amount'];
            $diff = $actual - $expected;
            return array(
                'ok' => $diff === 0,
                'status' => $diff === 0 ? 'MATCH' : 'MISMATCH',
                'expected_commission' => $expected,
                'actual_commission' => $actual,
                'difference' => $diff,
            );
        }
    }
}
