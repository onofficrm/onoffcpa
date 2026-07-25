<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$campaigns = lc_sample_cpa_campaigns();
$recommended = array();
$all = array();
foreach ($campaigns as $c) {
    if (!empty($c['recommended'])) {
        $recommended[] = $c;
    }
    $all[] = $c;
}
?>
<div class="lc-page-wrap lc-page-wrap--top">
  <?php lc_ui_page_head('CPA 광고상품', '상담 신청, 회원가입, 견적 문의 등 성과가 발생한 디비 기준으로 수익을 받을 수 있는 CPA 캠페인입니다. (샘플 데이터)'); ?>

  <?php lc_ui_filter_pills(lc_sample_cpa_categories(), 0); ?>

  <div class="lc-search-bar">
    <div class="lc-search-bar__input">
      <span class="lc-search-bar__icon" aria-hidden="true">🔍</span>
      <input type="text" placeholder="광고상품명 검색..." readonly>
    </div>
    <div class="lc-search-bar__filters">
      <?php foreach (array('단가 높은순', '승인율 높은순', '신규 캠페인', '인기 캠페인') as $f) { ?>
      <button type="button" class="lc-filter-btn"><?php echo lc_h($f); ?> ▾</button>
      <?php } ?>
    </div>
  </div>

  <section class="lc-panel" style="background:transparent;border:0;box-shadow:none;padding:0">
    <h2 class="lc-section-head__title">📈 추천 CPA 캠페인</h2>
    <?php lc_ui_cpa_campaign_grid($recommended); ?>
  </section>

  <div class="lc-divider"></div>

  <div class="lc-section-head">
    <h2 class="lc-section-head__title">전체 캠페인 <span class="lc-section-head__count">(<?php echo count($all); ?>)</span></h2>
  </div>
  <?php lc_ui_cpa_campaign_grid($all); ?>

  <div style="margin-top:2.5rem">
    <?php
    lc_ui_notice_panel('CPA 상품 홍보 전 꼭 확인해주세요.', array(
        '허용 채널과 금지 채널을 반드시 확인해주세요. 채널 위반 시 정산이 거절될 수 있습니다.',
        '허위광고, 과장광고로 발생한 디비는 전량 취소될 수 있으며 파트너 자격이 정지될 수 있습니다.',
        '중복 디비, 장난 신청, 연락 불가(결번 등) 디비는 무효 처리될 수 있습니다.',
        '광고주가 정상적인 상담/가입으로 승인한 디비만 확정수익으로 반영됩니다.',
    ));
    ?>
  </div>
</div>
