<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_notification_table')) {
    function lc_notification_table()
    {
        return lc_table('notifications');
    }
}

if (!function_exists('lc_notification_create')) {
    function lc_notification_create(array $payload)
    {
        if (!lc_db_table_exists(lc_notification_table())) {
            return 0;
        }

        $center = trim((string) ($payload['center'] ?? 'admin'));
        $user_id = (int) ($payload['userId'] ?? 0);
        $type = trim((string) ($payload['type'] ?? 'system'));
        $priority = trim((string) ($payload['priority'] ?? 'normal'));
        if ($priority !== 'critical') {
            $priority = 'normal';
        }
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $link = trim((string) ($payload['link'] ?? ''));
        $ref_type = trim((string) ($payload['refType'] ?? ''));
        $ref_id = (int) ($payload['refId'] ?? 0);

        if ($title === '') {
            return 0;
        }

        $table = lc_notification_table();
        $has_priority = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'nf_priority');

        if ($has_priority) {
            lc_sql_query("
                INSERT INTO `{$table}` (
                    nf_center, nf_user_id, nf_type, nf_priority, nf_title, nf_body, nf_link, nf_ref_type, nf_ref_id
                ) VALUES (
                    '" . lc_sql_escape($center) . "',
                    {$user_id},
                    '" . lc_sql_escape($type) . "',
                    '" . lc_sql_escape($priority) . "',
                    '" . lc_sql_escape($title) . "',
                    '" . lc_sql_escape($body) . "',
                    '" . lc_sql_escape($link) . "',
                    '" . lc_sql_escape($ref_type) . "',
                    {$ref_id}
                )
            ", false);
        } else {
            lc_sql_query("
                INSERT INTO `{$table}` (
                    nf_center, nf_user_id, nf_type, nf_title, nf_body, nf_link, nf_ref_type, nf_ref_id
                ) VALUES (
                    '" . lc_sql_escape($center) . "',
                    {$user_id},
                    '" . lc_sql_escape($type) . "',
                    '" . lc_sql_escape($title) . "',
                    '" . lc_sql_escape($body) . "',
                    '" . lc_sql_escape($link) . "',
                    '" . lc_sql_escape($ref_type) . "',
                    {$ref_id}
                )
            ", false);
        }

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_notification_row_to_api')) {
    function lc_notification_row_to_api(array $row)
    {
        $priority = (string) ($row['nf_priority'] ?? 'normal');
        if ($priority !== 'critical') {
            $priority = 'normal';
        }

        return array(
            'id'        => (int) ($row['nf_id'] ?? 0),
            'center'    => (string) ($row['nf_center'] ?? ''),
            'userId'    => (int) ($row['nf_user_id'] ?? 0),
            'type'      => (string) ($row['nf_type'] ?? ''),
            'priority'  => $priority,
            'title'     => (string) ($row['nf_title'] ?? ''),
            'body'      => (string) ($row['nf_body'] ?? ''),
            'link'      => (string) ($row['nf_link'] ?? ''),
            'refType'   => (string) ($row['nf_ref_type'] ?? ''),
            'refId'     => (int) ($row['nf_ref_id'] ?? 0),
            'read'      => !empty($row['nf_read_at']),
            'readAt'    => (string) ($row['nf_read_at'] ?? ''),
            'createdAt' => (string) ($row['nf_created_at'] ?? ''),
            'critical'  => $priority === 'critical',
        );
    }
}

if (!function_exists('lc_notification_list_for_api')) {
    function lc_notification_list_for_api($center, $user_id = 0, array $filters = array())
    {
        if (!lc_db_table_exists(lc_notification_table())) {
            return array('items' => array(), 'unread' => 0, 'total' => 0);
        }

        $table = lc_notification_table();
        $center = lc_sql_escape(trim((string) $center));
        $user_id = (int) $user_id;
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 30)));
        $unread_only = !empty($filters['unreadOnly']);

        $where = array("nf_center = '{$center}'");
        if ($center === 'admin') {
            $where[] = '(nf_user_id = 0 OR nf_user_id = ' . $user_id . ')';
        } else {
            $where[] = 'nf_user_id = ' . $user_id;
        }
        if ($unread_only) {
            $where[] = 'nf_read_at IS NULL';
        }

        $items = array();
        $has_priority = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'nf_priority');
        $order = $has_priority
            ? "ORDER BY CASE WHEN nf_priority = 'critical' AND nf_read_at IS NULL THEN 0 ELSE 1 END, nf_id DESC"
            : 'ORDER BY nf_id DESC';

        $result = lc_sql_query("
            SELECT * FROM `{$table}`
            WHERE " . implode(' AND ', $where) . "
            {$order}
            LIMIT {$limit}
        ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_notification_row_to_api($row);
            }
        }

        $count_row = lc_sql_fetch("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN nf_read_at IS NULL THEN 1 ELSE 0 END) AS unread_cnt
            FROM `{$table}`
            WHERE " . implode(' AND ', $where) . "
        ", false);

        return array(
            'items'  => $items,
            'unread' => (int) ($count_row['unread_cnt'] ?? 0),
            'total'  => (int) ($count_row['total'] ?? 0),
        );
    }
}

if (!function_exists('lc_notification_mark_read')) {
    function lc_notification_mark_read($center, $user_id, $nf_id = 0)
    {
        if (!lc_db_table_exists(lc_notification_table())) {
            return array('ok' => false, 'message' => '알림 테이블이 없습니다.');
        }

        $table = lc_notification_table();
        $center = lc_sql_escape(trim((string) $center));
        $user_id = (int) $user_id;
        $nf_id = (int) $nf_id;

        if ($nf_id > 0) {
            lc_sql_query("
                UPDATE `{$table}`
                SET nf_read_at = NOW()
                WHERE nf_id = {$nf_id}
                  AND nf_center = '{$center}'
                  AND (nf_user_id = {$user_id}" . ($center === 'admin' ? ' OR nf_user_id = 0' : '') . ")
            ", false);
        } else {
            lc_sql_query("
                UPDATE `{$table}`
                SET nf_read_at = NOW()
                WHERE nf_center = '{$center}'
                  AND nf_read_at IS NULL
                  AND (nf_user_id = {$user_id}" . ($center === 'admin' ? ' OR nf_user_id = 0' : '') . ")
            ", false);
        }

        return array('ok' => true, 'message' => '읽음 처리되었습니다.');
    }
}

if (!function_exists('lc_notification_emit_conversion')) {
    function lc_notification_emit_conversion(array $conversion, $event = 'received')
    {
        $cv_code = (string) ($conversion['cv_code'] ?? '');
        $cp_name = (string) ($conversion['cp_name'] ?? '캠페인');
        $pt_id = (int) ($conversion['pt_id'] ?? 0);
        $mt_id = (int) ($conversion['mt_id'] ?? 0);
        $cv_id = (int) ($conversion['cv_id'] ?? 0);

        if ($event === 'received') {
            if ($mt_id > 0) {
                lc_notification_create(array(
                    'center'  => 'merchant',
                    'userId'  => $mt_id,
                    'type'    => 'conversion',
                    'title'   => '신규 DB 접수',
                    'body'    => $cp_name . ' · ' . $cv_code,
                    'link'    => '/advertiser/db',
                    'refType' => 'conversion',
                    'refId'   => $cv_id,
                ));
            }
            if ($pt_id > 0) {
                lc_notification_create(array(
                    'center'  => 'partner',
                    'userId'  => $pt_id,
                    'type'    => 'conversion',
                    'title'   => '신규 DB 발생',
                    'body'    => $cp_name . ' · ' . $cv_code,
                    'link'    => '/partner/db-status',
                    'refType' => 'conversion',
                    'refId'   => $cv_id,
                ));
            }
            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'conversion',
                'title'   => '신규 DB 접수',
                'body'    => $cp_name . ' · ' . $cv_code,
                'link'    => '/admin/conversions',
                'refType' => 'conversion',
                'refId'   => $cv_id,
            ));
        }

        if ($event === 'approved' && $pt_id > 0) {
            lc_notification_create(array(
                'center'  => 'partner',
                'userId'  => $pt_id,
                'type'    => 'conversion',
                'title'   => 'DB 승인 완료',
                'body'    => $cp_name . ' · +' . number_format((int) ($conversion['cv_price'] ?? 0)) . '원',
                'link'    => '/partner/db-status',
                'refType' => 'conversion',
                'refId'   => $cv_id,
            ));
        }

        if ($event === 'rejected' && $pt_id > 0) {
            $reason = trim((string) ($conversion['cv_reject_reason'] ?? ''));
            lc_notification_create(array(
                'center'  => 'partner',
                'userId'  => $pt_id,
                'type'    => 'conversion',
                'title'   => 'DB 취소/무효 처리',
                'body'    => $cp_name . ($reason !== '' ? ' · ' . $reason : ''),
                'link'    => '/partner/db-cancel',
                'refType' => 'conversion',
                'refId'   => $cv_id,
            ));
        }

        if (function_exists('lc_email_notify_on_conversion')) {
            lc_email_notify_on_conversion($conversion, $event);
        }
    }
}

if (!function_exists('lc_notification_emit_low_balance')) {
    function lc_notification_emit_low_balance($mt_id, $balance, $threshold)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return;
        }

        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        $name = is_array($merchant) ? (string) ($merchant['mt_company'] ?? '') : '';

        lc_notification_create(array(
            'center'  => 'merchant',
            'userId'  => $mt_id,
            'type'    => 'wallet',
            'title'   => '광고비 잔액 부족',
            'body'    => '현재 잔액 ' . number_format((int) $balance) . '원 (기준 ' . number_format((int) $threshold) . '원)',
            'link'    => '/advertiser/billing',
            'refType' => 'merchant',
            'refId'   => $mt_id,
        ));
        lc_notification_create(array(
            'center'  => 'admin',
            'userId'  => 0,
            'type'    => 'wallet',
            'title'   => '광고주 잔액 부족',
            'body'    => ($name !== '' ? $name . ' · ' : '') . number_format((int) $balance) . '원',
            'link'    => '/admin/billing',
            'refType' => 'merchant',
            'refId'   => $mt_id,
        ));

        if (function_exists('lc_email_notify_on_low_balance')) {
            lc_email_notify_on_low_balance($mt_id, $balance, $threshold);
        }
    }
}

if (!function_exists('lc_notification_recent_exists')) {
    function lc_notification_recent_exists($center, $user_id, $type, $hours = 24)
    {
        if (!lc_db_table_exists(lc_notification_table())) {
            return false;
        }

        $table = lc_notification_table();
        $center = lc_sql_escape(trim((string) $center));
        $user_id = (int) $user_id;
        $type = lc_sql_escape(trim((string) $type));
        $hours = max(1, (int) $hours);

        $row = lc_sql_fetch("
            SELECT nf_id FROM `{$table}`
            WHERE nf_center = '{$center}'
              AND nf_user_id = {$user_id}
              AND nf_type = '{$type}'
              AND nf_created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
            LIMIT 1
        ", false);

        return is_array($row) && !empty($row['nf_id']);
    }
}

if (!function_exists('lc_notification_emit_event_reward')) {
    function lc_notification_emit_event_reward($pt_id, $amount, $title, $er_id = 0)
    {
        lc_notification_create(array(
            'center'  => 'partner',
            'userId'  => (int) $pt_id,
            'type'    => 'event',
            'title'   => '이벤트 리워드 지급',
            'body'    => $title . ' · ' . number_format((int) $amount) . '원',
            'link'    => '/events',
            'refType' => 'event_reward',
            'refId'   => (int) $er_id,
        ));
    }
}
