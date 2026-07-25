<?php
// CPS 미취급 — CPA 목록으로 이동 (플러그인 LC_CPS_ENABLED=true 시 SPA 라우트 복원)
require_once dirname(__DIR__) . '/common.php';

$lc_cps_config = G5_PLUGIN_PATH . '/linkconnect/config.php';
if (is_file($lc_cps_config)) {
    include_once $lc_cps_config;
}

if (!function_exists('lc_cps_enabled') || !lc_cps_enabled()) {
    header('Location: ' . G5_URL . '/cpa-list', true, 302);
    exit;
}

require dirname(__DIR__) . '/inc/linkconnect-spa-bootstrap.php';
