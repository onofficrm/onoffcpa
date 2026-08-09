<?php
require_once dirname(__DIR__) . '/_common.php';

if (function_exists('lc_api_handle_cors_preflight')) {
    lc_api_handle_cors_preflight();
}
if (function_exists('lc_api_allow_public_cors')) {
    lc_api_allow_public_cors();
}

lc_api_require_method('GET');

$lk_code = '';
foreach (array('lkCode', 'lk_code', 'code') as $key) {
    if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
        $lk_code = trim((string) $_GET[$key]);
        break;
    }
}

$widget_key = '';
foreach (array('widgetKey', 'widget_key', 'wgt') as $key) {
    if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
        $widget_key = trim((string) $_GET[$key]);
        break;
    }
}

if ($lk_code === '') {
    lc_api_error('lkCode가 필요합니다.', 'INVALID_LINK', 400);
}

$config = function_exists('lc_embed_config_for_lk_code')
    ? lc_embed_config_for_lk_code($lk_code, array(
        'check_domain'     => true,
        'check_widget_key' => true,
        'widget_key'       => $widget_key,
    ))
    : null;

if (is_array($config) && isset($config['_error']) && $config['_error'] === 'DOMAIN_NOT_ALLOWED') {
    lc_api_error('등록된 허용 도메인에서만 상담 위젯을 사용할 수 있습니다.', 'DOMAIN_NOT_ALLOWED', 403);
}

if (is_array($config) && isset($config['_error']) && $config['_error'] === 'WIDGET_KEY_INVALID') {
    lc_api_error('위젯 키가 올바르지 않습니다. 설치 코드를 다시 복사해 주세요.', 'WIDGET_KEY_INVALID', 403);
}

if (!is_array($config)) {
    lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
}

$config['snippet'] = function_exists('lc_embed_snippet_html')
    ? lc_embed_snippet_html(
        (string) $config['lkCode'],
        'form',
        array('widgetKey' => (string) ($config['widgetKey'] ?? $widget_key))
    )
    : '';

lc_api_success($config);
