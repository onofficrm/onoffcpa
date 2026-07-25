<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_merchant_intro('운영 중인 CPA 광고상품과 성과·광고비 사용 현황을 확인하세요.');
lc_ui_merchant_summary_grid(array(
    array('label' => '진행 상품', 'value' => '3', 'suffix' => '개', 'icon' => '◇'),
    array('label' => '이번 달 접수 DB', 'value' => '211', 'suffix' => '건', 'icon' => '📥'),
    array('label' => '평균 승인율', 'value' => '65', 'suffix' => '%', 'icon' => '📊', 'tone' => 'highlight'),
    array('label' => '이번 달 사용 광고비', 'value' => '2,650,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'dark'),
), '4');

foreach (lc_sample_merchant_campaigns() as $c) {
    ?>
<article class="lc-campaign-row">
  <div class="lc-campaign-row__top">
    <div>
      <h3 class="lc-campaign-row__title"><?php echo lc_h($c['title']); ?></h3>
      <span class="lc-muted" style="font-size:0.8rem"><?php echo lc_h($c['category']); ?> · CPA (DB접수)</span>
    </div>
    <?php echo lc_ui_merchant_status_badge($c['status']); ?>
  </div>
  <div class="lc-campaign-row__grid">
    <div class="lc-campaign-row__stat">
      <div class="lc-campaign-row__stat-label">디비당 차감</div>
      <div class="lc-campaign-row__stat-value lc-campaign-row__stat-value--cyan"><?php echo number_format((int) $c['price']); ?>원</div>
    </div>
    <div class="lc-campaign-row__stat">
      <div class="lc-campaign-row__stat-label">오늘 접수 DB</div>
      <div class="lc-campaign-row__stat-value"><?php echo (int) $c['today_dbs']; ?>건</div>
    </div>
    <div class="lc-campaign-row__stat">
      <div class="lc-campaign-row__stat-label">승인율</div>
      <div class="lc-campaign-row__stat-value lc-campaign-row__stat-value--emerald"><?php echo lc_h($c['approval_rate']); ?></div>
    </div>
    <div class="lc-campaign-row__stat">
      <div class="lc-campaign-row__stat-label">이번 달 사용 광고비</div>
      <div class="lc-campaign-row__stat-value"><?php echo number_format((int) $c['month_spend']); ?>원</div>
    </div>
  </div>
  <div style="margin-top:0.85rem;display:flex;gap:0.5rem;flex-wrap:wrap">
    <button type="button" class="lc-btn lc-btn--sm lc-btn--outline">상세 설정</button>
    <button type="button" class="lc-btn lc-btn--sm lc-btn--primary">성과 보기</button>
  </div>
</article>
    <?php
}
