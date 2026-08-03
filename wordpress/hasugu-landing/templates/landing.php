<?php
if (!defined('ABSPATH')) {
    exit;
}

/** @var array<string,string> $ctx */
$brand = $ctx['brand_name'] !== '' ? $ctx['brand_name'] : '하수구폴리스';
$areas = $ctx['areas'] !== '' ? $ctx['areas'] : '서울 · 인천 · 경기';
$phone = $ctx['phone'];
$phone_href = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : '';
$privacy = $ctx['privacy_url'];
$mount_id = $ctx['mount_id'];
$lk_code = $ctx['lk_code'];
$channel = $ctx['channel'];
$sub_id = $ctx['sub_id'];
$script_src = $ctx['script_src'];
$config_url = $ctx['config_url'];

$symptoms = array(
    array('label' => '물이 천천히 내려가요', 'hint' => '욕실, 베란다, 세탁실 등 배수가 느리거나 막힌 경우'),
    array('label' => '싱크대 또는 변기가 막혔어요', 'hint' => '음식물·휴지·이물질로 물이 내려가지 않는 경우'),
    array('label' => '하수구에서 악취가 올라와요', 'hint' => '배수구와 배관에서 반복적으로 올라오는 악취'),
    array('label' => '물이 거꾸로 역류해요', 'hint' => '하수 또는 오수가 거꾸로 올라오는 경우'),
    array('label' => '뚫었는데 자꾸 다시 막혀요', 'hint' => '일시적 통수 후 동일 구간이 재발하는 경우'),
    array('label' => '공용배관막힘', 'hint' => '다세대·상가·아파트 공용 배관 문제'),
);

$services = array(
    array('title' => '하수구막힘', 'desc' => '욕실·베란다·세탁실 배수 불량 점검'),
    array('title' => '싱크대막힘', 'desc' => '음식물·기름때·이물질 막힘 상담'),
    array('title' => '변기막힘', 'desc' => '넘침·배수 불량 원인 확인'),
    array('title' => '공용배관', 'desc' => '관리실 협의 절차까지 안내'),
);

$steps = array(
    array('n' => '01', 'title' => '상담 접수', 'desc' => '전화 또는 상담신청으로 증상과 지역을 확인합니다.'),
    array('n' => '02', 'title' => '방문 일정 안내', 'desc' => '지역과 기사 일정을 확인한 뒤 방문 가능 시간을 안내합니다.'),
    array('n' => '03', 'title' => '현장 점검·안내', 'desc' => '배관 상태를 점검하고 필요한 작업과 예상 비용을 설명합니다.'),
    array('n' => '04', 'title' => '동의 후 작업', 'desc' => '고객이 작업 내용에 동의한 경우에만 진행합니다.'),
);

$principles = array(
    '상담만으로 비용이 발생하지 않습니다',
    '현장 확인 후 필요한 작업과 예상 비용을 안내합니다',
    '고객이 동의한 작업만 진행합니다',
    '동일 구간 7일 이내 재발 시 재점검 여부를 안내합니다',
    '아파트 공용배관은 관리실 협의 절차를 함께 안내합니다',
);

$faqs = array(
    array(
        'q' => '상담만 받아도 비용이 발생하나요?',
        'a' => '전화·온라인 상담 접수만으로 비용이 발생하지 않습니다. 현장 확인 후 필요한 작업과 예상 비용을 안내하고, 동의하신 작업만 진행합니다.',
    ),
    array(
        'q' => '현장에서 추가 비용을 요구하지 않나요?',
        'a' => '작업 전 안내한 범위 안에서 진행합니다. 예상과 다른 구조·추가 구간이 발견되면 먼저 설명하고, 동의 후에만 진행합니다.',
    ),
    array(
        'q' => '아파트·빌라 공용배관도 가능한가요?',
        'a' => '세대 내부 배관은 바로 상담 가능합니다. 공용배관은 관리사무소·입주자대표 협의가 필요할 수 있어, 접수 시 현장 유형을 알려주시면 절차를 안내합니다.',
    ),
    array(
        'q' => '야간·주말에도 출동하나요?',
        'a' => '긴급 증상은 접수 가능합니다. 지역·기사 일정에 따라 방문 가능 시간이 달라지며, 확인 후 가능한 일정을 안내합니다.',
    ),
    array(
        'q' => '접수 후 얼마나 빨리 연락받나요?',
        'a' => '영업시간 기준 평균 10분 이내 연락을 목표로 합니다. 야간·주말·통화 폭주 시에는 순차적으로 안내됩니다.',
    ),
);

$faq_entities = array();
foreach ($faqs as $faq) {
    $faq_entities[] = array(
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => array(
            '@type' => 'Answer',
            'text' => $faq['a'],
        ),
    );
}
$schema = array(
    '@context' => 'https://schema.org',
    '@graph' => array(
        array(
            '@type' => 'LocalBusiness',
            'name' => $brand,
            'description' => '하수구막힘·싱크대막힘·변기막힘·악취·역류 상담 및 배관 점검',
            'areaServed' => array('서울', '인천', '경기도'),
            'url' => (is_singular() ? get_permalink() : home_url('/')),
        ),
        array(
            '@type' => 'FAQPage',
            'mainEntity' => $faq_entities,
        ),
    ),
);
?>
<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<div class="hsg" id="hsg-landing" data-brand="<?php echo esc_attr($brand); ?>">
  <header class="hsg-top">
    <div class="hsg-wrap hsg-top__inner">
      <a class="hsg-logo" href="#hsg-landing"><?php echo esc_html($brand); ?></a>
      <nav class="hsg-nav" aria-label="랜딩 메뉴">
        <a href="#hsg-symptoms">증상</a>
        <a href="#hsg-process">진행과정</a>
        <a href="#hsg-faq">FAQ</a>
        <a class="hsg-nav__cta" href="#hsg-form">상담신청</a>
      </nav>
      <?php if ($phone_href !== '') : ?>
        <a class="hsg-top__phone" href="<?php echo esc_attr($phone_href); ?>"><?php echo esc_html($phone); ?></a>
      <?php endif; ?>
    </div>
  </header>

  <section class="hsg-hero">
    <div class="hsg-hero__bg" aria-hidden="true"></div>
    <div class="hsg-wrap hsg-hero__grid">
      <div class="hsg-hero__copy">
        <p class="hsg-eyebrow"><?php echo esc_html($brand); ?> · 하수구·배관 문제 해결 전문</p>
        <h1>악취·역류·막힘,<br />막힌 원인을 먼저 확인합니다</h1>
        <p class="hsg-lead">
          하수구·싱크대·변기·공용배관까지 전문 장비로 점검하고,
          협의된 작업만 진행합니다.
        </p>
        <ul class="hsg-pills" aria-label="핵심 안내">
          <li>평균 10분 이내 연락</li>
          <li>동의 후 작업만 진행</li>
          <li><?php echo esc_html($areas); ?></li>
        </ul>
        <div class="hsg-hero__actions">
          <a class="hsg-btn hsg-btn--primary" href="#hsg-form">빠른 상담신청</a>
          <a class="hsg-btn hsg-btn--ghost" href="#hsg-symptoms">증상 체크하기</a>
        </div>
      </div>

      <aside class="hsg-hero__card" aria-label="빠른 상담">
        <div class="hsg-hero__card-head">
          <strong>이름·연락처만 남겨주세요</strong>
          <span>영업시간 기준 평균 10분 이내 연락 목표</span>
        </div>
        <div class="hsg-form-mount hsg-form-mount--hero">
          <?php if ($lk_code === '') : ?>
            <p class="hsg-form-warn">홍보코드(lkCode)가 필요합니다. 플러그인 설정 또는 숏코드에 코드를 넣어 주세요.</p>
          <?php else : ?>
            <div
              id="<?php echo esc_attr($mount_id); ?>"
              class="linkconnect-lead-root hsg-lead-root"
              data-lk-code="<?php echo esc_attr($lk_code); ?>"
              <?php echo $channel !== '' ? ' data-channel="' . esc_attr($channel) . '"' : ''; ?>
              <?php echo $sub_id !== '' ? ' data-sub-id="' . esc_attr($sub_id) . '"' : ''; ?>
            ></div>
            <script
              src="<?php echo esc_url($script_src); ?>"
              data-lk-code="<?php echo esc_attr($lk_code); ?>"
              data-target="#<?php echo esc_attr($mount_id); ?>"
              <?php echo $channel !== '' ? 'data-channel="' . esc_attr($channel) . '" ' : ''; ?>
              <?php echo $sub_id !== '' ? 'data-sub-id="' . esc_attr($sub_id) . '" ' : ''; ?>
              data-config-url="<?php echo esc_url($config_url); ?>"
              async
            ></script>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </section>

  <section class="hsg-section" id="hsg-symptoms">
    <div class="hsg-wrap">
      <div class="hsg-section__head">
        <p class="hsg-eyebrow">긴급 증상 체크</p>
        <h2>지금 이런 증상이 있으신가요?</h2>
        <p>해당하는 증상을 누르면 상담 신청으로 이동합니다.</p>
      </div>
      <div class="hsg-symptom-grid">
        <?php foreach ($symptoms as $item) : ?>
          <button type="button" class="hsg-symptom" data-hsg-symptom="<?php echo esc_attr($item['label']); ?>">
            <strong><?php echo esc_html($item['label']); ?></strong>
            <span><?php echo esc_html($item['hint']); ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="hsg-section hsg-section--tint">
    <div class="hsg-wrap">
      <div class="hsg-section__head">
        <p class="hsg-eyebrow">주요 발생 문제</p>
        <h2>생활 속 배관 문제, 한 번에 상담하세요</h2>
        <p>단순히 막힌 곳만 뚫는 것이 아니라 위치와 원인을 확인한 뒤 필요한 작업을 안내합니다.</p>
      </div>
      <div class="hsg-service-grid">
        <?php foreach ($services as $svc) : ?>
          <article class="hsg-service">
            <h3><?php echo esc_html($svc['title']); ?></h3>
            <p><?php echo esc_html($svc['desc']); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="hsg-section" id="hsg-process">
    <div class="hsg-wrap">
      <div class="hsg-section__head">
        <p class="hsg-eyebrow">진행 과정</p>
        <h2>접수부터 작업까지, 진행 과정을 확인하세요</h2>
        <p>작업 전 안내를 확인한 후 진행 여부를 결정하세요.</p>
      </div>
      <ol class="hsg-steps">
        <?php foreach ($steps as $step) : ?>
          <li>
            <span class="hsg-steps__n"><?php echo esc_html($step['n']); ?></span>
            <div>
              <strong><?php echo esc_html($step['title']); ?></strong>
              <p><?php echo esc_html($step['desc']); ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="hsg-section hsg-section--tint">
    <div class="hsg-wrap hsg-split">
      <div>
        <p class="hsg-eyebrow">안심 상담 원칙</p>
        <h2>부담을 줄이는<br />안심 상담 원칙</h2>
        <p>현장 구조와 배관 상태에 따라 작업 방법과 비용이 달라질 수 있습니다. 확정 비용은 현장 확인 후 안내합니다.</p>
      </div>
      <ul class="hsg-checklist">
        <?php foreach ($principles as $line) : ?>
          <li><?php echo esc_html($line); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="hsg-section" id="hsg-faq">
    <div class="hsg-wrap hsg-faq-wrap">
      <div class="hsg-section__head">
        <p class="hsg-eyebrow">자주 묻는 질문</p>
        <h2>상담 전 궁금한 점</h2>
      </div>
      <div class="hsg-faq">
        <?php foreach ($faqs as $i => $faq) : ?>
          <details class="hsg-faq__item" <?php echo $i === 0 ? 'open' : ''; ?>>
            <summary><?php echo esc_html($faq['q']); ?></summary>
            <p><?php echo esc_html($faq['a']); ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="hsg-section hsg-section--cta" id="hsg-form">
    <div class="hsg-wrap hsg-cta">
      <div class="hsg-cta__copy">
        <p class="hsg-eyebrow">빠른 상담</p>
        <h2>증상이 있다면<br />빠른 상담을 신청해주세요</h2>
        <p>지역과 증상을 남겨주시면 접수 내용을 확인한 후 연락드립니다.</p>
        <ul class="hsg-pills hsg-pills--dark">
          <li>상담만으로 비용 없음</li>
          <li><?php echo esc_html($areas); ?> 출동 가능</li>
          <li>평균 10분 이내 연락 목표</li>
        </ul>
        <?php if ($phone_href !== '') : ?>
          <a class="hsg-btn hsg-btn--light" href="<?php echo esc_attr($phone_href); ?>">전화 상담 <?php echo esc_html($phone); ?></a>
        <?php endif; ?>
      </div>
      <div class="hsg-cta__form">
        <div class="hsg-form-mount" id="hsg-form-anchor">
          <?php if ($lk_code === '') : ?>
            <p class="hsg-form-warn">홍보코드(lkCode)가 필요합니다. 플러그인 설정에서 코드를 저장해 주세요.</p>
          <?php else : ?>
            <p class="hsg-cta__form-note">위 히어로 상담폼과 동일한 접수로 연결됩니다. 아직 작성하지 않으셨다면 상단 폼을 이용해 주세요.</p>
            <a class="hsg-btn hsg-btn--primary" href="#<?php echo esc_attr($mount_id); ?>">상담폼으로 이동</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <footer class="hsg-foot">
    <div class="hsg-wrap hsg-foot__inner">
      <strong><?php echo esc_html($brand); ?></strong>
      <p>하수구막힘 · 싱크대막힘 · 변기막힘 · 악취 · 역류 상담</p>
      <p class="hsg-foot__meta">
        <?php echo esc_html($areas); ?>
        <?php if ($privacy !== '') : ?>
          · <a href="<?php echo esc_url($privacy); ?>">개인정보처리방침</a>
        <?php endif; ?>
      </p>
    </div>
  </footer>
</div>
