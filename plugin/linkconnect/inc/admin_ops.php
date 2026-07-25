<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_admin_decode_tags')) {
    function lc_admin_decode_tags($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

if (!function_exists('lc_admin_encode_tags')) {
    function lc_admin_encode_tags($tags)
    {
        if (!is_array($tags)) {
            return '';
        }
        $clean = array_values(array_filter(array_map('trim', array_map('strval', $tags))));
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('lc_admin_entity_meta_for_api')) {
    function lc_admin_entity_meta_for_api(array $row, $type = 'partner')
    {
        $prefix = $type === 'merchant' ? 'mt' : 'pt';
        return array(
            'adminMemo'    => (string) ($row[$prefix . '_admin_memo'] ?? ''),
            'tags'         => lc_admin_decode_tags($row[$prefix . '_admin_tags'] ?? ''),
            'assignedTo'   => (string) ($row[$prefix . '_assigned_mb_id'] ?? ''),
            'abuseScore'   => (int) ($row[$prefix . '_abuse_score'] ?? 0),
            'reviewScore'  => (int) lc_admin_review_score($row, $type),
            'tier'         => $type === 'partner' ? lc_partner_tier_label($row) : null,
        );
    }
}

if (!function_exists('lc_admin_review_score')) {
    function lc_admin_review_score(array $row, $type = 'partner')
    {
        $score = 0;
        $status = (string) ($row[($type === 'merchant' ? 'mt' : 'pt') . '_status'] ?? '');
        if ($status === 'pending') {
            $score += 50;
        }
        $total = (int) ($row['total_db'] ?? 0);
        $canceled = (int) ($row['canceled_db'] ?? 0);
        if ($total > 0) {
            $cancel_rate = ($canceled / $total) * 100;
            if ($cancel_rate >= 50) {
                $score += 30;
            } elseif ($cancel_rate >= 30) {
                $score += 15;
            }
        }
        $score += min(20, (int) ($row[($type === 'merchant' ? 'mt' : 'pt') . '_abuse_score'] ?? 0) * 4);
        if ($type === 'merchant') {
            $balance = (int) ($row['mt_balance'] ?? 0);
            if ($balance < 500000) {
                $score += 20;
            }
        }
        return min(100, $score);
    }
}

if (!function_exists('lc_admin_save_entity_meta')) {
    function lc_admin_save_entity_meta($type, $id, array $payload)
    {
        $type = trim((string) $type);
        $id = (int) $id;
        if ($id <= 0 || !in_array($type, array('partner', 'merchant'), true)) {
            return array('ok' => false, 'message' => '유효하지 않은 요청입니다.');
        }

        $table = lc_table($type === 'merchant' ? 'merchants' : 'partners');
        $prefix = $type === 'merchant' ? 'mt' : 'pt';
        $sets = array();

        if (array_key_exists('adminMemo', $payload)) {
            $sets[] = "{$prefix}_admin_memo = '" . lc_sql_escape(trim((string) $payload['adminMemo'])) . "'";
        }
        if (array_key_exists('tags', $payload)) {
            $sets[] = "{$prefix}_admin_tags = '" . lc_sql_escape(lc_admin_encode_tags($payload['tags'])) . "'";
        }
        if (array_key_exists('assignedTo', $payload)) {
            $sets[] = "{$prefix}_assigned_mb_id = '" . lc_sql_escape(trim((string) $payload['assignedTo'])) . "'";
        }
        if (!$sets) {
            return array('ok' => false, 'message' => '저장할 항목이 없습니다.');
        }

        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . ", {$prefix}_updated_at = NOW() WHERE {$prefix}_id = {$id} ", false);

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('admin_meta_save', $type, $id, '관리자 메모/태그 저장');
        }

        return array('ok' => true, 'message' => '저장되었습니다.');
    }
}

if (!function_exists('lc_admin_bulk_partner_status')) {
    function lc_admin_bulk_partner_status(array $ids, $action)
    {
        $map = array(
            'activate' => LC_PARTNER_STATUS_ACTIVE,
            'suspend'  => LC_PARTNER_STATUS_SUSPENDED,
            'pending'  => LC_PARTNER_STATUS_PENDING,
        );
        if (!isset($map[$action])) {
            return array('ok' => false, 'message' => '유효하지 않은 action입니다.', 'count' => 0);
        }
        $count = 0;
        foreach ($ids as $id) {
            $result = lc_partner_update_status((int) $id, $map[$action]);
            if (!empty($result['ok'])) {
                $count++;
            }
        }
        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('partner_bulk_' . $action, 'partner', 0, $count . '건 일괄 처리', array('ids' => $ids));
        }
        return array('ok' => true, 'message' => $count . '건 처리되었습니다.', 'count' => $count);
    }
}

if (!function_exists('lc_admin_bulk_merchant_status')) {
    function lc_admin_bulk_merchant_status(array $ids, $action)
    {
        $map = array(
            'activate' => LC_MERCHANT_STATUS_ACTIVE,
            'suspend'  => LC_MERCHANT_STATUS_SUSPENDED,
            'pending'  => LC_MERCHANT_STATUS_PENDING,
        );
        if (!isset($map[$action])) {
            return array('ok' => false, 'message' => '유효하지 않은 action입니다.', 'count' => 0);
        }
        $count = 0;
        foreach ($ids as $id) {
            $result = lc_merchant_update_status((int) $id, $map[$action]);
            if (!empty($result['ok'])) {
                $count++;
            }
        }
        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('merchant_bulk_' . $action, 'merchant', 0, $count . '건 일괄 처리', array('ids' => $ids));
        }
        return array('ok' => true, 'message' => $count . '건 처리되었습니다.', 'count' => $count);
    }
}

if (!function_exists('lc_admin_bulk_reward_pay')) {
    function lc_admin_bulk_reward_pay(array $ids)
    {
        $count = 0;
        foreach ($ids as $id) {
            $result = lc_event_reward_update_status((int) $id, 'paid');
            if (!empty($result['ok'])) {
                $count++;
            }
        }
        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('event_reward_bulk_pay', 'event_reward', 0, $count . '건 리워드 일괄 지급', array('ids' => $ids));
        }
        return array('ok' => true, 'message' => $count . '건 지급되었습니다.', 'count' => $count);
    }
}

if (!function_exists('lc_admin_bulk_notify')) {
    function lc_admin_bulk_notify(array $payload)
    {
        $center = trim((string) ($payload['center'] ?? 'partner'));
        $user_ids = isset($payload['userIds']) && is_array($payload['userIds']) ? $payload['userIds'] : array();
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        if ($title === '' || !$user_ids) {
            return array('ok' => false, 'message' => '제목과 대상이 필요합니다.', 'count' => 0);
        }
        $count = 0;
        foreach ($user_ids as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0) {
                continue;
            }
            if (function_exists('lc_notification_create')) {
                lc_notification_create(array(
                    'center' => $center,
                    'userId' => $uid,
                    'type'   => 'system',
                    'title'  => $title,
                    'body'   => $body,
                    'link'   => $center === 'merchant' ? '/advertiser' : '/partner',
                ));
                $count++;
            }
        }
        return array('ok' => true, 'message' => $count . '건 알림 발송', 'count' => $count);
    }
}

if (!function_exists('lc_impersonate_log_table')) {
    function lc_impersonate_log_table()
    {
        return lc_table('impersonate_logs');
    }
}

if (!function_exists('lc_impersonate_log_start')) {
    function lc_impersonate_log_start($type, $id, $label = '')
    {
        if (!lc_db_table_exists(lc_impersonate_log_table())) {
            return 0;
        }
        global $member;
        $table = lc_impersonate_log_table();
        lc_sql_query("
            INSERT INTO `{$table}` (mb_id, ilog_type, ilog_target_id, ilog_label, ilog_started_at)
            VALUES (
                '" . lc_sql_escape((string) ($member['mb_id'] ?? '')) . "',
                '" . lc_sql_escape((string) $type) . "',
                " . (int) $id . ",
                '" . lc_sql_escape((string) $label) . "',
                NOW()
            )
        ", false);
        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_impersonate_log_end_active')) {
    function lc_impersonate_log_end_active($type, $id)
    {
        if (!lc_db_table_exists(lc_impersonate_log_table())) {
            return;
        }
        global $member;
        $table = lc_impersonate_log_table();
        $mb_id = lc_sql_escape((string) ($member['mb_id'] ?? ''));
        lc_sql_query("
            UPDATE `{$table}`
            SET ilog_ended_at = NOW()
            WHERE mb_id = '{$mb_id}'
              AND ilog_type = '" . lc_sql_escape((string) $type) . "'
              AND ilog_target_id = " . (int) $id . "
              AND ilog_ended_at IS NULL
            ORDER BY ilog_id DESC
            LIMIT 1
        ", false);
    }
}

if (!function_exists('lc_impersonate_history_for_api')) {
    function lc_impersonate_history_for_api($limit = 10)
    {
        if (!lc_db_table_exists(lc_impersonate_log_table())) {
            return array();
        }
        global $member;
        $table = lc_impersonate_log_table();
        $mb_id = lc_sql_escape((string) ($member['mb_id'] ?? ''));
        $limit = max(1, min(20, (int) $limit));
        $items = array();
        $result = lc_sql_query("
            SELECT * FROM `{$table}`
            WHERE mb_id = '{$mb_id}'
            ORDER BY ilog_id DESC
            LIMIT {$limit}
        ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'        => (int) ($row['ilog_id'] ?? 0),
                    'type'      => (string) ($row['ilog_type'] ?? ''),
                    'targetId'  => (int) ($row['ilog_target_id'] ?? 0),
                    'label'     => (string) ($row['ilog_label'] ?? ''),
                    'startedAt' => (string) ($row['ilog_started_at'] ?? ''),
                    'endedAt'   => (string) ($row['ilog_ended_at'] ?? ''),
                );
            }
        }
        return $items;
    }
}

if (!function_exists('lc_admin_review_queue_for_api')) {
    function lc_admin_review_queue_for_api()
    {
        $partners = lc_admin_list_partners(array('status' => LC_PARTNER_STATUS_PENDING));
        $merchants = lc_admin_list_merchants(array('status' => LC_MERCHANT_STATUS_PENDING));
        $items = array();
        foreach ($partners as $row) {
            $items[] = array_merge(array(
                'entityType' => 'partner',
                'entityId'   => (int) $row['pt_id'],
                'code'       => (string) $row['pt_code'],
                'name'       => (string) $row['pt_name'],
                'status'     => lc_admin_partner_status_ui($row['pt_status']),
            ), lc_admin_entity_meta_for_api($row, 'partner'));
        }
        foreach ($merchants as $row) {
            $items[] = array_merge(array(
                'entityType' => 'merchant',
                'entityId'   => (int) $row['mt_id'],
                'code'       => (string) $row['mt_code'],
                'name'       => (string) $row['mt_company'],
                'status'     => lc_admin_merchant_status_ui($row['mt_status']),
            ), lc_admin_entity_meta_for_api($row, 'merchant'));
        }
        usort($items, function ($a, $b) {
            return ($b['reviewScore'] ?? 0) <=> ($a['reviewScore'] ?? 0);
        });
        return $items;
    }
}
