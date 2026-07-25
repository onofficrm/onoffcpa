<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$lc_sidebar_active = isset($lc_sidebar_active) ? (string) $lc_sidebar_active : '';
$lc_center = LC_CENTER_ADMIN;
$items = lc_sidebar_items($lc_center);
?>
<div class="lc-sidebar-overlay" id="lcSidebarOverlay" data-lc-sidebar-overlay hidden></div>
<aside class="lc-sidebar lc-sidebar--admin" id="lcAdminSidebar" aria-label="<?php echo lc_h(lc_center_label($lc_center)); ?> 메뉴">
  <p class="lc-sidebar__title"><?php echo lc_h(lc_center_label($lc_center)); ?></p>
  <nav class="lc-sidebar__nav">
    <?php foreach ($items as $item) {
        $link_cls = 'lc-sidebar__link';
        if ($lc_sidebar_active === $item['id']) {
            $link_cls .= ' is-active';
        }
        ?>
    <a href="<?php echo lc_h($item['url']); ?>" class="<?php echo lc_h($link_cls); ?>" data-lc-sidebar-link>
      <span class="lc-sidebar__link-main">
        <span class="lc-sidebar__icon" aria-hidden="true"><?php echo lc_h($item['icon']); ?></span>
        <span><?php echo lc_h($item['label']); ?></span>
      </span>
      <?php if (!empty($item['badge'])) { ?>
      <span class="lc-sidebar__badge"><?php echo lc_h($item['badge']); ?></span>
      <?php } ?>
    </a>
    <?php } ?>
  </nav>
</aside>
