<?php
/**
 * ONOFF Referral/Commission C4 feature flags — default fail-safe OFF.
 */
if (!class_exists('OnoffCore_ReferralFeatureFlags', false)) {
    final class OnoffCore_ReferralFeatureFlags
    {
        public static function referralCoreEnabled()
        {
            return self::envBool('ONOFF_REFERRAL_CORE_ENABLED', false);
        }

        public static function commissionEnabled()
        {
            return self::referralCoreEnabled()
                && self::envBool('ONOFF_COMMISSION_ENABLED', false);
        }

        /** @var bool|null */
        private static $testReferralCoreOverride = null;
        /** @var bool|null */
        private static $testCommissionOverride = null;

        public static function forceEnableForTests($referralCore = true, $commission = true)
        {
            self::$testReferralCoreOverride = (bool) $referralCore;
            self::$testCommissionOverride = (bool) $commission;
        }

        public static function resetTestOverrides()
        {
            self::$testReferralCoreOverride = null;
            self::$testCommissionOverride = null;
        }

        private static function envBool($name, $default)
        {
            if ($name === 'ONOFF_REFERRAL_CORE_ENABLED' && self::$testReferralCoreOverride !== null) {
                return self::$testReferralCoreOverride;
            }
            if ($name === 'ONOFF_COMMISSION_ENABLED' && self::$testCommissionOverride !== null) {
                return self::$testCommissionOverride;
            }
            if (defined($name)) {
                return (bool) constant($name);
            }
            $v = getenv($name);
            if ($v === false || $v === null || $v === '') {
                return (bool) $default;
            }
            $v = strtolower(trim((string) $v));
            return in_array($v, array('1', 'true', 'yes', 'on'), true);
        }
    }
}
