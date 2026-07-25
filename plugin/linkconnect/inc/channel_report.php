<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_channel_report_table')) {
    function lc_channel_report_table()
    {
        return lc_table('channel_reports');
    }
}

if (!function_exists('lc_channel_report_create')) {
    function lc_channel_report_create(array $payload)
    {
        if (!lc_db_table_exists(lc_channel_report_table())) {
            return array('ok' => false, 'message' => '신고 테이블이 없습니다.');
        }

        $cv_id = (int) ($payload['cvId'] ?? 0);
        $mt_id = (int) ($payload['mtId'] ?? 0);
        $channel = trim((string) ($payload['channel'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($cv_id <= 0 || $mt_id <= 0 || $reason === '') {
            return array('ok' => false, 'message' => '필수 정보가 누락되었습니다.');
        }

        $conversion = function_exists('lc_conversion_get_by_id') ? lc_conversion_get_by_id($cv_id) : null;
        if (!is_array($conversion)) {
            return array('ok' => false, 'message' => '디비를 찾을 수 없습니다.');
        }

        $pt_id = (int) ($conversion['pt_id'] ?? 0);
        $table = lc_channel_report_table();
        lc_sql_query("
            INSERT INTO `{$table}` (cv_id, pt_id, mt_id, cr_channel, cr_reason, cr_status)
            VALUES (
                {$cv_id}, {$pt_id}, {$mt_id},
                '" . lc_sql_escape($channel !== '' ? $channel : (string) ($conversion['cv_channel'] ?? '')) . "',
                '" . lc_sql_escape($reason) . "',
                'pending'
            )
        ", false);
        $cr_id = (int) lc_sql_insert_id();

        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'abuse',
                'title'   => '금지 채널 신고',
                'body'    => $reason . ' · CV#' . $cv_id,
                'link'    => '/admin/inspections',
                'refType' => 'channel_report',
                'refId'   => $cr_id,
            ));
            if ($pt_id > 0) {
                lc_notification_create(array(
                    'center'  => 'partner',
                    'userId'  => $pt_id,
                    'type'    => 'system',
                    'title'   => '금지 채널 유입 신고 접수',
                    'body'    => $reason,
                    'link'    => '/partner/support',
                    'refType' => 'channel_report',
                    'refId'   => $cr_id,
                ));
            }
        }

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('channel_report', 'conversion', $cv_id, '금지 채널 신고: ' . $reason);
        }

        return array('ok' => true, 'message' => '신고가 접수되었습니다.', 'id' => $cr_id);
    }
}

if (!function_exists('lc_channel_report_update_status')) {
    function lc_channel_report_update_status($cr_id, $status, $memo = '')
    {
        $cr_id = (int) $cr_id;
        $status = trim((string) $status);
        $allowed = array('pending', 'reviewing', 'sanctioned', 'dismissed');
        if ($cr_id <= 0 || !in_array($status, $allowed, true)) {
            return array('ok' => false, 'message' => '상태 변경에 실패했습니다.');
        }

        $table = lc_channel_report_table();
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cr_id = {$cr_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '신고를 찾을 수 없습니다.');
        }

        lc_sql_query("
            UPDATE `{$table}`
            SET cr_status = '" . lc_sql_escape($status) . "',
                cr_admin_memo = '" . lc_sql_escape($memo) . "',
                cr_updated_at = NOW()
            WHERE cr_id = {$cr_id}
        ", false);

        if ($status === 'sanctioned') {
            $pt_id = (int) ($row['pt_id'] ?? 0);
            if ($pt_id > 0 && function_exists('lc_partner_update_status')) {
                lc_partner_update_status($pt_id, LC_PARTNER_STATUS_SUSPENDED);
            }
            if ($pt_id > 0 && function_exists('lc_abuse_refresh_partner_score')) {
                lc_sql_query(" UPDATE `" . lc_table('partners') . "` SET pt_abuse_score = LEAST(100, pt_abuse_score + 20) WHERE pt_id = {$pt_id} ", false);
            }
        }

        return array('ok' => true, 'message' => '신고 상태가 변경되었습니다.');
    }
}

if (!function_exists('lc_channel_report_list_for_api')) {
    function lc_channel_report_list_for_api(array $filters = array())
    {
        if (!lc_db_table_exists(lc_channel_report_table())) {
            return array();
        }
        $table = lc_channel_report_table();
        $cv = lc_table('conversions');
        $pt = lc_table('partners');
        $where = array('1=1');
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = "cr.cr_status = '" . lc_sql_escape($status) . "'";
        }
        $items = array();
        $result = lc_sql_query("
            SELECT cr.*, cv.cv_code, p.pt_code, p.pt_name
            FROM `{$table}` cr
            LEFT JOIN `{$cv}` cv ON cv.cv_id = cr.cv_id
            LEFT JOIN `{$pt}` p ON p.pt_id = cr.pt_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY cr.cr_id DESC
            LIMIT 100
        ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'        => (int) ($row['cr_id'] ?? 0),
                    'cvId'      => (int) ($row['cv_id'] ?? 0),
                    'cvCode'    => (string) ($row['cv_code'] ?? ''),
                    'ptId'      => (int) ($row['pt_id'] ?? 0),
                    'partner'   => (string) ($row['pt_code'] ?? ''),
                    'partnerName'=> (string) ($row['pt_name'] ?? ''),
                    'channel'   => (string) ($row['cr_channel'] ?? ''),
                    'reason'    => (string) ($row['cr_reason'] ?? ''),
                    'status'    => (string) ($row['cr_status'] ?? ''),
                    'adminMemo' => (string) ($row['cr_admin_memo'] ?? ''),
                    'createdAt' => (string) ($row['cr_created_at'] ?? ''),
                );
            }
        }
        return $items;
    }
}
