<?php
/**
 * LinkConnect 데모 계정 시드 (파트너·광고주 각 1개 + 샘플 데이터)
 *
 * 브라우저: /plugin/linkconnect/install/seed_demo.php?token=...
 * CLI: php scripts/seed-linkconnect-demo.php
 */
require_once dirname(__DIR__) . '/_common.php';

if (is_file(LC_PLUGIN_PATH . '/inc/demo_seed.php')) {
    require_once LC_PLUGIN_PATH . '/inc/demo_seed.php';
}

$is_cli = php_sapi_name() === 'cli';

if (!function_exists('lc_seed_token_ok')) {
    function lc_seed_token_ok()
    {
        $expected = function_exists('g5site_cfg') ? g5site_cfg('linkconnect_seed_token', '') : '';
        if ($expected === '') {
            $expected = function_exists('g5site_cfg') ? g5site_cfg('linkconnect_install_token', '') : '';
        }
        if ($expected === '') {
            return false;
        }
        $given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
        if ($given === '') {
            return false;
        }

        return hash_equals($expected, $given);
    }
}

if (!function_exists('lc_seed_respond_json')) {
    function lc_seed_respond_json($payload, $status = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code((int) $status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

$seed_token_ok = lc_seed_token_ok();

if (!$is_cli && !$seed_token_ok) {
    if (!lc_is_super_admin()) {
        alert('최고관리자만 데모 시드를 실행할 수 있습니다.', G5_URL);
    }
}

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';
$json_response = $seed_token_ok || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json');

$options = array(
    'partner_mb_id'      => isset($_REQUEST['partner_mb_id']) ? trim((string) $_REQUEST['partner_mb_id']) : lc_demo_default_partner_mb_id(),
    'advertiser_mb_id'   => isset($_REQUEST['advertiser_mb_id']) ? trim((string) $_REQUEST['advertiser_mb_id']) : lc_demo_default_advertiser_mb_id(),
    'password'           => isset($_REQUEST['password']) ? (string) $_REQUEST['password'] : lc_demo_default_password(),
    'partner_name'       => isset($_REQUEST['partner_name']) ? (string) $_REQUEST['partner_name'] : '데모파트너',
    'advertiser_company' => isset($_REQUEST['advertiser_company']) ? (string) $_REQUEST['advertiser_company'] : '데모광고주',
);

if ($action === 'run' || $is_cli) {
    $result = lc_demo_seed_run($options);
    if (!$result['ok']) {
        if ($is_cli) {
            fwrite(STDERR, $result['message'] . PHP_EOL);
            exit(1);
        }
        if ($json_response) {
            lc_seed_respond_json(array('ok' => false, 'message' => $result['message']), 500);
        }
        alert($result['message']);
    }

    if ($is_cli) {
        echo $result['message'] . PHP_EOL;
        exit(0);
    }

    if ($json_response) {
        lc_seed_respond_json(array(
            'ok'      => true,
            'message' => $result['message'],
            'details' => $result['details'],
        ));
    }

    alert($result['message'], lc_url('install/seed_demo.php?done=1'));
}

$done = isset($_GET['done']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LinkConnect 데모 시드</title>
  <link rel="stylesheet" href="<?php echo lc_h(lc_asset_url('css/linkconnect.css')); ?>">
</head>
<body class="lc-app lc-app--public">
<main class="lc-main" style="max-width:720px;margin:2rem auto;padding:1rem;">
  <div class="lc-panel">
    <h1 class="lc-panel__title">LinkConnect 데모 계정 시드</h1>
    <?php if ($done) { ?>
      <p class="lc-muted">데모 파트너·광고주 계정과 샘플 데이터가 준비되었습니다.</p>
    <?php } else { ?>
      <p class="lc-muted">그누보드 회원 <strong>lc_partner</strong>, <strong>lc_advertiser</strong>를 만들고 파트너·광고주 레코드와 캠페인·전환·링크·지갑 샘플을 넣습니다.</p>
    <?php } ?>

    <ul class="lc-muted" style="margin:1rem 0;line-height:1.8;">
      <li>기본 비밀번호: <code><?php echo lc_h(lc_demo_default_password()); ?></code></li>
      <li>DB 설치 여부: <strong><?php echo lc_db_installed() ? '완료' : '미설치'; ?></strong></li>
    </ul>

    <form method="post" action="<?php echo lc_h(lc_url('install/seed_demo.php')); ?>" style="display:flex;flex-direction:column;gap:0.75rem;max-width:360px;">
      <input type="hidden" name="action" value="run">
      <label>
        <span class="lc-muted">파트너 회원 ID</span>
        <input type="text" name="partner_mb_id" class="lc-input" value="<?php echo lc_h($options['partner_mb_id']); ?>">
      </label>
      <label>
        <span class="lc-muted">광고주 회원 ID</span>
        <input type="text" name="advertiser_mb_id" class="lc-input" value="<?php echo lc_h($options['advertiser_mb_id']); ?>">
      </label>
      <label>
        <span class="lc-muted">비밀번호 (신규 회원만 적용)</span>
        <input type="text" name="password" class="lc-input" value="<?php echo lc_h($options['password']); ?>">
      </label>
      <button type="submit" class="lc-btn lc-btn--primary"<?php echo lc_db_installed() ? '' : ' disabled'; ?>>데모 시드 실행</button>
    </form>

    <p style="margin-top:1.5rem">
      <a class="lc-btn lc-btn--ghost" href="<?php echo lc_h(lc_url('install/install.php')); ?>">DB 설치</a>
      <a class="lc-btn lc-btn--ghost" href="<?php echo lc_h(G5_URL); ?>">홈</a>
    </p>
  </div>
</main>
</body>
</html>
