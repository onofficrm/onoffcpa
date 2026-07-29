<?php
/**
 * 광고주 관리 플랫폼 정책
 *
 * 규칙:
 * - 단독 입점(그룹 미등록): 해당 플랫폼에서만 조회·승인/취소
 * - 공동 입점(그룹 멤버 2+): 멤버 플랫폼 모두에서 조회·승인/취소 가능
 * - 지갑 차감(공동 입점):
 *   · 온오프CPA(primary)에서 승인 → 온오프CPA만 차감
 *   · 링크커넥트에서 승인 → 온오프CPA + 링크커넥트 모두 차감
 *   · 원격 ACK 수신 시: primary(온오프CPA)만 추가 차감, 링크커넥트는 스킵
 * - 불변식: 공동 입점 광고주 잔액은 온오프CPA ≥ 링크커넥트
 * - management_platform_id 는 기본 미러/허브 우선순위로만 사용 (승인 독점 아님)
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_mp_primary_wallet_platform_code')) {
    /**
     * 광고비 primary 지갑 플랫폼 (항상 차감 대상)
     */
    function lc_mp_primary_wallet_platform_code()
    {
        if (defined('LC_PLATFORM_ONOFFCPA') && LC_PLATFORM_ONOFFCPA !== '') {
            return (string) LC_PLATFORM_ONOFFCPA;
        }

        return 'ONOFFCPA';
    }
}

if (!function_exists('lc_mp_local_is_primary_wallet_platform')) {
    function lc_mp_local_is_primary_wallet_platform()
    {
        return strtoupper((string) lc_mp_local_platform_code()) === strtoupper(lc_mp_primary_wallet_platform_code());
    }
}

if (!function_exists('lc_mp_should_charge_wallet_on_approve')) {
    /**
     * 승인 시 로컬 지갑 차감 여부
     * - 로컬 개시(initiator): 항상 차감
     * - 원격 ACK: primary(온오프CPA)만 차감
     */
    function lc_mp_should_charge_wallet_on_approve($mp_remote_ack)
    {
        if (empty($mp_remote_ack)) {
            return true;
        }

        return lc_mp_local_is_primary_wallet_platform();
    }
}

if (!function_exists('lc_mp_get_platform_by_code')) {
    function lc_mp_get_platform_by_code($code)
    {
        if (!lc_mp_enabled()) {
            return null;
        }
        $table = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        $code_esc = lc_sql_escape((string) $code);
        $row = sql_fetch(" SELECT * FROM `{$table}` WHERE platform_code = '{$code_esc}' LIMIT 1 ");

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_mp_get_platform_by_id')) {
    function lc_mp_get_platform_by_id($platform_id)
    {
        if (!lc_mp_enabled()) {
            return null;
        }
        $table = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        $row = sql_fetch(" SELECT * FROM `{$table}` WHERE platform_id = '" . (int) $platform_id . "' LIMIT 1 ");

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_mp_find_group_by_local_mt')) {
    function lc_mp_find_group_by_local_mt($mt_id)
    {
        if (!lc_mp_enabled() || (int) $mt_id <= 0) {
            return null;
        }
        $mem = lc_mp_db_table('advertiser_memberships');
        $grp = lc_mp_db_table('advertiser_groups');
        if (!lc_db_table_exists($mem) || !lc_db_table_exists($grp)) {
            return null;
        }
        $row = sql_fetch(" SELECT g.* FROM `{$mem}` m
            INNER JOIN `{$grp}` g ON g.group_id = m.group_id
            WHERE m.local_mt_id = '" . (int) $mt_id . "' AND m.status = 'active'
            LIMIT 1 ");

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_mp_list_memberships')) {
    /**
     * @return array<int,array<string,mixed>>
     */
    function lc_mp_list_memberships($group_id)
    {
        if (!lc_mp_enabled()) {
            return array();
        }
        $mem = lc_mp_db_table('advertiser_memberships');
        $plat = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($mem)) {
            return array();
        }
        $items = array();
        $result = sql_query(" SELECT m.*, p.platform_code, p.platform_name, p.is_local
            FROM `{$mem}` m
            LEFT JOIN `{$plat}` p ON p.platform_id = m.platform_id
            WHERE m.group_id = '" . (int) $group_id . "' AND m.status = 'active' ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = $row;
            }
        }

        return $items;
    }
}

if (!function_exists('lc_mp_get_management_platform')) {
    /**
     * @return array|null platform row
     */
    function lc_mp_get_management_platform($group_id)
    {
        if (!lc_mp_enabled()) {
            return null;
        }
        $pol = lc_mp_db_table('management_policies');
        if (!lc_db_table_exists($pol)) {
            return null;
        }
        $row = sql_fetch(" SELECT * FROM `{$pol}` WHERE group_id = '" . (int) $group_id . "' LIMIT 1 ");
        if (!is_array($row) || empty($row['management_platform_id'])) {
            return lc_mp_resolve_default_management_platform($group_id);
        }

        return lc_mp_get_platform_by_id((int) $row['management_platform_id']);
    }
}

if (!function_exists('lc_mp_resolve_default_management_platform')) {
    /**
     * 정책 미설정 시: 멤버십 1개면 그 플랫폼, 2개 이상이면 로컬(ONOFFCPA) 우선.
     */
    function lc_mp_resolve_default_management_platform($group_id)
    {
        $memberships = lc_mp_list_memberships($group_id);
        if (count($memberships) === 0) {
            return lc_mp_get_platform_by_code(lc_mp_local_platform_code());
        }
        if (count($memberships) === 1) {
            return lc_mp_get_platform_by_id((int) $memberships[0]['platform_id']);
        }
        foreach ($memberships as $m) {
            if (!empty($m['is_local'])) {
                return lc_mp_get_platform_by_id((int) $m['platform_id']);
            }
        }

        return lc_mp_get_platform_by_code(lc_mp_local_platform_code());
    }
}

if (!function_exists('lc_mp_local_is_management_for_mt')) {
    /**
     * 로컬이 지정된 management(허브) 플랫폼인지.
     * 미러 생성 우선순위·레거시 호출용. 승인 권한은 lc_mp_local_can_mutate_for_mt 를 쓴다.
     */
    function lc_mp_local_is_management_for_mt($mt_id)
    {
        if (!lc_mp_enabled()) {
            return true;
        }
        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return true; // 미등록 광고주 = 로컬 전용
        }
        $mgmt = lc_mp_get_management_platform((int) $group['group_id']);
        if (!$mgmt) {
            return true;
        }

        return !empty($mgmt['is_local']) || ((string) ($mgmt['platform_code'] ?? '') === lc_mp_local_platform_code());
    }
}

if (!function_exists('lc_mp_local_can_mutate_for_mt')) {
    /**
     * 이 인스턴스에서 광고주(mt) DB 승인/취소를 해도 되는지.
     * - 플래그 OFF / 그룹 미등록(단독) → true (기존 로컬만 가능)
     * - 공동 입점 → 로컬 플랫폼이 그룹 멤버이면 true (양쪽 모두 승인 가능)
     */
    function lc_mp_local_can_mutate_for_mt($mt_id)
    {
        if (!lc_mp_enabled()) {
            return true;
        }
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return true;
        }
        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return true; // 단독 입점
        }

        $memberships = lc_mp_list_memberships((int) $group['group_id']);
        if (!$memberships) {
            return true;
        }
        $local = lc_mp_local_platform_code();
        foreach ($memberships as $m) {
            $code = (string) ($m['platform_code'] ?? '');
            if ($code === $local || !empty($m['is_local'])) {
                return true;
            }
        }

        // 그룹은 있으나 로컬 멤버십이 없으면 이 플랫폼에서 처리 불가
        return false;
    }
}

if (!function_exists('lc_mp_peer_platforms_for_mt')) {
    /**
     * 공동 입점 광고주의 원격(비로컬) 멤버 플랫폼 목록.
     *
     * @return array<int,array<string,mixed>>
     */
    function lc_mp_peer_platforms_for_mt($mt_id)
    {
        if (!lc_mp_enabled()) {
            return array();
        }
        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return array();
        }
        $peers = array();
        foreach (lc_mp_list_memberships((int) $group['group_id']) as $m) {
            if (!empty($m['is_local'])) {
                continue;
            }
            $plat = lc_mp_get_platform_by_id((int) ($m['platform_id'] ?? 0));
            if (is_array($plat) && empty($plat['is_local'])) {
                $peers[] = $plat;
            }
        }

        return $peers;
    }
}

if (!function_exists('lc_mp_upsert_policy')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_mp_upsert_policy($group_id, $management_platform_id, $reason = '')
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }
        $pol = lc_mp_db_table('management_policies');
        if (!lc_db_table_exists($pol)) {
            return array('ok' => false, 'message' => 'policy table missing');
        }
        $group_id = (int) $group_id;
        $management_platform_id = (int) $management_platform_id;
        $reason_esc = lc_sql_escape((string) $reason);
        $exists = sql_fetch(" SELECT policy_id FROM `{$pol}` WHERE group_id = '{$group_id}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['policy_id'])) {
            lc_sql_query(" UPDATE `{$pol}` SET
                management_platform_id = '{$management_platform_id}',
                reason = '{$reason_esc}',
                updated_at = NOW()
                WHERE group_id = '{$group_id}' ", false);
        } else {
            lc_sql_query(" INSERT INTO `{$pol}`
                (`group_id`, `management_platform_id`, `reason`)
                VALUES ('{$group_id}', '{$management_platform_id}', '{$reason_esc}') ", false);
        }
        lc_mp_audit('policy.upsert', array(
            'group_id' => $group_id,
            'management_platform_id' => $management_platform_id,
            'reason' => $reason,
        ));

        return array('ok' => true, 'message' => 'policy saved');
    }
}

if (!function_exists('lc_mp_create_group')) {
    /**
     * @return array{ok:bool,message:string,group_id?:int}
     */
    function lc_mp_create_group($display_name, $business_number = '', $group_code = '')
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }
        $table = lc_mp_db_table('advertiser_groups');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => 'groups table missing');
        }
        if ($group_code === '') {
            $group_code = 'AG' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10));
        }
        $code_esc = lc_sql_escape($group_code);
        $name_esc = lc_sql_escape((string) $display_name);
        $biz_esc = lc_sql_escape((string) $business_number);
        lc_sql_query(" INSERT INTO `{$table}` (`group_code`, `display_name`, `business_number`)
            VALUES ('{$code_esc}', '{$name_esc}', '{$biz_esc}') ", false);
        $id = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;

        return array('ok' => true, 'message' => 'created', 'group_id' => $id);
    }
}

if (!function_exists('lc_mp_attach_membership')) {
    /**
     * 광고주 그룹에 플랫폼 멤버십 연결.
     *
     * @return array{ok:bool,message:string,membership_id?:int}
     */
    function lc_mp_attach_membership($group_id, $platform_code, $local_mt_id = 0, $external_merchant_id = '', $external_merchant_code = '')
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }
        $platform = lc_mp_get_platform_by_code($platform_code);
        if (!$platform) {
            return array('ok' => false, 'message' => 'platform not found');
        }
        $mem = lc_mp_db_table('advertiser_memberships');
        if (!lc_db_table_exists($mem)) {
            return array('ok' => false, 'message' => 'memberships table missing');
        }

        $platform_id = (int) $platform['platform_id'];
        $external_merchant_id = trim((string) $external_merchant_id);
        if ($external_merchant_id === '' && (int) $local_mt_id > 0) {
            $external_merchant_id = 'local:' . (int) $local_mt_id;
        }
        if ($external_merchant_id === '') {
            return array('ok' => false, 'message' => 'external_merchant_id or local_mt_id required');
        }

        $ext_esc = lc_sql_escape($external_merchant_id);
        $exists = sql_fetch(" SELECT membership_id FROM `{$mem}`
            WHERE platform_id = '{$platform_id}' AND external_merchant_id = '{$ext_esc}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['membership_id'])) {
            lc_sql_query(" UPDATE `{$mem}` SET
                group_id = '" . (int) $group_id . "',
                local_mt_id = '" . (int) $local_mt_id . "',
                external_merchant_code = '" . lc_sql_escape((string) $external_merchant_code) . "',
                status = 'active',
                updated_at = NOW()
                WHERE membership_id = '" . (int) $exists['membership_id'] . "' ", false);

            return array('ok' => true, 'message' => 'updated', 'membership_id' => (int) $exists['membership_id']);
        }

        lc_sql_query(" INSERT INTO `{$mem}`
            (`group_id`, `platform_id`, `local_mt_id`, `external_merchant_id`, `external_merchant_code`)
            VALUES ('" . (int) $group_id . "', '{$platform_id}', '" . (int) $local_mt_id . "',
             '{$ext_esc}', '" . lc_sql_escape((string) $external_merchant_code) . "') ", false);
        $id = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;
        lc_mp_audit('membership.attach', array(
            'group_id' => (int) $group_id,
            'platform' => (string) $platform_code,
            'local_mt_id' => (int) $local_mt_id,
            'external_merchant_id' => $external_merchant_id,
        ));

        return array('ok' => true, 'message' => 'attached', 'membership_id' => $id);
    }
}

if (!function_exists('lc_mp_membership_for_platform_in_group')) {
    /**
     * @return array|null
     */
    function lc_mp_membership_for_platform_in_group($group_id, $platform_code)
    {
        $group_id = (int) $group_id;
        $platform = lc_mp_get_platform_by_code($platform_code);
        if ($group_id <= 0 || !is_array($platform)) {
            return null;
        }
        foreach (lc_mp_list_memberships($group_id) as $m) {
            if ((int) ($m['platform_id'] ?? 0) === (int) $platform['platform_id']) {
                return $m;
            }
        }

        return null;
    }
}

if (!function_exists('lc_mp_resolve_local_mt_for_balance_lookup')) {
    /**
     * 잔액 조회용 로컬 mt_id 해석 (groupCode / externalMerchantId / mtId)
     *
     * @param array $lookup
     * @return array{ok:bool,message:string,mt_id?:int}
     */
    function lc_mp_resolve_local_mt_for_balance_lookup(array $lookup)
    {
        $mt_id = isset($lookup['mtId']) ? (int) $lookup['mtId'] : 0;
        if ($mt_id > 0) {
            return array('ok' => true, 'message' => 'ok', 'mt_id' => $mt_id);
        }

        $ext = isset($lookup['externalMerchantId']) ? trim((string) $lookup['externalMerchantId']) : '';
        $group_code = isset($lookup['groupCode']) ? trim((string) $lookup['groupCode']) : '';
        $mem = lc_mp_db_table('advertiser_memberships');
        $grp = lc_mp_db_table('advertiser_groups');
        $plat = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($mem)) {
            return array('ok' => false, 'message' => 'memberships missing');
        }

        $local_code = lc_sql_escape(lc_mp_local_platform_code());

        if ($ext !== '') {
            $ext_esc = lc_sql_escape($ext);
            $row = sql_fetch(" SELECT m.local_mt_id FROM `{$mem}` m
                INNER JOIN `{$plat}` p ON p.platform_id = m.platform_id
                WHERE m.external_merchant_id = '{$ext_esc}'
                  AND m.status = 'active'
                  AND (p.is_local = 1 OR p.platform_code = '{$local_code}')
                LIMIT 1 ");
            if (is_array($row) && (int) $row['local_mt_id'] > 0) {
                return array('ok' => true, 'message' => 'ok', 'mt_id' => (int) $row['local_mt_id']);
            }
            // local:123 형식
            if (preg_match('/^local:(\d+)$/', $ext, $m)) {
                return array('ok' => true, 'message' => 'ok', 'mt_id' => (int) $m[1]);
            }
        }

        if ($group_code !== '' && lc_db_table_exists($grp)) {
            $gc = lc_sql_escape($group_code);
            $row = sql_fetch(" SELECT m.local_mt_id FROM `{$mem}` m
                INNER JOIN `{$grp}` g ON g.group_id = m.group_id
                INNER JOIN `{$plat}` p ON p.platform_id = m.platform_id
                WHERE g.group_code = '{$gc}'
                  AND m.status = 'active'
                  AND m.local_mt_id > 0
                  AND (p.is_local = 1 OR p.platform_code = '{$local_code}')
                LIMIT 1 ");
            if (is_array($row) && (int) $row['local_mt_id'] > 0) {
                return array('ok' => true, 'message' => 'ok', 'mt_id' => (int) $row['local_mt_id']);
            }
        }

        return array('ok' => false, 'message' => 'merchant not found');
    }
}

if (!function_exists('lc_mp_fetch_primary_peer_balance')) {
    /**
     * 공동 입점 광고주의 primary(온오프CPA) 잔액을 조회.
     * 로컬이 primary면 로컬 잔액, 아니면 피어 HTTP 조회.
     *
     * @return array{ok:bool,message:string,balance?:int,local?:bool,skipped?:bool}
     */
    function lc_mp_fetch_primary_peer_balance($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0 || !lc_mp_enabled()) {
            return array('ok' => true, 'message' => 'skipped', 'balance' => 0, 'skipped' => true);
        }

        if (lc_mp_local_is_primary_wallet_platform()) {
            $bal = function_exists('lc_wallet_get_balance') ? lc_wallet_get_balance($mt_id) : 0;

            return array('ok' => true, 'message' => 'local primary', 'balance' => (int) $bal, 'local' => true);
        }

        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return array('ok' => true, 'message' => 'solo advertiser', 'balance' => 0, 'skipped' => true);
        }

        $primary_code = lc_mp_primary_wallet_platform_code();
        $primary_mem = lc_mp_membership_for_platform_in_group((int) $group['group_id'], $primary_code);
        $primary_plat = lc_mp_get_platform_by_code($primary_code);
        if (!is_array($primary_plat) || !empty($primary_plat['is_local'])) {
            return array('ok' => false, 'message' => 'primary peer platform missing');
        }

        $lookup = array(
            'groupCode' => (string) ($group['group_code'] ?? ''),
            'externalMerchantId' => is_array($primary_mem)
                ? (string) ($primary_mem['external_merchant_id'] ?? '')
                : '',
            'mtId' => is_array($primary_mem) ? (int) ($primary_mem['local_mt_id'] ?? 0) : 0,
        );

        if (!function_exists('lc_mp_adapter_fetch_wallet_balance')) {
            return array('ok' => false, 'message' => 'balance adapter missing');
        }

        return lc_mp_adapter_fetch_wallet_balance($primary_plat, $lookup);
    }
}

if (!function_exists('lc_mp_fetch_secondary_peer_balance')) {
    /**
     * 공동 입점 시 비-primary(링크커넥트) 잔액 조회 — primary 로컬에서 불변식 검사용
     *
     * @return array{ok:bool,message:string,balance?:int,skipped?:bool}
     */
    function lc_mp_fetch_secondary_peer_balance($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0 || !lc_mp_enabled() || !lc_mp_local_is_primary_wallet_platform()) {
            return array('ok' => true, 'message' => 'skipped', 'balance' => 0, 'skipped' => true);
        }

        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return array('ok' => true, 'message' => 'solo', 'balance' => 0, 'skipped' => true);
        }

        $peers = lc_mp_peer_platforms_for_mt($mt_id);
        if (!$peers) {
            return array('ok' => true, 'message' => 'no peer', 'balance' => 0, 'skipped' => true);
        }

        $peer = $peers[0];
        $peer_mem = lc_mp_membership_for_platform_in_group((int) $group['group_id'], (string) ($peer['platform_code'] ?? ''));
        $lookup = array(
            'groupCode' => (string) ($group['group_code'] ?? ''),
            'externalMerchantId' => is_array($peer_mem)
                ? (string) ($peer_mem['external_merchant_id'] ?? '')
                : '',
            'mtId' => is_array($peer_mem) ? (int) ($peer_mem['local_mt_id'] ?? 0) : 0,
        );

        if (!function_exists('lc_mp_adapter_fetch_wallet_balance')) {
            return array('ok' => false, 'message' => 'balance adapter missing');
        }

        return lc_mp_adapter_fetch_wallet_balance($peer, $lookup);
    }
}

if (!function_exists('lc_mp_ensure_balances_for_approve')) {
    /**
     * 승인 전 잔액 검사
     * - 로컬 잔액 ≥ amount
     * - 비-primary에서 승인: primary 잔액 ≥ amount
     * - primary에서 승인(공동): 차감 후에도 primary ≥ secondary 유지
     *
     * @return array{ok:bool,message:string}
     */
    function lc_mp_ensure_balances_for_approve($mt_id, $amount)
    {
        $mt_id = (int) $mt_id;
        $amount = (int) $amount;
        if ($amount <= 0) {
            return array('ok' => true, 'message' => 'ok');
        }

        $local_bal = function_exists('lc_wallet_get_balance') ? lc_wallet_get_balance($mt_id) : 0;
        if ($local_bal < $amount) {
            return array('ok' => false, 'message' => '광고비 잔액이 부족합니다.');
        }

        if (!lc_mp_enabled()) {
            return array('ok' => true, 'message' => 'ok');
        }

        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return array('ok' => true, 'message' => 'ok');
        }

        if (!lc_mp_local_is_primary_wallet_platform()) {
            $peer = lc_mp_fetch_primary_peer_balance($mt_id);
            if (empty($peer['ok'])) {
                return array(
                    'ok' => false,
                    'message' => '온오프CPA 잔액을 확인할 수 없습니다. ' . (string) ($peer['message'] ?? ''),
                );
            }
            if (empty($peer['skipped']) && (int) ($peer['balance'] ?? 0) < $amount) {
                return array('ok' => false, 'message' => '온오프CPA 광고비 잔액이 부족합니다.');
            }

            return array('ok' => true, 'message' => 'ok');
        }

        // primary 승인: 차감 후 불변식 유지 여부
        $secondary = lc_mp_fetch_secondary_peer_balance($mt_id);
        if (empty($secondary['ok'])) {
            // 피어 조회 실패 시 승인을 막지 않고 로컬만 검사 (가용성). 운영 로그용 audit.
            if (function_exists('lc_mp_audit')) {
                lc_mp_audit('wallet.peer_balance_check_failed', array(
                    'mt_id' => $mt_id,
                    'message' => (string) ($secondary['message'] ?? ''),
                ));
            }

            return array('ok' => true, 'message' => 'ok');
        }
        if (!empty($secondary['skipped'])) {
            return array('ok' => true, 'message' => 'ok');
        }

        $after = $local_bal - $amount;
        if ($after < (int) ($secondary['balance'] ?? 0)) {
            return array(
                'ok' => false,
                'message' => '승인 시 온오프CPA 잔액이 링크커넥트보다 작아집니다. 링크커넥트에서 승인하거나 잔액을 조정해 주세요.',
            );
        }

        return array('ok' => true, 'message' => 'ok');
    }
}

if (!function_exists('lc_mp_ensure_balance_invariant_for_charge')) {
    /**
     * 충전 완료 전 불변식: 비-primary 충전 후 잔액 ≤ primary 잔액
     *
     * @return array{ok:bool,message:string}
     */
    function lc_mp_ensure_balance_invariant_for_charge($mt_id, $projected_balance)
    {
        $mt_id = (int) $mt_id;
        $projected_balance = (int) $projected_balance;
        if (!lc_mp_enabled() || lc_mp_local_is_primary_wallet_platform()) {
            return array('ok' => true, 'message' => 'ok');
        }
        $group = lc_mp_find_group_by_local_mt($mt_id);
        if (!$group) {
            return array('ok' => true, 'message' => 'ok');
        }

        $peer = lc_mp_fetch_primary_peer_balance($mt_id);
        if (empty($peer['ok']) || !empty($peer['skipped'])) {
            return array(
                'ok' => false,
                'message' => '온오프CPA 잔액을 확인할 수 없어 충전을 완료할 수 없습니다.',
            );
        }
        if ($projected_balance > (int) ($peer['balance'] ?? 0)) {
            return array(
                'ok' => false,
                'message' => '링크커넥트 잔액은 온오프CPA 잔액 이하만 가능합니다. (온오프CPA '
                    . number_format((int) $peer['balance']) . '원)',
            );
        }

        return array('ok' => true, 'message' => 'ok');
    }
}
