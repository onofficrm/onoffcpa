<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_community_table')) {
    function lc_community_table()
    {
        return lc_table('community_posts');
    }
}

if (!function_exists('lc_community_ensure_schema')) {
    function lc_community_ensure_schema()
    {
        $table = lc_community_table();
        if (lc_db_table_exists($table)) {
            return true;
        }

        return lc_sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
            `cb_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `cb_mb_id` varchar(50) NOT NULL DEFAULT '',
            `cb_author` varchar(100) NOT NULL DEFAULT '',
            `cb_subject` varchar(255) NOT NULL DEFAULT '',
            `cb_content` mediumtext NOT NULL,
            `cb_hit` int unsigned NOT NULL DEFAULT 0,
            `cb_ip` varchar(45) NOT NULL DEFAULT '',
            `cb_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `cb_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`cb_id`),
            KEY `idx_cb_mb_id` (`cb_mb_id`),
            KEY `idx_cb_created_at` (`cb_created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false) !== false;
    }
}

if (!function_exists('lc_community_permissions')) {
    function lc_community_permissions(array $row = array())
    {
        global $member, $is_admin;

        $logged_in = !empty($member['mb_id']);
        $is_owner = $logged_in
            && !empty($row['cb_mb_id'])
            && (string) $row['cb_mb_id'] === (string) $member['mb_id'];
        $can_manage = !empty($is_admin)
            || (function_exists('lc_can_access_admin') && lc_can_access_admin());

        return array(
            'canWrite'  => $logged_in,
            'canEdit'   => $is_owner || $can_manage,
            'canDelete' => $is_owner || $can_manage,
        );
    }
}

if (!function_exists('lc_community_row_to_api')) {
    function lc_community_row_to_api(array $row, $detail = false)
    {
        $permissions = lc_community_permissions($row);
        $item = array(
            'id'        => (int) ($row['cb_id'] ?? 0),
            'subject'   => get_text((string) ($row['cb_subject'] ?? '')),
            'author'    => get_text((string) ($row['cb_author'] ?? '')),
            'memberId'  => (string) ($row['cb_mb_id'] ?? ''),
            'date'      => substr((string) ($row['cb_created_at'] ?? ''), 0, 10),
            'datetime'  => (string) ($row['cb_created_at'] ?? ''),
            'hit'       => (int) ($row['cb_hit'] ?? 0),
            'canEdit'   => $permissions['canEdit'],
            'canDelete' => $permissions['canDelete'],
        );

        if ($detail) {
            $plain = get_text((string) ($row['cb_content'] ?? ''), 0);
            $item['contentPlain'] = $plain;
            $item['contentHtml'] = nl2br($plain);
            $item['prevId'] = 0;
            $item['nextId'] = 0;
        }

        return $item;
    }
}

if (!function_exists('lc_community_list')) {
    function lc_community_list(array $filters = array())
    {
        if (!lc_community_ensure_schema()) {
            return array(
                'items' => array(), 'total' => 0, 'page' => 1, 'totalPages' => 1,
                'perPage' => 15, 'boardReady' => false, 'permissions' => lc_community_permissions(),
            );
        }

        $table = lc_community_table();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $per_page = max(5, min(50, (int) ($filters['perPage'] ?? 15)));
        $q = trim((string) ($filters['q'] ?? ''));
        $where = '1=1';
        if ($q !== '') {
            $q_esc = lc_sql_escape('%' . $q . '%');
            $where .= " AND (cb_subject LIKE '{$q_esc}' OR cb_content LIKE '{$q_esc}' OR cb_author LIKE '{$q_esc}')";
        }

        $count = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where} ");
        $total = (int) ($count['cnt'] ?? 0);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $per_page;

        $items = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where}
            ORDER BY cb_id DESC LIMIT {$offset}, {$per_page} ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_community_row_to_api($row);
            }
        }

        return array(
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => $total_pages,
            'perPage'     => $per_page,
            'boardReady'  => true,
            'permissions' => lc_community_permissions(),
            'boardTitle'  => '자유게시판',
        );
    }
}

if (!function_exists('lc_community_get')) {
    function lc_community_get($id, $increase_hit = true)
    {
        $id = (int) $id;
        if ($id <= 0 || !lc_community_ensure_schema()) {
            return null;
        }

        $table = lc_community_table();
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cb_id = '{$id}' LIMIT 1 ");
        if (!is_array($row) || empty($row['cb_id'])) {
            return null;
        }

        if ($increase_hit) {
            lc_sql_query(" UPDATE `{$table}` SET cb_hit = cb_hit + 1 WHERE cb_id = '{$id}' ", false);
            $row['cb_hit'] = (int) ($row['cb_hit'] ?? 0) + 1;
        }

        $item = lc_community_row_to_api($row, true);
        $prev = lc_sql_fetch(" SELECT cb_id FROM `{$table}` WHERE cb_id > '{$id}' ORDER BY cb_id ASC LIMIT 1 ");
        $next = lc_sql_fetch(" SELECT cb_id FROM `{$table}` WHERE cb_id < '{$id}' ORDER BY cb_id DESC LIMIT 1 ");
        $item['prevId'] = (int) ($prev['cb_id'] ?? 0);
        $item['nextId'] = (int) ($next['cb_id'] ?? 0);

        return $item;
    }
}

if (!function_exists('lc_community_save')) {
    function lc_community_save(array $payload, $id = 0)
    {
        global $member;

        if (!lc_community_ensure_schema()) {
            return array('ok' => false, 'message' => '게시판을 준비하지 못했습니다.');
        }

        $id = (int) $id;
        $subject = trim((string) ($payload['subject'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        if ($subject === '' || $content === '') {
            return array('ok' => false, 'message' => '제목과 내용을 입력해 주세요.');
        }
        if (mb_strlen($subject, 'UTF-8') > 255) {
            return array('ok' => false, 'message' => '제목은 255자 이내로 입력해 주세요.');
        }

        $table = lc_community_table();
        $subject_esc = lc_sql_escape($subject);
        $content_esc = lc_sql_escape($content);

        if ($id > 0) {
            $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cb_id = '{$id}' LIMIT 1 ");
            if (!is_array($row) || empty($row['cb_id'])) {
                return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
            }
            if (empty(lc_community_permissions($row)['canEdit'])) {
                return array('ok' => false, 'message' => '수정 권한이 없습니다.');
            }

            lc_sql_query(" UPDATE `{$table}` SET
                cb_subject = '{$subject_esc}',
                cb_content = '{$content_esc}',
                cb_updated_at = NOW()
                WHERE cb_id = '{$id}' ", false);

            return array('ok' => true, 'message' => '게시글이 수정되었습니다.', 'item' => lc_community_get($id, false));
        }

        if (empty(lc_community_permissions()['canWrite'])) {
            return array('ok' => false, 'message' => '로그인 후 글을 작성할 수 있습니다.');
        }

        $mb_id = lc_sql_escape((string) ($member['mb_id'] ?? ''));
        $author_source = !empty($member['mb_nick']) ? $member['mb_nick'] : ($member['mb_name'] ?? $member['mb_id']);
        $author = lc_sql_escape((string) $author_source);
        $ip = lc_sql_escape((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        lc_sql_query(" INSERT INTO `{$table}` SET
            cb_mb_id = '{$mb_id}',
            cb_author = '{$author}',
            cb_subject = '{$subject_esc}',
            cb_content = '{$content_esc}',
            cb_ip = '{$ip}',
            cb_created_at = NOW(),
            cb_updated_at = NOW() ", false);

        $new_id = (int) lc_sql_insert_id();
        if ($new_id <= 0) {
            return array('ok' => false, 'message' => '게시글 저장에 실패했습니다.');
        }

        return array('ok' => true, 'message' => '게시글이 등록되었습니다.', 'item' => lc_community_get($new_id, false));
    }
}

if (!function_exists('lc_community_delete')) {
    function lc_community_delete($id)
    {
        $id = (int) $id;
        if ($id <= 0 || !lc_community_ensure_schema()) {
            return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
        }

        $table = lc_community_table();
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cb_id = '{$id}' LIMIT 1 ");
        if (!is_array($row) || empty($row['cb_id'])) {
            return array('ok' => false, 'message' => '게시글을 찾을 수 없습니다.');
        }
        if (empty(lc_community_permissions($row)['canDelete'])) {
            return array('ok' => false, 'message' => '삭제 권한이 없습니다.');
        }

        lc_sql_query(" DELETE FROM `{$table}` WHERE cb_id = '{$id}' ", false);

        return array('ok' => true, 'message' => '게시글이 삭제되었습니다.');
    }
}
