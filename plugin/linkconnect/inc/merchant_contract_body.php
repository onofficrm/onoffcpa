<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_render_html')) {
    /**
     * @param array<string,string> $party_a
     * @param array{negotiatedTerms?:string,specialClauses?:string} $extras
     */
    function lc_merchant_contract_render_html(array $party_a, array $extras = array())
    {
        $party_b = lc_merchant_contract_party_b();
        $version = lc_merchant_contract_current_version();

        $esc = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $a_name = $esc($party_a['company_name'] ?? '');
        $a_rep = $esc($party_a['representative_name'] ?? '');
        $a_biz = $esc($party_a['business_number'] ?? '');
        $a_addr = $esc($party_a['company_address'] ?? '');
        $a_phone = $esc($party_a['company_phone'] ?? '');

        $b_name = $esc($party_b['company_name'] ?? '');
        $b_rep = $esc($party_b['representative_name'] ?? '');
        $b_biz = $esc($party_b['business_number'] ?? '');
        $b_addr = $esc($party_b['company_address'] ?? '');
        $b_phone = $esc($party_b['company_phone'] ?? '');

        $a_display = $a_name !== '' ? $a_name : '[ 광고주 회사명 ]';

        $negotiated = trim((string) ($extras['negotiatedTerms'] ?? ''));
        $special = trim((string) ($extras['specialClauses'] ?? ''));
        $extra_sections = '';
        if ($negotiated !== '') {
            $negotiated_html = nl2br($esc($negotiated));
            $extra_sections .= <<<HTML

  <section class="lc-contract-article lc-contract-extra-terms">
    <h2>제 11 조 [ 별도 협의사항 ]</h2>
    <p>본 조는 기본 계약 외에 갑·을이 별도로 합의한 사항을 기재한다. 본 조와 기본 조항이 충돌하는 경우, 본 조의 내용이 우선한다.</p>
    <div class="lc-contract-extra-body">{$negotiated_html}</div>
  </section>
HTML;
        }
        if ($special !== '') {
            $special_html = nl2br($esc($special));
            $article_no = $negotiated !== '' ? '12' : '11';
            $extra_sections .= <<<HTML

  <section class="lc-contract-article lc-contract-extra-terms lc-contract-special-clauses">
    <h2>제 {$article_no} 조 [ 특별조항 ]</h2>
    <p>본 조는 기본 계약에 추가되는 특별조항(제한·의무·패널티 등)을 기재한다. 본 조와 기본 조항이 충돌하는 경우, 본 조의 내용이 우선한다.</p>
    <div class="lc-contract-extra-body">{$special_html}</div>
  </section>
HTML;
        }

        return <<<HTML
<div class="lc-contract-document">
  <header class="lc-contract-header">
    <p class="lc-contract-version">계약서 버전: {$esc($version)}</p>
    <h1>CPA 광고 제휴 계약서</h1>
    <p class="lc-contract-lead">
      {$a_display}(이하 &quot;갑&quot;이라 한다)와 CPA 광고 플랫폼 &apos;링크커넥트&apos;를 운영하는 {$b_name}(이하 &quot;을&quot;이라 한다)는
      온라인 CPA(Cost Per Action) 마케팅 업무 제휴와 관련하여 상호 신의성실의 원칙에 따라 다음과 같이 계약을 체결한다.
    </p>
  </header>

  <section class="lc-contract-parties">
    <div class="lc-contract-party">
      <h2>갑 (광고주)</h2>
      <dl>
        <dt>상호명</dt><dd>{$a_name}</dd>
        <dt>대표자</dt><dd>{$a_rep}</dd>
        <dt>사업자번호</dt><dd>{$a_biz}</dd>
        <dt>주소</dt><dd>{$a_addr}</dd>
        <dt>연락처</dt><dd>{$a_phone}</dd>
      </dl>
    </div>
    <div class="lc-contract-party">
      <h2>을 (운영사)</h2>
      <dl>
        <dt>상호명</dt><dd>{$b_name}</dd>
        <dt>대표자</dt><dd>{$b_rep}</dd>
        <dt>사업자번호</dt><dd>{$b_biz}</dd>
        <dt>주소</dt><dd>{$b_addr}</dd>
        <dt>연락처</dt><dd>{$b_phone}</dd>
      </dl>
    </div>
  </section>

  <section class="lc-contract-article">
    <h2>제 1 조 [ 목적 ]</h2>
    <p>본 계약은 &quot;갑&quot;이 자사의 제품 및 서비스에 대한 마케팅을 &quot;을&quot;에게 위탁하고, &quot;을&quot;이 이를 수행하여 발생한 합당한 성과(DB)에 대하여 &quot;갑&quot;이 광고 대금을 지급함에 있어 필요한 제반 사항과 양 당사자의 권리 및 의무를 명확히 규정함을 목적으로 한다.</p>
  </section>

  <section class="lc-contract-article">
    <h2>제 2 조 [ 용어의 정의 ]</h2>
    <ol>
      <li><strong>CPA (Cost Per Action)</strong>: &quot;을&quot;이 진행하는 광고를 통해 소비자가 &quot;갑&quot;이 지정한 특정 행동(상담 신청, 회원가입, 구매 등)을 완료했을 때 광고비가 과금되는 방식을 의미한다.</li>
      <li><strong>DB (Database)</strong>: 광고를 통해 수집된 잠재 고객의 정보(이름, 연락처 등)를 의미한다.</li>
      <li><strong>유효 DB</strong>: 수집된 DB 중 제5조의 &apos;취소 및 거절 기준&apos;에 해당하지 않으며, &quot;갑&quot;의 실제 영업 및 마케팅에 활용 가능한 정상적인 고객 정보를 의미한다.</li>
      <li><strong>어뷰징 (Abusing)</strong>: 매크로 프로그램, 인센티브 제공(보상형 트래픽), 허위 사실 유포, 명의 도용 등 비정상적이거나 불법적인 방법으로 DB를 발생시키는 일체의 행위를 의미한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 3 조 [ 당사자의 의무 ]</h2>
    <p><strong>&quot;갑&quot;의 의무:</strong></p>
    <ol>
      <li>&quot;갑&quot;은 &quot;을&quot;이 원활하게 마케팅을 진행할 수 있도록 정확한 상품 정보 및 마케팅 가이드라인을 제공해야 한다.</li>
      <li>&quot;갑&quot;은 수집된 DB에 대해 신속하게 확인 및 상담을 진행하여 &quot;을&quot;의 광고 성과가 정당하게 평가받을 수 있도록 협조한다.</li>
    </ol>
    <p><strong>&quot;을&quot;의 의무:</strong></p>
    <ol>
      <li>&quot;을&quot;은 &quot;갑&quot;의 브랜드 가치가 훼손되지 않도록 합법적이고 윤리적인 테두리 내에서 마케팅을 수행한다.</li>
      <li>&quot;을&quot;은 하위 마케터나 제휴 매체사가 어뷰징 행위나 과대/허위 광고를 하지 않도록 철저히 관리·감독할 책임이 있다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 4 조 [ 광고비 및 정산 방식 ]</h2>
    <ol>
      <li><strong>단가</strong>: 유효 DB 1건당 광고비는 캠페인별로 플랫폼에 설정·합의한 단가(VAT 별도)로 산정한다.</li>
      <li><strong>선충전 원칙</strong>: 광고비는 &quot;갑&quot;이 &quot;을&quot;의 플랫폼(또는 지정 계좌)에 선충전하는 것을 원칙으로 하며, 유효 DB 발생 시 충전금에서 실시간(또는 주기적)으로 차감된다.</li>
      <li><strong>기본 선충전액</strong>: 계정당 최소 [ 500,000 원 ](VAT 별도)</li>
      <li>충전된 광고비가 모두 소진되기 전, &quot;갑&quot;은 광고가 중단되지 않도록 사전에 재충전하여야 한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 5 조 [ DB 검수 및 승인 기준 ]</h2>
    <ol>
      <li>&quot;갑&quot;은 DB가 접수된 시점으로부터 [ 7일 ] 이내에 정상(승인) 또는 불량(거절) 여부를 판별하여 시스템에 반영해야 한다.</li>
      <li>기한 내에 처리되지 않은 DB는 [ 8일 차 ]에 자동으로 &apos;승인&apos; 처리되며, 이후에는 어떠한 사유로도 취소 및 환불이 불가하다.</li>
      <li><strong>무효 DB (취소 기준)</strong>: 다음 각 호에 해당하는 경우에만 &quot;갑&quot;은 DB를 거절할 수 있으며, &quot;을&quot;이 증빙을 요청할 경우 &quot;갑&quot;은 이를 소명해야 한다.
        <ul>
          <li>결번, 수신 정지, 없는 번호 등 연락이 원천적으로 불가능한 경우</li>
          <li>중복 접수자 (가장 먼저 접수된 1건만 유효로 인정)</li>
          <li>만 19세 미만의 미성년자 (타겟이 성인인 경우)</li>
          <li>장난 기입, 명의 도용, 허위 정보 기재</li>
          <li>상품/서비스에 대한 전혀 인지가 없는 단순 오클릭 및 무관심자</li>
        </ul>
      </li>
      <li><strong>최소 승인율 보장</strong>: 정상적인 마케팅 환경 유지를 위해 &quot;갑&quot;은 유입된 전체 DB 중 최소 [ 50% ] 이상을 승인해야 한다. (단, 물리적 결번 및 중복 DB는 모수에서 제외한다.)</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 6 조 [ 금지 행위 및 패널티 ]</h2>
    <ol>
      <li>&quot;을&quot; 및 소속 마케터는 다음의 행위를 할 수 없으며, 적발 시 &quot;갑&quot;은 즉시 계약 해지 및 해당 DB에 대한 정산 거부를 요구할 수 있다.
        <ul>
          <li>금전, 포인트 등 대가를 제공하고 DB를 유도하는 행위(인센티브 트래픽)</li>
          <li>허위 사실 및 과장 광고로 고객을 기망하는 행위</li>
        </ul>
      </li>
      <li>&quot;갑&quot;이 고의로 정상 DB를 거절하거나 승인율을 악의적으로 낮추는 경우, &quot;을&quot;은 즉시 광고 송출을 중단하고 시정을 요구할 수 있으며, 이로 인한 손해 배상을 청구할 수 있다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 7 조 [ 개인정보의 보호 및 보안 ]</h2>
    <ol>
      <li>양 당사자는 업무상 취득한 고객의 개인정보를 처리함에 있어, 개인정보 보호법 및 정보통신망 이용촉진 및 정보보호 등에 관한 법률 등 대한민국의 관련 법령을 엄격히 준수하여야 한다.</li>
      <li>&quot;을&quot;은 수집된 DB를 &quot;갑&quot;에게 전달하는 목적 외에 타사에 판매, 양도, 공유하거나 다른 용도로 절대 사용할 수 없다.</li>
      <li>&quot;갑&quot;은 제공받은 DB를 사전에 고객이 동의한 목적(예: 해당 상품 상담)으로만 사용하여야 하며, 이를 위반하여 발생하는 모든 법적 책임은 &quot;갑&quot;에게 있다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 8 조 [ 비밀 유지 ]</h2>
    <p>양 당사자는 본 계약과 관련하여 지득한 상대방의 기술 정보, 마케팅 전략, 단가, 계약 조건 등의 영업비밀을 계약 기간은 물론 계약 종료 후에도 제3자에게 누설하거나 본 계약 외의 목적으로 사용할 수 없다.</p>
  </section>

  <section class="lc-contract-article">
    <h2>제 9 조 [ 계약 기간 및 해지 ]</h2>
    <ol>
      <li>본 계약의 유효 기간은 계약 체결일로부터 [ 1년 ]으로 한다.</li>
      <li>계약 만료 30일 전까지 서면(이메일 포함)에 의한 계약 종료 또는 변경의 의사표시가 없는 한, 본 계약은 동일한 조건으로 6개월씩 자동 연장된다.</li>
      <li>중도 해지를 원할 경우 해지 희망일 [ 15일 ] 전에 상대방에게 통보해야 한다. 만약 &quot;갑&quot;이 15일 전 통보 없이 일방적으로 광고를 중단할 경우, 그 시점에 대기 중인 모든 DB는 승인율 70%를 일괄 적용하여 정산한다.</li>
      <li>캠페인의 최소 유지 기간은 [ 3개월 ]로 권장하며, 상호 합의하에 조기 종료할 수 있다. 잔여 선충전금은 광고 종료일로부터 영업일 기준 5일 이내에 환불 처리한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 10 조 [ 분쟁 해결 및 관할 법원 ]</h2>
    <p>본 계약의 해석 및 이행과 관련하여 분쟁이 발생한 경우 상호 합의에 의해 원만히 해결하는 것을 원칙으로 한다. 합의가 이루어지지 않을 경우, 대한민국 법률의 적용을 받으며, &quot;갑&quot;의 본점 소재지를 관할하는 지방법원을 관할 법원으로 한다.</p>
    <p>본 계약의 성립을 증명하기 위하여 계약서 2부를 작성하고, 양 당사자가 기명날인 또는 서명한 후 각각 1부씩 보관한다. (전자적 방식으로 체결된 경우에도 서면 계약과 동일한 효력을 갖는다.)</p>
  </section>
{$extra_sections}
</div>
HTML;
    }
}

if (!function_exists('lc_merchant_contract_document_styles')) {
    function lc_merchant_contract_document_styles()
    {
        return <<<'CSS'
.lc-contract-document { max-width: 820px; margin: 0 auto; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans KR", sans-serif; line-height: 1.7; font-size: 15px; }
.lc-contract-header { text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; }
.lc-contract-version { font-size: 12px; color: #64748b; margin-bottom: 0.5rem; }
.lc-contract-header h1 { font-size: 1.75rem; font-weight: 800; margin: 0 0 0.75rem; }
.lc-contract-lead { color: #475569; margin: 0; text-align: left; line-height: 1.75; }
.lc-contract-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem; }
@media (max-width: 640px) { .lc-contract-parties { grid-template-columns: 1fr; } }
.lc-contract-party { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; }
.lc-contract-party h2 { font-size: 0.875rem; font-weight: 700; color: #0891b2; margin: 0 0 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.lc-contract-party dl { margin: 0; display: grid; gap: 0.35rem; }
.lc-contract-party dt { font-size: 0.75rem; color: #64748b; font-weight: 600; }
.lc-contract-party dd { margin: 0 0 0.5rem; font-weight: 500; }
.lc-contract-article { margin-bottom: 1.5rem; }
.lc-contract-article h2 { font-size: 1rem; font-weight: 700; margin: 0 0 0.5rem; color: #1e293b; }
.lc-contract-article p, .lc-contract-article ol, .lc-contract-article ul { margin: 0 0 0.75rem; color: #334155; }
.lc-contract-article ol { padding-left: 1.25rem; }
.lc-contract-article ul { padding-left: 1.25rem; list-style: disc; margin-top: 0.5rem; }
.lc-contract-article li { margin-bottom: 0.35rem; }
.lc-contract-extra-terms { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed #cbd5e1; }
.lc-contract-extra-body { margin-top: 0.75rem; padding: 1rem 1.15rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; white-space: pre-wrap; color: #0f172a; font-weight: 500; }
.lc-contract-addendum-meta { font-size: 0.875rem; color: #64748b; }
CSS;
    }
}
