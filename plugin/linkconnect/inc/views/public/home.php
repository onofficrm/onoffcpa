<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
$hero_img = lc_builder_asset_url('hero_dashboard_mockup.png');
$cpa_url = lc_url('pages/cpa.php');
$cps_url = lc_url('pages/cps.php');
$events_url = lc_url('pages/events.php');
$partner_url = lc_url('partner/dashboard.php');
$merchant_url = lc_url('merchant/dashboard.php');
?>
<?php lc_ui_super_admin_banner(); ?>
<section class="lc-hero">
  <div class="lc-hero__inner">
    <div>
      <div class="lc-hero__badge"><span class="lc-hero__badge-dot"></span> 클릭을 수익으로, DB를 성과로 연결하는 제휴마케팅 플랫폼</div>
      <h1 class="lc-hero__title">트래픽이 있다면,<br>지금 바로 <em>수익으로 연결</em>하세요</h1>
      <p class="lc-hero__lead">CPA DB 캠페인과 CPS 구매 캠페인을 한곳에서 확인하고, 실시간 성과와 정산 내역까지 링크커넥트에서 관리하세요.</p>
      <div class="lc-hero__actions">
        <a class="lc-btn lc-btn--emerald lc-btn--xl" href="<?php echo lc_h($cpa_url); ?>">인기 CPA 상품 보기</a>
        <a class="lc-btn lc-btn--ghost lc-btn--xl" href="<?php echo lc_h($cps_url); ?>">CPS 상품 둘러보기</a>
        <a class="lc-btn lc-btn--primary lc-btn--xl" href="<?php echo lc_h(lc_inquiry_url()); ?>" id="lc-inquiry">광고주 입점 문의</a>
      </div>
    </div>
    <div class="lc-hero__visual">
      <img src="<?php echo lc_h($hero_img); ?>" alt="LinkConnect 대시보드 미리보기" loading="lazy">
    </div>
  </div>
</section>

<div class="lc-metric-grid">
  <?php foreach (lc_sample_home_metrics() as $m) {
      $tone = isset($m['tone']) ? $m['tone'] : '';
      ?>
  <article class="lc-metric-card lc-metric-card--<?php echo lc_h($tone); ?>">
    <p class="lc-metric-card__value"><?php echo lc_h($m['value']); ?><small><?php echo lc_h($m['suffix']); ?></small></p>
    <p class="lc-metric-card__label"><?php echo lc_h($m['label']); ?></p>
  </article>
  <?php } ?>
</div>

<section class="lc-section lc-section--muted">
  <div class="lc-section__inner">
    <h2 class="lc-section__title">CPA 운영 흐름</h2>
    <p class="lc-section__lead">캠페인 선택부터 정산까지, 링크커넥트에서 투명하게 관리되는 CPA 프로세스입니다.</p>
    <div class="lc-flow">
      <?php foreach (lc_sample_cpa_flow() as $step) { ?>
      <article class="lc-flow__step">
        <span class="lc-flow__num"><?php echo lc_h($step['step']); ?></span>
        <h3 class="lc-flow__title"><?php echo lc_h($step['title']); ?></h3>
        <p class="lc-flow__desc"><?php echo lc_h($step['desc']); ?></p>
      </article>
      <?php } ?>
    </div>
  </div>
</section>

<section class="lc-section lc-section--navy" id="partner">
  <div class="lc-section__inner lc-intro">
    <div>
      <h2 class="lc-section__title">내 수익, DB, 유입분석을 <em>한눈에</em></h2>
      <p class="lc-section__lead">복잡한 데이터도 직관적인 대시보드에서. 전환율을 높이는 인사이트를 파트너센터에서 제공합니다.</p>
      <ul class="lc-intro__list">
        <?php foreach (array('수익금 정산 및 출금 신청', '실시간 DB 승인/반려 현황', '유입 URL 및 키워드 분석', '캠페인별 성과 리포트') as $item) { ?>
        <li><span class="lc-intro__list-icon lc-intro__list-icon--emerald">↗</span><?php echo lc_h($item); ?></li>
        <?php } ?>
      </ul>
      <p style="margin-top:1.5rem"><a class="lc-btn lc-btn--emerald" href="<?php echo lc_h($partner_url); ?>">파트너센터 바로가기</a></p>
    </div>
    <div class="lc-intro-panel">
      <div class="lc-intro-panel__head">
        <div class="lc-intro-panel__icon lc-intro-panel__icon--emerald">₩</div>
        <div>
          <div class="lc-intro-panel__label">출금 가능 수익금</div>
          <div class="lc-intro-panel__value">4,250,000 원</div>
        </div>
      </div>
      <div class="lc-intro-mini-grid">
        <div class="lc-intro-mini">
          <div class="lc-intro-mini__label">누적 DB</div>
          <div class="lc-intro-mini__value">405<small style="font-size:0.75rem;color:#94a3b8"> 건</small></div>
        </div>
        <div class="lc-intro-mini">
          <div class="lc-intro-mini__label">승인율</div>
          <div class="lc-intro-mini__value">82.5<small style="font-size:0.75rem;color:#94a3b8"> %</small></div>
        </div>
      </div>
      <div class="lc-chart-placeholder" aria-hidden="true">
        <p class="lc-muted" style="margin:0 0 0.75rem;font-size:0.8rem">최근 7일 DB 현황</p>
        <div class="lc-chart-placeholder__bars">
          <?php foreach (array(45, 52, 38, 65, 48, 72, 85) as $h) { ?>
          <span style="height:<?php echo (int) $h; ?>%"></span>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="lc-section lc-section--light" id="advertiser">
  <div class="lc-section__inner lc-intro">
    <div class="lc-intro-panel lc-intro-panel--light">
      <div class="lc-intro-panel__head">
        <div class="lc-intro-panel__icon lc-intro-panel__icon--cyan">💳</div>
        <div>
          <div class="lc-intro-panel__label">잔여 광고비 (캐시)</div>
          <div class="lc-intro-panel__value">12,500,000 원</div>
        </div>
      </div>
      <p class="lc-muted" style="margin:0 0 0.65rem;font-size:0.8rem;font-weight:600">실시간 DB 인입 현황</p>
      <div class="lc-db-feed">
        <?php
        $feeds = array(
            array('name' => '개인회생 상담 (홍길동)', 'time' => '방금 전', 'status' => '대기', 'source' => '네이버 블로그'),
            array('name' => '보험 비교 (김철수)', 'time' => '5분 전', 'status' => '승인', 'source' => '유튜브'),
            array('name' => '다이어트 (이영희)', 'time' => '12분 전', 'status' => '승인', 'source' => '인스타그램'),
            array('name' => '신차 렌트 (박민수)', 'time' => '28분 전', 'status' => '반려', 'source' => '페이스북'),
        );
        foreach ($feeds as $db) {
            $badge = $db['status'] === '승인' ? 'emerald' : ($db['status'] === '대기' ? 'amber' : 'red');
            ?>
        <div class="lc-db-feed__item">
          <div>
            <div class="lc-db-feed__name"><?php echo lc_h($db['name']); ?></div>
            <div class="lc-db-feed__source"><?php echo lc_h($db['source']); ?></div>
          </div>
          <div style="text-align:right">
            <div class="lc-muted" style="font-size:0.7rem"><?php echo lc_h($db['time']); ?></div>
            <?php echo lc_ui_badge($db['status'], $badge); ?>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
    <div>
      <h2 class="lc-section__title" style="color:var(--lc-slate-900)">광고주는 DB와 광고비를 <span class="lc-accent-cyan">투명하게 관리</span></h2>
      <p class="lc-section__lead">허수 DB는 필터링하고 진성 DB만 정산하세요. 광고 성과와 단가를 실시간으로 모니터링할 수 있습니다.</p>
      <ul class="lc-intro__list" style="color:var(--lc-slate-700)">
        <?php foreach (array('실시간 DB 확인 및 다운로드', '간편한 광고비 충전/차감 내역', '캠페인별 단가 및 ROI 분석', '매체별/파트너별 유입 품질 확인') as $item) { ?>
        <li><span class="lc-intro__list-icon lc-intro__list-icon--cyan">↗</span><?php echo lc_h($item); ?></li>
        <?php } ?>
      </ul>
      <p style="margin-top:1.5rem"><a class="lc-btn lc-btn--primary" href="<?php echo lc_h($merchant_url); ?>">광고주센터 바로가기</a></p>
    </div>
  </div>
</section>

<section class="lc-section lc-section--light">
  <div class="lc-section__inner" style="text-align:center">
    <h2 class="lc-section__title">왜 <em>링크커넥트</em>인가?</h2>
    <p class="lc-section__lead" style="margin:0 auto 2rem">CPA/CPS를 한 플랫폼에서 확장 가능하게 운영하는 SaaS형 제휴마케팅 인프라입니다.</p>
    <div class="lc-feature-grid">
      <?php
      $features = array(
          array('icon' => '⚡', 'title' => '다양한 CPA/CPS 상품', 'desc' => '모든 트래픽에 맞는 최적의 캠페인 매칭'),
          array('icon' => '📊', 'title' => '실시간 전환 추적', 'desc' => '누락 없는 강력한 자체 트래킹 시스템'),
          array('icon' => '🛡', 'title' => '투명한 승인/반려 확인', 'desc' => '명확한 사유 제공으로 신뢰할 수 있는 데이터'),
          array('icon' => '💰', 'title' => '빠르고 정확한 수익 정산', 'desc' => '주급/월급 정산 등 파트너 맞춤형 시스템'),
      );
      foreach ($features as $f) {
          ?>
      <article class="lc-feature-card">
        <div class="lc-feature-card__icon"><?php echo $f['icon']; ?></div>
        <h3 class="lc-feature-card__title"><?php echo lc_h($f['title']); ?></h3>
        <p class="lc-feature-card__desc"><?php echo lc_h($f['desc']); ?></p>
      </article>
      <?php } ?>
    </div>
    <div style="margin-top:2.5rem;display:flex;flex-wrap:wrap;justify-content:center;gap:0.75rem">
      <a class="lc-btn lc-btn--emerald" href="<?php echo lc_h($cpa_url); ?>">CPA 상품 보기</a>
      <a class="lc-btn lc-btn--outline" href="<?php echo lc_h($cps_url); ?>">CPS 준비 현황</a>
    </div>
  </div>
</section>

<section class="lc-section lc-section--muted" id="events">
  <div class="lc-section__inner">
    <div class="lc-section-head">
      <h2 class="lc-section__title">🎁 수익을 더 높이는 이벤트와 프로모션</h2>
      <a class="lc-btn lc-btn--ghost lc-btn--sm" href="<?php echo lc_h($events_url); ?>">더보기 →</a>
    </div>
    <div class="lc-event-board">
      <?php foreach (lc_sample_home_events() as $ev) { ?>
      <a class="lc-event-board__item" href="<?php echo lc_h($events_url); ?>">
        <div>
          <?php echo lc_ui_badge('진행중', 'emerald'); ?>
          <span class="lc-event-board__title"><?php echo lc_h($ev['title']); ?></span>
        </div>
        <span class="lc-event-board__date"><?php echo lc_h($ev['date']); ?></span>
      </a>
      <?php } ?>
    </div>
  </div>
</section>

<section class="lc-final-cta">
  <div class="lc-section__inner">
    <h2 class="lc-final-cta__title">광고주는 성과를 만들고,<br>파트너는 수익을 만듭니다.</h2>
    <p class="lc-final-cta__lead">지금 바로 링크커넥트에서 완벽한 제휴마케팅을 시작하세요.</p>
    <div class="lc-final-cta__actions">
      <a class="lc-btn lc-btn--white lc-btn--xl" href="<?php echo lc_h($partner_url); ?>">파트너로 시작하기</a>
      <a class="lc-btn lc-btn--emerald-dark lc-btn--xl" href="<?php echo lc_h(lc_inquiry_url()); ?>">광고주로 문의하기</a>
    </div>
  </div>
</section>
