<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_merchant_intro('광고비 잔액을 확인하고 충전·사용 내역을 관리하세요.');

lc_ui_merchant_summary_grid(array(
    array('label' => '광고비 잔액', 'value' => '2,350,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight', 'color' => 'cyan'),
    array('label' => '가차감 금액', 'value' => '450,000', 'suffix' => '원', 'icon' => '⏳'),
    array('label' => '사용 가능 잔액', 'value' => '1,900,000', 'suffix' => '원', 'icon' => '✓', 'tone' => 'dark'),
    array('label' => '이번 달 충전액', 'value' => '5,000,000', 'suffix' => '원', 'icon' => '➕'),
), '4');
?>
<div class="lc-partner-split">
  <div class="lc-panel--partner">
    <h2 class="lc-panel__title">광고비 충전 신청</h2>
    <form class="lc-form" style="max-width:none" onsubmit="return false;">
      <label>충전 금액 *
        <input type="text" class="lc-input" value="1,000,000" readonly style="font-size:1.1rem;font-weight:800">
      </label>
      <p class="lc-muted" style="margin:-0.35rem 0 0;font-size:0.75rem">최소 충전 금액: 100,000원</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
        <label>입금 은행
          <select class="lc-input" disabled><option>신한은행 110-xxx-xxxxx</option></select>
        </label>
        <label>입금자명 *
          <input type="text" class="lc-input" value="(주)리드앤솔루션" readonly>
        </label>
      </div>
      <label>메모 (선택)
        <textarea class="lc-input" rows="2" readonly placeholder="입금 확인 요청 메모"></textarea>
      </label>
      <button type="button" class="lc-btn lc-btn--primary">충전 신청하기 (샘플)</button>
    </form>
    <div class="lc-placeholder" style="margin-top:1rem;font-size:0.8rem">
      ℹ 충전 신청 후 안내된 계좌로 입금하시면 관리자 확인 후 잔액에 반영됩니다.
    </div>
  </div>
  <?php lc_ui_wallet_card(); ?>
</div>

<div class="lc-panel--partner">
  <h2 class="lc-panel__title">충전/차감/환급 내역</h2>
  <div class="lc-table-wrap">
    <table class="lc-table lc-table--partner">
      <thead>
        <tr>
          <th>일시</th><th>구분</th><th style="text-align:right">금액</th><th style="text-align:right">잔액</th><th>메모</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (lc_sample_merchant_wallet_history() as $h) {
            $amt_cls = (int) $h['amount'] < 0 ? 'lc-amount-minus' : 'lc-amount-plus';
            $type_map = array('충전' => '충전완료', '차감' => '차감완료', '환급' => '환급완료');
            $badge = isset($type_map[$h['type']]) ? $type_map[$h['type']] : $h['type'];
            ?>
        <tr>
          <td class="lc-muted"><?php echo lc_h($h['date']); ?></td>
          <td><?php echo lc_ui_merchant_status_badge($badge); ?></td>
          <td style="text-align:right" class="<?php echo lc_h($amt_cls); ?>"><?php echo ($h['amount'] > 0 ? '+' : '') . number_format((int) $h['amount']); ?>원</td>
          <td style="text-align:right;font-weight:700"><?php echo number_format((int) $h['balance']); ?>원</td>
          <td class="lc-muted" style="font-size:0.8rem"><?php echo lc_h($h['memo']); ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>
