<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('클릭·유입·채널별 성과를 분석합니다. (샘플 데이터)');
lc_ui_partner_filter_bar('채널, sub_id 검색');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">유입경로 분석</h2>';
lc_ui_inflow_bars(lc_sample_inflow());
echo '</div>';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">일별 유입 추이</h2>';
lc_ui_partner_chart_7d(lc_sample_partner_chart_7d());
echo '</div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">채널별 성과</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>채널</th><th>클릭</th><th>접수 DB</th><th>승인 DB</th><th>전환율</th><th style="text-align:right">수익</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_partner_traffic_channels() as $row) {
    echo '<tr>';
    echo '<td><strong>' . lc_h($row['channel']) . '</strong></td>';
    echo '<td>' . number_format((int) $row['clicks']) . '</td>';
    echo '<td>' . (int) $row['dbs'] . '</td>';
    echo '<td style="color:var(--lc-emerald);font-weight:700">' . (int) $row['approved'] . '</td>';
    echo '<td>' . lc_h($row['cvr']) . '</td>';
    echo '<td style="text-align:right;font-weight:700">' . number_format((int) $row['revenue']) . '원</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">sub_id별 성과</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>sub_id</th><th>채널</th><th>클릭</th><th>접수 DB</th><th>승인 DB</th><th style="text-align:right">수익</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_partner_traffic_subid() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['sub_id']) . '</code></td>';
    echo '<td>' . lc_h($row['channel']) . '</td>';
    echo '<td>' . number_format((int) $row['clicks']) . '</td>';
    echo '<td>' . (int) $row['dbs'] . '</td>';
    echo '<td style="color:var(--lc-emerald);font-weight:700">' . (int) $row['approved'] . '</td>';
    echo '<td style="text-align:right;font-weight:700">' . number_format((int) $row['revenue']) . '원</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
