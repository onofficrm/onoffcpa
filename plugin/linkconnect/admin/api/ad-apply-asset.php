<?php
/**
 * 관리자: 광고등록 신청 배너·첨부 소재 조회
 */
require_once __DIR__ . '/_common.php';

lc_api_require_admin();
lc_merchant_ad_apply_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'GET') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$kind = isset($_GET['kind']) ? (string) $_GET['kind'] : '';
$maa_id = isset($_GET['maaId']) ? (int) $_GET['maaId'] : 0;
$asset_id = isset($_GET['assetId']) ? (int) $_GET['assetId'] : 0;

$path = '';
$mime = 'application/octet-stream';
$filename = 'file';

if ($kind === 'banner' && $maa_id > 0) {
    $row = lc_merchant_ad_apply_get($maa_id);
    if (!is_array($row)) {
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
    $asset = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE maaa_id = '{$asset_id}' LIMIT 1 ");
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
