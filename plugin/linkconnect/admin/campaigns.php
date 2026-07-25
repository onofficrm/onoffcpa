<?php
/** 관리자센터 — 접근 권한: lc_render_center_page() → lc_require_admin_access() (LC_ADMIN_GUARD_ENABLED) */
require_once dirname(__DIR__) . '/_common.php';
$lc_page_title = '광고상품 관리';
$lc_active_nav = 'admin';
$lc_sidebar_active = 'campaigns';
lc_render_center_page(LC_CENTER_ADMIN, 'admin/campaigns');
