<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$conversions_url = lc_url('merchant/conversions.php');
lc_ui_merchant_intro('캠페인·디비·광고비 현황을 한눈에 확인하세요. (샘플 UI)');

lc_ui_merchant_summary_grid(array(
    array('label' => '광고비 잔액', 'value' => '2,350,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight', 'color' => 'cyan'),
    array('label' => '가차감 광고비', 'value' => '450,000', 'suffix' => '원', 'icon' => '⏳'),
    array('label' => '사용 가능 잔액', 'value' => '1,900,000', 'suffix' => '원', 'icon' => '✓', 'tone' => 'dark'),
    array('label' => '오늘 접수 DB', 'value' => '17', 'suffix' => '건', 'icon' => '📥'),
    array('label' => '승인대기 DB', 'value' => '9', 'suffix' => '건', 'icon' => '⏱', 'color' => 'blue'),
    array('label' => '승인완료 DB', 'value' => '21', 'suffix' => '건', 'icon' => '✅', 'tone' => 'highlight'),
    array('label' => '오늘 사용 광고비', 'value' => '300,000', 'suffix' => '원', 'icon' => '📉'),
), '6');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel--partner"><h2 class="lc-panel__title">최근 7일 디비 처리 현황</h2>';
lc_ui_merchant_chart_7d(lc_sample_merchant_chart_7d());
echo '</div>';
lc_ui_wallet_card();
echo '</div>';

lc_ui_merchant_alert(9, $conversions_url);

echo '<div class="lc-panel--partner">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">최근 접수 디비</h2>';
echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h($conversions_url) . '">디비 관리 가기</a></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>접수일</th><th>고객명</th><th>연락처</th><th>상품명</th><th>상태</th><th style="text-align:right">처리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_merchant_dbs() as $row) {
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td><strong>' . lc_h($row['name']) . '</strong></td>';
    echo '<td class="lc-mask">' . lc_h($row['phone']) . '</td>';
    echo '<td>' . lc_h($row['campaign']) . '</td>';
    echo '<td>' . lc_ui_merchant_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_merchant_db_actions(!empty($row['needs_action']), true) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

lc_ui_merchant_db_modal(lc_sample_merchant_dbs()[0]);
