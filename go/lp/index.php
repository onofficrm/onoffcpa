<?php
/**
 * 링크프라이스 CPS 클릭 추적 — /go/lp/{merchant_code}?p={partner_token}&u={deeplink}
 * CPA /r/{code} 와 완전 분리.
 */
require_once dirname(__DIR__, 2) . '/plugin/linkconnect/_common.php';

$merchant_code = isset($_GET['m']) ? trim((string) $_GET['m']) : '';
if ($merchant_code === '' && isset($_GET['merchant'])) {
    $merchant_code = trim((string) $_GET['merchant']);
}
if ($merchant_code === '' && isset($_SERVER['PATH_INFO'])) {
    $merchant_code = trim((string) $_SERVER['PATH_INFO'], '/');
}
if ($merchant_code === '' && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/go/lp/([A-Za-z0-9_-]+)#', (string) $_SERVER['REQUEST_URI'], $m)) {
        $merchant_code = $m[1];
    }
}

$partner_token = isset($_GET['p']) ? trim((string) $_GET['p']) : '';
$deeplink = '';
if (isset($_GET['u'])) {
    $deeplink = (string) $_GET['u'];
} elseif (isset($_GET['url'])) {
    $deeplink = (string) $_GET['url'];
}

$result = lc_lp_handle_public_click($merchant_code, $partner_token, $deeplink);

if (!empty($result['ok']) && !empty($result['redirect'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Location: ' . $result['redirect'], true, 302);
    exit;
}

$http = isset($result['http']) ? (int) $result['http'] : 400;
if ($http < 400) {
    $http = 400;
}
http_response_code($http);
header('Content-Type: text/html; charset=utf-8');
$msg = htmlspecialchars((string) ($result['message'] ?? '이동할 수 없습니다.'), ENT_QUOTES, 'UTF-8');
echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>링크 오류</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;color:#334155">';
echo '<h1 style="font-size:1.25rem">링크를 열 수 없습니다</h1>';
echo '<p>' . $msg . '</p>';
echo '</body></html>';
exit;
