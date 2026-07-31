<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/* ── 상태 · 승인 방식 ── */
if (!defined('LC_CPG_STATUS_DRAFT')) {
    define('LC_CPG_STATUS_DRAFT', 'draft');
}
if (!defined('LC_CPG_STATUS_REVIEW')) {
    define('LC_CPG_STATUS_REVIEW', 'review');
}
if (!defined('LC_CPG_STATUS_PUBLISHED')) {
    define('LC_CPG_STATUS_PUBLISHED', 'published');
}
if (!defined('LC_CPG_STATUS_HIDDEN')) {
    define('LC_CPG_STATUS_HIDDEN', 'hidden');
}
if (!defined('LC_CPG_STATUS_REVISION')) {
    define('LC_CPG_STATUS_REVISION', 'revision');
}

if (!defined('LC_CPG_APPROVAL_FREE')) {
    define('LC_CPG_APPROVAL_FREE', 'free');
}
if (!defined('LC_CPG_APPROVAL_FIRST_REVIEW')) {
    define('LC_CPG_APPROVAL_FIRST_REVIEW', 'first_review');
}
if (!defined('LC_CPG_APPROVAL_ALL_REVIEW')) {
    define('LC_CPG_APPROVAL_ALL_REVIEW', 'all_review');
}

if (!function_exists('lc_campaign_promo_guide_table')) {
    function lc_campaign_promo_guide_table()
    {
        return lc_table('campaign_promo_guides');
    }
}

if (!function_exists('lc_campaign_promo_guide_asset_table')) {
    function lc_campaign_promo_guide_asset_table()
    {
        return lc_table('campaign_promo_assets');
    }
}

if (!function_exists('lc_campaign_promo_guide_limits')) {
    /**
     * @return array<string,int>
     */
    function lc_campaign_promo_guide_limits()
    {
        return array(
            'promotion_points'     => 3,
            'recommended_keywords' => 10,
            'forbidden_words'      => 10,
            'precautions'          => 5,
            'valid_db_rules'       => 5,
            'invalid_db_rules'     => 5,
            'images'               => 10,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_max_image_bytes')) {
    function lc_campaign_promo_guide_max_image_bytes()
    {
        $default = 2097152;
        if (!function_exists('lc_settings_get')) {
            return $default;
        }

        $raw = lc_settings_get('promoGuideMaxImageBytes', (string) $default);
        $bytes = (int) $raw;

        return $bytes > 0 ? $bytes : $default;
    }
}

if (!function_exists('lc_campaign_promo_guide_log_table')) {
    function lc_campaign_promo_guide_log_table()
    {
        return lc_table('campaign_promo_guide_logs');
    }
}

if (!function_exists('lc_campaign_promo_guide_log_create_table_sql')) {
    function lc_campaign_promo_guide_log_create_table_sql()
    {
        $table = lc_campaign_promo_guide_log_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
                `cpgl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `cpgl_cpg_id` bigint unsigned NOT NULL DEFAULT 0,
                `cpgl_cp_id` int unsigned NOT NULL DEFAULT 0,
                `cpgl_actor_type` varchar(20) NOT NULL DEFAULT '',
                `cpgl_actor_id` varchar(20) NOT NULL DEFAULT '',
                `cpgl_from_status` varchar(20) NOT NULL DEFAULT '',
                `cpgl_to_status` varchar(20) NOT NULL DEFAULT '',
                `cpgl_summary` varchar(500) NOT NULL DEFAULT '',
                `cpgl_revision_reason` varchar(500) NOT NULL DEFAULT '',
                `cpgl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`cpgl_id`),
                KEY `idx_cpgl_cpg_id` (`cpgl_cpg_id`),
                KEY `idx_cpgl_cp_id` (`cpgl_cp_id`),
                KEY `idx_cpgl_created` (`cpgl_created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_campaign_promo_guide_confirmation_table')) {
    function lc_campaign_promo_guide_confirmation_table()
    {
        return lc_table('campaign_promo_guide_confirmations');
    }
}

if (!function_exists('lc_campaign_promo_guide_confirmation_create_table_sql')) {
    function lc_campaign_promo_guide_confirmation_create_table_sql()
    {
        $table = lc_campaign_promo_guide_confirmation_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
                `cpgc_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `cpgc_pt_id` int unsigned NOT NULL DEFAULT 0,
                `cpgc_cp_id` int unsigned NOT NULL DEFAULT 0,
                `cpgc_cpg_id` bigint unsigned NOT NULL DEFAULT 0,
                `cpgc_guide_updated_at` datetime DEFAULT NULL,
                `cpgc_confirmed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`cpgc_id`),
                UNIQUE KEY `uk_cpgc_pt_cp` (`cpgc_pt_id`, `cpgc_cp_id`),
                KEY `idx_cpgc_pt_id` (`cpgc_pt_id`),
                KEY `idx_cpgc_cp_id` (`cpgc_cp_id`),
                KEY `idx_cpgc_cpg_id` (`cpgc_cpg_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_campaign_promo_guide_create_table_sql')) {
    function lc_campaign_promo_guide_create_table_sql()
    {
        $table = lc_campaign_promo_guide_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
                `cpg_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `cpg_cp_id` int unsigned NOT NULL DEFAULT 0,
                `cpg_mt_id` int unsigned NOT NULL DEFAULT 0,
                `cpg_promotion_points` longtext,
                `cpg_recommended_keywords` longtext,
                `cpg_forbidden_words` longtext,
                `cpg_precautions` longtext,
                `cpg_valid_db_rules` longtext,
                `cpg_invalid_db_rules` longtext,
                `cpg_approval_type` varchar(30) NOT NULL DEFAULT 'free',
                `cpg_status` varchar(20) NOT NULL DEFAULT 'draft',
                `cpg_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cpg_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `cpg_published_at` datetime DEFAULT NULL,
                PRIMARY KEY (`cpg_id`),
                UNIQUE KEY `uk_cpg_cp_id` (`cpg_cp_id`),
                KEY `idx_cpg_mt_id` (`cpg_mt_id`),
                KEY `idx_cpg_status` (`cpg_status`),
                KEY `idx_cpg_published_at` (`cpg_published_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_campaign_promo_guide_asset_create_table_sql')) {
    function lc_campaign_promo_guide_asset_create_table_sql()
    {
        $table = lc_campaign_promo_guide_asset_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
                `cpga_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `cpga_cpg_id` bigint unsigned NOT NULL DEFAULT 0,
                `cpga_cp_id` int unsigned NOT NULL DEFAULT 0,
                `cpga_mt_id` int unsigned NOT NULL DEFAULT 0,
                `cpga_original_filename` varchar(255) NOT NULL DEFAULT '',
                `cpga_stored_filename` varchar(255) NOT NULL DEFAULT '',
                `cpga_file_path` varchar(500) NOT NULL DEFAULT '',
                `cpga_mime_type` varchar(50) NOT NULL DEFAULT '',
                `cpga_file_size` int unsigned NOT NULL DEFAULT 0,
                `cpga_image_title` varchar(200) NOT NULL DEFAULT '',
                `cpga_sort_order` int NOT NULL DEFAULT 0,
                `cpga_is_active` tinyint(1) NOT NULL DEFAULT 1,
                `cpga_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`cpga_id`),
                KEY `idx_cpga_cpg_id` (`cpga_cpg_id`),
                KEY `idx_cpga_cp_id` (`cpga_cp_id`),
                KEY `idx_cpga_mt_id` (`cpga_mt_id`),
                KEY `idx_cpga_sort` (`cpga_cpg_id`, `cpga_sort_order`),
                KEY `idx_cpga_active` (`cpga_is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_campaign_promo_guide_db_ensure_schema')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_promo_guide_db_ensure_schema()
    {
        $guides = lc_campaign_promo_guide_table();
        if (!lc_db_table_exists($guides)) {
            $create = lc_sql_query(lc_campaign_promo_guide_create_table_sql(), false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'campaign_promo_guides 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $assets = lc_campaign_promo_guide_asset_table();
        if (!lc_db_table_exists($assets)) {
            $create = lc_sql_query(lc_campaign_promo_guide_asset_create_table_sql(), false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'campaign_promo_assets 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $confirmations = lc_campaign_promo_guide_confirmation_table();
        if (!lc_db_table_exists($confirmations)) {
            $create = lc_sql_query(lc_campaign_promo_guide_confirmation_create_table_sql(), false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'campaign_promo_guide_confirmations 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $logs = lc_campaign_promo_guide_log_table();
        if (!lc_db_table_exists($logs)) {
            $create = lc_sql_query(lc_campaign_promo_guide_log_create_table_sql(), false);
            if ($create === false) {
                return array('ok' => false, 'message' => 'campaign_promo_guide_logs 테이블 생성 실패: ' . lc_sql_error());
            }
        }

        $guides = lc_campaign_promo_guide_table();
        if (lc_db_table_exists($guides) && function_exists('lc_db_column_exists') && !lc_db_column_exists($guides, 'cpg_revision_reason')) {
            $alter = lc_sql_query("ALTER TABLE `{$guides}` ADD COLUMN `cpg_revision_reason` varchar(500) NOT NULL DEFAULT '' AFTER `cpg_status`", false);
            if ($alter === false) {
                return array('ok' => false, 'message' => 'cpg_revision_reason 컬럼 추가 실패: ' . lc_sql_error());
            }
        }

        return array('ok' => true, 'message' => 'campaign_promo_guides 스키마 준비 완료');
    }
}

if (!function_exists('lc_campaign_promo_guide_storage_base_dir')) {
    function lc_campaign_promo_guide_storage_base_dir()
    {
        if (defined('G5_DATA_PATH') && (string) G5_DATA_PATH !== '') {
            $dir = G5_DATA_PATH . '/linkconnect/campaign_promo_assets';
        } else {
            $dir = LC_PLUGIN_PATH . '/data/campaign_promo_assets';
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }

        return $dir;
    }
}

if (!function_exists('lc_campaign_promo_guide_campaign_dir')) {
    function lc_campaign_promo_guide_campaign_dir($mt_id, $cp_id)
    {
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;
        $base = lc_campaign_promo_guide_storage_base_dir();
        $dir = $base . '/' . $mt_id . '/' . $cp_id;

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $real_base = realpath($base);
        $real_dir = realpath($dir);
        if ($real_base === false || $real_dir === false || strpos($real_dir, $real_base) !== 0) {
            return '';
        }

        return $real_dir;
    }
}

if (!function_exists('lc_campaign_promo_guide_sanitize_text')) {
    function lc_campaign_promo_guide_sanitize_text($value, $max_len = 500)
    {
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, (int) $max_len, 'UTF-8');
        }

        return substr($text, 0, (int) $max_len);
    }
}

if (!function_exists('lc_campaign_promo_guide_normalize_string_list')) {
    /**
     * @param mixed $input
     * @return array<int,string>
     */
    function lc_campaign_promo_guide_normalize_string_list($input, $max_items, $item_max_len = 500)
    {
        $max_items = (int) $max_items;
        if ($max_items < 0) {
            $max_items = 0;
        }

        $items = array();
        if (!is_array($input)) {
            return $items;
        }

        foreach ($input as $raw) {
            if (count($items) >= $max_items) {
                break;
            }
            $text = lc_campaign_promo_guide_sanitize_text($raw, $item_max_len);
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_promo_guide_encode_json_list')) {
    function lc_campaign_promo_guide_encode_json_list(array $items)
    {
        $json = json_encode(array_values($items), JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '[]';
        }

        return $json;
    }
}

if (!function_exists('lc_campaign_promo_guide_decode_json_list')) {
    /**
     * @return array<int,string>
     */
    function lc_campaign_promo_guide_decode_json_list($json)
    {
        if (!is_string($json) || trim($json) === '') {
            return array();
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return array();
        }

        $items = array();
        foreach ($decoded as $item) {
            if (is_string($item) || is_numeric($item)) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $items[] = $text;
                }
            }
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_promo_guide_valid_approval_type')) {
    function lc_campaign_promo_guide_valid_approval_type($type)
    {
        $type = (string) $type;
        $allowed = array(
            LC_CPG_APPROVAL_FREE,
            LC_CPG_APPROVAL_FIRST_REVIEW,
            LC_CPG_APPROVAL_ALL_REVIEW,
        );

        return in_array($type, $allowed, true) ? $type : LC_CPG_APPROVAL_FREE;
    }
}

if (!function_exists('lc_campaign_promo_guide_status_label')) {
    function lc_campaign_promo_guide_status_label($status)
    {
        $labels = array(
            LC_CPG_STATUS_DRAFT     => '작성 중',
            LC_CPG_STATUS_REVIEW    => '검토 대기',
            LC_CPG_STATUS_REVISION  => '수정 요청',
            LC_CPG_STATUS_PUBLISHED => '파트너 공개 중',
            LC_CPG_STATUS_HIDDEN    => '비공개',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_campaign_promo_guide_current_actor')) {
    /**
     * @return array{type:string,id:string}
     */
    function lc_campaign_promo_guide_current_actor($actor_type = '')
    {
        global $member;

        $mb_id = isset($member['mb_id']) ? (string) $member['mb_id'] : '';
        if ($actor_type === 'admin' || ($actor_type === '' && function_exists('lc_can_access_admin') && lc_can_access_admin())) {
            return array('type' => 'admin', 'id' => $mb_id);
        }
        if ($actor_type === 'merchant' || $actor_type === '') {
            return array('type' => 'merchant', 'id' => $mb_id);
        }

        return array('type' => (string) $actor_type, 'id' => $mb_id);
    }
}

if (!function_exists('lc_campaign_promo_guide_write_log')) {
    function lc_campaign_promo_guide_write_log(array $guide, $from_status, $to_status, $summary, $actor_type = '', $actor_id = '', $revision_reason = '')
    {
        if (!lc_db_installed() || !is_array($guide)) {
            return;
        }

        lc_campaign_promo_guide_db_ensure_schema();

        if ($actor_type === '' && $actor_id === '') {
            $actor = lc_campaign_promo_guide_current_actor();
            $actor_type = $actor['type'];
            $actor_id = $actor['id'];
        }

        $table = lc_campaign_promo_guide_log_table();
        $cpg_id = (int) ($guide['cpg_id'] ?? 0);
        $cp_id = (int) ($guide['cpg_cp_id'] ?? 0);

        lc_sql_query(" INSERT INTO `{$table}` SET
            cpgl_cpg_id = '{$cpg_id}',
            cpgl_cp_id = '{$cp_id}',
            cpgl_actor_type = '" . lc_sql_escape((string) $actor_type) . "',
            cpgl_actor_id = '" . lc_sql_escape((string) $actor_id) . "',
            cpgl_from_status = '" . lc_sql_escape((string) $from_status) . "',
            cpgl_to_status = '" . lc_sql_escape((string) $to_status) . "',
            cpgl_summary = '" . lc_sql_escape(lc_campaign_promo_guide_sanitize_text($summary, 500)) . "',
            cpgl_revision_reason = '" . lc_sql_escape(lc_campaign_promo_guide_sanitize_text($revision_reason, 500)) . "',
            cpgl_created_at = NOW() ", false);
    }
}

if (!function_exists('lc_campaign_promo_guide_empty_summary')) {
    function lc_campaign_promo_guide_empty_summary()
    {
        return array(
            'exists'          => false,
            'guideId'         => 0,
            'status'          => '',
            'statusLabel'     => '-',
            'hasPoints'       => false,
            'keywordCount'    => 0,
            'forbiddenCount'  => 0,
            'imageCount'      => 0,
            'updatedAt'       => '',
            'publishedAt'     => '',
            'revisionReason'  => '',
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_row_to_summary')) {
    function lc_campaign_promo_guide_row_to_summary(array $guide)
    {
        $points = lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_promotion_points'] ?? ''));
        $keywords = lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_recommended_keywords'] ?? ''));
        $forbidden = lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_forbidden_words'] ?? ''));
        $image_count = lc_campaign_promo_guide_count_active_assets((int) ($guide['cpg_id'] ?? 0));
        $status = (string) ($guide['cpg_status'] ?? '');

        return array(
            'exists'          => true,
            'guideId'         => (int) ($guide['cpg_id'] ?? 0),
            'status'          => $status,
            'statusLabel'     => lc_campaign_promo_guide_status_label($status),
            'hasPoints'       => count($points) > 0,
            'keywordCount'    => count($keywords),
            'forbiddenCount'  => count($forbidden),
            'imageCount'      => $image_count,
            'updatedAt'       => (string) ($guide['cpg_updated_at'] ?? ''),
            'publishedAt'     => (string) ($guide['cpg_published_at'] ?? ''),
            'revisionReason'  => (string) ($guide['cpg_revision_reason'] ?? ''),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_summaries_for_cp_ids')) {
    /**
     * @param array<int,int> $cp_ids
     * @return array<int,array>
     */
    function lc_campaign_promo_guide_summaries_for_cp_ids(array $cp_ids)
    {
        $out = array();
        if (!lc_db_installed() || count($cp_ids) === 0) {
            return $out;
        }

        $ids = array();
        foreach ($cp_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if (count($ids) === 0) {
            return $out;
        }

        $in = implode(',', array_map('intval', array_values($ids)));
        $table = lc_campaign_promo_guide_table();
        $result = lc_sql_query(
            " SELECT * FROM `{$table}`
              WHERE cpg_cp_id IN ({$in})
              ORDER BY cpg_updated_at ASC, cpg_id ASC ",
            false
        );
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                // 동일 cp_id 중복 시 최신(뒤에 오는 행)으로 덮어씀
                $out[(int) $row['cpg_cp_id']] = lc_campaign_promo_guide_row_to_summary($row);
            }
        }

        return $out;
    }
}

if (!function_exists('lc_campaign_promo_guide_valid_status')) {
    function lc_campaign_promo_guide_valid_status($status)
    {
        $status = (string) $status;
        $allowed = array(
            LC_CPG_STATUS_DRAFT,
            LC_CPG_STATUS_REVIEW,
            LC_CPG_STATUS_PUBLISHED,
            LC_CPG_STATUS_HIDDEN,
            LC_CPG_STATUS_REVISION,
        );

        return in_array($status, $allowed, true) ? $status : LC_CPG_STATUS_DRAFT;
    }
}

if (!function_exists('lc_campaign_promo_guide_assert_campaign_owner')) {
    /**
     * @return array{ok:bool,message:string,campaign?:array}
     */
    function lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id)
    {
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;

        if ($mt_id <= 0 || $cp_id <= 0) {
            return array('ok' => false, 'message' => '유효하지 않은 광고상품입니다.');
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        if ((int) ($campaign['mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '해당 광고상품에 대한 권한이 없습니다.');
        }

        return array('ok' => true, 'message' => '', 'campaign' => $campaign);
    }
}

if (!function_exists('lc_campaign_promo_guide_get_by_cp_id')) {
    function lc_campaign_promo_guide_get_by_cp_id($cp_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return null;
        }
        $table = lc_campaign_promo_guide_table();

        // 최신 행 우선 (과거 중복 행이 있어도 내용 있는 가이드를 찾도록)
        $row = lc_sql_fetch(
            " SELECT * FROM `{$table}`
              WHERE cpg_cp_id = '{$cp_id}'
              ORDER BY cpg_updated_at DESC, cpg_id DESC
              LIMIT 1 ",
            false
        );

        return is_array($row) && !empty($row['cpg_id']) ? $row : null;
    }
}

if (!function_exists('lc_campaign_promo_guide_get_by_id')) {
    function lc_campaign_promo_guide_get_by_id($cpg_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cpg_id = (int) $cpg_id;
        $table = lc_campaign_promo_guide_table();

        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cpg_id = '{$cpg_id}' LIMIT 1 ", false);

        return is_array($row) && !empty($row['cpg_id']) ? $row : null;
    }
}

if (!function_exists('lc_campaign_promo_guide_heal_owner')) {
    /**
     * 캠페인 광고주가 바뀐 뒤에도 가이드/소재를 현재 광고주 소유로 맞춤
     *
     * @param array<string,mixed> $guide
     * @return array<string,mixed>
     */
    function lc_campaign_promo_guide_heal_owner($mt_id, array $guide)
    {
        $mt_id = (int) $mt_id;
        $guide_mt = (int) ($guide['cpg_mt_id'] ?? 0);
        $cp_id = (int) ($guide['cpg_cp_id'] ?? 0);
        $cpg_id = (int) ($guide['cpg_id'] ?? 0);
        if ($mt_id <= 0 || $cpg_id <= 0 || $guide_mt === $mt_id) {
            return $guide;
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign) || (int) ($campaign['mt_id'] ?? 0) !== $mt_id) {
            return $guide;
        }

        $old_mt = $guide_mt;
        $assets_table = lc_campaign_promo_guide_asset_table();
        if (function_exists('lc_db_table_exists') && lc_db_table_exists($assets_table)) {
            $result = lc_sql_query(
                " SELECT * FROM `{$assets_table}` WHERE cpga_cpg_id = '{$cpg_id}' ",
                false
            );
            if ($result) {
                while ($asset = sql_fetch_array($result)) {
                    lc_campaign_promo_guide_relocate_asset_to_mt($asset, $old_mt, $mt_id, $cp_id);
                }
            }
        }

        $table = lc_campaign_promo_guide_table();
        lc_sql_query(
            " UPDATE `{$table}` SET cpg_mt_id = '{$mt_id}', cpg_updated_at = NOW()
              WHERE cpg_id = '{$cpg_id}' LIMIT 1 ",
            false
        );

        if (function_exists('lc_db_table_exists') && lc_db_table_exists($assets_table)) {
            lc_sql_query(
                " UPDATE `{$assets_table}` SET cpga_mt_id = '{$mt_id}'
                  WHERE cpga_cpg_id = '{$cpg_id}' ",
                false
            );
        }

        $fresh = lc_campaign_promo_guide_get_by_id($cpg_id);

        return is_array($fresh) ? $fresh : array_merge($guide, array('cpg_mt_id' => $mt_id));
    }
}

if (!function_exists('lc_campaign_promo_guide_relocate_asset_to_mt')) {
    /**
     * 소재 파일을 새 광고주 디렉터리로 이동하고 DB 상대경로를 갱신
     *
     * @param array<string,mixed> $asset
     */
    function lc_campaign_promo_guide_relocate_asset_to_mt(array $asset, $old_mt, $new_mt, $cp_id)
    {
        $old_mt = (int) $old_mt;
        $new_mt = (int) $new_mt;
        $cp_id = (int) $cp_id;
        $cpga_id = (int) ($asset['cpga_id'] ?? 0);
        $stored = basename((string) ($asset['cpga_stored_filename'] ?? ''));
        if ($cpga_id <= 0 || $stored === '' || $old_mt <= 0 || $new_mt <= 0 || $cp_id <= 0 || $old_mt === $new_mt) {
            return false;
        }

        $src = '';
        // 현재 DB mt 기준 경로
        $try = lc_campaign_promo_guide_asset_full_path(array_merge($asset, array('cpga_mt_id' => $old_mt)));
        if ($try !== '' && is_file($try)) {
            $src = $try;
        }
        if ($src === '') {
            $try = lc_campaign_promo_guide_asset_full_path($asset);
            if ($try !== '' && is_file($try)) {
                $src = $try;
            }
        }

        $dest_dir = lc_campaign_promo_guide_campaign_dir($new_mt, $cp_id);
        if ($dest_dir === '') {
            return false;
        }
        $dest = $dest_dir . '/' . $stored;

        if ($src !== '' && is_file($src) && realpath($src) !== realpath($dest)) {
            if (!@rename($src, $dest)) {
                if (@copy($src, $dest)) {
                    @unlink($src);
                } else {
                    return false;
                }
            }
            @chmod($dest, 0644);
        }

        $relative = 'linkconnect/campaign_promo_assets/' . $new_mt . '/' . $cp_id . '/' . $stored;
        $table = lc_campaign_promo_guide_asset_table();
        lc_sql_query(
            " UPDATE `{$table}` SET
                cpga_mt_id = '{$new_mt}',
                cpga_file_path = '" . lc_sql_escape($relative) . "'
              WHERE cpga_id = '{$cpga_id}' LIMIT 1 ",
            false
        );

        return true;
    }
}

if (!function_exists('lc_campaign_promo_guide_assert_owner')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_assert_owner($mt_id, $guide)
    {
        $mt_id = (int) $mt_id;
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $guide = lc_campaign_promo_guide_heal_owner($mt_id, $guide);

        if ((int) ($guide['cpg_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '해당 홍보 가이드에 대한 권한이 없습니다.');
        }

        $cp_id = (int) ($guide['cpg_cp_id'] ?? 0);
        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        return array('ok' => true, 'message' => '', 'guide' => $guide);
    }
}

if (!function_exists('lc_campaign_promo_guide_list_assets')) {
    /**
     * @return array<int,array>
     */
    function lc_campaign_promo_guide_list_assets($cpg_id, $active_only = true)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $cpg_id = (int) $cpg_id;
        $table = lc_campaign_promo_guide_asset_table();
        $where = " cpga_cpg_id = '{$cpg_id}' ";
        if ($active_only) {
            $where .= " AND cpga_is_active = 1 ";
        }

        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY cpga_sort_order ASC, cpga_id ASC ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_campaign_promo_guide_count_active_assets')) {
    function lc_campaign_promo_guide_count_active_assets($cpg_id)
    {
        if (!lc_db_installed()) {
            return 0;
        }

        $cpg_id = (int) $cpg_id;
        $table = lc_campaign_promo_guide_asset_table();
        $row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE cpga_cpg_id = '{$cpg_id}' AND cpga_is_active = 1 ");

        return $row ? (int) $row['cnt'] : 0;
    }
}

if (!function_exists('lc_campaign_promo_guide_asset_get_by_id')) {
    function lc_campaign_promo_guide_asset_get_by_id($cpga_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cpga_id = (int) $cpga_id;
        $table = lc_campaign_promo_guide_asset_table();

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cpga_id = '{$cpga_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_campaign_promo_guide_validate_payload')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string,errors:array<string,string>,data:array<string,mixed>}
     */
    function lc_campaign_promo_guide_validate_payload(array $payload)
    {
        $limits = lc_campaign_promo_guide_limits();
        $errors = array();

        $fields = array(
            'promotionPoints'     => array('key' => 'promotion_points', 'max' => $limits['promotion_points'], 'len' => 500),
            'recommendedKeywords' => array('key' => 'recommended_keywords', 'max' => $limits['recommended_keywords'], 'len' => 100),
            'forbiddenWords'      => array('key' => 'forbidden_words', 'max' => $limits['forbidden_words'], 'len' => 100),
            'precautions'         => array('key' => 'precautions', 'max' => $limits['precautions'], 'len' => 500),
            'validDbRules'        => array('key' => 'valid_db_rules', 'max' => $limits['valid_db_rules'], 'len' => 500),
            'invalidDbRules'      => array('key' => 'invalid_db_rules', 'max' => $limits['invalid_db_rules'], 'len' => 500),
        );

        $data = array();
        foreach ($fields as $api_key => $meta) {
            $source_key = $meta['key'];
            $input = null;
            if (array_key_exists($api_key, $payload)) {
                $input = $payload[$api_key];
            } elseif (array_key_exists($source_key, $payload)) {
                $input = $payload[$source_key];
            } else {
                $input = array();
            }

            $list = lc_campaign_promo_guide_normalize_string_list($input, $meta['max'], $meta['len']);
            if (is_array($input) && count($input) > $meta['max']) {
                $errors[$api_key] = '최대 ' . $meta['max'] . '개까지 입력할 수 있습니다.';
            }
            $data[$source_key] = $list;
        }

        $approval = LC_CPG_APPROVAL_FREE;
        if (isset($payload['approvalType'])) {
            $approval = lc_campaign_promo_guide_valid_approval_type($payload['approvalType']);
        } elseif (isset($payload['approval_type'])) {
            $approval = lc_campaign_promo_guide_valid_approval_type($payload['approval_type']);
        }
        $data['approval_type'] = $approval;

        if (count($errors) > 0) {
            return array(
                'ok'      => false,
                'message' => '입력값을 확인해 주세요.',
                'errors'  => $errors,
                'data'    => $data,
            );
        }

        return array(
            'ok'      => true,
            'message' => '',
            'errors'  => array(),
            'data'    => $data,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_to_api')) {
    /**
     * @param array<string,mixed> $row
     * @param array<int,array>|null $assets
     * @return array<string,mixed>
     */
    function lc_campaign_promo_guide_to_api(array $row, $assets = null, $include_internal = false)
    {
        $cpg_id = (int) ($row['cpg_id'] ?? 0);
        if ($assets === null) {
            $assets = lc_campaign_promo_guide_list_assets($cpg_id, true);
        }

        $asset_api = array_map('lc_campaign_promo_guide_asset_to_api', $assets);

        $data = array(
            'exists'             => true,
            'id'                 => $cpg_id,
            'guideId'            => $cpg_id,
            'campaignId'         => (int) ($row['cpg_cp_id'] ?? 0),
            'cpId'               => (int) ($row['cpg_cp_id'] ?? 0),
            'promotionPoints'    => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_promotion_points'] ?? '')),
            'recommendedKeywords'=> lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_recommended_keywords'] ?? '')),
            'forbiddenWords'     => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_forbidden_words'] ?? '')),
            'precautions'        => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_precautions'] ?? '')),
            'validDbRules'       => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_valid_db_rules'] ?? '')),
            'invalidDbRules'     => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_invalid_db_rules'] ?? '')),
            'approvalType'       => (string) ($row['cpg_approval_type'] ?? LC_CPG_APPROVAL_FREE),
            'guideStatus'        => (string) ($row['cpg_status'] ?? LC_CPG_STATUS_DRAFT),
            'guideStatusLabel'   => lc_campaign_promo_guide_status_label((string) ($row['cpg_status'] ?? LC_CPG_STATUS_DRAFT)),
            'revisionReason'     => (string) ($row['cpg_revision_reason'] ?? ''),
            'createdAt'          => (string) ($row['cpg_created_at'] ?? ''),
            'updatedAt'          => (string) ($row['cpg_updated_at'] ?? ''),
            'publishedAt'        => (string) ($row['cpg_published_at'] ?? ''),
            'images'             => $asset_api,
            'limits'             => lc_campaign_promo_guide_limits(),
            'maxImageBytes'      => lc_campaign_promo_guide_max_image_bytes(),
        );

        if ($include_internal) {
            $data['advertiserId'] = (int) ($row['cpg_mt_id'] ?? 0);
            $data['mtId'] = (int) ($row['cpg_mt_id'] ?? 0);
            // 관리자 검수 화면에서는 관리자 전용 다운로드 URL 사용
            foreach ($data['images'] as $idx => $img) {
                if (!empty($img['adminDownloadUrl'])) {
                    $data['images'][$idx]['downloadUrl'] = $img['adminDownloadUrl'];
                }
            }
        }

        return $data;
    }
}

if (!function_exists('lc_campaign_promo_guide_asset_to_api')) {
    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    function lc_campaign_promo_guide_asset_to_api(array $row)
    {
        $id = (int) ($row['cpga_id'] ?? 0);

        return array(
            'id'               => $id,
            'assetId'          => $id,
            'guideId'          => (int) ($row['cpga_cpg_id'] ?? 0),
            'campaignId'       => (int) ($row['cpga_cp_id'] ?? 0),
            'originalFilename' => (string) ($row['cpga_original_filename'] ?? ''),
            'mimeType'         => (string) ($row['cpga_mime_type'] ?? ''),
            'fileSize'         => (int) ($row['cpga_file_size'] ?? 0),
            'imageTitle'       => (string) ($row['cpga_image_title'] ?? ''),
            'sortOrder'        => (int) ($row['cpga_sort_order'] ?? 0),
            'isActive'         => (int) ($row['cpga_is_active'] ?? 0) === 1,
            'createdAt'        => (string) ($row['cpga_created_at'] ?? ''),
            'downloadUrl'      => LC_PLUGIN_URL . '/merchant/api/campaign-guide-asset.php?assetId=' . $id,
            'partnerDownloadUrl' => LC_PLUGIN_URL . '/partner/api/campaign-guide-asset.php?assetId=' . $id,
            'adminDownloadUrl' => LC_PLUGIN_URL . '/admin/api/campaign-guide-asset.php?assetId=' . $id,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_create')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_create($mt_id, $cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        lc_campaign_promo_guide_db_ensure_schema();

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;

        $existing = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (is_array($existing)) {
            $existing = lc_campaign_promo_guide_heal_owner($mt_id, $existing);
            $assert = lc_campaign_promo_guide_assert_owner($mt_id, $existing);
            if (empty($assert['ok'])) {
                return array('ok' => false, 'message' => $assert['message']);
            }

            // 멱등: 이미 있으면 성공으로 반환해 광고주/관리자 화면이 기존 소재를 이어서 편집하게 함
            return array(
                'ok'      => true,
                'message' => '이미 등록된 홍보 가이드를 불러왔습니다.',
                'guide'   => $assert['guide'],
                'created' => false,
            );
        }

        $table = lc_campaign_promo_guide_table();
        $approval = lc_sql_escape(LC_CPG_APPROVAL_FREE);
        $status = lc_sql_escape(LC_CPG_STATUS_DRAFT);
        $empty = lc_sql_escape('[]');

        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            cpg_cp_id = '{$cp_id}',
            cpg_mt_id = '{$mt_id}',
            cpg_promotion_points = '{$empty}',
            cpg_recommended_keywords = '{$empty}',
            cpg_forbidden_words = '{$empty}',
            cpg_precautions = '{$empty}',
            cpg_valid_db_rules = '{$empty}',
            cpg_invalid_db_rules = '{$empty}',
            cpg_approval_type = '{$approval}',
            cpg_status = '{$status}',
            cpg_created_at = NOW(),
            cpg_updated_at = NOW() ", false);

        if ($ok === false) {
            // UNIQUE 충돌 등 → 기존 행 재조회
            $race = lc_campaign_promo_guide_get_by_cp_id($cp_id);
            if (is_array($race)) {
                $race = lc_campaign_promo_guide_heal_owner($mt_id, $race);
                $assert = lc_campaign_promo_guide_assert_owner($mt_id, $race);
                if (!empty($assert['ok'])) {
                    return array(
                        'ok'      => true,
                        'message' => '이미 등록된 홍보 가이드를 불러왔습니다.',
                        'guide'   => $assert['guide'],
                        'created' => false,
                    );
                }
            }

            return array('ok' => false, 'message' => '홍보 가이드 생성에 실패했습니다.');
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드 생성 후 조회에 실패했습니다.');
        }

        return array('ok' => true, 'message' => '홍보 가이드가 생성되었습니다.', 'guide' => $guide, 'created' => true);
    }
}

if (!function_exists('lc_campaign_promo_guide_save_content')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string,errors?:array,guide?:array}
     */
    function lc_campaign_promo_guide_save_content($mt_id, $cp_id, array $payload, $force_draft = false)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            $created = lc_campaign_promo_guide_create($mt_id, $cp_id);
            if (empty($created['ok']) || empty($created['guide'])) {
                return array(
                    'ok'      => false,
                    'message' => !empty($created['message']) ? $created['message'] : '홍보 가이드를 먼저 생성해 주세요.',
                );
            }
            $guide = $created['guide'];
        }

        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }
        $guide = $assert['guide'];

        $status = (string) ($guide['cpg_status'] ?? LC_CPG_STATUS_DRAFT);
        if ($status === LC_CPG_STATUS_REVIEW) {
            return array('ok' => false, 'message' => '검토 중인 가이드는 수정할 수 없습니다.');
        }
        if ($status === LC_CPG_STATUS_PUBLISHED && !$force_draft) {
            return array('ok' => false, 'message' => '공개된 가이드는 임시저장 또는 검토 요청을 이용해 주세요.');
        }

        $validated = lc_campaign_promo_guide_validate_payload($payload);
        if (empty($validated['ok'])) {
            return array('ok' => false, 'message' => $validated['message'], 'errors' => $validated['errors']);
        }

        $data = $validated['data'];
        $cpg_id = (int) $guide['cpg_id'];
        $table = lc_campaign_promo_guide_table();
        $new_status = $status;
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;

        $encoded_points = lc_campaign_promo_guide_encode_json_list($data['promotion_points']);
        $encoded_keywords = lc_campaign_promo_guide_encode_json_list($data['recommended_keywords']);
        $encoded_forbidden = lc_campaign_promo_guide_encode_json_list($data['forbidden_words']);
        $encoded_precautions = lc_campaign_promo_guide_encode_json_list($data['precautions']);
        $encoded_valid = lc_campaign_promo_guide_encode_json_list($data['valid_db_rules']);
        $encoded_invalid = lc_campaign_promo_guide_encode_json_list($data['invalid_db_rules']);

        $sets = array(
            // 소유권은 위에서 검증했으므로 저장 시 현재 광고주로 맞춤 (구 mt_id 불일치로 0건 업데이트 방지)
            "cpg_mt_id = '{$mt_id}'",
            "cpg_promotion_points = '" . lc_sql_escape($encoded_points) . "'",
            "cpg_recommended_keywords = '" . lc_sql_escape($encoded_keywords) . "'",
            "cpg_forbidden_words = '" . lc_sql_escape($encoded_forbidden) . "'",
            "cpg_precautions = '" . lc_sql_escape($encoded_precautions) . "'",
            "cpg_valid_db_rules = '" . lc_sql_escape($encoded_valid) . "'",
            "cpg_invalid_db_rules = '" . lc_sql_escape($encoded_invalid) . "'",
            "cpg_approval_type = '" . lc_sql_escape($data['approval_type']) . "'",
            "cpg_updated_at = NOW()",
        );

        if ($status === LC_CPG_STATUS_PUBLISHED && $force_draft) {
            // 공개 상태 유지 (단순 수정)
        } elseif ($force_draft || $status === LC_CPG_STATUS_HIDDEN || $status === LC_CPG_STATUS_REVISION) {
            $new_status = LC_CPG_STATUS_DRAFT;
            $sets[] = "cpg_status = '" . lc_sql_escape(LC_CPG_STATUS_DRAFT) . "'";
            $sets[] = "cpg_revision_reason = ''";
        }

        // cpg_id 기준으로만 갱신 (사전 assert_owner 완료). mt_id 조건은 0건 성공 위장 원인이었음.
        $sql = " UPDATE `{$table}` SET " . implode(', ', $sets) . "
            WHERE cpg_id = '{$cpg_id}' AND cpg_cp_id = '{$cp_id}' LIMIT 1 ";

        if (lc_sql_query($sql, false) === false) {
            return array('ok' => false, 'message' => '홍보 가이드 저장에 실패했습니다.');
        }

        if (function_exists('lc_sql_affected_rows') && lc_sql_affected_rows() < 1) {
            // MySQL은 값이 동일하면 affected=0 일 수 있어, 재조회로 실제 반영 여부 확인
            $probe = lc_campaign_promo_guide_get_by_id($cpg_id);
            if (!is_array($probe) || (int) ($probe['cpg_cp_id'] ?? 0) !== $cp_id) {
                return array('ok' => false, 'message' => '홍보 가이드 저장에 실패했습니다. (대상 행 없음)');
            }
        }

        $updated = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($updated)) {
            return array('ok' => false, 'message' => '저장 후 가이드를 불러오지 못했습니다.');
        }

        // 핵심 필드가 요청값과 다르면 저장 실패로 처리
        $saved_points = lc_campaign_promo_guide_decode_json_list((string) ($updated['cpg_promotion_points'] ?? ''));
        if (count($data['promotion_points']) > 0 && $saved_points !== $data['promotion_points']) {
            // 순서만 다른 경우는 허용, 내용 누락만 실패
            $missing = array_diff($data['promotion_points'], $saved_points);
            if (count($missing) > 0) {
                return array('ok' => false, 'message' => '홍보 가이드 내용이 DB에 반영되지 않았습니다. 다시 저장해 주세요.');
            }
        }

        if ($status !== $new_status) {
            lc_campaign_promo_guide_write_log($updated, $status, $new_status, '광고주 내용 저장', 'merchant');
        }

        return array(
            'ok'      => true,
            'message' => $force_draft ? '임시 저장되었습니다.' : '홍보 가이드가 저장되었습니다.',
            'guide'   => $updated,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_submit_review')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_submit_review($mt_id, $cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }

        $status = (string) ($guide['cpg_status'] ?? '');
        if ($status === LC_CPG_STATUS_REVIEW) {
            return array('ok' => false, 'message' => '이미 검토 요청된 상태입니다.');
        }

        $cpg_id = (int) $guide['cpg_id'];
        $table = lc_campaign_promo_guide_table();
        $review = lc_sql_escape(LC_CPG_STATUS_REVIEW);
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;

        $ok = lc_sql_query(" UPDATE `{$table}` SET
            cpg_mt_id = '{$mt_id}',
            cpg_status = '{$review}',
            cpg_revision_reason = '',
            cpg_updated_at = NOW()
            WHERE cpg_id = '{$cpg_id}' AND cpg_cp_id = '{$cp_id}' LIMIT 1 ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '검토 요청에 실패했습니다.');
        }

        $updated = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($updated) || (string) ($updated['cpg_status'] ?? '') !== LC_CPG_STATUS_REVIEW) {
            return array('ok' => false, 'message' => '검토 요청이 DB에 반영되지 않았습니다. 다시 시도해 주세요.');
        }

        lc_campaign_promo_guide_write_log($updated, $status, LC_CPG_STATUS_REVIEW, '광고주 검토 요청', 'merchant');

        return array(
            'ok'      => true,
            'message' => '검토 요청이 접수되었습니다.',
            'guide'   => $updated,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_admin_update_status')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_admin_update_status($cpg_id, $new_status)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cpg_id = (int) $cpg_id;
        $new_status = lc_campaign_promo_guide_valid_status($new_status);
        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $from_status = (string) ($guide['cpg_status'] ?? '');
        $table = lc_campaign_promo_guide_table();
        $status_esc = lc_sql_escape($new_status);
        $published_sql = 'cpg_published_at = NULL';
        $revision_clear = '';
        if ($new_status === LC_CPG_STATUS_PUBLISHED) {
            $published_sql = 'cpg_published_at = NOW()';
            $revision_clear = ", cpg_revision_reason = ''";
        }

        $ok = lc_sql_query(" UPDATE `{$table}` SET
            cpg_status = '{$status_esc}',
            {$published_sql}
            {$revision_clear},
            cpg_updated_at = NOW()
            WHERE cpg_id = '{$cpg_id}' LIMIT 1 ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '상태 변경에 실패했습니다.');
        }

        $updated = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (is_array($updated) && $from_status !== $new_status) {
            lc_campaign_promo_guide_write_log($updated, $from_status, $new_status, '상태 변경: ' . lc_campaign_promo_guide_status_label($new_status), 'admin');
        }

        return array(
            'ok'      => true,
            'message' => '상태가 변경되었습니다.',
            'guide'   => $updated,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_admin_request_revision')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_admin_request_revision($cpg_id, $reason)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cpg_id = (int) $cpg_id;
        $reason = lc_campaign_promo_guide_sanitize_text($reason, 500);
        if ($reason === '') {
            return array('ok' => false, 'message' => '수정 요청 사유를 입력해 주세요.');
        }

        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $from_status = (string) ($guide['cpg_status'] ?? '');
        $table = lc_campaign_promo_guide_table();
        $status_esc = lc_sql_escape(LC_CPG_STATUS_REVISION);
        $reason_esc = lc_sql_escape($reason);

        $ok = lc_sql_query(" UPDATE `{$table}` SET
            cpg_status = '{$status_esc}',
            cpg_revision_reason = '{$reason_esc}',
            cpg_updated_at = NOW()
            WHERE cpg_id = '{$cpg_id}' LIMIT 1 ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '수정 요청 처리에 실패했습니다.');
        }

        $updated = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (is_array($updated)) {
            lc_campaign_promo_guide_write_log($updated, $from_status, LC_CPG_STATUS_REVISION, '관리자 수정 요청', 'admin', '', $reason);
            if (function_exists('lc_admin_log_write')) {
                lc_admin_log_write('promo_guide_revision', 'campaign_promo_guide', $cpg_id, '홍보 가이드 수정 요청: ' . $reason, array('cp_id' => (int) ($guide['cpg_cp_id'] ?? 0)));
            }
        }

        return array(
            'ok'      => true,
            'message' => '광고주에게 수정 요청이 전달되었습니다.',
            'guide'   => $updated,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_admin_list_for_api')) {
    /**
     * @return array{items:array,total:int}
     */
    function lc_campaign_promo_guide_admin_list_for_api(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array('items' => array(), 'total' => 0);
        }

        $table = lc_table('campaigns');
        $gt = lc_campaign_promo_guide_table();
        $mt = lc_table('merchants');

        $where = ' 1=1 ';
        if (!empty($filters['status'])) {
            $where .= " AND g.cpg_status = '" . lc_sql_escape((string) $filters['status']) . "' ";
        } else {
            $where .= ' AND g.cpg_id IS NOT NULL ';
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape((string) $filters['q']);
            $where .= " AND (c.cp_name LIKE '%{$q}%' OR m.mt_company LIKE '%{$q}%') ";
        }

        $sql = " SELECT g.*, c.cp_name, c.cp_code, c.cp_status, m.mt_company
            FROM `{$gt}` g
            INNER JOIN `{$table}` c ON c.cp_id = g.cpg_cp_id
            LEFT JOIN `{$mt}` m ON m.mt_id = g.cpg_mt_id
            WHERE {$where}
            ORDER BY g.cpg_updated_at DESC
            LIMIT 200 ";

        $items = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $summary = lc_campaign_promo_guide_row_to_summary($row);
                $items[] = array(
                    'guideId'        => (int) $row['cpg_id'],
                    'campaignId'     => (int) $row['cpg_cp_id'],
                    'campaignName'   => (string) ($row['cp_name'] ?? ''),
                    'campaignCode'   => (string) ($row['cp_code'] ?? ''),
                    'campaignStatus' => (string) ($row['cp_status'] ?? ''),
                    'advertiserName' => (string) ($row['mt_company'] ?? ''),
                    'mtId'           => (int) ($row['cpg_mt_id'] ?? 0),
                    'promoGuide'     => $summary,
                );
            }
        }

        return array('items' => $items, 'total' => count($items));
    }
}

if (!function_exists('lc_campaign_promo_guide_logs_for_api')) {
    function lc_campaign_promo_guide_logs_for_api($cpg_id, $limit = 30)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $cpg_id = (int) $cpg_id;
        $limit = max(1, min(100, (int) $limit));
        $table = lc_campaign_promo_guide_log_table();
        $items = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE cpgl_cpg_id = '{$cpg_id}' ORDER BY cpgl_created_at DESC LIMIT {$limit} ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'             => (int) $row['cpgl_id'],
                    'guideId'        => (int) $row['cpgl_cpg_id'],
                    'campaignId'     => (int) $row['cpgl_cp_id'],
                    'actorType'      => (string) $row['cpgl_actor_type'],
                    'actorId'        => (string) $row['cpgl_actor_id'],
                    'fromStatus'     => (string) $row['cpgl_from_status'],
                    'fromStatusLabel'=> lc_campaign_promo_guide_status_label((string) $row['cpgl_from_status']),
                    'toStatus'       => (string) $row['cpgl_to_status'],
                    'toStatusLabel'  => lc_campaign_promo_guide_status_label((string) $row['cpgl_to_status']),
                    'summary'        => (string) $row['cpgl_summary'],
                    'revisionReason' => (string) $row['cpgl_revision_reason'],
                    'createdAt'      => (string) $row['cpgl_created_at'],
                );
            }
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_promo_guide_admin_save')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string,errors?:array,guide?:array}
     */
    function lc_campaign_promo_guide_admin_save($cpg_id, array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cpg_id = (int) $cpg_id;
        $guide = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $validated = lc_campaign_promo_guide_validate_payload($payload);
        if (empty($validated['ok'])) {
            return array('ok' => false, 'message' => $validated['message'], 'errors' => $validated['errors']);
        }

        $data = $validated['data'];
        $from_status = (string) ($guide['cpg_status'] ?? '');
        $table = lc_campaign_promo_guide_table();
        $sets = array(
            "cpg_promotion_points = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['promotion_points'])) . "'",
            "cpg_recommended_keywords = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['recommended_keywords'])) . "'",
            "cpg_forbidden_words = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['forbidden_words'])) . "'",
            "cpg_precautions = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['precautions'])) . "'",
            "cpg_valid_db_rules = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['valid_db_rules'])) . "'",
            "cpg_invalid_db_rules = '" . lc_sql_escape(lc_campaign_promo_guide_encode_json_list($data['invalid_db_rules'])) . "'",
            "cpg_approval_type = '" . lc_sql_escape($data['approval_type']) . "'",
            "cpg_updated_at = NOW()",
        );

        $new_status = $from_status;
        if (isset($payload['guideStatus']) || isset($payload['guide_status'])) {
            $status = isset($payload['guideStatus']) ? $payload['guideStatus'] : $payload['guide_status'];
            $new_status = lc_campaign_promo_guide_valid_status($status);
            $sets[] = "cpg_status = '" . lc_sql_escape($new_status) . "'";
            if ($new_status === LC_CPG_STATUS_PUBLISHED) {
                $sets[] = 'cpg_published_at = NOW()';
            }
        }

        $sql = " UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE cpg_id = '{$cpg_id}' LIMIT 1 ";
        if (lc_sql_query($sql, false) === false) {
            return array('ok' => false, 'message' => '홍보 가이드 저장에 실패했습니다.');
        }

        $updated = lc_campaign_promo_guide_get_by_id($cpg_id);
        if (is_array($updated)) {
            lc_campaign_promo_guide_write_log($updated, $from_status, $new_status, '관리자 직접 수정', 'admin');
            if (function_exists('lc_admin_log_write')) {
                lc_admin_log_write('promo_guide_save', 'campaign_promo_guide', $cpg_id, '관리자 홍보 가이드 직접 수정', array('cp_id' => (int) ($guide['cpg_cp_id'] ?? 0)));
            }
        }

        return array(
            'ok'      => true,
            'message' => '홍보 가이드가 저장되었습니다.',
            'guide'   => $updated,
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_get_for_partner')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_get_for_partner($cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) $cp_id;
        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign) || (string) ($campaign['cp_status'] ?? '') !== LC_STATUS_ACTIVE) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide) || (string) ($guide['cpg_status'] ?? '') !== LC_CPG_STATUS_PUBLISHED) {
            return array('ok' => false, 'message' => '공개된 홍보 가이드가 없습니다.');
        }

        return array('ok' => true, 'message' => '', 'guide' => $guide);
    }
}

if (!function_exists('lc_campaign_promo_guide_allowed_image_meta')) {
    /**
     * @return array<string,array{mime:string,ext:string}>
     */
    function lc_campaign_promo_guide_allowed_image_meta()
    {
        return array(
            'image/jpeg' => array('mime' => 'image/jpeg', 'ext' => 'jpg'),
            'image/png'  => array('mime' => 'image/png', 'ext' => 'png'),
            'image/webp' => array('mime' => 'image/webp', 'ext' => 'webp'),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_detect_upload_mime')) {
    function lc_campaign_promo_guide_detect_upload_mime($tmp_path)
    {
        if (!is_string($tmp_path) || $tmp_path === '' || !is_file($tmp_path)) {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return strtolower($mime);
                }
            }
        }

        $image_info = @getimagesize($tmp_path);
        if (is_array($image_info) && !empty($image_info['mime'])) {
            return strtolower((string) $image_info['mime']);
        }

        return '';
    }
}

if (!function_exists('lc_campaign_promo_guide_validate_upload_extension')) {
    function lc_campaign_promo_guide_validate_upload_extension($original_name, $mime)
    {
        $allowed = lc_campaign_promo_guide_allowed_image_meta();
        if (!isset($allowed[$mime])) {
            return '';
        }

        $base = basename((string) $original_name);
        if ($base === '' || strpos($base, "\0") !== false) {
            return '';
        }

        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $valid_ext = array('jpg', 'jpeg', 'png', 'webp');
        if (!in_array($ext, $valid_ext, true)) {
            return '';
        }

        if ($mime === 'image/jpeg' && !in_array($ext, array('jpg', 'jpeg'), true)) {
            return '';
        }
        if ($mime === 'image/png' && $ext !== 'png') {
            return '';
        }
        if ($mime === 'image/webp' && $ext !== 'webp') {
            return '';
        }

        return $allowed[$mime]['ext'] === 'jpg' && $ext === 'jpeg' ? 'jpg' : $allowed[$mime]['ext'];
    }
}

if (!function_exists('lc_campaign_promo_guide_upload_asset')) {
    /**
     * @param array<string,mixed> $file $_FILES entry
     * @return array{ok:bool,message:string,asset?:array}
     */
    function lc_campaign_promo_guide_save_binary($mt_id, $cp_id, $binary, $mime = 'image/jpeg', $image_title = '', $original_name = 'ai-promo.jpg', $admin_override = false)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 먼저 생성해 주세요.');
        }

        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }

        $status = (string) ($guide['cpg_status'] ?? '');
        if (!$admin_override) {
            if ($status === LC_CPG_STATUS_REVIEW) {
                return array('ok' => false, 'message' => '검토 중인 가이드에는 이미지를 추가할 수 없습니다.');
            }
        }

        $cpg_id = (int) $guide['cpg_id'];
        $limits = lc_campaign_promo_guide_limits();
        if (lc_campaign_promo_guide_count_active_assets($cpg_id) >= $limits['images']) {
            return array('ok' => false, 'message' => '이미지는 최대 ' . $limits['images'] . '개까지 등록할 수 있습니다.');
        }

        $binary = (string) $binary;
        $max_bytes = lc_campaign_promo_guide_max_image_bytes();
        $size = strlen($binary);
        if ($size <= 0 || $size > $max_bytes) {
            return array('ok' => false, 'message' => '파일 크기가 허용 범위를 초과했습니다.');
        }

        if (@getimagesizefromstring($binary) === false) {
            return array('ok' => false, 'message' => '유효하지 않은 이미지 파일입니다.');
        }

        $mime = strtolower(trim((string) $mime));
        $ext = function_exists('lc_image_mime_to_ext') ? lc_image_mime_to_ext($mime) : '';
        if ($ext === '') {
            $ext = lc_campaign_promo_guide_validate_upload_extension($original_name, $mime);
        }
        if ($ext === '') {
            return array('ok' => false, 'message' => '허용되지 않은 이미지 형식입니다. (JPG, PNG, WEBP만 가능)');
        }

        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;
        $dir = lc_campaign_promo_guide_campaign_dir($mt_id, $cp_id);
        if ($dir === '') {
            return array('ok' => false, 'message' => '이미지 저장 경로를 준비하지 못했습니다.');
        }

        $stored = 'img_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $full = $dir . '/' . $stored;
        if (@file_put_contents($full, $binary, LOCK_EX) === false) {
            return array('ok' => false, 'message' => '이미지 저장에 실패했습니다.');
        }
        @chmod($full, 0644);

        $relative = 'linkconnect/campaign_promo_assets/' . $mt_id . '/' . $cp_id . '/' . $stored;
        $base_real = realpath(lc_campaign_promo_guide_storage_base_dir());
        $full_real = realpath($full);
        if ($base_real === false || $full_real === false || strpos($full_real, $base_real) !== 0) {
            @unlink($full);
            return array('ok' => false, 'message' => '이미지 저장 경로가 유효하지 않습니다.');
        }

        $sort = lc_campaign_promo_guide_count_active_assets($cpg_id);
        $table = lc_campaign_promo_guide_asset_table();
        $original = lc_sql_escape(lc_campaign_promo_guide_sanitize_text($original_name, 255));
        $stored_esc = lc_sql_escape($stored);
        $path_esc = lc_sql_escape($relative);
        $mime_esc = lc_sql_escape($mime !== '' ? $mime : 'image/jpeg');
        $title_esc = lc_sql_escape(lc_campaign_promo_guide_sanitize_text($image_title, 200));

        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            cpga_cpg_id = '{$cpg_id}',
            cpga_cp_id = '{$cp_id}',
            cpga_mt_id = '{$mt_id}',
            cpga_original_filename = '{$original}',
            cpga_stored_filename = '{$stored_esc}',
            cpga_file_path = '{$path_esc}',
            cpga_mime_type = '{$mime_esc}',
            cpga_file_size = '{$size}',
            cpga_image_title = '{$title_esc}',
            cpga_sort_order = '{$sort}',
            cpga_is_active = 1,
            cpga_created_at = NOW() ", false);

        if ($ok === false) {
            @unlink($full);
            return array('ok' => false, 'message' => '이미지 정보 저장에 실패했습니다.');
        }

        $cpga_id = lc_sql_insert_id();
        $asset = lc_campaign_promo_guide_asset_get_by_id($cpga_id);

        return array(
            'ok'      => true,
            'message' => '이미지가 업로드되었습니다.',
            'asset'   => is_array($asset) ? $asset : null,
        );
    }

    function lc_campaign_promo_guide_upload_asset($mt_id, $cp_id, array $file, $image_title = '', $admin_override = false)
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'message' => '업로드된 파일이 없습니다.');
        }

        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'message' => '파일 업로드에 실패했습니다.');
        }

        $mime = lc_campaign_promo_guide_detect_upload_mime($file['tmp_name']);
        $binary = @file_get_contents($file['tmp_name']);
        if ($binary === false || $binary === '') {
            return array('ok' => false, 'message' => '이미지 파일을 읽을 수 없습니다.');
        }

        return lc_campaign_promo_guide_save_binary(
            $mt_id,
            $cp_id,
            $binary,
            $mime !== '' ? $mime : 'image/jpeg',
            $image_title,
            isset($file['name']) ? (string) $file['name'] : 'upload.jpg',
            $admin_override
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_delete_asset')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_promo_guide_delete_asset($mt_id, $cpga_id, $admin_override = false)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $mt_id = (int) $mt_id;
        $cpga_id = (int) $cpga_id;
        $asset = lc_campaign_promo_guide_asset_get_by_id($cpga_id);
        if (!is_array($asset)) {
            return array('ok' => false, 'message' => '이미지를 찾을 수 없습니다.');
        }

        if ((int) ($asset['cpga_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '해당 이미지에 대한 권한이 없습니다.');
        }

        $guide = lc_campaign_promo_guide_get_by_id((int) ($asset['cpga_cpg_id'] ?? 0));
        if (is_array($guide) && !$admin_override) {
            $status = (string) ($guide['cpg_status'] ?? '');
            if ($status === LC_CPG_STATUS_REVIEW) {
                return array('ok' => false, 'message' => '현재 상태에서는 이미지를 삭제할 수 없습니다.');
            }
        }

        lc_campaign_promo_guide_remove_asset_file($asset);

        $table = lc_campaign_promo_guide_asset_table();
        lc_sql_query(" DELETE FROM `{$table}` WHERE cpga_id = '{$cpga_id}' AND cpga_mt_id = '{$mt_id}' LIMIT 1 ", false);

        return array('ok' => true, 'message' => '이미지가 삭제되었습니다.');
    }
}

if (!function_exists('lc_campaign_promo_guide_admin_delete_asset')) {
    function lc_campaign_promo_guide_admin_delete_asset($cpga_id)
    {
        $cpga_id = (int) $cpga_id;
        $asset = lc_campaign_promo_guide_asset_get_by_id($cpga_id);
        if (!is_array($asset)) {
            return array('ok' => false, 'message' => '이미지를 찾을 수 없습니다.');
        }

        lc_campaign_promo_guide_remove_asset_file($asset);
        $table = lc_campaign_promo_guide_asset_table();
        lc_sql_query(" DELETE FROM `{$table}` WHERE cpga_id = '{$cpga_id}' LIMIT 1 ", false);

        return array('ok' => true, 'message' => '이미지가 삭제되었습니다.');
    }
}

if (!function_exists('lc_campaign_promo_guide_remove_asset_file')) {
    function lc_campaign_promo_guide_remove_asset_file(array $asset)
    {
        $full = lc_campaign_promo_guide_asset_full_path($asset);
        if ($full !== '' && is_file($full)) {
            @unlink($full);
        }
    }
}

if (!function_exists('lc_campaign_promo_guide_asset_full_path')) {
    function lc_campaign_promo_guide_asset_full_path(array $asset)
    {
        $stored = basename((string) ($asset['cpga_stored_filename'] ?? ''));
        $mt_id = (int) ($asset['cpga_mt_id'] ?? 0);
        $cp_id = (int) ($asset['cpga_cp_id'] ?? 0);
        if ($stored === '') {
            return '';
        }

        $candidates = array();

        // 1) mt/cp 디렉터리
        if ($mt_id > 0 && $cp_id > 0) {
            $base = lc_campaign_promo_guide_storage_base_dir();
            $candidates[] = $base . '/' . $mt_id . '/' . $cp_id . '/' . $stored;
        }

        // 2) DB에 저장된 상대 경로 (소유권 변경 전 경로 포함)
        $rel = trim((string) ($asset['cpga_file_path'] ?? ''));
        if ($rel !== '') {
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            if (defined('G5_DATA_PATH') && G5_DATA_PATH !== '') {
                $candidates[] = rtrim((string) G5_DATA_PATH, '/') . '/' . $rel;
            }
            $candidates[] = LC_PLUGIN_PATH . '/data/' . $rel;
            // linkconnect/campaign_promo_assets/... 형태면 storage base 기준
            if (strpos($rel, 'linkconnect/campaign_promo_assets/') === 0) {
                $suffix = substr($rel, strlen('linkconnect/campaign_promo_assets/'));
                $candidates[] = lc_campaign_promo_guide_storage_base_dir() . '/' . $suffix;
            }
        }

        $base_real = realpath(lc_campaign_promo_guide_storage_base_dir());
        foreach ($candidates as $full) {
            if (!is_string($full) || $full === '' || !is_file($full)) {
                continue;
            }
            $real_dir = realpath(dirname($full));
            $real_full = ($real_dir !== false) ? ($real_dir . '/' . basename($full)) : false;
            if ($base_real === false || $real_dir === false || $real_full === false) {
                continue;
            }
            if (strpos($real_full, $base_real) !== 0 && !(defined('G5_DATA_PATH') && G5_DATA_PATH !== '' && strpos($real_full, realpath((string) G5_DATA_PATH) ?: '___') === 0)) {
                // storage base 밖이면 스킵 (단 G5_DATA_PATH 하위는 허용)
                $g5_data = (defined('G5_DATA_PATH') && G5_DATA_PATH !== '') ? realpath((string) G5_DATA_PATH) : false;
                if ($g5_data === false || strpos($real_full, $g5_data) !== 0) {
                    continue;
                }
            }

            return is_file($full) ? $full : '';
        }

        // 3) 파일명으로 storage 트리 탐색 (소유권 변경으로 경로가 어긋난 경우)
        if ($base_real !== false && $stored !== '') {
            $found = lc_campaign_promo_guide_find_stored_file($base_real, $stored);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }
}

if (!function_exists('lc_campaign_promo_guide_find_stored_file')) {
    function lc_campaign_promo_guide_find_stored_file($base_dir, $stored)
    {
        $stored = basename((string) $stored);
        $base_dir = (string) $base_dir;
        if ($stored === '' || $base_dir === '' || !is_dir($base_dir)) {
            return '';
        }

        // mt_id/cp_id/filename 2단계만 탐색
        $mt_dirs = @scandir($base_dir);
        if (!is_array($mt_dirs)) {
            return '';
        }
        foreach ($mt_dirs as $mt) {
            if ($mt === '.' || $mt === '..') {
                continue;
            }
            $mt_path = $base_dir . '/' . $mt;
            if (!is_dir($mt_path)) {
                continue;
            }
            $cp_dirs = @scandir($mt_path);
            if (!is_array($cp_dirs)) {
                continue;
            }
            foreach ($cp_dirs as $cp) {
                if ($cp === '.' || $cp === '..') {
                    continue;
                }
                $full = $mt_path . '/' . $cp . '/' . $stored;
                if (is_file($full)) {
                    return $full;
                }
            }
        }

        return '';
    }
}

if (!function_exists('lc_campaign_promo_guide_sort_assets')) {
    /**
     * @param array<int,int> $asset_ids ordered list
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_promo_guide_sort_assets($mt_id, $cp_id, array $asset_ids, $admin_override = false)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 찾을 수 없습니다.');
        }

        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }

        $status = (string) ($guide['cpg_status'] ?? '');
        if (!$admin_override && $status === LC_CPG_STATUS_REVIEW) {
            return array('ok' => false, 'message' => '현재 상태에서는 이미지 순서를 변경할 수 없습니다.');
        }

        $cpg_id = (int) $guide['cpg_id'];
        $table = lc_campaign_promo_guide_asset_table();
        $order = 0;
        foreach ($asset_ids as $raw_id) {
            $cpga_id = (int) $raw_id;
            if ($cpga_id <= 0) {
                continue;
            }
            $asset = lc_campaign_promo_guide_asset_get_by_id($cpga_id);
            if (!is_array($asset)) {
                continue;
            }
            if ((int) ($asset['cpga_cpg_id'] ?? 0) !== $cpg_id || (int) ($asset['cpga_mt_id'] ?? 0) !== (int) $mt_id) {
                return array('ok' => false, 'message' => '이미지 소유권이 일치하지 않습니다.');
            }
            lc_sql_query(" UPDATE `{$table}` SET cpga_sort_order = '{$order}' WHERE cpga_id = '{$cpga_id}' AND cpga_mt_id = '" . (int) $mt_id . "' LIMIT 1 ", false);
            $order++;
        }

        return array('ok' => true, 'message' => '이미지 순서가 저장되었습니다.');
    }
}

if (!function_exists('lc_campaign_promo_guide_can_merchant_view_asset')) {
    function lc_campaign_promo_guide_can_merchant_view_asset($mt_id, array $asset)
    {
        return (int) ($asset['cpga_mt_id'] ?? 0) === (int) $mt_id
            && (int) ($asset['cpga_is_active'] ?? 0) === 1;
    }
}

if (!function_exists('lc_campaign_promo_guide_can_partner_view_asset')) {
    function lc_campaign_promo_guide_can_partner_view_asset(array $asset)
    {
        $cp_id = (int) ($asset['cpga_cp_id'] ?? 0);
        $guide_result = lc_campaign_promo_guide_get_for_partner($cp_id);
        if (empty($guide_result['ok']) || !is_array($guide_result['guide'])) {
            return false;
        }

        return (int) ($asset['cpga_cpg_id'] ?? 0) === (int) ($guide_result['guide']['cpg_id'] ?? 0)
            && (int) ($asset['cpga_is_active'] ?? 0) === 1;
    }
}

if (!function_exists('lc_campaign_promo_guide_serve_asset')) {
    /**
     * @return array{ok:bool,message:string,path?:string,mime?:string}
     */
    function lc_campaign_promo_guide_serve_asset(array $asset)
    {
        $path = lc_campaign_promo_guide_asset_full_path($asset);
        if ($path === '' || !is_file($path)) {
            return array('ok' => false, 'message' => '이미지 파일을 찾을 수 없습니다.');
        }

        return array(
            'ok'    => true,
            'message' => '',
            'path'  => $path,
            'mime'  => (string) ($asset['cpga_mime_type'] ?? 'application/octet-stream'),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_api_csrf_ok')) {
    function lc_campaign_promo_guide_api_csrf_ok()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';

        $check = static function ($url) use ($host) {
            if ($url === '' || $host === '') {
                return false;
            }
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['host'])) {
                return false;
            }

            return strtolower((string) $parts['host']) === $host;
        };

        if ($origin !== '') {
            return $check($origin);
        }
        if ($referer !== '') {
            return $check($referer);
        }

        return false;
    }
}

if (!function_exists('lc_campaign_promo_guide_csrf_token')) {
    function lc_campaign_promo_guide_csrf_token()
    {
        if (!isset($_SESSION['lc_campaign_promo_guide_csrf']) || !is_string($_SESSION['lc_campaign_promo_guide_csrf']) || $_SESSION['lc_campaign_promo_guide_csrf'] === '') {
            $_SESSION['lc_campaign_promo_guide_csrf'] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION['lc_campaign_promo_guide_csrf'];
    }
}

if (!function_exists('lc_campaign_promo_guide_csrf_verify')) {
    function lc_campaign_promo_guide_csrf_verify($token)
    {
        $expected = isset($_SESSION['lc_campaign_promo_guide_csrf']) ? (string) $_SESSION['lc_campaign_promo_guide_csrf'] : '';
        if ($expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }
}

if (!function_exists('lc_campaign_promo_guide_api_require_post_csrf')) {
    function lc_campaign_promo_guide_api_require_post_csrf(array $body)
    {
        if (!lc_campaign_promo_guide_api_csrf_ok()) {
            lc_api_error('CSRF 검증에 실패했습니다.', 'CSRF', 403);
        }

        $token = isset($body['csrfToken']) ? (string) $body['csrfToken'] : '';
        if ($token === '' && isset($_POST['csrfToken'])) {
            $token = (string) $_POST['csrfToken'];
        }
        if (!lc_campaign_promo_guide_csrf_verify($token)) {
            lc_api_error('보안 토큰이 유효하지 않습니다. 페이지를 새로고침해 주세요.', 'CSRF_TOKEN', 403);
        }
    }
}

if (!function_exists('lc_campaign_promo_guide_skip_review')) {
    function lc_campaign_promo_guide_skip_review()
    {
        if (!function_exists('lc_settings_get')) {
            return false;
        }

        return lc_settings_get('promoGuideSkipReview', '0') === '1';
    }
}

if (!function_exists('lc_campaign_promo_guide_merchant_publish')) {
    /**
     * @return array{ok:bool,message:string,guide?:array}
     */
    function lc_campaign_promo_guide_merchant_publish($mt_id, $cp_id)
    {
        if (!lc_campaign_promo_guide_skip_review()) {
            return array('ok' => false, 'message' => '관리자 검토 후 공개됩니다.');
        }

        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array('ok' => false, 'message' => '홍보 가이드를 먼저 생성해 주세요.');
        }

        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }

        return lc_campaign_promo_guide_admin_update_status((int) $guide['cpg_id'], LC_CPG_STATUS_PUBLISHED);
    }
}

if (!function_exists('lc_campaign_promo_guide_update_asset_title')) {
    function lc_campaign_promo_guide_update_asset_title($mt_id, $cpga_id, $title, $admin_override = false)
    {
        $cpga_id = (int) $cpga_id;
        $asset = lc_campaign_promo_guide_asset_get_by_id($cpga_id);
        if (!is_array($asset)) {
            return array('ok' => false, 'message' => '이미지를 찾을 수 없습니다.');
        }

        if ((int) ($asset['cpga_mt_id'] ?? 0) !== (int) $mt_id) {
            return array('ok' => false, 'message' => '해당 이미지에 대한 권한이 없습니다.');
        }

        if (!$admin_override) {
            $guide = lc_campaign_promo_guide_get_by_id((int) ($asset['cpga_cpg_id'] ?? 0));
            if (is_array($guide)) {
                $status = (string) ($guide['cpg_status'] ?? '');
                if ($status === LC_CPG_STATUS_REVIEW || $status === LC_CPG_STATUS_PUBLISHED) {
                    return array('ok' => false, 'message' => '현재 상태에서는 이미지 제목을 수정할 수 없습니다.');
                }
            }
        }

        $table = lc_campaign_promo_guide_asset_table();
        $title_esc = lc_sql_escape(lc_campaign_promo_guide_sanitize_text($title, 200));
        lc_sql_query(" UPDATE `{$table}` SET cpga_image_title = '{$title_esc}' WHERE cpga_id = '{$cpga_id}' AND cpga_mt_id = '" . (int) $mt_id . "' LIMIT 1 ", false);

        return array(
            'ok'      => true,
            'message' => '이미지 제목이 저장되었습니다.',
            'asset'   => lc_campaign_promo_guide_asset_get_by_id($cpga_id),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_merchant_view')) {
    /**
     * @return array{ok:bool,message:string,data?:array}
     */
    function lc_campaign_promo_guide_merchant_view($mt_id, $cp_id)
    {
        $owner = lc_campaign_promo_guide_assert_campaign_owner($mt_id, $cp_id);
        if (empty($owner['ok'])) {
            return array('ok' => false, 'message' => $owner['message']);
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        $campaign_name = is_array($campaign) ? (string) ($campaign['cp_name'] ?? '') : '';

        $guide = lc_campaign_promo_guide_get_by_cp_id($cp_id);
        if (!is_array($guide)) {
            return array(
                'ok'      => true,
                'message' => '',
                'data'    => array(
                    'exists'        => false,
                    'campaignId'    => (int) $cp_id,
                    'campaignName'  => $campaign_name,
                    'csrfToken'     => lc_campaign_promo_guide_csrf_token(),
                    'limits'        => lc_campaign_promo_guide_limits(),
                    'maxImageBytes' => lc_campaign_promo_guide_max_image_bytes(),
                    'skipReview'    => lc_campaign_promo_guide_skip_review(),
                ),
            );
        }

        $guide = lc_campaign_promo_guide_heal_owner($mt_id, $guide);
        $assert = lc_campaign_promo_guide_assert_owner($mt_id, $guide);
        if (empty($assert['ok'])) {
            return array('ok' => false, 'message' => $assert['message']);
        }
        $guide = $assert['guide'];

        $api = lc_campaign_promo_guide_to_api($guide, null, false);
        $api['exists'] = true;
        $api['campaignName'] = $campaign_name;
        $api['csrfToken'] = lc_campaign_promo_guide_csrf_token();
        $api['skipReview'] = lc_campaign_promo_guide_skip_review();

        return array('ok' => true, 'message' => '', 'data' => $api);
    }
}

if (!function_exists('lc_campaign_promo_guide_published_cp_id_set')) {
    /**
     * @param array<int,int> $cp_ids
     * @return array<int,bool>
     */
    function lc_campaign_promo_guide_published_cp_id_set(array $cp_ids)
    {
        if (!lc_db_installed() || count($cp_ids) === 0) {
            return array();
        }

        $ids = array();
        foreach ($cp_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if (count($ids) === 0) {
            return array();
        }

        $in = implode(',', array_map('intval', array_values($ids)));
        $table = lc_campaign_promo_guide_table();
        $status = lc_sql_escape(LC_CPG_STATUS_PUBLISHED);
        $set = array();
        $result = lc_sql_query(" SELECT cpg_cp_id FROM `{$table}` WHERE cpg_cp_id IN ({$in}) AND cpg_status = '{$status}' ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $set[(int) $row['cpg_cp_id']] = true;
            }
        }

        return $set;
    }
}

if (!function_exists('lc_campaign_promo_guide_confirmation_get')) {
    function lc_campaign_promo_guide_confirmation_get($pt_id, $cp_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        $table = lc_campaign_promo_guide_confirmation_table();

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cpgc_pt_id = '{$pt_id}' AND cpgc_cp_id = '{$cp_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_campaign_promo_guide_confirmation_is_current')) {
    function lc_campaign_promo_guide_confirmation_is_current(array $confirmation, array $guide)
    {
        if (empty($confirmation) || empty($guide)) {
            return false;
        }

        $stored = isset($confirmation['cpgc_guide_updated_at']) ? (string) $confirmation['cpgc_guide_updated_at'] : '';
        $current = isset($guide['cpg_updated_at']) ? (string) $guide['cpg_updated_at'] : '';
        if ($stored === '' || $current === '') {
            return false;
        }

        return $stored === $current;
    }
}

if (!function_exists('lc_campaign_promo_guide_confirmation_to_api')) {
    function lc_campaign_promo_guide_confirmation_to_api($pt_id, $cp_id, array $guide)
    {
        $row = lc_campaign_promo_guide_confirmation_get($pt_id, $cp_id);
        $current = is_array($row) && lc_campaign_promo_guide_confirmation_is_current($row, $guide);

        return array(
            'confirmed'      => $current,
            'confirmedAt'    => $current && is_array($row) ? (string) ($row['cpgc_confirmed_at'] ?? '') : '',
            'guideUpdatedAt' => (string) ($guide['cpg_updated_at'] ?? ''),
            'guideId'        => (int) ($guide['cpg_id'] ?? 0),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_partner_confirm')) {
    /**
     * @return array{ok:bool,message:string,confirmation?:array}
     */
    function lc_campaign_promo_guide_partner_confirm($pt_id, $cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        lc_campaign_promo_guide_db_ensure_schema();

        $result = lc_campaign_promo_guide_get_for_partner($cp_id);
        if (empty($result['ok']) || !is_array($result['guide'])) {
            return array('ok' => false, 'message' => $result['message']);
        }

        $guide = $result['guide'];
        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        $cpg_id = (int) ($guide['cpg_id'] ?? 0);
        $guide_updated = lc_sql_escape((string) ($guide['cpg_updated_at'] ?? ''));

        $table = lc_campaign_promo_guide_confirmation_table();
        $existing = lc_campaign_promo_guide_confirmation_get($pt_id, $cp_id);

        if (is_array($existing)) {
            $cpgc_id = (int) $existing['cpgc_id'];
            $ok = lc_sql_query(" UPDATE `{$table}` SET
                cpgc_cpg_id = '{$cpg_id}',
                cpgc_guide_updated_at = '{$guide_updated}',
                cpgc_confirmed_at = NOW()
                WHERE cpgc_id = '{$cpgc_id}' AND cpgc_pt_id = '{$pt_id}' AND cpgc_cp_id = '{$cp_id}' LIMIT 1 ", false);
        } else {
            $ok = lc_sql_query(" INSERT INTO `{$table}` SET
                cpgc_pt_id = '{$pt_id}',
                cpgc_cp_id = '{$cp_id}',
                cpgc_cpg_id = '{$cpg_id}',
                cpgc_guide_updated_at = '{$guide_updated}',
                cpgc_confirmed_at = NOW() ", false);
        }

        if ($ok === false) {
            return array('ok' => false, 'message' => '확인 상태 저장에 실패했습니다.');
        }

        return array(
            'ok'           => true,
            'message'      => '홍보 가이드 확인이 완료되었습니다.',
            'confirmation' => lc_campaign_promo_guide_confirmation_to_api($pt_id, $cp_id, $guide),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_partner_detail_for_api')) {
    /**
     * @return array{ok:bool,message:string,data?:array}
     */
    function lc_campaign_promo_guide_partner_detail_for_api($pt_id, $cp_id)
    {
        $result = lc_campaign_promo_guide_get_for_partner($cp_id);
        if (empty($result['ok']) || !is_array($result['guide'])) {
            return array('ok' => false, 'message' => $result['message']);
        }

        $guide = $result['guide'];
        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        $guide_api = lc_campaign_promo_guide_to_api($guide);
        unset($guide_api['limits'], $guide_api['maxImageBytes']);

        if (!empty($guide_api['images']) && is_array($guide_api['images'])) {
            foreach ($guide_api['images'] as $idx => $img) {
                $asset_id = (int) ($img['id'] ?? 0);
                $guide_api['images'][$idx]['downloadUrl'] = LC_PLUGIN_URL . '/partner/api/campaign-guide-asset.php?assetId=' . $asset_id;
                unset($guide_api['images'][$idx]['adminDownloadUrl'], $guide_api['images'][$idx]['partnerDownloadUrl']);
            }
        }

        return array(
            'ok'      => true,
            'message' => '',
            'data'    => array(
                'campaign'     => function_exists('lc_campaign_to_api') ? lc_campaign_to_api($campaign) : array(),
                'guide'          => $guide_api,
                'confirmation' => lc_campaign_promo_guide_confirmation_to_api((int) $pt_id, (int) $cp_id, $guide),
            ),
        );
    }
}

if (!function_exists('lc_campaign_promo_guide_partner_is_confirmed')) {
    function lc_campaign_promo_guide_partner_is_confirmed($pt_id, $cp_id)
    {
        $result = lc_campaign_promo_guide_get_for_partner($cp_id);
        if (empty($result['ok']) || !is_array($result['guide'])) {
            return true;
        }

        $conf = lc_campaign_promo_guide_confirmation_to_api((int) $pt_id, (int) $cp_id, $result['guide']);

        return !empty($conf['confirmed']);
    }
}

