<?php
require_once dirname(__DIR__) . '/contracts/ReferralPayment.php';
require_once __DIR__ . '/FeatureFlags.php';
require_once __DIR__ . '/Errors.php';
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/MemoryStore.php';

if (!class_exists('OnoffCore_ReferralService', false)) {
    final class OnoffCore_ReferralService
    {
        /** @var OnoffCore_MemoryReferralStore|null */
        private $store;
        private $forceEnabled = false;
        private $actorType = 'system';
        private $actorId = '';

        public function __construct($store = null)
        {
            $this->store = $store;
        }

        public function forceEnableForTests()
        {
            $this->forceEnabled = true;
        }

        public function setAuth($actorType, $actorId)
        {
            $this->actorType = (string) $actorType;
            $this->actorId = (string) $actorId;
        }

        public function createReferralCode($referrer_mb_id, $referral_code, $campaign_id = '')
        {
            if (!$this->isEnabled()) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::REFERRAL_DISABLED);
            }
            $referrer_mb_id = trim((string) $referrer_mb_id);
            $referral_code = strtoupper(trim((string) $referral_code));
            if ($referrer_mb_id === '' || $referral_code === '') {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::INVALID_CODE);
            }
            $now = date('Y-m-d H:i:s');
            $row = array(
                'referral_code' => $referral_code,
                'referrer_mb_id' => $referrer_mb_id,
                'campaign_id' => trim((string) $campaign_id),
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            );
            $ins = $this->insertCodeRow($row);
            if (!$ins['ok']) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::IDEMPOTENCY_CONFLICT);
            }
            return OnoffCore_ReferralError::ok(array('referral_code' => $row));
        }

        public function validateCode($referral_code)
        {
            if (!$this->isEnabled()) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::REFERRAL_DISABLED);
            }
            $row = $this->findCode($referral_code);
            if (!$row || strtoupper((string) $row['status']) !== 'ACTIVE') {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::INVALID_CODE);
            }
            return OnoffCore_ReferralError::ok(array(
                'referral_code' => $row['referral_code'],
                'referrer_mb_id' => $row['referrer_mb_id'],
                'campaign_id' => $row['campaign_id'],
            ));
        }

        /**
         * Signup bind — locks 1 customer → 1 referrer.
         *
         * @param array $context source_service, source_reference, first_click_at
         */
        public function bindOnSignup($customer_mb_id, $referral_code, array $context = array())
        {
            if (!$this->isEnabled()) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::REFERRAL_DISABLED);
            }
            $customer_mb_id = trim((string) $customer_mb_id);
            $valid = $this->validateCode($referral_code);
            if (!$valid['ok']) {
                return $valid;
            }
            $referrer = (string) $valid['referrer_mb_id'];
            $bindCheck = OnoffCore_ReferralRules::validateBind($customer_mb_id, $referrer);
            if (!$bindCheck['ok']) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::SELF_REFERRAL);
            }
            $existing = $this->findMemberReferral($customer_mb_id);
            if ($existing) {
                if ((string) $existing['referral_code'] === strtoupper(trim((string) $referral_code))) {
                    $out = OnoffCore_ReferralError::ok(array('member_referral' => $existing));
                    $out['replay'] = true;
                    return $out;
                }
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::OVERWRITE_FORBIDDEN);
            }
            $now = date('Y-m-d H:i:s');
            $row = array(
                'customer_mb_id' => $customer_mb_id,
                'referrer_mb_id' => $referrer,
                'referral_code' => strtoupper(trim((string) $referral_code)),
                'campaign_id' => (string) ($valid['campaign_id'] ?? ''),
                'source_service' => isset($context['source_service']) ? (string) $context['source_service'] : '',
                'source_reference' => isset($context['source_reference']) ? (string) $context['source_reference'] : '',
                'first_click_at' => isset($context['first_click_at']) ? $context['first_click_at'] : null,
                'registered_at' => $now,
                'locked_at' => $now,
                'status' => OnoffCore_ReferralStatus::LOCKED,
                'created_at' => $now,
                'updated_at' => $now,
            );
            $ins = $this->insertMemberReferralRow($row);
            if (!$ins['ok']) {
                return OnoffCore_ReferralError::fail(OnoffCore_ReferralError::ALREADY_BOUND);
            }
            return OnoffCore_ReferralError::ok(array('member_referral' => $row));
        }

        public function getMemberReferral($customer_mb_id)
        {
            $row = $this->findMemberReferral($customer_mb_id);
            return $row ? OnoffCore_ReferralError::ok(array('member_referral' => $row)) : OnoffCore_ReferralError::fail(OnoffCore_ReferralError::CUSTOMER_NOT_FOUND);
        }

        private function isEnabled()
        {
            return $this->forceEnabled || OnoffCore_ReferralFeatureFlags::referralCoreEnabled();
        }

        private function findCode($referral_code)
        {
            $c = strtoupper(trim((string) $referral_code));
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->getCode($c);
            }
            if (!function_exists('sql_fetch')) {
                return null;
            }
            $tbl = OnoffCore_ReferralSchema::tableReferralCode();
            $cs = sql_escape_string($c);
            return sql_fetch("SELECT * FROM `{$tbl}` WHERE referral_code = '{$cs}' LIMIT 1", false);
        }

        private function findMemberReferral($customer_mb_id)
        {
            $customer_mb_id = trim((string) $customer_mb_id);
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->getMemberReferralByCustomer($customer_mb_id);
            }
            if (!function_exists('sql_fetch')) {
                return null;
            }
            $tbl = OnoffCore_ReferralSchema::tableMemberReferral();
            $mb = sql_escape_string($customer_mb_id);
            return sql_fetch("SELECT * FROM `{$tbl}` WHERE customer_mb_id = '{$mb}' LIMIT 1", false);
        }

        private function insertCodeRow(array $row)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->insertCode($row);
            }
            if (!function_exists('sql_query')) {
                return array('ok' => false);
            }
            $tbl = OnoffCore_ReferralSchema::tableReferralCode();
            $sql = "INSERT INTO `{$tbl}` (`referral_code`,`referrer_mb_id`,`campaign_id`,`status`,`created_at`,`updated_at`) VALUES ('"
                . sql_escape_string($row['referral_code']) . "','"
                . sql_escape_string($row['referrer_mb_id']) . "','"
                . sql_escape_string($row['campaign_id']) . "','"
                . sql_escape_string($row['status']) . "','"
                . sql_escape_string($row['created_at']) . "','"
                . sql_escape_string($row['updated_at']) . "')";
            return array('ok' => (bool) sql_query($sql, false));
        }

        private function insertMemberReferralRow(array $row)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->insertMemberReferral($row);
            }
            if (!function_exists('sql_query')) {
                return array('ok' => false);
            }
            $tbl = OnoffCore_ReferralSchema::tableMemberReferral();
            $fca = $row['first_click_at'] ? "'" . sql_escape_string($row['first_click_at']) . "'" : 'NULL';
            $sql = "INSERT INTO `{$tbl}` (`customer_mb_id`,`referrer_mb_id`,`referral_code`,`campaign_id`,`source_service`,`source_reference`,`first_click_at`,`registered_at`,`locked_at`,`status`,`created_at`,`updated_at`) VALUES ('"
                . sql_escape_string($row['customer_mb_id']) . "','"
                . sql_escape_string($row['referrer_mb_id']) . "','"
                . sql_escape_string($row['referral_code']) . "','"
                . sql_escape_string($row['campaign_id']) . "','"
                . sql_escape_string($row['source_service']) . "','"
                . sql_escape_string($row['source_reference']) . "',{$fca},'"
                . sql_escape_string($row['registered_at']) . "','"
                . sql_escape_string($row['locked_at']) . "','"
                . sql_escape_string($row['status']) . "','"
                . sql_escape_string($row['created_at']) . "','"
                . sql_escape_string($row['updated_at']) . "')";
            return array('ok' => (bool) sql_query($sql, false));
        }
    }
}
