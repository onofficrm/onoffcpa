<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$lc_page_title = isset($lc_page_title) ? (string) $lc_page_title : lc_site_name();
$lc_active_nav = isset($lc_active_nav) ? (string) $lc_active_nav : '';
$lc_body_class = isset($lc_body_class) ? (string) $lc_body_class : 'lc-app';
$lc_show_footer = !isset($lc_show_footer) || $lc_show_footer;
$lc_company_nav_active = in_array($lc_active_nav, array('home', 'affiliate', 'notice'), true);
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo lc_h(lc_site_desc()); ?>">
<title><?php echo lc_h($lc_page_title . ' | ' . lc_site_name()); ?></title>
<?php lc_enqueue_assets(); ?>
</head>
<body class="<?php echo lc_h($lc_body_class); ?>">
<header class="lc-header" id="lcHeader">
  <div class="lc-header__inner">
    <a class="lc-brand" href="<?php echo lc_h(lc_public_home_url()); ?>">
      <svg class="lc-brand__icon" width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M10 13a5 5 0 0 1 7.07 0l1.41 1.41a5 5 0 0 1-7.07 7.07l-.71-.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M14 11a5 5 0 0 1-7.07 0L5.52 9.59a5 5 0 0 1 7.07-7.07l.71.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span class="lc-brand__text">링크커넥트</span>
    </a>

    <nav class="lc-nav lc-nav--desktop" data-lc-nav aria-label="주요 메뉴">
      <div class="lc-nav-dropdown<?php echo $lc_company_nav_active ? ' is-active' : ''; ?>" data-lc-nav-dropdown>
        <button type="button" class="lc-nav-dropdown__toggle" aria-expanded="false" aria-haspopup="true">
          회사소개
          <svg class="lc-nav-dropdown__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div class="lc-nav-dropdown__menu" role="menu">
          <?php foreach (lc_nav_company_items() as $item) {
              $cls = 'lc-nav-dropdown__link';
              if ($lc_active_nav === $item['id']) {
                  $cls .= ' is-active';
              }
              ?>
          <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($cls); ?>" role="menuitem"><?php echo lc_h($item['label']); ?></a>
          <?php } ?>
        </div>
      </div>

      <?php foreach (lc_nav_items_public() as $item) {
          $cls = 'lc-nav__link';
          if ($lc_active_nav === $item['id']) {
              $cls .= ' is-active';
          }
          if ($item['tone'] === 'partner') {
              $cls .= ' lc-nav__link--partner';
          } elseif ($item['tone'] === 'merchant') {
              $cls .= ' lc-nav__link--merchant';
          }
          ?>
      <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($cls); ?>"><?php echo lc_h($item['label']); ?></a>
      <?php } ?>
    </nav>

    <div class="lc-header__actions-wrap">
      <?php lc_render_header_actions(false); ?>
    </div>
    <div class="lc-header__admin-wrap">
      <?php lc_ui_admin_header_btn(); ?>
    </div>

    <button type="button" class="lc-nav-toggle" data-lc-nav-toggle aria-expanded="false" aria-controls="lcMobileNav" aria-label="메뉴 열기">
      <span class="lc-nav-toggle__icon" aria-hidden="true">
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
        <span class="lc-nav-toggle__bar"></span>
      </span>
    </button>
  </div>

  <div class="lc-nav-mobile" id="lcMobileNav" data-lc-mobile-nav hidden>
    <nav class="lc-nav-mobile__menu" aria-label="모바일 메뉴">
      <p class="lc-nav-mobile__group">회사소개</p>
      <?php foreach (lc_nav_company_items() as $item) {
          $cls = 'lc-nav-mobile__link';
          if ($lc_active_nav === $item['id']) {
              $cls .= ' is-active';
          }
          ?>
      <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($cls); ?>" data-lc-mobile-link><?php echo lc_h($item['label']); ?></a>
      <?php } ?>

      <p class="lc-nav-mobile__group">캠페인</p>
      <?php foreach (lc_nav_items_public() as $item) {
          if (!in_array($item['id'], array('cpa', 'cps', 'events'), true)) {
              continue;
          }
          $cls = 'lc-nav-mobile__link';
          if ($lc_active_nav === $item['id']) {
              $cls .= ' is-active';
          }
          ?>
      <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($cls); ?>" data-lc-mobile-link><?php echo lc_h($item['label']); ?></a>
      <?php } ?>

      <p class="lc-nav-mobile__group">센터</p>
      <?php foreach (lc_nav_items_public() as $item) {
          if (!in_array($item['id'], array('partner', 'merchant'), true)) {
              continue;
          }
          $cls = 'lc-nav-mobile__link';
          if ($lc_active_nav === $item['id']) {
              $cls .= ' is-active';
          }
          if ($item['tone'] === 'partner') {
              $cls .= ' lc-nav-mobile__link--partner';
          } elseif ($item['tone'] === 'merchant') {
              $cls .= ' lc-nav-mobile__link--merchant';
          }
          ?>
      <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($cls); ?>" data-lc-mobile-link><?php echo lc_h($item['label']); ?></a>
      <?php } ?>
    </nav>
    <?php lc_render_header_actions(true); ?>
    <div class="lc-header__admin-wrap lc-header__admin-wrap--mobile">
      <?php lc_ui_admin_header_btn(); ?>
    </div>
  </div>
</header>
