<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('파트너센터 이용 문의를 남겨주세요. (샘플 UI)');
?>
<div class="lc-partner-split">
  <div class="lc-panel--partner">
    <h2 class="lc-panel__title">문의 작성</h2>
    <form class="lc-form" style="max-width:none" onsubmit="return false;">
      <label>문의 유형
        <select class="lc-input" disabled>
          <option>정산</option>
          <option>디비/승인</option>
          <option>홍보 링크</option>
          <option>기타</option>
        </select>
      </label>
      <label>제목
        <input type="text" class="lc-input" value="정산 지급일 문의드립니다" readonly>
      </label>
      <label>내용
        <textarea class="lc-input" rows="6" readonly>10월 1일 정산 신청 건의 지급 예정일을 확인하고 싶습니다.</textarea>
      </label>
      <button type="button" class="lc-btn lc-btn--emerald">문의 등록 (샘플)</button>
    </form>
  </div>
  <div class="lc-panel--partner">
    <h2 class="lc-panel__title">문의 내역</h2>
    <?php foreach (lc_sample_partner_inquiries() as $inq) { ?>
    <div class="lc-inquiry-item">
      <div>
        <div class="lc-inquiry-item__title"><?php echo lc_h($inq['title']); ?></div>
        <div class="lc-inquiry-item__meta"><?php echo lc_h($inq['id']); ?> · <?php echo lc_h($inq['category']); ?> · <?php echo lc_h($inq['date']); ?></div>
      </div>
      <?php echo lc_ui_partner_status_badge($inq['status']); ?>
    </div>
    <?php } ?>
  </div>
</div>

<div class="lc-panel--partner">
  <h2 class="lc-panel__title">자주 묻는 질문</h2>
  <div class="lc-faq-grid">
    <?php foreach (lc_sample_partner_faq() as $faq) { ?>
    <article class="lc-faq-card">
      <p class="lc-faq-card__q">Q. <?php echo lc_h($faq['q']); ?></p>
      <p class="lc-faq-card__a"><?php echo lc_h($faq['a']); ?></p>
    </article>
    <?php } ?>
  </div>
</div>
