<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_admin_log_table')) {
    function lc_admin_log_table()
    {
        return lc_table('admin_logs');
    }
}

if (!function_exists('lc_admin_log_write')) {
    function lc_admin_log_write($action, $target_type = '', $target_id = 0, $summary = '', array $payload = array())
    {
        if (!lc_db_table_exists(lc_admin_log_table())) {
            return 0;
        }

        global $member;
        $mb_id = isset($member['mb_id']) ? (string) $member['mb_id'] : '';
        if ($mb_id === '') {
            return 0;
        }

        $table = lc_admin_log_table();
        $ip = isset($_SERVER['REMOTE_ADDR']) ? lc_sql_escape((string) $_SERVER['REMOTE_ADDR']) : '';
        $payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payload_json === false) {
            $payload_json = '{}';
        }

        lc_sql_query("
            INSERT INTO `{$table}` (
                mb_id, alog_action, alog_target_type, alog_target_id, alog_summary, alog_payload, alog_ip
            ) VALUES (
                '" . lc_sql_escape($mb_id) . "',
                '" . lc_sql_escape((string) $action) . "',
                '" . lc_sql_escape((string) $target_type) . "',
                " . (int) $target_id . ",
                '" . lc_sql_escape((string) $summary) . "',
                '" . lc_sql_escape($payload_json) . "',
                '{$ip}'
            )
        ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_admin_log_row_to_api')) {
    function lc_admin_log_row_to_api(array $row)
    {
        $payload = array();
        if (!empty($row['alog_payload'])) {
            $decoded = json_decode((string) $row['alog_payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return array(
            'id'         => (int) ($row['alog_id'] ?? 0),
            'memberId'   => (string) ($row['mb_id'] ?? ''),
            'action'     => (string) ($row['alog_action'] ?? ''),
            'targetType' => (string) ($row['alog_target_type'] ?? ''),
            'targetId'   => (int) ($row['alog_target_id'] ?? 0),
            'summary'    => (string) ($row['alog_summary'] ?? ''),
            'payload'    => $payload,
            'ip'         => (string) ($row['alog_ip'] ?? ''),
            'createdAt'  => (string) ($row['alog_created_at'] ?? ''),
        );
    }
}

if (!function_exists('lc_admin_log_list_for_api')) {
    function lc_admin_log_list_for_api(array $filters = array())
    {
        if (!lc_db_table_exists(lc_admin_log_table())) {
            return array('items' => array(), 'total' => 0);
        }

        $table = lc_admin_log_table();
        $where = array('1=1');
        $q = trim((string) ($filters['q'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));
        $limit = max(1, min(200, (int) ($filters['limit'] ?? 50)));

        if ($q !== '') {
            $esc = lc_sql_escape($q);
            $where[] = "(alog_summary LIKE '%{$esc}%' OR mb_id LIKE '%{$esc}%' OR alog_action LIKE '%{$esc}%')";
        }
        if ($action !== '') {
            $where[] = "alog_action = '" . lc_sql_escape($action) . "'";
        }

        $items = array();
        $result = lc_sql_query("
            SELECT * FROM `{$table}`
            WHERE " . implode(' AND ', $where) . "
            ORDER BY alog_id DESC
            LIMIT {$limit}
        ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_admin_log_row_to_api($row);
            }
        }

        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE " . implode(' AND ', $where) . " ", false);

        return array(
            'items' => $items,
            'total' => (int) ($count_row['cnt'] ?? 0),
        );
    }
}
