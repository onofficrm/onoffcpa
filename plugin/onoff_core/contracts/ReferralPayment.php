<?php
/**
 * ONOFF Core Phase C1 — Referral / Payment / Commission contract stubs (design only).
 */
if (!class_exists('OnoffCore_ReferralStatus', false)) {
    final class OnoffCore_ReferralStatus
    {
        const PENDING = 'PENDING';
        const LOCKED = 'LOCKED';
        const CANCELLED = 'CANCELLED';

        /**
         * @return string[]
         */
        public static function all()
        {
            return array(self::PENDING, self::LOCKED, self::CANCELLED);
        }

        /**
         * @param string $status
         * @return bool
         */
        public static function isValid($status)
        {
            return in_array((string) $status, self::all(), true);
        }
    }
}

if (!class_exists('OnoffCore_CommissionStatus', false)) {
    final class OnoffCore_CommissionStatus
    {
        const PENDING = 'PENDING';
        const APPROVED = 'APPROVED';
        const AVAILABLE = 'AVAILABLE';
        const PAID = 'PAID';
        const CANCELLED = 'CANCELLED';
        const CLAWBACK = 'CLAWBACK';

        /**
         * @return string[]
         */
        public static function all()
        {
            return array(
                self::PENDING,
                self::APPROVED,
                self::AVAILABLE,
                self::PAID,
                self::CANCELLED,
                self::CLAWBACK,
            );
        }
    }
}

if (!class_exists('OnoffCore_PaymentStatus', false)) {
    final class OnoffCore_PaymentStatus
    {
        const CREATED = 'CREATED';
        const PENDING = 'PENDING';
        const PAID = 'PAID';
        const FAILED = 'FAILED';
        const REFUNDED = 'REFUNDED';

        /**
         * @return string[]
         */
        public static function all()
        {
            return array(self::CREATED, self::PENDING, self::PAID, self::FAILED, self::REFUNDED);
        }
    }
}

if (!class_exists('OnoffCore_ReferralRules', false)) {
    final class OnoffCore_ReferralRules
    {
        /**
         * 1 customer → 1 referrer. Self-referral forbidden.
         *
         * @param string $customer_user_id
         * @param string $referrer_user_id
         * @return array{ok:bool,error?:string}
         */
        public static function validateBind($customer_user_id, $referrer_user_id)
        {
            $c = trim((string) $customer_user_id);
            $r = trim((string) $referrer_user_id);
            if ($c === '' || $r === '') {
                return array('ok' => false, 'error' => 'missing_ids');
            }
            if ($c === $r) {
                return array('ok' => false, 'error' => 'self_referral');
            }
            return array('ok' => true);
        }
    }
}
