-- LinkConnect 광고주 CPA 계약서 스키마 (v2: 계약번호·감사 로그)
-- 기존 테이블이 있으면 ALTER 만 필요할 수 있습니다. 자동 마이그레이션: lc_db_run_migrations()

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_merchant_contracts` (
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
  `mc_negotiated_terms` mediumtext,
  `mc_special_clauses` mediumtext,
  `mc_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mc_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mc_id`),
  UNIQUE KEY `uk_mc_mt_version` (`mc_mt_id`, `mc_contract_version`),
  KEY `idx_mc_contract_code` (`mc_contract_code`),
  KEY `idx_mc_mt_id` (`mc_mt_id`),
  KEY `idx_mc_status` (`mc_status`),
  KEY `idx_mc_signed_at` (`mc_signed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_merchant_contract_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_merchant_contract_status_logs` (
  `mcsl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_id` bigint unsigned NOT NULL DEFAULT 0,
  `mt_id` int unsigned NOT NULL DEFAULT 0,
  `admin_mb_id` varchar(50) NOT NULL DEFAULT '',
  `mcsl_old_status` varchar(20) NOT NULL DEFAULT '',
  `mcsl_new_status` varchar(20) NOT NULL DEFAULT '',
  `mcsl_reason` varchar(1000) NOT NULL DEFAULT '',
  `mcsl_ip` varchar(45) NOT NULL DEFAULT '',
  `mcsl_user_agent` varchar(500) NOT NULL DEFAULT '',
  `mcsl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mcsl_id`),
  KEY `idx_mcsl_mc_id` (`mc_id`),
  KEY `idx_mcsl_mt_id` (`mt_id`),
  KEY `idx_mcsl_admin` (`admin_mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `g5_lc_merchant_contract_addenda` (
  `mca_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_id` bigint unsigned NOT NULL DEFAULT 0,
  `mt_id` int unsigned NOT NULL DEFAULT 0,
  `mca_seq` int unsigned NOT NULL DEFAULT 1,
  `mca_title` varchar(200) NOT NULL DEFAULT '특약사항',
  `mca_body` mediumtext NOT NULL,
  `mca_status` varchar(20) NOT NULL DEFAULT 'active',
  `mca_created_by_type` varchar(20) NOT NULL DEFAULT 'admin',
  `mca_created_by` varchar(100) NOT NULL DEFAULT '',
  `mca_voided_at` datetime DEFAULT NULL,
  `mca_void_reason` varchar(500) NOT NULL DEFAULT '',
  `mca_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mca_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mca_id`),
  KEY `idx_mca_mc_id` (`mc_id`),
  KEY `idx_mca_mt_id` (`mt_id`),
  KEY `idx_mca_status` (`mca_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 기존 설치에 mc_contract_code 컬럼이 없다면:
-- ALTER TABLE `g5_lc_merchant_contracts`
--   ADD COLUMN `mc_contract_code` varchar(50) NOT NULL DEFAULT '' AFTER `mc_contract_version`,
--   ADD KEY `idx_mc_contract_code` (`mc_contract_code`);
