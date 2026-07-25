<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('예상·확정 수익과 정산 가능 금액을 확인하세요.');
lc_ui_summary_grid(lc_sample_partner_earnings(), '4');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">월별 수익 추이</h2>';
$months = array('5월', '6월', '7월', '8월', '9월', '10월');
echo '<div class="lc-chart-placeholder__bars" style="height:10rem">';
foreach (array(55, 68, 72, 80, 88, 95) as $h) {
    echo '<span style="height:' . (int) $h . '%"></span>';
}
echo '</div>';
echo '<div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--lc-slate-500)">';
foreach ($months as $m) {
    echo '<span>' . lc_h($m) . '</span>';
}
echo '</div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">수익 구성</h2>';
$breakdown = array(
    array('label' => '승인완료 수익', 'value' => '8,430,000원', 'pct' => 92),
    array('label' => '검수중 예상', 'value' => '690,000원', 'pct' => 8),
    array('label' => '취소/무효 차감', 'value' => '-690,000원', 'pct' => 0),
);
foreach ($breakdown as $b) {
    echo '<div class="lc-inflow__row"><span>' . lc_h($b['label']) . '</span>';
    if ($b['pct'] > 0) {
        echo '<div class="lc-inflow__track"><span class="lc-inflow__bar" style="width:' . (int) $b['pct'] . '%"></span></div>';
    } else {
        echo '<div class="lc-inflow__track"></div>';
    }
    echo '<span style="font-weight:700;min-width:6rem;text-align:right">' . lc_h($b['value']) . '</span></div>';
}
echo '</div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">캠페인별 수익 TOP 5</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>캠페인</th><th>승인 DB</th><th style="text-align:right">확정수익</th>';
echo '</tr></thead><tbody>';
$top = array(
    array('campaign' => '개인회생 상담 DB', 'dbs' => 58, 'revenue' => 1740000),
    array('campaign' => '소상공인 대출 상담', 'dbs' => 30, 'revenue' => 960000),
    array('campaign' => '자동차 렌트 상담', 'dbs' => 22, 'revenue' => 550000),
    array('campaign' => '어린이 영어캠프', 'dbs' => 15, 'revenue' => 525000),
    array('campaign' => '임플란트 상담', 'dbs' => 8, 'revenue' => 320000),
);
foreach ($top as $t) {
    echo '<tr><td>' . lc_h($t['campaign']) . '</td><td>' . (int) $t['dbs'] . '건</td>';
    echo '<td style="text-align:right;color:var(--lc-emerald);font-weight:700">' . number_format((int) $t['revenue']) . '원</td></tr>';
}
echo '</tbody></table></div></div>';
