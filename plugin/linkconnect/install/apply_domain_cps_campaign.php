<?php
/**
 * 낙장도메인 CPS 첫 상품 적용
 *
 * 브라우저: /plugin/linkconnect/install/apply_domain_cps_campaign.php?action=run
 * CLI: php plugin/linkconnect/install/apply_domain_cps_campaign.php
 */
require_once dirname(__DIR__) . '/_common.php';

$is_cli = php_sapi_name() === 'cli';
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';

if (!function_exists('lc_apply_domain_cps_token_ok')) {
    function lc_apply_domain_cps_token_ok()
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

$token_ok = lc_apply_domain_cps_token_ok();

if (!$is_cli && $action === 'run' && !$token_ok && !lc_is_super_admin()) {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

if ($action === 'run' || $is_cli) {
    if (!function_exists('lc_campaign_ensure_domain_cps')) {
        if ($is_cli) {
            fwrite(STDERR, "lc_campaign_ensure_domain_cps not found.\n");
            exit(1);
        }
        alert('campaign_domain_cps.php를 로드할 수 없습니다.');
    }

    $result = lc_campaign_ensure_domain_cps(array(
        'activate' => true,
        'mt_id'    => isset($_REQUEST['mt_id']) ? (int) $_REQUEST['mt_id'] : 0,
    ));

    if ($is_cli) {
        if (empty($result['ok'])) {
            fwrite(STDERR, ($result['message'] ?? 'failed') . PHP_EOL);
            exit(1);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit(0);
    }

    if (empty($result['ok'])) {
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
  <title>낙장도메인 CPS 상품 적용</title>
</head>
<body style="font-family:sans-serif;max-width:640px;margin:2rem auto;padding:1rem;">
  <h1>낙장도메인 CPS 상품 적용</h1>
  <p>CPS-DOMAIN (https://domain.icrm.co.kr/) 을 운영중으로 등록합니다. Core LIFETIME 10% 규칙.</p>
  <p><a href="?action=run">실행</a></p>
</body>
</html>
