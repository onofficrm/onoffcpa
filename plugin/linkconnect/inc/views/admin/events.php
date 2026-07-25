<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('이벤트·프로모션 등록, CPA 광고상품 연결, 리워드 지급을 관리합니다. (샘플 UI)');

echo '<div class="lc-admin-events-summary">';
echo '<div class="lc-admin-events-summary__item"><span>진행중 이벤트</span><strong>4</strong></div>';
echo '<div class="lc-admin-events-summary__item"><span>마감임박</span><strong style="color:#ea580c">1</strong></div>';
echo '<div class="lc-admin-events-summary__item"><span>참여 파트너</span><strong>3,795</strong></div>';
echo '<div class="lc-admin-events-summary__item"><span>지급 예정 리워드</span><strong style="color:var(--lc-cyan)">29.5M</strong></div>';
echo '</div>';

lc_ui_admin_filter_bar('이벤트명, 코드, CPA 상품 검색...');

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">이벤트 목록</h2>';
echo '<button type="button" class="lc-btn lc-btn--primary lc-btn--sm">+ 이벤트 등록</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>이벤트 코드</th><th>이벤트명</th><th>유형</th><th>적용 광고상품</th><th>기간</th>';
echo '<th class="lc-num">참여 파트너</th><th class="lc-num">발생 DB</th><th class="lc-num">승인 DB</th>';
echo '<th class="lc-num">지급 예정 리워드</th><th>상태</th><th style="text-align:right">관리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_events_list() as $row) {
    echo '<tr>';
    echo '<td><code>' . lc_h($row['code']) . '</code></td>';
    echo '<td><strong>' . lc_h($row['title']) . '</strong></td>';
    echo '<td>' . lc_ui_admin_event_type_badge($row['type']) . '</td>';
    echo '<td class="lc-campaign-link">' . lc_h($row['campaigns']) . '</td>';
    echo '<td class="lc-muted">' . lc_h($row['period']) . '</td>';
    echo '<td class="lc-num">' . number_format((int) $row['partners']) . '</td>';
    echo '<td class="lc-num">' . number_format((int) $row['received']) . '</td>';
    echo '<td class="lc-num lc-num--emerald">' . number_format((int) $row['approved']) . '</td>';
    echo '<td class="lc-num lc-num--cyan">' . ($row['reward_pending'] === '—' ? '—' : lc_h($row['reward_pending']) . '원') . '</td>';
    echo '<td>' . lc_ui_admin_event_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right"><button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">수정</button></td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div style="margin-bottom:1.25rem">';
lc_ui_admin_event_form('EVT-301');
echo '</div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<h2 class="lc-panel__title">참여 파트너 현황</h2>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>파트너</th><th>이벤트</th><th>참여일</th><th class="lc-num">발생 DB</th><th class="lc-num">승인 DB</th>';
echo '<th class="lc-num">달성률</th><th class="lc-num">예상 리워드</th><th>상태</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_event_partners() as $row) {
    echo '<tr>';
    echo '<td><strong>' . lc_h($row['partner']) . '</strong></td>';
    echo '<td>' . lc_h($row['event']) . '</td>';
    echo '<td class="lc-muted">' . lc_h($row['joined']) . '</td>';
    echo '<td class="lc-num">' . (int) $row['received'] . '</td>';
    echo '<td class="lc-num lc-num--emerald">' . (int) $row['approved'] . '</td>';
    echo '<td class="lc-num">' . lc_h($row['progress']) . '</td>';
    echo '<td class="lc-num lc-num--cyan">' . ($row['reward'] === '—' ? '—' : lc_h($row['reward']) . '원') . '</td>';
    echo '<td>' . lc_ui_admin_event_status_badge($row['status']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">이벤트 성과 관리</h2>';
echo '<button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">엑셀 다운로드</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>이벤트</th><th>연결 CPA 상품</th><th class="lc-num">발생 DB</th><th class="lc-num">승인 DB</th>';
echo '<th class="lc-num">승인율</th><th class="lc-num">매출</th><th class="lc-num">보너스 지급</th><th class="lc-num">관리자 마진</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_event_performance() as $row) {
    echo '<tr>';
    echo '<td><strong>' . lc_h($row['event']) . '</strong></td>';
    echo '<td class="lc-campaign-link">' . lc_h($row['campaign']) . '</td>';
    echo '<td class="lc-num">' . number_format((int) $row['received']) . '</td>';
    echo '<td class="lc-num lc-num--emerald">' . number_format((int) $row['approved']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['rate']) . '</td>';
    echo '<td class="lc-num">' . lc_h($row['revenue']) . '원</td>';
    echo '<td class="lc-num lc-num--red">' . lc_h($row['bonus_paid']) . '원</td>';
    echo '<td class="lc-num lc-num--cyan">' . lc_h($row['margin']) . '원</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">리워드 지급 관리</h2>';
echo '<span class="lc-admin-badge lc-admin-badge--yellow">지급대기 2건</span></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>지급 ID</th><th>파트너</th><th>이벤트</th><th>달성 조건</th><th class="lc-num">금액</th><th>상태</th><th>신청일</th><th style="text-align:right">처리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_event_rewards() as $row) {
    $can_pay = ($row['status'] === '지급대기' || $row['status'] === '검수중');
    echo '<tr>';
    echo '<td><code>' . lc_h($row['id']) . '</code></td>';
    echo '<td>' . lc_h($row['partner']) . '</td>';
    echo '<td>' . lc_h($row['event']) . '</td>';
    echo '<td class="lc-muted">' . lc_h($row['condition']) . '</td>';
    echo '<td class="lc-num lc-num--cyan"><strong>' . lc_h($row['amount']) . '원</strong></td>';
    echo '<td>' . lc_ui_admin_event_status_badge($row['status']) . '</td>';
    echo '<td class="lc-muted">' . lc_h($row['date']) . '</td>';
    echo '<td style="text-align:right">';
    if ($can_pay) {
        echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--approve">지급</button> ';
        echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--reject">반려</button>';
    } else {
        echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">상세</button>';
    }
    echo '</td></tr>';
}
echo '</tbody></table></div></div>';
