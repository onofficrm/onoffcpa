<?php
/**
 * 다중 플랫폼 스키마 (mp_*)
 * LC_MULTI_PLATFORM_ENABLED=true 일 때만 CREATE TABLE 실행.
 * 기존 lc_conversions / lc_merchants 스키마는 변경하지 않음.
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_mp_db_table')) {
    function lc_mp_db_table($suffix)
    {
        $suffix = preg_replace('/[^a-z0-9_]/i', '', (string) $suffix);

        return lc_table('mp_' . $suffix);
    }
}

if (!function_exists('lc_mp_db_ensure_schema')) {
    /**
     * @return array{ok:bool,message:string,created?:bool}
     */
    function lc_mp_db_ensure_schema()
    {
        if (!lc_mp_enabled()) {
            return array('ok' => true, 'message' => 'multi-platform disabled — schema skipped', 'created' => false);
        }
        if (!function_exists('lc_db_installed') || !lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB not installed');
        }

        $charset = 'utf8mb4';
        $statements = array();

        $platforms = lc_mp_db_table('platforms');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$platforms}` (
            `platform_id` int unsigned NOT NULL AUTO_INCREMENT,
            `platform_code` varchar(40) NOT NULL,
            `platform_name` varchar(100) NOT NULL DEFAULT '',
            `is_local` tinyint(1) NOT NULL DEFAULT 0,
            `api_base_url` varchar(500) NOT NULL DEFAULT '',
            `webhook_secret` varchar(255) NOT NULL DEFAULT '',
            `outbound_token` varchar(255) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`platform_id`),
            UNIQUE KEY `uk_mp_platform_code` (`platform_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $groups = lc_mp_db_table('advertiser_groups');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$groups}` (
            `group_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `group_code` varchar(40) NOT NULL,
            `display_name` varchar(200) NOT NULL DEFAULT '',
            `business_number` varchar(40) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`group_id`),
            UNIQUE KEY `uk_mp_group_code` (`group_code`),
            KEY `idx_mp_group_biz` (`business_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $memberships = lc_mp_db_table('advertiser_memberships');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$memberships}` (
            `membership_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `group_id` bigint unsigned NOT NULL,
            `platform_id` int unsigned NOT NULL,
            `local_mt_id` int unsigned NOT NULL DEFAULT 0,
            `external_merchant_id` varchar(80) NOT NULL DEFAULT '',
            `external_merchant_code` varchar(80) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`membership_id`),
            UNIQUE KEY `uk_mp_membership_platform_ext` (`platform_id`, `external_merchant_id`),
            KEY `idx_mp_membership_group` (`group_id`),
            KEY `idx_mp_membership_local_mt` (`local_mt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $policies = lc_mp_db_table('management_policies');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$policies}` (
            `policy_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `group_id` bigint unsigned NOT NULL,
            `management_platform_id` int unsigned NOT NULL,
            `reason` varchar(255) NOT NULL DEFAULT '',
            `effective_from` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`policy_id`),
            UNIQUE KEY `uk_mp_policy_group` (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $leads = lc_mp_db_table('lead_refs');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$leads}` (
            `lead_ref_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `group_id` bigint unsigned NOT NULL DEFAULT 0,
            `local_mt_id` int unsigned NOT NULL DEFAULT 0,
            `local_cv_id` bigint unsigned NOT NULL DEFAULT 0,
            `source_platform_id` int unsigned NOT NULL,
            `external_lead_id` varchar(80) NOT NULL,
            `external_campaign_id` varchar(80) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `version` int unsigned NOT NULL DEFAULT 1,
            `sync_status` varchar(20) NOT NULL DEFAULT 'synced',
            `payload_json` mediumtext,
            `last_error` varchar(500) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`lead_ref_id`),
            UNIQUE KEY `uk_mp_lead_source_ext` (`source_platform_id`, `external_lead_id`),
            KEY `idx_mp_lead_local_cv` (`local_cv_id`),
            KEY `idx_mp_lead_mt_status` (`local_mt_id`, `status`),
            KEY `idx_mp_lead_sync` (`sync_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $outbox = lc_mp_db_table('sync_outbox');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$outbox}` (
            `outbox_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `target_platform_id` int unsigned NOT NULL,
            `lead_ref_id` bigint unsigned NOT NULL DEFAULT 0,
            `command` varchar(40) NOT NULL,
            `idempotency_key` varchar(80) NOT NULL,
            `payload_json` mediumtext,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `attempts` int unsigned NOT NULL DEFAULT 0,
            `next_attempt_at` datetime DEFAULT NULL,
            `last_error` varchar(500) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`outbox_id`),
            UNIQUE KEY `uk_mp_outbox_idem` (`idempotency_key`),
            KEY `idx_mp_outbox_status_next` (`status`, `next_attempt_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $inbox = lc_mp_db_table('sync_inbox');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$inbox}` (
            `inbox_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `source_platform_id` int unsigned NOT NULL,
            `event_type` varchar(40) NOT NULL,
            `idempotency_key` varchar(80) NOT NULL,
            `payload_json` mediumtext,
            `status` varchar(20) NOT NULL DEFAULT 'received',
            `processed_at` datetime DEFAULT NULL,
            `last_error` varchar(500) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`inbox_id`),
            UNIQUE KEY `uk_mp_inbox_idem` (`idempotency_key`),
            KEY `idx_mp_inbox_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        $audit = lc_mp_db_table('audit_logs');
        $statements[] = "CREATE TABLE IF NOT EXISTS `{$audit}` (
            `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `action` varchar(80) NOT NULL,
            `payload_json` mediumtext,
            `actor_mb_id` varchar(50) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`audit_id`),
            KEY `idx_mp_audit_action` (`action`),
            KEY `idx_mp_audit_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";

        foreach ($statements as $sql) {
            $ok = lc_sql_query($sql, false);
            if ($ok === false) {
                return array('ok' => false, 'message' => 'mp schema create failed: ' . lc_sql_error());
            }
        }

        lc_mp_db_seed_platforms();

        return array('ok' => true, 'message' => 'mp schema ready', 'created' => true);
    }
}

if (!function_exists('lc_mp_db_seed_platforms')) {
    function lc_mp_db_seed_platforms()
    {
        if (!lc_mp_enabled()) {
            return;
        }
        $table = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($table)) {
            return;
        }

        $seeds = array(
            array(
                'code' => lc_mp_local_platform_code(),
                'name' => (lc_mp_local_platform_code() === 'LINKCONNECT') ? '링크커넥트' : '온오프CPA',
                'local' => 1,
            ),
        );
        // 상대 플랫폼 시드
        $local = lc_mp_local_platform_code();
        if ($local === 'ONOFFCPA') {
            $seeds[] = array(
                'code' => defined('LC_PLATFORM_LINKCONNECT') ? LC_PLATFORM_LINKCONNECT : 'LINKCONNECT',
                'name' => '링크커넥트',
                'local' => 0,
            );
        } else {
            $seeds[] = array(
                'code' => defined('LC_PLATFORM_ONOFFCPA') ? LC_PLATFORM_ONOFFCPA : 'ONOFFCPA',
                'name' => '온오프CPA',
                'local' => 0,
            );
        }

        foreach ($seeds as $seed) {
            $code = lc_sql_escape($seed['code']);
            $exists = sql_fetch(" SELECT platform_id FROM `{$table}` WHERE platform_code = '{$code}' LIMIT 1 ");
            if (is_array($exists) && !empty($exists['platform_id'])) {
                continue;
            }
            $name = lc_sql_escape($seed['name']);
            $is_local = (int) $seed['local'];
            lc_sql_query(" INSERT INTO `{$table}` (`platform_code`, `platform_name`, `is_local`, `status`)
                VALUES ('{$code}', '{$name}', {$is_local}, 'active') ", false);
        }
    }
}
