-- LinkConnect plugin schema (reference)
-- Runtime install: plugin/linkconnect/install/install.php
-- Prefix placeholder: {prefix} → e.g. g5_

CREATE TABLE IF NOT EXISTS `{prefix}lc_partners` (
  `pt_id` int unsigned NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(20) NOT NULL,
  `pt_code` varchar(20) NOT NULL,
  `pt_name` varchar(100) NOT NULL DEFAULT '',
  `pt_status` varchar(20) NOT NULL DEFAULT 'pending',
  `pt_bank_name` varchar(50) NOT NULL DEFAULT '',
  `pt_bank_account` varchar(50) NOT NULL DEFAULT '',
  `pt_bank_holder` varchar(50) NOT NULL DEFAULT '',
  `pt_balance` int NOT NULL DEFAULT 0,
  `pt_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pt_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pt_id`),
  UNIQUE KEY `uk_mb_id` (`mb_id`),
  UNIQUE KEY `uk_pt_code` (`pt_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{prefix}lc_campaigns` (
  `cp_id` int unsigned NOT NULL AUTO_INCREMENT,
  `cp_code` varchar(20) NOT NULL,
  `cp_name` varchar(200) NOT NULL,
  `cp_category` varchar(50) NOT NULL DEFAULT '',
  `cp_type` varchar(10) NOT NULL DEFAULT 'cpa',
  `cp_price` int unsigned NOT NULL DEFAULT 0,
  `cp_approval_rate` varchar(10) NOT NULL DEFAULT '',
  `cp_avg_time` varchar(20) NOT NULL DEFAULT '',
  `cp_allowed_channels` varchar(500) NOT NULL DEFAULT '',
  `cp_forbidden_channels` varchar(500) NOT NULL DEFAULT '',
  `cp_description` text,
  `cp_landing_url` varchar(500) NOT NULL DEFAULT '',
  `cp_status` varchar(20) NOT NULL DEFAULT 'draft',
  `cp_badge` varchar(20) NOT NULL DEFAULT '',
  `cp_recommended` tinyint(1) NOT NULL DEFAULT 0,
  `cp_sort` int NOT NULL DEFAULT 0,
  `cp_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cp_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cp_id`),
  UNIQUE KEY `uk_cp_code` (`cp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sprint 2+: links, clicks, conversions, settlements, inquiries
-- See inc/db.php lc_db_run_schema() for full DDL
