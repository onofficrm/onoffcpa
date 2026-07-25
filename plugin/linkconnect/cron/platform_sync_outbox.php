<?php
/**
 * 다중 플랫폼 outbox 워커
 *
 * CLI: php plugin/linkconnect/cron/platform_sync_outbox.php
 * Web: /plugin/linkconnect/cron/platform_sync_outbox.php?token=YOUR_TOKEN
 *
 * 토큰: settings `mpCronToken` 또는 환경변수 LC_MP_CRON_TOKEN
 * 플래그 OFF 시 즉시 종료 (exit 0).
 */
$is_cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

if (!$is_cli) {
    require_once dirname(__DIR__) . '/_common.php';
    $token = isset($_GET['token']) ? (string) $_GET['token'] : (isset($_SERVER['HTTP_X_LC_CRON_TOKEN']) ? (string) $_SERVER['HTTP_X_LC_CRON_TOKEN'] : '');

    $expected = '';
    if (function_exists('lc_settings_get')) {
        $expected = trim((string) lc_settings_get('mpCronToken', ''));
    }
    if ($expected === '' && getenv('LC_MP_CRON_TOKEN')) {
        $expected = trim((string) getenv('LC_MP_CRON_TOKEN'));
    }
    if ($expected === '' || !hash_equals($expected, $token)) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => 'Invalid cron token'), JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    $g5_root = realpath(dirname(__DIR__, 3));
    if ($g5_root === false || !is_file($g5_root . '/common.php')) {
        fwrite(STDERR, "GNUBoard common.php not found\n");
        exit(1);
    }
    if (!defined('_GNUBOARD_')) {
        include_once $g5_root . '/common.php';
    }
    require_once dirname(__DIR__) . '/_common.php';
}

if (!function_exists('lc_mp_enabled') || !lc_mp_enabled()) {
    if ($is_cli) {
        fwrite(STDOUT, "multi-platform disabled\n");
        exit(0);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'message' => 'disabled', 'processed' => 0), JSON_UNESCAPED_UNICODE);
    exit;
}

$limit = 20;
if (!$is_cli && isset($_GET['limit'])) {
    $limit = (int) $_GET['limit'];
} elseif ($is_cli) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--limit=') === 0) {
            $limit = (int) substr($arg, 8);
        }
    }
}

$result = lc_mp_process_outbox_once($limit);
if ($is_cli) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($result['ok']) ? 1 : 0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
