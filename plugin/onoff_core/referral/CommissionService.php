<?php
require_once dirname(__DIR__) . '/contracts/ReferralPayment.php';
require_once dirname(__DIR__) . '/payment/PaymentStatus.php';
require_once __DIR__ . '/FeatureFlags.php';
require_once __DIR__ . '/Errors.php';
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/MemoryStore.php';
require_once __DIR__ . '/ReferralService.php';
require_once __DIR__ . '/CommissionRuleService.php';

if (!class_exists('OnoffCore_CommissionService', false)) {
    final class OnoffCore_CommissionService
    {
        /** @var OnoffCore_MemoryReferralStore|null */
        private $store;
        private $forceEnabled = false;

        public function __construct($store = null)
        {
            $this->store = $store;
        }

        public function forceEnableForTests()
        {
            $this->forceEnabled = true;
        }

        /**
         * @param array<string,mixed> $paymentRow
         * @return array
         */
        public function onPaymentSucceeded(array $paymentRow)
        {
            if (!$this->isEnabled()) {
                return OnoffCore_ReferralError::ok(array('skipped' => true, 'reason' => 'commission_disabled'));
            }
            if ((string) ($paymentRow['economic_kind'] ?? '') !== OnoffCore_PaymentEconomicKind::PURCHASED_POINT) {
                return OnoffCore_ReferralError::ok(array('skipped' => true, 'reason' => 'not_purchased_point'));
            }
            if ((string) ($paymentRow['status'] ?? '') !== OnoffCore_PaymentCoreStatus::SUCCESS
                && empty($paymentRow['_commission_trigger'])) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::PAYMENT_NOT_COMMISSIONABLE);
            }

            $payment_id = (string) $paymentRow['payment_id'];
            $existing = $this->findByPayment($payment_id);
            if ($existing) {
                $out = OnoffCore_ReferralError::ok(array('commission' => $existing));
                $out['replay'] = true;
                return $out;
            }

            $customer = (string) $paymentRow['mb_id'];
            $refSvc = new OnoffCore_ReferralService($this->store);
            if ($this->forceEnabled) {
                $refSvc->forceEnableForTests();
            }
            $mr = $refSvc->getMemberReferral($customer);
            if (!$mr['ok']) {
                return OnoffCore_ReferralError::ok(array('skipped' => true, 'reason' => 'no_referral'));
            }
            $referral = $mr['member_referral'];
            if ((string) $referral['status'] !== OnoffCore_ReferralStatus::LOCKED) {
                return OnoffCore_ReferralError::ok(array('skipped' => true, 'reason' => 'referral_not_locked'));
            }

            $ruleSvc = new OnoffCore_CommissionRuleService($this->store);
            $resolved = $ruleSvc->resolveRule(
                $referral['referrer_mb_id'],
                $referral['campaign_id'],
                isset($paymentRow['approved_at']) ? $paymentRow['approved_at'] : null
            );
            if (empty($resolved['ok'])) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::RULE_NOT_FOUND);
            }
            $rule = $resolved['rule'];
            $gross = (int) $paymentRow['amount'];
            $rate = (float) $rule['commission_rate'];
            $commissionAmount = (int) floor($gross * $rate);
            $now = date('Y-m-d H:i:s');
            $row = array(
                'commission_id' => self::newCommissionId(),
                'payment_id' => $payment_id,
                'customer_mb_id' => $customer,
                'referrer_mb_id' => (string) $referral['referrer_mb_id'],
                'campaign_id' => (string) $referral['campaign_id'],
                'gross_amount' => $gross,
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'rule_id' => (string) $rule['rule_id'],
                'status' => OnoffCore_CommissionStatus::PENDING,
                'created_at' => $now,
                'available_at' => null,
                'paid_at' => null,
                'cancelled_at' => null,
                'clawback_at' => null,
                'metadata_json' => json_encode(array('source_service' => $paymentRow['source_service'] ?? '')),
            );
            $ins = $this->insertCommissionRow($row);
            if (!$ins['ok']) {
                $dup = $this->findByPayment($payment_id);
                if ($dup) {
                    $out = OnoffCore_ReferralError::ok(array('commission' => $dup));
                    $out['replay'] = true;
                    return $out;
                }
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::IDEMPOTENCY_CONFLICT);
            }
            return OnoffCore_ReferralError::ok(array('commission' => $row));
        }

        /**
         * @param array<string,mixed> $paymentRow
         */
        public function onPaymentRefunded(array $paymentRow, $wasPaidOut = false)
        {
            if (!$this->isEnabled()) {
                return OnoffCore_ReferralError::ok(array('skipped' => true));
            }
            $payment_id = (string) $paymentRow['payment_id'];
            $com = $this->findByPayment($payment_id);
            if (!$com) {
                return OnoffCore_ReferralError::ok(array('skipped' => true, 'reason' => 'no_commission'));
            }
            if (in_array((string) $com['status'], array(OnoffCore_CommissionStatus::CANCELLED, OnoffCore_CommissionStatus::CLAWBACK), true)) {
                $out = OnoffCore_ReferralError::ok(array('commission' => $com));
                $out['replay'] = true;
                return $out;
            }
            $now = date('Y-m-d H:i:s');
            if ($wasPaidOut || (string) $com['status'] === OnoffCore_CommissionStatus::PAID) {
                $com['status'] = OnoffCore_CommissionStatus::CLAWBACK;
                $com['clawback_at'] = $now;
            } else {
                $com['status'] = OnoffCore_CommissionStatus::CANCELLED;
                $com['cancelled_at'] = $now;
            }
            $this->updateCommissionRow((string) $com['commission_id'], $com);
            return OnoffCore_ReferralError::ok(array('commission' => $com));
        }

        public static function newCommissionId()
        {
            try {
                $bytes = random_bytes(16);
            } catch (Exception $e) {
                $bytes = md5(uniqid((string) mt_rand(), true), true);
            }
            return 'ocom_' . bin2hex($bytes);
        }

        private function isEnabled()
        {
            return $this->forceEnabled || OnoffCore_ReferralFeatureFlags::commissionEnabled();
        }

        private function findByPayment($payment_id)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->getCommissionByPayment($payment_id);
            }
            if (!function_exists('sql_fetch')) {
                return null;
            }
            $tbl = OnoffCore_ReferralSchema::tableCommission();
            $pid = sql_escape_string((string) $payment_id);
            return sql_fetch("SELECT * FROM `{$tbl}` WHERE payment_id = '{$pid}' LIMIT 1", false);
        }

        private function insertCommissionRow(array $row)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->insertCommission($row);
            }
            if (!function_exists('sql_query')) {
                return array('ok' => false);
            }
            $tbl = OnoffCore_ReferralSchema::tableCommission();
            $sql = "INSERT INTO `{$tbl}` (`commission_id`,`payment_id`,`customer_mb_id`,`referrer_mb_id`,`campaign_id`,`gross_amount`,`commission_rate`,`commission_amount`,`rule_id`,`status`,`created_at`)
                VALUES ('" . sql_escape_string($row['commission_id']) . "','"
                . sql_escape_string($row['payment_id']) . "','"
                . sql_escape_string($row['customer_mb_id']) . "','"
                . sql_escape_string($row['referrer_mb_id']) . "','"
                . sql_escape_string($row['campaign_id']) . "',"
                . (int) $row['gross_amount'] . ","
                . (float) $row['commission_rate'] . ","
                . (int) $row['commission_amount'] . ",'"
                . sql_escape_string($row['rule_id']) . "','"
                . sql_escape_string($row['status']) . "','"
                . sql_escape_string($row['created_at']) . "')";
            return array('ok' => (bool) sql_query($sql, false));
        }

        private function updateCommissionRow($commission_id, array $row)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->updateCommission($commission_id, $row);
            }
            if (!function_exists('sql_query')) {
                return false;
            }
            $tbl = OnoffCore_ReferralSchema::tableCommission();
            $cid = sql_escape_string((string) $commission_id);
            $sets = array();
            foreach (array('status', 'cancelled_at', 'clawback_at', 'paid_at', 'available_at') as $f) {
                if (!array_key_exists($f, $row)) {
                    continue;
                }
                $v = $row[$f];
                $sets[] = $v === null ? "`{$f}` = NULL" : "`{$f}` = '" . sql_escape_string((string) $v) . "'";
            }
            if (empty($sets)) {
                return true;
            }
            return (bool) sql_query("UPDATE `{$tbl}` SET " . implode(',', $sets) . " WHERE commission_id = '{$cid}' LIMIT 1", false);
        }
    }
}
