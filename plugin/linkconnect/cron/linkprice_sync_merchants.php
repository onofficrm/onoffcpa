<?php
/**
 * 링크프라이스 CPS 광고주 동기화 크론
 *
 * CLI:
 *   php plugin/linkconnect/cron/linkprice_sync_merchants.php
 *   php plugin/linkconnect/cron/linkprice_sync_merchants.php --scope=apr --detail=1
 *
 * Web (보안 토큰 필수):
 *   /plugin/linkconnect/cron/linkprice_sync_merchants.php?token=YOUR_TOKEN
 *
 * 토큰: settings `lpCronToken` 또는 환경변수 LC_LP_CRON_TOKEN
 */
$is_cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

if (!$is_cli) {
    // 웹 호출: 그누보드 부트스트랩 후 토큰 검증
    require_once dirname(__DIR__) . '/_common.php';

    $token = '';
    if (isset($_GET['token'])) {
        $token = (string) $_GET['token'];
    } elseif (isset($_SERVER['HTTP_X_LC_CRON_TOKEN'])) {
        $token = (string) $_SERVER['HTTP_X_LC_CRON_TOKEN'];
    }

    $expected = '';
    if (function_exists('lc_settings_get')) {
        $expected = trim((string) lc_settings_get('lpCronToken', ''));
    }
    if ($expected === '' && getenv('LC_LP_CRON_TOKEN')) {
        $expected = trim((string) getenv('LC_LP_CRON_TOKEN'));
    }
    if ($expected === '' || !hash_equals($expected, $token)) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => 'Invalid cron token'), JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    // CLI: 그누보드 루트 common 로드
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

@set_time_limit(300);
@ini_set('memory_limit', '256M');

$scope = 'apr';
$detail = true;
$test_mode = false;

if ($is_cli) {
    global $argv;
    foreach ((array) $argv as $arg) {
        if (preg_match('/^--scope=(all|apr)$/', $arg, $m)) {
            $scope = $m[1];
        } elseif ($arg === '--all') {
            $scope = 'all';
        } elseif (preg_match('/^--detail=([01])$/', $arg, $m)) {
            $detail = $m[1] === '1';
        } elseif ($arg === '--test') {
            $test_mode = true;
        }
    }
} else {
    if (isset($_GET['scope']) && $_GET['scope'] === 'all') {
        $scope = 'all';
    }
    if (isset($_GET['detail']) && $_GET['detail'] === '0') {
        $detail = false;
    }
    if (!empty($_GET['test'])) {
        $test_mode = true;
    }
}

$result = lc_lp_sync_merchants(array(
    'scope'              => $scope,
    'detail'             => $detail,
    'test_mode'          => $test_mode,
    'deactivate_missing' => true,
));

$line = sprintf(
    "[%s] ok=%s fetched=%d inserted=%d updated=%d failed=%d deactivated=%d cpa_excluded=%s url=%s msg=%s\n",
    date('Y-m-d H:i:s'),
    !empty($result['ok']) ? '1' : '0',
    (int) ($result['fetched'] ?? 0),
    (int) ($result['inserted'] ?? 0),
    (int) ($result['updated'] ?? 0),
    (int) ($result['failed'] ?? 0),
    (int) ($result['deactivated'] ?? 0),
    !empty($result['cpa_excluded']) ? 'yes' : 'NO',
    (string) ($result['api_url'] ?? ''),
    (string) ($result['message'] ?? '')
);

$log_dir = defined('G5_DATA_PATH') ? G5_DATA_PATH . '/linkconnect' : sys_get_temp_dir() . '/linkconnect';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
@file_put_contents($log_dir . '/lp_merchant_sync.log', $line, FILE_APPEND);

if ($is_cli) {
    echo $line;
    if (!empty($result['sample_fields'])) {
        echo 'sample_fields: ' . implode(', ', $result['sample_fields']) . "\n";
    }
    if (!empty($result['errors'])) {
        foreach (array_slice($result['errors'], 0, 20) as $err) {
            echo '  error: ' . $err . "\n";
        }
    }
    exit(!empty($result['ok']) ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'ok'      => !empty($result['ok']),
    'message' => $result['message'] ?? '',
    'sync'    => $result,
), JSON_UNESCAPED_UNICODE);
exit(!empty($result['ok']) ? 0 : 1);
