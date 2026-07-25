<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_ui_badge')) {
    function lc_ui_badge($text, $tone = 'default')
    {
        return '<span class="lc-badge lc-badge--' . lc_h($tone) . '">' . lc_h($text) . '</span>';
    }
}

if (!function_exists('lc_ui_status_badge')) {
    function lc_ui_status_badge($status)
    {
        $tones = array(
            LC_STATUS_PENDING  => 'amber',
            LC_STATUS_APPROVED => 'emerald',
            LC_STATUS_REJECTED => 'red',
            LC_STATUS_ACTIVE   => 'cyan',
            LC_STATUS_SETTLED  => 'slate',
        );
        $tone = isset($tones[$status]) ? $tones[$status] : 'default';

        return lc_ui_badge(lc_status_label($status), $tone);
    }
}

if (!function_exists('lc_ui_stat_grid')) {
    function lc_ui_stat_grid(array $items)
    {
        echo '<div class="lc-stat-grid">';
        foreach ($items as $item) {
            $tone = isset($item['tone']) ? $item['tone'] : 'default';
            echo '<article class="lc-stat lc-stat--' . lc_h($tone) . '">';
            echo '<p class="lc-stat__label">' . lc_h($item['label']) . '</p>';
            echo '<p class="lc-stat__value">' . lc_h($item['value']) . '</p>';
            echo '</article>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_table')) {
    function lc_ui_table(array $headers, array $rows)
    {
        echo '<div class="lc-table-wrap"><table class="lc-table"><thead><tr>';
        foreach ($headers as $h) {
            echo '<th>' . lc_h($h) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . $cell . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}

if (!function_exists('lc_ui_campaign_cards')) {
    function lc_ui_campaign_cards(array $items, $type = 'cpa')
    {
        echo '<div class="lc-card-grid">';
        foreach ($items as $item) {
            echo '<article class="lc-campaign-card">';
            echo '<div class="lc-campaign-card__top">';
            echo lc_ui_badge(isset($item['badge']) ? $item['badge'] : '', 'cyan');
            echo '<span class="lc-campaign-card__cat">' . lc_h(isset($item['category']) ? $item['category'] : (isset($item['brand']) ? $item['brand'] : '')) . '</span>';
            echo '</div>';
            $title = isset($item['title']) ? $item['title'] : (isset($item['brand']) ? $item['brand'] : '');
            echo '<h3 class="lc-campaign-card__title">' . lc_h($title) . '</h3>';
            if ($type === 'cpa') {
                echo '<p class="lc-campaign-card__meta">단가 <strong>' . number_format((int) $item['reward']) . '원</strong> · 승인율 ' . lc_h($item['approval']) . '</p>';
            } else {
                echo '<p class="lc-campaign-card__meta">수수료 <strong>' . lc_h($item['rate']) . '</strong> · 쿠키 ' . lc_h($item['cookie']) . '</p>';
            }
            echo '<button type="button" class="lc-btn lc-btn--sm lc-btn--primary">상세 보기</button>';
            echo '</article>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_event_list')) {
    function lc_ui_event_list(array $items)
    {
        echo '<div class="lc-event-list">';
        foreach ($items as $item) {
            echo '<article class="lc-event-item">';
            echo '<div>';
            echo lc_ui_badge($item['badge'], 'emerald') . ' ';
            echo '<strong>' . lc_h($item['title']) . '</strong>';
            echo '<p class="lc-muted">' . lc_h($item['period']) . '</p>';
            echo '</div>';
            echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_url('pages/events.php')) . '">자세히</a>';
            echo '</article>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_inflow_bars')) {
    function lc_ui_inflow_bars(array $items)
    {
        echo '<div class="lc-inflow">';
        foreach ($items as $item) {
            echo '<div class="lc-inflow__row">';
            echo '<span class="lc-inflow__label">' . lc_h($item['label']) . '</span>';
            echo '<div class="lc-inflow__track"><span class="lc-inflow__bar" style="width:' . (int) $item['pct'] . '%"></span></div>';
            echo '<span class="lc-inflow__pct">' . (int) $item['pct'] . '%</span>';
            echo '</div>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_chart_placeholder')) {
    function lc_ui_chart_placeholder($title = '성과 추이')
    {
        echo '<div class="lc-panel">';
        echo '<h2 class="lc-panel__title">' . lc_h($title) . '</h2>';
        echo '<div class="lc-chart-placeholder" aria-hidden="true">';
        echo '<div class="lc-chart-placeholder__bars">';
        for ($i = 0; $i < 7; $i++) {
            $h = 30 + ($i * 7) % 55;
            echo '<span style="height:' . $h . '%"></span>';
        }
        echo '</div>';
        echo '<p class="lc-muted">샘플 차트 영역 — 온오프빌더 차트 디자인 연동 예정</p>';
        echo '</div></div>';
    }
}

if (!function_exists('lc_ui_form_placeholder')) {
    function lc_ui_form_placeholder($title = '문의하기')
    {
        echo '<div class="lc-panel">';
        echo '<h2 class="lc-panel__title">' . lc_h($title) . '</h2>';
        echo '<form class="lc-form" onsubmit="return false;">';
        echo '<label>제목<input type="text" class="lc-input" value="샘플 문의 제목" readonly></label>';
        echo '<label>내용<textarea class="lc-input" rows="5" readonly>디자인 적용용 샘플 문의 폼입니다.</textarea></label>';
        echo '<button type="button" class="lc-btn lc-btn--primary">제출 (샘플)</button>';
        echo '</form></div>';
    }
}

if (!function_exists('lc_ui_page_head')) {
    function lc_ui_page_head($title, $desc = '')
    {
        echo '<div class="lc-page-head">';
        echo '<h1 class="lc-page-title">' . lc_h($title) . '</h1>';
        if ($desc !== '') {
            echo '<p class="lc-page-desc">' . lc_h($desc) . '</p>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_filter_pills')) {
    function lc_ui_filter_pills(array $items, $active_index = 0, $class = '')
    {
        echo '<div class="lc-pills' . ($class !== '' ? ' ' . lc_h($class) : '') . '" data-lc-pills>';
        foreach ($items as $i => $item) {
            $cls = 'lc-pill';
            if ($i === $active_index) {
                $cls .= ' is-active';
            }
            echo '<button type="button" class="' . lc_h($cls) . '" data-lc-pill>' . lc_h($item) . '</button>';
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_cpa_badge_class')) {
    function lc_ui_cpa_badge_class($badge)
    {
        if ($badge === '추천') {
            return 'emerald';
        }
        if ($badge === '인기') {
            return 'cyan';
        }
        if ($badge === '신규') {
            return 'purple';
        }

        return 'default';
    }
}

if (!function_exists('lc_ui_cpa_campaign_card')) {
    function lc_ui_cpa_campaign_card(array $item)
    {
        $badge = isset($item['badge']) ? $item['badge'] : '';
        $status = isset($item['status']) ? $item['status'] : '진행중';
        ?>
<article class="lc-cpa-card">
  <div class="lc-cpa-card__body">
    <div class="lc-cpa-card__top">
      <span class="lc-cpa-card__cat"><?php echo lc_h($item['category']); ?></span>
      <div class="lc-cpa-card__badges">
        <?php if ($badge !== '') {
            echo lc_ui_badge($badge, lc_ui_cpa_badge_class($badge));
        } ?>
        <span class="lc-cpa-card__status lc-cpa-card__status--<?php echo $status === '진행중' ? 'active' : 'closing'; ?>"><?php echo lc_h($status); ?></span>
      </div>
    </div>
    <h3 class="lc-cpa-card__title"><?php echo lc_h($item['title']); ?></h3>
    <p class="lc-cpa-card__type">유형: CPA (DB접수)</p>
    <div class="lc-cpa-card__metrics">
      <div class="lc-cpa-card__metric lc-cpa-card__metric--price">
        <span class="lc-cpa-card__metric-label">파트너 단가</span>
        <span class="lc-cpa-card__metric-value"><?php echo number_format((int) $item['price']); ?><small>원</small></span>
      </div>
      <div class="lc-cpa-card__metric">
        <span class="lc-cpa-card__metric-label">승인율 / 평균</span>
        <span class="lc-cpa-card__metric-value"><?php echo lc_h($item['approval_rate']); ?> <small>(<?php echo lc_h($item['avg_time']); ?>)</small></span>
      </div>
    </div>
    <div class="lc-cpa-card__channels">
      <div><span class="lc-cpa-card__ch-label">허용채널</span> <?php echo lc_h($item['allowed_channels']); ?></div>
      <div><span class="lc-cpa-card__ch-label lc-cpa-card__ch-label--danger">금지채널</span> <span class="lc-cpa-card__ch-danger"><?php echo lc_h($item['forbidden_channels']); ?></span></div>
    </div>
  </div>
  <div class="lc-cpa-card__foot">
    <button type="button" class="lc-btn lc-btn--sm lc-btn--outline">상세보기</button>
    <button type="button" class="lc-btn lc-btn--sm lc-btn--dark">🔗 홍보하기</button>
  </div>
</article>
        <?php
    }
}

if (!function_exists('lc_ui_cpa_campaign_grid')) {
    function lc_ui_cpa_campaign_grid(array $items)
    {
        echo '<div class="lc-cpa-grid">';
        foreach ($items as $item) {
            lc_ui_cpa_campaign_card($item);
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_notice_panel')) {
    function lc_ui_notice_panel($title, array $items)
    {
        echo '<section class="lc-notice-panel">';
        echo '<div class="lc-notice-panel__icon" aria-hidden="true">ℹ</div>';
        echo '<div><h3 class="lc-notice-panel__title">' . lc_h($title) . '</h3><ul class="lc-notice-panel__list">';
        foreach ($items as $item) {
            echo '<li>' . lc_h($item) . '</li>';
        }
        echo '</ul></div></section>';
    }
}

if (!function_exists('lc_ui_progress_card')) {
    function lc_ui_progress_card(array $item)
    {
        $tone = isset($item['tone']) ? $item['tone'] : 'default';
        $cls = 'lc-progress-card';
        if ($tone === 'done') {
            $cls .= ' lc-progress-card--done';
        }
        ?>
<article class="<?php echo lc_h($cls); ?>">
  <div class="lc-progress-card__info">
    <div class="lc-progress-card__meta">
      <?php echo lc_ui_badge($item['badge'], $tone === 'done' ? 'emerald' : 'cyan'); ?>
      <span class="lc-progress-card__period"><?php echo lc_h($item['period']); ?></span>
    </div>
    <h3 class="lc-progress-card__title"><?php echo lc_h($item['title']); ?></h3>
    <p class="lc-progress-card__desc"><?php echo lc_h($item['desc']); ?></p>
    <p class="lc-progress-card__alert"><?php echo lc_h($item['alert']); ?></p>
  </div>
  <div class="lc-progress-card__chart">
    <div class="lc-progress-card__chart-head">
      <span>진행률 <strong><?php echo (int) $item['pct']; ?>%</strong></span>
      <span><?php echo (int) $item['current']; ?> / <?php echo (int) $item['target']; ?>건</span>
    </div>
    <div class="lc-progress-bar"><span style="width:<?php echo (int) $item['pct']; ?>%"></span></div>
    <div class="lc-progress-card__reward">
      <span>예상 보너스</span>
      <strong><?php echo lc_h($item['reward']); ?></strong>
    </div>
  </div>
  <button type="button" class="lc-btn lc-btn--sm lc-btn--dark lc-progress-card__cta">추천 상품 보기</button>
</article>
        <?php
    }
}

if (!function_exists('lc_ui_event_badge')) {
    function lc_ui_event_badge($text)
    {
        $map = array(
            '진행중' => 'cyan', '마감임박' => 'amber', '단가 상승' => 'emerald',
            '파트너 보너스' => 'purple', '신규 캠페인' => 'cyan', '고승인율' => 'cyan',
        );
        $tone = isset($map[$text]) ? $map[$text] : 'default';

        return lc_ui_badge($text, $tone);
    }
}

if (!function_exists('lc_ui_promo_copy_tabs')) {
    function lc_ui_promo_copy_tabs(array $tabs)
    {
        if (empty($tabs)) {
            return;
        }
        ?>
<div class="lc-copy-tabs" data-lc-copy-tabs>
  <div class="lc-copy-tabs__nav" role="tablist">
    <?php foreach ($tabs as $i => $tab) { ?>
    <button type="button" class="lc-copy-tabs__btn <?php echo $i === 0 ? 'is-active' : ''; ?>" role="tab" data-lc-copy-tab="<?php echo lc_h($tab['id']); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo lc_h($tab['label']); ?></button>
    <?php } ?>
  </div>
  <?php foreach ($tabs as $i => $tab) { ?>
  <div class="lc-copy-tabs__panel <?php echo $i === 0 ? 'is-active' : ''; ?>" role="tabpanel" data-lc-copy-panel="<?php echo lc_h($tab['id']); ?>" <?php echo $i !== 0 ? 'hidden' : ''; ?>>
    <?php foreach ($tab['copies'] as $copy) { ?>
    <article class="lc-copy-tabs__card">
      <h4 class="lc-copy-tabs__card-title"><?php echo lc_h($copy['title']); ?></h4>
      <p class="lc-copy-tabs__card-text"><?php echo nl2br(lc_h($copy['text'])); ?></p>
      <button type="button" class="lc-btn lc-btn--sm lc-btn--outline" data-lc-copy-btn>복사</button>
    </article>
    <?php } ?>
  </div>
  <?php } ?>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_event_detail_preview')) {
    function lc_ui_event_detail_preview(array $item)
    {
        ?>
<div class="lc-preview-box lc-preview-box--event">
  <div class="lc-preview-box__head">
    <span>이벤트 상세 미리보기</span>
    <?php if (!empty($item['dday'])) { ?><span class="lc-preview-box__dday"><?php echo lc_h($item['dday']); ?></span><?php } ?>
  </div>
  <div class="lc-preview-box__body">
    <div class="lc-preview-box__badges">
      <?php foreach ($item['badges'] as $b) {
          echo lc_ui_event_badge($b);
      } ?>
    </div>
    <h3 class="lc-preview-box__title"><?php echo lc_h($item['title']); ?></h3>
    <dl class="lc-preview-box__dl">
      <dt>이벤트 기간</dt><dd><?php echo lc_h($item['period']); ?></dd>
      <dt>참여 조건</dt><dd><?php echo lc_h($item['condition']); ?></dd>
      <dt>혜택</dt><dd><strong class="lc-preview-box__benefit"><?php echo lc_h($item['benefit']); ?></strong></dd>
      <dt>적용 CPA 상품</dt>
      <dd>
        <ul class="lc-preview-box__products">
          <?php foreach ($item['products'] as $p) { ?>
          <li><?php echo lc_h($p); ?></li>
          <?php } ?>
        </ul>
      </dd>
    </dl>
    <p class="lc-muted" style="font-size:0.78rem;margin:0.85rem 0 0">※ 샘플 미리보기 — 실제 이벤트 상세 페이지 연동 예정</p>
  </div>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_event_rules_warn')) {
    function lc_ui_event_rules_warn(array $items)
    {
        ?>
<div class="lc-rules-warn">
  <div class="lc-rules-warn__banner" role="alert">
    <span class="lc-rules-warn__icon" aria-hidden="true">⚠</span>
    <div>
      <strong>이벤트 홍보 시 반드시 확인하세요</strong>
      <p>금지사항 위반 시 이벤트 참여 자격 박탈 및 리워드 지급이 취소될 수 있습니다.</p>
    </div>
  </div>
  <div class="lc-rules-checklist">
    <?php foreach ($items as $item) {
        $cls = !empty($item['critical']) ? ' lc-rules-checklist__item--critical' : '';
        ?>
    <label class="lc-rules-checklist__item<?php echo $cls; ?>">
      <input type="checkbox" disabled <?php echo !empty($item['critical']) ? 'checked' : ''; ?>>
      <span><?php echo lc_h($item['text']); ?></span>
    </label>
    <?php } ?>
  </div>
</div>
        <?php
    }
}

/* ── 파트너센터 UI ── */

if (!function_exists('lc_ui_partner_status_badge')) {
    function lc_ui_partner_status_badge($status)
    {
        $map = array(
            '접수완료' => 'default', '검수중' => 'cyan', '승인완료' => 'emerald', '승인대기' => 'cyan',
            '취소/무효' => 'red', '확정완료' => 'purple', '정산완료' => 'slate',
            '지급완료' => 'emerald', '반려' => 'red', '신청완료' => 'default',
            '답변대기' => 'amber', '답변완료' => 'emerald', '운영중' => 'emerald',
            '중지' => 'amber', '만료' => 'default', '보류' => 'amber',
        );
        $tone = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="lc-pstatus lc-pstatus--' . lc_h($tone) . '">' . lc_h($status) . '</span>';
    }
}

if (!function_exists('lc_ui_partner_intro')) {
    function lc_ui_partner_intro($desc)
    {
        echo '<p class="lc-partner-intro">' . lc_h($desc) . '</p>';
    }
}

if (!function_exists('lc_ui_summary_card')) {
    function lc_ui_summary_card(array $item)
    {
        $tone = isset($item['tone']) ? $item['tone'] : 'default';
        $icon = isset($item['icon']) ? $item['icon'] : '';
        $suffix = isset($item['suffix']) ? $item['suffix'] : '';
        $cls = 'lc-summary-card';
        if ($tone === 'highlight') {
            $cls .= ' lc-summary-card--highlight';
        }
        if ($tone === 'dark') {
            $cls .= ' lc-summary-card--dark';
        }
        if ($tone === 'red') {
            $cls .= ' lc-summary-card--red';
        }
        ?>
<article class="<?php echo lc_h($cls); ?>">
  <div class="lc-summary-card__head">
    <span class="lc-summary-card__label"><?php echo lc_h($item['label']); ?></span>
    <?php if ($icon !== '') { ?><span class="lc-summary-card__icon" aria-hidden="true"><?php echo $icon; ?></span><?php } ?>
  </div>
  <p class="lc-summary-card__value"><?php echo lc_h($item['value']); ?><?php if ($suffix !== '') { ?> <small><?php echo lc_h($suffix); ?></small><?php } ?></p>
</article>
        <?php
    }
}

if (!function_exists('lc_ui_summary_grid')) {
    function lc_ui_summary_grid(array $items, $cols = '')
    {
        $cls = 'lc-summary-grid';
        if ($cols !== '') {
            $cls .= ' lc-summary-grid--' . $cols;
        }
        echo '<div class="' . lc_h($cls) . '">';
        foreach ($items as $item) {
            lc_ui_summary_card($item);
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_partner_chart_7d')) {
    function lc_ui_partner_chart_7d(array $data)
    {
        $max = 1;
        foreach ($data as $d) {
            $max = max($max, (int) $d['click'], (int) $d['approval']);
        }
        echo '<div class="lc-partner-chart">';
        echo '<div class="lc-partner-chart__bars">';
        foreach ($data as $d) {
            $ch = round(((int) $d['click'] / $max) * 100);
            $ap = round(((int) $d['approval'] / $max) * 100);
            echo '<div class="lc-partner-chart__col">';
            echo '<div class="lc-partner-chart__stack">';
            echo '<span class="lc-partner-chart__bar lc-partner-chart__bar--click" style="height:' . (int) $ch . '%" title="클릭 ' . (int) $d['click'] . '"></span>';
            echo '<span class="lc-partner-chart__bar lc-partner-chart__bar--approval" style="height:' . (int) $ap . '%" title="승인 ' . (int) $d['approval'] . '"></span>';
            echo '</div>';
            echo '<span class="lc-partner-chart__label">' . lc_h($d['date']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="lc-partner-chart__legend"><span><i class="lc-partner-chart__dot lc-partner-chart__dot--click"></i> 클릭</span><span><i class="lc-partner-chart__dot lc-partner-chart__dot--approval"></i> 승인 DB</span></div>';
        echo '</div>';
    }
}

if (!function_exists('lc_ui_settlement_card')) {
    function lc_ui_settlement_card()
    {
        $url = lc_url('partner/settlements.php');
        ?>
<aside class="lc-settle-card">
  <h2 class="lc-settle-card__title">🎯 정산 가능 금액</h2>
  <div class="lc-settle-card__rows">
    <div class="lc-settle-card__row"><span>이번 달 확정수익</span><strong>8,430,000 원</strong></div>
    <div class="lc-settle-card__row"><span>정산 대기</span><strong class="lc-settle-card__pending">1,200,000 원</strong></div>
    <div class="lc-settle-card__total"><span>정산 가능</span><strong>7,230,000 <small>원</small></strong></div>
  </div>
  <a class="lc-btn lc-btn--emerald" href="<?php echo lc_h($url); ?>" style="width:100%;justify-content:center;margin-top:1rem">정산 신청하기</a>
</aside>
        <?php
    }
}

if (!function_exists('lc_ui_partner_filter_bar')) {
    function lc_ui_partner_filter_bar($placeholder = '검색...')
    {
        ?>
<div class="lc-partner-filter">
  <div class="lc-partner-filter__dates">
    <input type="date" class="lc-input" value="2026-10-01" readonly>
    <span>~</span>
    <input type="date" class="lc-input" value="2026-10-07" readonly>
  </div>
  <select class="lc-input" disabled><option>전체 캠페인</option></select>
  <select class="lc-input" disabled><option>상태 전체</option></select>
  <div class="lc-partner-filter__search">
    <input type="text" class="lc-input" placeholder="<?php echo lc_h($placeholder); ?>" readonly>
  </div>
  <button type="button" class="lc-btn lc-btn--dark lc-btn--sm">조회</button>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_format_won')) {
    function lc_ui_format_won($amount)
    {
        $n = (int) $amount;
        if ($n === 0) {
            return '<span class="lc-won lc-won--zero">0</span>';
        }

        return '<span class="lc-won lc-won--plus">+' . number_format($n) . '</span>';
    }
}

/* ── 광고주센터 UI ── */

if (!function_exists('lc_ui_merchant_intro')) {
    function lc_ui_merchant_intro($desc)
    {
        echo '<p class="lc-partner-intro">' . lc_h($desc) . '</p>';
    }
}

if (!function_exists('lc_ui_merchant_status_badge')) {
    function lc_ui_merchant_status_badge($status)
    {
        $map = array(
            '신규접수' => 'default', '접수완료' => 'default', '확인중' => 'cyan', '검수중' => 'cyan',
            '승인완료' => 'emerald', '취소요청' => 'amber', '취소/무효' => 'red', '보류' => 'amber',
            '진행중' => 'cyan', '일시중지' => 'amber', '종료' => 'default',
            '충전완료' => 'cyan', '차감완료' => 'slate', '환급완료' => 'emerald',
            '답변대기' => 'amber', '답변완료' => 'cyan',
        );
        $tone = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="lc-pstatus lc-pstatus--' . lc_h($tone) . '">' . lc_h($status) . '</span>';
    }
}

if (!function_exists('lc_ui_merchant_summary_grid')) {
    function lc_ui_merchant_summary_grid(array $items, $cols = '6')
    {
        echo '<div class="lc-summary-grid lc-summary-grid--' . lc_h($cols) . ' lc-summary-grid--merchant">';
        foreach ($items as $item) {
            $tone = isset($item['tone']) ? $item['tone'] : 'default';
            $color = isset($item['color']) ? $item['color'] : '';
            $icon = isset($item['icon']) ? $item['icon'] : '';
            $suffix = isset($item['suffix']) ? $item['suffix'] : '';
            $cls = 'lc-summary-card';
            if ($tone === 'highlight') {
                $cls .= ' lc-summary-card--highlight';
            }
            if ($tone === 'dark') {
                $cls .= ' lc-summary-card--dark';
            }
            if ($color === 'cyan') {
                $cls .= ' lc-summary-card--cyan';
            }
            if ($color === 'blue') {
                $cls .= ' lc-summary-card--blue';
            }
            ?>
<article class="<?php echo lc_h($cls); ?>">
  <div class="lc-summary-card__head">
    <span class="lc-summary-card__label"><?php echo lc_h($item['label']); ?></span>
    <?php if ($icon !== '') { ?><span class="lc-summary-card__icon"><?php echo $icon; ?></span><?php } ?>
  </div>
  <p class="lc-summary-card__value"><?php echo lc_h($item['value']); ?><?php if ($suffix !== '') { ?> <small><?php echo lc_h($suffix); ?></small><?php } ?></p>
</article>
            <?php
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_merchant_chart_7d')) {
    function lc_ui_merchant_chart_7d(array $data)
    {
        $max = 1;
        foreach ($data as $d) {
            $max = max($max, (int) $d['db'], (int) $d['approval'], (int) $d['cancel']);
        }
        echo '<div class="lc-partner-chart">';
        echo '<div class="lc-partner-chart__bars">';
        foreach ($data as $d) {
            $db = round(((int) $d['db'] / $max) * 100);
            $ap = round(((int) $d['approval'] / $max) * 100);
            $ca = round(((int) $d['cancel'] / $max) * 100);
            echo '<div class="lc-partner-chart__col">';
            echo '<div class="lc-partner-chart__stack" style="flex-direction:row;align-items:flex-end;gap:2px;width:100%;justify-content:center">';
            echo '<span class="lc-partner-chart__bar" style="height:' . (int) $db . '%;background:rgba(148,163,184,0.6);width:28%"></span>';
            echo '<span class="lc-partner-chart__bar lc-partner-chart__bar--approval" style="height:' . (int) $ap . '%;width:28%"></span>';
            echo '<span class="lc-partner-chart__bar" style="height:' . (int) $ca . '%;background:rgba(239,68,68,0.55);width:28%"></span>';
            echo '</div>';
            echo '<span class="lc-partner-chart__label">' . lc_h($d['date']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="lc-partner-chart__legend"><span><i class="lc-partner-chart__dot" style="background:#94a3b8"></i> 접수</span><span><i class="lc-partner-chart__dot lc-partner-chart__dot--approval"></i> 승인</span><span><i class="lc-partner-chart__dot" style="background:#ef4444"></i> 취소</span></div>';
        echo '</div>';
    }
}

if (!function_exists('lc_ui_wallet_card')) {
    function lc_ui_wallet_card($wallet_url = '')
    {
        if ($wallet_url === '') {
            $wallet_url = lc_url('merchant/wallet.php');
        }
        ?>
<aside class="lc-wallet-card">
  <h2 class="lc-wallet-card__title">💰 이번 달 광고비 현황</h2>
  <div class="lc-wallet-card__rows">
    <div class="lc-wallet-card__row"><span>이번 달 충전액</span><strong>5,000,000 원</strong></div>
    <div class="lc-wallet-card__row"><span>이번 달 사용액</span><strong class="lc-wallet-card__minus">-2,650,000 원</strong></div>
    <div class="lc-wallet-card__total"><span>남은 잔액</span><strong>2,350,000 <small>원</small></strong></div>
  </div>
  <a class="lc-btn lc-btn--primary" href="<?php echo lc_h($wallet_url); ?>" style="width:100%;justify-content:center;margin-top:1rem">광고비 충전하기</a>
</aside>
        <?php
    }
}

if (!function_exists('lc_ui_merchant_alert')) {
    function lc_ui_merchant_alert($count, $url = '')
    {
        if ($url === '') {
            $url = lc_url('merchant/conversions.php');
        }
        ?>
<div class="lc-merchant-alert">
  <div class="lc-merchant-alert__icon">⚠</div>
  <p>현재 승인 또는 취소 처리가 필요한 디비가 <strong><?php echo (int) $count; ?>건</strong> 있습니다.</p>
  <a class="lc-btn lc-btn--sm lc-btn--outline" href="<?php echo lc_h($url); ?>">바로 처리하기 →</a>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_merchant_db_actions')) {
    function lc_ui_merchant_db_actions($needs_action = false, $show_detail = true)
    {
        echo '<div class="lc-db-actions">';
        if ($needs_action) {
            echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--approve">승인</button>';
            echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--reject">취소</button>';
        }
        if ($show_detail) {
            echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--outline" data-lc-modal-open="merchant-db-detail">상세보기</button>';
        }
        echo '<button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">코멘트</button>';
        echo '</div>';
    }
}

if (!function_exists('lc_ui_merchant_db_modal')) {
    function lc_ui_merchant_db_modal(array $db)
    {
        ?>
<div class="lc-modal" id="lcMerchantDbModal" data-lc-modal hidden aria-hidden="true">
  <div class="lc-modal__backdrop" data-lc-modal-close></div>
  <div class="lc-modal__panel" role="dialog" aria-labelledby="lcModalTitle" aria-modal="true">
    <div class="lc-modal__head">
      <div>
        <h2 class="lc-modal__title" id="lcModalTitle">디비 상세보기</h2>
        <p class="lc-modal__sub"><?php echo lc_h($db['id']); ?> · <?php echo lc_ui_merchant_status_badge($db['status']); ?></p>
      </div>
      <button type="button" class="lc-modal__close" data-lc-modal-close aria-label="닫기">✕</button>
    </div>
    <div class="lc-modal__body">
      <section class="lc-modal__section">
        <h3 class="lc-modal__section-title">고객 정보</h3>
        <dl class="lc-modal__dl">
          <dt>고객명</dt><dd><?php echo lc_h($db['name']); ?></dd>
          <dt>연락처</dt><dd class="lc-mask"><?php echo lc_h($db['phone']); ?></dd>
          <dt>이메일</dt><dd><?php echo lc_h($db['email']); ?></dd>
          <dt>지역</dt><dd><?php echo lc_h($db['region']); ?></dd>
          <dt>문의내용</dt><dd><?php echo lc_h($db['inquiry']); ?></dd>
        </dl>
      </section>
      <section class="lc-modal__section">
        <h3 class="lc-modal__section-title">유입 정보</h3>
        <dl class="lc-modal__dl">
          <dt>유입경로</dt><dd><?php echo lc_h($db['channel']); ?></dd>
          <dt>sub_id</dt><dd><code><?php echo lc_h($db['sub_id'] !== '' ? $db['sub_id'] : '-'); ?></code></dd>
          <dt>파트너</dt><dd><?php echo lc_h($db['partner']); ?></dd>
          <dt>랜딩 URL</dt><dd style="word-break:break-all;font-size:0.8rem"><?php echo lc_h($db['landing']); ?></dd>
          <dt>Referer</dt><dd style="word-break:break-all;font-size:0.8rem"><?php echo lc_h($db['referer']); ?></dd>
        </dl>
      </section>
      <section class="lc-modal__section">
        <h3 class="lc-modal__section-title">광고상품 정보</h3>
        <dl class="lc-modal__dl">
          <dt>상품명</dt><dd><?php echo lc_h($db['campaign']); ?></dd>
          <dt>디비당 차감</dt><dd><strong style="color:var(--lc-cyan)"><?php echo number_format((int) $db['price']); ?>원</strong></dd>
          <dt>접수일시</dt><dd><?php echo lc_h($db['date']); ?></dd>
        </dl>
      </section>
      <section class="lc-modal__section">
        <h3 class="lc-modal__section-title">처리 이력</h3>
        <ul class="lc-modal__timeline">
          <?php foreach ($db['history'] as $h) { ?>
          <li><span class="lc-modal__time"><?php echo lc_h($h['time']); ?></span> <?php echo lc_h($h['text']); ?></li>
          <?php } ?>
        </ul>
      </section>
      <section class="lc-modal__section">
        <h3 class="lc-modal__section-title">코멘트</h3>
        <textarea class="lc-input" rows="3" readonly placeholder="처리 코멘트를 입력하세요"><?php echo lc_h($db['comment']); ?></textarea>
      </section>
    </div>
    <div class="lc-modal__foot">
      <button type="button" class="lc-btn lc-btn--reject" data-lc-modal-close>취소/무효 처리</button>
      <button type="button" class="lc-btn lc-btn--approve">승인 처리</button>
    </div>
  </div>
</div>
        <?php
    }
}

/* ── 관리자센터 UI ── */

if (!function_exists('lc_ui_admin_intro')) {
    function lc_ui_admin_intro($desc)
    {
        echo '<p class="lc-admin-intro">' . lc_h($desc) . '</p>';
    }
}

if (!function_exists('lc_ui_admin_summary_card')) {
    function lc_ui_admin_summary_card(array $item)
    {
        $tone = isset($item['tone']) ? $item['tone'] : 'default';
        $color = isset($item['color']) ? $item['color'] : '';
        $icon = isset($item['icon']) ? $item['icon'] : '';
        $suffix = isset($item['suffix']) ? $item['suffix'] : '';
        $cls = 'lc-summary-card lc-summary-card--admin';
        if ($tone === 'highlight' && $color !== '') {
            $cls .= ' lc-summary-card--' . $color;
        }
        if ($tone === 'dark') {
            $cls .= ' lc-summary-card--dark';
        }
        ?>
<article class="<?php echo lc_h($cls); ?>">
  <div class="lc-summary-card__head">
    <span class="lc-summary-card__label"><?php echo lc_h($item['label']); ?></span>
    <?php if ($icon !== '') { ?><span class="lc-summary-card__icon" aria-hidden="true"><?php echo $icon; ?></span><?php } ?>
  </div>
  <p class="lc-summary-card__value"><?php echo lc_h($item['value']); ?><?php if ($suffix !== '') { ?> <small><?php echo lc_h($suffix); ?></small><?php } ?></p>
</article>
        <?php
    }
}

if (!function_exists('lc_ui_admin_summary_grid')) {
    function lc_ui_admin_summary_grid(array $items, $cols = '8')
    {
        echo '<div class="lc-summary-grid lc-summary-grid--admin lc-summary-grid--' . lc_h($cols) . '">';
        foreach ($items as $item) {
            lc_ui_admin_summary_card($item);
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_admin_status_badge')) {
    function lc_ui_admin_status_badge($status)
    {
        $map = array(
            '신규접수' => 'default', '확인중' => 'blue', '승인완료' => 'emerald', '취소요청' => 'orange',
            '취소/무효' => 'red', '정상' => 'cyan', 'API오류' => 'red', 'API 오류' => 'red', '지연' => 'yellow',
            '광고비부족' => 'red', '광고비 부족' => 'red', '운영중' => 'cyan', '일시중지' => 'yellow',
            '종료' => 'default', '진행중' => 'cyan', '검수대기' => 'blue', '검수완료' => 'emerald',
            '검수반려' => 'red', '승인대기' => 'yellow', '보류' => 'yellow', '반려' => 'red',
            '지급완료' => 'dark', '충전대기' => 'blue', '충전완료' => 'cyan', '차감완료' => 'default',
            '환급완료' => 'emerald', '답변대기' => 'orange', '처리중' => 'blue', '답변완료' => 'emerald',
            '가입대기' => 'yellow', '실패' => 'red', '성공' => 'emerald', '차단' => 'red',
            '예정' => 'blue', '마감임박' => 'orange', '종료' => 'default', '숨김' => 'default',
            '달성' => 'emerald', '지급대기' => 'yellow', '지급완료' => 'emerald', '검수중' => 'blue',
        );
        $tone = isset($map[$status]) ? $map[$status] : 'default';
        return '<span class="lc-admin-badge lc-admin-badge--' . lc_h($tone) . '">' . lc_h($status) . '</span>';
    }
}

if (!function_exists('lc_ui_admin_chart_7d')) {
    function lc_ui_admin_chart_7d(array $data)
    {
        $max = 1;
        foreach ($data as $d) {
            $max = max($max, (int) $d['received'], (int) $d['approved'], (int) $d['canceled']);
        }
        $rev_max = 1;
        foreach ($data as $d) {
            $rev_max = max($rev_max, (int) $d['revenue']);
        }
        echo '<div class="lc-admin-chart">';
        echo '<div class="lc-admin-chart__bars">';
        foreach ($data as $d) {
            $rc = round(((int) $d['received'] / $max) * 100);
            $ap = round(((int) $d['approved'] / $max) * 100);
            $ca = round(((int) $d['canceled'] / $max) * 100);
            $rv = round(((int) $d['revenue'] / $rev_max) * 100);
            echo '<div class="lc-admin-chart__col">';
            echo '<div class="lc-admin-chart__stack">';
            echo '<span class="lc-admin-chart__bar lc-admin-chart__bar--received" style="height:' . (int) $rc . '%" title="접수 ' . (int) $d['received'] . '"></span>';
            echo '<span class="lc-admin-chart__bar lc-admin-chart__bar--approved" style="height:' . (int) $ap . '%" title="승인 ' . (int) $d['approved'] . '"></span>';
            echo '<span class="lc-admin-chart__bar lc-admin-chart__bar--canceled" style="height:' . (int) $ca . '%" title="취소 ' . (int) $d['canceled'] . '"></span>';
            echo '</div>';
            echo '<span class="lc-admin-chart__revenue" style="height:' . (int) $rv . '%" title="매출 ' . (int) $d['revenue'] . '만"></span>';
            echo '<span class="lc-admin-chart__label">' . lc_h($d['date']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="lc-admin-chart__legend">';
        echo '<span><i class="lc-admin-chart__dot lc-admin-chart__dot--received"></i> 접수</span>';
        echo '<span><i class="lc-admin-chart__dot lc-admin-chart__dot--approved"></i> 승인</span>';
        echo '<span><i class="lc-admin-chart__dot lc-admin-chart__dot--canceled"></i> 취소</span>';
        echo '<span><i class="lc-admin-chart__dot lc-admin-chart__dot--revenue"></i> 매출</span>';
        echo '</div></div>';
    }
}

if (!function_exists('lc_ui_admin_alerts')) {
    function lc_ui_admin_alerts(array $items)
    {
        echo '<div class="lc-admin-alerts">';
        foreach ($items as $item) {
            $tone = isset($item['tone']) ? $item['tone'] : 'default';
            $url = isset($item['url']) ? $item['url'] : '#';
            ?>
<div class="lc-admin-alert lc-admin-alert--<?php echo lc_h($tone); ?>">
  <div class="lc-admin-alert__left">
    <span class="lc-admin-alert__icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
    <span class="lc-admin-alert__text"><?php echo lc_h($item['label']); ?> <strong><?php echo lc_h($item['count']); ?></strong></span>
  </div>
  <a class="lc-btn lc-btn--xs lc-btn--ghost" href="<?php echo lc_h($url); ?>">바로가기</a>
</div>
            <?php
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_admin_filter_bar')) {
    function lc_ui_admin_filter_bar($placeholder = '검색...')
    {
        ?>
<div class="lc-admin-filter">
  <div class="lc-admin-filter__dates">
    <input type="date" class="lc-input" value="2026-07-01" readonly>
    <span>~</span>
    <input type="date" class="lc-input" value="2026-07-07" readonly>
  </div>
  <input type="search" class="lc-input lc-admin-filter__search" placeholder="<?php echo lc_h($placeholder); ?>" readonly>
  <select class="lc-input lc-admin-filter__select" disabled>
    <option>전체 상태</option>
    <option>운영중</option>
    <option>일시중지</option>
  </select>
  <button type="button" class="lc-btn lc-btn--primary lc-btn--sm">조회</button>
  <button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">엑셀</button>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_admin_review_actions')) {
    function lc_ui_admin_review_actions($pending = true)
    {
        if (!$pending) {
            return '<span class="lc-muted">—</span>';
        }
        return '<div class="lc-db-actions">'
            . '<button type="button" class="lc-btn lc-btn--xs lc-btn--approve">취소 승인</button>'
            . '<button type="button" class="lc-btn lc-btn--xs lc-btn--reject">반려</button>'
            . '</div>';
    }
}

if (!function_exists('lc_ui_admin_settlement_actions')) {
    function lc_ui_admin_settlement_actions($status)
    {
        if ($status === '지급완료' || $status === '반려') {
            return '<button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">상세</button>';
        }
        return '<div class="lc-db-actions">'
            . '<button type="button" class="lc-btn lc-btn--xs lc-btn--approve">승인</button>'
            . '<button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">보류</button>'
            . '<button type="button" class="lc-btn lc-btn--xs lc-btn--reject">반려</button>'
            . '</div>';
    }
}

if (!function_exists('lc_ui_admin_wallet_actions')) {
    function lc_ui_admin_wallet_actions($status)
    {
        if ($status === '충전대기') {
            return '<button type="button" class="lc-btn lc-btn--xs lc-btn--approve">충전 승인</button>';
        }
        return '<button type="button" class="lc-btn lc-btn--xs lc-btn--ghost">관리</button>';
    }
}

if (!function_exists('lc_ui_admin_api_retry')) {
    function lc_ui_admin_api_retry($failed = false)
    {
        if (!$failed) {
            return '<span class="lc-muted">—</span>';
        }
        return '<button type="button" class="lc-btn lc-btn--xs lc-btn--primary">재처리</button>';
    }
}

if (!function_exists('lc_ui_admin_settings_form')) {
    function lc_ui_admin_settings_form(array $sections)
    {
        echo '<div class="lc-admin-settings">';
        foreach ($sections as $sec) {
            ?>
<section class="lc-admin-settings__section">
  <div class="lc-admin-settings__head">
    <h2 class="lc-admin-settings__title"><?php echo lc_h($sec['title']); ?></h2>
    <p class="lc-admin-settings__desc"><?php echo lc_h($sec['desc']); ?></p>
  </div>
  <div class="lc-admin-settings__fields">
    <?php foreach ($sec['fields'] as $field) { ?>
    <label class="lc-admin-settings__field">
      <span class="lc-admin-settings__label"><?php echo lc_h($field['label']); ?></span>
      <?php if ($field['type'] === 'select') { ?>
      <select class="lc-input" disabled><option><?php echo lc_h($field['value']); ?></option></select>
      <?php } else { ?>
      <input type="<?php echo lc_h($field['type']); ?>" class="lc-input" value="<?php echo lc_h($field['value']); ?>" readonly>
      <?php } ?>
    </label>
    <?php } ?>
  </div>
  <div class="lc-admin-settings__actions">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm">저장</button>
  </div>
</section>
            <?php
        }
        echo '</div>';
    }
}

if (!function_exists('lc_ui_admin_inquiry_reply')) {
    function lc_ui_admin_inquiry_reply()
    {
        ?>
<div class="lc-panel lc-panel--admin lc-admin-reply">
  <h2 class="lc-panel__title">답변 작성</h2>
  <textarea class="lc-input" rows="5" placeholder="문의에 대한 답변을 작성하세요. (샘플 UI)"></textarea>
  <div class="lc-admin-reply__actions">
    <button type="button" class="lc-btn lc-btn--ghost lc-btn--sm">임시저장</button>
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm">답변 등록</button>
  </div>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_admin_event_status_badge')) {
    function lc_ui_admin_event_status_badge($status)
    {
        return lc_ui_admin_status_badge($status);
    }
}

if (!function_exists('lc_ui_admin_event_type_badge')) {
    function lc_ui_admin_event_type_badge($type)
    {
        $map = array(
            '파트너 보너스' => 'purple', '단가 상승' => 'emerald', '신규 캠페인' => 'blue',
            '랭킹 이벤트' => 'orange', '광고주 프로모션' => 'cyan', '광고비 충전 보너스' => 'yellow',
        );
        $tone = isset($map[$type]) ? $map[$type] : 'default';
        return '<span class="lc-admin-badge lc-admin-badge--' . lc_h($tone) . '">' . lc_h($type) . '</span>';
    }
}

if (!function_exists('lc_ui_admin_event_form')) {
    function lc_ui_admin_event_form($selected_code = 'EVT-301')
    {
        $campaigns = lc_sample_admin_event_campaign_options();
        ?>
<div class="lc-panel lc-panel--admin lc-admin-event-form">
  <div class="lc-panel__head">
    <h2 class="lc-panel__title">이벤트 등록 / 수정</h2>
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm">+ 신규 등록</button>
  </div>
  <div class="lc-admin-form-grid">
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">이벤트 코드</span><input type="text" class="lc-input" value="<?php echo lc_h($selected_code); ?>" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">이벤트 유형</span><select class="lc-input" disabled><option>파트너 보너스</option><option>단가 상승</option><option>랭킹 이벤트</option></select></label>
    <label class="lc-admin-settings__field" style="grid-column:1/-1"><span class="lc-admin-settings__label">이벤트명</span><input type="text" class="lc-input" value="7월 신규 파트너 웰컴 보너스" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">시작일</span><input type="date" class="lc-input" value="2026-07-01" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">종료일</span><input type="date" class="lc-input" value="2026-07-31" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">상태</span><select class="lc-input" disabled><option>진행중</option><option>마감임박</option><option>종료</option></select></label>
  </div>

  <h3 class="lc-admin-event-form__sub">적용 광고상품 연결</h3>
  <div class="lc-admin-campaign-link">
    <?php foreach ($campaigns as $c) {
        $checked = (strpos($c, '개인회생') !== false || strpos($c, '신용대출') !== false);
        ?>
    <label class="lc-admin-campaign-link__item">
      <input type="checkbox" disabled <?php echo $checked ? 'checked' : ''; ?>>
      <span><?php echo lc_h($c); ?></span>
    </label>
    <?php } ?>
  </div>

  <h3 class="lc-admin-event-form__sub">리워드 설정</h3>
  <div class="lc-admin-form-grid">
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">리워드 유형</span><select class="lc-input" disabled><option>고정 보너스</option><option>단가 상승</option><option>달성 보너스</option></select></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">보너스 금액</span><input type="text" class="lc-input" value="50,000" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">달성 조건</span><input type="text" class="lc-input" value="첫 승인 DB 1건" readonly></label>
    <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">지급 방식</span><select class="lc-input" disabled><option>정산 시 합산</option><option>즉시 지급</option></select></label>
  </div>
  <div class="lc-admin-settings__actions"><button type="button" class="lc-btn lc-btn--primary lc-btn--sm">저장</button></div>
</div>
        <?php
    }
}

if (!function_exists('lc_ui_admin_manual_wallet')) {
    function lc_ui_admin_manual_wallet()
    {
        ?>
<aside class="lc-panel lc-panel--admin lc-admin-manual-wallet">
  <h2 class="lc-panel__title">수동 충전 / 차감 / 환급</h2>
  <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">광고주</span><select class="lc-input" disabled><option>희망법무법인</option></select></label>
  <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">유형</span><select class="lc-input" disabled><option>충전</option><option>차감</option><option>환급</option></select></label>
  <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">금액</span><input type="text" class="lc-input" placeholder="0" readonly></label>
  <label class="lc-admin-settings__field"><span class="lc-admin-settings__label">사유</span><textarea class="lc-input" rows="2" readonly placeholder="처리 사유"></textarea></label>
  <button type="button" class="lc-btn lc-btn--primary" style="width:100%;margin-top:0.5rem">처리 실행</button>
</aside>
        <?php
    }
}

/* ── 최고관리자 전용 바로가기 (is_admin super · mb_level >= 10) ── */

if (!function_exists('lc_ui_super_admin_icon')) {
    function lc_ui_super_admin_icon()
    {
        return '<svg class="lc-admin-btn__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
            . '<path d="M12 2l7 4v6c0 5-3.5 9.5-7 10-3.5-.5-7-5-7-10V6l7-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>'
            . '<path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }
}

if (!function_exists('lc_ui_admin_header_btn')) {
    /**
     * 헤더 우측 끝 관리자센터 배지 (항상 표시)
     */
    function lc_ui_admin_header_btn()
    {
        $admin_url = rtrim(lc_public_home_url(), '/') . '/admin';
        ?>
<a class="lc-btn lc-btn--admin lc-btn--sm lc-admin-btn" href="<?php echo lc_h($admin_url); ?>">
  <?php echo lc_ui_super_admin_icon(); ?>
  <span>관리자센터</span>
</a>
        <?php
    }
}

if (!function_exists('lc_ui_super_admin_header_btn')) {
    /** @deprecated lc_ui_admin_header_btn() 사용 */
    function lc_ui_super_admin_header_btn()
    {
        lc_ui_admin_header_btn();
    }
}

if (!function_exists('lc_ui_super_admin_banner')) {
    /**
     * 메인 페이지 상단 배너 — 최고관리자 로그인 시에만
     */
    function lc_ui_super_admin_banner()
    {
        if (!lc_is_super_admin()) {
            return;
        }
        ?>
<aside class="lc-admin-banner" role="status" aria-label="최고관리자 안내">
  <div class="lc-admin-banner__inner">
    <div class="lc-admin-banner__icon" aria-hidden="true"><?php echo lc_ui_super_admin_icon(); ?></div>
    <div class="lc-admin-banner__body">
      <p class="lc-admin-banner__title">최고관리자 모드로 접속 중입니다.</p>
      <p class="lc-admin-banner__desc">링크커넥트 관리자센터에서 파트너, 광고주, 디비, 정산, API를 관리할 수 있습니다.</p>
    </div>
    <a class="lc-btn lc-btn--admin lc-btn--sm lc-admin-banner__cta" href="<?php echo lc_h(LC_URL_ADMIN_DASHBOARD); ?>">
      <?php echo lc_ui_super_admin_icon(); ?>
      <span>관리자센터 바로가기</span>
    </a>
  </div>
</aside>
        <?php
    }
}

if (!function_exists('lc_ui_super_admin_fab')) {
    /**
     * 모바일 우측 하단 플로팅 버튼 — 최고관리자만
     */
    function lc_ui_super_admin_fab()
    {
        if (!lc_is_super_admin()) {
            return;
        }
        ?>
<a class="lc-admin-fab" href="<?php echo lc_h(LC_URL_ADMIN_DASHBOARD); ?>" aria-label="관리자센터 바로가기">
  <?php echo lc_ui_super_admin_icon(); ?>
  <span>관리자센터</span>
</a>
        <?php
    }
}

