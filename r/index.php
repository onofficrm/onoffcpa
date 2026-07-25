<?php
/**
 * 홍보 링크 클릭 리다이렉트 — /r/{code}
 */
require_once dirname(__DIR__) . '/plugin/linkconnect/_common.php';

$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
if ($code === '' && isset($_SERVER['PATH_INFO'])) {
    $code = trim((string) $_SERVER['PATH_INFO'], '/');
}
if ($code === '' && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/r/([a-zA-Z0-9_-]+)#', (string) $_SERVER['REQUEST_URI'], $m)) {
        $code = $m[1];
    }
}

if ($code === '') {
    header('HTTP/1.1 404 Not Found');
    exit('유효하지 않은 링크입니다.');
}

$link = lc_link_get_with_campaign($code);
if (!$link || $link['lk_status'] !== 'active' || $link['cp_status'] !== LC_STATUS_ACTIVE) {
    header('HTTP/1.1 404 Not Found');
    exit('만료되었거나 유효하지 않은 홍보 링크입니다.');
}

lc_link_record_click($link);
$target = lc_link_resolve_redirect_url($link);
goto_url($target);
