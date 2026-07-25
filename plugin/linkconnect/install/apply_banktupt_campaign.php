<?php
/**
 * banktupt 개인회생 CPA 단일 상품 적용 (데모 캠페인 정리)
 *
 * 브라우저: /plugin/linkconnect/install/apply_banktupt_campaign.php?action=run
 * CLI: php scripts/apply-banktupt-campaign.php
 */
require_once dirname(__DIR__) . '/_common.php';

$is_cli = php_sapi_name() === 'cli';
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';

if (!function_exists('lc_apply_banktupt_token_ok')) {
    function lc_apply_banktupt_token_ok()
    {
        if (!function_exists('g5site_cfg')) {
            return false;
        }
        $expected = g5site_cfg('linkconnect_seed_token', '');
        if ($expected === '') {
            $expected = g5site_cfg('linkconnect_install_token', '');
        }
        if ($expected === '') {
            return false;
        }
        $given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';

        return $given !== '' && hash_equals($expected, $given);
    }
}

$token_ok = lc_apply_banktupt_token_ok();

if (!$is_cli && $action === 'run' && !$token_ok && !lc_is_super_admin()) {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

if ($action === 'run' || $is_cli) {
    if (!function_exists('lc_campaign_apply_banktupt_only')) {
        if ($is_cli) {
            fwrite(STDERR, "lc_campaign_apply_banktupt_only not found.\n");
            exit(1);
        }
        alert('campaign_banktupt.php를 로드할 수 없습니다.');
    }

    $result = lc_campaign_apply_banktupt_only(array(
        'advertiser_mb_id' => isset($_REQUEST['advertiser_mb_id']) ? trim((string) $_REQUEST['advertiser_mb_id']) : 'lc_advertiser',
    ));

    if ($is_cli) {
        if (!$result['ok']) {
            fwrite(STDERR, $result['message'] . PHP_EOL);
            exit(1);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit(0);
    }

    if (!$result['ok']) {
        alert($result['message']);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'data' => $result), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>banktupt CPA 단일 상품 적용</title>
</head>
<body style="font-family:sans-serif;max-width:640px;margin:2rem auto;padding:1rem;">
  <h1>banktupt CPA 단일 상품 적용</h1>
  <p>데모 CPA 광고상품을 종료하고, 개인회생(banktupt) 랜딩 연결 상품 1개만 활성화합니다.</p>
  <p><a href="?action=run">실행</a></p>
</body>
</html>
