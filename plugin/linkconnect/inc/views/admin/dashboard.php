<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('링크커넥트 CPA 운영 현황과 주요 이슈를 한눈에 확인하세요. (샘플 UI)');

lc_ui_admin_summary_grid(lc_sample_admin_dashboard_stats(), '8');

echo '<div class="lc-partner-split lc-partner-split--admin">';
echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">최근 7일 전체 성과</h2>';
echo '<select class="lc-input lc-admin-filter__select" disabled style="width:auto"><option>최근 7일</option><option>최근 30일</option></select></div>';
lc_ui_admin_chart_7d(lc_sample_admin_chart_7d());
echo '</div>';
echo '<div class="lc-panel lc-panel--admin">';
echo '<h2 class="lc-panel__title">처리 필요 알림</h2>';
lc_ui_admin_alerts(lc_sample_admin_alerts());
echo '</div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">광고상품별 실적</h2>';
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_url('admin/campaigns.php')) . '">더보기</a></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>광고상품명</th><th>광고주</th><th class="lc-num">접수</th><th class="lc-num">승인</th><th class="lc-num">취소</th>';
echo '<th class="lc-num">승인율</th><th class="lc-num">매출</th><th class="lc-num">파트너수익</th><th class="lc-num">마진</th><th>상태</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_campaign_perf() as $row) {
    echo '<tr>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td>' . lc_h($row['merchant']) . '</td>';
    echo '<td class="lc-num">' . number_format((int) $row['received']) . '</td>';
    echo '<td class="lc-num lc-num--emerald">' . number_format((int) $row['approved']) . '</td>';
    echo '<td class="lc-num lc-num--red">' . number_format((int) $row['canceled']) . '</td>';
    echo '<td class="lc-num"><strong>' . lc_h($row['rate']) . '</strong></td>';
    echo '<td class="lc-num">' . lc_h($row['revenue']) . '원</td>';
    echo '<td class="lc-num">' . lc_h($row['partner_profit']) . '원</td>';
    echo '<td class="lc-num lc-num--cyan">' . lc_h($row['margin']) . '원</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">최근 접수 디비</h2>';
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_url('admin/conversions.php')) . '">전체 보기</a></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>접수일</th><th>상품</th><th>파트너</th><th>광고주</th><th>고객</th><th>상태</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_recent_dbs() as $row) {
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td>' . lc_h($row['campaign']) . '</td>';
    echo '<td><code>' . lc_h($row['partner']) . '</code></td>';
    echo '<td>' . lc_h($row['merchant']) . '</td>';
    echo '<td>' . lc_h($row['customer']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">API 오류</h2>';
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_url('admin/api.php')) . '">API 관리</a></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>시간</th><th>클라이언트</th><th>코드</th><th>메시지</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_api_errors() as $row) {
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['time']) . '</td>';
    echo '<td>' . lc_h($row['name']) . '</td>';
    echo '<td><code class="lc-num--red">' . lc_h($row['code']) . '</code></td>';
    echo '<td>' . lc_h($row['msg']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div></div>';
