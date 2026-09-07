<?php
/**
 * Payment ↔ Commission bridge (flag-guarded).
 */
require_once __DIR__ . '/FeatureFlags.php';
require_once __DIR__ . '/CommissionService.php';

if (!class_exists('OnoffCore_PaymentCommissionBridge', false)) {
    final class OnoffCore_PaymentCommissionBridge
    {
        /** @var bool */
        private static $forceForTests = false;

        public static function forceEnableForTests()
        {
            self::$forceForTests = true;
        }

        /** @var OnoffCore_MemoryReferralStore|null */
        private static $testStore = null;

        public static function setTestStore($store)
        {
            self::$testStore = $store;
        }

        public static function resetTestState()
        {
            self::$forceForTests = false;
            self::$testStore = null;
        }

        private static function makeCommissionService()
        {
            $svc = new OnoffCore_CommissionService(self::$testStore);
            if (self::$forceForTests) {
                $svc->forceEnableForTests();
            }
            return $svc;
        }

        private static function isEnabled()
        {
            return self::$forceForTests || OnoffCore_ReferralFeatureFlags::commissionEnabled();
        }

        /**
         * @param array<string,mixed> $paymentRow
         * @return array{ok:bool,blocking?:bool}
         */
        public static function onPaymentSucceeded(array $paymentRow)
        {
            if (!self::isEnabled()) {
                return array('ok' => true, 'skipped' => true);
            }
            $paymentRow['_commission_trigger'] = true;
            $svc = self::makeCommissionService();
            $res = $svc->onPaymentSucceeded($paymentRow);
            if (!$res['ok']) {
                return array('ok' => false, 'blocking' => true, 'error' => $res['error'] ?? 'commission_failed');
            }
            return array(
                'ok'         => true,
                'commission' => isset($res['commission']) ? $res['commission'] : null,
                'skipped'    => !empty($res['skipped']),
                'reason'     => isset($res['reason']) ? (string) $res['reason'] : '',
            );
        }

        /**
         * @param array<string,mixed> $paymentRow
         */
        public static function onPaymentRefunded(array $paymentRow, $wasPaidOut = false)
        {
            if (!self::isEnabled()) {
                return array('ok' => true, 'skipped' => true);
            }
            $svc = self::makeCommissionService();
            $res = $svc->onPaymentRefunded($paymentRow, $wasPaidOut);
            return array('ok' => !empty($res['ok']), 'commission' => isset($res['commission']) ? $res['commission'] : null);
        }
    }
}
