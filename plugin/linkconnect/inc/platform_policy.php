<?php
/**
 * 광고주 관리 플랫폼 정책
 *
 * 규칙:
 * - 단독 입점: 해당 플랫폼만 management
 * - 공동 입점(로컬+원격): 기본 management = 로컬(ONOFFCPA)
 * - 광고주는 관리 플랫폼에서만 조회·상태 변경
 */
if (!defined('_GNUBOARD_')) {
    exit;
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
     * 로컬 광고주(mt_id)가 이 인스턴스에서 DB 상태 변경을 해도 되는지.
     * 플래그 OFF 또는 멤버십 없음 → true (기존 동작 유지)
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
