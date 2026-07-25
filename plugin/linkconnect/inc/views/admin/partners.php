<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('파트너 계정·성과·정산 대기 현황을 관리합니다. (샘플 UI)');
lc_ui_admin_filter_bar('파트너 코드, 이름 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">파트너 목록</h2>';
echo '<button type="button" class="lc-btn lc-btn--primary lc-btn--sm">파트너 등록</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>파트너코드</th><th>이름</th><th class="lc-num">접수 DB</th><th class="lc-num">승인 DB</th>';
echo '<th class="lc-num">승인율</th><th class="lc-num">수익</th><th class="lc-num">정산대기</th><th>상태</th><th style="text-align:right">관리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_partners() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['code']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td class="lc-num">' . number_format((int) $row['received']) . '</td>';
    echo '<td class="lc-num lc-num--emerald">' . number_format((int) $row['approved']) . '</td>';
    echo '<td class="lc-num"><strong>' . lc_h($row['rate']) . '</strong></td>';
    echo '<td class="lc-num">' . lc_h($row['profit']) . '원</td>';
    echo '<td class="lc-num lc-num--cyan">' . lc_h($row['pending']) . '원</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right"><button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">상세</button></td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
