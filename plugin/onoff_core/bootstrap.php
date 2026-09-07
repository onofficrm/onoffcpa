<?php
/**
 * ONOFF Core slim bootstrap for ONOFFCPA (referral + commission SoT).
 * Full platform_auth / wallet stack intentionally omitted.
 */
$onoff_core_root = dirname(__FILE__);
require_once $onoff_core_root . '/contracts/ReferralPayment.php';
require_once $onoff_core_root . '/payment/PaymentStatus.php';
require_once $onoff_core_root . '/referral/FeatureFlags.php';
require_once $onoff_core_root . '/referral/Errors.php';
require_once $onoff_core_root . '/referral/Schema.php';
require_once $onoff_core_root . '/referral/MemoryStore.php';
require_once $onoff_core_root . '/referral/ReferralService.php';
require_once $onoff_core_root . '/referral/CommissionRuleService.php';
require_once $onoff_core_root . '/referral/CommissionService.php';
require_once $onoff_core_root . '/referral/PaymentCommissionBridge.php';
require_once $onoff_core_root . '/referral/PartnerEarnings.php';
