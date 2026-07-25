<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('CPA 캠페인을 검색하고 홍보 링크를 생성하세요. (샘플 데이터)');
lc_ui_filter_pills(lc_sample_cpa_categories(), 0);
?>
<div class="lc-search-bar">
  <div class="lc-search-bar__input">
    <span class="lc-search-bar__icon">🔍</span>
    <input type="text" placeholder="광고상품명 검색..." readonly>
  </div>
  <div class="lc-search-bar__filters">
    <?php foreach (array('단가 높은순', '승인율 높은순', '신규', '인기') as $f) { ?>
    <button type="button" class="lc-filter-btn"><?php echo lc_h($f); ?></button>
    <?php } ?>
  </div>
</div>
<?php lc_ui_cpa_campaign_grid(lc_sample_cpa_campaigns()); ?>
