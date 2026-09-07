<?php
if (!class_exists('OnoffCore_ReferralSchema', false)) {
    final class OnoffCore_ReferralSchema
    {
        public static function tableReferralCode()
        {
            return (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_') . 'onoff_referral_code';
        }

        public static function tableMemberReferral()
        {
            return (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_') . 'onoff_member_referral';
        }

        public static function tableCommissionRule()
        {
            return (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_') . 'onoff_commission_rule';
        }

        public static function tableCommission()
        {
            return (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_') . 'onoff_commission';
        }

        public static function ensureTables()
        {
            if (!function_exists('sql_query') || !function_exists('sql_fetch')) {
                return false;
            }
            foreach (self::migrationStatements() as $sql) {
                sql_query($sql, false);
            }
            return true;
        }

        /**
         * @return string[]
         */
        public static function migrationStatements()
        {
            $code = self::tableReferralCode();
            $mr = self::tableMemberReferral();
            $rule = self::tableCommissionRule();
            $com = self::tableCommission();
            return array(
                "CREATE TABLE IF NOT EXISTS `{$code}` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `referral_code` VARCHAR(32) NOT NULL,
                    `referrer_mb_id` VARCHAR(20) NOT NULL,
                    `campaign_id` VARCHAR(64) NOT NULL DEFAULT '',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_referral_code` (`referral_code`),
                    KEY `idx_referrer` (`referrer_mb_id`),
                    KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS `{$mr}` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `customer_mb_id` VARCHAR(20) NOT NULL,
                    `referrer_mb_id` VARCHAR(20) NOT NULL,
                    `referral_code` VARCHAR(32) NOT NULL,
                    `campaign_id` VARCHAR(64) NOT NULL DEFAULT '',
                    `source_service` VARCHAR(32) NOT NULL DEFAULT '',
                    `source_reference` VARCHAR(255) NOT NULL DEFAULT '',
                    `first_click_at` DATETIME NULL DEFAULT NULL,
                    `registered_at` DATETIME NOT NULL,
                    `locked_at` DATETIME NOT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'LOCKED',
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_customer` (`customer_mb_id`),
                    KEY `idx_referrer` (`referrer_mb_id`),
                    KEY `idx_code` (`referral_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS `{$rule}` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `rule_id` VARCHAR(64) NOT NULL,
                    `scope` VARCHAR(32) NOT NULL DEFAULT 'GLOBAL',
                    `referrer_mb_id` VARCHAR(20) NOT NULL DEFAULT '',
                    `campaign_id` VARCHAR(64) NOT NULL DEFAULT '',
                    `commission_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
                    `rule_type` VARCHAR(32) NOT NULL DEFAULT 'LIFETIME',
                    `active_from` DATETIME NOT NULL,
                    `active_until` DATETIME NULL DEFAULT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                    `priority` INT NOT NULL DEFAULT 0,
                    `metadata_json` MEDIUMTEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_rule_id` (`rule_id`),
                    KEY `idx_scope_status` (`scope`, `status`),
                    KEY `idx_referrer` (`referrer_mb_id`),
                    KEY `idx_campaign` (`campaign_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS `{$com}` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `commission_id` VARCHAR(64) NOT NULL,
                    `payment_id` VARCHAR(64) NOT NULL,
                    `customer_mb_id` VARCHAR(20) NOT NULL,
                    `referrer_mb_id` VARCHAR(20) NOT NULL,
                    `campaign_id` VARCHAR(64) NOT NULL DEFAULT '',
                    `gross_amount` INT UNSIGNED NOT NULL DEFAULT 0,
                    `commission_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
                    `commission_amount` INT UNSIGNED NOT NULL DEFAULT 0,
                    `rule_id` VARCHAR(64) NOT NULL DEFAULT '',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'PENDING',
                    `created_at` DATETIME NOT NULL,
                    `available_at` DATETIME NULL DEFAULT NULL,
                    `paid_at` DATETIME NULL DEFAULT NULL,
                    `cancelled_at` DATETIME NULL DEFAULT NULL,
                    `clawback_at` DATETIME NULL DEFAULT NULL,
                    `metadata_json` MEDIUMTEXT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_commission_id` (`commission_id`),
                    UNIQUE KEY `uk_payment_id` (`payment_id`),
                    KEY `idx_referrer_status` (`referrer_mb_id`, `status`),
                    KEY `idx_customer` (`customer_mb_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            );
        }
    }
}
