<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_admin_intro('API 클라이언트·디비쉐어 연동·전송 로그를 관리합니다. (샘플 UI)');

echo '<div class="lc-api-card">';
foreach (lc_sample_admin_api_clients() as $row) {
    ?>
<div class="lc-api-card__item">
  <p class="lc-api-card__name"><?php echo lc_h($row['name']); ?></p>
  <p class="lc-api-card__meta"><?php echo lc_h($row['type']); ?> · <?php echo lc_h($row['endpoint']); ?></p>
  <p style="margin:0.5rem 0 0"><?php echo lc_ui_admin_status_badge($row['status']); ?></p>
  <p class="lc-api-card__meta" style="margin-top:0.35rem">성공률 <?php echo lc_h($row['success_rate']); ?> · 최근 <?php echo lc_h($row['last']); ?></p>
</div>
    <?php
}
echo '</div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<div class="lc-panel__head"><h2 class="lc-panel__title">API 로그</h2>';
echo '<button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">실패만 보기</button></div>';
echo '<div class="lc-table-wrap"><table class="lc-table lc-table--admin"><thead><tr>';
echo '<th>시간</th><th>클라이언트</th><th>DB ID</th><th>코드</th><th>메시지</th><th>결과</th><th style="text-align:right">재처리</th>';
echo '</tr></thead><tbody>';
foreach (lc_sample_admin_api_logs() as $row) {
    $failed = ($row['status'] === '실패');
    echo '<tr>';
    echo '<td class="lc-muted">' . lc_h($row['time']) . '</td>';
    echo '<td>' . lc_h($row['client']) . '</td>';
    echo '<td><code>' . lc_h($row['db_id']) . '</code></td>';
    echo '<td><code' . ($failed ? ' class="lc-num--red"' : '') . '>' . lc_h($row['code']) . '</code></td>';
    echo '<td>' . lc_h($row['msg']) . '</td>';
    echo '<td>' . lc_ui_admin_status_badge($row['status']) . '</td>';
    echo '<td style="text-align:right">' . lc_ui_admin_api_retry($failed) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="lc-panel lc-panel--admin">';
echo '<h2 class="lc-panel__title">API 클라이언트 등록 (샘플)</h2>';
echo '<div class="lc-admin-form-grid">';
echo '<label class="lc-admin-settings__field"><span class="lc-admin-settings__label">클라이언트명</span><input type="text" class="lc-input" readonly placeholder="광고주 CRM명"></label>';
echo '<label class="lc-admin-settings__field"><span class="lc-admin-settings__label">연동 유형</span><select class="lc-input" disabled><option>디비쉐어</option><option>REST</option></select></label>';
echo '<label class="lc-admin-settings__field"><span class="lc-admin-settings__label">Webhook URL</span><input type="url" class="lc-input" readonly></label>';
echo '<label class="lc-admin-settings__field"><span class="lc-admin-settings__label">API 토큰</span><input type="text" class="lc-input" readonly value="lc_••••••••••••"></label>';
echo '</div>';
echo '<div class="lc-admin-settings__actions"><button type="button" class="lc-btn lc-btn--primary lc-btn--sm">클라이언트 저장</button></div>';
echo '</div>';
