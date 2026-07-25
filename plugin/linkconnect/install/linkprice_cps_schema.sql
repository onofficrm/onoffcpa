-- LinkConnect × 링크프라이스 CPS 스키마
-- 적용 DB: linkconnect (LC_MYSQL_DB)
-- 문자셋: utf8mb4
-- 접두사: g5_lc_lp_*
-- CPA 테이블(g5_lc_campaigns/conversions/clicks 등)과 무관
--
-- 적용 예:
--   mysql -u USER -p linkconnect < plugin/linkconnect/install/linkprice_cps_schema.sql
--
-- 롤백 예:
--   DROP TABLE IF EXISTS
--     g5_lc_lp_sync_logs,
--     g5_lc_lp_order_status_logs,
--     g5_lc_lp_orders,
--     g5_lc_lp_postbacks,
--     g5_lc_lp_clicks,
--     g5_lc_lp_merchants,
--     g5_lc_lp_networks;
--
-- 운영 DB에는 자동 실행하지 말고, 스테이징에서 검증 후 적용하세요.
-- 플러그인 로드 시 lc_db_run_migrations() → lc_lp_db_ensure_schema() 가
-- CREATE TABLE IF NOT EXISTS 로 동일 스키마를 보장합니다.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_networks` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_merchants` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_clicks` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_postbacks` (
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
  KEY `idx_lp_pb_received` (`received_at`),
  KEY `idx_lp_pb_merchant` (`merchant_code`),
  KEY `idx_lp_pb_order` (`order_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_orders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_order_status_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_sync_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_ledger` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_lp_shortlinks` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `g5_lc_lp_networks`
  (`network_code`, `network_name`, `api_enabled`, `default_partner_rate`, `created_at`, `updated_at`)
SELECT 'LINKPRICE', '링크프라이스', 0, 70.00, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `g5_lc_lp_networks` WHERE `network_code` = 'LINKPRICE'
);
