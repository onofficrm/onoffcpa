<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_campaign_thumbnail_storage_base_dir')) {
    function lc_campaign_thumbnail_storage_base_dir()
    {
        return rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/campaign_thumbnails';
    }
}

if (!function_exists('lc_campaign_thumbnail_ensure_storage')) {
    function lc_campaign_thumbnail_ensure_storage()
    {
        $base = lc_campaign_thumbnail_storage_base_dir();
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }

        $htaccess = $base . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }

        return is_dir($base);
    }
}

if (!function_exists('lc_campaign_thumbnail_relative_from_row')) {
    function lc_campaign_thumbnail_relative_from_row(array $row)
    {
        return trim((string) ($row['cp_thumbnail'] ?? ''));
    }
}

if (!function_exists('lc_campaign_thumbnail_full_path')) {
    function lc_campaign_thumbnail_full_path($relative_path)
    {
        $relative_path = trim((string) $relative_path);
        if ($relative_path === '' || strpos($relative_path, '..') !== false) {
            return '';
        }

        $full = rtrim((string) G5_DATA_PATH, '/') . '/' . ltrim($relative_path, '/');
        $base = realpath(lc_campaign_thumbnail_storage_base_dir());
        $real = realpath($full);

        if ($base === false || $real === false || strpos($real, $base) !== 0) {
            return '';
        }

        return $real;
    }
}

if (!function_exists('lc_campaign_thumbnail_public_url')) {
    function lc_campaign_thumbnail_public_url($cp_id)
    {
        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return '';
        }

        $campaign = function_exists('lc_campaign_get_by_id') ? lc_campaign_get_by_id($cp_id) : null;
        if (!is_array($campaign) || lc_campaign_thumbnail_relative_from_row($campaign) === '') {
            return '';
        }

        $base = defined('LC_PLUGIN_URL') ? LC_PLUGIN_URL : (G5_PLUGIN_URL . '/linkconnect');

        return rtrim($base, '/') . '/api/campaign-thumbnail.php?cpId=' . $cp_id;
    }
}

if (!function_exists('lc_campaign_thumbnail_admin_url')) {
    function lc_campaign_thumbnail_admin_url($cp_id)
    {
        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return '';
        }

        $base = defined('LC_PLUGIN_URL') ? LC_PLUGIN_URL : (G5_PLUGIN_URL . '/linkconnect');

        return rtrim($base, '/') . '/admin/api/campaign-thumbnail.php?cpId=' . $cp_id;
    }
}

if (!function_exists('lc_campaign_thumbnail_remove_file')) {
    function lc_campaign_thumbnail_remove_file($relative_path)
    {
        $full = lc_campaign_thumbnail_full_path($relative_path);
        if ($full !== '' && is_file($full)) {
            @unlink($full);
        }
    }
}

if (!function_exists('lc_campaign_thumbnail_save_binary')) {
    /**
     * @return array{ok:bool,message:string,thumbnailUrl?:string,relativePath?:string}
     */
    function lc_campaign_thumbnail_save_binary($cp_id, $binary, $mime = 'image/jpeg', $original_name = 'ai-thumbnail.jpg')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '광고상품 ID가 필요합니다.');
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        $binary = (string) $binary;
        $max_bytes = function_exists('lc_campaign_promo_guide_max_image_bytes')
            ? lc_campaign_promo_guide_max_image_bytes()
            : 2097152;
        $size = strlen($binary);
        if ($size <= 0 || $size > $max_bytes) {
            return array('ok' => false, 'message' => '파일 크기가 허용 범위를 초과합니다. (최대 2MB)');
        }

        if (@getimagesizefromstring($binary) === false) {
            return array('ok' => false, 'message' => '유효하지 않은 이미지 파일입니다.');
        }

        $mime = strtolower(trim((string) $mime));
        $ext = function_exists('lc_image_mime_to_ext') ? lc_image_mime_to_ext($mime) : '';
        if ($ext === '' && function_exists('lc_campaign_promo_guide_validate_upload_extension')) {
            $ext = lc_campaign_promo_guide_validate_upload_extension($original_name, $mime);
        }
        if ($ext === '') {
            return array('ok' => false, 'message' => '허용되지 않은 이미지 형식입니다. (JPG, PNG, WEBP만 가능)');
        }

        if (!lc_campaign_thumbnail_ensure_storage()) {
            return array('ok' => false, 'message' => '이미지 저장 경로를 준비하지 못했습니다.');
        }

        $dir = lc_campaign_thumbnail_storage_base_dir() . '/' . $cp_id;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return array('ok' => false, 'message' => '이미지 저장 폴더를 만들지 못했습니다.');
        }

        $stored = 'thumb_' . bin2hex(random_bytes(12)) . '.' . $ext;
        $full = $dir . '/' . $stored;
        if (@file_put_contents($full, $binary, LOCK_EX) === false) {
            return array('ok' => false, 'message' => '이미지 저장에 실패했습니다.');
        }
        @chmod($full, 0644);

        $relative = 'linkconnect/campaign_thumbnails/' . $cp_id . '/' . $stored;
        if (lc_campaign_thumbnail_full_path($relative) === '') {
            @unlink($full);
            return array('ok' => false, 'message' => '이미지 저장 경로가 유효하지 않습니다.');
        }

        $old = lc_campaign_thumbnail_relative_from_row($campaign);
        if ($old !== '') {
            lc_campaign_thumbnail_remove_file($old);
        }

        $table = lc_table('campaigns');
        $relative_esc = lc_sql_escape($relative);
        $ok = lc_sql_query(" UPDATE `{$table}` SET cp_thumbnail = '{$relative_esc}', cp_updated_at = NOW() WHERE cp_id = '{$cp_id}' LIMIT 1 ", false);
        if ($ok === false) {
            @unlink($full);
            return array('ok' => false, 'message' => '썸네일 정보 저장에 실패했습니다.');
        }

        return array(
            'ok'           => true,
            'message'      => '썸네일이 등록되었습니다.',
            'thumbnailUrl' => lc_campaign_thumbnail_admin_url($cp_id),
            'relativePath' => $relative,
        );
    }
}

if (!function_exists('lc_campaign_thumbnail_upload')) {
    /**
     * @return array{ok:bool,message:string,thumbnailUrl?:string,relativePath?:string}
     */
    function lc_campaign_thumbnail_upload($cp_id, array $file)
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'message' => '업로드된 파일이 없습니다.');
        }

        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'message' => '파일 업로드에 실패했습니다.');
        }

        $mime = function_exists('lc_campaign_promo_guide_detect_upload_mime')
            ? lc_campaign_promo_guide_detect_upload_mime($file['tmp_name'])
            : '';
        $binary = @file_get_contents($file['tmp_name']);
        if ($binary === false || $binary === '') {
            return array('ok' => false, 'message' => '유효하지 않은 이미지 파일입니다.');
        }

        return lc_campaign_thumbnail_save_binary(
            $cp_id,
            $binary,
            $mime !== '' ? $mime : 'image/jpeg',
            isset($file['name']) ? (string) $file['name'] : 'upload.jpg'
        );
    }
}

if (!function_exists('lc_campaign_thumbnail_delete')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_thumbnail_delete($cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) $cp_id;
        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        $old = lc_campaign_thumbnail_relative_from_row($campaign);
        if ($old !== '') {
            lc_campaign_thumbnail_remove_file($old);
        }

        $table = lc_table('campaigns');
        lc_sql_query(" UPDATE `{$table}` SET cp_thumbnail = '', cp_updated_at = NOW() WHERE cp_id = '{$cp_id}' LIMIT 1 ", false);

        return array('ok' => true, 'message' => '썸네일이 삭제되었습니다.');
    }
}

if (!function_exists('lc_campaign_thumbnail_serve')) {
    /**
     * @return array{ok:bool,message:string,file?:string,mime?:string}
     */
    function lc_campaign_thumbnail_serve($cp_id, $require_active = false)
    {
        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '광고상품 ID가 필요합니다.');
        }

        $campaign = lc_campaign_get_by_id($cp_id);
        if (!is_array($campaign)) {
            return array('ok' => false, 'message' => '광고상품을 찾을 수 없습니다.');
        }

        if ($require_active && (string) ($campaign['cp_status'] ?? '') !== LC_STATUS_ACTIVE) {
            return array('ok' => false, 'message' => '공개되지 않은 광고상품입니다.');
        }

        $relative = lc_campaign_thumbnail_relative_from_row($campaign);
        if ($relative === '') {
            return array('ok' => false, 'message' => '등록된 썸네일이 없습니다.');
        }

        $full = lc_campaign_thumbnail_full_path($relative);
        if ($full === '' || !is_file($full)) {
            return array('ok' => false, 'message' => '이미지 파일을 찾을 수 없습니다.');
        }

        $mime = function_exists('lc_campaign_promo_guide_detect_upload_mime')
            ? lc_campaign_promo_guide_detect_upload_mime($full)
            : 'image/jpeg';
        if ($mime === '') {
            $mime = 'image/jpeg';
        }

        return array('ok' => true, 'message' => '', 'file' => $full, 'mime' => $mime);
    }
}
