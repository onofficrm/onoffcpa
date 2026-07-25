<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_merchant_intro('광고상품·파트너별 성과와 광고비 사용 현황을 분석하세요.');

lc_ui_merchant_summary_grid(array(
    array('label' => '접수 DB', 'value' => '211', 'suffix' => '건', 'icon' => '📥'),
    array('label' => '승인 DB', 'value' => '146', 'suffix' => '건', 'icon' => '✅', 'tone' => 'highlight'),
    array('label' => '취소 DB', 'value' => '35', 'suffix' => '건', 'icon' => '✕', 'tone' => 'red'),
    array('label' => '승인율', 'value' => '69.2', 'suffix' => '%', 'icon' => '📊', 'color' => 'cyan'),
    array('label' => '이번 달 광고비 사용', 'value' => '2,650,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'dark'),
    array('label' => 'DB당 평균 단가', 'value' => '18,150', 'suffix' => '원', 'icon' => '💹'),
), '6');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">일별 DB·광고비 추이</h2>';
lc_ui_merchant_chart_7d(lc_sample_merchant_chart_7d());
echo '</div>';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">광고비 사용 구성</h2>';
$spend = array(
    array('label' => '개인회생 상담 DB', 'pct' => 55, 'value' => '1,460,000원'),
    array('label' => '어린이 영어캠프', 'pct' => 20, 'value' => '530,000원'),
    array('label' => '자동차 렌트 상담', 'pct' => 15, 'value' => '400,000원'),
    array('label' => '기타', 'pct' => 10, 'value' => '260,000원'),
);
foreach ($spend as $s) {
    echo '<div class="lc-inflow__row"><span>' . lc_h($s['label']) . '</span>';
    echo '<div class="lc-inflow__track"><span class="lc-inflow__bar" style="width:' . (int) $s['pct'] . '%;background:linear-gradient(90deg,var(--lc-cyan),#3b82f6)"></span></div>';
    echo '<span style="font-weight:700;min-width:5.5rem;text-align:right;font-size:0.8rem">' . lc_h($s['value']) . '</span></div>';
}
echo '</div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">광고상품별 성과</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>광고상품</th><th>접수</th><th>승인</th><th>취소</th><th>승인율</th><th style="text-align:right">광고비 사용</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_merchant_report_campaigns() as $r) {
    echo '<tr>';
    echo '<td><strong>' . lc_h($r['campaign']) . '</strong></td>';
    echo '<td>' . (int) $r['received'] . '</td>';
    echo '<td style="color:var(--lc-emerald);font-weight:700">' . (int) $r['approved'] . '</td>';
    echo '<td style="color:var(--lc-red)">' . (int) $r['canceled'] . '</td>';
    echo '<td>' . lc_h($r['approval_rate']) . '</td>';
    echo '<td style="text-align:right;font-weight:700">' . number_format((int) $r['spend']) . '원</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">파트너별 성과</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>파트너</th><th>접수</th><th>승인</th><th>취소</th><th>승인율</th><th style="text-align:right">광고비 기여</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_merchant_report_partners() as $r) {
    echo '<tr>';
    echo '<td><code>' . lc_h($r['partner']) . '</code></td>';
    echo '<td>' . (int) $r['received'] . '</td>';
    echo '<td style="color:var(--lc-emerald);font-weight:700">' . (int) $r['approved'] . '</td>';
    echo '<td style="color:var(--lc-red)">' . (int) $r['canceled'] . '</td>';
    echo '<td>' . lc_h($r['approval_rate']) . '</td>';
    echo '<td style="text-align:right;font-weight:700">' . number_format((int) $r['spend']) . '원</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
