<?php
/**
 * 관리자센터 — 통합 대시보드
 *
 * 접근 권한: lc_render_center_page() → lc_require_admin_access()
 * - 최고관리자 (lc_is_super_admin) 또는 링크커넥트 관리자 (lc_is_linkconnect_admin)
 * - LC_ADMIN_GUARD_ENABLED = true 시 비관리자 URL 직접 접근 차단
 */
require_once dirname(__DIR__) . '/_common.php';
$lc_page_title = '통합 대시보드';
$lc_active_nav = 'admin';
$lc_sidebar_active = 'dashboard';
lc_render_center_page(LC_CENTER_ADMIN, 'admin/dashboard');
