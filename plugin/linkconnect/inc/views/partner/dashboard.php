<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$conversions_url = lc_url('partner/conversions.php');
lc_ui_partner_intro('홍보 링크·유입·디비·수익을 한곳에서 관리합니다. (샘플 UI)');

$stats = array(
    array('label' => '오늘 클릭 수', 'value' => '1,248', 'icon' => '🖱', 'tone' => 'default'),
    array('label' => '오늘 접수 DB', 'value' => '32', 'icon' => '🎯', 'tone' => 'default'),
    array('label' => '승인완료 DB', 'value' => '21', 'icon' => '✅', 'tone' => 'default'),
    array('label' => '취소/무효 DB', 'value' => '4', 'icon' => '✕', 'tone' => 'red'),
    array('label' => '오늘 예상수익', 'value' => '960,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight'),
    array('label' => '이번 달 확정수익', 'value' => '8,430,000', 'suffix' => '원', 'icon' => '📊', 'tone' => 'dark'),
);
lc_ui_summary_grid($stats, '6');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">최근 7일 성과 추이</h2>';
lc_ui_partner_chart_7d(lc_sample_partner_chart_7d());
echo '</div>';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">유입경로 분석</h2>';
lc_ui_inflow_bars(lc_sample_inflow());
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_url('partner/traffic.php')) . '" style="width:100%;margin-top:1rem;justify-content:center">상세 리포트 보기 →</a>';
echo '</div></div>';

echo '<div class="lc-partner-split lc-partner-split--db">';
echo '<div class="lc-panel--partner">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">최근 접수 디비</h2>';
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h($conversions_url) . '">전체보기</a></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>접수일</th><th>상품명</th><th>고객명</th><th>연락처</th><th>상태</th><th style="text-align:right">예상수익</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_partner_dbs() as $row) {
    $strike = !empty($row['settled']) ? ' style="color:var(--lc-slate-400);text-decoration:line-through"' : '';
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td' . $strike . '>' . lc_h($row['campaign']) . '</td>';
    echo '<td class="lc-mask">' . lc_h($row['name']) . '</td>';
    echo '<td class="lc-mask">' . lc_h($row['phone']) . '</td>';
    echo '<td>' . lc_ui_partner_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_format_won($row['est_revenue']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
lc_ui_settlement_card();
echo '</div>';
