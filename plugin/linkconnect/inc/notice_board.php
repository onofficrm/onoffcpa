<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('LC_NOTICE_BO_TABLE', 'notice');

if (!function_exists('lc_notice_board_table')) {
    function lc_notice_board_table()
    {
        global $g5;

        return $g5['write_prefix'] . LC_NOTICE_BO_TABLE;
    }
}

if (!function_exists('lc_notice_board_get_board')) {
    function lc_notice_board_get_board()
    {
        global $g5;

        static $board = null;
        if ($board !== null) {
            return $board;
        }

        $board = sql_fetch("
            SELECT *
            FROM {$g5['board_table']}
            WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "'
            LIMIT 1
        ");

        return is_array($board) ? $board : array();
    }
}

if (!function_exists('lc_notice_board_ready')) {
    function lc_notice_board_ready()
    {
        $board = lc_notice_board_get_board();

        return !empty($board['bo_table']);
    }
}

if (!function_exists('lc_notice_board_notice_ids')) {
    function lc_notice_board_notice_ids(array $board)
    {
        $raw = trim((string) ($board['bo_notice'] ?? ''));
        if ($raw === '') {
            return array();
        }

        $ids = array();
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}

if (!function_exists('lc_notice_board_permissions')) {
    function lc_notice_board_permissions(array $row = array())
    {
        global $member, $is_admin;

        $board = lc_notice_board_get_board();
        $level = isset($member['mb_level']) ? (int) $member['mb_level'] : 0;
        $write_level = isset($board['bo_write_level']) ? (int) $board['bo_write_level'] : 10;
        $reply_level = isset($board['bo_reply_level']) ? (int) $board['bo_reply_level'] : 10;

        $can_manage = !empty($is_admin) || $level >= $write_level || (function_exists('lc_can_access_admin') && lc_can_access_admin());
        $is_owner = false;
        if (!empty($row['mb_id']) && !empty($member['mb_id'])) {
            $is_owner = (string) $row['mb_id'] === (string) $member['mb_id'];
        }

        return array(
            'canWrite' => $can_manage,
            'canEdit'  => $can_manage || ($is_owner && $level >= $reply_level),
            'canDelete'=> $can_manage,
        );
    }
}

if (!function_exists('lc_notice_board_format_date')) {
    function lc_notice_board_format_date($datetime)
    {
        $datetime = trim((string) $datetime);
        if ($datetime === '') {
            return '';
        }

        return substr($datetime, 0, 10);
    }
}

if (!function_exists('lc_notice_board_content_for_api')) {
    function lc_notice_board_content_for_api(array $row)
    {
        $content = (string) ($row['wr_content'] ?? '');
        $option = (string) ($row['wr_option'] ?? '');

        if ($content === '') {
            return array(
                'html'    => '',
                'plain'   => '',
                'isHtml'  => false,
            );
        }

        if (strpos($option, 'html') !== false) {
            if (function_exists('conv_content')) {
                $html = conv_content($content, 1);
            } else {
                $html = $content;
            }

            return array(
                'html'   => $html,
                'plain'  => trim(strip_tags($html)),
                'isHtml' => true,
            );
        }

        $html = nl2br(get_text($content, 0));

        return array(
            'html'   => $html,
            'plain'  => get_text($content, 0),
            'isHtml' => false,
        );
    }
}

if (!function_exists('lc_notice_board_row_to_api')) {
    function lc_notice_board_row_to_api(array $row, array $board = array(), $detail = false)
    {
        if (!$board) {
            $board = lc_notice_board_get_board();
        }

        $notice_ids = lc_notice_board_notice_ids($board);
        $wr_id = (int) ($row['wr_id'] ?? 0);
        $content = lc_notice_board_content_for_api($row);
        $perms = lc_notice_board_permissions($row);

        $item = array(
            'id'        => $wr_id,
            'subject'   => get_text((string) ($row['wr_subject'] ?? '')),
            'author'    => get_text((string) ($row['wr_name'] ?? '')),
            'memberId'  => (string) ($row['mb_id'] ?? ''),
            'date'      => lc_notice_board_format_date($row['wr_datetime'] ?? ''),
            'datetime'  => (string) ($row['wr_datetime'] ?? ''),
            'hit'       => (int) ($row['wr_hit'] ?? 0),
            'isNotice'  => in_array($wr_id, $notice_ids, true),
            'canEdit'   => $perms['canEdit'],
            'canDelete' => $perms['canDelete'],
        );

        if ($detail) {
            $item['contentHtml'] = $content['html'];
            $item['contentPlain'] = $content['plain'];
            $item['isHtml'] = $content['isHtml'];
            $item['prevId'] = 0;
            $item['nextId'] = 0;
        }

        return $item;
    }
}

if (!function_exists('lc_notice_board_list')) {
    function lc_notice_board_list(array $filters = array())
    {
        if (!lc_notice_board_ready()) {
            return array(
                'items'      => array(),
                'total'      => 0,
                'page'       => 1,
                'totalPages' => 1,
                'perPage'    => 15,
                'boardReady' => false,
                'permissions'=> lc_notice_board_permissions(),
            );
        }

        $board = lc_notice_board_get_board();
        $write_table = lc_notice_board_table();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $per_page = max(5, min(50, (int) ($filters['perPage'] ?? 15)));
        $q = trim((string) ($filters['q'] ?? ''));
        $offset = ($page - 1) * $per_page;

        $where = array('wr_is_comment = 0');
        if ($q !== '') {
            $esc = sql_real_escape_string($q);
            $where[] = "(wr_subject LIKE '%{$esc}%' OR wr_content LIKE '%{$esc}%')";
        }

        $where_sql = implode(' AND ', $where);
        $count_row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$write_table} WHERE {$where_sql} ");
        $total = (int) ($count_row['cnt'] ?? 0);
        $total_pages = max(1, (int) ceil($total / $per_page));
        if ($page > $total_pages) {
            $page = $total_pages;
            $offset = ($page - 1) * $per_page;
        }

        $notice_ids = lc_notice_board_notice_ids($board);
        $order_notice = '';
        if ($notice_ids) {
            $order_notice = 'CASE WHEN wr_id IN (' . implode(',', array_map('intval', $notice_ids)) . ') THEN 0 ELSE 1 END, ';
        }

        $items = array();
        $result = sql_query("
            SELECT *
            FROM {$write_table}
            WHERE {$where_sql}
            ORDER BY {$order_notice} wr_num DESC, wr_reply ASC
            LIMIT {$offset}, {$per_page}
        ");

        while ($row = sql_fetch_array($result)) {
            $items[] = lc_notice_board_row_to_api($row, $board, false);
        }

        return array(
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => $total_pages,
            'perPage'     => $per_page,
            'boardReady'  => true,
            'permissions' => lc_notice_board_permissions(),
            'boardTitle'  => (string) ($board['bo_subject'] ?? '공지사항'),
        );
    }
}

if (!function_exists('lc_notice_board_get')) {
    function lc_notice_board_get($wr_id, $increase_hit = true)
    {
        $wr_id = (int) $wr_id;
        if ($wr_id <= 0 || !lc_notice_board_ready()) {
            return null;
        }

        $board = lc_notice_board_get_board();
        $write_table = lc_notice_board_table();
        $row = sql_fetch(" SELECT * FROM {$write_table} WHERE wr_id = {$wr_id} AND wr_is_comment = 0 LIMIT 1 ");
        if (!is_array($row) || empty($row['wr_id'])) {
            return null;
        }

        if ($increase_hit) {
            sql_query(" UPDATE {$write_table} SET wr_hit = wr_hit + 1 WHERE wr_id = {$wr_id} ");
            $row['wr_hit'] = (int) ($row['wr_hit'] ?? 0) + 1;
        }

        $item = lc_notice_board_row_to_api($row, $board, true);

        $prev = sql_fetch("
            SELECT wr_id
            FROM {$write_table}
            WHERE wr_is_comment = 0 AND wr_num > '" . sql_real_escape_string($row['wr_num']) . "'
            ORDER BY wr_num ASC
            LIMIT 1
        ");
        $next = sql_fetch("
            SELECT wr_id
            FROM {$write_table}
            WHERE wr_is_comment = 0 AND wr_num < '" . sql_real_escape_string($row['wr_num']) . "'
            ORDER BY wr_num DESC
            LIMIT 1
        ");

        $item['prevId'] = (int) ($prev['wr_id'] ?? 0);
        $item['nextId'] = (int) ($next['wr_id'] ?? 0);

        return $item;
    }
}

if (!function_exists('lc_notice_board_normalize_content')) {
    function lc_notice_board_normalize_content($content)
    {
        $content = trim((string) $content);
        if ($content === '') {
            return array('content' => '', 'option' => '');
        }

        if (strpos($content, '<') !== false) {
            return array('content' => $content, 'option' => 'html1');
        }

        return array('content' => $content, 'option' => '');
    }
}

if (!function_exists('lc_notice_board_save')) {
    function lc_notice_board_save(array $payload, $wr_id = 0)
    {
        global $g5, $member, $is_admin;

        if (!lc_notice_board_ready()) {
            return array('ok' => false, 'message' => '공지 게시판을 찾을 수 없습니다.');
        }

        $board = lc_notice_board_get_board();
        $write_table = lc_notice_board_table();
        $wr_id = (int) $wr_id;
        $subject = trim((string) ($payload['subject'] ?? ''));
        $content_raw = trim((string) ($payload['content'] ?? ''));
        $is_notice = !empty($payload['isNotice']);

        if ($subject === '') {
            return array('ok' => false, 'message' => '제목을 입력해주세요.');
        }
        if ($content_raw === '') {
            return array('ok' => false, 'message' => '내용을 입력해주세요.');
        }

        $existing = array();
        if ($wr_id > 0) {
            $existing = sql_fetch(" SELECT * FROM {$write_table} WHERE wr_id = {$wr_id} AND wr_is_comment = 0 LIMIT 1 ");
            if (!is_array($existing) || empty($existing['wr_id'])) {
                return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
            }
        }

        $perms = lc_notice_board_permissions($existing);
        if ($wr_id > 0 && empty($perms['canEdit'])) {
            return array('ok' => false, 'message' => '수정 권한이 없습니다.');
        }
        if ($wr_id <= 0 && empty($perms['canWrite'])) {
            return array('ok' => false, 'message' => '글쓰기 권한이 없습니다.');
        }

        $normalized = lc_notice_board_normalize_content($content_raw);
        $subject_esc = sql_real_escape_string($subject);
        $content_esc = sql_real_escape_string($normalized['content']);
        $option_esc = sql_real_escape_string($normalized['option']);
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sql_real_escape_string($_SERVER['REMOTE_ADDR']) : '';

        if ($wr_id > 0) {
            sql_query("
                UPDATE {$write_table}
                SET wr_subject = '{$subject_esc}',
                    wr_content = '{$content_esc}',
                    wr_option = '{$option_esc}',
                    wr_last = '" . G5_TIME_YMDHIS . "'
                WHERE wr_id = {$wr_id}
                LIMIT 1
            ");

            if ($is_notice && (!empty($is_admin) || (function_exists('lc_can_access_admin') && lc_can_access_admin()))) {
                $notice = board_notice($board['bo_notice'], $wr_id, true);
                sql_query(" UPDATE {$g5['board_table']} SET bo_notice = '" . sql_real_escape_string($notice) . "' WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' ");
            } elseif (!$is_notice && !empty($is_admin)) {
                $notice = board_notice($board['bo_notice'], $wr_id, false);
                sql_query(" UPDATE {$g5['board_table']} SET bo_notice = '" . sql_real_escape_string($notice) . "' WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' ");
            }

            return array(
                'ok'      => true,
                'message' => '공지사항이 수정되었습니다.',
                'item'    => lc_notice_board_get($wr_id, false),
            );
        }

        $wr_name = addslashes(clean_xss_tags(!empty($board['bo_use_name']) ? $member['mb_name'] : $member['mb_nick']));
        $wr_email = addslashes($member['mb_email']);
        $wr_homepage = addslashes(clean_xss_tags($member['mb_homepage']));
        $mb_id_esc = sql_real_escape_string($member['mb_id']);

        sql_query("
            INSERT INTO {$write_table}
            SET wr_num = (SELECT IFNULL(MIN(wr_num) - 1, -1) FROM {$write_table} AS sq),
                wr_reply = '',
                wr_comment = 0,
                ca_name = '',
                wr_option = '{$option_esc}',
                wr_subject = '{$subject_esc}',
                wr_content = '{$content_esc}',
                wr_seo_title = '',
                wr_link1 = '',
                wr_link2 = '',
                wr_link1_hit = 0,
                wr_link2_hit = 0,
                wr_hit = 0,
                wr_good = 0,
                wr_nogood = 0,
                mb_id = '{$mb_id_esc}',
                wr_password = '',
                wr_name = '{$wr_name}',
                wr_email = '{$wr_email}',
                wr_homepage = '{$wr_homepage}',
                wr_datetime = '" . G5_TIME_YMDHIS . "',
                wr_last = '" . G5_TIME_YMDHIS . "',
                wr_ip = '{$remote_ip}'
        ");

        $new_id = (int) sql_insert_id();
        if ($new_id <= 0) {
            return array('ok' => false, 'message' => '저장에 실패했습니다.');
        }

        sql_query(" UPDATE {$write_table} SET wr_parent = '{$new_id}' WHERE wr_id = '{$new_id}' ");
        sql_query("
            INSERT INTO {$g5['board_new_table']} (bo_table, wr_id, wr_parent, bn_datetime, mb_id)
            VALUES ('" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "', '{$new_id}', '{$new_id}', '" . G5_TIME_YMDHIS . "', '{$mb_id_esc}')
        ");
        sql_query(" UPDATE {$g5['board_table']} SET bo_count_write = bo_count_write + 1 WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' ");

        if ($is_notice) {
            $notice = board_notice($board['bo_notice'], $new_id, true);
            sql_query(" UPDATE {$g5['board_table']} SET bo_notice = '" . sql_real_escape_string($notice) . "' WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' ");
        }

        return array(
            'ok'      => true,
            'message' => '공지사항이 등록되었습니다.',
            'item'    => lc_notice_board_get($new_id, false),
        );
    }
}

if (!function_exists('lc_notice_board_delete')) {
    function lc_notice_board_delete($wr_id)
    {
        global $g5;

        $wr_id = (int) $wr_id;
        if ($wr_id <= 0 || !lc_notice_board_ready()) {
            return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
        }

        $write_table = lc_notice_board_table();
        $row = sql_fetch(" SELECT * FROM {$write_table} WHERE wr_id = {$wr_id} AND wr_is_comment = 0 LIMIT 1 ");
        if (!is_array($row) || empty($row['wr_id'])) {
            return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
        }

        $perms = lc_notice_board_permissions($row);
        if (empty($perms['canDelete'])) {
            return array('ok' => false, 'message' => '삭제 권한이 없습니다.');
        }

        $board = lc_notice_board_get_board();
        $notice = board_notice($board['bo_notice'], $wr_id, false);
        sql_query(" UPDATE {$g5['board_table']} SET bo_notice = '" . sql_real_escape_string($notice) . "', bo_count_write = GREATEST(0, bo_count_write - 1) WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' ");
        sql_query(" DELETE FROM {$g5['board_new_table']} WHERE bo_table = '" . sql_real_escape_string(LC_NOTICE_BO_TABLE) . "' AND wr_id = {$wr_id} ");
        sql_query(" DELETE FROM {$write_table} WHERE wr_id = {$wr_id} ");

        return array('ok' => true, 'message' => '공지사항이 삭제되었습니다.');
    }
}
