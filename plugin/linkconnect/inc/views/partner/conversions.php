<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('접수된 디비의 상태와 수익 반영 여부를 확인할 수 있습니다. 개인정보는 마스킹 처리됩니다.');

lc_ui_summary_grid(array(
    array('label' => '전체 접수 DB', 'value' => '1,248', 'suffix' => '건', 'icon' => '📋', 'tone' => 'default'),
    array('label' => '승인대기', 'value' => '145', 'suffix' => '건', 'icon' => '⏳', 'tone' => 'default'),
    array('label' => '승인완료', 'value' => '892', 'suffix' => '건', 'icon' => '✅', 'tone' => 'highlight'),
    array('label' => '취소/무효', 'value' => '211', 'suffix' => '건', 'icon' => '✕', 'tone' => 'red'),
    array('label' => '예상수익', 'value' => '960,000', 'suffix' => '원', 'icon' => '💹', 'tone' => 'default'),
    array('label' => '확정수익', 'value' => '630,000', 'suffix' => '원', 'icon' => '🎯', 'tone' => 'highlight'),
), '6');

lc_ui_partner_filter_bar('고객명, 연락처 검색 (마스킹)');

echo '<div class="lc-panel--partner">';
echo '<div class="lc-panel__head"><span class="lc-muted">총 <strong style="color:var(--lc-emerald)">' . count(lc_sample_partner_dbs()) . '</strong>건 조회</span>';
echo '<button type="button" class="lc-btn lc-btn--sm lc-btn--outline">엑셀 다운로드</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--partner"><thead><tr>';
echo '<th>접수일</th><th>광고상품</th><th>고객명</th><th>연락처</th><th>유입경로</th><th>상태</th>';
echo '<th style="text-align:right">단가</th><th style="text-align:right">예상수익</th><th style="text-align:right">확정수익</th><th>코멘트</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_partner_dbs() as $row) {
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td><strong>' . lc_h($row['campaign']) . '</strong></td>';
    echo '<td class="lc-mask">' . lc_h($row['name']) . '</td>';
    echo '<td class="lc-mask">' . lc_h($row['phone']) . '</td>';
    echo '<td>' . lc_h($row['channel']) . '</td>';
    echo '<td>' . lc_ui_partner_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . number_format((int) $row['price']) . '원</td>';
    echo '<td style="text-align:right">' . lc_ui_format_won($row['est_revenue']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_format_won($row['conf_revenue']) . '</td>';
    echo '<td class="lc-muted" style="font-size:0.8rem">' . lc_h($row['comment'] !== '' ? $row['comment'] : '-') . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';
