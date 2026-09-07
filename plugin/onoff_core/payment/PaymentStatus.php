<?php
/**
 * ONOFF Payment Core C3 status machine.
 *
 * Maps legacy g5_charge_requests.cr_status:
 *   pending  → PENDING (PROCESSING when legacy precredit exists)
 *   approved → SUCCESS
 *   rejected → CANCELLED
 */
if (!class_exists('OnoffCore_PaymentCoreStatus', false)) {
    final class OnoffCore_PaymentCoreStatus
    {
        const PENDING = 'PENDING';
        const PROCESSING = 'PROCESSING';
        const SUCCESS = 'SUCCESS';
        const FAILED = 'FAILED';
        const CANCELLED = 'CANCELLED';
        const REFUNDED = 'REFUNDED';

        /**
         * @return string[]
         */
        public static function all()
        {
            return array(
                self::PENDING,
                self::PROCESSING,
                self::SUCCESS,
                self::FAILED,
                self::CANCELLED,
                self::REFUNDED,
            );
        }

        /**
         * @return string[]
         */
        public static function terminal()
        {
            return array(self::SUCCESS, self::FAILED, self::CANCELLED, self::REFUNDED);
        }

        /**
         * @param string $from
         * @param string $to
         * @return bool
         */
        public static function canTransition($from, $to)
        {
            $from = (string) $from;
            $to = (string) $to;
            if ($from === $to) {
                return true;
            }
            $allowed = array(
                self::PENDING => array(self::PROCESSING, self::SUCCESS, self::FAILED, self::CANCELLED),
                self::PROCESSING => array(self::SUCCESS, self::FAILED, self::CANCELLED),
                self::SUCCESS => array(self::REFUNDED),
                self::FAILED => array(),
                self::CANCELLED => array(),
                self::REFUNDED => array(),
            );
            return isset($allowed[$from]) && in_array($to, $allowed[$from], true);
        }

        /**
         * @param string $cr_status
         * @param bool $precredited
         * @return string
         */
        public static function fromChargeRequestStatus($cr_status, $precredited = false)
        {
            $s = strtolower(trim((string) $cr_status));
            if ($s === 'approved') {
                return self::SUCCESS;
            }
            if ($s === 'rejected') {
                return self::CANCELLED;
            }
            if ($precredited) {
                return self::PROCESSING;
            }
            return self::PENDING;
        }
    }
}

if (!class_exists('OnoffCore_PaymentEconomicKind', false)) {
    final class OnoffCore_PaymentEconomicKind
    {
        const PURCHASED_POINT = 'PURCHASED_POINT';
        const ADMIN_ADJUSTMENT = 'ADMIN_ADJUSTMENT';
        const BONUS = 'BONUS';

        /**
         * @return string[]
         */
        public static function all()
        {
            return array(self::PURCHASED_POINT, self::ADMIN_ADJUSTMENT, self::BONUS);
        }

        /**
         * Commission trigger candidate (C4).
         * @param string $kind
         * @return bool
         */
        public static function isCommissionTrigger($kind)
        {
            return (string) $kind === self::PURCHASED_POINT;
        }
    }
}

if (!class_exists('OnoffCore_PaymentEventType', false)) {
    final class OnoffCore_PaymentEventType
    {
        const CREATED = 'PAYMENT_CREATED';
        const APPROVED = 'PAYMENT_APPROVED';
        const SUCCEEDED = 'PAYMENT_SUCCEEDED';
        const FAILED = 'PAYMENT_FAILED';
        const CANCELLED = 'PAYMENT_CANCELLED';
        const REFUNDED = 'PAYMENT_REFUNDED';
        const DUPLICATE_CALLBACK = 'PAYMENT_DUPLICATE_CALLBACK';
        const IDEMPOTENCY_CONFLICT = 'PAYMENT_IDEMPOTENCY_CONFLICT';
        const WALLET_CREDIT_OK = 'PAYMENT_WALLET_CREDIT_OK';
        const WALLET_CREDIT_FAIL = 'PAYMENT_WALLET_CREDIT_FAIL';
        const WALLET_REFUND_OK = 'PAYMENT_WALLET_REFUND_OK';
        const WALLET_REFUND_FAIL = 'PAYMENT_WALLET_REFUND_FAIL';
    }
}
