<?php
/**
 * 링크프라이스 CPS 실적(확정·취소) 동기화 크론
 *
 * CLI:
 *   php cron/linkprice_sync_conversions.php
 *   php cron/linkprice_sync_conversions.php --mode=last7
 *   php cron/linkprice_sync_conversions.php --mode=day --date=20260709
 *   php cron/linkprice_sync_conversions.php --mode=this_month
 *   php cron/linkprice_sync_conversions.php --mode=prev_month
 *   php cron/linkprice_sync_conversions.php --mode=month --date=202606
 *
 * Web: ?token=lpCronToken&mode=last7
 *
 * 권장: 15~30분 간격 (공식 API 과도 호출 자제)
 */
$is_cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

if (!$is_cli) {
    require_once dirname(__DIR__) . '/_common.php';
    $token = isset($_GET['token']) ? (string) $_GET['token'] : (isset($_SERVER['HTTP_X_LC_CRON_TOKEN']) ? (string) $_SERVER['HTTP_X_LC_CRON_TOKEN'] : '');
    $expected = function_exists('lc_settings_get') ? trim((string) lc_settings_get('lpCronToken', '')) : '';
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

$options = array(
    'mode' => 'last7',
    'date' => date('Ymd'),
);

if ($is_cli) {
    global $argv;
    foreach ((array) $argv as $arg) {
        if (preg_match('/^--mode=(.+)$/', $arg, $m)) {
            $options['mode'] = $m[1];
        } elseif (preg_match('/^--date=(.+)$/', $arg, $m)) {
            $options['date'] = $m[1];
        } elseif (preg_match('/^--from=(.+)$/', $arg, $m)) {
            $options['from'] = $m[1];
            $options['mode'] = 'range';
        } elseif (preg_match('/^--to=(.+)$/', $arg, $m)) {
            $options['to'] = $m[1];
        } elseif (preg_match('/^--merchant=(.+)$/', $arg, $m)) {
            $options['merchant_id'] = $m[1];
        } elseif (preg_match('/^--order=(.+)$/', $arg, $m)) {
            $options['order_code'] = $m[1];
        } elseif ($arg === '--test') {
            $options['test_mode'] = true;
        } elseif (preg_match('/^--cancel_flag=([YN])$/', $arg, $m)) {
            $options['cancel_flag'] = $m[1];
        }
    }
} else {
    foreach (array('mode', 'date', 'from', 'to', 'merchant_id', 'order_code', 'cancel_flag') as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $options[$k] = (string) $_GET[$k];
        }
    }
    if (!empty($_GET['test'])) {
        $options['test_mode'] = true;
    }
}

$result = lc_lp_sync_orders($options);

$line = sprintf(
    "[%s] ok=%s fetched=%d inserted=%d updated=%d unchanged=%d failed=%d pages=%d dates=%s msg=%s\n",
    date('Y-m-d H:i:s'),
    !empty($result['ok']) ? '1' : '0',
    (int) ($result['fetched'] ?? 0),
    (int) ($result['inserted'] ?? 0),
    (int) ($result['updated'] ?? 0),
    (int) ($result['unchanged'] ?? 0),
    (int) ($result['failed'] ?? 0),
    (int) ($result['pages'] ?? 0),
    implode(',', (array) ($result['dates'] ?? array())),
    (string) ($result['message'] ?? '')
);

$log_dir = defined('G5_DATA_PATH') ? G5_DATA_PATH . '/linkconnect' : sys_get_temp_dir() . '/linkconnect';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
@file_put_contents($log_dir . '/lp_order_sync.log', $line, FILE_APPEND);

if ($is_cli) {
    echo $line;
    if (!empty($result['errors'])) {
        foreach (array_slice($result['errors'], 0, 20) as $err) {
            echo "  error: {$err}\n";
        }
    }
    exit(!empty($result['ok']) ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('ok' => !empty($result['ok']), 'message' => $result['message'] ?? '', 'sync' => $result), JSON_UNESCAPED_UNICODE);
exit(!empty($result['ok']) ? 0 : 1);
