<?php
/**
 * CPA 상담 신청 랜딩 — /c/{code}
 */
require_once dirname(__DIR__) . '/plugin/linkconnect/_common.php';

$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
if ($code === '' && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/c/([a-zA-Z0-9_-]+)#', (string) $_SERVER['REQUEST_URI'], $m)) {
        $code = $m[1];
    }
}

$link = $code !== '' ? lc_link_get_with_campaign($code) : null;
$done = isset($_GET['done']);
$error = '';
$success = '';

$tracking_base = is_array($link) ? (string) ($link['cp_tracking_base_url'] ?? '') : '';
$landing_url = $code !== '' ? lc_landing_public_url($code, $tracking_base) : lc_link_tracking_base_url();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($link)) {
    $result = lc_conversion_create_from_link($link, array(
        'name'    => isset($_POST['name']) ? (string) $_POST['name'] : '',
        'phone'   => isset($_POST['phone']) ? (string) $_POST['phone'] : '',
        'email'   => isset($_POST['email']) ? (string) $_POST['email'] : '',
        'region'  => isset($_POST['region']) ? (string) $_POST['region'] : '',
        'inquiry' => isset($_POST['inquiry']) ? (string) $_POST['inquiry'] : '',
    ));

    if ($result['ok']) {
        goto_url($landing_url . '?done=1');
    }

    $error = $result['message'];
}

if ($done) {
    $success = '상담 신청이 접수되었습니다. 담당자가 곧 연락드리겠습니다.';
}

$campaign_name = is_array($link) ? (string) $link['cp_name'] : '';
$seo = lc_link_landing_seo_meta($campaign_name, $code, $tracking_base);
$home_url = lc_link_tracking_base_url($tracking_base);
if ($home_url === '' && defined('G5_URL')) {
    $home_url = rtrim((string) G5_URL, '/');
}
// 독립 도메인에서 CSS도 같은 호스트로 (메인 도메인 노출 최소화)
$asset_css = lc_asset_url('css/linkconnect.css');
if (function_exists('lc_link_is_tracking_request_host') && lc_link_is_tracking_request_host() && function_exists('lc_link_apply_tracking_host')) {
    $asset_css = lc_link_apply_tracking_host($asset_css, $tracking_base !== '' ? $tracking_base : $home_url);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo lc_h($seo['title']); ?></title>
  <?php if ($seo['description'] !== '') { ?>
  <meta name="description" content="<?php echo lc_h($seo['description']); ?>">
  <?php } ?>
  <?php if ($seo['keywords'] !== '') { ?>
  <meta name="keywords" content="<?php echo lc_h($seo['keywords']); ?>">
  <?php } ?>
  <meta name="robots" content="<?php echo lc_h($seo['robots']); ?>">
  <link rel="canonical" href="<?php echo lc_h($seo['canonical']); ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo lc_h($seo['title']); ?>">
  <?php if ($seo['description'] !== '') { ?>
  <meta property="og:description" content="<?php echo lc_h($seo['description']); ?>">
  <?php } ?>
  <meta property="og:url" content="<?php echo lc_h($seo['canonical']); ?>">
  <?php if ($seo['ogImage'] !== '') { ?>
  <meta property="og:image" content="<?php echo lc_h($seo['ogImage']); ?>">
  <?php } ?>
  <meta name="twitter:card" content="<?php echo $seo['ogImage'] !== '' ? 'summary_large_image' : 'summary'; ?>">
  <meta name="twitter:title" content="<?php echo lc_h($seo['title']); ?>">
  <?php if ($seo['description'] !== '') { ?>
  <meta name="twitter:description" content="<?php echo lc_h($seo['description']); ?>">
  <?php } ?>
  <link rel="stylesheet" href="<?php echo lc_h($asset_css); ?>">
</head>
<body class="lc-app lc-app--public">
<main class="lc-main" style="max-width:560px;margin:2rem auto;padding:1rem;">
  <div class="lc-panel">
    <?php if (!is_array($link)) { ?>
      <h1 class="lc-panel__title">링크를 찾을 수 없습니다</h1>
      <p class="lc-muted">유효하지 않거나 만료된 홍보 링크입니다.</p>
      <p style="margin-top:1.5rem"><a class="lc-btn lc-btn--ghost" href="<?php echo lc_h($home_url); ?>">홈으로</a></p>
    <?php } elseif ($success !== '') { ?>
      <h1 class="lc-panel__title">접수 완료</h1>
      <p class="lc-muted"><?php echo lc_h($success); ?></p>
      <p style="margin-top:1.5rem"><a class="lc-btn lc-btn--ghost" href="<?php echo lc_h($home_url); ?>">홈으로</a></p>
    <?php } else { ?>
      <h1 class="lc-panel__title"><?php echo lc_h($campaign_name); ?></h1>
      <p class="lc-muted">무료 상담 신청서를 작성해 주세요. 입력하신 정보는 해당 광고주에게 전달됩니다.</p>

      <?php if ($error !== '') { ?>
        <p class="lc-muted" style="color:#b91c1c;margin-top:1rem;"><?php echo lc_h($error); ?></p>
      <?php } ?>

      <form method="post" action="<?php echo lc_h($landing_url); ?>" style="display:flex;flex-direction:column;gap:0.75rem;margin-top:1.5rem;">
        <label>
          <span class="lc-muted">이름 (필수)</span>
          <input type="text" name="name" class="lc-input" required maxlength="50">
        </label>
        <label>
          <span class="lc-muted">연락처 (필수)</span>
          <input type="tel" name="phone" class="lc-input" required maxlength="20" placeholder="010-0000-0000">
        </label>
        <label>
          <span class="lc-muted">이메일</span>
          <input type="email" name="email" class="lc-input" maxlength="100">
        </label>
        <label>
          <span class="lc-muted">지역</span>
          <input type="text" name="region" class="lc-input" maxlength="50" placeholder="서울, 경기 등">
        </label>
        <label>
          <span class="lc-muted">상담 내용</span>
          <textarea name="inquiry" class="lc-input" rows="4" maxlength="500" placeholder="상담 받고 싶은 내용을 입력해 주세요."></textarea>
        </label>
        <button type="submit" class="lc-btn lc-btn--primary">무료 상담 신청하기</button>
      </form>
    <?php } ?>
  </div>
</main>
</body>
</html>
