<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$lc_sidebar_active = isset($lc_sidebar_active) ? (string) $lc_sidebar_active : '';
$lc_center = LC_CENTER_PARTNER;
$items = lc_sidebar_items($lc_center);
?>
<div class="lc-sidebar-overlay" id="lcSidebarOverlay" data-lc-sidebar-overlay hidden></div>
<aside class="lc-sidebar lc-sidebar--partner" id="lcPartnerSidebar" aria-label="<?php echo lc_h(lc_center_label($lc_center)); ?> 메뉴">
  <p class="lc-sidebar__title"><?php echo lc_h(lc_center_label($lc_center)); ?></p>
  <nav class="lc-sidebar__nav">
    <?php foreach ($items as $item) { ?>
    <a href="<?php echo lc_h($item['url']); ?>" class="lc-sidebar__link <?php echo $lc_sidebar_active === $item['id'] ? 'is-active' : ''; ?>" data-lc-sidebar-link>
      <span class="lc-sidebar__icon" aria-hidden="true"><?php echo lc_h($item['icon']); ?></span>
      <span><?php echo lc_h($item['label']); ?></span>
    </a>
    <?php } ?>
  </nav>
</aside>
