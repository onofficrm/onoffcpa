<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('광고주 취소 요청과 파트너 이의신청을 검수합니다. (샘플 UI)');
lc_ui_admin_filter_bar('DB ID, 취소사유 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">취소/무효 검수 대기</h2>';
echo '<span class="lc-admin-badge lc-admin-badge--orange">대기 18건</span></div>';

foreach (lc_sample_admin_cancel_reviews() as $row) {
    $pending = ($row['status'] === '검수대기');
    ?>
<article class="lc-admin-review-card">
  <div class="lc-admin-review-card__meta">
    <span><code><?php echo lc_h($row['id']); ?></code></span>
    <span><?php echo lc_h($row['date']); ?></span>
    <span><?php echo lc_h($row['campaign']); ?></span>
    <span><?php echo lc_h($row['merchant']); ?></span>
    <span>파트너 <?php echo lc_h($row['partner']); ?></span>
    <?php echo lc_ui_admin_status_badge($row['status']); ?>
  </div>
  <div class="lc-admin-review-card__block">
    <strong>취소 사유</strong>
    <?php echo lc_h($row['reason']); ?>
  </div>
  <div class="lc-admin-review-card__block">
    <strong>광고주 코멘트</strong>
    <?php echo lc_h($row['merchant_comment']); ?>
  </div>
  <div class="lc-admin-review-card__block">
    <strong>파트너 이의신청</strong>
    <?php echo lc_h($row['partner_appeal']); ?>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:0.75rem">
    <?php echo lc_ui_admin_review_actions($pending); ?>
  </div>
</article>
    <?php
}
echo '</div>';
