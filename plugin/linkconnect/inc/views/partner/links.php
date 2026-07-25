<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('생성한 홍보 링크를 관리하고, 채널별 성과를 확인하세요.');
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
  <button type="button" class="lc-btn lc-btn--emerald">+ 새 홍보 링크 만들기</button>
</div>
<?php
lc_ui_summary_grid(array(
    array('label' => '전체 링크', 'value' => '4', 'suffix' => '개', 'icon' => '🔗', 'tone' => 'default'),
    array('label' => '총 클릭', 'value' => '4,518', 'icon' => '🖱', 'tone' => 'default'),
    array('label' => '총 접수 DB', 'value' => '94', 'icon' => '📥', 'tone' => 'default'),
    array('label' => '총 확정수익', 'value' => '1,860,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight'),
), '4');

foreach (lc_sample_partner_links_detail() as $link) {
    ?>
<article class="lc-link-card">
  <div class="lc-link-card__top">
    <h3 class="lc-link-card__title"><?php echo lc_h($link['campaign']); ?></h3>
    <?php echo lc_ui_partner_status_badge($link['status']); ?>
  </div>
  <p class="lc-link-card__meta">채널: <?php echo lc_h($link['channel']); ?> · sub_id: <code><?php echo lc_h($link['sub_id']); ?></code></p>
  <div class="lc-link-card__url">
    <code><?php echo lc_h($link['url']); ?></code>
    <button type="button" class="lc-btn lc-btn--sm lc-btn--outline">복사</button>
  </div>
  <div class="lc-link-card__stats">
    <div class="lc-link-card__stat"><div class="lc-link-card__stat-label">클릭</div><div class="lc-link-card__stat-value"><?php echo number_format((int) $link['clicks']); ?></div></div>
    <div class="lc-link-card__stat"><div class="lc-link-card__stat-label">접수 DB</div><div class="lc-link-card__stat-value"><?php echo (int) $link['received']; ?></div></div>
    <div class="lc-link-card__stat"><div class="lc-link-card__stat-label">승인 DB</div><div class="lc-link-card__stat-value lc-link-card__stat-value--emerald"><?php echo (int) $link['approved']; ?></div></div>
    <div class="lc-link-card__stat"><div class="lc-link-card__stat-label">수익(확정)</div><div class="lc-link-card__stat-value lc-link-card__stat-value--emerald"><?php echo number_format((int) $link['conf_revenue']); ?>원</div></div>
  </div>
</article>
    <?php
}
