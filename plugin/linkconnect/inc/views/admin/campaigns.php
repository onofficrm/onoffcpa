<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('CPA 광고상품 단가·마진·승인율을 관리합니다. (샘플 UI)');
lc_ui_admin_filter_bar('광고상품명, 광고주 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">CPA 광고상품 목록</h2>';
echo '<button type="button" class="lc-btn lc-btn--primary lc-btn--sm">상품 등록</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>ID</th><th>광고상품명</th><th>광고주</th><th class="lc-num">파트너단가</th>';
echo '<th class="lc-num">광고주차감</th><th class="lc-num">관리자마진</th><th class="lc-num">승인율</th><th>상태</th><th style="text-align:right">관리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_campaigns() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td>' . lc_h($row['merchant']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['partner_price']) . '원</td>';
    echo '<td class="lc-num">' . lc_h($row['merchant_price']) . '원</td>';
    echo '<td class="lc-num lc-num--cyan"><strong>' . lc_h($row['margin']) . '원</strong></td>';
    echo '<td class="lc-num">' . lc_h($row['rate']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right"><button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">수정</button></td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
