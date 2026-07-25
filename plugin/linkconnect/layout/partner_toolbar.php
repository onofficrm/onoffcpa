<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$lc_page_title = isset($lc_page_title) ? (string) $lc_page_title : '파트너센터';
?>
<header class="lc-partner-toolbar">
  <div class="lc-partner-toolbar__left">
    <button type="button" class="lc-sidebar-toggle" data-lc-sidebar-toggle aria-expanded="false" aria-controls="lcPartnerSidebar" aria-label="사이드바 열기">
      <span class="lc-nav-toggle__icon" aria-hidden="true">
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
      </span>
    </button>
    <div>
      <h1 class="lc-partner-toolbar__title"><?php echo lc_h($lc_page_title); ?></h1>
      <p class="lc-partner-toolbar__date"><?php echo date('Y년 n월 j일'); ?> (<?php echo array('일','월','화','수','목','금','토')[date('w')]; ?>)</p>
    </div>
  </div>
  <div class="lc-partner-toolbar__right">
    <div class="lc-partner-code">
      <span class="lc-partner-code__label">파트너 코드</span>
      <span class="lc-partner-code__value"><?php
        $lc_toolbar_partner = function_exists('lc_get_current_partner') ? lc_get_current_partner() : null;
        echo lc_h(is_array($lc_toolbar_partner) ? $lc_toolbar_partner['pt_code'] : '—');
      ?></span>
      <button type="button" class="lc-partner-code__copy" title="복사">📋</button>
    </div>
    <button type="button" class="lc-partner-icon-btn" title="알림">🔔</button>
    <a class="lc-partner-icon-btn lc-partner-icon-btn--user" href="<?php echo lc_h(lc_member_edit_url()); ?>" title="회원정보 수정">👤</a>
    <?php if (lc_is_logged_in()) { ?>
    <a class="lc-btn lc-btn--ghost lc-btn--xs" href="<?php echo lc_h(lc_logout_url()); ?>">로그아웃</a>
    <?php } else { ?>
    <a class="lc-btn lc-btn--ghost lc-btn--xs" href="<?php echo lc_h(lc_login_url()); ?>">로그인</a>
    <?php } ?>
  </div>
</header>
