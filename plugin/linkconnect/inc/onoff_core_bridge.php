<?php
/**
 * ONOFFCPA ↔ ONOFF Core integration bridge.
 *
 * Commission / referral relationship SoT = ONOFF Core (never duplicated here).
 * Production writes disabled by default; fixture/test mode for offline verification.
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!defined('LC_ONOFF_CORE_INTEGRATION_ENABLED')) {
    define('LC_ONOFF_CORE_INTEGRATION_ENABLED', true);
}

if (!defined('LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED')) {
    /** Core CPS settlement submit/pay enabled when true. */
    define('LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED', true);
}

if (!defined('ONOFF_REFERRAL_CORE_ENABLED')) {
    define('ONOFF_REFERRAL_CORE_ENABLED', true);
}
if (!defined('ONOFF_COMMISSION_ENABLED')) {
    define('ONOFF_COMMISSION_ENABLED', true);
}

/** @var OnoffCore_MemoryReferralStore|null */
$GLOBALS['lc_onoff_core_test_store'] = null;

/** @var bool */
$GLOBALS['lc_onoff_core_test_force_enabled'] = false;

if (!function_exists('lc_onoff_core_root_path')) {
    function lc_onoff_core_root_path()
    {
        $env = getenv('ONOFF_CORE_ROOT');
        if ($env !== false && $env !== '') {
            $p = rtrim((string) $env, '/');
            if (@is_file($p . '/bootstrap.php')) {
                return $p;
            }
        }

        $candidates = array();
        if (defined('G5_PLUGIN_PATH')) {
            $candidates[] = rtrim((string) G5_PLUGIN_PATH, '/') . '/onoff_core';
        }
        if (defined('G5_PATH')) {
            $home = rtrim((string) G5_PATH, '/');
            $candidates[] = $home . '/plugin/onoff_core';
            $candidates[] = $home . '/modules/onoff_core';
            $rp = @realpath(dirname($home) . '/../../onoffcrm_v1/modules/onoff_core');
            if ($rp) {
                $candidates[] = $rp;
            }
            $rp2 = @realpath(dirname($home) . '/../onoffcrm_v1/modules/onoff_core');
            if ($rp2) {
                $candidates[] = $rp2;
            }
        }
        // Dev workstation only — skip when open_basedir would warn on this path.
        if (@is_dir('/Volumes/onoff/cursor')) {
            $candidates[] = '/Volumes/onoff/cursor/onoffcrm_v1/modules/onoff_core';
            $candidates[] = '/Volumes/onoff/cursor/customer/onoffcpa/plugin/onoff_core';
        }

        foreach ($candidates as $path) {
            if (!$path || !is_string($path)) {
                continue;
            }
            // Suppress open_basedir warnings on hosts that cannot see this path.
            if (@is_file(rtrim($path, '/') . '/bootstrap.php')) {
                return rtrim($path, '/');
            }
        }

        return '';
    }
}

if (!function_exists('lc_onoff_core_bootstrap')) {
    function lc_onoff_core_bootstrap()
    {
        static $loaded = false;
        if ($loaded) {
            return class_exists('OnoffCore_PartnerEarnings', false);
        }

        $root = lc_onoff_core_root_path();
        if ($root === '') {
            return false;
        }

        require_once $root . '/bootstrap.php';
        $loaded = true;

        return true;
    }
}

if (!function_exists('lc_onoff_core_integration_enabled')) {
    function lc_onoff_core_integration_enabled()
    {
        if (!empty($GLOBALS['lc_onoff_core_test_force_enabled'])) {
            return true;
        }

        return defined('LC_ONOFF_CORE_INTEGRATION_ENABLED') && LC_ONOFF_CORE_INTEGRATION_ENABLED;
    }
}

if (!function_exists('lc_onoff_core_set_test_mode')) {
    /**
     * @param OnoffCore_MemoryReferralStore|null $store
     */
    function lc_onoff_core_set_test_mode($store = null, $enabled = true)
    {
        $GLOBALS['lc_onoff_core_test_force_enabled'] = (bool) $enabled;
        $GLOBALS['lc_onoff_core_test_store'] = $store;
        if ($enabled && $store instanceof OnoffCore_MemoryReferralStore) {
            OnoffCore_PaymentCommissionBridge::forceEnableForTests();
            OnoffCore_PaymentCommissionBridge::setTestStore($store);
        }
    }
}

if (!function_exists('lc_onoff_core_test_store')) {
    function lc_onoff_core_test_store()
    {
        return isset($GLOBALS['lc_onoff_core_test_store'])
            ? $GLOBALS['lc_onoff_core_test_store']
            : null;
    }
}

if (!function_exists('lc_onoff_core_referral_service')) {
    function lc_onoff_core_referral_service()
    {
        if (!lc_onoff_core_bootstrap()) {
            return null;
        }

        $store = lc_onoff_core_test_store();
        $svc = new OnoffCore_ReferralService($store);
        if ($store instanceof OnoffCore_MemoryReferralStore) {
            $svc->forceEnableForTests();
        }

        return $svc;
    }
}

if (!function_exists('lc_onoff_core_platform_services')) {
    /** ONOFF platform CPS campaign service codes (Core ServiceCodes). */
    function lc_onoff_core_platform_services()
    {
        return array('TRAFFIC', 'BACKLINK', 'CONTENT', 'DOMAIN', 'GEO', 'ONOFFCPA');
    }
}

if (!function_exists('lc_onoff_core_partner_mb_id')) {
    function lc_onoff_core_partner_mb_id($pt_id)
    {
        $partner = lc_get_partner_by_id((int) $pt_id);

        return is_array($partner) ? trim((string) ($partner['mb_id'] ?? '')) : '';
    }
}

if (!function_exists('lc_onoff_core_partner_roles')) {
    /**
     * Core role compatibility map — CUSTOMER + PARTNER when active partner record exists.
     *
     * @return string[]
     */
    function lc_onoff_core_partner_roles($mb_id)
    {
        if (!lc_onoff_core_bootstrap()) {
            return array('CUSTOMER');
        }

        $roles = array(OnoffCore_Roles::CUSTOMER);
        $partner = lc_get_partner_by_mb_id(trim((string) $mb_id));
        if (is_array($partner) && ($partner['pt_status'] ?? '') === LC_PARTNER_STATUS_ACTIVE) {
            $roles[] = OnoffCore_Roles::PARTNER;
        }

        $norm = OnoffCore_Roles::normalizeSet($roles);

        return !empty($norm['ok']) ? $norm['roles'] : array(OnoffCore_Roles::CUSTOMER);
    }
}

if (!function_exists('lc_onoff_core_sync_partner_enrollment')) {
    /**
     * Non-destructive partner ↔ Core PARTNER role mapping (metadata only).
     *
     * @param array<string,mixed> $partner
     * @return array{ok:bool,message:string,roles?:string[]}
     */
    function lc_onoff_core_sync_partner_enrollment(array $partner)
    {
        if (!lc_onoff_core_integration_enabled()) {
            return array('ok' => true, 'message' => 'core integration disabled');
        }

        $mb_id = trim((string) ($partner['mb_id'] ?? ''));
        if ($mb_id === '') {
            return array('ok' => false, 'message' => 'mb_id required');
        }

        $roles = lc_onoff_core_partner_roles($mb_id);

        if (lc_db_installed() && function_exists('lc_db_column_exists')) {
            $table = lc_table('partners');
            if (lc_db_column_exists($table, 'pt_onoff_roles')) {
                $json = lc_sql_escape(json_encode($roles, JSON_UNESCAPED_UNICODE));
                $pt_id = (int) ($partner['pt_id'] ?? 0);
                if ($pt_id > 0) {
                    lc_sql_query(
                        " UPDATE `{$table}` SET pt_onoff_roles = '{$json}', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' ",
                        false
                    );
                }
            }
        }

        return array('ok' => true, 'message' => 'partner roles mapped', 'roles' => $roles);
    }
}

if (!function_exists('lc_onoff_core_campaign_platform_service')) {
    function lc_onoff_core_campaign_platform_service(array $campaign)
    {
        if (!empty($campaign['cp_platform_service'])) {
            return strtoupper(trim((string) $campaign['cp_platform_service']));
        }

        return 'ONOFFCPA';
    }
}

if (!function_exists('lc_onoff_core_campaign_product_type')) {
    function lc_onoff_core_campaign_product_type(array $campaign)
    {
        $type = strtolower(trim((string) ($campaign['cp_product_type'] ?? $campaign['cp_type'] ?? 'cpa')));

        return $type;
    }
}

if (!function_exists('lc_onoff_core_is_lifetime_campaign')) {
    function lc_onoff_core_is_lifetime_campaign(array $campaign)
    {
        $product = lc_onoff_core_campaign_product_type($campaign);

        return $product === 'lifetime' || strtoupper($product) === 'LIFETIME';
    }
}

if (!function_exists('lc_onoff_core_uses_core_referral')) {
    /** Core referral code path for ONOFF platform CPS / LIFETIME campaigns. */
    function lc_onoff_core_uses_core_referral(array $campaign)
    {
        if (!lc_onoff_core_integration_enabled()) {
            return false;
        }

        if (lc_onoff_core_is_lifetime_campaign($campaign)) {
            return true;
        }

        $type = strtolower(trim((string) ($campaign['cp_type'] ?? 'cpa')));
        $platform = lc_onoff_core_campaign_platform_service($campaign);

        return $type === 'cps' && in_array($platform, lc_onoff_core_platform_services(), true);
    }
}

if (!function_exists('lc_onoff_core_build_referral_url')) {
    function lc_onoff_core_build_referral_url($landing_url, $referral_code)
    {
        $landing = trim((string) $landing_url);
        $code = strtoupper(trim((string) $referral_code));
        if ($landing === '' || $code === '') {
            return '';
        }

        $sep = strpos($landing, '?') !== false ? '&' : '?';

        return $landing . $sep . 'ref=' . rawurlencode($code);
    }
}

if (!function_exists('lc_onoff_core_referral_code_key')) {
    function lc_onoff_core_referral_code_key($pt_id, $cp_id)
    {
        return 'CPA' . str_pad((string) (int) $pt_id, 4, '0', STR_PAD_LEFT)
            . 'C' . str_pad((string) (int) $cp_id, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_onoff_core_get_or_create_referral_code')) {
    /**
     * Partner + Campaign → Core referral code (SoT in Core).
     *
     * @return array{ok:bool,message:string,referralCode?:string,referralUrl?:string}
     */
    function lc_onoff_core_get_or_create_referral_code($pt_id, $cp_id, array $campaign)
    {
        if (!lc_onoff_core_uses_core_referral($campaign)) {
            return array('ok' => false, 'message' => 'campaign does not use core referral');
        }

        if (($campaign['cp_status'] ?? '') !== LC_STATUS_ACTIVE) {
            return array('ok' => false, 'message' => 'inactive campaign');
        }

        $mb_id = lc_onoff_core_partner_mb_id($pt_id);
        if ($mb_id === '') {
            return array('ok' => false, 'message' => 'partner mb_id not found');
        }

        $svc = lc_onoff_core_referral_service();
        if (!$svc) {
            return array('ok' => false, 'message' => 'core referral unavailable');
        }

        $code = lc_onoff_core_referral_code_key($pt_id, $cp_id);
        $campaign_id = 'cp_' . (int) $cp_id;
        $existing = $svc->validateCode($code);
        if (empty($existing['ok'])) {
            $created = $svc->createReferralCode($mb_id, $code, $campaign_id);
            if (empty($created['ok'])) {
                return array('ok' => false, 'message' => 'referral code create failed');
            }
        }

        $landing = trim((string) ($campaign['cp_landing_url'] ?? ''));
        if ($landing === '' && defined('G5_DOMAIN')) {
            $landing = rtrim((string) G5_DOMAIN, '/');
        }

        return array(
            'ok'           => true,
            'message'      => 'ok',
            'referralCode' => $code,
            'referralUrl'  => lc_onoff_core_build_referral_url($landing, $code),
            'landingUrl'   => $landing,
            'campaignId'   => $campaign_id,
        );
    }
}

if (!function_exists('lc_onoff_core_mask_mb_id')) {
    function lc_onoff_core_mask_mb_id($mb_id)
    {
        $mb_id = trim((string) $mb_id);
        $len = strlen($mb_id);
        if ($len <= 2) {
            return str_repeat('*', $len);
        }
        if ($len <= 4) {
            return substr($mb_id, 0, 1) . str_repeat('*', $len - 1);
        }

        return substr($mb_id, 0, 2) . str_repeat('*', max(1, $len - 4)) . substr($mb_id, -2);
    }
}

if (!function_exists('lc_onoff_core_mask_commission_history')) {
    function lc_onoff_core_mask_commission_history(array $history)
    {
        $out = array();
        foreach ($history as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['customer_mb_id'] = lc_onoff_core_mask_mb_id($row['customer_mb_id'] ?? '');
            $out[] = $row;
        }

        return $out;
    }
}

if (!function_exists('lc_onoff_core_partner_earnings')) {
    /**
     * Read-only Core earnings (no local commission ledger).
     */
    function lc_onoff_core_partner_earnings($pt_id)
    {
        if (!lc_onoff_core_integration_enabled() || !lc_onoff_core_bootstrap()) {
            return null;
        }

        $mb_id = lc_onoff_core_partner_mb_id($pt_id);
        if ($mb_id === '') {
            return null;
        }

        $store = lc_onoff_core_test_store();
        $summary = OnoffCore_PartnerEarnings::summary($mb_id, $store);
        $summary['commission_history'] = lc_onoff_core_mask_commission_history(
            isset($summary['commission_history']) && is_array($summary['commission_history'])
                ? $summary['commission_history']
                : array()
        );

        return $summary;
    }
}

if (!function_exists('lc_onoff_core_referred_members')) {
    function lc_onoff_core_referred_members($pt_id, $limit = 20)
    {
        if (!lc_onoff_core_integration_enabled() || !lc_onoff_core_bootstrap()) {
            return array();
        }

        $mb_id = lc_onoff_core_partner_mb_id($pt_id);
        if ($mb_id === '') {
            return array();
        }

        $limit = max(1, min(100, (int) $limit));
        $rows = array();
        $store = lc_onoff_core_test_store();

        if ($store instanceof OnoffCore_MemoryReferralStore) {
            foreach ($store->memberReferrals as $mr) {
                if ((string) ($mr['referrer_mb_id'] ?? '') !== $mb_id) {
                    continue;
                }
                $rows[] = array(
                    'customerMbId'  => lc_onoff_core_mask_mb_id($mr['customer_mb_id'] ?? ''),
                    'referralCode'  => (string) ($mr['referral_code'] ?? ''),
                    'campaignId'    => (string) ($mr['campaign_id'] ?? ''),
                    'registeredAt'  => (string) ($mr['registered_at'] ?? ''),
                    'status'        => (string) ($mr['status'] ?? ''),
                );
                if (count($rows) >= $limit) {
                    break;
                }
            }

            return $rows;
        }

        if (!function_exists('sql_query') || !class_exists('OnoffCore_ReferralSchema', false)) {
            return array();
        }

        $tbl = OnoffCore_ReferralSchema::tableMemberReferral();
        $esc = sql_escape_string($mb_id);
        $res = sql_query(
            "SELECT customer_mb_id, referral_code, campaign_id, registered_at, status
             FROM `{$tbl}` WHERE referrer_mb_id = '{$esc}' ORDER BY registered_at DESC LIMIT {$limit}",
            false
        );
        if ($res) {
            while ($r = sql_fetch_array($res)) {
                $rows[] = array(
                    'customerMbId' => lc_onoff_core_mask_mb_id($r['customer_mb_id'] ?? ''),
                    'referralCode' => (string) ($r['referral_code'] ?? ''),
                    'campaignId'   => (string) ($r['campaign_id'] ?? ''),
                    'registeredAt' => (string) ($r['registered_at'] ?? ''),
                    'status'       => (string) ($r['status'] ?? ''),
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_onoff_core_partner_dashboard')) {
    /**
     * Core read model for partner dashboard (earnings / referral / commission history).
     */
    function lc_onoff_core_partner_dashboard($pt_id)
    {
        $earnings = lc_onoff_core_partner_earnings($pt_id);
        if (!is_array($earnings)) {
            return null;
        }

        $pending = (int) ($earnings['pending_earnings'] ?? 0);
        $available = (int) ($earnings['available_earnings'] ?? 0);
        $paid = (int) ($earnings['paid_earnings'] ?? 0);

        return array(
            'source'                  => 'onoff_core',
            'referredMemberCount'     => (int) ($earnings['referred_member_count'] ?? 0),
            'successfulPaymentTotal'  => (int) ($earnings['successful_payment_total'] ?? 0),
            'pendingEarnings'         => $pending,
            'availableEarnings'       => $available,
            'paidEarnings'            => $paid,
            'totalEarnings'           => $pending + $available + $paid,
            'commissionHistory'       => $earnings['commission_history'] ?? array(),
            'referredMembers'         => lc_onoff_core_referred_members($pt_id),
            'settlementPayoutEnabled' => defined('LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED')
                && LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED,
        );
    }
}

if (!function_exists('lc_onoff_core_assert_partner_access')) {
    /**
     * Block cross-partner Core data access.
     */
    function lc_onoff_core_assert_partner_access($request_pt_id, $referrer_mb_id)
    {
        $expected = lc_onoff_core_partner_mb_id((int) $request_pt_id);

        return $expected !== '' && $expected === trim((string) $referrer_mb_id);
    }
}

if (!function_exists('lc_onoff_core_enrich_link_api')) {
    /**
     * Attach Core referral URL when campaign uses lifetime/CPS platform path.
     * CPA / Content contract fields remain unchanged.
     * Core 미기동 시에도 플랫폼 CPS는 랜딩 ?ref= 추천 URL을 붙여 홍보 가능하게 한다.
     */
    function lc_onoff_core_enrich_link_api(array $link_api, array $campaign, $pt_id)
    {
        $type = strtolower(trim((string) ($campaign['cp_type'] ?? '')));
        $platform = function_exists('lc_onoff_core_campaign_platform_service')
            ? lc_onoff_core_campaign_platform_service($campaign)
            : strtoupper(trim((string) ($campaign['cp_platform_service'] ?? '')));
        $is_platform_cps = ($type === 'cps' && in_array($platform, lc_onoff_core_platform_services(), true))
            || (function_exists('lc_onoff_core_is_lifetime_campaign') && lc_onoff_core_is_lifetime_campaign($campaign));

        if (!$is_platform_cps && !lc_onoff_core_uses_core_referral($campaign)) {
            return $link_api;
        }

        $ref = lc_onoff_core_get_or_create_referral_code((int) $pt_id, (int) ($campaign['cp_id'] ?? 0), $campaign);
        if (!empty($ref['ok'])) {
            $link_api['referralCode'] = (string) $ref['referralCode'];
            $link_api['referralUrl'] = (string) $ref['referralUrl'];
            $link_api['referralSource'] = 'onoff_core';
            if (!empty($ref['referralUrl'])) {
                $link_api['landingUrl'] = (string) $ref['referralUrl'];
            }

            return $link_api;
        }

        // Fallback: deterministic promo URL (Core 기동·코드 등록 전까지 홍보용)
        $code = lc_onoff_core_referral_code_key((int) $pt_id, (int) ($campaign['cp_id'] ?? 0));
        $landing = trim((string) ($campaign['cp_landing_url'] ?? ''));
        if ($landing === '' && !empty($link_api['landingUrl'])) {
            $landing = trim((string) $link_api['landingUrl']);
        }
        $promo = lc_onoff_core_build_referral_url($landing, $code);
        if ($promo !== '') {
            $link_api['referralCode'] = $code;
            $link_api['referralUrl'] = $promo;
            $link_api['referralSource'] = 'promo_fallback';
            $link_api['landingUrl'] = $promo;
            $link_api['referralWarning'] = 'Core 추천코드 SoT 미연결 — 결제 수수료 귀속은 Core 연동 후 확정됩니다.';
        }

        return $link_api;
    }
}

if (!function_exists('lc_onoff_core_content_campaign_contract')) {
    /**
     * Content platform minimum contract — additive fields only.
     */
    function lc_onoff_core_content_campaign_contract(array $campaign_api, array $campaign_row, $pt_id = 0)
    {
        $out = $campaign_api;
        $out['campaignId'] = (int) ($campaign_api['id'] ?? $campaign_row['cp_id'] ?? 0);
        $out['product'] = lc_onoff_core_campaign_product_type($campaign_row);
        $out['landing_url'] = (string) ($campaign_api['landingUrl'] ?? $campaign_row['cp_landing_url'] ?? '');
        $out['referral_url'] = '';
        $out['cta'] = (string) ($campaign_row['cp_badge'] ?? '상담 신청');
        $out['promotionGuide'] = true;

        if ($pt_id > 0 && lc_onoff_core_uses_core_referral($campaign_row)) {
            $ref = lc_onoff_core_get_or_create_referral_code($pt_id, (int) $campaign_row['cp_id'], $campaign_row);
            if (!empty($ref['ok'])) {
                $out['referral_url'] = (string) $ref['referralUrl'];
            }
        }

        return $out;
    }
}

if (!function_exists('lc_onoff_core_project_identity_check')) {
    /**
     * Verify this repo is onoffcpa.icrm.co.kr (not linkconnect-only fork).
     *
     * @return array{ok:bool,code:string,checks:array<string,bool>}
     */
    function lc_onoff_core_project_identity_check()
    {
        $checks = array(
            'domain'    => defined('G5_DOMAIN') && strpos((string) G5_DOMAIN, 'onoffcpa') !== false,
            'spa_brand' => is_file(LC_PLUGIN_PATH . '/../onoff-builder-bridge/imports/linkconnect/spa-brand.onoffcpa')
                || is_file(G5_PLUGIN_PATH . '/onoff-builder-bridge/imports/linkconnect/spa-brand.onoffcpa'),
            'cpa'       => is_file(LC_INC_PATH . '/campaign.php'),
            'partner'   => is_file(LC_INC_PATH . '/partner.php'),
            'settlement'=> is_file(LC_INC_PATH . '/settlement.php'),
            'content_s2s' => is_file(LC_INC_PATH . '/content_s2s.php'),
        );

        $ok = !in_array(false, $checks, true);

        return array(
            'ok'     => $ok,
            'code'   => $ok ? 'ONOFFCPA' : 'ONOFFCPA_WRONG_PROJECT',
            'checks' => $checks,
        );
    }
}

if (!function_exists('lc_onoff_core_ensure_ready')) {
    /**
     * Ensure Core bootstrap + referral tables + global 10% lifetime rule.
     *
     * @return array{ok:bool,message:string,path?:string,tables?:bool,rule?:bool}
     */
    function lc_onoff_core_ensure_ready()
    {
        if (!lc_onoff_core_integration_enabled()) {
            return array('ok' => false, 'message' => 'core integration disabled');
        }
        if (!lc_onoff_core_bootstrap()) {
            return array('ok' => false, 'message' => 'core bootstrap failed', 'path' => lc_onoff_core_root_path());
        }

        $tables = false;
        if (class_exists('OnoffCore_ReferralSchema', false)) {
            $tables = (bool) OnoffCore_ReferralSchema::ensureTables();
        }

        $rule_ok = false;
        if (class_exists('OnoffCore_CommissionRuleService', false)) {
            $rules = new OnoffCore_CommissionRuleService(lc_onoff_core_test_store());
            $upsert = $rules->upsertRule(array(
                'rule_id'         => 'global_lifetime_default_10pct',
                'scope'           => 'GLOBAL',
                'commission_rate' => '0.1000',
                'rule_type'       => 'LIFETIME',
                'priority'        => 0,
                'status'          => 'ACTIVE',
            ));
            $rule_ok = !empty($upsert['ok']) || !empty($upsert);
            // upsertRule may return bool/array depending on store — treat non-false as ok
            if ($upsert === false) {
                $rule_ok = false;
            } elseif (is_array($upsert) && array_key_exists('ok', $upsert)) {
                $rule_ok = !empty($upsert['ok']);
            } else {
                $rule_ok = true;
            }
        }

        return array(
            'ok'      => $tables && $rule_ok,
            'message' => ($tables && $rule_ok) ? 'ready' : 'partial',
            'path'    => lc_onoff_core_root_path(),
            'tables'  => $tables,
            'rule'    => $rule_ok,
        );
    }
}

if (!function_exists('lc_onoff_core_bind_customer')) {
    /**
     * Bind CatchDomain (or other service) customer to an existing Core referral code.
     *
     * @param array{source_service?:string,source_reference?:string,first_click_at?:string} $context
     * @return array{ok:bool,message:string,replay?:bool,memberReferral?:array}
     */
    function lc_onoff_core_bind_customer($customer_mb_id, $referral_code, array $context = array())
    {
        $ready = lc_onoff_core_ensure_ready();
        if (empty($ready['ok']) && empty($ready['tables'])) {
            return array('ok' => false, 'message' => $ready['message'] ?? 'core not ready');
        }

        $svc = lc_onoff_core_referral_service();
        if (!$svc) {
            return array('ok' => false, 'message' => 'core referral unavailable');
        }

        $ctx = array_merge(array(
            'source_service' => 'DOMAIN',
        ), $context);

        $bind = $svc->bindOnSignup(trim((string) $customer_mb_id), strtoupper(trim((string) $referral_code)), $ctx);
        if (empty($bind['ok'])) {
            return array(
                'ok'      => false,
                'message' => (string) ($bind['error'] ?? $bind['message'] ?? 'bind_failed'),
                'code'    => (string) ($bind['error'] ?? 'BIND_FAILED'),
            );
        }

        return array(
            'ok'             => true,
            'message'        => !empty($bind['replay']) ? 'replay' : 'bound',
            'replay'         => !empty($bind['replay']),
            'memberReferral' => $bind['member_referral'] ?? null,
        );
    }
}

if (!function_exists('lc_onoff_core_record_purchased_point_payment')) {
    /**
     * Record DOMAIN (or other) purchased-point payment → lifetime commission.
     *
     * @param array{payment_id:string,customer_mb_id:string,amount:int,source_service?:string,approved_at?:string} $payload
     * @return array{ok:bool,message:string,skipped?:bool,commission?:array}
     */
    function lc_onoff_core_record_purchased_point_payment(array $payload)
    {
        $ready = lc_onoff_core_ensure_ready();
        if (empty($ready['ok']) && empty($ready['tables'])) {
            return array('ok' => false, 'message' => $ready['message'] ?? 'core not ready');
        }

        $payment_id = trim((string) ($payload['payment_id'] ?? ''));
        $customer = trim((string) ($payload['customer_mb_id'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        if ($payment_id === '' || $customer === '' || $amount <= 0) {
            return array('ok' => false, 'message' => 'payment_id, customer_mb_id, amount required');
        }

        if (!class_exists('OnoffCore_PaymentCommissionBridge', false)) {
            return array('ok' => false, 'message' => 'commission bridge missing');
        }

        $row = array(
            'payment_id'     => $payment_id,
            'mb_id'          => $customer,
            'amount'         => $amount,
            'status'         => class_exists('OnoffCore_PaymentCoreStatus', false)
                ? OnoffCore_PaymentCoreStatus::SUCCESS
                : 'SUCCESS',
            'economic_kind'  => class_exists('OnoffCore_PaymentEconomicKind', false)
                ? OnoffCore_PaymentEconomicKind::PURCHASED_POINT
                : 'PURCHASED_POINT',
            'source_service' => (string) ($payload['source_service'] ?? 'DOMAIN'),
            'approved_at'    => (string) ($payload['approved_at'] ?? date('Y-m-d H:i:s')),
        );

        $res = OnoffCore_PaymentCommissionBridge::onPaymentSucceeded($row);
        if (empty($res['ok'])) {
            return array(
                'ok'      => false,
                'message' => (string) ($res['error'] ?? 'commission_failed'),
            );
        }

        return array(
            'ok'         => true,
            'message'    => !empty($res['skipped']) ? 'skipped' : 'recorded',
            'skipped'    => !empty($res['skipped']),
            'skipReason' => (string) ($res['reason'] ?? ''),
            'commission' => $res['commission'] ?? null,
        );
    }
}
