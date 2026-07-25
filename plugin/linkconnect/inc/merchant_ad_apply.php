<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * 광고주 캠페인 광고등록 신청서 (계약 체결 후)
 */

if (!function_exists('lc_merchant_ad_apply_table')) {
    function lc_merchant_ad_apply_table()
    {
        return lc_table('merchant_ad_applications');
    }
}

if (!function_exists('lc_merchant_ad_apply_asset_table')) {
    function lc_merchant_ad_apply_asset_table()
    {
        return lc_table('merchant_ad_application_assets');
    }
}

if (!function_exists('lc_merchant_ad_apply_create_table_sql')) {
    function lc_merchant_ad_apply_create_table_sql()
    {
        $table = lc_merchant_ad_apply_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
            `maa_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `maa_mt_id` int unsigned NOT NULL DEFAULT 0,
            `maa_status` varchar(20) NOT NULL DEFAULT 'draft',
            `maa_campaign_title` varchar(200) NOT NULL DEFAULT '',
            `maa_landing_url` varchar(500) NOT NULL DEFAULT '',
            `maa_intro` mediumtext,
            `maa_selling_points` mediumtext,
            `maa_allowed_channels` text,
            `maa_forbidden_channels` text,
            `maa_recommended_keywords` text,
            `maa_forbidden_keywords` text,
            `maa_precautions` mediumtext,
            `maa_banner_path` varchar(500) NOT NULL DEFAULT '',
            `maa_banner_name` varchar(255) NOT NULL DEFAULT '',
            `maa_admin_note` varchar(500) NOT NULL DEFAULT '',
            `maa_submitted_at` datetime DEFAULT NULL,
            `maa_reviewed_at` datetime DEFAULT NULL,
            `maa_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `maa_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`maa_id`),
            KEY `idx_maa_mt_id` (`maa_mt_id`),
            KEY `idx_maa_status` (`maa_status`),
            KEY `idx_maa_submitted_at` (`maa_submitted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_merchant_ad_apply_asset_create_table_sql')) {
    function lc_merchant_ad_apply_asset_create_table_sql()
    {
        $table = lc_merchant_ad_apply_asset_table();

        return "CREATE TABLE IF NOT EXISTS `{$table}` (
            `maaa_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `maaa_maa_id` bigint unsigned NOT NULL DEFAULT 0,
            `maaa_mt_id` int unsigned NOT NULL DEFAULT 0,
            `maaa_kind` varchar(30) NOT NULL DEFAULT 'extra',
            `maaa_path` varchar(500) NOT NULL DEFAULT '',
            `maaa_filename` varchar(255) NOT NULL DEFAULT '',
            `maaa_mime` varchar(100) NOT NULL DEFAULT '',
            `maaa_size` int unsigned NOT NULL DEFAULT 0,
            `maaa_sort` int unsigned NOT NULL DEFAULT 0,
            `maaa_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`maaa_id`),
            KEY `idx_maaa_maa_id` (`maaa_maa_id`),
            KEY `idx_maaa_mt_id` (`maaa_mt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('lc_merchant_ad_apply_db_ensure_schema')) {
    function lc_merchant_ad_apply_db_ensure_schema()
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB 미설치');
        }

        $table = lc_merchant_ad_apply_table();
        if (!lc_db_table_exists($table)) {
            if (lc_sql_query(lc_merchant_ad_apply_create_table_sql(), false) === false) {
                return array('ok' => false, 'message' => 'merchant_ad_applications 생성 실패: ' . lc_sql_error());
            }
        }

        $assets = lc_merchant_ad_apply_asset_table();
        if (!lc_db_table_exists($assets)) {
            if (lc_sql_query(lc_merchant_ad_apply_asset_create_table_sql(), false) === false) {
                return array('ok' => false, 'message' => 'merchant_ad_application_assets 생성 실패: ' . lc_sql_error());
            }
        }

        return array('ok' => true, 'message' => 'ok');
    }
}

if (!function_exists('lc_merchant_ad_apply_storage_dir')) {
    function lc_merchant_ad_apply_storage_dir($mt_id, $maa_id = 0)
    {
        $mt_id = (int) $mt_id;
        $maa_id = (int) $maa_id;
        $base = LC_PLUGIN_PATH . '/data/ad_applications/' . $mt_id;
        if ($maa_id > 0) {
            $base .= '/' . $maa_id;
        }
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }
        $ht = $base . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\n");
        }

        return $base;
    }
}

if (!function_exists('lc_merchant_ad_apply_json_encode')) {
    function lc_merchant_ad_apply_json_encode($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $json === false ? '[]' : $json;
    }
}

if (!function_exists('lc_merchant_ad_apply_json_decode_list')) {
    function lc_merchant_ad_apply_json_decode_list($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        $out = array();
        foreach ($decoded as $item) {
            if (is_string($item) || is_numeric($item)) {
                $v = trim((string) $item);
                if ($v !== '') {
                    $out[] = $v;
                }
            }
        }

        return array_values(array_unique($out));
    }
}

if (!function_exists('lc_merchant_ad_apply_sanitize_text')) {
    function lc_merchant_ad_apply_sanitize_text($value, $max = 20000)
    {
        $value = trim(strip_tags((string) $value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }

        return substr($value, 0, $max);
    }
}

if (!function_exists('lc_merchant_ad_apply_get')) {
    function lc_merchant_ad_apply_get($maa_id)
    {
        $maa_id = (int) $maa_id;
        if ($maa_id <= 0) {
            return null;
        }
        $table = lc_merchant_ad_apply_table();

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maa_id = '{$maa_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_ad_apply_get_latest_for_merchant')) {
    function lc_merchant_ad_apply_get_latest_for_merchant($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return null;
        }
        $table = lc_merchant_ad_apply_table();

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maa_mt_id = '{$mt_id}' ORDER BY maa_id DESC LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_ad_apply_list_assets')) {
    function lc_merchant_ad_apply_list_assets($maa_id)
    {
        $maa_id = (int) $maa_id;
        if ($maa_id <= 0) {
            return array();
        }
        $table = lc_merchant_ad_apply_asset_table();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE maaa_maa_id = '{$maa_id}' ORDER BY maaa_sort ASC, maaa_id ASC ", false);
        $rows = array();
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_ad_apply_ensure_draft')) {
    function lc_merchant_ad_apply_ensure_draft($mt_id)
    {
        $mt_id = (int) $mt_id;
        $existing = lc_merchant_ad_apply_get_latest_for_merchant($mt_id);
        if (is_array($existing)) {
            $status = (string) ($existing['maa_status'] ?? '');
            // 제출/검수 완료본은 유지하고, 수정은 draft 새로 만들지 않고 같은 건 편집 (제출 전 draft만)
            if ($status === 'draft' || $status === 'revision') {
                return $existing;
            }
            if ($status === 'submitted') {
                return $existing;
            }
        }

        $table = lc_merchant_ad_apply_table();
        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            maa_mt_id = '{$mt_id}',
            maa_status = 'draft',
            maa_allowed_channels = '" . lc_sql_escape(lc_merchant_ad_apply_json_encode(array('seo_blog', 'naver_cafe', 'sns', 'youtube'))) . "',
            maa_forbidden_channels = '" . lc_sql_escape(lc_merchant_ad_apply_json_encode(array('brand_sa', 'reward_app', 'macro', 'fake_review'))) . "',
            maa_created_at = NOW(),
            maa_updated_at = NOW() ", false);

        if ($ok === false) {
            return null;
        }

        return lc_merchant_ad_apply_get_latest_for_merchant($mt_id);
    }
}

if (!function_exists('lc_merchant_ad_apply_asset_to_api')) {
    function lc_merchant_ad_apply_asset_to_api(array $row)
    {
        $id = (int) ($row['maaa_id'] ?? 0);

        return array(
            'id'       => $id,
            'kind'     => (string) ($row['maaa_kind'] ?? 'extra'),
            'filename' => (string) ($row['maaa_filename'] ?? ''),
            'mime'     => (string) ($row['maaa_mime'] ?? ''),
            'size'     => (int) ($row['maaa_size'] ?? 0),
            'url'      => $id > 0 ? ('ad-apply-asset.php?assetId=' . $id) : '',
        );
    }
}

if (!function_exists('lc_merchant_ad_apply_to_api')) {
    function lc_merchant_ad_apply_to_api(array $row, $with_assets = true)
    {
        $maa_id = (int) ($row['maa_id'] ?? 0);
        $assets = array();
        if ($with_assets) {
            foreach (lc_merchant_ad_apply_list_assets($maa_id) as $asset) {
                $assets[] = lc_merchant_ad_apply_asset_to_api($asset);
            }
        }

        $banner_path = trim((string) ($row['maa_banner_path'] ?? ''));

        return array(
            'id'                   => $maa_id,
            'mtId'                 => (int) ($row['maa_mt_id'] ?? 0),
            'status'               => (string) ($row['maa_status'] ?? 'draft'),
            'campaignTitle'        => (string) ($row['maa_campaign_title'] ?? ''),
            'landingUrl'           => (string) ($row['maa_landing_url'] ?? ''),
            'intro'                => (string) ($row['maa_intro'] ?? ''),
            'sellingPoints'        => (string) ($row['maa_selling_points'] ?? ''),
            'allowedChannels'      => lc_merchant_ad_apply_json_decode_list($row['maa_allowed_channels'] ?? ''),
            'forbiddenChannels'    => lc_merchant_ad_apply_json_decode_list($row['maa_forbidden_channels'] ?? ''),
            'recommendedKeywords'  => (string) ($row['maa_recommended_keywords'] ?? ''),
            'forbiddenKeywords'    => (string) ($row['maa_forbidden_keywords'] ?? ''),
            'precautions'          => (string) ($row['maa_precautions'] ?? ''),
            'bannerName'           => (string) ($row['maa_banner_name'] ?? ''),
            'hasBanner'            => $banner_path !== '',
            'bannerUrl'            => $banner_path !== '' && $maa_id > 0 ? ('ad-apply-asset.php?maaId=' . $maa_id . '&kind=banner') : '',
            'adminNote'            => (string) ($row['maa_admin_note'] ?? ''),
            'submittedAt'          => (string) ($row['maa_submitted_at'] ?? ''),
            'reviewedAt'           => (string) ($row['maa_reviewed_at'] ?? ''),
            'createdAt'            => (string) ($row['maa_created_at'] ?? ''),
            'updatedAt'            => (string) ($row['maa_updated_at'] ?? ''),
            'assets'               => $assets,
        );
    }
}

if (!function_exists('lc_merchant_ad_apply_save')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string,row?:array<string,mixed>|null}
     */
    function lc_merchant_ad_apply_save($mt_id, $maa_id, array $payload, $submit = false)
    {
        $mt_id = (int) $mt_id;
        $maa_id = (int) $maa_id;
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row) || (int) ($row['maa_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '신청서를 찾을 수 없습니다.');
        }

        $status = (string) ($row['maa_status'] ?? '');
        if ($status === 'accepted') {
            return array('ok' => false, 'message' => '이미 승인된 신청서는 수정할 수 없습니다.');
        }

        $title = lc_merchant_ad_apply_sanitize_text($payload['campaignTitle'] ?? '', 200);
        $url = lc_merchant_ad_apply_sanitize_text($payload['landingUrl'] ?? '', 500);
        $intro = lc_merchant_ad_apply_sanitize_text($payload['intro'] ?? '', 20000);
        $selling = lc_merchant_ad_apply_sanitize_text($payload['sellingPoints'] ?? '', 20000);
        $rec_kw = lc_merchant_ad_apply_sanitize_text($payload['recommendedKeywords'] ?? '', 2000);
        $for_kw = lc_merchant_ad_apply_sanitize_text($payload['forbiddenKeywords'] ?? '', 2000);
        $precautions = lc_merchant_ad_apply_sanitize_text($payload['precautions'] ?? '', 20000);

        $allowed = isset($payload['allowedChannels']) && is_array($payload['allowedChannels'])
            ? array_values(array_filter(array_map('strval', $payload['allowedChannels'])))
            : lc_merchant_ad_apply_json_decode_list($row['maa_allowed_channels'] ?? '');
        $forbidden = isset($payload['forbiddenChannels']) && is_array($payload['forbiddenChannels'])
            ? array_values(array_filter(array_map('strval', $payload['forbiddenChannels'])))
            : lc_merchant_ad_apply_json_decode_list($row['maa_forbidden_channels'] ?? '');

        if ($submit) {
            if ($title === '' || $url === '') {
                return array('ok' => false, 'message' => '캠페인 제목과 랜딩페이지 URL은 필수입니다.');
            }
            if ($intro === '' || $selling === '') {
                return array('ok' => false, 'message' => '소개글과 셀링 포인트를 입력해 주세요.');
            }
        }

        $new_status = $submit ? 'submitted' : (($status === 'submitted') ? 'submitted' : 'draft');
        if ($status === 'revision' && !$submit) {
            $new_status = 'revision';
        }

        $table = lc_merchant_ad_apply_table();
        $set_submitted = $submit ? ", maa_submitted_at = NOW()" : '';
        $ok = lc_sql_query(" UPDATE `{$table}` SET
            maa_status = '" . lc_sql_escape($new_status) . "',
            maa_campaign_title = '" . lc_sql_escape($title) . "',
            maa_landing_url = '" . lc_sql_escape($url) . "',
            maa_intro = '" . lc_sql_escape($intro) . "',
            maa_selling_points = '" . lc_sql_escape($selling) . "',
            maa_allowed_channels = '" . lc_sql_escape(lc_merchant_ad_apply_json_encode($allowed)) . "',
            maa_forbidden_channels = '" . lc_sql_escape(lc_merchant_ad_apply_json_encode($forbidden)) . "',
            maa_recommended_keywords = '" . lc_sql_escape($rec_kw) . "',
            maa_forbidden_keywords = '" . lc_sql_escape($for_kw) . "',
            maa_precautions = '" . lc_sql_escape($precautions) . "',
            maa_updated_at = NOW()
            {$set_submitted}
            WHERE maa_id = '{$maa_id}' AND maa_mt_id = '{$mt_id}' ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '저장에 실패했습니다.');
        }

        return array(
            'ok'      => true,
            'message' => $submit ? '광고등록 신청서를 제출했습니다.' : '임시 저장되었습니다.',
            'row'     => lc_merchant_ad_apply_get($maa_id),
        );
    }
}

if (!function_exists('lc_merchant_ad_apply_save_banner')) {
    function lc_merchant_ad_apply_save_banner($mt_id, $maa_id, array $file)
    {
        $mt_id = (int) $mt_id;
        $maa_id = (int) $maa_id;
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row) || (int) ($row['maa_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '신청서를 찾을 수 없습니다.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'message' => '배너 파일이 없습니다.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 20 * 1024 * 1024) {
            return array('ok' => false, 'message' => '배너 파일은 20MB 이하만 가능합니다.');
        }

        $name = (string) ($file['name'] ?? 'banner');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
        if (!in_array($ext, $allowed_ext, true)) {
            return array('ok' => false, 'message' => '배너는 JPG/PNG/WEBP만 가능합니다.');
        }

        $dir = lc_merchant_ad_apply_storage_dir($mt_id, $maa_id);
        $filename = 'banner_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return array('ok' => false, 'message' => '배너 업로드에 실패했습니다.');
        }

        $rel = 'data/ad_applications/' . $mt_id . '/' . $maa_id . '/' . $filename;
        $table = lc_merchant_ad_apply_table();
        lc_sql_query(" UPDATE `{$table}` SET
            maa_banner_path = '" . lc_sql_escape($rel) . "',
            maa_banner_name = '" . lc_sql_escape($name) . "',
            maa_updated_at = NOW()
            WHERE maa_id = '{$maa_id}' AND maa_mt_id = '{$mt_id}' ", false);

        return array('ok' => true, 'message' => '배너가 업로드되었습니다.', 'path' => $rel, 'filename' => $name);
    }
}

if (!function_exists('lc_merchant_ad_apply_save_extra_asset')) {
    function lc_merchant_ad_apply_save_extra_asset($mt_id, $maa_id, array $file)
    {
        $mt_id = (int) $mt_id;
        $maa_id = (int) $maa_id;
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row) || (int) ($row['maa_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '신청서를 찾을 수 없습니다.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'message' => '파일이 없습니다.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 100 * 1024 * 1024) {
            return array('ok' => false, 'message' => '파일은 100MB 이하만 가능합니다.');
        }

        $existing = lc_merchant_ad_apply_list_assets($maa_id);
        if (count($existing) >= 20) {
            return array('ok' => false, 'message' => '추가 자료는 최대 20개까지 업로드할 수 있습니다.');
        }

        $name = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'ppt', 'pptx', 'zip', 'doc', 'docx');
        if (!in_array($ext, $allowed_ext, true)) {
            return array('ok' => false, 'message' => '허용되지 않는 파일 형식입니다.');
        }

        $dir = lc_merchant_ad_apply_storage_dir($mt_id, $maa_id);
        $filename = 'extra_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return array('ok' => false, 'message' => '파일 업로드에 실패했습니다.');
        }

        $rel = 'data/ad_applications/' . $mt_id . '/' . $maa_id . '/' . $filename;
        $mime = (string) ($file['type'] ?? '');
        $sort = count($existing) + 1;
        $table = lc_merchant_ad_apply_asset_table();
        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            maaa_maa_id = '{$maa_id}',
            maaa_mt_id = '{$mt_id}',
            maaa_kind = 'extra',
            maaa_path = '" . lc_sql_escape($rel) . "',
            maaa_filename = '" . lc_sql_escape($name) . "',
            maaa_mime = '" . lc_sql_escape($mime) . "',
            maaa_size = '{$size}',
            maaa_sort = '{$sort}',
            maaa_created_at = NOW() ", false);

        if ($ok === false) {
            @unlink($dest);
            return array('ok' => false, 'message' => '파일 저장에 실패했습니다.');
        }

        $id = (int) lc_sql_insert_id();
        $asset = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maaa_id = '{$id}' LIMIT 1 ");

        return array(
            'ok'      => true,
            'message' => '파일이 업로드되었습니다.',
            'asset'   => is_array($asset) ? lc_merchant_ad_apply_asset_to_api($asset) : null,
        );
    }
}

if (!function_exists('lc_merchant_ad_apply_delete_asset')) {
    function lc_merchant_ad_apply_delete_asset($mt_id, $asset_id)
    {
        $mt_id = (int) $mt_id;
        $asset_id = (int) $asset_id;
        $table = lc_merchant_ad_apply_asset_table();
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maaa_id = '{$asset_id}' AND maaa_mt_id = '{$mt_id}' LIMIT 1 ");
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '파일을 찾을 수 없습니다.');
        }
        $path = LC_PLUGIN_PATH . '/' . ltrim((string) ($row['maaa_path'] ?? ''), '/');
        if (is_file($path)) {
            @unlink($path);
        }
        lc_sql_query(" DELETE FROM `{$table}` WHERE maaa_id = '{$asset_id}' AND maaa_mt_id = '{$mt_id}' ", false);

        return array('ok' => true, 'message' => '파일이 삭제되었습니다.');
    }
}

if (!function_exists('lc_merchant_ad_apply_admin_list')) {
    function lc_merchant_ad_apply_admin_list($page = 1, $limit = 30, $status = '')
    {
        $page = max(1, (int) $page);
        $limit = min(100, max(1, (int) $limit));
        $offset = ($page - 1) * $limit;
        $table = lc_merchant_ad_apply_table();
        $where = '1=1';
        $status = trim((string) $status);
        if ($status !== '') {
            $where .= " AND maa_status = '" . lc_sql_escape($status) . "'";
        }
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where} ");
        $total = (int) ($total_row['cnt'] ?? 0);
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY maa_id DESC LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $api = lc_merchant_ad_apply_to_api($row, false);
                $mt_id = (int) ($row['maa_mt_id'] ?? 0);
                $merchant = $mt_id > 0 && function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
                $api['merchantCode'] = is_array($merchant) ? (string) ($merchant['mt_code'] ?? '') : '';
                $api['merchantCompany'] = is_array($merchant) ? (string) ($merchant['mt_company'] ?? '') : '';
                $items[] = $api;
            }
        }

        return array(
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        );
    }
}

if (!function_exists('lc_merchant_ad_apply_admin_set_status')) {
    function lc_merchant_ad_apply_admin_set_status($maa_id, $status, $note = '')
    {
        $maa_id = (int) $maa_id;
        $status = trim((string) $status);
        $allowed = array('submitted', 'revision', 'accepted', 'rejected', 'draft');
        if (!in_array($status, $allowed, true)) {
            return array('ok' => false, 'message' => '잘못된 상태입니다.');
        }
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '신청서를 찾을 수 없습니다.');
        }
        $table = lc_merchant_ad_apply_table();
        $note = lc_merchant_ad_apply_sanitize_text($note, 500);
        $ok = lc_sql_query(" UPDATE `{$table}` SET
            maa_status = '" . lc_sql_escape($status) . "',
            maa_admin_note = '" . lc_sql_escape($note) . "',
            maa_reviewed_at = NOW(),
            maa_updated_at = NOW()
            WHERE maa_id = '{$maa_id}' ", false);
        if ($ok === false) {
            return array('ok' => false, 'message' => '상태 변경 실패');
        }

        return array('ok' => true, 'message' => '상태가 변경되었습니다.', 'row' => lc_merchant_ad_apply_get($maa_id));
    }
}
