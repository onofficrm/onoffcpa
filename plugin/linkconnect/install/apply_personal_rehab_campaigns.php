<?php
/**
 * 개인회생 CPA 2개(banktupt + dasibom) 랜딩·계약 표기 일괄 적용.
 *
 * 브라우저: /plugin/linkconnect/install/apply_personal_rehab_campaigns.php?action=run
 * CLI: php plugin/linkconnect/install/apply_personal_rehab_campaigns.php
 */
require_once dirname(__DIR__) . '/_common.php';

$is_cli = php_sapi_name() === 'cli';
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';

if (!function_exists('lc_apply_personal_rehab_token_ok')) {
    function lc_apply_personal_rehab_token_ok()
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

$token_ok = lc_apply_personal_rehab_token_ok();

if (!$is_cli && $action === 'run' && !$token_ok && !lc_is_super_admin()) {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

if ($action === 'run' || $is_cli) {
    if (!function_exists('lc_campaign_apply_personal_rehab_pair')) {
        if ($is_cli) {
            fwrite(STDERR, "lc_campaign_apply_personal_rehab_pair not found.\n");
            exit(1);
        }
        alert('campaign_dasibom.php / campaign_banktupt.php 를 로드할 수 없습니다.');
    }

    $opts = array();
    if (!empty($_REQUEST['banktupt_cp_id'])) {
        $opts['banktupt_cp_id'] = (int) $_REQUEST['banktupt_cp_id'];
    }
    if (!empty($_REQUEST['dasibom_cp_id'])) {
        $opts['dasibom_cp_id'] = (int) $_REQUEST['dasibom_cp_id'];
    }
    // 운영 DB에 이미 등록된 2개 고정 매핑 (랜딩 URL로도 찾음)
    if (empty($opts['banktupt_cp_id'])) {
        $opts['banktupt_cp_id'] = 6;
    }
    if (empty($opts['dasibom_cp_id'])) {
        $opts['dasibom_cp_id'] = 11;
    }

    $result = lc_campaign_apply_personal_rehab_pair($opts);

    if ($is_cli) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit(!empty($result['ok']) ? 0 : 1);
    }

    if (empty($result['ok'])) {
        alert(isset($result['message']) ? (string) $result['message'] : '적용 실패');
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
  <title>개인회생 CPA 2개 랜딩·계약 적용</title>
</head>
<body style="font-family:sans-serif;max-width:640px;margin:2rem auto;padding:1rem;">
  <h1>개인회생 CPA 2개 랜딩·계약 적용</h1>
  <p>banktupt·dasibom 랜딩 URL을 온오프CPA로 맞추고, 승인율·채널·설명 등 계약 표기 필드를 채웁니다. 기존 단가·상품명은 유지합니다.</p>
  <p><a href="?action=run">실행</a></p>
</body>
</html>
