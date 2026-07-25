<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('확정수익을 기준으로 정산 가능 금액을 확인하고 정산을 신청하세요.');
lc_ui_summary_grid(array(
    array('label' => '이번 달 확정수익', 'value' => '8,430,000', 'suffix' => '원', 'icon' => '🧮', 'tone' => 'default'),
    array('label' => '정산 완료 금액', 'value' => '23,500,000', 'suffix' => '원', 'icon' => '✅', 'tone' => 'default'),
    array('label' => '정산 대기 금액', 'value' => '1,200,000', 'suffix' => '원', 'icon' => '⏳', 'tone' => 'default'),
    array('label' => '정산 가능 금액', 'value' => '7,230,000', 'suffix' => '원', 'icon' => '💳', 'tone' => 'highlight'),
), '4');
?>
<div class="lc-partner-split">
  <div class="lc-panel--partner">
    <h2 class="lc-panel__title">정산 신청</h2>
    <form class="lc-form" style="max-width:none" onsubmit="return false;">
      <label>정산 신청 금액 *
        <input type="text" class="lc-input" value="7,230,000" readonly style="font-size:1.1rem;font-weight:800">
      </label>
      <p class="lc-muted" style="margin:-0.5rem 0 0;font-size:0.75rem">최소 정산 가능 금액: 50,000원 · <button type="button" class="lc-btn lc-btn--ghost lc-btn--sm" style="padding:0;font-size:0.75rem">전액 입력</button></p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
        <label>은행명 *
          <select class="lc-input" disabled><option>신한은행</option></select>
        </label>
        <label>예금주 *
          <input type="text" class="lc-input" value="김파트너" readonly>
        </label>
      </div>
      <label>계좌번호 *
        <input type="text" class="lc-input" value="110-123-******" readonly>
      </label>
      <label>메모 (선택)
        <textarea class="lc-input" rows="3" readonly placeholder="관리자에게 남길 메모"></textarea>
      </label>
      <button type="button" class="lc-btn lc-btn--emerald">정산 신청하기 (샘플)</button>
    </form>
    <div class="lc-placeholder" style="margin-top:1rem;font-size:0.8rem">
      ℹ 정산 신청 후 영업일 3~5일 내 지급됩니다. 세금 처리는 별도 안내에 따릅니다.
    </div>
  </div>
  <div class="lc-settle-card">
    <h2 class="lc-settle-card__title">정산 요약</h2>
    <div class="lc-settle-card__rows">
      <div class="lc-settle-card__row"><span>확정수익</span><strong>8,430,000 원</strong></div>
      <div class="lc-settle-card__row"><span>정산 대기</span><strong class="lc-settle-card__pending">1,200,000 원</strong></div>
      <div class="lc-settle-card__total"><span>신청 가능</span><strong>7,230,000 <small>원</small></strong></div>
    </div>
  </div>
</div>

<div class="lc-panel--partner">
  <h2 class="lc-panel__title">정산 신청 내역</h2>
  <div class="lc-table-wrap">
    <table class="lc-table lc-table--partner">
      <thead>
        <tr>
          <th>신청번호</th><th>신청일</th><th>신청금액</th><th>지급금액</th><th>상태</th><th>지급일</th><th>메모</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (lc_sample_partner_settlements() as $s) { ?>
        <tr>
          <td><code><?php echo lc_h($s['id']); ?></code></td>
          <td class="lc-muted"><?php echo lc_h($s['date']); ?></td>
          <td><?php echo number_format((int) $s['req_amount']); ?>원</td>
          <td style="font-weight:700"><?php echo number_format((int) $s['app_amount']); ?>원</td>
          <td><?php echo lc_ui_partner_status_badge($s['status']); ?></td>
          <td><?php echo lc_h($s['pay_date']); ?></td>
          <td class="lc-muted" style="font-size:0.8rem"><?php echo lc_h($s['memo']); ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>
