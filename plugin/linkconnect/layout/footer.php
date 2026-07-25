<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!isset($lc_show_footer) || !$lc_show_footer) {
    echo '</body></html>';
    return;
}
?>
<footer class="lc-footer">
  <div class="lc-footer__inner">
    <div class="lc-footer__grid">
      <div class="lc-footer__brand">
        <a class="lc-brand lc-brand--footer" href="<?php echo lc_h(lc_public_home_url()); ?>">
          <svg class="lc-brand__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M10 13a5 5 0 0 1 7.07 0l1.41 1.41a5 5 0 0 1-7.07 7.07l-.71-.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M14 11a5 5 0 0 1-7.07 0L5.52 9.59a5 5 0 0 1 7.07-7.07l.71.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span class="lc-brand__text">링크커넥트</span>
        </a>
        <p class="lc-footer__desc">클릭을 수익으로, DB를 성과로 연결하는 제휴마케팅 플랫폼입니다. 최고의 전환율과 투명한 정산 시스템을 제공합니다.</p>
        <p class="lc-footer__contact">
          이메일: <?php echo lc_h(lc_contact_email()); ?><br>
          고객센터: <?php echo lc_h(lc_contact_phone()); ?> (평일 10:00 ~ 17:00)
        </p>
      </div>

      <div>
        <h4 class="lc-footer__title">회사소개</h4>
        <ul class="lc-footer__links">
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/about') : lc_url('pages/home.php')); ?>">회사소개</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/affiliate') : lc_url('pages/home.php')); ?>">제휴마케팅이란?</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/notice') : (defined('G5_BBS_URL') ? G5_BBS_URL . '/board.php?bo_table=notice' : '#')); ?>">공지사항</a></li>
        </ul>
      </div>

      <div>
        <h4 class="lc-footer__title">캠페인 · 서비스</h4>
        <ul class="lc-footer__links">
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/cpa-list') : lc_url('pages/cpa.php')); ?>">CPA 상품</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/cps') : lc_url('pages/cps.php')); ?>">CPS 상품</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/events') : lc_url('pages/events.php')); ?>">이벤트/프로모션</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/partner') : lc_url('partner/dashboard.php')); ?>">파트너센터</a></li>
          <li><a href="<?php echo lc_h(function_exists('lc_spa_url') && lc_builder_spa_enabled() ? lc_spa_url('/advertiser') : lc_url('merchant/dashboard.php')); ?>">광고주센터</a></li>
          <?php if (!lc_is_logged_in()) { ?>
          <li><a href="<?php echo lc_h(lc_login_url()); ?>">로그인</a></li>
          <li><a href="<?php echo lc_h(lc_register_url()); ?>">회원가입</a></li>
          <?php } else { ?>
          <li><a href="<?php echo lc_h(lc_member_edit_url()); ?>">정보수정</a></li>
          <li><a href="<?php echo lc_h(lc_logout_url()); ?>">로그아웃</a></li>
          <?php } ?>
          <li><a href="<?php echo lc_h(defined('G5_BBS_URL') ? G5_BBS_URL . '/content.php?co_id=provision' : '#'); ?>">이용약관</a></li>
          <li><a href="<?php echo lc_h(defined('G5_BBS_URL') ? G5_BBS_URL . '/content.php?co_id=privacy' : '#'); ?>">개인정보처리방침</a></li>
          <?php if (lc_is_super_admin()) { ?>
          <li><a href="<?php echo lc_h(LC_URL_ADMIN_DASHBOARD); ?>">관리자센터</a></li>
          <?php } ?>
        </ul>
      </div>
    </div>

    <p class="lc-footer__copy">&copy; <?php echo date('Y'); ?> <?php echo lc_h(lc_site_name()); ?>. All rights reserved.</p>
  </div>
</footer>
<?php lc_ui_super_admin_fab(); ?>
</body>
</html>
