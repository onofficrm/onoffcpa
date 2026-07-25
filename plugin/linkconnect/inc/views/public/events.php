<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$cpa_url = lc_url('pages/cpa.php');
$merchant_url = lc_url('merchant/dashboard.php');
$icon_map = array('megaphone' => '📢', 'gift' => '🎁', 'trend' => '📈', 'clock' => '⏱');
$preview = lc_sample_event_detail_preview();
?>
<!-- 1. 이벤트 히어로 -->
<section class="lc-events-hero">
  <div class="lc-events-hero__inner">
    <div class="lc-events-hero__badge">🎁 이벤트 / 프로모션</div>
    <h1 class="lc-events-hero__title">성과가 좋은 캠페인에는 <span>더 큰 리워드를</span></h1>
    <p class="lc-events-hero__lead">파트너 전용 보너스, 단가 상승 이벤트, 신규 광고상품 프로모션을 한눈에 확인하고 CPA 상품과 바로 연결해 홍보하세요.</p>
    <div class="lc-events-hero__actions">
      <a class="lc-btn lc-btn--primary lc-btn--xl" href="#lc-events-list">진행 중 이벤트 보기</a>
      <a class="lc-btn lc-btn--ghost lc-btn--xl" href="#lc-promo-cpa" style="border-color:rgba(255,255,255,0.2);color:#fff">프로모션 CPA 상품</a>
    </div>
  </div>
</section>

<div class="lc-event-summary">
  <?php foreach (lc_sample_event_summary() as $s) {
      $icon_cls = 'lc-event-summary__icon--' . (isset($s['icon']) && $s['icon'] === 'gift' ? 'purple' : ($s['icon'] === 'trend' ? 'emerald' : ($s['icon'] === 'clock' ? 'amber' : 'cyan')));
      ?>
  <article class="lc-event-summary__card">
    <div class="lc-event-summary__icon <?php echo lc_h($icon_cls); ?>"><?php echo $icon_map[$s['icon']]; ?></div>
    <p class="lc-event-summary__value"><?php echo lc_h($s['value']); ?><small><?php echo lc_h($s['suffix']); ?></small></p>
    <p class="lc-event-summary__label"><?php echo lc_h($s['label']); ?></p>
  </article>
  <?php } ?>
</div>

<div class="lc-page-wrap" style="padding-top:3rem">

  <!-- 2. 이벤트 카테고리 탭 -->
  <section class="lc-event-tabs-strip" id="lc-event-tabs">
    <div class="lc-section-head" style="margin-bottom:0.75rem">
      <h2 class="lc-section-head__title">이벤트 카테고리</h2>
    </div>
    <?php lc_ui_filter_pills(lc_sample_event_tabs(), 0, 'lc-pills'); ?>
  </section>

  <!-- 3. 파트너별 맞춤 이벤트 추천 -->
  <section class="lc-dark-panel" style="margin-bottom:2.5rem">
    <div class="lc-rec-head">
      <div>
        <div style="margin-bottom:0.5rem"><?php echo lc_ui_badge('✨ 맞춤 추천', 'cyan'); ?></div>
        <h2 class="lc-section__title" style="color:#fff;margin:0 0 0.35rem">PTN-8291 파트너님에게 추천하는 이벤트</h2>
        <p class="lc-section__lead" style="margin:0">최근 유입 성과와 홍보 채널을 기준으로 CPA 상품과 연결된 이벤트를 추천합니다.</p>
      </div>
      <aside class="lc-rec-insight">
        <div class="lc-rec-insight__avatar" aria-hidden="true">👤</div>
        <p class="lc-rec-insight__text">"최근 <strong style="color:var(--lc-cyan-400)">블로그 채널</strong>에서 접수 DB가 많이 발생하고 있어, 블로그 홍보가 가능한 <strong style="color:var(--lc-emerald-400)">고승인율 캠페인</strong>을 추천합니다."</p>
        <div class="lc-rec-insight__tags">
          <span class="lc-rec-insight__tag">#블로그추천</span>
          <span class="lc-rec-insight__tag">#고승인율</span>
          <span class="lc-rec-insight__tag">#단가상승</span>
          <span class="lc-rec-insight__tag">#마감임박</span>
        </div>
      </aside>
    </div>
    <div class="lc-rec-grid">
      <?php foreach (lc_sample_event_recommendations() as $rec) { ?>
      <article class="lc-rec-card">
        <div class="lc-rec-card__top">
          <?php echo lc_ui_event_badge($rec['badge']); ?>
          <?php if ($rec['dday'] !== '') { ?><span class="lc-rec-card__dday"><?php echo lc_h($rec['dday']); ?></span><?php } ?>
        </div>
        <h3 class="lc-rec-card__title"><?php echo lc_h($rec['title']); ?></h3>
        <p class="lc-rec-card__reason">✓ <?php echo lc_h($rec['reason']); ?></p>
        <div class="lc-rec-card__price-box">
          <div class="lc-muted" style="font-size:0.75rem;margin-bottom:0.35rem">적용 CPA: <strong style="color:var(--lc-slate-800)"><?php echo lc_h($rec['product']); ?></strong></div>
          <?php if ((int) $rec['old_price'] > 0) { ?>
          <div class="lc-rec-card__old"><?php echo number_format((int) $rec['old_price']); ?>원</div>
          <?php } ?>
          <?php if ($rec['approval_rate'] !== '') { ?>
          <div style="text-align:right;margin-bottom:0.15rem"><?php echo lc_ui_badge('승인율 ' . $rec['approval_rate'], 'emerald'); ?></div>
          <?php } ?>
          <div class="lc-rec-card__price"><?php echo number_format((int) $rec['price']); ?><small>원</small></div>
        </div>
        <div class="lc-rec-card__foot">
          <button type="button" class="lc-btn lc-btn--ghost lc-btn--sm" style="font-size:0.75rem">왜 추천되었나요?</button>
          <button type="button" class="lc-btn lc-btn--sm <?php echo $rec['cta_tone'] === 'dark' ? 'lc-btn--dark' : ($rec['cta_tone'] === 'primary' ? 'lc-btn--primary' : 'lc-btn--outline'); ?>"><?php echo lc_h($rec['cta']); ?></button>
        </div>
      </article>
      <?php } ?>
    </div>
  </section>

  <!-- 4. 이벤트 참여 조건 진행률 -->
  <section class="lc-panel" id="lc-event-progress">
    <h2 class="lc-panel__title">내 이벤트 참여 현황</h2>
    <p class="lc-muted" style="margin-bottom:1.25rem">현재 참여 중인 이벤트의 달성률과 예상 보너스를 확인하세요. (샘플 UI)</p>
    <div class="lc-stat-grid" style="margin-bottom:1.5rem">
      <?php
      $progress_stats = array(
          array('label' => '참여 중 이벤트', 'value' => '3개', 'tone' => 'default'),
          array('label' => '달성 완료', 'value' => '1개', 'tone' => 'highlight'),
          array('label' => '목표까지 남은 DB', 'value' => '7건', 'tone' => 'default'),
          array('label' => '예상 추가 보너스', 'value' => '320,000원', 'tone' => 'cyan'),
      );
      foreach ($progress_stats as $st) {
          echo '<article class="lc-stat lc-stat--' . lc_h($st['tone']) . '"><p class="lc-stat__label">' . lc_h($st['label']) . '</p><p class="lc-stat__value">' . lc_h($st['value']) . '</p></article>';
      }
      ?>
    </div>
    <?php foreach (lc_sample_event_progress() as $prog) {
        lc_ui_progress_card($prog);
    } ?>
    <div class="lc-placeholder" style="margin-top:1rem;background:var(--lc-slate-50)">
      <strong>진행률 기준 안내</strong>
      <ul class="lc-rules-list" style="margin-top:0.5rem">
        <li>승인 완료된 DB만 이벤트 실적에 반영됩니다.</li>
        <li>취소/무효 처리된 DB는 진행률에서 제외됩니다.</li>
        <li>이벤트 기간 내 발생한 성과만 인정됩니다.</li>
        <li>보너스는 관리자 검수 후 정산에 반영됩니다.</li>
      </ul>
    </div>
  </section>

  <!-- 이벤트 목록 (카테고리 탭 연동) -->
  <section id="lc-events-list" style="margin-top:2.5rem">
    <div class="lc-search-bar" style="margin-bottom:1.5rem">
      <div class="lc-search-bar__input" style="max-width:none;flex:1">
        <span class="lc-search-bar__icon">🔍</span>
        <input type="text" placeholder="이벤트명, CPA 광고상품명으로 검색하세요." readonly>
      </div>
      <button type="button" class="lc-filter-btn">필터 ▾</button>
    </div>
    <div class="lc-event-grid">
      <?php foreach (lc_sample_event_cards() as $ev) { ?>
      <article class="lc-event-card">
        <?php if ($ev['ribbon'] !== '') { ?><span class="lc-event-card__ribbon"><?php echo lc_h($ev['ribbon']); ?></span><?php } ?>
        <div class="lc-event-card__body">
          <div class="lc-event-card__badges">
            <?php foreach ($ev['badges'] as $b) {
                echo lc_ui_event_badge($b);
            } ?>
          </div>
          <h3 class="lc-event-card__title"><?php echo lc_h($ev['title']); ?></h3>
          <p class="lc-event-card__desc"><?php echo lc_h($ev['desc']); ?></p>
          <div class="lc-event-card__meta">
            <div class="lc-event-card__meta-row"><span class="lc-muted">기간</span><strong><?php echo lc_h($ev['period']); ?></strong></div>
            <div class="lc-event-card__meta-row"><span class="lc-muted">적용 CPA</span><strong><?php echo lc_h($ev['product']); ?></strong></div>
            <div class="lc-event-card__meta-row"><span>보너스 혜택</span><strong style="color:var(--lc-emerald);font-size:0.95rem"><?php echo lc_h($ev['benefit']); ?></strong></div>
          </div>
        </div>
        <div class="lc-event-card__foot">
          <button type="button" class="lc-btn lc-btn--sm lc-btn--outline">상세보기</button>
          <button type="button" class="lc-btn lc-btn--sm lc-btn--primary">홍보 링크 만들기</button>
        </div>
      </article>
      <?php } ?>
    </div>
  </section>

  <!-- 5. 파트너 랭킹 + 내 순위 -->
  <section class="lc-ranking-panel" id="lc-ranking" style="margin-top:2.5rem">
    <div style="margin-bottom:1.25rem"><?php echo lc_ui_badge('🏆 랭킹 이벤트', 'amber'); ?></div>
    <h2 class="lc-section__title" style="color:#fff">이번 달 파트너 리워드 랭킹</h2>
    <p class="lc-section__lead">승인 DB 기준 상위 파트너에게 추가 리워드가 지급됩니다. (연결 CPA: 전체 상품)</p>

    <div class="lc-stat-grid" style="margin:1.5rem 0">
      <article class="lc-stat lc-stat--dark"><p class="lc-stat__label">현재 1위 승인 DB</p><p class="lc-stat__value">128건</p></article>
      <article class="lc-stat" style="background:rgba(6,182,212,0.15);border-color:rgba(6,182,212,0.3)"><p class="lc-stat__label" style="color:var(--lc-cyan)">내 현재 순위</p><p class="lc-stat__value" style="color:var(--lc-cyan)">18위</p></article>
      <article class="lc-stat lc-stat--dark"><p class="lc-stat__label">TOP 10까지 남은 DB</p><p class="lc-stat__value">7건</p></article>
      <article class="lc-stat lc-stat--dark"><p class="lc-stat__label">내 예상 보너스</p><p class="lc-stat__value">0원</p><p style="margin:0.25rem 0 0;font-size:0.7rem;color:var(--lc-emerald-400)">TOP 10 진입 시 100,000원</p></article>
    </div>

    <div class="lc-partner-split lc-partner-split--chart">
      <div>
        <div class="lc-podium">
          <?php
          $podium_order = array(1, 0, 2);
          foreach ($podium_order as $idx) {
              $p = lc_sample_ranking_top()[$idx];
              ?>
          <div class="lc-podium__item lc-podium__item--<?php echo lc_h($p['tone']); ?>">
            <div class="lc-podium__rank lc-podium__rank--<?php echo lc_h($p['tone']); ?>"><?php echo (int) $p['rank']; ?></div>
            <div style="font-weight:800;color:#fff"><?php echo lc_h($p['partner']); ?></div>
            <?php if (isset($p['earnings'])) { ?><div class="lc-muted" style="font-size:0.75rem;color:#fbbf24">확정수익 <?php echo lc_h($p['earnings']); ?></div><?php } ?>
            <div class="lc-muted" style="font-size:0.8rem">승인 DB <?php echo (int) $p['dbs']; ?>건</div>
            <div style="margin-top:0.65rem;font-weight:800;color:var(--lc-cyan-400)"><?php echo lc_h($p['reward']); ?></div>
          </div>
          <?php } ?>
        </div>

        <div class="lc-my-rank">
          <h3 style="margin:0 0 0.5rem;color:var(--lc-cyan-400);font-size:1rem">🎯 내 현재 순위</h3>
          <div class="lc-my-rank__grid">
            <div><div class="lc-my-rank__stat-label">현재 순위</div><div class="lc-my-rank__stat-value">18위</div></div>
            <div><div class="lc-my-rank__stat-label">승인 DB</div><div class="lc-my-rank__stat-value">42건</div></div>
            <div><div class="lc-my-rank__stat-label">10위까지 남은 DB</div><div class="lc-my-rank__stat-value" style="color:#f43f5e">7건</div></div>
            <div><div class="lc-my-rank__stat-label">현재 확정수익</div><div class="lc-my-rank__stat-value">1,260,000원</div></div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.85rem">
            <button type="button" class="lc-btn lc-btn--primary lc-btn--sm">순위 올리기 좋은 캠페인</button>
            <button type="button" class="lc-btn lc-btn--sm lc-btn--dark" style="border:1px solid rgba(6,182,212,0.35);color:var(--lc-cyan)">이벤트 상품 홍보하기</button>
          </div>
        </div>
      </div>

      <div>
        <table class="lc-ranking-table">
          <thead><tr><th colspan="4" style="color:#fff;font-size:0.85rem;text-transform:none">TOP 4 ~ 10 순위</th></tr>
          <tr><th>순위</th><th>파트너</th><th style="text-align:right">승인 DB</th><th style="text-align:right">리워드</th></tr></thead>
          <tbody>
            <?php foreach (lc_sample_ranking_list() as $row) { ?>
            <tr>
              <td><?php echo (int) $row['rank']; ?></td>
              <td><?php echo lc_h($row['partner']); ?></td>
              <td style="text-align:right"><?php echo (int) $row['dbs']; ?>건</td>
              <td><?php echo lc_h($row['reward']); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <div class="lc-ranking-reward-box">
          <h3>리워드 지급 기준</h3>
          <ul>
            <li><span style="color:#fbbf24">1위</span><strong>1,000,000원</strong></li>
            <li><span>2위</span><strong>500,000원</strong></li>
            <li><span style="color:#fb923c">3위</span><strong>300,000원</strong></li>
            <li><span style="color:var(--lc-cyan-400)">4~10위</span><strong>100,000원</strong></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- 6. 프로모션 적용 CPA 상품 -->
  <section id="lc-promo-cpa" style="margin-top:2.5rem">
    <div class="lc-section-head">
      <div>
        <h2 class="lc-section-head__title">프로모션 적용 CPA 상품</h2>
        <p class="lc-muted" style="margin:0.35rem 0 0">이벤트 혜택이 적용 중인 CPA 광고상품 — 단가·보너스를 확인하고 바로 홍보하세요.</p>
      </div>
      <a class="lc-btn lc-btn--ghost lc-btn--sm" href="<?php echo lc_h($cpa_url); ?>">전체보기 →</a>
    </div>
    <div class="lc-promo-grid">
      <?php foreach (lc_sample_promo_cpa() as $p) { ?>
      <article class="lc-promo-card<?php echo !empty($p['highlight']) ? ' lc-promo-card--highlight' : ''; ?>">
        <?php if (!empty($p['highlight'])) { ?><span class="lc-promo-card__flag">⚡ 단가 상승 중</span><?php } ?>
        <span class="lc-promo-card__event"><?php echo lc_h($p['event']); ?></span>
        <h3 class="lc-promo-card__title"><?php echo lc_h($p['title']); ?></h3>
        <p class="lc-promo-card__cat"><?php echo lc_h($p['category']); ?> · 승인율 <?php echo lc_h($p['approval_rate']); ?></p>
        <div class="lc-rec-card__price-box">
          <?php if ((int) $p['old_price'] > 0) { ?>
          <div class="lc-rec-card__old">기존 <?php echo number_format((int) $p['old_price']); ?>원</div>
          <?php } ?>
          <div style="text-align:right;font-size:0.75rem;font-weight:800;color:var(--lc-emerald)"><?php echo lc_h($p['bonus']); ?></div>
          <div class="lc-rec-card__price"><?php echo number_format((int) $p['price']); ?><small>원</small></div>
        </div>
        <button type="button" class="lc-btn lc-btn--dark" style="margin-top:auto">홍보 링크 생성</button>
      </article>
      <?php } ?>
    </div>
  </section>

  <!-- 7. 광고주용 프로모션 신청 (프리미엄 CTA) -->
  <section class="lc-adv-premium" id="lc-advertiser-promo" style="margin-top:2.5rem">
    <div style="margin-bottom:1rem"><?php echo lc_ui_badge('광고주 전용', 'default'); ?></div>
    <h2 class="lc-section__title" style="color:#fff;margin-bottom:0.35rem">내 CPA 상품도 프로모션에 노출하고 싶으신가요?</h2>
    <p class="lc-section__lead">단가 상승, 추천 캠페인 노출, 파트너 랭킹 이벤트로 CPA 상품과 직접 연결된 프로모션을 운영하세요.</p>

    <div class="lc-adv-promo-grid" style="margin-top:1.5rem">
      <?php foreach (lc_sample_advertiser_promo_cards() as $card) { ?>
      <article class="lc-adv-promo-card">
        <div class="lc-adv-promo-card__icon"><?php echo $card['icon']; ?></div>
        <h3 class="lc-adv-promo-card__title"><?php echo lc_h($card['title']); ?></h3>
        <p class="lc-adv-promo-card__desc"><?php echo lc_h($card['desc']); ?></p>
        <div class="lc-adv-promo-card__meta">
          <span>추천 대상</span><strong><?php echo lc_h($card['target']); ?></strong>
          <span style="margin-top:0.35rem">혜택</span><strong><?php echo lc_h($card['benefit']); ?></strong>
        </div>
        <button type="button" class="lc-btn lc-btn--sm <?php echo $card['tone'] === 'primary' ? 'lc-btn--primary' : 'lc-btn--outline'; ?>" style="<?php echo $card['tone'] !== 'primary' ? 'color:#fff;border-color:var(--lc-slate-600)' : ''; ?>"><?php echo lc_h($card['cta']); ?></button>
      </article>
      <?php } ?>
    </div>

    <div class="lc-adv-process">
      <p class="lc-adv-process__title">프로모션 신청 및 진행 과정</p>
      <div class="lc-adv-process__steps">
        <?php
        $steps = array('광고상품 선택', '유형 선택', '관리자 검토', '이벤트 노출', '파트너 홍보', '디비 성과 확인');
        foreach ($steps as $i => $step) {
            echo '<div class="lc-adv-process__step"><div class="lc-adv-process__num">' . (int) ($i + 1) . '</div><span class="lc-adv-process__label">' . lc_h($step) . '</span></div>';
        }
        ?>
      </div>
    </div>

    <div class="lc-adv-cta-bar">
      <div class="lc-adv-cta-bar__stats">
        <div class="lc-adv-cta-bar__stat"><span>프로모션 적용 CPA</span><strong>38<small style="font-size:0.8rem;font-weight:400;color:var(--lc-slate-400)">개</small></strong></div>
        <div class="lc-adv-cta-bar__stat"><span>참여 파트너</span><strong>1,200<small style="font-size:0.8rem;font-weight:400;color:var(--lc-slate-400)">명+</small></strong></div>
        <div class="lc-adv-cta-bar__stat"><span>평균 DB 증가율</span><strong><em>32</em>%</strong></div>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
        <a class="lc-btn lc-btn--sm lc-btn--dark" href="<?php echo lc_h($merchant_url); ?>">광고주센터로 이동</a>
        <button type="button" class="lc-btn lc-btn--sm lc-btn--primary">프로모션 신청하기</button>
      </div>
    </div>
  </section>

  <!-- 8~10. 상세 미리보기 + 홍보 문구 탭 + 금지사항 -->
  <section class="lc-split" style="margin-top:2.5rem;gap:1.25rem;align-items:start">
    <?php lc_ui_event_detail_preview($preview); ?>

    <div style="display:flex;flex-direction:column;gap:1.25rem">
      <div class="lc-panel">
        <h3 class="lc-panel__title" style="font-size:1rem">홍보 문구 샘플</h3>
        <p class="lc-muted" style="font-size:0.8rem;margin:0 0 0.85rem">채널별 홍보 문구를 복사해 CPA 이벤트 홍보에 활용하세요.</p>
        <?php lc_ui_promo_copy_tabs(lc_sample_promo_copy_tabs()); ?>
      </div>
      <?php lc_ui_event_rules_warn(lc_sample_event_rules_checklist()); ?>
    </div>
  </section>

  <section class="lc-panel" style="margin-top:1.25rem;background:var(--lc-slate-100)">
    <h3 class="lc-panel__title" style="font-size:1rem">이벤트 참여 전 확인해주세요.</h3>
    <ul class="lc-rules-list">
      <li>이벤트별 참여 조건을 반드시 확인해주세요.</li>
      <li>승인 완료된 디비만 리워드 지급 대상에 포함됩니다.</li>
      <li>취소/무효 처리된 디비는 이벤트 실적에서 제외됩니다.</li>
      <li>리워드는 정산 정책에 따라 지급되며, 세금 처리가 적용될 수 있습니다.</li>
    </ul>
  </section>
</div>
