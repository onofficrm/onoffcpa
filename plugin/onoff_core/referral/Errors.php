<?php
if (!class_exists('OnoffCore_ReferralError', false)) {
    final class OnoffCore_ReferralError
    {
        const REFERRAL_DISABLED = 'REFERRAL_DISABLED';
        const COMMISSION_DISABLED = 'COMMISSION_DISABLED';
        const UNAUTHORIZED = 'UNAUTHORIZED';
        const INVALID_CODE = 'INVALID_CODE';
        const SELF_REFERRAL = 'SELF_REFERRAL';
        const ALREADY_BOUND = 'ALREADY_BOUND';
        const OVERWRITE_FORBIDDEN = 'OVERWRITE_FORBIDDEN';
        const CUSTOMER_NOT_FOUND = 'CUSTOMER_NOT_FOUND';
        const IDEMPOTENCY_CONFLICT = 'IDEMPOTENCY_CONFLICT';
        const COMMISSION_EXISTS = 'COMMISSION_EXISTS';
        const RULE_NOT_FOUND = 'RULE_NOT_FOUND';
        const PAYMENT_NOT_COMMISSIONABLE = 'PAYMENT_NOT_COMMISSIONABLE';

        public static function fail($code, $message = '', array $extra = array())
        {
            $out = array('ok' => false, 'error' => (string) $code, 'message' => $message !== '' ? $message : (string) $code);
            return !empty($extra) ? array_merge($out, $extra) : $out;
        }

        public static function ok(array $data = array())
        {
            return array_merge(array('ok' => true), $data);
        }
    }
}
