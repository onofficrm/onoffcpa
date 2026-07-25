<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_table_prefix')) {
    function lc_table_prefix()
    {
        if (defined('G5_TABLE_PREFIX') && (string) G5_TABLE_PREFIX !== '') {
            return (string) G5_TABLE_PREFIX;
        }

        global $g5;

        if (isset($g5['table_prefix']) && (string) $g5['table_prefix'] !== '') {
            return (string) $g5['table_prefix'];
        }

        return 'g5_';
    }
}

if (!function_exists('lc_table')) {
    function lc_table($name)
    {
        return lc_table_prefix() . 'lc_' . ltrim((string) $name, '_');
    }
}

if (!function_exists('lc_mysql_db_name')) {
    function lc_mysql_db_name()
    {
        if (defined('LC_MYSQL_DB') && (string) LC_MYSQL_DB !== '') {
            return (string) LC_MYSQL_DB;
        }

        return 'linkconnect';
    }
}

if (!function_exists('lc_mysql_credentials')) {
    /**
     * @return array{host:string,user:string,pass:string,db:string}
     */
    function lc_mysql_credentials()
    {
        return array(
            'host' => (defined('LC_MYSQL_HOST') && (string) LC_MYSQL_HOST !== '') ? (string) LC_MYSQL_HOST : G5_MYSQL_HOST,
            'user' => (defined('LC_MYSQL_USER') && (string) LC_MYSQL_USER !== '') ? (string) LC_MYSQL_USER : G5_MYSQL_USER,
            'pass' => defined('LC_MYSQL_PASSWORD') ? (string) LC_MYSQL_PASSWORD : G5_MYSQL_PASSWORD,
            'db'   => lc_mysql_db_name(),
        );
    }
}

if (!function_exists('lc_connect_db')) {
    function lc_connect_db()
    {
        global $g5;

        if (isset($g5['lc_connect_db']) && $g5['lc_connect_db']) {
            return $g5['lc_connect_db'];
        }

        $cred = lc_mysql_credentials();
        $link = sql_connect($cred['host'], $cred['user'], $cred['pass'], $cred['db']);
        if (!$link) {
            return null;
        }

        sql_set_charset(G5_DB_CHARSET, $link);
        $g5['lc_connect_db'] = $link;

        return $link;
    }
}

if (!function_exists('lc_uses_gnuboard_db')) {
    function lc_uses_gnuboard_db()
    {
        return defined('G5_MYSQL_DB') && G5_MYSQL_DB === lc_mysql_db_name();
    }
}

if (!function_exists('lc_sql_link')) {
    function lc_sql_link()
    {
        global $g5;

        if (lc_uses_gnuboard_db()) {
            return $g5['connect_db'];
        }

        $link = lc_connect_db();
        if ($link) {
            return $link;
        }

        return $g5['connect_db'];
    }
}

if (!function_exists('lc_db_connection_ok')) {
    function lc_db_connection_ok()
    {
        if (lc_uses_gnuboard_db()) {
            return true;
        }

        return lc_connect_db() !== null;
    }
}

if (!function_exists('lc_sql_query')) {
    function lc_sql_query($sql, $error = G5_DISPLAY_SQL_ERROR)
    {
        return sql_query($sql, $error, lc_sql_link());
    }
}

if (!function_exists('lc_sql_fetch')) {
    function lc_sql_fetch($sql, $error = G5_DISPLAY_SQL_ERROR)
    {
        return sql_fetch($sql, $error, lc_sql_link());
    }
}

if (!function_exists('lc_sql_insert_id')) {
    function lc_sql_insert_id()
    {
        return sql_insert_id(lc_sql_link());
    }
}

if (!function_exists('lc_sql_error')) {
    function lc_sql_error()
    {
        return sql_error_info(lc_sql_link());
    }
}

if (!function_exists('lc_sql_affected_rows')) {
    function lc_sql_affected_rows()
    {
        if (function_exists('get_sql_affected_rows')) {
            return (int) get_sql_affected_rows(lc_sql_link());
        }

        return 0;
    }
}

if (!function_exists('lc_sql_escape')) {
    function lc_sql_escape($value)
    {
        return sql_real_escape_string((string) $value, lc_sql_link());
    }
}

if (!function_exists('lc_sql_begin')) {
    function lc_sql_begin()
    {
        return lc_sql_query('START TRANSACTION', false);
    }
}

if (!function_exists('lc_sql_commit')) {
    function lc_sql_commit()
    {
        return lc_sql_query('COMMIT', false);
    }
}

if (!function_exists('lc_sql_rollback')) {
    function lc_sql_rollback()
    {
        return lc_sql_query('ROLLBACK', false);
    }
}

if (!function_exists('lc_db_table_exists')) {
    function lc_db_table_exists($table_name)
    {
        $table_name = lc_sql_escape($table_name);
        $row = lc_sql_fetch(" SHOW TABLES LIKE '{$table_name}' ", false);

        return is_array($row) && count($row) > 0;
    }
}

if (!function_exists('lc_db_installed')) {
    function lc_db_installed()
    {
        static $installed = null;

        if ($installed !== null) {
            return $installed;
        }

        if (!lc_db_connection_ok()) {
            $installed = false;
            return $installed;
        }

        $installed = lc_db_table_exists(lc_table('partners'))
            && lc_db_table_exists(lc_table('campaigns'))
            && lc_db_table_exists(lc_table('merchants'));

        return $installed;
    }
}

if (!function_exists('lc_db_run_schema')) {
    /**
     * @return array{ok:bool,message:string,tables:array}
     */
    function lc_db_run_schema()
    {
        $tables = array(
            lc_table('partners'),
            lc_table('merchants'),
            lc_table('campaigns'),
            lc_table('links'),
            lc_table('clicks'),
            lc_table('conversions'),
            lc_table('wallet_transactions'),
            lc_table('settlements'),
            lc_table('inquiries'),
            lc_table('settings'),
            lc_table('api_clients'),
            lc_table('api_logs'),
            lc_table('events'),
            lc_table('event_participants'),
            lc_table('event_rewards'),
            lc_table('notifications'),
            lc_table('admin_logs'),
            lc_table('merchant_contracts'),
        );

        $queries = array(
            "CREATE TABLE IF NOT EXISTS `" . lc_table('partners') . "` (
                `pt_id` int unsigned NOT NULL AUTO_INCREMENT,
                `mb_id` varchar(20) NOT NULL,
                `pt_code` varchar(20) NOT NULL,
                `pt_name` varchar(100) NOT NULL DEFAULT '',
                `pt_status` varchar(20) NOT NULL DEFAULT 'pending',
                `pt_bank_name` varchar(50) NOT NULL DEFAULT '',
                `pt_bank_account` varchar(50) NOT NULL DEFAULT '',
                `pt_bank_holder` varchar(50) NOT NULL DEFAULT '',
                `pt_balance` int NOT NULL DEFAULT 0,
                `pt_notify_prefs` text,
                `pt_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `pt_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`pt_id`),
                UNIQUE KEY `uk_mb_id` (`mb_id`),
                UNIQUE KEY `uk_pt_code` (`pt_code`),
                KEY `idx_pt_status` (`pt_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('merchants') . "` (
                `mt_id` int unsigned NOT NULL AUTO_INCREMENT,
                `mb_id` varchar(20) NOT NULL,
                `mt_code` varchar(20) NOT NULL,
                `mt_company` varchar(200) NOT NULL DEFAULT '',
                `mt_status` varchar(20) NOT NULL DEFAULT 'pending',
                `mt_balance` int NOT NULL DEFAULT 0,
                `mt_notify_prefs` text,
                `mt_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `mt_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`mt_id`),
                UNIQUE KEY `uk_mb_id` (`mb_id`),
                UNIQUE KEY `uk_mt_code` (`mt_code`),
                KEY `idx_mt_status` (`mt_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('campaigns') . "` (
                `cp_id` int unsigned NOT NULL AUTO_INCREMENT,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `cp_code` varchar(20) NOT NULL,
                `cp_name` varchar(200) NOT NULL,
                `cp_category` varchar(50) NOT NULL DEFAULT '',
                `cp_type` varchar(10) NOT NULL DEFAULT 'cpa',
                `cp_price` int unsigned NOT NULL DEFAULT 0,
                `cp_merchant_price` int unsigned NOT NULL DEFAULT 0,
                `cp_approval_rate` varchar(10) NOT NULL DEFAULT '',
                `cp_avg_time` varchar(20) NOT NULL DEFAULT '',
                `cp_allowed_channels` varchar(500) NOT NULL DEFAULT '',
                `cp_forbidden_channels` varchar(500) NOT NULL DEFAULT '',
                `cp_description` text,
                `cp_landing_url` varchar(500) NOT NULL DEFAULT '',
                `cp_tracking_base_url` varchar(500) NOT NULL DEFAULT '',
                `cp_status` varchar(20) NOT NULL DEFAULT 'draft',
                `cp_badge` varchar(20) NOT NULL DEFAULT '',
                `cp_recommended` tinyint(1) NOT NULL DEFAULT 0,
                `cp_sort` int NOT NULL DEFAULT 0,
                `cp_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cp_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`cp_id`),
                UNIQUE KEY `uk_cp_code` (`cp_code`),
                KEY `idx_cp_status` (`cp_status`),
                KEY `idx_cp_category` (`cp_category`),
                KEY `idx_mt_id` (`mt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('links') . "` (
                `lk_id` int unsigned NOT NULL AUTO_INCREMENT,
                `pt_id` int unsigned NOT NULL,
                `cp_id` int unsigned NOT NULL,
                `lk_code` varchar(32) NOT NULL,
                `lk_channel` varchar(100) NOT NULL DEFAULT '',
                `lk_sub_id` varchar(100) NOT NULL DEFAULT '',
                `lk_status` varchar(20) NOT NULL DEFAULT 'active',
                `lk_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `lk_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`lk_id`),
                UNIQUE KEY `uk_lk_code` (`lk_code`),
                KEY `idx_pt_id` (`pt_id`),
                KEY `idx_cp_id` (`cp_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('clicks') . "` (
                `cl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `lk_id` int unsigned NOT NULL,
                `pt_id` int unsigned NOT NULL,
                `cp_id` int unsigned NOT NULL,
                `cl_ip` varchar(45) NOT NULL DEFAULT '',
                `cl_user_agent` varchar(500) NOT NULL DEFAULT '',
                `cl_referer` varchar(500) NOT NULL DEFAULT '',
                `cl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`cl_id`),
                KEY `idx_lk_id` (`lk_id`),
                KEY `idx_pt_created` (`pt_id`, `cl_created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('conversions') . "` (
                `cv_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `cv_code` varchar(20) NOT NULL,
                `pt_id` int unsigned NOT NULL,
                `cp_id` int unsigned NOT NULL,
                `lk_id` int unsigned NOT NULL DEFAULT 0,
                `cv_name` varchar(100) NOT NULL DEFAULT '',
                `cv_phone` varchar(20) NOT NULL DEFAULT '',
                `cv_email` varchar(100) NOT NULL DEFAULT '',
                `cv_region` varchar(50) NOT NULL DEFAULT '',
                `cv_inquiry` varchar(500) NOT NULL DEFAULT '',
                `cv_status` varchar(20) NOT NULL DEFAULT 'pending',
                `cv_price` int NOT NULL DEFAULT 0,
                `cv_partner_price` int NOT NULL DEFAULT 0,
                `cv_channel` varchar(100) NOT NULL DEFAULT '',
                `cv_sub_id` varchar(100) NOT NULL DEFAULT '',
                `cv_comment` varchar(500) NOT NULL DEFAULT '',
                `cv_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cv_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`cv_id`),
                UNIQUE KEY `uk_cv_code` (`cv_code`),
                KEY `idx_pt_status` (`pt_id`, `cv_status`),
                KEY `idx_cp_id` (`cp_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('wallet_transactions') . "` (
                `wt_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `mt_id` int unsigned NOT NULL,
                `wt_type` varchar(20) NOT NULL DEFAULT 'charge',
                `wt_amount` int NOT NULL DEFAULT 0,
                `wt_balance_after` int NOT NULL DEFAULT 0,
                `wt_status` varchar(20) NOT NULL DEFAULT 'completed',
                `wt_memo` varchar(500) NOT NULL DEFAULT '',
                `wt_ref_type` varchar(20) NOT NULL DEFAULT '',
                `wt_ref_id` bigint unsigned NOT NULL DEFAULT 0,
                `wt_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`wt_id`),
                KEY `idx_mt_created` (`mt_id`, `wt_created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('settlements') . "` (
                `st_id` int unsigned NOT NULL AUTO_INCREMENT,
                `st_code` varchar(20) NOT NULL,
                `pt_id` int unsigned NOT NULL,
                `st_amount` int NOT NULL DEFAULT 0,
                `st_approved_amount` int NOT NULL DEFAULT 0,
                `st_status` varchar(20) NOT NULL DEFAULT 'pending',
                `st_bank_name` varchar(50) NOT NULL DEFAULT '',
                `st_bank_account` varchar(50) NOT NULL DEFAULT '',
                `st_bank_holder` varchar(50) NOT NULL DEFAULT '',
                `st_memo` varchar(500) NOT NULL DEFAULT '',
                `st_requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `st_processed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`st_id`),
                UNIQUE KEY `uk_st_code` (`st_code`),
                KEY `idx_pt_id` (`pt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('inquiries') . "` (
                `iq_id` int unsigned NOT NULL AUTO_INCREMENT,
                `iq_code` varchar(20) NOT NULL,
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `mb_id` varchar(20) NOT NULL DEFAULT '',
                `iq_center` varchar(20) NOT NULL DEFAULT '',
                `iq_category` varchar(50) NOT NULL DEFAULT '',
                `iq_subject` varchar(200) NOT NULL DEFAULT '',
                `iq_body` text,
                `iq_campaign` varchar(200) NOT NULL DEFAULT '',
                `iq_cv_code` varchar(30) NOT NULL DEFAULT '',
                `iq_contact_name` varchar(100) NOT NULL DEFAULT '',
                `iq_contact_email` varchar(120) NOT NULL DEFAULT '',
                `iq_contact_phone` varchar(40) NOT NULL DEFAULT '',
                `iq_attachment_path` varchar(500) NOT NULL DEFAULT '',
                `iq_attachment_name` varchar(255) NOT NULL DEFAULT '',
                `iq_attachment_mime` varchar(120) NOT NULL DEFAULT '',
                `iq_reply` text,
                `iq_admin_memo` varchar(500) NOT NULL DEFAULT '',
                `iq_status` varchar(20) NOT NULL DEFAULT 'waiting',
                `iq_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `iq_replied_at` datetime DEFAULT NULL,
                PRIMARY KEY (`iq_id`),
                UNIQUE KEY `uk_iq_code` (`iq_code`),
                KEY `idx_pt_id` (`pt_id`),
                KEY `idx_mt_id` (`mt_id`),
                KEY `idx_iq_status` (`iq_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('settings') . "` (
                `st_key` varchar(100) NOT NULL,
                `st_value` text,
                `st_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`st_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('api_clients') . "` (
                `ac_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ac_code` varchar(30) NOT NULL,
                `ac_name` varchar(100) NOT NULL DEFAULT '',
                `ac_type` varchar(30) NOT NULL DEFAULT 'landing',
                `ac_mt_id` int unsigned NOT NULL DEFAULT 0,
                `ac_api_key` varchar(64) NOT NULL DEFAULT '',
                `ac_api_secret` varchar(64) NOT NULL DEFAULT '',
                `ac_allowed_ips` varchar(500) NOT NULL DEFAULT '',
                `ac_status` varchar(20) NOT NULL DEFAULT 'active',
                `ac_last_call_at` datetime DEFAULT NULL,
                `ac_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ac_id`),
                UNIQUE KEY `uk_ac_code` (`ac_code`),
                UNIQUE KEY `uk_ac_api_key` (`ac_api_key`),
                KEY `idx_ac_mt_id` (`ac_mt_id`),
                KEY `idx_ac_type` (`ac_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('api_logs') . "` (
                `al_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ac_id` int unsigned NOT NULL DEFAULT 0,
                `al_client_name` varchar(100) NOT NULL DEFAULT '',
                `al_direction` varchar(30) NOT NULL DEFAULT 'receive',
                `al_endpoint` varchar(200) NOT NULL DEFAULT '',
                `al_ext_id` varchar(100) NOT NULL DEFAULT '',
                `al_int_code` varchar(30) NOT NULL DEFAULT '',
                `al_status_code` int NOT NULL DEFAULT 200,
                `al_status` varchar(30) NOT NULL DEFAULT 'success',
                `al_error` varchar(500) NOT NULL DEFAULT '',
                `al_request_body` text,
                `al_response_body` text,
                `al_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`al_id`),
                KEY `idx_ac_id` (`ac_id`),
                KEY `idx_al_created_at` (`al_created_at`),
                KEY `idx_al_status` (`al_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('events') . "` (
                `ev_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_code` varchar(20) NOT NULL,
                `ev_title` varchar(200) NOT NULL,
                `ev_type` varchar(50) NOT NULL DEFAULT '',
                `ev_desc` text,
                `ev_period` varchar(100) NOT NULL DEFAULT '',
                `ev_product` varchar(200) NOT NULL DEFAULT '',
                `ev_benefit` varchar(200) NOT NULL DEFAULT '',
                `ev_badges` varchar(500) NOT NULL DEFAULT '',
                `ev_ribbon` varchar(50) NOT NULL DEFAULT '',
                `ev_status` varchar(20) NOT NULL DEFAULT 'active',
                `ev_target` varchar(20) NOT NULL DEFAULT 'partner',
                `ev_campaign_ids` varchar(500) NOT NULL DEFAULT '',
                `ev_campaign_labels` varchar(500) NOT NULL DEFAULT '',
                `ev_featured` tinyint(1) NOT NULL DEFAULT 0,
                `ev_sort` int NOT NULL DEFAULT 0,
                `ev_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ev_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ev_id`),
                UNIQUE KEY `uk_ev_code` (`ev_code`),
                KEY `idx_ev_status` (`ev_status`),
                KEY `idx_ev_sort` (`ev_sort`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('event_participants') . "` (
                `ep_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_id` int unsigned NOT NULL,
                `pt_id` int unsigned NOT NULL,
                `ep_status` varchar(20) NOT NULL DEFAULT 'joined',
                `ep_approved_cnt` int NOT NULL DEFAULT 0,
                `ep_joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ep_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ep_id`),
                UNIQUE KEY `uk_ev_pt` (`ev_id`, `pt_id`),
                KEY `idx_ep_ev_id` (`ev_id`),
                KEY `idx_ep_pt_id` (`pt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `" . lc_table('event_rewards') . "` (
                `er_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_id` int unsigned NOT NULL DEFAULT 0,
                `pt_id` int unsigned NOT NULL,
                `er_amount` int NOT NULL DEFAULT 0,
                `er_status` varchar(20) NOT NULL DEFAULT 'pending',
                `er_condition` varchar(200) NOT NULL DEFAULT '',
                `er_memo` varchar(500) NOT NULL DEFAULT '',
                `er_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `er_paid_at` datetime DEFAULT NULL,
                PRIMARY KEY (`er_id`),
                KEY `idx_er_ev_id` (`ev_id`),
                KEY `idx_er_pt_id` (`pt_id`),
                KEY `idx_er_status` (`er_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            lc_merchant_contract_create_table_sql(),
        );

        foreach ($queries as $sql) {
            $result = lc_sql_query($sql, false);
            if ($result === false) {
                return array(
                    'ok'      => false,
                    'message' => '테이블 생성 실패: ' . lc_sql_error(),
                    'tables'  => array(),
                );
            }
        }

        $migrate = lc_db_run_migrations();
        if (!$migrate['ok']) {
            return $migrate;
        }

        return array(
            'ok'      => true,
            'message' => 'LinkConnect DB 스키마가 설치되었습니다.',
            'tables'  => $tables,
        );
    }
}

if (!function_exists('lc_db_column_exists')) {
    function lc_db_column_exists($table_name, $column_name)
    {
        $table_name = lc_sql_escape($table_name);
        $column_name = lc_sql_escape($column_name);
        $row = lc_sql_fetch(" SHOW COLUMNS FROM `{$table_name}` LIKE '{$column_name}' ", false);

        return is_array($row) && count($row) > 0;
    }
}

if (!function_exists('lc_db_run_migrations')) {
    /**
     * @return array{ok:bool,message:string,tables?:array}
     */
    function lc_db_run_migrations()
    {
        $campaigns = lc_table('campaigns');
        $conversions = lc_table('conversions');
        $inquiries = lc_table('inquiries');
        $settlements = lc_table('settlements');

        $alters = array();

        if (lc_db_table_exists($campaigns) && !lc_db_column_exists($campaigns, 'mt_id')) {
            $alters[] = "ALTER TABLE `{$campaigns}` ADD COLUMN `mt_id` int unsigned NOT NULL DEFAULT 0 AFTER `cp_id`, ADD KEY `idx_mt_id` (`mt_id`)";
        }

        if (lc_db_table_exists($campaigns) && !lc_db_column_exists($campaigns, 'cp_merchant_price')) {
            $alters[] = "ALTER TABLE `{$campaigns}` ADD COLUMN `cp_merchant_price` int unsigned NOT NULL DEFAULT 0 AFTER `cp_price`";
        }

        if (lc_db_table_exists($campaigns) && !lc_db_column_exists($campaigns, 'cp_thumbnail')) {
            $alters[] = "ALTER TABLE `{$campaigns}` ADD COLUMN `cp_thumbnail` varchar(500) NOT NULL DEFAULT '' AFTER `cp_landing_url`";
        }

        if (lc_db_table_exists($campaigns) && !lc_db_column_exists($campaigns, 'cp_tracking_base_url')) {
            $after = lc_db_column_exists($campaigns, 'cp_thumbnail') ? 'cp_thumbnail' : 'cp_landing_url';
            $alters[] = "ALTER TABLE `{$campaigns}` ADD COLUMN `cp_tracking_base_url` varchar(500) NOT NULL DEFAULT '' AFTER `{$after}`";
        }

        if (lc_db_table_exists($conversions) && !lc_db_column_exists($conversions, 'cv_partner_price')) {
            $alters[] = "ALTER TABLE `{$conversions}` ADD COLUMN `cv_partner_price` int NOT NULL DEFAULT 0 AFTER `cv_price`";
        }

        foreach (array(
            'cv_email'   => "varchar(100) NOT NULL DEFAULT '' AFTER `cv_phone`",
            'cv_region'  => "varchar(50) NOT NULL DEFAULT '' AFTER `cv_email`",
            'cv_inquiry' => "varchar(500) NOT NULL DEFAULT '' AFTER `cv_region`",
        ) as $col => $definition) {
            if (lc_db_table_exists($conversions) && !lc_db_column_exists($conversions, $col)) {
                $alters[] = "ALTER TABLE `{$conversions}` ADD COLUMN `{$col}` {$definition}";
            }
        }

        if (lc_db_table_exists($inquiries) && !lc_db_column_exists($inquiries, 'mt_id')) {
            $alters[] = "ALTER TABLE `{$inquiries}` ADD COLUMN `mt_id` int unsigned NOT NULL DEFAULT 0 AFTER `pt_id`";
        }

        foreach (array(
            'iq_center'        => "varchar(20) NOT NULL DEFAULT '' AFTER `mb_id`",
            'iq_campaign'      => "varchar(200) NOT NULL DEFAULT '' AFTER `iq_body`",
            'iq_cv_code'       => "varchar(30) NOT NULL DEFAULT '' AFTER `iq_campaign`",
            'iq_contact_name'  => "varchar(100) NOT NULL DEFAULT '' AFTER `iq_cv_code`",
            'iq_contact_email' => "varchar(120) NOT NULL DEFAULT '' AFTER `iq_contact_name`",
            'iq_contact_phone' => "varchar(40) NOT NULL DEFAULT '' AFTER `iq_contact_email`",
            'iq_reply'         => "text AFTER `iq_contact_phone`",
            'iq_admin_memo'    => "varchar(500) NOT NULL DEFAULT '' AFTER `iq_reply`",
            'iq_replied_at'    => "datetime DEFAULT NULL AFTER `iq_status`",
        ) as $col => $definition) {
            if (lc_db_table_exists($inquiries) && !lc_db_column_exists($inquiries, $col)) {
                $alters[] = "ALTER TABLE `{$inquiries}` ADD COLUMN `{$col}` {$definition}";
            }
        }

        if (lc_db_table_exists($settlements) && !lc_db_column_exists($settlements, 'st_approved_amount')) {
            $alters[] = "ALTER TABLE `{$settlements}` ADD COLUMN `st_approved_amount` int NOT NULL DEFAULT 0 AFTER `st_amount`";
        }

        if (lc_db_table_exists($settlements) && !lc_db_column_exists($settlements, 'st_memo')) {
            $alters[] = "ALTER TABLE `{$settlements}` ADD COLUMN `st_memo` varchar(500) NOT NULL DEFAULT '' AFTER `st_bank_holder`";
        }

        $partners = lc_table('partners');
        $merchants = lc_table('merchants');
        if (lc_db_table_exists($partners) && !lc_db_column_exists($partners, 'pt_notify_prefs')) {
            $alters[] = "ALTER TABLE `{$partners}` ADD COLUMN `pt_notify_prefs` text AFTER `pt_balance`";
        }
        if (lc_db_table_exists($merchants) && !lc_db_column_exists($merchants, 'mt_notify_prefs')) {
            $alters[] = "ALTER TABLE `{$merchants}` ADD COLUMN `mt_notify_prefs` text AFTER `mt_balance`";
        }

        $nf = lc_table('notifications');
        if (lc_db_table_exists($nf) && !lc_db_column_exists($nf, 'nf_priority')) {
            $alters[] = "ALTER TABLE `{$nf}` ADD COLUMN `nf_priority` varchar(20) NOT NULL DEFAULT 'normal' AFTER `nf_type`, ADD KEY `idx_nf_priority` (`nf_priority`)";
        }

        $api_clients = lc_table('api_clients');
        if (lc_db_table_exists($api_clients) && !lc_db_column_exists($api_clients, 'ac_mt_id')) {
            $alters[] = "ALTER TABLE `{$api_clients}` ADD COLUMN `ac_mt_id` int unsigned NOT NULL DEFAULT 0 AFTER `ac_type`, ADD KEY `idx_ac_mt_id` (`ac_mt_id`)";
        }

        foreach (array(
            'cv_review_status' => "varchar(20) NOT NULL DEFAULT '' AFTER `cv_comment`",
            'cv_reject_reason' => "varchar(100) NOT NULL DEFAULT '' AFTER `cv_review_status`",
            'cv_partner_appeal' => "varchar(500) NOT NULL DEFAULT '' AFTER `cv_reject_reason`",
            'cv_quality_score' => "tinyint unsigned NOT NULL DEFAULT 0 AFTER `cv_partner_appeal`",
            'cv_quality_tags' => "varchar(200) NOT NULL DEFAULT '' AFTER `cv_quality_score`",
            'cv_partner_visible' => "tinyint(1) NOT NULL DEFAULT 1 AFTER `cv_quality_tags`",
            'cv_ip' => "varchar(45) NOT NULL DEFAULT '' AFTER `cv_partner_visible`",
            'cv_abuse_score' => "tinyint unsigned NOT NULL DEFAULT 0 AFTER `cv_ip`",
            'cv_is_duplicate' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `cv_abuse_score`",
            'cv_source' => "varchar(20) NOT NULL DEFAULT 'form' AFTER `cv_is_duplicate`",
            'cv_call_id' => "bigint unsigned NOT NULL DEFAULT 0 AFTER `cv_source`",
            'cv_call_duration' => "int unsigned NOT NULL DEFAULT 0 AFTER `cv_call_id`",
            'cv_call_result' => "varchar(20) NOT NULL DEFAULT '' AFTER `cv_call_duration`",
            'cv_final_status' => "varchar(20) NOT NULL DEFAULT '' AFTER `cv_call_result`",
            'cv_final_locked' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `cv_final_status`",
        ) as $col => $definition) {
            if (lc_db_table_exists($conversions) && !lc_db_column_exists($conversions, $col)) {
                $alters[] = "ALTER TABLE `{$conversions}` ADD COLUMN `{$col}` {$definition}";
            }
        }

        $partners = lc_table('partners');
        foreach (array(
            'pt_admin_memo' => "varchar(500) NOT NULL DEFAULT '' AFTER `pt_balance`",
            'pt_admin_tags' => "varchar(200) NOT NULL DEFAULT '' AFTER `pt_admin_memo`",
            'pt_assigned_mb_id' => "varchar(20) NOT NULL DEFAULT '' AFTER `pt_admin_tags`",
            'pt_abuse_score' => "tinyint unsigned NOT NULL DEFAULT 0 AFTER `pt_assigned_mb_id`",
            'pt_tier' => "varchar(20) NOT NULL DEFAULT 'bronze' AFTER `pt_abuse_score`",
        ) as $col => $definition) {
            if (lc_db_table_exists($partners) && !lc_db_column_exists($partners, $col)) {
                $alters[] = "ALTER TABLE `{$partners}` ADD COLUMN `{$col}` {$definition}";
            }
        }

        $merchants = lc_table('merchants');
        foreach (array(
            'mt_admin_memo' => "varchar(500) NOT NULL DEFAULT '' AFTER `mt_balance`",
            'mt_admin_tags' => "varchar(200) NOT NULL DEFAULT '' AFTER `mt_admin_memo`",
            'mt_assigned_mb_id' => "varchar(20) NOT NULL DEFAULT '' AFTER `mt_admin_tags`",
            'mt_abuse_score' => "tinyint unsigned NOT NULL DEFAULT 0 AFTER `mt_assigned_mb_id`",
        ) as $col => $definition) {
            if (lc_db_table_exists($merchants) && !lc_db_column_exists($merchants, $col)) {
                $alters[] = "ALTER TABLE `{$merchants}` ADD COLUMN `{$col}` {$definition}";
            }
        }

        foreach ($alters as $sql) {
            $result = lc_sql_query($sql, false);
            if ($result === false) {
                return array(
                    'ok'      => false,
                    'message' => '마이그레이션 실패: ' . lc_sql_error(),
                );
            }
        }

        if (lc_db_table_exists($campaigns) && lc_db_column_exists($campaigns, 'cp_merchant_price')) {
            lc_sql_query(" UPDATE `{$campaigns}` SET cp_merchant_price = cp_price WHERE cp_merchant_price = 0 AND cp_price > 0 ", false);
        }

        if (lc_db_table_exists($conversions) && lc_db_column_exists($conversions, 'cv_partner_price')) {
            lc_sql_query(" UPDATE `{$conversions}` SET cv_partner_price = cv_price WHERE cv_partner_price = 0 AND cv_price > 0 ", false);
        }

        $events = lc_table('events');
        if (!lc_db_table_exists($events)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$events}` (
                `ev_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_code` varchar(20) NOT NULL,
                `ev_title` varchar(200) NOT NULL,
                `ev_type` varchar(50) NOT NULL DEFAULT '',
                `ev_desc` text,
                `ev_period` varchar(100) NOT NULL DEFAULT '',
                `ev_product` varchar(200) NOT NULL DEFAULT '',
                `ev_benefit` varchar(200) NOT NULL DEFAULT '',
                `ev_badges` varchar(500) NOT NULL DEFAULT '',
                `ev_ribbon` varchar(50) NOT NULL DEFAULT '',
                `ev_status` varchar(20) NOT NULL DEFAULT 'active',
                `ev_target` varchar(20) NOT NULL DEFAULT 'partner',
                `ev_campaign_ids` varchar(500) NOT NULL DEFAULT '',
                `ev_campaign_labels` varchar(500) NOT NULL DEFAULT '',
                `ev_featured` tinyint(1) NOT NULL DEFAULT 0,
                `ev_sort` int NOT NULL DEFAULT 0,
                `ev_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ev_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ev_id`),
                UNIQUE KEY `uk_ev_code` (`ev_code`),
                KEY `idx_ev_status` (`ev_status`),
                KEY `idx_ev_sort` (`ev_sort`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

            if ($create === false) {
                return array(
                    'ok'      => false,
                    'message' => 'events 테이블 생성 실패: ' . lc_sql_error(),
                );
            }
        }

        $ep = lc_table('event_participants');
        if (!lc_db_table_exists($ep)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$ep}` (
                `ep_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_id` int unsigned NOT NULL,
                `pt_id` int unsigned NOT NULL,
                `ep_status` varchar(20) NOT NULL DEFAULT 'joined',
                `ep_approved_cnt` int NOT NULL DEFAULT 0,
                `ep_joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ep_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ep_id`),
                UNIQUE KEY `uk_ev_pt` (`ev_id`, `pt_id`),
                KEY `idx_ep_ev_id` (`ev_id`),
                KEY `idx_ep_pt_id` (`pt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

            if ($create === false) {
                return array(
                    'ok'      => false,
                    'message' => 'event_participants 테이블 생성 실패: ' . lc_sql_error(),
                );
            }
        }

        $er = lc_table('event_rewards');
        if (!lc_db_table_exists($er)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$er}` (
                `er_id` int unsigned NOT NULL AUTO_INCREMENT,
                `ev_id` int unsigned NOT NULL DEFAULT 0,
                `pt_id` int unsigned NOT NULL,
                `er_amount` int NOT NULL DEFAULT 0,
                `er_status` varchar(20) NOT NULL DEFAULT 'pending',
                `er_condition` varchar(200) NOT NULL DEFAULT '',
                `er_memo` varchar(500) NOT NULL DEFAULT '',
                `er_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `er_paid_at` datetime DEFAULT NULL,
                PRIMARY KEY (`er_id`),
                KEY `idx_er_ev_id` (`ev_id`),
                KEY `idx_er_pt_id` (`pt_id`),
                KEY `idx_er_status` (`er_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

            if ($create === false) {
                return array(
                    'ok'      => false,
                    'message' => 'event_rewards 테이블 생성 실패: ' . lc_sql_error(),
                );
            }
        }

        $nf = lc_table('notifications');
        if (!lc_db_table_exists($nf)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$nf}` (
                `nf_id` int unsigned NOT NULL AUTO_INCREMENT,
                `nf_center` varchar(20) NOT NULL DEFAULT 'admin',
                `nf_user_id` int unsigned NOT NULL DEFAULT 0,
                `nf_type` varchar(30) NOT NULL DEFAULT 'system',
                `nf_priority` varchar(20) NOT NULL DEFAULT 'normal',
                `nf_title` varchar(200) NOT NULL DEFAULT '',
                `nf_body` varchar(500) NOT NULL DEFAULT '',
                `nf_link` varchar(200) NOT NULL DEFAULT '',
                `nf_ref_type` varchar(30) NOT NULL DEFAULT '',
                `nf_ref_id` int unsigned NOT NULL DEFAULT 0,
                `nf_read_at` datetime DEFAULT NULL,
                `nf_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`nf_id`),
                KEY `idx_nf_center_user` (`nf_center`, `nf_user_id`),
                KEY `idx_nf_type` (`nf_type`),
                KEY `idx_nf_priority` (`nf_priority`),
                KEY `idx_nf_read` (`nf_read_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

            if ($create === false) {
                return array(
                    'ok'      => false,
                    'message' => 'notifications 테이블 생성 실패: ' . lc_sql_error(),
                );
            }
        }

        $alog = lc_table('admin_logs');
        if (!lc_db_table_exists($alog)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$alog}` (
                `alog_id` int unsigned NOT NULL AUTO_INCREMENT,
                `mb_id` varchar(20) NOT NULL DEFAULT '',
                `alog_action` varchar(50) NOT NULL DEFAULT '',
                `alog_target_type` varchar(30) NOT NULL DEFAULT '',
                `alog_target_id` int unsigned NOT NULL DEFAULT 0,
                `alog_summary` varchar(500) NOT NULL DEFAULT '',
                `alog_payload` text,
                `alog_ip` varchar(45) NOT NULL DEFAULT '',
                `alog_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`alog_id`),
                KEY `idx_alog_action` (`alog_action`),
                KEY `idx_alog_mb_id` (`mb_id`),
                KEY `idx_alog_created` (`alog_created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

            if ($create === false) {
                return array(
                    'ok'      => false,
                    'message' => 'admin_logs 테이블 생성 실패: ' . lc_sql_error(),
                );
            }
        }

        $ilog = lc_table('impersonate_logs');
        if (!lc_db_table_exists($ilog)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$ilog}` (
                `ilog_id` int unsigned NOT NULL AUTO_INCREMENT,
                `mb_id` varchar(20) NOT NULL DEFAULT '',
                `ilog_type` varchar(20) NOT NULL DEFAULT '',
                `ilog_target_id` int unsigned NOT NULL DEFAULT 0,
                `ilog_label` varchar(200) NOT NULL DEFAULT '',
                `ilog_started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ilog_ended_at` datetime DEFAULT NULL,
                PRIMARY KEY (`ilog_id`),
                KEY `idx_ilog_mb_id` (`mb_id`),
                KEY `idx_ilog_type_target` (`ilog_type`, `ilog_target_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'impersonate_logs 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $cr = lc_table('channel_reports');
        if (!lc_db_table_exists($cr)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$cr}` (
                `cr_id` int unsigned NOT NULL AUTO_INCREMENT,
                `cv_id` int unsigned NOT NULL DEFAULT 0,
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `cr_channel` varchar(100) NOT NULL DEFAULT '',
                `cr_reason` varchar(500) NOT NULL DEFAULT '',
                `cr_status` varchar(20) NOT NULL DEFAULT 'pending',
                `cr_admin_memo` varchar(500) NOT NULL DEFAULT '',
                `cr_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cr_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`cr_id`),
                KEY `idx_cr_status` (`cr_status`),
                KEY `idx_cr_pt_id` (`pt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'channel_reports 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        // ── 콜디비 ──────────────────────────────────────────────
        $call_numbers = lc_table('call_numbers');
        if (!lc_db_table_exists($call_numbers)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$call_numbers}` (
                `cn_id` int unsigned NOT NULL AUTO_INCREMENT,
                `cn_number` varchar(30) NOT NULL,
                `cn_provider` varchar(50) NOT NULL DEFAULT '',
                `cn_provider_number_id` varchar(100) NOT NULL DEFAULT '',
                `cn_status` varchar(20) NOT NULL DEFAULT 'available',
                `cn_memo` varchar(500) NOT NULL DEFAULT '',
                `cn_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cn_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`cn_id`),
                UNIQUE KEY `uk_cn_number` (`cn_number`),
                KEY `idx_cn_status` (`cn_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'call_numbers 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $call_settings = lc_table('call_settings');
        if (!lc_db_table_exists($call_settings)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$call_settings}` (
                `cs_id` int unsigned NOT NULL AUTO_INCREMENT,
                `cp_id` int unsigned NOT NULL,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `cs_enabled` tinyint(1) NOT NULL DEFAULT 0,
                `cs_alias` varchar(200) NOT NULL DEFAULT '',
                `cs_forward1` varchar(30) NOT NULL DEFAULT '',
                `cs_forward2` varchar(30) NOT NULL DEFAULT '',
                `cs_admin_enabled` tinyint(1) NOT NULL DEFAULT 1,
                `cs_recording_mode` varchar(20) NOT NULL DEFAULT 'normal',
                `cs_coloring` varchar(100) NOT NULL DEFAULT '',
                `cs_call_ment` varchar(200) NOT NULL DEFAULT '',
                `cs_business_start` varchar(5) NOT NULL DEFAULT '00:00',
                `cs_business_end` varchar(5) NOT NULL DEFAULT '23:59',
                `cs_holiday_weeks` varchar(50) NOT NULL DEFAULT '',
                `cs_holiday_days` varchar(50) NOT NULL DEFAULT '',
                `cs_price` int unsigned NOT NULL DEFAULT 0,
                `cs_min_duration` int unsigned NOT NULL DEFAULT 0,
                `cs_memo` varchar(500) NOT NULL DEFAULT '',
                `cs_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cs_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`cs_id`),
                UNIQUE KEY `uk_cs_cp_id` (`cp_id`),
                KEY `idx_cs_mt_id` (`mt_id`),
                KEY `idx_cs_enabled` (`cs_enabled`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'call_settings 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $call_requests = lc_table('call_requests');
        if (!lc_db_table_exists($call_requests)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$call_requests}` (
                `car_id` int unsigned NOT NULL AUTO_INCREMENT,
                `pt_id` int unsigned NOT NULL,
                `cp_id` int unsigned NOT NULL,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `car_status` varchar(20) NOT NULL DEFAULT 'pending',
                `cn_id` int unsigned NOT NULL DEFAULT 0,
                `car_virtual_number` varchar(30) NOT NULL DEFAULT '',
                `car_request_memo` varchar(500) NOT NULL DEFAULT '',
                `car_admin_memo` varchar(500) NOT NULL DEFAULT '',
                `car_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `car_processed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`car_id`),
                KEY `idx_car_pt_id` (`pt_id`),
                KEY `idx_car_cp_id` (`cp_id`),
                KEY `idx_car_status` (`car_status`),
                KEY `idx_car_number` (`car_virtual_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'call_requests 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $call_logs = lc_table('call_logs');
        if (!lc_db_table_exists($call_logs)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$call_logs}` (
                `clog_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `clog_provider_call_id` varchar(100) NOT NULL DEFAULT '',
                `cn_id` int unsigned NOT NULL DEFAULT 0,
                `car_id` int unsigned NOT NULL DEFAULT 0,
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `cp_id` int unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `clog_virtual_number` varchar(30) NOT NULL DEFAULT '',
                `clog_caller` varchar(30) NOT NULL DEFAULT '',
                `clog_callee` varchar(30) NOT NULL DEFAULT '',
                `clog_started_at` datetime DEFAULT NULL,
                `clog_duration` int unsigned NOT NULL DEFAULT 0,
                `clog_result` varchar(20) NOT NULL DEFAULT '',
                `clog_recording_url` varchar(500) NOT NULL DEFAULT '',
                `clog_recording_id` varchar(100) NOT NULL DEFAULT '',
                `cv_id` bigint unsigned NOT NULL DEFAULT 0,
                `clog_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`clog_id`),
                UNIQUE KEY `uk_clog_provider_call_id` (`clog_provider_call_id`),
                KEY `idx_clog_number` (`clog_virtual_number`),
                KEY `idx_clog_pt_id` (`pt_id`),
                KEY `idx_clog_cp_id` (`cp_id`),
                KEY `idx_clog_started` (`clog_started_at`),
                KEY `idx_clog_cv_id` (`cv_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'call_logs 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $call_recording_requests = lc_table('call_recording_requests');
        if (!lc_db_table_exists($call_recording_requests)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$call_recording_requests}` (
                `crr_id` int unsigned NOT NULL AUTO_INCREMENT,
                `clog_id` bigint unsigned NOT NULL,
                `requester_type` varchar(20) NOT NULL DEFAULT 'partner',
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `crr_status` varchar(20) NOT NULL DEFAULT 'pending',
                `crr_request_memo` varchar(500) NOT NULL DEFAULT '',
                `crr_admin_memo` varchar(500) NOT NULL DEFAULT '',
                `crr_file_path` varchar(500) NOT NULL DEFAULT '',
                `crr_original_name` varchar(255) NOT NULL DEFAULT '',
                `crr_requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `crr_processed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`crr_id`),
                KEY `idx_crr_clog_id` (`clog_id`),
                KEY `idx_crr_status` (`crr_status`),
                KEY `idx_crr_pt_id` (`pt_id`),
                KEY `idx_crr_mt_id` (`mt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'call_recording_requests 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        // ── 광고주 CPA 계약서 (기존 merchants 테이블과 분리) ──
        $mc = lc_merchant_contract_db_ensure_schema();
        if (empty($mc['ok'])) {
            return $mc;
        }

        $mc_logs = lc_merchant_contract_log_db_ensure_schema();
        if (empty($mc_logs['ok'])) {
            return $mc_logs;
        }

        if (function_exists('lc_merchant_contract_status_log_db_ensure_schema')) {
            $mc_status_logs = lc_merchant_contract_status_log_db_ensure_schema();
            if (empty($mc_status_logs['ok'])) {
                return $mc_status_logs;
            }
        }

        if (function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
            $mc_addenda = lc_merchant_contract_addendum_db_ensure_schema();
            if (empty($mc_addenda['ok'])) {
                return $mc_addenda;
            }
        }

        // ── 광고상품 홍보 가이드 (campaigns 테이블과 분리) ──
        if (function_exists('lc_campaign_promo_guide_db_ensure_schema')) {
            $cpg = lc_campaign_promo_guide_db_ensure_schema();
            if (empty($cpg['ok'])) {
                return $cpg;
            }
        }

        if (function_exists('lc_merchant_ad_apply_db_ensure_schema')) {
            $maa = lc_merchant_ad_apply_db_ensure_schema();
            if (empty($maa['ok'])) {
                return $maa;
            }
        }

        // ── 링크프라이스 CPS (CPA 테이블과 완전 분리) ──
        if (function_exists('lc_lp_db_ensure_schema')) {
            $lp = lc_lp_db_ensure_schema();
            if (empty($lp['ok'])) {
                return $lp;
            }
        }

        return array('ok' => true, 'message' => '마이그레이션 완료');
    }
}

if (!function_exists('lc_merchant_contract_create_table_sql')) {
    function lc_merchant_contract_create_table_sql()
    {
        $table = lc_table('merchant_contracts');

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
                `mc_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `mc_mt_id` int unsigned NOT NULL DEFAULT 0,
                `mc_contract_version` varchar(50) NOT NULL DEFAULT '',
                `mc_contract_code` varchar(50) NOT NULL DEFAULT '',
                `mc_status` varchar(20) NOT NULL DEFAULT 'pending',
                `mc_company_name` varchar(200) NOT NULL DEFAULT '',
                `mc_representative_name` varchar(100) NOT NULL DEFAULT '',
                `mc_business_number` varchar(20) NOT NULL DEFAULT '',
                `mc_company_address` varchar(500) NOT NULL DEFAULT '',
                `mc_company_phone` varchar(30) NOT NULL DEFAULT '',
                `mc_signer_name` varchar(100) NOT NULL DEFAULT '',
                `mc_signer_position` varchar(100) NOT NULL DEFAULT '',
                `mc_signer_phone` varchar(30) NOT NULL DEFAULT '',
                `mc_signer_email` varchar(200) NOT NULL DEFAULT '',
                `mc_signature_file_path` varchar(500) NOT NULL DEFAULT '',
                `mc_signed_at` datetime DEFAULT NULL,
                `mc_signed_ip` varchar(45) NOT NULL DEFAULT '',
                `mc_user_agent` varchar(500) NOT NULL DEFAULT '',
                `mc_contract_pdf_path` varchar(500) NOT NULL DEFAULT '',
                `mc_contract_file_hash` varchar(128) NOT NULL DEFAULT '',
                `mc_company_snapshot` longtext,
                `mc_contract_snapshot` longtext,
                `mc_agreement_snapshot` longtext,
                `mc_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `mc_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`mc_id`),
                UNIQUE KEY `uk_mc_mt_version` (`mc_mt_id`, `mc_contract_version`),
                KEY `idx_mc_contract_code` (`mc_contract_code`),
                KEY `idx_mc_mt_id` (`mc_mt_id`),
                KEY `idx_mc_status` (`mc_status`),
                KEY `idx_mc_signed_at` (`mc_signed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_merchant_contract_db_ensure_schema')) {
    /**
     * 광고주 계약 테이블 생성 (IF NOT EXISTS)
     *
     * @return array{ok:bool,message:string}
     */
    function lc_merchant_contract_db_ensure_schema()
    {
        $table = lc_table('merchant_contracts');
        if (!lc_db_table_exists($table)) {
            $create = lc_sql_query(lc_merchant_contract_create_table_sql(), false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'merchant_contracts 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        if (lc_db_table_exists($table) && !lc_db_column_exists($table, 'mc_contract_code')) {
            $alter = lc_sql_query("ALTER TABLE `{$table}` ADD COLUMN `mc_contract_code` varchar(50) NOT NULL DEFAULT '' AFTER `mc_contract_version`, ADD KEY `idx_mc_contract_code` (`mc_contract_code`)", false);
            if ($alter === false) {
                return array('ok' => false, 'message' => 'merchant_contracts mc_contract_code 컬럼 추가 실패: ' . lc_sql_error());
            }
        }

        if (lc_db_table_exists($table) && !lc_db_column_exists($table, 'mc_negotiated_terms')) {
            $alter = lc_sql_query(
                "ALTER TABLE `{$table}` ADD COLUMN `mc_negotiated_terms` mediumtext NULL AFTER `mc_agreement_snapshot`",
                false
            );
            if ($alter === false) {
                return array('ok' => false, 'message' => 'merchant_contracts mc_negotiated_terms 컬럼 추가 실패: ' . lc_sql_error());
            }
        }
        if (lc_db_table_exists($table) && !lc_db_column_exists($table, 'mc_special_clauses')) {
            $alter = lc_sql_query(
                "ALTER TABLE `{$table}` ADD COLUMN `mc_special_clauses` mediumtext NULL AFTER `mc_negotiated_terms`",
                false
            );
            if ($alter === false) {
                return array('ok' => false, 'message' => 'merchant_contracts mc_special_clauses 컬럼 추가 실패: ' . lc_sql_error());
            }
        }

        return array('ok' => true, 'message' => 'merchant_contracts 테이블 준비 완료');
    }
}

if (!function_exists('lc_merchant_contract_log_db_ensure_schema')) {
    function lc_merchant_contract_log_db_ensure_schema()
    {
        $table = lc_table('merchant_contract_logs');
        if (lc_db_table_exists($table)) {
            return array('ok' => true, 'message' => 'merchant_contract_logs 테이블 준비 완료');
        }

        $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `mcl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `mc_id` bigint unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `mcl_contract_code` varchar(50) NOT NULL DEFAULT '',
                `mcl_contract_version` varchar(50) NOT NULL DEFAULT '',
                `mcl_signed_at` datetime DEFAULT NULL,
                `mcl_ip` varchar(45) NOT NULL DEFAULT '',
                `mcl_user_agent` varchar(500) NOT NULL DEFAULT '',
                `mcl_pdf_path` varchar(500) NOT NULL DEFAULT '',
                `mcl_pdf_hash` varchar(128) NOT NULL DEFAULT '',
                `mcl_result` varchar(20) NOT NULL DEFAULT 'success',
                `mcl_message` varchar(500) NOT NULL DEFAULT '',
                `mcl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`mcl_id`),
                KEY `idx_mcl_mt_id` (`mt_id`),
                KEY `idx_mcl_mc_id` (`mc_id`),
                KEY `idx_mcl_contract_code` (`mcl_contract_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

        if ($create === false) {
            return array('ok' => false, 'message' => 'merchant_contract_logs 테이블 생성 실패: ' . lc_sql_error());
        }

        return array('ok' => true, 'message' => 'merchant_contract_logs 테이블 준비 완료');
    }
}

if (!function_exists('lc_lp_db_ensure_schema')) {
    /**
     * 링크프라이스 CPS 전용 테이블 생성 (IF NOT EXISTS)
     *
     * @return array{ok:bool,message:string}
     */
    function lc_lp_db_ensure_schema()
    {
        $charset = 'utf8mb4';

        $networks = lc_table('lp_networks');
        if (!lc_db_table_exists($networks)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$networks}` (
                `network_id` int unsigned NOT NULL AUTO_INCREMENT,
                `network_code` varchar(30) NOT NULL DEFAULT 'LINKPRICE',
                `network_name` varchar(100) NOT NULL DEFAULT '링크프라이스',
                `affiliate_code` varchar(20) NOT NULL DEFAULT '',
                `api_auth_key` varchar(64) NOT NULL DEFAULT '',
                `postback_secret` varchar(64) NOT NULL DEFAULT '',
                `api_enabled` tinyint(1) NOT NULL DEFAULT 0,
                `default_partner_rate` decimal(5,2) NOT NULL DEFAULT 70.00,
                `last_merchant_sync_at` datetime DEFAULT NULL,
                `last_order_sync_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`network_id`),
                UNIQUE KEY `uk_lp_network_code` (`network_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_networks 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $merchants = lc_table('lp_merchants');
        if (!lc_db_table_exists($merchants)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$merchants}` (
                `lpm_id` int unsigned NOT NULL AUTO_INCREMENT,
                `network_id` int unsigned NOT NULL DEFAULT 0,
                `merchant_code` varchar(20) NOT NULL DEFAULT '',
                `merchant_name` varchar(200) NOT NULL DEFAULT '',
                `merchant_logo` varchar(500) NOT NULL DEFAULT '',
                `merchant_url` varchar(500) NOT NULL DEFAULT '',
                `category_id` varchar(10) NOT NULL DEFAULT '',
                `category_name` varchar(100) NOT NULL DEFAULT '',
                `commission_pc` varchar(50) NOT NULL DEFAULT '',
                `commission_mobile` varchar(50) NOT NULL DEFAULT '',
                `click_url` varchar(1000) NOT NULL DEFAULT '',
                `deeplink_yn` char(1) NOT NULL DEFAULT 'N',
                `mobile_yn` char(1) NOT NULL DEFAULT 'Y',
                `return_day` int unsigned NOT NULL DEFAULT 0,
                `reward_yn` char(1) NOT NULL DEFAULT '',
                `subscript` varchar(10) NOT NULL DEFAULT '',
                `deny_ad` text,
                `deny_product` text,
                `notice` text,
                `when_trans` varchar(200) NOT NULL DEFAULT '',
                `trans_reposition` varchar(200) NOT NULL DEFAULT '',
                `commission_payment_standard` varchar(200) NOT NULL DEFAULT '',
                `visible` tinyint(1) NOT NULL DEFAULT 1,
                `sync_active` tinyint(1) NOT NULL DEFAULT 1,
                `partner_rate` decimal(5,2) NOT NULL DEFAULT 70.00,
                `campaign_alias` varchar(200) NOT NULL DEFAULT '',
                `partner_notice` text,
                `is_recommended` tinyint(1) NOT NULL DEFAULT 0,
                `admin_memo` varchar(500) NOT NULL DEFAULT '',
                `raw_json` mediumtext,
                `synced_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpm_id`),
                UNIQUE KEY `uk_lp_merchant_code` (`merchant_code`),
                KEY `idx_lp_merchant_network` (`network_id`),
                KEY `idx_lp_merchant_visible` (`visible`),
                KEY `idx_lp_merchant_sync_active` (`sync_active`),
                KEY `idx_lp_merchant_subscript` (`subscript`),
                KEY `idx_lp_merchant_category` (`category_id`),
                KEY `idx_lp_merchant_recommended` (`is_recommended`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_merchants 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        // 기존 lp_merchants 에 관리자 전용 컬럼 보강
        if (lc_db_table_exists($merchants)) {
            foreach (array(
                'sync_active'    => "tinyint(1) NOT NULL DEFAULT 1 AFTER `visible`",
                'campaign_alias' => "varchar(200) NOT NULL DEFAULT '' AFTER `partner_rate`",
                'partner_notice' => "text AFTER `campaign_alias`",
                'is_recommended' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `partner_notice`",
                'admin_memo'     => "varchar(500) NOT NULL DEFAULT '' AFTER `is_recommended`",
            ) as $col => $definition) {
                if (!lc_db_column_exists($merchants, $col)) {
                    lc_sql_query(" ALTER TABLE `{$merchants}` ADD COLUMN `{$col}` {$definition} ", false);
                }
            }
        }

        $clicks = lc_table('lp_clicks');
        if (!lc_db_table_exists($clicks)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$clicks}` (
                `lpc_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `click_token` varchar(32) NOT NULL DEFAULT '',
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `lpm_id` int unsigned NOT NULL DEFAULT 0,
                `u_id` varchar(80) NOT NULL DEFAULT '',
                `landing_url` varchar(1000) NOT NULL DEFAULT '',
                `redirect_url` varchar(1500) NOT NULL DEFAULT '',
                `ip` varchar(45) NOT NULL DEFAULT '',
                `user_agent` varchar(500) NOT NULL DEFAULT '',
                `referer` varchar(500) NOT NULL DEFAULT '',
                `device` varchar(20) NOT NULL DEFAULT '',
                `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpc_id`),
                UNIQUE KEY `uk_lp_click_token` (`click_token`),
                KEY `idx_lp_click_pt` (`pt_id`),
                KEY `idx_lp_click_lpm` (`lpm_id`),
                KEY `idx_lp_click_uid` (`u_id`),
                KEY `idx_lp_click_at` (`clicked_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_clicks 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $postbacks = lc_table('lp_postbacks');
        if (!lc_db_table_exists($postbacks)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$postbacks}` (
                `lpp_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `trlog_id` varchar(30) NOT NULL DEFAULT '',
                `uniq_id` varchar(30) NOT NULL DEFAULT '',
                `merchant_code` varchar(20) NOT NULL DEFAULT '',
                `order_code` varchar(100) NOT NULL DEFAULT '',
                `u_id` varchar(80) NOT NULL DEFAULT '',
                `request_json` mediumtext,
                `request_headers` text,
                `request_ip` varchar(45) NOT NULL DEFAULT '',
                `is_duplicate` tinyint(1) NOT NULL DEFAULT 0,
                `process_status` varchar(20) NOT NULL DEFAULT 'received',
                `error_message` varchar(500) NOT NULL DEFAULT '',
                `match_note` varchar(255) NOT NULL DEFAULT '',
                `lpo_id` bigint unsigned NOT NULL DEFAULT 0,
                `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `processed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`lpp_id`),
                KEY `idx_lp_pb_trlog` (`trlog_id`),
                KEY `idx_lp_pb_uniq` (`uniq_id`),
                KEY `idx_lp_pb_status` (`process_status`),
                KEY `idx_lp_pb_received` (`received_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_postbacks 테이블 생성 실패: ' . lc_sql_error());
            }
        }
        if (lc_db_table_exists($postbacks)) {
            foreach (array(
                'merchant_code' => "varchar(20) NOT NULL DEFAULT '' AFTER `uniq_id`",
                'order_code'    => "varchar(100) NOT NULL DEFAULT '' AFTER `merchant_code`",
                'u_id'          => "varchar(80) NOT NULL DEFAULT '' AFTER `order_code`",
                'match_note'    => "varchar(255) NOT NULL DEFAULT '' AFTER `error_message`",
            ) as $col => $definition) {
                if (!lc_db_column_exists($postbacks, $col)) {
                    lc_sql_query(" ALTER TABLE `{$postbacks}` ADD COLUMN `{$col}` {$definition} ", false);
                }
            }
        }

        $orders = lc_table('lp_orders');
        if (!lc_db_table_exists($orders)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$orders}` (
                `lpo_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `trlog_id` varchar(30) NOT NULL DEFAULT '',
                `uniq_id` varchar(30) NOT NULL DEFAULT '',
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `lpm_id` int unsigned NOT NULL DEFAULT 0,
                `lpc_id` bigint unsigned NOT NULL DEFAULT 0,
                `u_id` varchar(80) NOT NULL DEFAULT '',
                `merchant_code` varchar(20) NOT NULL DEFAULT '',
                `order_code` varchar(100) NOT NULL DEFAULT '',
                `product_code` varchar(100) NOT NULL DEFAULT '',
                `product_name` varchar(300) NOT NULL DEFAULT '',
                `item_count` int unsigned NOT NULL DEFAULT 1,
                `sales_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
                `lp_commission` decimal(14,2) NOT NULL DEFAULT 0.00,
                `partner_rate` decimal(5,2) NOT NULL DEFAULT 70.00,
                `partner_expected` decimal(14,2) NOT NULL DEFAULT 0.00,
                `partner_confirmed` decimal(14,2) NOT NULL DEFAULT 0.00,
                `platform_margin` decimal(14,2) NOT NULL DEFAULT 0.00,
                `raw_status` varchar(10) NOT NULL DEFAULT '',
                `lc_status` varchar(20) NOT NULL DEFAULT 'expected',
                `occurred_at` datetime DEFAULT NULL,
                `confirmed_at` datetime DEFAULT NULL,
                `cancelled_at` datetime DEFAULT NULL,
                `last_synced_at` datetime DEFAULT NULL,
                `raw_json` mediumtext,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpo_id`),
                UNIQUE KEY `uk_lp_order_trlog` (`trlog_id`),
                KEY `idx_lp_order_uniq` (`uniq_id`),
                KEY `idx_lp_order_pt` (`pt_id`),
                KEY `idx_lp_order_lpm` (`lpm_id`),
                KEY `idx_lp_order_uid` (`u_id`),
                KEY `idx_lp_order_code` (`order_code`),
                KEY `idx_lp_order_merchant_order_uid` (`merchant_code`, `order_code`, `u_id`),
                KEY `idx_lp_order_status` (`lc_status`),
                KEY `idx_lp_order_raw_status` (`raw_status`),
                KEY `idx_lp_order_occurred` (`occurred_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_orders 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $status_logs = lc_table('lp_order_status_logs');
        if (!lc_db_table_exists($status_logs)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$status_logs}` (
                `lpsl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `lpo_id` bigint unsigned NOT NULL DEFAULT 0,
                `from_status` varchar(20) NOT NULL DEFAULT '',
                `to_status` varchar(20) NOT NULL DEFAULT '',
                `from_commission` decimal(14,2) NOT NULL DEFAULT 0.00,
                `to_commission` decimal(14,2) NOT NULL DEFAULT 0.00,
                `reason` varchar(500) NOT NULL DEFAULT '',
                `raw_json` mediumtext,
                `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpsl_id`),
                KEY `idx_lp_status_lpo` (`lpo_id`),
                KEY `idx_lp_status_changed` (`changed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_order_status_logs 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $sync_logs = lc_table('lp_sync_logs');
        if (!lc_db_table_exists($sync_logs)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$sync_logs}` (
                `lps_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `sync_type` varchar(30) NOT NULL DEFAULT '',
                `request_url` varchar(1000) NOT NULL DEFAULT '',
                `request_body` mediumtext,
                `response_code` int NOT NULL DEFAULT 0,
                `response_body` mediumtext,
                `success` tinyint(1) NOT NULL DEFAULT 0,
                `processed_count` int NOT NULL DEFAULT 0,
                `error_message` varchar(500) NOT NULL DEFAULT '',
                `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `finished_at` datetime DEFAULT NULL,
                PRIMARY KEY (`lps_id`),
                KEY `idx_lp_sync_type` (`sync_type`),
                KEY `idx_lp_sync_started` (`started_at`),
                KEY `idx_lp_sync_success` (`success`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_sync_logs 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $ledger = lc_table('lp_ledger');
        if (!lc_db_table_exists($ledger)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$ledger}` (
                `lpl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `lpo_id` bigint unsigned NOT NULL DEFAULT 0,
                `entry_type` varchar(20) NOT NULL DEFAULT '',
                `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
                `balance_after` decimal(14,2) NOT NULL DEFAULT 0.00,
                `idempotency_key` varchar(80) NOT NULL DEFAULT '',
                `memo` varchar(500) NOT NULL DEFAULT '',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpl_id`),
                UNIQUE KEY `uk_lp_ledger_idem` (`idempotency_key`),
                KEY `idx_lp_ledger_pt` (`pt_id`),
                KEY `idx_lp_ledger_lpo` (`lpo_id`),
                KEY `idx_lp_ledger_type` (`entry_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_ledger 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $shortlinks = lc_table('lp_shortlinks');
        if (!lc_db_table_exists($shortlinks)) {
            $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$shortlinks}` (
                `lpsh_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `pt_id` int unsigned NOT NULL DEFAULT 0,
                `lpm_id` int unsigned NOT NULL DEFAULT 0,
                `merchant_code` varchar(20) NOT NULL DEFAULT '',
                `short_code` varchar(16) NOT NULL DEFAULT '',
                `target_url` varchar(2000) NOT NULL DEFAULT '',
                `target_hash` char(64) NOT NULL DEFAULT '',
                `product_url` varchar(1000) NOT NULL DEFAULT '',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`lpsh_id`),
                UNIQUE KEY `uk_lp_short_code` (`short_code`),
                UNIQUE KEY `uk_lp_short_pt_hash` (`pt_id`, `target_hash`),
                KEY `idx_lp_short_pt` (`pt_id`),
                KEY `idx_lp_short_merchant` (`merchant_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}", false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'lp_shortlinks 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        // 기본 LINKPRICE 네트워크 행 시드
        $row = lc_sql_fetch(" SELECT network_id FROM `{$networks}` WHERE network_code = 'LINKPRICE' LIMIT 1 ", false);
        if (!$row) {
            lc_sql_query(" INSERT INTO `{$networks}` SET
                network_code = 'LINKPRICE',
                network_name = '링크프라이스',
                api_enabled = 0,
                default_partner_rate = 70.00,
                created_at = NOW(),
                updated_at = NOW() ", false);
        }

        return array('ok' => true, 'message' => '링크프라이스 CPS 스키마 준비 완료');
    }
}
