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

if ($lk_code === '') {
    lc_api_error('lkCode가 필요합니다.', 'INVALID_LINK', 400);
}

$config = function_exists('lc_embed_config_for_lk_code') ? lc_embed_config_for_lk_code($lk_code) : null;
if (!is_array($config)) {
    lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
}

$config['snippet'] = function_exists('lc_embed_snippet_html')
    ? lc_embed_snippet_html((string) $config['lkCode'])
    : '';

lc_api_success($config);
