<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('파트너 정산 신청을 승인·보류·반려·지급 처리합니다. (샘플 UI)');
lc_ui_admin_filter_bar('파트너, 정산 ID 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">정산 신청 목록</h2>';
echo '<span class="lc-admin-badge lc-admin-badge--yellow">승인대기 7건</span></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>정산 ID</th><th>파트너</th><th>정산월</th><th class="lc-num">신청금액</th>';
echo '<th class="lc-num">수수료</th><th class="lc-num">실지급</th><th>상태</th><th>신청일</th><th style="text-align:right">처리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_settlements() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['partner']) . '</strong></td>';
    echo '<td>' . lc_h($row['period']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['amount']) . '원</td>';
    echo '<td class="lc-num">' . lc_h($row['fee']) . '원</td>';
    echo '<td class="lc-num lc-num--cyan"><strong>' . lc_h($row['net']) . '원</strong></td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_admin_settlement_actions($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<h2 class="lc-panel__title">파트너 정산 상세 (샘플)</h2>';
echo '<dl class="lc-modal__dl" style="max-width:36rem">';
echo '<dt>파트너</dt><dd>PT-8832 김파트너</dd>';
echo '<dt>정산 기간</dt><dd>2026.06.01 ~ 2026.06.30</dd>';
echo '<dt>확정 DB</dt><dd>320건</dd>';
echo '<dt>총 수익</dt><dd><strong>8,430,000원</strong></dd>';
echo '<dt>정산 계좌</dt><dd>국민은행 123-456-789012 (김파트너)</dd>';
echo '<dt>메모</dt><dd>6월 정산 일괄 신청</dd>';
echo '</dl></div>';
