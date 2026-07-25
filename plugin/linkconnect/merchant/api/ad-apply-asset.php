<?php
require_once __DIR__ . '/_common.php';

lc_merchant_ad_apply_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
    $merchant = lc_api_require_active_merchant();
} else {
    lc_api_require_login();
    $merchant = lc_get_current_merchant();
}

if (!is_array($merchant)) {
    lc_api_error('광고주 등록이 필요합니다.', 'NOT_MERCHANT', 403);
}

if (function_exists('lc_merchant_contract_guard_api_or_fail')) {
    lc_merchant_contract_guard_api_or_fail($merchant);
}

$mt_id = (int) ($merchant['mt_id'] ?? 0);

if ($method === 'GET') {
    $kind = isset($_GET['kind']) ? (string) $_GET['kind'] : '';
    $maa_id = isset($_GET['maaId']) ? (int) $_GET['maaId'] : 0;
    $asset_id = isset($_GET['assetId']) ? (int) $_GET['assetId'] : 0;

    $path = '';
    $mime = 'application/octet-stream';
    $filename = 'file';

    if ($kind === 'banner' && $maa_id > 0) {
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row) || (int) ($row['maa_mt_id'] ?? 0) !== $mt_id) {
            lc_api_error('배너를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $rel = trim((string) ($row['maa_banner_path'] ?? ''));
        if ($rel === '') {
            lc_api_error('배너가 없습니다.', 'NOT_FOUND', 404);
        }
        $path = LC_PLUGIN_PATH . '/' . ltrim($rel, '/');
        $filename = (string) ($row['maa_banner_name'] ?? 'banner');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime_map = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
        $mime = $mime_map[$ext] ?? 'application/octet-stream';
    } elseif ($asset_id > 0) {
        $table = lc_merchant_ad_apply_asset_table();
        $asset = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maaa_id = '{$asset_id}' AND maaa_mt_id = '{$mt_id}' LIMIT 1 ");
        if (!is_array($asset)) {
            lc_api_error('파일을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $rel = trim((string) ($asset['maaa_path'] ?? ''));
        $path = LC_PLUGIN_PATH . '/' . ltrim($rel, '/');
        $filename = (string) ($asset['maaa_filename'] ?? 'file');
        $mime = (string) ($asset['maaa_mime'] ?? 'application/octet-stream');
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }
    } else {
        lc_api_error('파일 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    if (!is_file($path)) {
        lc_api_error('파일이 존재하지 않습니다.', 'NOT_FOUND', 404);
    }

    if (!headers_sent()) {
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
    }
    readfile($path);
    exit;
}

if ($method === 'POST') {
    $maa_id = isset($_POST['maaId']) ? (int) $_POST['maaId'] : 0;
    $kind = isset($_POST['kind']) ? (string) $_POST['kind'] : 'extra';

    if ($maa_id <= 0) {
        $draft = lc_merchant_ad_apply_ensure_draft($mt_id);
        $maa_id = is_array($draft) ? (int) ($draft['maa_id'] ?? 0) : 0;
    }

    if (empty($_FILES['file'])) {
        lc_api_error('파일이 필요합니다.', 'INVALID_FILE', 400);
    }

    $file = $_FILES['file'];
    if ($kind === 'banner') {
        $result = lc_merchant_ad_apply_save_banner($mt_id, $maa_id, $file);
    } else {
        $result = lc_merchant_ad_apply_save_extra_asset($mt_id, $maa_id, $file);
    }

    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'UPLOAD_FAILED', 400);
    }

    $row = lc_merchant_ad_apply_get($maa_id);
    lc_api_success(array(
        'message'     => $result['message'],
        'application' => is_array($row) ? lc_merchant_ad_apply_to_api($row, true) : null,
        'asset'       => $result['asset'] ?? null,
    ));
}

lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
