<?php
/**
 * ONOFFCPA Core Integration — offline fixture tests.
 *
 * Usage: php plugin/linkconnect/tests/run_core_integration_tests.php
 *
 * Requires ONOFF Core at ../../onoffcrm_v1 (or ONOFF_CORE_ROOT env).
 * No production DB writes; memory fixtures only.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('_GNUBOARD_', true);
define('G5_DOMAIN', 'https://onoffcpa.icrm.co.kr');
define('G5_PATH', dirname(dirname(dirname(__DIR__))));
define('G5_PLUGIN_PATH', G5_PATH . '/plugin');
define('LC_PLUGIN_PATH', G5_PLUGIN_PATH . '/linkconnect');
define('LC_INC_PATH', LC_PLUGIN_PATH . '/inc');
define('LC_PARTNER_STATUS_ACTIVE', 'active');
define('LC_PARTNER_STATUS_PENDING', 'pending');
define('LC_PARTNER_STATUS_SUSPENDED', 'suspended');
define('LC_STATUS_ACTIVE', 'active');
define('LC_ONOFF_CORE_INTEGRATION_ENABLED', true);
define('LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED', false);

require_once LC_INC_PATH . '/onoff_core_bridge.php';
require_once LC_INC_PATH . '/onoff_core_settlement.php';

// Minimal partner stubs (no DB)
function lc_get_partner_by_id($pt_id)
{
    static $partners = array(
        1 => array('pt_id' => 1, 'mb_id' => 'partner_a', 'pt_status' => LC_PARTNER_STATUS_ACTIVE, 'pt_name' => 'Partner A'),
        2 => array('pt_id' => 2, 'mb_id' => 'partner_b', 'pt_status' => LC_PARTNER_STATUS_ACTIVE, 'pt_name' => 'Partner B'),
    );
    return isset($partners[(int) $pt_id]) ? $partners[(int) $pt_id] : null;
}

function lc_get_partner_by_mb_id($mb_id)
{
    foreach (array(1, 2) as $id) {
        $p = lc_get_partner_by_id($id);
        if ($p && ($p['mb_id'] ?? '') === $mb_id) {
            return $p;
        }
    }
    return null;
}

function lc_table($name) { return 'g5_lc_' . $name; }
function lc_db_installed() { return false; }
function lc_sql_escape($s) { return addslashes((string) $s); }

$pass = 0;
$fail = 0;
function ci_assert($cond, $name)
{
    global $pass, $fail;
    echo ($cond ? 'PASS' : 'FAIL') . "  {$name}\n";
    $cond ? $pass++ : $fail++;
}

// ── 0. Project identity ──
$id = lc_onoff_core_project_identity_check();
ci_assert(!empty($id['ok']) && $id['code'] === 'ONOFFCPA', '0 project identity onoffcpa');

// ── 1. Core bootstrap ──
ci_assert(lc_onoff_core_bootstrap(), '1 core bootstrap loads');

// ── 2. Fixture: Partner A, Referral ABC123, Customer B ──
$refStore = new OnoffCore_MemoryReferralStore();
$walletStore = new OnoffCore_MemoryWalletStore();
$payStore = new OnoffCore_MemoryPaymentStore();
$walletStore->seedSettled('partner_a', 0);
$walletStore->seedSettled('customer_b', 0);
$wallet = new OnoffCore_WalletService($walletStore);
$wallet->forceEnableForTests(false);
$pay = new OnoffCore_PaymentService($payStore, $wallet);
$pay->forceEnableForTests(true);
$pay->setAuth(true, 'admin', 'cpa_core_test');
$ref = new OnoffCore_ReferralService($refStore);
$ref->forceEnableForTests();
$rules = new OnoffCore_CommissionRuleService($refStore);
$com = new OnoffCore_CommissionService($refStore);
$com->forceEnableForTests();
OnoffCore_PaymentCommissionBridge::forceEnableForTests();
OnoffCore_PaymentCommissionBridge::setTestStore($refStore);

lc_onoff_core_set_test_mode($refStore, true);
lc_onoff_core_settlement_reset_test_memory();

$ref->createReferralCode('partner_a', 'ABC123', 'cp_1001');
$rules->upsertRule(array('rule_id' => 'global_default', 'scope' => 'GLOBAL', 'commission_rate' => '0.1000', 'priority' => 0));
$bind = $ref->bindOnSignup('customer_b', 'ABC123', array('source_service' => 'ONOFFCPA'));
ci_assert(!empty($bind['ok']), '2 referral bind customer_b → partner_a');

// ── 3. Payments 500k + 1M + 300k @ 10% = 180k ──
$p1 = $pay->createPending(array('mb_id' => 'customer_b', 'amount' => 500000, 'idempotency_key' => 'cpa:P1'));
$pay->completeSuccess($p1['payment']['payment_id']);
$p2 = $pay->createPending(array('mb_id' => 'customer_b', 'amount' => 1000000, 'idempotency_key' => 'cpa:P2'));
$pay->completeSuccess($p2['payment']['payment_id']);
$p3 = $pay->createPending(array('mb_id' => 'customer_b', 'amount' => 300000, 'idempotency_key' => 'cpa:P3'));
$pay->completeSuccess($p3['payment']['payment_id']);

$dash = lc_onoff_core_partner_dashboard(1);
ci_assert(is_array($dash) && (int) $dash['totalEarnings'] === 180000, '3 dashboard total earnings 180000');
ci_assert((int) $dash['referredMemberCount'] === 1, '3 referred member count 1');

// ── 4. Duplicate earnings 0 ──
for ($i = 0; $i < 10; $i++) {
    $pay->completeSuccess($p1['payment']['payment_id']);
}
$dupCount = 0;
foreach ($refStore->commissions as $c) {
    if ($c['payment_id'] === $p1['payment']['payment_id']) {
        $dupCount++;
    }
}
ci_assert($dupCount === 1, '4 duplicate payment callback → 1 commission');

// ── 5. Refund CANCELLED ──
$pay->refundSuccess($p1['payment']['payment_id']);
$c1 = $refStore->getCommissionByPayment($p1['payment']['payment_id']);
ci_assert($c1 && $c1['status'] === OnoffCore_CommissionStatus::CANCELLED, '5 refund → CANCELLED');

// ── 6. Cross-partner access blocked ──
$dashB = lc_onoff_core_partner_dashboard(2);
ci_assert(is_array($dashB) && (int) $dashB['totalEarnings'] === 0, '6 partner B cannot see partner A earnings');
ci_assert(!lc_onoff_core_assert_partner_access(2, 'partner_a'), '6 access control partner B ≠ partner_a');

// ── 7. Inactive campaign referral blocked ──
$inactive = array(
    'cp_id' => 99,
    'cp_status' => 'paused',
    'cp_type' => 'cps',
    'cp_platform_service' => 'TRAFFIC',
    'cp_product_type' => 'lifetime',
    'cp_landing_url' => 'https://traffic.icrm.co.kr/',
);
ci_assert(empty(lc_onoff_core_get_or_create_referral_code(1, 99, $inactive)['ok']), '7 inactive campaign blocks referral');

// ── 8. Active lifetime campaign referral URL ──
$active = array(
    'cp_id' => 1001,
    'cp_status' => LC_STATUS_ACTIVE,
    'cp_type' => 'cps',
    'cp_platform_service' => 'CONTENT',
    'cp_product_type' => 'lifetime',
    'cp_landing_url' => 'https://content.icrm.co.kr/landing',
);
$refLink = lc_onoff_core_get_or_create_referral_code(1, 1001, $active);
ci_assert(!empty($refLink['ok']) && strpos($refLink['referralUrl'], 'ref=') !== false, '8 lifetime campaign referral URL ref=CODE');

// ── 9. Duplicate settlement blocked ──
$avail = lc_onoff_core_settlement_prepare_request(1, 50000, array($c1['commission_id']));
ci_assert(empty($avail['ok']), '9 cancelled commission not settleable');
$c2 = $refStore->getCommissionByPayment($p2['payment']['payment_id']);
$draft1 = lc_onoff_core_settlement_prepare_request(1, (int) $c2['commission_amount'], array($c2['commission_id']));
ci_assert(!empty($draft1['ok']) && !empty($draft1['draft']['payoutBlocked']), '9a settlement draft prepared payout blocked');
ci_assert(empty($draft1['draft']['payoutEnabled']), '9a2 payoutEnabled false in draft');
$res = lc_onoff_core_settlement_reserve_commissions(1, 9001, array(
    array('commission_id' => $c2['commission_id'], 'amount' => (int) $c2['commission_amount']),
));
ci_assert(!empty($res['ok']), '9b commission reserved for settlement');
$dupSettle = lc_onoff_core_settlement_prepare_request(1, (int) $c2['commission_amount'], array($c2['commission_id']));
ci_assert(empty($dupSettle['ok']), '9c duplicate settlement commission blocked');

// ── 10. Partner role mapping ──
$roles = lc_onoff_core_partner_roles('partner_a');
ci_assert(in_array('CUSTOMER', $roles, true) && in_array('PARTNER', $roles, true), '10 CUSTOMER + PARTNER roles');

// ── 11. Privacy masking ──
ci_assert(lc_onoff_core_mask_mb_id('customer_b') !== 'customer_b', '11 customer mb_id masked in dashboard');
$members = lc_onoff_core_referred_members(1);
ci_assert(!empty($members) && strpos($members[0]['customerMbId'], '*') !== false, '11 referred member masked');

// ── 12. Content contract — CPA fields preserved ──
$campaign_api = array('id' => 5, 'title' => 'CPA Test', 'landingUrl' => 'https://onoffcpa.icrm.co.kr/lp');
$campaign_row = array('cp_id' => 5, 'cp_type' => 'cpa', 'cp_landing_url' => 'https://onoffcpa.icrm.co.kr/lp', 'cp_badge' => '무료상담');
$content = lc_onoff_core_content_campaign_contract($campaign_api, $campaign_row, 1);
ci_assert(isset($content['campaignId']) && isset($content['landing_url']) && isset($content['cta']), '12 content contract fields present');
ci_assert($content['referral_url'] === '', '12 CPA campaign no core referral_url override');

// ── 13. CPA link path does not force core referral ──
ci_assert(!lc_onoff_core_uses_core_referral($campaign_row), '13 CPA campaign uses legacy link path');

echo "\nONOFFCPA CORE INTEGRATION: {$pass} PASS, {$fail} FAIL\n";
if ($fail === 0) {
    echo "ONOFFCPA_CORE_INTEGRATION_READY\n";
} else {
    echo "ONOFFCPA_CORE_INTEGRATION_BLOCKED\n";
}
exit($fail > 0 ? 1 : 0);
