<?php
/**
 * 다중 플랫폼 동기화 — lead_ref / outbox / inbound
 * 플래그 OFF 시 모든 public 함수는 즉시 반환.
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

// outbox 재시도 상한 (초과 시 dead 처리 후 관리자 알림)
if (!defined('LC_MP_OUTBOX_MAX_ATTEMPTS')) {
    define('LC_MP_OUTBOX_MAX_ATTEMPTS', 12);
}

if (!function_exists('lc_mp_verify_webhook_secret')) {
    function lc_mp_verify_webhook_secret(array $platform)
    {
        $expected = trim((string) ($platform['webhook_secret'] ?? ''));
        if ($expected === '') {
            return false;
        }
        $got = '';
        if (isset($_SERVER['HTTP_X_LC_PLATFORM_SECRET'])) {
            $got = (string) $_SERVER['HTTP_X_LC_PLATFORM_SECRET'];
        } elseif (isset($_GET['secret'])) {
            $got = (string) $_GET['secret'];
        }

        return $got !== '' && hash_equals($expected, $got);
    }
}

if (!function_exists('lc_mp_inbox_store')) {
    /**
     * @return array{ok:bool,message:string,inbox_id?:int,duplicate?:bool}
     */
    function lc_mp_inbox_store($source_platform_id, $event_type, $idempotency_key, array $payload)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $table = lc_mp_db_table('sync_inbox');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => 'inbox missing');
        }
        $key = lc_sql_escape((string) $idempotency_key);
        $exists = sql_fetch(" SELECT inbox_id FROM `{$table}` WHERE idempotency_key = '{$key}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['inbox_id'])) {
            return array('ok' => true, 'message' => 'duplicate', 'inbox_id' => (int) $exists['inbox_id'], 'duplicate' => true);
        }
        $event = lc_sql_escape((string) $event_type);
        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        lc_sql_query(" INSERT INTO `{$table}`
            (`source_platform_id`, `event_type`, `idempotency_key`, `payload_json`, `status`)
            VALUES ('" . (int) $source_platform_id . "', '{$event}', '{$key}', '{$json}', 'received') ", false);
        $id = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;

        return array('ok' => true, 'message' => 'stored', 'inbox_id' => $id, 'duplicate' => false);
    }
}

if (!function_exists('lc_mp_upsert_lead_ref_from_inbound')) {
    /**
     * 원격 플랫폼에서 유입된 DB를 lead_refs 에 미러링 (로컬 conversions 자동 생성은 2차).
     *
     * @return array{ok:bool,message:string,lead_ref_id?:int}
     */
    function lc_mp_upsert_lead_ref_from_inbound(array $platform, array $payload)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $leads = lc_mp_db_table('lead_refs');
        if (!lc_db_table_exists($leads)) {
            return array('ok' => false, 'message' => 'lead_refs missing');
        }

        $external_lead_id = trim((string) ($payload['externalLeadId'] ?? $payload['external_lead_id'] ?? ''));
        if ($external_lead_id === '') {
            return array('ok' => false, 'message' => 'externalLeadId required');
        }

        $platform_id = (int) ($platform['platform_id'] ?? 0);
        $ext_esc = lc_sql_escape($external_lead_id);
        $status = lc_sql_escape(strtolower((string) ($payload['status'] ?? 'pending')));
        $campaign = lc_sql_escape((string) ($payload['externalCampaignId'] ?? $payload['external_campaign_id'] ?? ''));
        $version = max(1, (int) ($payload['version'] ?? 1));
        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $group_id = (int) ($payload['groupId'] ?? $payload['group_id'] ?? 0);
        $local_mt_id = (int) ($payload['localMtId'] ?? $payload['local_mt_id'] ?? 0);
        // 원격 groupId 는 DB마다 다르므로 groupCode 로 로컬 그룹을 재해석
        if ($group_id <= 0) {
            $gcode = trim((string) ($payload['groupCode'] ?? $payload['group_code'] ?? ''));
            if ($gcode !== '' && function_exists('lc_mp_db_table') && lc_db_table_exists(lc_mp_db_table('advertiser_groups'))) {
                $g = sql_fetch(" SELECT group_id FROM `" . lc_mp_db_table('advertiser_groups') . "`
                    WHERE group_code = '" . lc_sql_escape($gcode) . "' LIMIT 1 ");
                if (is_array($g)) {
                    $group_id = (int) $g['group_id'];
                }
            }
        }

        $existing = sql_fetch(" SELECT * FROM `{$leads}`
            WHERE source_platform_id = '{$platform_id}' AND external_lead_id = '{$ext_esc}' LIMIT 1 ");

        if (is_array($existing) && !empty($existing['lead_ref_id'])) {
            if ((int) $existing['version'] > $version) {
                return array('ok' => true, 'message' => 'stale ignored', 'lead_ref_id' => (int) $existing['lead_ref_id']);
            }
            lc_sql_query(" UPDATE `{$leads}` SET
                status = '{$status}',
                version = '{$version}',
                external_campaign_id = '{$campaign}',
                payload_json = '{$json}',
                sync_status = 'synced',
                updated_at = NOW()
                WHERE lead_ref_id = '" . (int) $existing['lead_ref_id'] . "' ", false);

            return array('ok' => true, 'message' => 'updated', 'lead_ref_id' => (int) $existing['lead_ref_id']);
        }

        lc_sql_query(" INSERT INTO `{$leads}`
            (`group_id`, `local_mt_id`, `source_platform_id`, `external_lead_id`, `external_campaign_id`,
             `status`, `version`, `sync_status`, `payload_json`)
            VALUES ('{$group_id}', '{$local_mt_id}', '{$platform_id}', '{$ext_esc}', '{$campaign}',
             '{$status}', '{$version}', 'synced', '{$json}') ", false);
        $id = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;

        return array('ok' => true, 'message' => 'created', 'lead_ref_id' => $id);
    }
}

if (!function_exists('lc_mp_resolve_local_campaign_for_lead')) {
    /**
     * 인바운드 lead 를 매핑할 로컬 캠페인(cp_id) 결정.
     * 우선순위: payload.localCampaignId → payload.localCampaignCode → 광고주(mt)의 활성 캠페인 1개.
     *
     * @return int cp_id (0 = 미결정)
     */
    function lc_mp_resolve_local_campaign_for_lead($local_mt_id, array $payload)
    {
        $cp_table = lc_table('campaigns');
        if (!lc_db_table_exists($cp_table)) {
            return 0;
        }

        $cp_id = (int) ($payload['localCampaignId'] ?? $payload['local_cp_id'] ?? 0);
        if ($cp_id > 0) {
            $row = sql_fetch(" SELECT cp_id, mt_id FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
            if (is_array($row) && ((int) $local_mt_id <= 0 || (int) $row['mt_id'] === (int) $local_mt_id)) {
                return (int) $row['cp_id'];
            }
        }

        $code = trim((string) ($payload['localCampaignCode'] ?? $payload['local_cp_code'] ?? $payload['externalCampaignId'] ?? $payload['external_campaign_id'] ?? ''));
        if ($code !== '') {
            $code_esc = lc_sql_escape($code);
            if ((int) $local_mt_id > 0) {
                $row = sql_fetch(" SELECT cp_id FROM `{$cp_table}`
                    WHERE cp_code = '{$code_esc}' AND mt_id = '" . (int) $local_mt_id . "' LIMIT 1 ");
            } else {
                $row = sql_fetch(" SELECT cp_id FROM `{$cp_table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
            }
            if (is_array($row) && !empty($row['cp_id'])) {
                return (int) $row['cp_id'];
            }
        }

        // 광고주의 활성 캠페인이 정확히 1개면 그것으로.
        if ((int) $local_mt_id > 0) {
            $active = lc_sql_escape(defined('LC_STATUS_ACTIVE') ? LC_STATUS_ACTIVE : 'active');
            $rows = array();
            $res = sql_query(" SELECT cp_id FROM `{$cp_table}`
                WHERE mt_id = '" . (int) $local_mt_id . "' AND cp_status = '{$active}'
                ORDER BY cp_id ASC LIMIT 2 ", false);
            if ($res) {
                while ($r = sql_fetch_array($res)) {
                    $rows[] = (int) $r['cp_id'];
                }
            }
            if (count($rows) === 1) {
                return $rows[0];
            }
        }

        return 0;
    }
}

if (!function_exists('lc_mp_ensure_local_conversion_for_lead')) {
    /**
     * 인바운드 lead_ref 를 로컬 conversions 에 미러링하고 local_cv_id 를 연결한다.
     * - 로컬이 공동 입점 멤버(또는 단독)일 때 생성 — 승인 가능한 플랫폼에 미러.
     * - 멤버가 아니면 참조만 유지.
     * - 이미 local_cv_id 가 있으면 그대로 반환(멱등).
     * - lc_conversion_create 를 쓰지 않고 최소 필드만 삽입(중복/어뷰즈/파트너 로직 우회).
     *
     * @return array{ok:bool,message:string,cvId?:int,created?:bool}
     */
    function lc_mp_ensure_local_conversion_for_lead(array $lead_ref, array $payload)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $leads = lc_mp_db_table('lead_refs');
        $cv_table = lc_table('conversions');
        if (!lc_db_table_exists($leads) || !lc_db_table_exists($cv_table)) {
            return array('ok' => false, 'message' => 'tables missing');
        }

        $lead_ref_id = (int) ($lead_ref['lead_ref_id'] ?? 0);
        if ($lead_ref_id <= 0) {
            return array('ok' => false, 'message' => 'lead_ref_id required');
        }

        // 이미 연결됨 → 멱등 반환
        if ((int) ($lead_ref['local_cv_id'] ?? 0) > 0) {
            return array('ok' => true, 'message' => 'already linked', 'cvId' => (int) $lead_ref['local_cv_id'], 'created' => false);
        }

        // 로컬 광고주(mt) 결정
        $local_mt_id = (int) ($lead_ref['local_mt_id'] ?? 0);
        if ($local_mt_id <= 0) {
            $group_id = (int) ($lead_ref['group_id'] ?? 0);
            if ($group_id > 0) {
                $mem = lc_mp_db_table('advertiser_memberships');
                $local_code = lc_mp_local_platform_code();
                $plat = lc_mp_get_platform_by_code($local_code);
                if (is_array($plat) && lc_db_table_exists($mem)) {
                    $row = sql_fetch(" SELECT local_mt_id FROM `{$mem}`
                        WHERE group_id = '{$group_id}' AND platform_id = '" . (int) $plat['platform_id'] . "'
                          AND status = 'active' AND local_mt_id > 0 LIMIT 1 ");
                    if (is_array($row)) {
                        $local_mt_id = (int) $row['local_mt_id'];
                    }
                }
            }
        }
        $cp_id = lc_mp_resolve_local_campaign_for_lead($local_mt_id, $payload);
        // mt 미지정 시 캠페인 코드/ID 로 먼저 찾고, 그 캠페인의 mt_id 사용
        if ($cp_id <= 0 && $local_mt_id <= 0) {
            $cp_id = lc_mp_resolve_local_campaign_for_lead(0, $payload);
        }
        if ($cp_id > 0 && $local_mt_id <= 0) {
            $cp_probe = sql_fetch(" SELECT mt_id FROM `" . lc_table('campaigns') . "` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
            if (is_array($cp_probe)) {
                $local_mt_id = (int) ($cp_probe['mt_id'] ?? 0);
            }
        }
        if ($cp_id <= 0) {
            // mt 만 있고 캠페인이 여러 개면 실패 — 위에서 이미 시도함
            $cp_id = lc_mp_resolve_local_campaign_for_lead($local_mt_id, $payload);
        }
        if ($local_mt_id <= 0) {
            return array('ok' => false, 'message' => 'local_mt_id unresolved');
        }

        // 로컬에서 승인/처리할 수 없는 광고주면 로컬 conversion 미생성(참조만).
        $can_mutate = function_exists('lc_mp_local_can_mutate_for_mt')
            ? lc_mp_local_can_mutate_for_mt($local_mt_id)
            : lc_mp_local_is_management_for_mt($local_mt_id);
        if (!$can_mutate) {
            return array('ok' => false, 'message' => 'local not member — ref only');
        }

        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => 'local campaign unresolved');
        }

        $name = trim((string) ($payload['name'] ?? $payload['customerName'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? $payload['customerPhone'] ?? ''));
        if ($name === '') {
            $name = '외부접수';
        }

        $status_local = function_exists('lc_mp_status_map_local')
            ? lc_mp_status_map_local((string) ($payload['status'] ?? 'pending'))
            : (defined('LC_STATUS_PENDING') ? LC_STATUS_PENDING : 'pending');

        $cp_row = sql_fetch(" SELECT * FROM `" . lc_table('campaigns') . "` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        $merchant_price = is_array($cp_row) && function_exists('lc_campaign_resolve_merchant_price')
            ? (int) lc_campaign_resolve_merchant_price($cp_row)
            : (is_array($cp_row) ? (int) ($cp_row['cp_price'] ?? 0) : 0);
        $partner_price = is_array($cp_row) && function_exists('lc_campaign_resolve_partner_price')
            ? (int) lc_campaign_resolve_partner_price($cp_row)
            : 0;

        $cv_code = function_exists('lc_conversion_generate_code')
            ? lc_conversion_generate_code()
            : ('MP-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10)));
        $external_lead_id = (string) ($lead_ref['external_lead_id'] ?? '');
        $src_code = '';
        $src = lc_mp_get_platform_by_id((int) ($lead_ref['source_platform_id'] ?? 0));
        if (is_array($src)) {
            $src_code = strtolower((string) ($src['platform_code'] ?? ''));
        }

        lc_sql_query(" INSERT INTO `{$cv_table}` SET
            cv_code = '" . lc_sql_escape($cv_code) . "',
            pt_id = '0',
            cp_id = '{$cp_id}',
            lk_id = '0',
            cv_name = '" . lc_sql_escape($name) . "',
            cv_phone = '" . lc_sql_escape($phone) . "',
            cv_email = '" . lc_sql_escape((string) ($payload['email'] ?? '')) . "',
            cv_region = '" . lc_sql_escape((string) ($payload['region'] ?? '')) . "',
            cv_inquiry = '" . lc_sql_escape((string) ($payload['inquiry'] ?? '')) . "',
            cv_status = '" . lc_sql_escape($status_local) . "',
            cv_price = '{$merchant_price}',
            cv_partner_price = '{$partner_price}',
            cv_channel = '" . lc_sql_escape('external:' . $src_code) . "',
            cv_sub_id = '" . lc_sql_escape($external_lead_id) . "',
            cv_comment = '',
            cv_created_at = NOW(),
            cv_updated_at = NOW() ", false);

        $cv_id = function_exists('lc_sql_insert_id') ? (int) lc_sql_insert_id() : (int) sql_insert_id();
        if ($cv_id <= 0) {
            return array('ok' => false, 'message' => 'conversion insert failed');
        }

        lc_sql_query(" UPDATE `{$leads}` SET
            local_cv_id = '{$cv_id}',
            local_mt_id = '{$local_mt_id}',
            updated_at = NOW()
            WHERE lead_ref_id = '{$lead_ref_id}' ", false);

        lc_mp_audit('inbound.local_conversion', array(
            'lead_ref_id' => $lead_ref_id,
            'cv_id' => $cv_id,
            'cp_id' => $cp_id,
            'mt_id' => $local_mt_id,
            'status' => $status_local,
        ));

        // 미러 유입도 자체 접수와 동일하게 광고주/관리자 알림을 발송한다.
        // (lc_conversion_create 를 우회하므로 여기서 직접 호출)
        if (function_exists('lc_notification_emit_conversion')) {
            $meta = function_exists('lc_conversion_with_meta') ? lc_conversion_with_meta($cv_id) : null;
            if (!is_array($meta)) {
                $meta = array(
                    'cv_id'   => $cv_id,
                    'cv_code' => $cv_code,
                    'cp_name' => is_array($cp_row) ? (string) ($cp_row['cp_name'] ?? '캠페인') : '캠페인',
                    'pt_id'   => 0,
                    'mt_id'   => $local_mt_id,
                );
            }
            lc_notification_emit_conversion($meta, 'received');
        }

        return array('ok' => true, 'message' => 'created', 'cvId' => $cv_id, 'created' => true);
    }
}

if (!function_exists('lc_mp_apply_remote_status')) {
    /**
     * 원격(관리) 플랫폼이 보낸 상태 변경을 로컬 conversion 에 반영(수신측).
     * 관리 게이트를 우회하고, 역전송 훅은 건너뛴다(mp_no_sync). 멱등.
     *
     * @return array{ok:bool,message:string,cvId?:int,applied?:bool}
     */
    function lc_mp_apply_remote_status($external_lead_id, $status, $comment = '')
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $external_lead_id = trim((string) $external_lead_id);
        if ($external_lead_id === '') {
            return array('ok' => false, 'message' => 'externalLeadId required');
        }

        // 1) 원본 플랫폼: external_lead_id = 로컬 cv_code
        // 2) 미러 플랫폼: lead_ref.external_lead_id 또는 cv_sub_id 로 로컬 미러 건 조회
        $conversion = function_exists('lc_conversion_get_by_code')
            ? lc_conversion_get_by_code($external_lead_id)
            : null;
        if (!is_array($conversion) || empty($conversion['cv_id'])) {
            $conversion = null;
            $leads = lc_mp_db_table('lead_refs');
            if (lc_db_table_exists($leads)) {
                $ext_esc = lc_sql_escape($external_lead_id);
                $ref = sql_fetch(" SELECT local_cv_id FROM `{$leads}`
                    WHERE external_lead_id = '{$ext_esc}' AND local_cv_id > 0 LIMIT 1 ");
                if (is_array($ref) && (int) $ref['local_cv_id'] > 0 && function_exists('lc_conversion_get_by_id')) {
                    $conversion = lc_conversion_get_by_id((int) $ref['local_cv_id']);
                }
            }
        }
        if (!is_array($conversion) || empty($conversion['cv_id'])) {
            $conversion = null;
            $cv_table = lc_table('conversions');
            $ext_esc = lc_sql_escape($external_lead_id);
            $row = sql_fetch(" SELECT * FROM `{$cv_table}` WHERE cv_sub_id = '{$ext_esc}' ORDER BY cv_id DESC LIMIT 1 ");
            if (is_array($row) && !empty($row['cv_id'])) {
                $conversion = $row;
            }
        }
        if (!is_array($conversion) || empty($conversion['cv_id'])) {
            return array('ok' => false, 'message' => 'local conversion not found');
        }

        $cv_id = (int) $conversion['cv_id'];
        $cp_table = lc_table('campaigns');
        $cp = sql_fetch(" SELECT mt_id FROM `{$cp_table}` WHERE cp_id = '" . (int) $conversion['cp_id'] . "' LIMIT 1 ");
        $mt_id = is_array($cp) ? (int) $cp['mt_id'] : 0;
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => 'merchant unresolved');
        }

        $status_local = function_exists('lc_mp_status_map_local')
            ? lc_mp_status_map_local($status)
            : strtolower((string) $status);

        // 이미 동일 상태면 멱등 성공
        if ((string) $conversion['cv_status'] === $status_local) {
            return array('ok' => true, 'message' => 'already in status', 'cvId' => $cv_id, 'applied' => false);
        }

        $pending = defined('LC_STATUS_PENDING') ? LC_STATUS_PENDING : 'pending';
        $approved = defined('LC_STATUS_APPROVED') ? LC_STATUS_APPROVED : 'approved';
        $rejected = defined('LC_STATUS_REJECTED') ? LC_STATUS_REJECTED : 'rejected';

        // 승인 → 취소(환급): pending 이 아닌 경우의 대칭 환불
        if ((string) $conversion['cv_status'] === $approved
            && in_array($status_local, array($rejected, 'canceled', 'cancelled'), true)) {
            $price = (int) ($conversion['cv_price'] ?? 0);
            if ($mt_id > 0 && $price > 0 && function_exists('lc_wallet_refund_for_conversion')) {
                $refund = lc_wallet_refund_for_conversion(
                    $mt_id,
                    $cv_id,
                    $price,
                    (string) ($conversion['cv_code'] ?? '') . ' 피어승인취소 환급'
                );
                if (empty($refund['ok'])) {
                    return array(
                        'ok' => false,
                        'message' => (string) ($refund['message'] ?? 'refund failed'),
                        'cvId' => $cv_id,
                    );
                }
            }
            if (function_exists('lc_partner_debit_for_conversion')) {
                lc_partner_debit_for_conversion($conversion);
            }
            $cv_table = lc_table('conversions');
            $comment_esc = lc_sql_escape((string) $comment);
            lc_sql_query(" UPDATE `{$cv_table}` SET
                cv_status = '" . lc_sql_escape($rejected) . "',
                cv_comment = '{$comment_esc}',
                cv_reject_reason = '{$comment_esc}',
                cv_updated_at = NOW()
                WHERE cv_id = '{$cv_id}' ", false);

            lc_mp_audit('inbound.remote_status_refund', array(
                'cv_id' => $cv_id,
                'external_lead_id' => $external_lead_id,
                'status' => $rejected,
            ));

            return array('ok' => true, 'message' => 'refunded', 'cvId' => $cv_id, 'applied' => true);
        }

        if ((string) $conversion['cv_status'] !== $pending) {
            return array('ok' => true, 'message' => 'already processed', 'cvId' => $cv_id, 'applied' => false);
        }

        $result = lc_conversion_update_status($cv_id, $mt_id, $status_local, (string) $comment, array(
            'mp_remote_ack' => true,
            'mp_no_sync'    => true,
        ));

        if (empty($result['ok'])) {
            return array('ok' => false, 'message' => (string) ($result['message'] ?? 'apply failed'), 'cvId' => $cv_id);
        }

        lc_mp_audit('inbound.remote_status', array(
            'cv_id' => $cv_id,
            'external_lead_id' => $external_lead_id,
            'status' => $status_local,
        ));

        return array('ok' => true, 'message' => 'applied', 'cvId' => $cv_id, 'applied' => true);
    }
}

if (!function_exists('lc_mp_enqueue_status_change')) {
    /**
     * 로컬에서 외부 원본 DB 상태 변경 요청 → outbox.
     * 실제 로컬 conversion 업데이트를 대체하지 않음 — 외부 lead 전용.
     *
     * @return array{ok:bool,message:string,outbox_id?:int}
     */
    function lc_mp_enqueue_status_change($lead_ref_id, $new_status, $comment = '', $actor_mt_id = 0)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }

        $leads = lc_mp_db_table('lead_refs');
        $outbox = lc_mp_db_table('sync_outbox');
        if (!lc_db_table_exists($leads) || !lc_db_table_exists($outbox)) {
            return array('ok' => false, 'message' => 'tables missing');
        }

        $ref = sql_fetch(" SELECT * FROM `{$leads}` WHERE lead_ref_id = '" . (int) $lead_ref_id . "' LIMIT 1 ");
        if (!is_array($ref)) {
            return array('ok' => false, 'message' => 'lead_ref not found');
        }

        // 공동 입점 멤버만 상태 변경·역전송 가능
        if ($actor_mt_id > 0) {
            $can = function_exists('lc_mp_local_can_mutate_for_mt')
                ? lc_mp_local_can_mutate_for_mt($actor_mt_id)
                : lc_mp_local_is_management_for_mt($actor_mt_id);
            if (!$can) {
                return array('ok' => false, 'message' => '이 광고주는 로컬에서 상태를 변경할 수 없습니다.');
            }
        }

        $new_status = strtolower(trim((string) $new_status));
        $version = (int) $ref['version'] + 1;
        $idem = 'status:' . (int) $lead_ref_id . ':' . $new_status . ':' . $version;
        $idem_esc = lc_sql_escape($idem);
        $exists = sql_fetch(" SELECT outbox_id FROM `{$outbox}` WHERE idempotency_key = '{$idem_esc}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['outbox_id'])) {
            return array('ok' => true, 'message' => 'already queued', 'outbox_id' => (int) $exists['outbox_id']);
        }

        $payload = array(
            'command'            => 'status_change',
            'external_lead_id'   => (string) $ref['external_lead_id'],
            'status'             => $new_status,
            'comment'            => (string) $comment,
            'version'            => $version,
            'idempotency_key'    => $idem,
            'lead_ref_id'        => (int) $lead_ref_id,
        );
        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $target = (int) $ref['source_platform_id'];

        lc_sql_query(" INSERT INTO `{$outbox}`
            (`target_platform_id`, `lead_ref_id`, `command`, `idempotency_key`, `payload_json`, `status`, `next_attempt_at`)
            VALUES ('{$target}', '" . (int) $lead_ref_id . "', 'status_change', '{$idem_esc}', '{$json}', 'pending', NOW()) ", false);
        $oid = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;

        // optimistic local mirror — sync_status=pending until ACK
        $status_esc = lc_sql_escape($new_status);
        lc_sql_query(" UPDATE `{$leads}` SET
            status = '{$status_esc}',
            version = '{$version}',
            sync_status = 'pending',
            updated_at = NOW()
            WHERE lead_ref_id = '" . (int) $lead_ref_id . "' ", false);

        lc_mp_audit('outbox.enqueue', $payload);

        return array('ok' => true, 'message' => 'queued', 'outbox_id' => $oid);
    }
}

if (!function_exists('lc_mp_process_outbox_once')) {
    /**
     * 단일 outbox 항목 처리 (크론/수동).
     *
     * @return array{ok:bool,message:string,processed?:int}
     */
    function lc_mp_process_outbox_once($limit = 20)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => true, 'message' => 'disabled', 'processed' => 0);
        }
        $outbox = lc_mp_db_table('sync_outbox');
        $leads = lc_mp_db_table('lead_refs');
        if (!lc_db_table_exists($outbox)) {
            return array('ok' => false, 'message' => 'outbox missing');
        }

        $limit = max(1, min(100, (int) $limit));
        $result = sql_query(" SELECT * FROM `{$outbox}`
            WHERE status IN ('pending','failed')
              AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
            ORDER BY outbox_id ASC
            LIMIT {$limit} ", false);

        $processed = 0;
        $failed = 0;
        $failed_dead = 0;
        if (!$result) {
            return array('ok' => true, 'message' => 'none', 'processed' => 0, 'failed' => 0, 'dead' => 0);
        }

        while ($row = sql_fetch_array($result)) {
            $platform = lc_mp_get_platform_by_id((int) $row['target_platform_id']);
            if (!$platform) {
                // next_attempt_at 를 반드시 채워 다음 실행에서 즉시 재선택되는 것을 막는다.
                $miss_attempts = (int) $row['attempts'] + 1;
                $miss_status = ($miss_attempts >= LC_MP_OUTBOX_MAX_ATTEMPTS) ? 'dead' : 'failed';
                $miss_delay = min(3600, 60 * $miss_attempts);
                lc_sql_query(" UPDATE `{$outbox}` SET status='{$miss_status}', last_error='platform missing',
                    attempts='{$miss_attempts}',
                    next_attempt_at = DATE_ADD(NOW(), INTERVAL {$miss_delay} SECOND), updated_at=NOW()
                    WHERE outbox_id='" . (int) $row['outbox_id'] . "' ", false);
                if ($miss_status === 'dead') {
                    $failed_dead++;
                }
                $failed++;
                continue;
            }
            $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
            if (!is_array($payload)) {
                $payload = array();
            }
            $command = trim((string) ($row['command'] ?? 'status_change'));
            if ($command === 'inbound_lead') {
                $push = function_exists('lc_mp_adapter_push_inbound_lead')
                    ? lc_mp_adapter_push_inbound_lead($platform, $payload)
                    : array('ok' => false, 'message' => 'inbound_lead adapter missing');
            } else {
                $push = lc_mp_adapter_push_status($platform, $payload);
            }
            $attempts = (int) $row['attempts'] + 1;
            if (!empty($push['ok'])) {
                lc_sql_query(" UPDATE `{$outbox}` SET status='done', attempts='{$attempts}', last_error='', updated_at=NOW()
                    WHERE outbox_id='" . (int) $row['outbox_id'] . "' ", false);
                if (lc_db_table_exists($leads) && (int) $row['lead_ref_id'] > 0) {
                    lc_sql_query(" UPDATE `{$leads}` SET sync_status='synced', last_error='', updated_at=NOW()
                        WHERE lead_ref_id='" . (int) $row['lead_ref_id'] . "' ", false);
                }
                $processed++;
            } else {
                $err = lc_sql_escape(substr((string) ($push['message'] ?? 'failed'), 0, 480));
                // 재시도 상한 초과분은 dead 로 내려 무한 재시도를 막고 운영자 확인 대상으로 남긴다.
                $dead = ($attempts >= LC_MP_OUTBOX_MAX_ATTEMPTS);
                if ($dead) {
                    lc_sql_query(" UPDATE `{$outbox}` SET status='dead', attempts='{$attempts}', last_error='{$err}',
                        next_attempt_at = NULL, updated_at=NOW()
                        WHERE outbox_id='" . (int) $row['outbox_id'] . "' ", false);
                    lc_mp_audit('outbox.dead', array(
                        'outbox_id' => (int) $row['outbox_id'],
                        'command' => $command,
                        'attempts' => $attempts,
                        'error' => (string) ($push['message'] ?? 'failed'),
                    ));
                    $failed_dead++;
                } else {
                    $delay = min(3600, 30 * $attempts);
                    lc_sql_query(" UPDATE `{$outbox}` SET status='failed', attempts='{$attempts}', last_error='{$err}',
                        next_attempt_at = DATE_ADD(NOW(), INTERVAL {$delay} SECOND), updated_at=NOW()
                        WHERE outbox_id='" . (int) $row['outbox_id'] . "' ", false);
                }
                if (lc_db_table_exists($leads) && (int) $row['lead_ref_id'] > 0) {
                    lc_sql_query(" UPDATE `{$leads}` SET sync_status='failed', last_error='{$err}', updated_at=NOW()
                        WHERE lead_ref_id='" . (int) $row['lead_ref_id'] . "' ", false);
                }
                $failed++;
            }
        }

        if ($failed_dead > 0) {
            lc_mp_notify_admin_outbox_dead($failed_dead);
        }

        return array(
            'ok' => true,
            'message' => 'done',
            'processed' => $processed,
            'failed' => $failed,
            'dead' => $failed_dead,
        );
    }
}

if (!function_exists('lc_mp_notify_admin_outbox_dead')) {
    /**
     * 재시도 상한을 넘겨 포기한 동기화 건을 관리자에게 알린다.
     * 이번 실행에서 새로 dead 가 된 건수만 대상이라 반복 알림이 쌓이지 않는다.
     */
    function lc_mp_notify_admin_outbox_dead($count)
    {
        $count = (int) $count;
        if ($count <= 0 || !function_exists('lc_notification_create')) {
            return;
        }
        lc_notification_create(array(
            'center'   => 'admin',
            'userId'   => 0,
            'type'     => 'system',
            'priority' => 'critical',
            'title'    => '플랫폼 동기화 실패',
            'body'     => $count . '건이 재시도 한도를 초과했습니다. 수동 확인이 필요합니다.',
            'link'     => '/admin/platform',
            'refType'  => 'platform_outbox',
            'refId'    => 0,
        ));
    }
}

if (!function_exists('lc_mp_outbox_enqueue_inbound_lead')) {
    /**
     * 신규 리드의 관리 플랫폼 푸시를 재시도 큐에 적재한다.
     * idempotency_key UNIQUE 로 중복 적재는 자동 차단된다.
     *
     * @return array{ok:bool,message:string,outbox_id?:int}
     */
    function lc_mp_outbox_enqueue_inbound_lead($target_platform_id, array $payload)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $outbox = lc_mp_db_table('sync_outbox');
        if (!lc_db_table_exists($outbox)) {
            return array('ok' => false, 'message' => 'outbox missing');
        }
        $target_platform_id = (int) $target_platform_id;
        $idem = trim((string) ($payload['idempotencyKey'] ?? ''));
        if ($target_platform_id <= 0 || $idem === '') {
            return array('ok' => false, 'message' => 'target/idempotencyKey required');
        }

        $idem_esc = lc_sql_escape($idem);
        $exists = sql_fetch(" SELECT outbox_id FROM `{$outbox}` WHERE idempotency_key = '{$idem_esc}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['outbox_id'])) {
            return array('ok' => true, 'message' => 'already queued', 'outbox_id' => (int) $exists['outbox_id']);
        }

        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        lc_sql_query(" INSERT INTO `{$outbox}`
            (`target_platform_id`, `lead_ref_id`, `command`, `idempotency_key`, `payload_json`, `status`, `next_attempt_at`)
            VALUES ('{$target_platform_id}', '0', 'inbound_lead', '{$idem_esc}', '{$json}', 'pending',
                DATE_ADD(NOW(), INTERVAL 30 SECOND)) ", false);
        $oid = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;

        lc_mp_audit('outbox.enqueue_inbound_lead', array(
            'outbox_id' => $oid,
            'target_platform_id' => $target_platform_id,
            'idempotency_key' => $idem,
        ));

        return array('ok' => true, 'message' => 'queued', 'outbox_id' => $oid);
    }
}

if (!function_exists('lc_mp_on_local_conversion_status_changed')) {
    /**
     * 로컬 승인/취소 후 상대 플랫폼에 ACK 동기화.
     * - 단독 광고주 → no-op
     * - 미러본(lead_ref 있음) → 원본 플랫폼으로 status push
     * - 원본(lead_ref 없음) + 공동 입점 → 피어 플랫폼으로 status push
     * 지갑: 로컬 개시 차감 + (ACK 수신 시 primary/온오프CPA 추가 차감)
     */
    function lc_mp_on_local_conversion_status_changed($cv_id, $mt_id, $new_status, $comment = '')
    {
        if (!lc_mp_enabled()) {
            return;
        }
        $cv_id = (int) $cv_id;
        $mt_id = (int) $mt_id;
        $can = function_exists('lc_mp_local_can_mutate_for_mt')
            ? lc_mp_local_can_mutate_for_mt($mt_id)
            : lc_mp_local_is_management_for_mt($mt_id);
        if (!$can) {
            lc_mp_audit('conversion.status_blocked', array(
                'cv_id' => $cv_id,
                'mt_id' => $mt_id,
                'status' => (string) $new_status,
            ));

            return;
        }

        $leads = lc_mp_db_table('lead_refs');
        if (lc_db_table_exists($leads)) {
            $ref = sql_fetch(" SELECT * FROM `{$leads}` WHERE local_cv_id = '{$cv_id}' LIMIT 1 ");
            if (is_array($ref) && !empty($ref['lead_ref_id'])) {
                $src = lc_mp_get_platform_by_id((int) $ref['source_platform_id']);
                // 원본이 원격이면 원본으로 ACK
                if (is_array($src) && empty($src['is_local'])) {
                    lc_mp_enqueue_status_change((int) $ref['lead_ref_id'], $new_status, $comment, $mt_id);

                    return;
                }
            }
        }

        // 원본 로컬 DB → 공동 입점 피어로 상태 푸시
        $peers = function_exists('lc_mp_peer_platforms_for_mt')
            ? lc_mp_peer_platforms_for_mt($mt_id)
            : array();
        if (!$peers) {
            return;
        }

        $conversion = function_exists('lc_conversion_get_by_id') ? lc_conversion_get_by_id($cv_id) : null;
        if (!is_array($conversion)) {
            return;
        }
        $ext_id = (string) ($conversion['cv_code'] ?? '');
        if ($ext_id === '') {
            return;
        }

        foreach ($peers as $peer) {
            lc_mp_enqueue_peer_status_change($peer, $ext_id, $new_status, $comment, $cv_id);
        }
    }
}

if (!function_exists('lc_mp_enqueue_peer_status_change')) {
    /**
     * lead_ref 없이 원본 로컬 DB 상태를 피어로 푸시(outbox).
     * external_lead_id = 로컬 cv_code (수신측 remote_status 가 cv_code 로 찾음)
     *
     * @return array{ok:bool,message:string,outbox_id?:int}
     */
    function lc_mp_enqueue_peer_status_change(array $peer, $external_lead_id, $new_status, $comment = '', $cv_id = 0)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'disabled');
        }
        $outbox = lc_mp_db_table('sync_outbox');
        if (!lc_db_table_exists($outbox)) {
            return array('ok' => false, 'message' => 'outbox missing');
        }
        $target = (int) ($peer['platform_id'] ?? 0);
        $external_lead_id = trim((string) $external_lead_id);
        $new_status = strtolower(trim((string) $new_status));
        if ($target <= 0 || $external_lead_id === '') {
            return array('ok' => false, 'message' => 'target/externalLeadId required');
        }

        $idem = 'peer_status:' . $target . ':' . $external_lead_id . ':' . $new_status;
        $idem_esc = lc_sql_escape($idem);
        $exists = sql_fetch(" SELECT outbox_id FROM `{$outbox}` WHERE idempotency_key = '{$idem_esc}' LIMIT 1 ");
        if (is_array($exists) && !empty($exists['outbox_id'])) {
            return array('ok' => true, 'message' => 'already queued', 'outbox_id' => (int) $exists['outbox_id']);
        }

        $payload = array(
            'command'            => 'status_change',
            'external_lead_id'   => $external_lead_id,
            'status'             => $new_status,
            'comment'            => (string) $comment,
            'version'            => 1,
            'idempotency_key'    => $idem,
            'lead_ref_id'        => 0,
            'local_cv_id'        => (int) $cv_id,
        );
        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        lc_sql_query(" INSERT INTO `{$outbox}`
            (`target_platform_id`, `lead_ref_id`, `command`, `idempotency_key`, `payload_json`, `status`, `next_attempt_at`)
            VALUES ('{$target}', '0', 'status_change', '{$idem_esc}', '{$json}', 'pending', NOW()) ", false);
        $oid = function_exists('sql_insert_id') ? (int) sql_insert_id() : 0;
        lc_mp_audit('outbox.enqueue_peer_status', $payload);

        return array('ok' => true, 'message' => 'queued', 'outbox_id' => $oid);
    }
}

if (!function_exists('lc_mp_on_local_conversion_created')) {
    /**
     * 로컬 신규 DB → 공동 입점 피어로 inbound_lead 미러.
     * 단독 광고주 / 피어 없음 → no-op.
     */
    function lc_mp_on_local_conversion_created($cv_id, $mt_id = 0)
    {
        if (!lc_mp_enabled()) {
            return;
        }
        $cv_id = (int) $cv_id;
        $mt_id = (int) $mt_id;
        if ($cv_id <= 0) {
            return;
        }

        $conversion = function_exists('lc_conversion_get_by_id') ? lc_conversion_get_by_id($cv_id) : null;
        if (!is_array($conversion)) {
            return;
        }
        if ($mt_id <= 0) {
            $cp = sql_fetch(" SELECT mt_id FROM `" . lc_table('campaigns') . "` WHERE cp_id = '" . (int) $conversion['cp_id'] . "' LIMIT 1 ");
            $mt_id = is_array($cp) ? (int) $cp['mt_id'] : 0;
        }
        if ($mt_id <= 0) {
            return;
        }

        // 미러로 들어온 건(external:*) 은 다시 푸시하지 않음
        $channel = (string) ($conversion['cv_channel'] ?? '');
        if (strpos($channel, 'external:') === 0) {
            return;
        }

        $peers = function_exists('lc_mp_peer_platforms_for_mt')
            ? lc_mp_peer_platforms_for_mt($mt_id)
            : array();
        if (!$peers) {
            return;
        }

        $group = lc_mp_find_group_by_local_mt($mt_id);
        $cp_row = sql_fetch(" SELECT cp_code, cp_id FROM `" . lc_table('campaigns') . "` WHERE cp_id = '" . (int) $conversion['cp_id'] . "' LIMIT 1 ");
        $payload = array(
            'sourcePlatform'     => lc_mp_local_platform_code(),
            'eventType'          => 'lead.upsert',
            'externalLeadId'     => (string) ($conversion['cv_code'] ?? ''),
            'externalCampaignId' => is_array($cp_row) ? (string) ($cp_row['cp_code'] ?? '') : '',
            'localCampaignCode'  => is_array($cp_row) ? (string) ($cp_row['cp_code'] ?? '') : '',
            'status'             => 'pending',
            'name'               => (string) ($conversion['cv_name'] ?? ''),
            'phone'              => (string) ($conversion['cv_phone'] ?? ''),
            'email'              => (string) ($conversion['cv_email'] ?? ''),
            'region'             => (string) ($conversion['cv_region'] ?? ''),
            'inquiry'            => (string) ($conversion['cv_inquiry'] ?? ''),
            'groupCode'          => is_array($group) ? (string) ($group['group_code'] ?? '') : '',
            'version'            => 1,
            'idempotencyKey'     => lc_mp_local_platform_code() . ':' . (string) ($conversion['cv_code'] ?? '') . ':1',
        );

        if (!function_exists('lc_mp_adapter_push_inbound_lead')) {
            return;
        }
        foreach ($peers as $peer) {
            $push = lc_mp_adapter_push_inbound_lead($peer, $payload);
            lc_mp_audit('outbound.inbound_lead', array(
                'cv_id' => $cv_id,
                'mt_id' => $mt_id,
                'target' => (string) ($peer['platform_code'] ?? ''),
                'ok' => !empty($push['ok']),
                'message' => (string) ($push['message'] ?? ''),
            ));
            if (empty($push['ok']) && function_exists('lc_mp_outbox_enqueue_inbound_lead')) {
                lc_mp_outbox_enqueue_inbound_lead((int) $peer['platform_id'], $payload);
            }
        }
    }
}
