<?php
require_once dirname(__DIR__) . '/_common.php';
$lc_page_title = '대시보드';
$lc_active_nav = 'merchant';
$lc_sidebar_active = 'dashboard';
lc_render_center_page(LC_CENTER_MERCHANT, 'merchant/dashboard');
