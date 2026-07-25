<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$dbs = lc_sample_merchant_dbs();
$detail_db = $dbs[0];

lc_ui_merchant_intro('접수된 디비를 확인하고 승인 또는 취소/무효 처리를 진행하세요.');

lc_ui_merchant_summary_grid(array(
    array('label' => '신규접수', 'value' => '17', 'suffix' => '건', 'icon' => '📥'),
    array('label' => '확인중', 'value' => '9', 'suffix' => '건', 'icon' => '⏳', 'color' => 'blue'),
    array('label' => '승인완료', 'value' => '21', 'suffix' => '건', 'icon' => '✅', 'tone' => 'highlight'),
    array('label' => '취소/무효', 'value' => '4', 'suffix' => '건', 'icon' => '✕', 'tone' => 'red'),
    array('label' => '오늘 처리 필요', 'value' => '9', 'suffix' => '건', 'icon' => '⚠', 'tone' => 'highlight', 'color' => 'cyan'),
    array('label' => '오늘 사용 광고비', 'value' => '300,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'dark'),
), '6');

lc_ui_merchant_alert(9);

lc_ui_partner_filter_bar('고객명, 연락처 검색');

echo '<div class="lc-panel--partner">';
echo '<div class="lc-panel__head"><span class="lc-muted">총 <strong style="color:var(--lc-cyan)">' . count($dbs) . '</strong>건 조회</span>';
echo '<button type="button" class="lc-btn lc-btn--sm lc-btn--outline">엑셀 다운로드</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>접수일</th><th>DB ID</th><th>광고상품</th><th>고객명</th><th>연락처</th><th>유입경로</th><th>상태</th>';
echo '<th style="text-align:right">단가</th><th style="text-align:right">처리</th>';
echo '</tr></thead><tbody>';
foreach ($dbs as $row) {
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['campaign']) . '</strong></td>';
    echo '<td>' . lc_h($row['name']) . '</td>';
    echo '<td class="lc-mask">' . lc_h($row['phone']) . '</td>';
    echo '<td>' . lc_h($row['channel']) . '</td>';
    echo '<td>' . lc_ui_merchant_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . number_format((int) $row['price']) . '원</td>';
    echo '<td style="text-align:right">' . lc_ui_merchant_db_actions(!empty($row['needs_action']), true) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

lc_ui_merchant_db_modal($detail_db);
