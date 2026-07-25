<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('전체 접수·승인·취소 디비와 수익 분배를 조회합니다. (샘플 UI)');
lc_ui_admin_filter_bar('DB ID, 고객명, 파트너 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">전체 디비 관리</h2>';
echo '<button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">엑셀 다운로드</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>DB ID</th><th>접수일</th><th>고객</th><th>연락처</th><th>파트너</th><th>광고주</th><th>상품</th>';
echo '<th>상태</th><th class="lc-num">파트너수익</th><th class="lc-num">광고주차감</th><th class="lc-num">마진</th><th>API</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_conversions() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td><strong>' . lc_h($row['customer']) . '</strong></td>';
    echo '<td class="lc-mask">' . lc_h($row['phone']) . '</td>';
    echo '<td><code>' . lc_h($row['partner']) . '</code></td>';
    echo '<td>' . lc_h($row['merchant']) . '</td>';
    echo '<td>' . lc_h($row['campaign']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['partner_profit']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['merchant_cost']) . '</td>';
    echo '<td class="lc-num lc-num--cyan">' . lc_h($row['margin']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['api']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
