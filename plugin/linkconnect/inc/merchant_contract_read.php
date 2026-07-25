<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_sanitize_version')) {
    function lc_merchant_contract_sanitize_version($version)
    {
        $version = trim((string) $version);
        if ($version === '') {
            return '';
        }

        if (!preg_match('/^[A-Za-z0-9\-_.]+$/', $version)) {
            return '';
        }

        return $version;
    }
}

if (!function_exists('lc_merchant_contract_send_file_headers')) {
    function lc_merchant_contract_send_file_headers($content_type, $disposition = 'attachment', $filename = '')
    {
        header('Content-Type: ' . $content_type);
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        if ($filename !== '') {
            $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
            header('Content-Disposition: ' . $disposition . '; filename="' . $safe_name . '"');
        }
    }
}

if (!function_exists('lc_merchant_contract_mask_hash')) {
    function lc_merchant_contract_mask_hash($hash)
    {
        $hash = strtolower(trim((string) $hash));
        if ($hash === '') {
            return '';
        }
        if (strlen($hash) <= 16) {
            return $hash;
        }

        return substr($hash, 0, 8) . '…' . substr($hash, -8);
    }
}

if (!function_exists('lc_merchant_contract_is_row_fully_signed')) {
    /**
     * @param array<string,mixed> $contract
     */
    function lc_merchant_contract_is_row_fully_signed(array $contract)
    {
        if ((string) ($contract['mc_status'] ?? '') !== LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
            return false;
        }

        $signed_at = (string) ($contract['mc_signed_at'] ?? '');
        if ($signed_at === '' || $signed_at === '0000-00-00 00:00:00') {
            return false;
        }

        return trim((string) ($contract['mc_contract_pdf_path'] ?? '')) !== '';
    }
}

if (!function_exists('lc_merchant_contract_form_from_row')) {
    /**
     * 계약 행·스냅샷만 사용 (현재 회원정보 미반영)
     *
     * @param array<string,mixed>|null $contract
     * @return array<string,mixed>
     */
    function lc_merchant_contract_form_from_row($contract)
    {
        $form = array(
            'companyName'        => '',
            'representativeName' => '',
            'businessNumber'     => '',
            'companyAddress'     => '',
            'companyPhone'       => '',
            'signerName'         => '',
            'signerPosition'     => '',
            'signerPhone'        => '',
            'signerEmail'        => '',
            'negotiatedTerms'    => '',
            'specialClauses'     => '',
            'agreements'         => array(
                'readAll'      => false,
                'hasAuthority' => false,
                'electronic'   => false,
                'noModify'     => false,
            ),
            'agreementCheckedAt' => '',
        );

        if (!is_array($contract)) {
            return $form;
        }

        $form['companyName'] = (string) ($contract['mc_company_name'] ?? '');
        $form['representativeName'] = (string) ($contract['mc_representative_name'] ?? '');
        $form['businessNumber'] = (string) ($contract['mc_business_number'] ?? '');
        $form['companyAddress'] = (string) ($contract['mc_company_address'] ?? '');
        $form['companyPhone'] = (string) ($contract['mc_company_phone'] ?? '');
        $form['signerName'] = (string) ($contract['mc_signer_name'] ?? '');
        $form['signerPosition'] = (string) ($contract['mc_signer_position'] ?? '');
        $form['signerPhone'] = (string) ($contract['mc_signer_phone'] ?? '');
        $form['signerEmail'] = (string) ($contract['mc_signer_email'] ?? '');
        if (isset($contract['mc_negotiated_terms'])) {
            $form['negotiatedTerms'] = (string) $contract['mc_negotiated_terms'];
        }
        if (isset($contract['mc_special_clauses'])) {
            $form['specialClauses'] = (string) $contract['mc_special_clauses'];
        }

        $snapshot = lc_merchant_contract_decode_snapshot($contract['mc_company_snapshot'] ?? '');
        if (is_array($snapshot)) {
            foreach (array(
                'companyName'        => 'company_name',
                'representativeName' => 'representative_name',
                'businessNumber'     => 'business_number',
                'companyAddress'     => 'company_address',
                'companyPhone'       => 'company_phone',
            ) as $form_key => $snap_key) {
                if ($form[$form_key] === '' && !empty($snapshot[$snap_key])) {
                    $form[$form_key] = (string) $snapshot[$snap_key];
                }
            }
        }

        $agreement = lc_merchant_contract_decode_snapshot($contract['mc_agreement_snapshot'] ?? '');
        if (is_array($agreement)) {
            if (isset($agreement['agreements']) && is_array($agreement['agreements'])) {
                $form['agreements'] = array_merge($form['agreements'], $agreement['agreements']);
            }
            if (!empty($agreement['agreementCheckedAt'])) {
                $form['agreementCheckedAt'] = (string) $agreement['agreementCheckedAt'];
            }
            if ($form['negotiatedTerms'] === '' && !empty($agreement['negotiatedTerms'])) {
                $form['negotiatedTerms'] = (string) $agreement['negotiatedTerms'];
            }
            if ($form['specialClauses'] === '' && !empty($agreement['specialClauses'])) {
                $form['specialClauses'] = (string) $agreement['specialClauses'];
            }
        }

        return $form;
    }
}

if (!function_exists('lc_merchant_contract_list_by_merchant')) {
    /**
     * @return list<array<string,mixed>>
     */
    function lc_merchant_contract_list_by_merchant($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0 || !lc_db_installed() || !lc_db_table_exists(lc_merchant_contract_table())) {
            return array();
        }

        $table = lc_merchant_contract_table();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE mc_mt_id = '{$mt_id}' ORDER BY mc_created_at DESC, mc_id DESC ", false);
        if (!$result) {
            return array();
        }

        $rows = array();
        while ($row = sql_fetch_array($result)) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_contract_get_by_id')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_get_by_id($mc_id)
    {
        $mc_id = (int) $mc_id;
        if ($mc_id <= 0 || !lc_db_installed()) {
            return null;
        }

        $table = lc_merchant_contract_table();

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mc_id = '{$mc_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_contract_resolve_read_version')) {
    function lc_merchant_contract_resolve_read_version($mt_id, $requested_version = null)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return null;
        }

        if ($requested_version !== null && $requested_version !== '') {
            $requested_version = lc_merchant_contract_sanitize_version($requested_version);
            if ($requested_version === '') {
                return null;
            }
            $contract = lc_merchant_contract_get($mt_id, $requested_version);
            if (!is_array($contract) || !lc_merchant_contract_is_row_fully_signed($contract)) {
                return null;
            }

            return (string) $contract['mc_contract_version'];
        }

        $latest = lc_merchant_contract_latest_signed($mt_id);
        if (is_array($latest) && lc_merchant_contract_is_row_fully_signed($latest)) {
            return (string) $latest['mc_contract_version'];
        }

        $current = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        if (is_array($current) && lc_merchant_contract_is_row_fully_signed($current)) {
            return (string) $current['mc_contract_version'];
        }

        return null;
    }
}

if (!function_exists('lc_merchant_contract_history_item_to_api')) {
    /**
     * @param array<string,mixed> $contract
     * @return array<string,mixed>
     */
    function lc_merchant_contract_history_item_to_api(array $contract)
    {
        return array(
            'id'              => (int) ($contract['mc_id'] ?? 0),
            'contractVersion' => (string) ($contract['mc_contract_version'] ?? ''),
            'contractCode'    => (string) ($contract['mc_contract_code'] ?? ''),
            'status'          => (string) ($contract['mc_status'] ?? ''),
            'statusLabel'     => lc_merchant_contract_status_label($contract['mc_status'] ?? ''),
            'signedAt'        => (string) ($contract['mc_signed_at'] ?? ''),
            'createdAt'       => (string) ($contract['mc_created_at'] ?? ''),
            'isFullySigned'   => lc_merchant_contract_is_row_fully_signed($contract),
            'isCurrentVersion'=> (string) ($contract['mc_contract_version'] ?? '') === lc_merchant_contract_current_version(),
        );
    }
}

if (!function_exists('lc_merchant_contract_read_to_api')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_contract_read_to_api(array $contract)
    {
        $mt_id = (int) ($contract['mc_mt_id'] ?? 0);
        $version = (string) ($contract['mc_contract_version'] ?? '');
        $form = lc_merchant_contract_form_from_row($contract);
        $party_b = lc_merchant_contract_party_b();
        $hash = (string) ($contract['mc_contract_file_hash'] ?? '');

        $contract_html = (string) ($contract['mc_contract_snapshot'] ?? '');
        if ($contract_html === '' && function_exists('lc_merchant_contract_render_html')) {
            $contract_html = lc_merchant_contract_render_html(array(
                'company_name'        => $form['companyName'],
                'representative_name' => $form['representativeName'],
                'business_number'     => $form['businessNumber'],
                'company_address'     => $form['companyAddress'],
                'company_phone'       => $form['companyPhone'],
            ), array(
                'negotiatedTerms' => $form['negotiatedTerms'],
                'specialClauses'  => $form['specialClauses'],
            ));
        }

        $version_q = rawurlencode($version);
        $signature_path = (string) ($contract['mc_signature_file_path'] ?? '');
        $signature_url = '';
        if ($signature_path !== '') {
            $signature_url = LC_PLUGIN_URL . '/merchant/contract-signature.php?version=' . $version_q;
        }

        return array(
            'id'                 => (int) ($contract['mc_id'] ?? 0),
            'advertiserId'       => $mt_id,
            'contractVersion'    => $version,
            'status'             => (string) ($contract['mc_status'] ?? ''),
            'statusLabel'        => lc_merchant_contract_status_label($contract['mc_status'] ?? ''),
            'contractCode'       => (string) ($contract['mc_contract_code'] ?? ''),
            'companyName'        => $form['companyName'],
            'representativeName' => $form['representativeName'],
            'businessNumber'     => $form['businessNumber'],
            'companyAddress'     => $form['companyAddress'],
            'companyPhone'       => $form['companyPhone'],
            'signerName'         => $form['signerName'],
            'signerPosition'     => $form['signerPosition'],
            'signerPhone'        => $form['signerPhone'],
            'signerEmail'        => $form['signerEmail'],
            'negotiatedTerms'    => $form['negotiatedTerms'],
            'specialClauses'     => $form['specialClauses'],
            'signedAt'           => (string) ($contract['mc_signed_at'] ?? ''),
            'signedIp'           => (string) ($contract['mc_signed_ip'] ?? ''),
            'userAgent'          => (string) ($contract['mc_user_agent'] ?? ''),
            'createdAt'          => (string) ($contract['mc_created_at'] ?? ''),
            'pdfHash'            => $hash,
            'pdfHashMasked'      => lc_merchant_contract_mask_hash($hash),
            'agreements'         => $form['agreements'],
            'agreementCheckedAt' => $form['agreementCheckedAt'],
            'partyB'             => array(
                'companyName'        => (string) ($party_b['company_name'] ?? ''),
                'representativeName' => (string) ($party_b['representative_name'] ?? ''),
                'businessNumber'     => (string) ($party_b['business_number'] ?? ''),
                'companyAddress'     => (string) ($party_b['company_address'] ?? ''),
                'companyPhone'       => (string) ($party_b['company_phone'] ?? ''),
            ),
            'contractHtml'       => $contract_html,
            'signatureUrl'       => $signature_url,
            'documentPreviewUrl' => LC_PLUGIN_URL . '/merchant/contract-document.php?version=' . $version_q,
            'documentPdfUrl'     => LC_PLUGIN_URL . '/merchant/contract-download.php?version=' . $version_q,
            'isReadOnly'         => true,
            'isFullySigned'      => lc_merchant_contract_is_row_fully_signed($contract),
            'canAddAddendum'     => function_exists('lc_merchant_contract_addendum_can_add')
                ? lc_merchant_contract_addendum_can_add($contract, 'merchant')
                : false,
        );
    }
}

if (!function_exists('lc_merchant_contract_campaign_schedule_html')) {
    /**
     * 광고상품(캠페인)별 계약 부속 표기용 HTML
     *
     * @param array<string,mixed> $campaign
     */
    function lc_merchant_contract_campaign_schedule_html(array $campaign)
    {
        $esc = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $name = $esc($campaign['cp_name'] ?? $campaign['name'] ?? '');
        $code = $esc($campaign['cp_code'] ?? $campaign['code'] ?? '');
        $type = $esc(strtoupper((string) ($campaign['cp_type'] ?? $campaign['type'] ?? 'CPA')));
        $price = isset($campaign['cpa'])
            ? (int) $campaign['cpa']
            : (function_exists('lc_campaign_resolve_merchant_price') ? (int) lc_campaign_resolve_merchant_price($campaign) : (int) ($campaign['cp_price'] ?? 0));
        $price_fmt = number_format($price);
        $status = '';
        if (isset($campaign['status']) && is_string($campaign['status'])) {
            $status = $esc($campaign['status']);
        } elseif (function_exists('lc_campaign_merchant_status_ui')) {
            $status = $esc(lc_campaign_merchant_status_ui($campaign['cp_status'] ?? ''));
        }

        return <<<HTML
<section class="lc-contract-article lc-contract-campaign-schedule">
  <h2>부속 [ 광고상품별 계약 조건 ]</h2>
  <p>본 부속은 위 CPA 광고 제휴 계약서에 따라 아래 광고상품에 적용되는 조건을 명시한다.</p>
  <dl>
    <dt>광고상품명</dt><dd>{$name}</dd>
    <dt>상품코드</dt><dd>{$code}</dd>
    <dt>광고 유형</dt><dd>{$type}</dd>
    <dt>유효 DB 1건당 단가 (VAT 별도)</dt><dd>{$price_fmt} 원</dd>
    <dt>상품 상태</dt><dd>{$status}</dd>
  </dl>
  <p>단가 및 상품 운영 조건은 플랫폼에 등록된 해당 광고상품 설정을 기준으로 하며, 상호 합의 시 변경될 수 있다.</p>
</section>
HTML;
    }
}

if (!function_exists('lc_merchant_contract_append_campaign_to_html')) {
    /**
     * @param array<string,mixed>|null $campaign
     */
    function lc_merchant_contract_append_campaign_to_html($html, $campaign)
    {
        $html = (string) $html;
        if (!is_array($campaign) || $html === '') {
            return $html;
        }

        $schedule = lc_merchant_contract_campaign_schedule_html($campaign);
        if (stripos($html, '</div>') !== false) {
            $pos = strripos($html, '</div>');
            if ($pos !== false) {
                return substr($html, 0, $pos) . $schedule . substr($html, $pos);
            }
        }

        return $html . $schedule;
    }
}

if (!function_exists('lc_merchant_contract_campaigns_for_view')) {
    /**
     * @return array<int,array<string,mixed>>
     */
    function lc_merchant_contract_campaigns_for_view($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0 || !function_exists('lc_campaign_list_for_merchant')) {
            return array();
        }

        $signed = function_exists('lc_merchant_contract_is_signed') && lc_merchant_contract_is_signed($mt_id);
        $status = function_exists('lc_merchant_contract_status') ? (string) lc_merchant_contract_status($mt_id) : '';
        $status_label = function_exists('lc_merchant_contract_status_label')
            ? lc_merchant_contract_status_label($status)
            : $status;

        $items = array();
        foreach (lc_campaign_list_for_merchant($mt_id) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $api = function_exists('lc_campaign_merchant_to_api')
                ? lc_campaign_merchant_to_api($row)
                : array(
                    'id'   => (int) ($row['cp_id'] ?? 0),
                    'code' => (string) ($row['cp_code'] ?? ''),
                    'name' => (string) ($row['cp_name'] ?? ''),
                    'type' => strtoupper((string) ($row['cp_type'] ?? 'CPA')),
                    'cpa'  => (int) ($row['cp_price'] ?? 0),
                    'status' => (string) ($row['cp_status'] ?? ''),
                    'statusCode' => (string) ($row['cp_status'] ?? ''),
                );

            $api['contractViewable'] = $signed;
            $api['contractStatus'] = $status;
            $api['contractStatusLabel'] = $signed ? '체결완료' : ($status_label !== '' ? $status_label : '미체결');

            $items[] = $api;
        }

        return $items;
    }
}

if (!function_exists('lc_merchant_contract_find_merchant_campaign')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_find_merchant_campaign($mt_id, $cp_id)
    {
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;
        if ($mt_id <= 0 || $cp_id <= 0 || !function_exists('lc_campaign_list_for_merchant')) {
            return null;
        }

        foreach (lc_campaign_list_for_merchant($mt_id) as $row) {
            if (is_array($row) && (int) ($row['cp_id'] ?? 0) === $cp_id) {
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('lc_merchant_contract_read_view_for_merchant')) {
    /**
     * @return array{ok:bool,message:string,data?:array<string,mixed>}
     */
    function lc_merchant_contract_read_view_for_merchant($mt_id, $requested_version = null, $cp_id = 0)
    {
        $mt_id = (int) $mt_id;
        $cp_id = (int) $cp_id;
        $version = lc_merchant_contract_resolve_read_version($mt_id, $requested_version);
        if ($version === null) {
            return array('ok' => false, 'message' => '열람 가능한 체결 계약서가 없습니다.');
        }

        $contract = lc_merchant_contract_get($mt_id, $version);
        if (!is_array($contract) || (int) ($contract['mc_mt_id'] ?? 0) !== $mt_id) {
            return array('ok' => false, 'message' => '계약서를 찾을 수 없습니다.');
        }

        $history = array();
        foreach (lc_merchant_contract_list_by_merchant($mt_id) as $row) {
            if (lc_merchant_contract_is_row_fully_signed($row)) {
                $history[] = lc_merchant_contract_history_item_to_api($row);
            }
        }

        $campaigns = lc_merchant_contract_campaigns_for_view($mt_id);
        $campaign_api = null;
        $campaign_row = null;
        if ($cp_id > 0) {
            $campaign_row = lc_merchant_contract_find_merchant_campaign($mt_id, $cp_id);
            if (!is_array($campaign_row)) {
                return array('ok' => false, 'message' => '해당 광고상품을 찾을 수 없습니다.');
            }
            $campaign_api = function_exists('lc_campaign_merchant_to_api')
                ? lc_campaign_merchant_to_api($campaign_row)
                : array(
                    'id'   => $cp_id,
                    'name' => (string) ($campaign_row['cp_name'] ?? ''),
                    'code' => (string) ($campaign_row['cp_code'] ?? ''),
                    'cpa'  => (int) ($campaign_row['cp_price'] ?? 0),
                );
        }

        $contract_api = lc_merchant_contract_read_to_api($contract);
        if (!empty($contract_api['contractHtml']) && function_exists('lc_merchant_contract_append_addenda_to_html')) {
            $contract_api['contractHtml'] = lc_merchant_contract_append_addenda_to_html(
                (string) $contract_api['contractHtml'],
                (int) ($contract['mc_id'] ?? 0)
            );
        }
        if (is_array($campaign_row) && !empty($contract_api['contractHtml'])) {
            $contract_api['contractHtml'] = lc_merchant_contract_append_campaign_to_html(
                (string) $contract_api['contractHtml'],
                $campaign_row
            );
            $contract_api['campaignId'] = $cp_id;
        }

        $addenda = function_exists('lc_merchant_contract_addendum_list_for_api')
            ? lc_merchant_contract_addendum_list_for_api((int) ($contract['mc_id'] ?? 0), true)
            : array();

        return array(
            'ok'      => true,
            'message' => '',
            'data'    => array(
                'contract'  => $contract_api,
                'history'   => $history,
                'campaigns' => $campaigns,
                'campaign'  => $campaign_api,
                'addenda'   => $addenda,
                'csrfToken' => function_exists('lc_merchant_contract_csrf_token')
                    ? lc_merchant_contract_csrf_token()
                    : '',
            ),
        );
    }
}

if (!function_exists('lc_merchant_contract_assert_merchant_owns')) {
    /**
     * @param array<string,mixed> $contract
     */
    function lc_merchant_contract_assert_merchant_owns(array $contract, $mt_id)
    {
        return (int) ($contract['mc_mt_id'] ?? 0) === (int) $mt_id;
    }
}
