<?php
/**
 * 관리자 — 다중 플랫폼 정책/멤버십 조회·저장 (플래그 OFF 시 404)
 *
 * GET  ?action=overview
 * POST { action: "upsert_policy", groupId, managementPlatformId, reason }
 * POST { action: "ensure_schema" }  — 플래그 ON 후 스키마 강제 생성
 */
require_once __DIR__ . '/_common.php';

lc_mp_require_enabled();
lc_api_require_admin();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $platforms_table = lc_mp_db_table('platforms');
    $groups_table = lc_mp_db_table('advertiser_groups');
    $pol_table = lc_mp_db_table('management_policies');

    $platforms = array();
    if (lc_db_table_exists($platforms_table)) {
        $r = sql_query(" SELECT platform_id, platform_code, platform_name, is_local, status,
            (api_base_url <> '') AS has_api_base,
            (webhook_secret <> '') AS has_webhook_secret,
            (outbound_token <> '') AS has_outbound_token
            FROM `{$platforms_table}` ORDER BY platform_id ASC ", false);
        if ($r) {
            while ($row = sql_fetch_array($r)) {
                $platforms[] = $row;
            }
        }
    }

    $groups = array();
    if (lc_db_table_exists($groups_table)) {
        $r = sql_query(" SELECT g.*, p.management_platform_id
            FROM `{$groups_table}` g
            LEFT JOIN `{$pol_table}` p ON p.group_id = g.group_id
            ORDER BY g.group_id DESC LIMIT 100 ", false);
        if ($r) {
            while ($row = sql_fetch_array($r)) {
                $row['memberships'] = lc_mp_list_memberships((int) $row['group_id']);
                $mgmt = lc_mp_get_management_platform((int) $row['group_id']);
                $row['managementPlatformCode'] = is_array($mgmt) ? (string) ($mgmt['platform_code'] ?? '') : '';
                $groups[] = $row;
            }
        }
    }

    lc_api_success(array(
        'localPlatform' => lc_mp_local_platform_code(),
        'platforms'     => $platforms,
        'groups'        => $groups,
        'schemaReady'   => lc_db_table_exists($platforms_table),
    ));
}

if ($method === 'POST') {
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'ensure_schema') {
        $r = lc_mp_db_ensure_schema();
        if (empty($r['ok'])) {
            lc_api_error($r['message'], 'SCHEMA_FAILED', 500);
        }
        lc_api_success($r);
    }

    if ($action === 'upsert_policy') {
        $group_id = isset($body['groupId']) ? (int) $body['groupId'] : 0;
        $platform_id = isset($body['managementPlatformId']) ? (int) $body['managementPlatformId'] : 0;
        $reason = isset($body['reason']) ? (string) $body['reason'] : '';
        if ($group_id <= 0 || $platform_id <= 0) {
            lc_api_error('groupId and managementPlatformId required', 'INVALID', 400);
        }
        $r = lc_mp_upsert_policy($group_id, $platform_id, $reason);
        if (empty($r['ok'])) {
            lc_api_error($r['message'], 'POLICY_FAILED', 400);
        }
        lc_api_success($r);
    }

    if ($action === 'create_group') {
        $r = lc_mp_create_group(
            (string) ($body['displayName'] ?? ''),
            (string) ($body['businessNumber'] ?? ''),
            (string) ($body['groupCode'] ?? '')
        );
        if (empty($r['ok'])) {
            lc_api_error($r['message'], 'GROUP_FAILED', 400);
        }
        lc_api_success($r);
    }

    if ($action === 'attach_membership') {
        $r = lc_mp_attach_membership(
            (int) ($body['groupId'] ?? 0),
            (string) ($body['platformCode'] ?? ''),
            (int) ($body['localMtId'] ?? 0),
            (string) ($body['externalMerchantId'] ?? ''),
            (string) ($body['externalMerchantCode'] ?? '')
        );
        if (empty($r['ok'])) {
            lc_api_error($r['message'], 'MEMBERSHIP_FAILED', 400);
        }
        lc_api_success($r);
    }

    lc_api_error('invalid action', 'INVALID_ACTION', 400);
}

lc_api_error('method not allowed', 'METHOD_NOT_ALLOWED', 405);
