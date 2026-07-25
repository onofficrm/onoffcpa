<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('광고주 광고비 충전·차감·환급을 관리합니다. (샘플 UI)');

lc_ui_admin_summary_grid(lc_sample_admin_wallet_summary(), '4');

echo '<div class="lc-admin-split">';
echo '<div>';
echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">광고주별 광고비</h2>';
echo '<button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">충전 승인 대기</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>광고주</th><th class="lc-num">잔액</th><th class="lc-num">충전대기</th><th>상태</th><th style="text-align:right">처리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_wallet_merchants() as $row) {
    $bal_cls = ($row['status'] === '광고비부족') ? ' lc-num--red' : '';
    echo '<tr>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td class="lc-num' . $bal_cls . '"><strong>' . lc_h($row['balance']) . '원</strong></td>';
    echo '<td class="lc-num">' . ($row['pending_charge'] !== '0' ? lc_h($row['pending_charge']) . '원' : '—') . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_admin_wallet_actions($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<h2 class="lc-panel__title">거래 내역</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>일시</th><th>광고주</th><th>유형</th><th class="lc-num">금액</th><th>상태</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_wallet_history() as $row) {
    $amt_cls = (strpos($row['amount'], '+') === 0) ? ' lc-amount-plus' : ' lc-amount-minus';
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td>' . lc_h($row['merchant']) . '</td>';
    echo '<td>' . lc_h($row['type']) . '</td>';
    echo '<td class="lc-num' . $amt_cls . '">' . lc_h($row['amount']) . '원</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div></div>';

lc_ui_admin_manual_wallet();
echo '</div>';
