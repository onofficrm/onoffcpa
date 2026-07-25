<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$lc_page_title = isset($lc_page_title) ? (string) $lc_page_title : '관리자센터';
$lc_admin_role = lc_is_super_admin() ? '최고관리자' : (lc_is_linkconnect_admin() ? '링크커넥트 관리자' : '운영 관리자 (샘플)');
$lc_admin_date = date('Y.m.d H:i');
?>
<header class="lc-partner-toolbar lc-admin-toolbar">
  <div class="lc-partner-toolbar__left">
    <button type="button" class="lc-sidebar-toggle" data-lc-sidebar-toggle aria-expanded="false" aria-controls="lcAdminSidebar" aria-label="사이드바 열기">
      <span class="lc-nav-toggle__icon" aria-hidden="true">
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
      </span>
    </button>
    <div>
      <h1 class="lc-partner-toolbar__title"><?php echo lc_h($lc_page_title); ?></h1>
      <p class="lc-partner-toolbar__date"><?php echo lc_h($lc_admin_role); ?> · <?php echo lc_h($lc_admin_date); ?></p>
    </div>
  </div>
  <div class="lc-partner-toolbar__right">
    <div class="lc-admin-kpi">
      <span class="lc-admin-kpi__item"><em>오늘 DB</em> <strong>248</strong></span>
      <span class="lc-admin-kpi__item lc-admin-kpi__item--warn"><em>검수대기</em> <strong>18</strong></span>
      <span class="lc-admin-kpi__item lc-admin-kpi__item--danger"><em>API 오류</em> <strong>2</strong></span>
    </div>
    <button type="button" class="lc-partner-icon-btn" title="알림">🔔</button>
    <button type="button" class="lc-partner-icon-btn lc-admin-icon-btn" title="시스템 상태">◆</button>
  </div>
</header>
