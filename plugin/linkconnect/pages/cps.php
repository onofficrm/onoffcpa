<?php
require_once dirname(__DIR__) . '/_common.php';

// CPS 미취급(LC_CPS_ENABLED=false) 시 CPA 목록으로 안내
if (!function_exists('lc_cps_enabled') || !lc_cps_enabled()) {
    header('Location: ' . lc_url('pages/cpa.php'), true, 302);
    exit;
}

$lc_page_title = 'CPS 광고상품';
$lc_active_nav = 'cps';
$lc_body_class = 'lc-app lc-app--public';

lc_render_public_page('public/cps');
