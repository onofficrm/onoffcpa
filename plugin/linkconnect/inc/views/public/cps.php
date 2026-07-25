<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
?>
<section class="lc-coming-hero">
  <div class="lc-section__inner" style="position:relative;z-index:1">
    <div class="lc-coming-badge">🚧 추후 오픈 예정</div>
    <h1 class="lc-coming-hero__title">구매가 발생하면 수익이 쌓이는 <span>CPS 상품</span></h1>
    <p class="lc-section__lead" style="margin:0 auto;max-width:36rem;text-align:center">
      쇼핑몰·건강식품·뷰티·교육상품 등 구매 확정 시 수수료가 지급되는 CPS 캠페인을 준비 중입니다.
    </p>
  </div>
</section>

<div class="lc-page-wrap">
  <section class="lc-panel">
    <h2 class="lc-panel__title">CPS 수익 구조 (준비 중)</h2>
    <p class="lc-muted" style="margin-bottom:1.5rem">주문 발생 → 구매 확정 → 수수료 산정 → 정산 반영 순서로 운영됩니다.</p>
    <div class="lc-cps-flow">
      <?php foreach (lc_sample_cps_flow() as $i => $step) {
          $icons = array('🛒', '✅', '💹', '💳');
          ?>
      <article class="lc-cps-flow__step">
        <div class="lc-cps-flow__icon"><?php echo $icons[$i]; ?></div>
        <span class="lc-flow__num" style="margin-bottom:0.5rem"><?php echo lc_h($step['step']); ?></span>
        <h3 class="lc-cps-flow__title"><?php echo lc_h($step['title']); ?></h3>
        <p class="lc-cps-flow__desc"><?php echo lc_h($step['desc']); ?></p>
      </article>
      <?php } ?>
    </div>
  </section>

  <section class="lc-panel" style="text-align:center">
    <div style="margin-bottom:1rem"><?php echo lc_ui_badge('COMING SOON', 'amber'); ?></div>
    <h2 class="lc-panel__title">CPS 캠페인 오픈 알림 신청</h2>
    <p class="lc-muted" style="max-width:28rem;margin:0 auto 1.5rem">
      CPS 상품 리스트, 수수료율, 쿠키 기간, 홍보 링크 생성 기능은 추후 업데이트 예정입니다.
      광고주 사전 문의를 남겨주시면 오픈 시 우선 안내해 드립니다.
    </p>
    <form class="lc-form" style="margin:0 auto" onsubmit="return false;">
      <label>이메일<input type="email" class="lc-input" value="partner@example.com" readonly></label>
      <label>관심 카테고리
        <select class="lc-input" disabled>
          <option>쇼핑몰 / 건강식품 / 뷰티 / 교육상품</option>
        </select>
      </label>
      <button type="button" class="lc-btn lc-btn--primary">사전 알림 신청 (샘플)</button>
    </form>
  </section>

  <section class="lc-final-cta" style="border-radius:var(--lc-radius-lg);margin-top:1.25rem">
    <h2 class="lc-final-cta__title" style="font-size:1.5rem">CPS 광고주이신가요?</h2>
    <p class="lc-final-cta__lead">오픈 전 사전 상담을 통해 캠페인 구조와 수수료 정책을 미리 설계해 보세요.</p>
    <div class="lc-final-cta__actions">
      <a class="lc-btn lc-btn--white" href="<?php echo lc_h(lc_inquiry_url()); ?>">광고주 사전문의</a>
      <a class="lc-btn lc-btn--emerald-dark" href="<?php echo lc_h(lc_url('pages/cpa.php')); ?>">지금 가능한 CPA 보기</a>
    </div>
  </section>
</div>
