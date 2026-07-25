<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('광고주 계정·광고비·승인/취소율을 관리합니다. (샘플 UI)');
lc_ui_admin_filter_bar('광고주명, 코드 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">광고주 목록</h2>';
echo '<button type="button" class="lc-btn lc-btn--primary lc-btn--sm">광고주 등록</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>코드</th><th>광고주</th><th class="lc-num">광고비 잔액</th><th class="lc-num">사용 광고비</th>';
echo '<th class="lc-num">승인율</th><th class="lc-num">취소율</th><th>상태</th><th style="text-align:right">관리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_merchants() as $row) {
    $bal_cls = ($row['status'] === '광고비부족') ? ' lc-num--red' : '';
    echo '<tr>';
    echo '<td><code>' . lc_h($row['code']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td class="lc-num' . $bal_cls . '"><strong>' . lc_h($row['balance']) . '원</strong></td>';
    echo '<td class="lc-num">' . lc_h($row['spent']) . '원</td>';
    echo '<td class="lc-num lc-num--emerald">' . lc_h($row['rate']) . '</td>';
    echo '<td class="lc-num lc-num--red">' . lc_h($row['cancel_rate']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right"><button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">상세</button></td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
