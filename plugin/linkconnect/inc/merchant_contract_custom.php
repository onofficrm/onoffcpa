<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * 광고주별 첨부(서면) 계약서 적용.
 * - ADV-0008(모두의철거/김장수): assets/contracts/custom/adv-0008-moduicheolge.docx
 */

if (!function_exists('lc_merchant_contract_custom_registry')) {
    /**
     * @return array<string,array{label:string,docx:string,mt_code:string,title:string}>
     */
    function lc_merchant_contract_custom_registry()
    {
        return array(
            'adv-0008-moduicheolge' => array(
                'label'   => '모두의철거 x 링크커넥트 계약서',
                'docx'    => 'assets/contracts/custom/adv-0008-moduicheolge.docx',
                'mt_code' => 'ADV-0008',
                'title'   => 'CPA 광고 제휴 계약서 (모두의철거)',
            ),
        );
    }
}

if (!function_exists('lc_get_merchant_by_code')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_get_merchant_by_code($mt_code)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $mt_code = strtoupper(trim((string) $mt_code));
        if ($mt_code === '') {
            return null;
        }

        $table = lc_table('merchants');
        $code_esc = lc_sql_escape($mt_code);

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mt_code = '{$code_esc}' LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_contract_custom_resolve_merchant')) {
    /**
     * @return array{ok:bool,message:string,merchant?:array<string,mixed>|null,mt_id?:int}
     */
    function lc_merchant_contract_custom_resolve_merchant($mt_id = 0, $mt_code = '')
    {
        $mt_id = (int) $mt_id;
        $mt_code = strtoupper(trim((string) $mt_code));

        $merchant = null;
        if ($mt_id > 0) {
            $merchant = lc_get_merchant_by_id($mt_id);
        } elseif ($mt_code !== '') {
            $merchant = lc_get_merchant_by_code($mt_code);
        }

        if (!is_array($merchant)) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'merchant' => null, 'mt_id' => 0);
        }

        return array(
            'ok'       => true,
            'message'  => 'ok',
            'merchant' => $merchant,
            'mt_id'    => (int) ($merchant['mt_id'] ?? 0),
        );
    }
}

if (!function_exists('lc_merchant_contract_custom_party_a_from_merchant')) {
    /**
     * @param array<string,mixed> $merchant
     * @param array<string,string> $overrides
     * @return array<string,string>
     */
    function lc_merchant_contract_custom_party_a_from_merchant(array $merchant, array $overrides = array())
    {
        $member = lc_merchant_contract_get_member_row((string) ($merchant['mb_id'] ?? ''));
        $company = trim((string) ($merchant['mt_company'] ?? ''));
        if ($company === '') {
            $company = '광고주';
        }

        $name = is_array($member) ? trim((string) ($member['mb_name'] ?? '')) : '';
        $phone = is_array($member) ? trim((string) (($member['mb_hp'] ?? '') !== '' ? $member['mb_hp'] : ($member['mb_tel'] ?? ''))) : '';
        $email = is_array($member) ? trim((string) ($member['mb_email'] ?? '')) : '';

        $base = array(
            'company_name'        => $company,
            'representative_name' => $name !== '' ? $name : '김장수',
            'business_number'     => '',
            'company_address'     => '',
            'company_phone'       => $phone,
            'signer_name'         => $name !== '' ? $name : '김장수',
            'signer_position'     => '대표',
            'signer_phone'        => $phone,
            'signer_email'        => $email,
        );

        foreach ($overrides as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $base[$key] = trim($value);
            }
        }

        return $base;
    }
}

if (!function_exists('lc_merchant_contract_custom_render_moduicheolge_html')) {
    /**
     * 모두의철거 첨부 Word 계약서 본문 (플랫폼 HTML 뷰어용)
     *
     * @param array<string,string> $party_a
     */
    function lc_merchant_contract_custom_render_moduicheolge_html(array $party_a)
    {
        $party_b = lc_merchant_contract_party_b();
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

        $a_display = $a_name !== '' ? $a_name : '모두의철거';

        return <<<HTML
<div class="lc-contract-document">
  <header class="lc-contract-header">
    <p class="lc-contract-version">첨부 계약서: 모두의철거 x 링크커넥트 · 관리자 적용</p>
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
      <li><strong>단가</strong>: 유효 DB 1건당 광고비는 캠페인별로 플랫폼에 설정·합의한 단가 30,000원(VAT 별도)로 산정한다.</li>
      <li><strong>선충전 원칙</strong>: 광고비는 &quot;갑&quot;이 &quot;을&quot;의 플랫폼(또는 지정 계좌)에 선충전하는 것을 원칙으로 하며, 유효 DB 발생 시 충전금에서 실시간(또는 주기적)으로 차감된다.</li>
      <li><strong>기본 선충전액</strong>: 계정당 최소 [ 500,000 원 ](VAT 별도)</li>
      <li>충전된 광고비가 모두 소진되기 전, &quot;갑&quot;은 광고가 중단되지 않도록 사전에 재충전하여야 한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 5 조 [ DB 검수 및 승인 기준 ]</h2>
    <ol>
      <li>&quot;갑&quot;은 DB가 접수된 시점으로부터 [ 영업일 기준 10일 ] 이내에 정상(승인) 또는 불량(거절) 여부를 판별하여 시스템에 반영해야 한다.</li>
      <li>기한 내에 처리되지 않은 DB는 [ 영업일 기준 11일 차 ]에 자동으로 &apos;승인&apos; 처리된다. 단, 사후에 어뷰징(매크로, 명의 도용 등) 등 명백한 불법 및 계약 위반으로 판명된 DB에 대해서는 예외로 하며, &quot;갑&quot;은 기 승인된 건이라 하더라도 취소 및 환불(또는 차기 정산 시 차감)을 요구할 수 있다.</li>
      <li><strong>무효 DB (취소 기준)</strong>: 다음 각 호에 해당하는 경우에만 &quot;갑&quot;은 DB를 거절할 수 있으며, &quot;을&quot;이 증빙을 요청할 경우 &quot;갑&quot;은 이를 소명해야 한다.
        <ul>
          <li>결번, 수신 정지, 없는 번호 등 연락이 원천적으로 불가능한 경우</li>
          <li>중복 접수자 (가장 먼저 접수된 1건만 유효로 인정)</li>
          <li>만 19세 미만의 미성년자 (타겟이 성인인 경우)</li>
          <li>장난 기입, 명의 도용, 허위 정보 기재</li>
          <li>상품/서비스에 대한 전혀 인지가 없는 단순 오클릭 및 무관심자</li>
        </ul>
      </li>
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
      <li>&quot;갑&quot;이 고의로 정상 DB를 거절하거나 승인율을 악의적으로 낮추는 경우, &quot;을&quot;은 즉시 광고 송출을 중단하고 시정을 요구할 수 있으며, 이로 인한 손해 배상을 청구할 수 있다. 단, 고의 여부는 &quot;갑&quot;이 제공하는 거절 사유 소명 자료를 기준으로 상호 협의하여 판단한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 7 조 [ 개인정보의 보호 및 보안 ]</h2>
    <ol>
      <li>양 당사자는 업무상 취득한 고객의 개인정보를 처리함에 있어, 개인정보 보호법 및 정보통신망 이용촉진 및 정보보호 등에 관한 법률 등 대한민국의 관련 법령을 엄격히 준수하여야 한다.</li>
      <li>&quot;을&quot;은 DB 수집 시 관련 법령에 따른 수집·이용 및 제3자 제공 동의를 적법하게 취득하였음을 보증하며, 수집 과정의 하자나 동의 누락 등으로 인해 발생하는 모든 책임은 &quot;을&quot;이 부담한다.</li>
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
      <li>중도 해지를 원할 경우 해지 희망일 [ 15일 ] 전에 상대방에게 통보해야 한다. 단, &quot;을&quot;의 명백한 계약 위반(어뷰징 행위 방치 등)으로 인해 &quot;갑&quot;이 긴급하게 광고를 중단해야 하는 경우에는 사전 통보 없이 즉시 중단할 수 있다. 일방적 또는 긴급 광고 중단 시 대기 중인 DB는 제5조의 검수 기준에 따라 실 승인 여부를 확인하여 정산한다.</li>
      <li>캠페인의 최소 유지 기간은 [ 3개월 ]로 권장하며, 상호 합의하에 조기 종료할 수 있다. 잔여 선충전금은 광고 종료일로부터 영업일 기준 5일 이내에 환불 처리한다.</li>
    </ol>
  </section>

  <section class="lc-contract-article">
    <h2>제 10 조 [ 분쟁 해결 및 관할 법원 ]</h2>
    <ol>
      <li>본 계약의 해석 및 이행과 관련하여 분쟁이 발생한 경우 상호 합의에 의해 원만히 해결하는 것을 원칙으로 한다.</li>
      <li>합의가 이루어지지 않을 경우, 대한민국 법률의 적용을 받으며, &quot;갑&quot;의 본점 소재지를 관할하는 지방법원을 관할 법원으로 한다.</li>
    </ol>
    <p>본 계약의 성립을 증명하기 위하여 계약서 2부를 작성하고, 양 당사자가 기명날인 또는 서명한 후 각각 1부씩 보관한다. (전자적 방식으로 체결된 경우에도 서면 계약과 동일한 효력을 갖는다.)</p>
  </section>
</div>
HTML;
    }
}

if (!function_exists('lc_merchant_contract_custom_render_html')) {
    /**
     * @param array<string,string> $party_a
     */
    function lc_merchant_contract_custom_render_html($document_key, array $party_a)
    {
        $document_key = trim((string) $document_key);
        if ($document_key === 'adv-0008-moduicheolge') {
            return lc_merchant_contract_custom_render_moduicheolge_html($party_a);
        }

        return '';
    }
}

if (!function_exists('lc_merchant_contract_admin_apply_custom_document')) {
    /**
     * 첨부 계약서를 해당 광고주의 현재 버전 계약으로 적용하고 체결(signed) 처리한다.
     *
     * @param array{mtId?:int,mtCode?:string,documentKey?:string,force?:bool,partyOverrides?:array<string,string>} $options
     * @return array{ok:bool,message:string,mcId?:int,contractCode?:string,mtId?:int,skipped?:bool}
     */
    function lc_merchant_contract_admin_apply_custom_document(array $options = array())
    {
        $registry = lc_merchant_contract_custom_registry();
        $document_key = trim((string) ($options['documentKey'] ?? 'adv-0008-moduicheolge'));
        if ($document_key === '' || !isset($registry[$document_key])) {
            return array('ok' => false, 'message' => '알 수 없는 계약서 문서 키입니다.');
        }

        $meta = $registry[$document_key];
        $mt_id = (int) ($options['mtId'] ?? 0);
        $mt_code = trim((string) ($options['mtCode'] ?? ''));
        if ($mt_code === '' && $mt_id <= 0) {
            $mt_code = (string) ($meta['mt_code'] ?? '');
        }

        $resolved = lc_merchant_contract_custom_resolve_merchant($mt_id, $mt_code);
        if (empty($resolved['ok']) || !is_array($resolved['merchant'])) {
            return array('ok' => false, 'message' => $resolved['message'] ?? '광고주를 찾을 수 없습니다.');
        }

        $merchant = $resolved['merchant'];
        $mt_id = (int) $resolved['mt_id'];
        $expected_code = strtoupper((string) ($meta['mt_code'] ?? ''));
        $actual_code = strtoupper((string) ($merchant['mt_code'] ?? ''));
        if ($expected_code !== '' && $actual_code !== '' && $expected_code !== $actual_code) {
            return array(
                'ok'      => false,
                'message' => "문서({$meta['label']})는 {$expected_code} 전용입니다. (요청: {$actual_code})",
            );
        }

        $docx_rel = (string) ($meta['docx'] ?? '');
        $docx_abs = $docx_rel !== '' ? (LC_PLUGIN_PATH . '/' . ltrim($docx_rel, '/')) : '';
        // docx는 보관용 — 없으면 HTML 본문만으로 적용 가능

        if (function_exists('lc_merchant_contract_db_ensure_schema')) {
            $schema = lc_merchant_contract_db_ensure_schema();
            if (empty($schema['ok'])) {
                return array('ok' => false, 'message' => $schema['message'] ?? '계약 테이블 준비 실패');
            }
        }

        $force = !empty($options['force']);
        $version = lc_merchant_contract_current_version();
        $existing = lc_merchant_contract_get($mt_id, $version);
        if (
            !$force
            && is_array($existing)
            && function_exists('lc_merchant_contract_custom_snapshot_matches')
            && lc_merchant_contract_custom_snapshot_matches((string) ($existing['mc_contract_snapshot'] ?? ''))
        ) {
            return array(
                'ok'           => true,
                'message'      => '이미 해당 첨부 계약서가 적용되어 있습니다.',
                'skipped'      => true,
                'mcId'         => (int) ($existing['mc_id'] ?? 0),
                'contractCode' => (string) ($existing['mc_contract_code'] ?? ''),
                'mtId'         => $mt_id,
            );
        }

        $overrides = isset($options['partyOverrides']) && is_array($options['partyOverrides'])
            ? $options['partyOverrides']
            : array();
        $party_a = lc_merchant_contract_custom_party_a_from_merchant($merchant, $overrides);
        if (trim((string) ($party_a['company_name'] ?? '')) === '' || trim((string) $party_a['company_name']) === '광고주') {
            $party_a['company_name'] = '모두의철거';
        }
        if (trim((string) ($party_a['representative_name'] ?? '')) === '') {
            $party_a['representative_name'] = '김장수';
            $party_a['signer_name'] = '김장수';
        }

        $contract_html = lc_merchant_contract_custom_render_html($document_key, $party_a);
        if ($contract_html === '') {
            return array('ok' => false, 'message' => '계약서 HTML을 생성하지 못했습니다.');
        }

        $created = lc_merchant_contract_create_pending($mt_id);
        if (empty($created['ok'])) {
            return array('ok' => false, 'message' => $created['message'] ?? '계약 레코드 생성 실패');
        }

        $row = lc_merchant_contract_get($mt_id, $version);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '계약 레코드를 찾을 수 없습니다.');
        }

        $mc_id = (int) ($row['mc_id'] ?? 0);
        $contract_code = trim((string) ($row['mc_contract_code'] ?? ''));
        if ($contract_code === '') {
            $contract_code = lc_merchant_contract_generate_code($mt_id);
        }

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $sig = lc_merchant_contract_save_signature_png($mt_id, $png === false ? '' : $png);
        if (empty($sig['ok'])) {
            return array('ok' => false, 'message' => $sig['message'] ?? '서명 파일 저장 실패');
        }
        $signature_path = (string) $sig['path'];
        $signature_absolute = LC_PLUGIN_PATH . '/' . ltrim($signature_path, '/');

        $signed_at = date('Y-m-d H:i:s');
        $meta_req = lc_merchant_contract_request_meta();

        $pdf_path = '';
        $pdf_hash = '';
        if (function_exists('lc_merchant_contract_generate_signed_pdf')) {
            $pdf = lc_merchant_contract_generate_signed_pdf(array(
                'mt_id'               => $mt_id,
                'contract_version'    => $version,
                'contract_code'       => $contract_code,
                'company_name'        => $party_a['company_name'],
                'representative_name' => $party_a['representative_name'],
                'business_number'     => $party_a['business_number'],
                'company_address'     => $party_a['company_address'],
                'company_phone'       => $party_a['company_phone'],
                'signer_name'         => $party_a['signer_name'],
                'signer_position'     => $party_a['signer_position'],
                'signer_phone'        => $party_a['signer_phone'],
                'signer_email'        => $party_a['signer_email'],
                'signed_at'           => $signed_at,
                'signed_ip'           => $meta_req['ip'],
                'contract_html'       => $contract_html,
                'signature_absolute'  => $signature_absolute,
                'overwrite'           => true,
                'unique_filename'     => true,
            ));
            if (!empty($pdf['ok'])) {
                $pdf_path = (string) ($pdf['path'] ?? '');
                $pdf_hash = (string) ($pdf['hash'] ?? '');
            } elseif (is_array($existing) && trim((string) ($existing['mc_contract_pdf_path'] ?? '')) !== '') {
                // PDF 재생성 실패 시 기존 경로 유지하되 HTML 스냅샷은 교체
                $pdf_path = (string) $existing['mc_contract_pdf_path'];
                $pdf_hash = (string) ($existing['mc_contract_file_hash'] ?? '');
            }
            // PDF 실패해도 본문 HTML 스냅샷은 저장 (체결 인정은 custom snapshot 기준)
        }

        // 원본 docx도 광고주 스토리지에 보관(있을 때만)
        $version_dir = preg_replace('/[^a-zA-Z0-9._-]/', '_', $version);
        $store_dir = lc_merchant_contract_signed_pdf_dir($mt_id, $version_dir);
        if ($docx_abs !== '' && is_file($docx_abs) && $store_dir !== '') {
            if (!is_dir($store_dir)) {
                @mkdir($store_dir, 0755, true);
            }
            @copy($docx_abs, rtrim($store_dir, '/') . '/ORIGINAL_' . basename($docx_abs));
        }

        $company_snapshot = lc_merchant_contract_build_company_snapshot($mt_id);
        $company_snapshot['company_name'] = $party_a['company_name'];
        $company_snapshot['representative_name'] = $party_a['representative_name'];
        $company_snapshot['business_number'] = $party_a['business_number'];
        $company_snapshot['company_address'] = $party_a['company_address'];
        $company_snapshot['company_phone'] = $party_a['company_phone'];
        $company_snapshot['custom_document_key'] = $document_key;
        $company_snapshot['custom_document_label'] = $meta['label'];
        $company_snapshot['custom_document_docx'] = $docx_rel;

        $agreement_snapshot = array(
            'readAll'      => true,
            'hasAuthority' => true,
            'electronic'   => true,
            'noModify'     => true,
            'appliedBy'    => 'admin_custom_document',
            'documentKey'  => $document_key,
            'sourceFile'   => $docx_abs !== '' ? basename($docx_abs) : 'moduicheolge-html',
        );

        $special_note = '원본 첨부 파일: ' . $meta['label'] . ' — 관리자가 서면 계약을 시스템에 적용함. (단가 30,000원 / 영업일 10일 검수)';

        $table = lc_merchant_contract_table();
        $old_status = (string) ($row['mc_status'] ?? '');
        $status = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_SIGNED);
        $update = lc_sql_query(" UPDATE `{$table}` SET
            mc_status = '{$status}',
            mc_contract_code = '" . lc_sql_escape($contract_code) . "',
            mc_company_name = '" . lc_sql_escape($party_a['company_name']) . "',
            mc_representative_name = '" . lc_sql_escape($party_a['representative_name']) . "',
            mc_business_number = '" . lc_sql_escape($party_a['business_number']) . "',
            mc_company_address = '" . lc_sql_escape($party_a['company_address']) . "',
            mc_company_phone = '" . lc_sql_escape($party_a['company_phone']) . "',
            mc_signer_name = '" . lc_sql_escape($party_a['signer_name']) . "',
            mc_signer_position = '" . lc_sql_escape($party_a['signer_position']) . "',
            mc_signer_phone = '" . lc_sql_escape($party_a['signer_phone']) . "',
            mc_signer_email = '" . lc_sql_escape($party_a['signer_email']) . "',
            mc_signature_file_path = '" . lc_sql_escape($signature_path) . "',
            mc_signed_at = '" . lc_sql_escape($signed_at) . "',
            mc_signed_ip = '" . lc_sql_escape($meta_req['ip']) . "',
            mc_user_agent = '" . lc_sql_escape($meta_req['user_agent']) . "',
            mc_contract_pdf_path = '" . lc_sql_escape($pdf_path) . "',
            mc_contract_file_hash = '" . lc_sql_escape($pdf_hash) . "',
            mc_company_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($company_snapshot)) . "',
            mc_contract_snapshot = '" . lc_sql_escape($contract_html) . "',
            mc_agreement_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($agreement_snapshot)) . "',
            mc_negotiated_terms = '',
            mc_special_clauses = '" . lc_sql_escape($special_note) . "',
            mc_updated_at = NOW()
            WHERE mc_id = '{$mc_id}' AND mc_mt_id = '{$mt_id}' ", false);

        if ($update === false) {
            return array('ok' => false, 'message' => '계약서 저장에 실패했습니다.');
        }

        lc_merchant_contract_log_write(array(
            'mc_id'             => $mc_id,
            'mt_id'             => $mt_id,
            'mcl_contract_code' => $contract_code,
            'mcl_contract_version' => $version,
            'mcl_signed_at'     => $signed_at,
            'mcl_ip'            => $meta_req['ip'],
            'mcl_user_agent'    => $meta_req['user_agent'],
            'mcl_pdf_path'      => $pdf_path,
            'mcl_pdf_hash'      => $pdf_hash,
            'mcl_result'        => 'success',
            'mcl_message'       => '관리자 첨부 계약서 적용: ' . $meta['label'],
        ));

        if (function_exists('lc_merchant_contract_status_log_write')) {
            global $member;
            lc_merchant_contract_status_log_write(array(
                'mc_id'           => $mc_id,
                'mt_id'           => $mt_id,
                'admin_mb_id'     => is_array($member) ? (string) ($member['mb_id'] ?? '') : '',
                'mcsl_old_status' => $old_status,
                'mcsl_new_status' => LC_MERCHANT_CONTRACT_STATUS_SIGNED,
                'mcsl_reason'     => '첨부 계약서 적용: ' . $meta['label'],
                'mcsl_ip'         => $meta_req['ip'],
                'mcsl_user_agent' => $meta_req['user_agent'],
            ));
        }

        if (function_exists('lc_merchant_contract_access_cache_clear')) {
            lc_merchant_contract_access_cache_clear($mt_id);
        }

        return array(
            'ok'           => true,
            'message'      => "{$meta['label']}를 {$actual_code}에 적용하고 체결 완료 처리했습니다.",
            'skipped'      => false,
            'mcId'         => $mc_id,
            'contractCode' => $contract_code,
            'mtId'         => $mt_id,
        );
    }
}

if (!function_exists('lc_merchant_contract_custom_snapshot_matches')) {
    function lc_merchant_contract_custom_snapshot_matches($html)
    {
        $html = (string) $html;
        if ($html === '') {
            return false;
        }

        return strpos($html, '30,000원') !== false
            && strpos($html, '영업일 기준 10일') !== false
            && strpos($html, '영업일 기준 11일') !== false
            && (strpos($html, '모두의철거 x 링크커넥트') !== false || strpos($html, '관리자 적용') !== false);
    }
}

if (!function_exists('lc_merchant_contract_custom_ensure_adv0008')) {
    /**
     * ADV-0008(김장수) 모두의철거 계약서가 미적용이면 강제 적용.
     *
     * @return array{ok:bool,message:string,skipped?:bool,mcId?:int,applied?:bool}
     */
    function lc_merchant_contract_custom_ensure_adv0008($force = false)
    {
        if (!function_exists('lc_merchant_contract_admin_apply_custom_document')) {
            return array('ok' => false, 'message' => '커스텀 계약 모듈이 없습니다.');
        }

        $resolved = lc_merchant_contract_custom_resolve_merchant(0, 'ADV-0008');
        if (empty($resolved['ok']) || !is_array($resolved['merchant'])) {
            // mt_id=8 폴백
            $resolved = lc_merchant_contract_custom_resolve_merchant(8, '');
        }
        if (empty($resolved['ok']) || !is_array($resolved['merchant'])) {
            return array('ok' => false, 'message' => 'ADV-0008 광고주를 찾을 수 없습니다.');
        }

        $mt_id = (int) $resolved['mt_id'];
        $existing = lc_merchant_contract_get($mt_id);
        if (
            !$force
            && is_array($existing)
            && lc_merchant_contract_custom_snapshot_matches((string) ($existing['mc_contract_snapshot'] ?? ''))
        ) {
            return array(
                'ok'      => true,
                'message' => '이미 적용됨',
                'skipped' => true,
                'mcId'    => (int) ($existing['mc_id'] ?? 0),
                'applied' => false,
            );
        }

        $result = lc_merchant_contract_admin_apply_custom_document(array(
            'mtId'        => $mt_id,
            'mtCode'      => (string) ($resolved['merchant']['mt_code'] ?? 'ADV-0008'),
            'documentKey' => 'adv-0008-moduicheolge',
            'force'       => true,
            'partyOverrides' => array(
                'company_name'        => '모두의철거',
                'representative_name' => '김장수',
                'signer_name'         => '김장수',
                'signer_position'     => '대표',
            ),
        ));

        $result['applied'] = !empty($result['ok']) && empty($result['skipped']);

        return $result;
    }
}
