<?php
/**
 * 링크프라이스 CPS 운영 헬스체크 (시크릿 미노출)
 *
 * GET /plugin/linkconnect/api/lp_health.php
 * 선택: ?token=lpCronToken (설정 시 필수)
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$expected = function_exists('lc_settings_get') ? trim((string) lc_settings_get('lpCronToken', '')) : '';
if ($expected === '' && getenv('LC_LP_CRON_TOKEN')) {
    $expected = trim((string) getenv('LC_LP_CRON_TOKEN'));
}
if ($expected !== '') {
    $token = isset($_GET['token']) ? (string) $_GET['token'] : (isset($_SERVER['HTTP_X_LC_CRON_TOKEN']) ? (string) $_SERVER['HTTP_X_LC_CRON_TOKEN'] : '');
    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        echo json_encode(array('ok' => false, 'message' => 'Invalid token'), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('lc_lp_health_snapshot')) {
    http_response_code(503);
    echo json_encode(array('ok' => false, 'message' => 'module not loaded'), JSON_UNESCAPED_UNICODE);
    exit;
}

$snap = lc_lp_health_snapshot();
$snap['at'] = date('c');
http_response_code($snap['ok'] ? 200 : 503);
echo json_encode($snap, JSON_UNESCAPED_UNICODE);
exit;
