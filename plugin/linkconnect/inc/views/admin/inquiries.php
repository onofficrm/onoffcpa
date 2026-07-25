<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('파트너·광고주 문의를 확인하고 답변합니다. (샘플 UI)');
lc_ui_admin_filter_bar('제목, 발신자 검색...');

echo '<div class="lc-partner-split lc-partner-split--chart">';
echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">문의 목록</h2>';
echo '<span class="lc-admin-badge lc-admin-badge--orange">답변대기 2건</span></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>ID</th><th>센터</th><th>발신자</th><th>제목</th><th>등록일</th><th>상태</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_inquiries() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td>' . lc_h($row['center']) . '</td>';
    echo '<td>' . lc_h($row['from']) . '</td>';
    echo '<td><strong>' . lc_h($row['title']) . '</strong></td>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

echo '<div class="lc-panel lc-panel--admin" style="margin-top:1rem">';
echo '<h2 class="lc-panel__title">선택 문의 상세</h2>';
echo '<dl class="lc-modal__dl">';
echo '<dt>문의 ID</dt><dd>INQ-A101</dd>';
echo '<dt>센터</dt><dd>파트너센터</dd>';
echo '<dt>발신자</dt><dd>PT-8832 김파트너</dd>';
echo '<dt>제목</dt><dd>6월 정산 금액 확인 요청</dd>';
echo '<dt>내용</dt><dd>6월 확정 수익과 정산 신청 가능 금액이 대시보드와 다르게 표시됩니다. 확인 부탁드립니다.</dd>';
echo '</dl></div></div>';

lc_ui_admin_inquiry_reply();
echo '</div>';
