<?php
/**
 * 외부 사이트 iframe용 상담 위젯 프레임.
 * 호스트 CSS와 격리되어 폼/전화 UI를 렌더링한다.
 */
require_once dirname(__DIR__) . '/_common.php';

$lk_code = '';
foreach (array('lkCode', 'lk_code', 'code') as $key) {
    if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
        $lk_code = trim((string) $_GET[$key]);
        break;
    }
}

$mode = isset($_GET['mode']) ? strtolower(trim((string) $_GET['mode'])) : 'form';
if (!in_array($mode, array('form', 'button', 'phone'), true)) {
    $mode = 'form';
}
// 프레임 안에서는 모달 대신 인라인 폼으로 표시
if ($mode === 'button') {
    $mode = 'form';
}

$channel = isset($_GET['channel']) ? trim((string) $_GET['channel']) : 'embed';
if ($channel === '') {
    $channel = 'embed';
}
$sub_id = isset($_GET['sub_id']) ? trim((string) $_GET['sub_id']) : (isset($_GET['subId']) ? trim((string) $_GET['subId']) : '');
$page_url = isset($_GET['page_url']) ? trim((string) $_GET['page_url']) : (isset($_GET['pageUrl']) ? trim((string) $_GET['pageUrl']) : '');
$referer = isset($_GET['referer']) ? trim((string) $_GET['referer']) : (isset($_GET['referrer']) ? trim((string) $_GET['referrer']) : '');
if ($referer !== '') {
    $referer = function_exists('mb_substr') ? mb_substr($referer, 0, 500) : substr($referer, 0, 500);
}
$widget_key = '';
foreach (array('widgetKey', 'widget_key', 'wgt') as $key) {
    if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
        $widget_key = trim((string) $_GET[$key]);
        break;
    }
}

// 허용 도메인·위젯 키 검증
$config = null;
if ($lk_code !== '' && function_exists('lc_embed_config_for_lk_code')) {
    // page_url을 요청에 넣어 도메인 검증에 활용
    if ($page_url !== '' && empty($_GET['page_url'])) {
        $_GET['page_url'] = $page_url;
    }
    $config = lc_embed_config_for_lk_code($lk_code, array(
        'check_domain'     => true,
        'check_widget_key' => true,
        'widget_key'       => $widget_key,
    ));
}

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');

$script_url = function_exists('lc_embed_script_url') ? lc_embed_script_url() : '';
$brand = function_exists('lc_embed_brand_name') ? lc_embed_brand_name() : '상담';
$error = '';
if ($lk_code === '') {
    $error = 'lkCode가 필요합니다.';
} elseif (is_array($config) && isset($config['_error']) && $config['_error'] === 'DOMAIN_NOT_ALLOWED') {
    $error = '등록된 허용 도메인에서만 상담 위젯을 사용할 수 있습니다.';
} elseif (is_array($config) && isset($config['_error']) && $config['_error'] === 'WIDGET_KEY_INVALID') {
    $error = '위젯 키가 올바르지 않습니다. 설치 코드를 다시 복사해 주세요.';
} elseif (!is_array($config)) {
    $error = '유효하지 않은 홍보 링크입니다.';
}

// 허용 도메인이 있으면 frame-ancestors 제한, 없으면 전체 허용
// 플랫폼 자체 도메인은 관리자/파트너 미리보기용으로 항상 포함
$frame_ancestors = '*';
if (is_array($config) && empty($config['_error']) && !empty($config['allowedDomains']) && is_array($config['allowedDomains'])) {
    $parts = array();
    $hosts = $config['allowedDomains'];
    if (function_exists('lc_embed_platform_host')) {
        $platform_host = lc_embed_platform_host();
        if ($platform_host !== '') {
            $hosts[] = $platform_host;
        }
    }
    foreach ($hosts as $domain) {
        $host = function_exists('lc_embed_normalize_host')
            ? lc_embed_normalize_host($domain)
            : strtolower(preg_replace('/:\d+$/', '', trim((string) $domain)));
        if ($host === '') {
            continue;
        }
        foreach (array($host, 'www.' . $host) as $h) {
            $parts[] = 'https://' . $h;
            $parts[] = 'http://' . $h;
        }
    }
    $parts = array_values(array_unique($parts));
    if ($parts) {
        $frame_ancestors = implode(' ', $parts);
    }
}
header('Content-Security-Policy: frame-ancestors ' . $frame_ancestors);

$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?php echo $esc($brand); ?> 상담 위젯</title>
  <style>
    html, body { margin: 0; padding: 0; background: transparent; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Apple SD Gothic Neo", "Noto Sans KR", sans-serif; }
    .lc-frame-error { margin: 12px; padding: 16px; border-radius: 12px; border: 1px solid #fecdd3; background: #fff1f2; color: #be123c; font-size: 14px; line-height: 1.45; }
  </style>
</head>
<body>
<?php if ($error !== '') { ?>
  <div class="lc-frame-error"><?php echo $esc($error); ?></div>
  <script>
    (function () {
      try {
        parent.postMessage({ type: 'lc-embed-resize', height: document.documentElement.scrollHeight || 120 }, '*');
        parent.postMessage({ type: 'lc-embed-error', message: <?php echo json_encode($error, JSON_UNESCAPED_UNICODE); ?> }, '*');
      } catch (e) {}
    })();
  </script>
<?php } else { ?>
  <div id="lc-lead-frame" data-lc-lead="1"></div>
  <script
    src="<?php echo $esc($script_url); ?>"
    data-lk-code="<?php echo $esc($lk_code); ?>"
    <?php if ($widget_key !== '') { ?>data-widget-key="<?php echo $esc($widget_key); ?>"<?php } ?>
    data-target="#lc-lead-frame"
    data-channel="<?php echo $esc($channel); ?>"
    data-sub-id="<?php echo $esc($sub_id); ?>"
    data-mode="<?php echo $esc($mode); ?>"
    data-frame="1"
    data-page-url="<?php echo $esc($page_url); ?>"
    <?php if ($referer !== '') { ?>data-referer="<?php echo $esc($referer); ?>"<?php } ?>
    async
  ></script>
  <script>
    (function () {
      function postHeight() {
        try {
          var h = Math.max(
            document.documentElement.scrollHeight || 0,
            document.body ? document.body.scrollHeight : 0,
            120
          );
          parent.postMessage({ type: 'lc-embed-resize', height: h }, '*');
        } catch (e) {}
      }
      window.addEventListener('load', postHeight);
      window.addEventListener('resize', postHeight);
      if (window.ResizeObserver) {
        try {
          var ro = new ResizeObserver(postHeight);
          ro.observe(document.documentElement);
        } catch (e) {}
      }
      setInterval(postHeight, 800);
    })();
  </script>
<?php } ?>
</body>
</html>
