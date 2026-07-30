<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_table')) {
    function lc_merchant_contract_table()
    {
        return lc_table('merchant_contracts');
    }
}

if (!function_exists('lc_merchant_contract_encode_snapshot')) {
    function lc_merchant_contract_encode_snapshot($data)
    {
        if ($data === null || $data === '') {
            return '';
        }

        if (is_string($data)) {
            return $data;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        return $json === false ? '' : $json;
    }
}

if (!function_exists('lc_merchant_contract_decode_snapshot')) {
    /**
     * @return array<string,mixed>|list<mixed>|null
     */
    function lc_merchant_contract_decode_snapshot($raw)
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('lc_merchant_contract_get_member_row')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_get_member_row($mb_id)
    {
        global $g5;

        if ($mb_id === '' || !isset($g5['member_table'])) {
            return null;
        }

        $mb_id = lc_sql_escape($mb_id);

        return lc_sql_fetch(" SELECT mb_id, mb_name, mb_nick, mb_email, mb_hp, mb_tel FROM `{$g5['member_table']}` WHERE mb_id = '{$mb_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_contract_build_company_snapshot')) {
    /**
     * 계약 당시 광고주 회사정보 전체 스냅샷
     *
     * @return array<string,mixed>
     */
    function lc_merchant_contract_build_company_snapshot($mt_id)
    {
        $mt_id = (int) $mt_id;
        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;

        if (!is_array($merchant)) {
            return array(
                'mt_id'       => $mt_id,
                'snapshot_at' => date('c'),
            );
        }

        $member = lc_merchant_contract_get_member_row((string) ($merchant['mb_id'] ?? ''));

        return array(
            'mt_id'               => (int) $merchant['mt_id'],
            'mb_id'               => (string) ($merchant['mb_id'] ?? ''),
            'mt_code'             => (string) ($merchant['mt_code'] ?? ''),
            'mt_company'          => (string) ($merchant['mt_company'] ?? ''),
            'mt_status'           => (string) ($merchant['mt_status'] ?? ''),
            'mt_balance'          => (int) ($merchant['mt_balance'] ?? 0),
            'company_name'        => (string) ($merchant['mt_company'] ?? ''),
            'representative_name' => '',
            'business_number'     => '',
            'company_address'     => '',
            'company_phone'       => '',
            'member'              => is_array($member) ? array(
                'mb_id'    => (string) ($member['mb_id'] ?? ''),
                'mb_name'  => (string) ($member['mb_name'] ?? ''),
                'mb_nick'  => (string) ($member['mb_nick'] ?? ''),
                'mb_email' => (string) ($member['mb_email'] ?? ''),
                'mb_hp'    => (string) ($member['mb_hp'] ?? ''),
                'mb_tel'   => (string) ($member['mb_tel'] ?? ''),
            ) : null,
            'snapshot_at'         => date('c'),
        );
    }
}

if (!function_exists('lc_merchant_contract_get')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_get($mt_id, $version = null)
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_merchant_contract_table())) {
            return null;
        }

        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return null;
        }

        if ($version === null || $version === '') {
            $version = lc_merchant_contract_current_version();
        }

        $table = lc_merchant_contract_table();
        $version_esc = lc_sql_escape((string) $version);

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mc_mt_id = '{$mt_id}' AND mc_contract_version = '{$version_esc}' LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_contract_latest_signed')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_latest_signed($mt_id)
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_merchant_contract_table())) {
            return null;
        }

        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return null;
        }

        $table = lc_merchant_contract_table();
        $signed = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_SIGNED);

        return lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE mc_mt_id = '{$mt_id}' AND mc_status = '{$signed}'
            ORDER BY mc_signed_at DESC, mc_id DESC
            LIMIT 1 ");
    }
}

if (!function_exists('lc_merchant_contract_status')) {
    /**
     * 현재 계약서 버전 기준 상태.
     * 계약 행이 없으면 pending, 이전 버전만 체결된 경우 renewal.
     */
    function lc_merchant_contract_status($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return LC_MERCHANT_CONTRACT_STATUS_PENDING;
        }

        $version = lc_merchant_contract_current_version();
        $contract = lc_merchant_contract_get($mt_id, $version);

        if (is_array($contract) && isset($contract['mc_status']) && $contract['mc_status'] !== '') {
            return (string) $contract['mc_status'];
        }

        $signed_old = lc_merchant_contract_latest_signed($mt_id);
        if (is_array($signed_old)) {
            $old_version = (string) ($signed_old['mc_contract_version'] ?? '');
            if ($old_version !== '' && $old_version !== $version) {
                return LC_MERCHANT_CONTRACT_STATUS_RENEWAL;
            }
        }

        return LC_MERCHANT_CONTRACT_STATUS_PENDING;
    }
}

if (!function_exists('lc_merchant_contract_is_signed')) {
    function lc_merchant_contract_is_signed($mt_id)
    {
        return lc_merchant_contract_is_fully_signed($mt_id);
    }
}

if (!function_exists('lc_merchant_contract_requires')) {
    /**
     * 현재 버전 계약서 체결이 필요한지 여부
     */
    function lc_merchant_contract_requires($mt_id)
    {
        return !lc_merchant_contract_is_signed($mt_id);
    }
}

if (!function_exists('lc_merchant_contract_create_pending')) {
    /**
     * @return array{ok:bool,message:string,contract:array<string,mixed>|null,created:bool}
     */
    function lc_merchant_contract_create_pending($mt_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'contract' => null, 'created' => false);
        }

        if (!lc_db_table_exists(lc_merchant_contract_table())) {
            if (function_exists('lc_merchant_contract_db_ensure_schema')) {
                $schema = lc_merchant_contract_db_ensure_schema();
                if (empty($schema['ok'])) {
                    return array('ok' => false, 'message' => $schema['message'] ?? '계약 테이블 준비 실패', 'contract' => null, 'created' => false);
                }
            } else {
                return array('ok' => false, 'message' => '계약 테이블이 없습니다.', 'contract' => null, 'created' => false);
            }
        }

        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => '광고주 ID가 올바르지 않습니다.', 'contract' => null, 'created' => false);
        }

        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        if (!is_array($merchant)) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'contract' => null, 'created' => false);
        }

        $version = lc_merchant_contract_current_version();
        $existing = lc_merchant_contract_get($mt_id, $version);
        if (is_array($existing)) {
            return array(
                'ok'       => true,
                'message'  => '이미 계약 레코드가 있습니다.',
                'contract' => $existing,
                'created'  => false,
            );
        }

        $snapshot = lc_merchant_contract_build_company_snapshot($mt_id);
        $snapshot_json = lc_merchant_contract_encode_snapshot($snapshot);
        $table = lc_merchant_contract_table();

        $company_name = lc_sql_escape((string) ($snapshot['company_name'] ?? ''));
        $status = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_PENDING);
        $version_esc = lc_sql_escape($version);
        $snapshot_esc = lc_sql_escape($snapshot_json);

        $insert = lc_sql_query(" INSERT INTO `{$table}`
            SET mc_mt_id = '{$mt_id}',
                mc_contract_version = '{$version_esc}',
                mc_status = '{$status}',
                mc_company_name = '{$company_name}',
                mc_representative_name = '',
                mc_business_number = '',
                mc_company_address = '',
                mc_company_phone = '',
                mc_signer_name = '',
                mc_signer_position = '',
                mc_signer_phone = '',
                mc_signer_email = '',
                mc_signature_file_path = '',
                mc_signed_at = NULL,
                mc_signed_ip = '',
                mc_user_agent = '',
                mc_contract_pdf_path = '',
                mc_contract_file_hash = '',
                mc_company_snapshot = '{$snapshot_esc}',
                mc_contract_snapshot = '',
                mc_agreement_snapshot = '',
                mc_created_at = NOW(),
                mc_updated_at = NOW() ", false);

        if ($insert === false) {
            $dup = lc_merchant_contract_get($mt_id, $version);
            if (is_array($dup)) {
                return array(
                    'ok'       => true,
                    'message'  => '이미 계약 레코드가 있습니다.',
                    'contract' => $dup,
                    'created'  => false,
                );
            }

            return array('ok' => false, 'message' => '계약 레코드 생성 실패: ' . lc_sql_error(), 'contract' => null, 'created' => false);
        }

        $contract = lc_merchant_contract_get($mt_id, $version);

        return array(
            'ok'       => true,
            'message'  => 'pending 계약 레코드가 생성되었습니다.',
            'contract' => is_array($contract) ? $contract : null,
            'created'  => true,
        );
    }
}

if (!function_exists('lc_merchant_contract_to_api')) {
    /**
     * @param array<string,mixed> $contract
     * @return array<string,mixed>
     */
    function lc_merchant_contract_to_api(array $contract)
    {
        return array(
            'id'              => (int) ($contract['mc_id'] ?? 0),
            'advertiserId'    => (int) ($contract['mc_mt_id'] ?? 0),
            'contractCode'    => (string) ($contract['mc_contract_code'] ?? ''),
            'contractVersion' => (string) ($contract['mc_contract_version'] ?? ''),
            'status'          => (string) ($contract['mc_status'] ?? ''),
            'statusLabel'     => lc_merchant_contract_status_label($contract['mc_status'] ?? ''),
            'companyName'     => (string) ($contract['mc_company_name'] ?? ''),
            'signerName'      => (string) ($contract['mc_signer_name'] ?? ''),
            'signedAt'        => (string) ($contract['mc_signed_at'] ?? ''),
            'createdAt'       => (string) ($contract['mc_created_at'] ?? ''),
            'updatedAt'       => (string) ($contract['mc_updated_at'] ?? ''),
            'pdfDownloadUrl'  => LC_PLUGIN_URL . '/merchant/contract-download.php',
        );
    }
}

if (!function_exists('lc_merchant_contract_api_csrf_ok')) {
    function lc_merchant_contract_api_csrf_ok()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';

        $check = static function ($url) use ($host) {
            if ($url === '' || $host === '') {
                return false;
            }
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['host'])) {
                return false;
            }

            return strtolower((string) $parts['host']) === $host;
        };

        if ($origin !== '') {
            return $check($origin);
        }
        if ($referer !== '') {
            return $check($referer);
        }

        return false;
    }
}

if (!function_exists('lc_merchant_contract_csrf_token')) {
    function lc_merchant_contract_csrf_token()
    {
        if (!isset($_SESSION['lc_merchant_contract_csrf']) || !is_string($_SESSION['lc_merchant_contract_csrf']) || $_SESSION['lc_merchant_contract_csrf'] === '') {
            $_SESSION['lc_merchant_contract_csrf'] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION['lc_merchant_contract_csrf'];
    }
}

if (!function_exists('lc_merchant_contract_csrf_verify')) {
    function lc_merchant_contract_csrf_verify($token)
    {
        $expected = isset($_SESSION['lc_merchant_contract_csrf']) ? (string) $_SESSION['lc_merchant_contract_csrf'] : '';

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }
}

if (!function_exists('lc_merchant_contract_can_write')) {
    function lc_merchant_contract_can_write($mt_id)
    {
        if (lc_merchant_contract_is_signed($mt_id)) {
            return false;
        }

        $status = lc_merchant_contract_status($mt_id);
        $blocked = array(
            LC_MERCHANT_CONTRACT_STATUS_SIGNED,
            LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING,
            LC_MERCHANT_CONTRACT_STATUS_CANCELLED,
            LC_MERCHANT_CONTRACT_STATUS_EXPIRED,
        );

        return !in_array($status, $blocked, true);
    }
}

if (!function_exists('lc_merchant_contract_get_form_defaults')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_contract_get_form_defaults($mt_id)
    {
        $mt_id = (int) $mt_id;
        $merchant = lc_get_merchant_by_id($mt_id);
        $member = is_array($merchant) ? lc_merchant_contract_get_member_row((string) ($merchant['mb_id'] ?? '')) : null;
        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        $snapshot = is_array($contract) ? lc_merchant_contract_decode_snapshot($contract['mc_company_snapshot'] ?? '') : null;

        $defaults = array(
            'companyName'        => '',
            'representativeName' => '',
            'businessNumber'     => '',
            'companyAddress'     => '',
            'companyPhone'       => '',
            'contactName'        => '',
            'contactPhone'       => '',
            'contactEmail'       => '',
            'signerName'         => '',
            'signerPosition'     => '',
            'signerPhone'        => '',
            'signerEmail'        => '',
            'negotiatedTerms'    => '',
            'specialClauses'     => '',
            'step'               => 1,
            'agreements'         => array(
                'readAll'      => false,
                'hasAuthority' => false,
                'electronic'   => false,
                'noModify'     => false,
            ),
            'agreementCheckedAt' => '',
        );

        if (is_array($merchant)) {
            $defaults['companyName'] = (string) ($merchant['mt_company'] ?? '');
        }
        if (is_array($member)) {
            $defaults['contactName'] = (string) ($member['mb_name'] ?? '');
            $defaults['contactPhone'] = (string) (($member['mb_hp'] ?? '') !== '' ? $member['mb_hp'] : ($member['mb_tel'] ?? ''));
            $defaults['contactEmail'] = (string) ($member['mb_email'] ?? '');
        }
        if (is_array($snapshot)) {
            foreach (array(
                'companyName'        => 'company_name',
                'representativeName' => 'representative_name',
                'businessNumber'     => 'business_number',
                'companyAddress'     => 'company_address',
                'companyPhone'       => 'company_phone',
                'contactName'        => 'contact_name',
                'contactPhone'       => 'contact_phone',
                'contactEmail'       => 'contact_email',
            ) as $form_key => $snap_key) {
                if (!empty($snapshot[$snap_key])) {
                    $defaults[$form_key] = (string) $snapshot[$snap_key];
                }
            }
        }
        if (is_array($contract)) {
            if ((string) ($contract['mc_company_name'] ?? '') !== '') {
                $defaults['companyName'] = (string) $contract['mc_company_name'];
            }
            if ((string) ($contract['mc_representative_name'] ?? '') !== '') {
                $defaults['representativeName'] = (string) $contract['mc_representative_name'];
            }
            if ((string) ($contract['mc_business_number'] ?? '') !== '') {
                $defaults['businessNumber'] = (string) $contract['mc_business_number'];
            }
            if ((string) ($contract['mc_company_address'] ?? '') !== '') {
                $defaults['companyAddress'] = (string) $contract['mc_company_address'];
            }
            if ((string) ($contract['mc_company_phone'] ?? '') !== '') {
                $defaults['companyPhone'] = (string) $contract['mc_company_phone'];
            }
            $defaults['signerName'] = (string) ($contract['mc_signer_name'] ?? '');
            $defaults['signerPosition'] = (string) ($contract['mc_signer_position'] ?? '');
            $defaults['signerPhone'] = (string) ($contract['mc_signer_phone'] ?? '');
            $defaults['signerEmail'] = (string) ($contract['mc_signer_email'] ?? '');
            if (isset($contract['mc_negotiated_terms'])) {
                $defaults['negotiatedTerms'] = (string) $contract['mc_negotiated_terms'];
            }
            if (isset($contract['mc_special_clauses'])) {
                $defaults['specialClauses'] = (string) $contract['mc_special_clauses'];
            }

            $agreement = lc_merchant_contract_decode_snapshot($contract['mc_agreement_snapshot'] ?? '');
            if (is_array($agreement)) {
                if (isset($agreement['agreements']) && is_array($agreement['agreements'])) {
                    $defaults['agreements'] = array_merge($defaults['agreements'], $agreement['agreements']);
                }
                if (!empty($agreement['step'])) {
                    $defaults['step'] = (int) $agreement['step'];
                }
                if (!empty($agreement['agreementCheckedAt'])) {
                    $defaults['agreementCheckedAt'] = (string) $agreement['agreementCheckedAt'];
                }
            }
        }

        return $defaults;
    }
}

if (!function_exists('lc_merchant_contract_sanitize_text')) {
    function lc_merchant_contract_sanitize_text($value, $max = 500)
    {
        $value = trim(strip_tags((string) $value));
        if (function_exists('clean_xss_tags')) {
            $value = clean_xss_tags($value);
        }

        if ($max > 0 && mb_strlen($value, 'UTF-8') > $max) {
            $value = mb_substr($value, 0, $max, 'UTF-8');
        }

        return $value;
    }
}

if (!function_exists('lc_merchant_contract_validate_company_form')) {
    /**
     * @return array{ok:bool,errors:array<string,string>,missing:string[]}
     */
    function lc_merchant_contract_validate_company_form(array $form)
    {
        $errors = array();
        $missing = array();
        $labels = array(
            'companyName'        => '회사명',
            'representativeName' => '대표자명',
            'businessNumber'     => '사업자등록번호',
            'companyAddress'     => '사업장 주소',
            'companyPhone'       => '회사 연락처',
            'contactName'        => '담당자명',
            'contactPhone'       => '담당자 연락처',
            'contactEmail'       => '담당자 이메일',
        );

        foreach ($labels as $key => $label) {
            $value = trim((string) ($form[$key] ?? ''));
            if ($value === '') {
                $errors[$key] = $label . '을(를) 입력해 주세요.';
                $missing[] = $label;
            }
        }

        if (!empty($form['contactEmail']) && !filter_var((string) $form['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors['contactEmail'] = '담당자 이메일 형식이 올바르지 않습니다.';
        }

        if (!empty($form['businessNumber']) && !preg_match('/^\d{3}-\d{2}-\d{5}$/', (string) $form['businessNumber'])) {
            $errors['businessNumber'] = '사업자등록번호는 000-00-00000 형식으로 입력해 주세요.';
        }

        return array(
            'ok'      => count($errors) === 0,
            'errors'  => $errors,
            'missing' => $missing,
        );
    }
}

if (!function_exists('lc_merchant_contract_validate_agreements')) {
    /**
     * @return array{ok:bool,errors:array<string,string>}
     */
    function lc_merchant_contract_validate_agreements(array $agreements)
    {
        $errors = array();
        $required = array(
            'readAll'      => '계약서 전체 내용을 확인하였습니다.',
            'hasAuthority' => '본인은 해당 회사의 계약 체결 권한을 보유하고 있습니다.',
            'electronic'   => '전자적 방식으로 계약을 체결하는 것에 동의합니다.',
            'noModify'     => '계약 체결 후 계약서 내용을 임의로 변경할 수 없음을 확인했습니다.',
        );

        foreach ($required as $key => $label) {
            if (empty($agreements[$key])) {
                $errors[$key] = $label;
            }
        }

        return array('ok' => count($errors) === 0, 'errors' => $errors);
    }
}

if (!function_exists('lc_merchant_contract_validate_signer_form')) {
    /**
     * @return array{ok:bool,errors:array<string,string>}
     */
    function lc_merchant_contract_validate_signer_form(array $form, $require_signature = false)
    {
        $errors = array();
        $labels = array(
            'signerName'     => '계약 담당자 이름',
            'signerPosition' => '직책',
            'signerPhone'    => '휴대전화번호',
            'signerEmail'    => '이메일',
        );

        foreach ($labels as $key => $label) {
            if (trim((string) ($form[$key] ?? '')) === '') {
                $errors[$key] = $label . '을(를) 입력해 주세요.';
            }
        }

        if (!empty($form['signerEmail']) && !filter_var((string) $form['signerEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors['signerEmail'] = '이메일 형식이 올바르지 않습니다.';
        }

        if ($require_signature && empty($form['hasSignature'])) {
            $errors['signature'] = '서명을 입력해 주세요.';
        }

        return array('ok' => count($errors) === 0, 'errors' => $errors);
    }
}

if (!function_exists('lc_merchant_contract_signature_dir')) {
    function lc_merchant_contract_signature_dir()
    {
        $dir = LC_PLUGIN_PATH . '/data/contract_signatures';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }
}

if (!function_exists('lc_merchant_contract_signature_filename')) {
    function lc_merchant_contract_signature_filename($mt_id)
    {
        $mt_id = (int) $mt_id;
        $version = preg_replace('/[^a-zA-Z0-9\-_]/', '', lc_merchant_contract_current_version());

        return 'sig_' . $mt_id . '_' . $version . '_' . bin2hex(random_bytes(8)) . '.png';
    }
}

if (!function_exists('lc_merchant_contract_save_signature_png')) {
    /**
     * @return array{ok:bool,message:string,path:string}
     */
    function lc_merchant_contract_save_signature_png($mt_id, $binary)
    {
        $max_bytes = 512000;
        if (!is_string($binary) || $binary === '') {
            return array('ok' => false, 'message' => '서명 데이터가 없습니다.', 'path' => '');
        }
        if (strlen($binary) > $max_bytes) {
            return array('ok' => false, 'message' => '서명 파일 크기가 너무 큽니다. (최대 500KB)', 'path' => '');
        }

        $png_header = "\x89PNG\r\n\x1a\n";
        if (strncmp($binary, $png_header, 8) !== 0) {
            return array('ok' => false, 'message' => 'PNG 형식의 서명만 허용됩니다.', 'path' => '');
        }

        if (@getimagesizefromstring($binary) === false) {
            return array('ok' => false, 'message' => '유효하지 않은 이미지입니다.', 'path' => '');
        }

        $dir = lc_merchant_contract_signature_dir();
        $filename = lc_merchant_contract_signature_filename($mt_id);
        $full = $dir . '/' . $filename;

        if (@file_put_contents($full, $binary, LOCK_EX) === false) {
            return array('ok' => false, 'message' => '서명 파일 저장에 실패했습니다.', 'path' => '');
        }

        @chmod($full, 0644);

        return array(
            'ok'      => true,
            'message' => '서명이 저장되었습니다.',
            'path'    => 'data/contract_signatures/' . $filename,
        );
    }
}

if (!function_exists('lc_merchant_contract_build_company_snapshot_from_form')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_contract_build_company_snapshot_from_form($mt_id, array $form)
    {
        $base = lc_merchant_contract_build_company_snapshot($mt_id);

        $base['company_name'] = lc_merchant_contract_sanitize_text($form['companyName'] ?? '', 200);
        $base['representative_name'] = lc_merchant_contract_sanitize_text($form['representativeName'] ?? '', 100);
        $base['business_number'] = lc_merchant_contract_sanitize_text($form['businessNumber'] ?? '', 20);
        $base['company_address'] = lc_merchant_contract_sanitize_text($form['companyAddress'] ?? '', 500);
        $base['company_phone'] = lc_merchant_contract_sanitize_text($form['companyPhone'] ?? '', 30);
        $base['contact_name'] = lc_merchant_contract_sanitize_text($form['contactName'] ?? '', 100);
        $base['contact_phone'] = lc_merchant_contract_sanitize_text($form['contactPhone'] ?? '', 30);
        $base['contact_email'] = lc_merchant_contract_sanitize_text($form['contactEmail'] ?? '', 200);
        $base['snapshot_at'] = date('c');

        return $base;
    }
}

if (!function_exists('lc_merchant_contract_save_draft')) {
    /**
     * @return array{ok:bool,message:string,errors?:array<string,string>,contract?:array<string,mixed>|null}
     */
    function lc_merchant_contract_save_draft($mt_id, array $payload)
    {
        $mt_id = (int) $mt_id;
        if (!lc_merchant_contract_can_write($mt_id)) {
            return array('ok' => false, 'message' => '계약서를 수정할 수 없는 상태입니다.');
        }

        lc_merchant_contract_create_pending($mt_id);
        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        if (!is_array($contract)) {
            return array('ok' => false, 'message' => '계약 레코드를 준비하지 못했습니다.');
        }

        $form = array(
            'companyName'        => lc_merchant_contract_sanitize_text($payload['companyName'] ?? '', 200),
            'representativeName' => lc_merchant_contract_sanitize_text($payload['representativeName'] ?? '', 100),
            'businessNumber'     => lc_merchant_contract_sanitize_text($payload['businessNumber'] ?? '', 20),
            'companyAddress'     => lc_merchant_contract_sanitize_text($payload['companyAddress'] ?? '', 500),
            'companyPhone'       => lc_merchant_contract_sanitize_text($payload['companyPhone'] ?? '', 30),
            'contactName'        => lc_merchant_contract_sanitize_text($payload['contactName'] ?? '', 100),
            'contactPhone'       => lc_merchant_contract_sanitize_text($payload['contactPhone'] ?? '', 30),
            'contactEmail'       => lc_merchant_contract_sanitize_text($payload['contactEmail'] ?? '', 200),
            'signerName'         => lc_merchant_contract_sanitize_text($payload['signerName'] ?? '', 100),
            'signerPosition'     => lc_merchant_contract_sanitize_text($payload['signerPosition'] ?? '', 100),
            'signerPhone'        => lc_merchant_contract_sanitize_text($payload['signerPhone'] ?? '', 30),
            'signerEmail'        => lc_merchant_contract_sanitize_text($payload['signerEmail'] ?? '', 200),
        );
        $negotiated_terms = lc_merchant_contract_sanitize_text($payload['negotiatedTerms'] ?? '', 8000);
        $special_clauses = lc_merchant_contract_sanitize_text($payload['specialClauses'] ?? '', 8000);

        $step = isset($payload['step']) ? (int) $payload['step'] : 1;
        $agreements = isset($payload['agreements']) && is_array($payload['agreements']) ? $payload['agreements'] : array();

        if ($step >= 1) {
            $company_check = lc_merchant_contract_validate_company_form($form);
            if ($step > 1 && !$company_check['ok']) {
                return array('ok' => false, 'message' => '광고주 정보를 먼저 완성해 주세요.', 'errors' => $company_check['errors']);
            }
        }
        if ($step >= 3) {
            $agreement_check = lc_merchant_contract_validate_agreements($agreements);
            if (!$agreement_check['ok']) {
                return array('ok' => false, 'message' => '필수 동의 항목을 모두 선택해 주세요.', 'errors' => $agreement_check['errors']);
            }
        }

        $party_a = array(
            'company_name'        => $form['companyName'],
            'representative_name' => $form['representativeName'],
            'business_number'     => $form['businessNumber'],
            'company_address'     => $form['companyAddress'],
            'company_phone'       => $form['companyPhone'],
        );
        $terms_extras = array(
            'negotiatedTerms' => $negotiated_terms,
            'specialClauses'  => $special_clauses,
        );
        $contract_html = function_exists('lc_merchant_contract_render_html')
            ? lc_merchant_contract_render_html($party_a, $terms_extras)
            : '';

        $agreement_snapshot = array(
            'step'               => $step,
            'agreements'         => array(
                'readAll'      => !empty($agreements['readAll']),
                'hasAuthority' => !empty($agreements['hasAuthority']),
                'electronic'   => !empty($agreements['electronic']),
                'noModify'     => !empty($agreements['noModify']),
            ),
            'agreementCheckedAt' => !empty($agreements['readAll']) ? date('c') : '',
            'draftSavedAt'       => date('c'),
            'negotiatedTerms'    => $negotiated_terms,
            'specialClauses'     => $special_clauses,
        );

        $company_snapshot = lc_merchant_contract_build_company_snapshot_from_form($mt_id, $form);
        $table = lc_merchant_contract_table();
        $status = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS);

        $sets = array(
            "mc_status = '{$status}'",
            "mc_company_name = '" . lc_sql_escape($form['companyName']) . "'",
            "mc_representative_name = '" . lc_sql_escape($form['representativeName']) . "'",
            "mc_business_number = '" . lc_sql_escape($form['businessNumber']) . "'",
            "mc_company_address = '" . lc_sql_escape($form['companyAddress']) . "'",
            "mc_company_phone = '" . lc_sql_escape($form['companyPhone']) . "'",
            "mc_signer_name = '" . lc_sql_escape($form['signerName']) . "'",
            "mc_signer_position = '" . lc_sql_escape($form['signerPosition']) . "'",
            "mc_signer_phone = '" . lc_sql_escape($form['signerPhone']) . "'",
            "mc_signer_email = '" . lc_sql_escape($form['signerEmail']) . "'",
            "mc_company_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($company_snapshot)) . "'",
            "mc_contract_snapshot = '" . lc_sql_escape($contract_html) . "'",
            "mc_agreement_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($agreement_snapshot)) . "'",
            'mc_updated_at = NOW()',
        );
        if (function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'mc_negotiated_terms')) {
            $sets[] = "mc_negotiated_terms = '" . lc_sql_escape($negotiated_terms) . "'";
        }
        if (function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'mc_special_clauses')) {
            $sets[] = "mc_special_clauses = '" . lc_sql_escape($special_clauses) . "'";
        }

        $mc_id = (int) ($contract['mc_id'] ?? 0);
        $update = lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE mc_id = '{$mc_id}' AND mc_mt_id = '{$mt_id}' ", false);
        if ($update === false) {
            return array('ok' => false, 'message' => '임시 저장에 실패했습니다.');
        }

        $fresh = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());

        return array(
            'ok'       => true,
            'message'  => '임시 저장되었습니다.',
            'contract' => is_array($fresh) ? $fresh : null,
        );
    }
}

if (!function_exists('lc_merchant_contract_validate_submit')) {
    /**
     * 최종 체결 전 검증 (체결 저장은 다음 단계)
     *
     * @return array{ok:bool,message:string,errors?:array<string,string>}
     */
    function lc_merchant_contract_validate_submit($mt_id, array $payload)
    {
        $mt_id = (int) $mt_id;
        if (!lc_merchant_contract_can_write($mt_id)) {
            return array('ok' => false, 'message' => '계약서를 체결할 수 없는 상태입니다.');
        }

        $form = array_merge($payload, array(
            'hasSignature' => !empty($payload['hasSignature']),
        ));

        $company_check = lc_merchant_contract_validate_company_form($form);
        if (!$company_check['ok']) {
            return array('ok' => false, 'message' => '광고주 정보를 확인해 주세요.', 'errors' => $company_check['errors']);
        }

        $agreements = isset($payload['agreements']) && is_array($payload['agreements']) ? $payload['agreements'] : array();
        $agreement_check = lc_merchant_contract_validate_agreements($agreements);
        if (!$agreement_check['ok']) {
            return array('ok' => false, 'message' => '필수 동의 항목을 확인해 주세요.', 'errors' => $agreement_check['errors']);
        }

        $signer_check = lc_merchant_contract_validate_signer_form($form, true);
        if (!$signer_check['ok']) {
            return array('ok' => false, 'message' => '계약 담당자 정보와 서명을 확인해 주세요.', 'errors' => $signer_check['errors']);
        }

        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        if (!is_array($contract) || (string) ($contract['mc_signature_file_path'] ?? '') === '') {
            return array('ok' => false, 'message' => '서명을 저장한 후 다시 시도해 주세요.', 'errors' => array('signature' => '서명을 입력해 주세요.'));
        }

        return array('ok' => true, 'message' => '입력 내용이 확인되었습니다. (최종 체결은 다음 단계에서 처리됩니다.)');
    }
}

if (!function_exists('lc_merchant_contract_latest_status_reason')) {
    /**
     * 특정 상태로 변경된 최근 사유 조회
     */
    function lc_merchant_contract_latest_status_reason($mc_id, $new_status)
    {
        $mc_id = (int) $mc_id;
        $new_status = (string) $new_status;
        if ($mc_id <= 0 || $new_status === '') {
            return '';
        }

        $table = function_exists('lc_merchant_contract_status_log_table')
            ? lc_merchant_contract_status_log_table()
            : '';
        if ($table === '' || !lc_db_table_exists($table)) {
            return '';
        }

        $row = lc_sql_fetch(" SELECT mcsl_reason FROM `{$table}`
            WHERE mc_id = '{$mc_id}'
              AND mcsl_new_status = '" . lc_sql_escape($new_status) . "'
            ORDER BY mcsl_created_at DESC, mcsl_id DESC
            LIMIT 1 ");

        return is_array($row) ? trim((string) ($row['mcsl_reason'] ?? '')) : '';
    }
}

if (!function_exists('lc_merchant_contract_view_to_api')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_contract_view_to_api($mt_id)
    {
        $mt_id = (int) $mt_id;
        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        $status = lc_merchant_contract_status($mt_id);
        $defaults = lc_merchant_contract_get_form_defaults($mt_id);
        $party_b = lc_merchant_contract_party_b();
        $rejection_reason = '';
        if (
            $status === LC_MERCHANT_CONTRACT_STATUS_REJECTED
            && is_array($contract)
            && function_exists('lc_merchant_contract_latest_status_reason')
        ) {
            $rejection_reason = lc_merchant_contract_latest_status_reason(
                (int) ($contract['mc_id'] ?? 0),
                LC_MERCHANT_CONTRACT_STATUS_REJECTED
            );
        }

        $party_a = array(
            'companyName'        => (string) ($defaults['companyName'] ?? ''),
            'representativeName' => (string) ($defaults['representativeName'] ?? ''),
            'businessNumber'     => (string) ($defaults['businessNumber'] ?? ''),
            'companyAddress'     => (string) ($defaults['companyAddress'] ?? ''),
            'companyPhone'       => (string) ($defaults['companyPhone'] ?? ''),
        );

        $terms_extras = array(
            'negotiatedTerms' => (string) ($defaults['negotiatedTerms'] ?? ''),
            'specialClauses'  => (string) ($defaults['specialClauses'] ?? ''),
        );

        $contract_html = function_exists('lc_merchant_contract_render_html')
            ? lc_merchant_contract_render_html(array(
                'company_name'        => $party_a['companyName'],
                'representative_name' => $party_a['representativeName'],
                'business_number'     => $party_a['businessNumber'],
                'company_address'     => $party_a['companyAddress'],
                'company_phone'       => $party_a['companyPhone'],
            ), $terms_extras)
            : '';

        $can_write = lc_merchant_contract_can_write($mt_id);
        if (!$can_write && is_array($contract) && (string) ($contract['mc_contract_snapshot'] ?? '') !== '') {
            $contract_html = (string) $contract['mc_contract_snapshot'];
        }

        return array(
            'contractVersion' => lc_merchant_contract_current_version(),
            'status'          => $status,
            'statusLabel'     => lc_merchant_contract_status_label($status),
            'isSigned'        => lc_merchant_contract_is_signed($mt_id),
            'isApprovalPending' => $status === LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING,
            'isRejected'      => $status === LC_MERCHANT_CONTRACT_STATUS_REJECTED,
            'rejectionReason' => $rejection_reason,
            'canWrite'        => $can_write,
            'requiresContract'=> lc_merchant_contract_requires($mt_id),
            'partyB'          => array(
                'companyName'        => (string) ($party_b['company_name'] ?? ''),
                'representativeName' => (string) ($party_b['representative_name'] ?? ''),
                'businessNumber'     => (string) ($party_b['business_number'] ?? ''),
                'companyAddress'     => (string) ($party_b['company_address'] ?? ''),
                'companyPhone'       => (string) ($party_b['company_phone'] ?? ''),
            ),
            'form'            => $defaults,
            'contractHtml'    => $contract_html,
            'contract'        => is_array($contract) ? lc_merchant_contract_to_api($contract) : null,
            'documentPreviewUrl' => LC_PLUGIN_URL . '/merchant/contract-document.php',
            'documentPdfUrl'     => LC_PLUGIN_URL . '/merchant/contract-download.php',
            'signedPdfDownloadUrl' => LC_PLUGIN_URL . '/merchant/contract-download.php',
            'csrfToken'       => lc_merchant_contract_csrf_token(),
            'hasSignature'    => is_array($contract) && (string) ($contract['mc_signature_file_path'] ?? '') !== '',
        );
    }
}

if (!function_exists('lc_merchant_contract_storage_root')) {
    function lc_merchant_contract_storage_root()
    {
        $outside = dirname(G5_PATH) . '/storage/contracts';
        if (!is_dir($outside)) {
            @mkdir($outside, 0750, true);
        }
        if (is_dir($outside) && is_writable($outside)) {
            return rtrim($outside, '/');
        }

        $data = G5_DATA_PATH . '/lc_contracts';
        if (!is_dir($data)) {
            @mkdir($data, 0755, true);
        }
        $htaccess = $data . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }

        return rtrim($data, '/');
    }
}

if (!function_exists('lc_merchant_contract_relative_storage_path')) {
    function lc_merchant_contract_relative_storage_path($absolute_path)
    {
        $root = lc_merchant_contract_storage_root();
        $absolute_path = str_replace('\\', '/', (string) $absolute_path);
        $root = str_replace('\\', '/', $root);
        if (strpos($absolute_path, $root . '/') === 0) {
            return ltrim(substr($absolute_path, strlen($root)), '/');
        }

        return basename($absolute_path);
    }
}

if (!function_exists('lc_merchant_contract_absolute_storage_path')) {
    function lc_merchant_contract_absolute_storage_path($relative_path)
    {
        $relative_path = ltrim(str_replace('\\', '/', (string) $relative_path), '/');
        if ($relative_path === '' || strpos($relative_path, '..') !== false) {
            return '';
        }

        return lc_merchant_contract_storage_root() . '/' . $relative_path;
    }
}

if (!function_exists('lc_merchant_contract_signed_pdf_dir')) {
    function lc_merchant_contract_signed_pdf_dir($mt_id, $version_dir)
    {
        $mt_id = (int) $mt_id;
        $version_dir = preg_replace('/[^a-zA-Z0-9\-_]/', '_', (string) $version_dir);
        $dir = lc_merchant_contract_storage_root() . '/advertisers/' . $mt_id . '/' . $version_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        return is_dir($dir) && is_writable($dir) ? $dir : '';
    }
}

if (!function_exists('lc_merchant_contract_signed_pdf_filename')) {
    function lc_merchant_contract_signed_pdf_filename($mt_id)
    {
        $mt_id = (int) $mt_id;

        return 'CPA_CONTRACT_' . $mt_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    }
}

if (!function_exists('lc_merchant_contract_generate_code')) {
    function lc_merchant_contract_generate_code($mt_id)
    {
        $mt_id = (int) $mt_id;

        return 'CPA-' . date('Ymd') . '-' . str_pad((string) $mt_id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (!function_exists('lc_merchant_contract_log_table')) {
    function lc_merchant_contract_log_table()
    {
        return lc_table('merchant_contract_logs');
    }
}

if (!function_exists('lc_merchant_contract_log_write')) {
    function lc_merchant_contract_log_write(array $data)
    {
        if (!lc_db_table_exists(lc_merchant_contract_log_table())) {
            return 0;
        }

        $table = lc_merchant_contract_log_table();
        lc_sql_query(" INSERT INTO `{$table}`
            SET mc_id = '" . (int) ($data['mc_id'] ?? 0) . "',
                mt_id = '" . (int) ($data['mt_id'] ?? 0) . "',
                mcl_contract_code = '" . lc_sql_escape((string) ($data['contract_code'] ?? '')) . "',
                mcl_contract_version = '" . lc_sql_escape((string) ($data['contract_version'] ?? '')) . "',
                mcl_signed_at = " . (!empty($data['signed_at']) ? "'" . lc_sql_escape((string) $data['signed_at']) . "'" : 'NULL') . ",
                mcl_ip = '" . lc_sql_escape((string) ($data['ip'] ?? '')) . "',
                mcl_user_agent = '" . lc_sql_escape((string) ($data['user_agent'] ?? '')) . "',
                mcl_pdf_path = '" . lc_sql_escape((string) ($data['pdf_path'] ?? '')) . "',
                mcl_pdf_hash = '" . lc_sql_escape((string) ($data['pdf_hash'] ?? '')) . "',
                mcl_result = '" . lc_sql_escape((string) ($data['result'] ?? 'success')) . "',
                mcl_message = '" . lc_sql_escape((string) ($data['message'] ?? '')) . "',
                mcl_created_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_merchant_contract_get_for_update')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_get_for_update($mt_id, $version = null)
    {
        $mt_id = (int) $mt_id;
        if ($version === null || $version === '') {
            $version = lc_merchant_contract_current_version();
        }

        $table = lc_merchant_contract_table();
        $version_esc = lc_sql_escape((string) $version);

        return lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE mc_mt_id = '{$mt_id}' AND mc_contract_version = '{$version_esc}'
            FOR UPDATE ");
    }
}

if (!function_exists('lc_merchant_contract_cleanup_files')) {
    function lc_merchant_contract_cleanup_files(array $paths)
    {
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            $absolute = strpos($path, '/') === 0 ? $path : lc_merchant_contract_absolute_storage_path($path);
            if ($absolute === '' && strpos($path, LC_PLUGIN_PATH) === 0) {
                $absolute = $path;
            }
            if ($absolute !== '' && is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }
}

if (!function_exists('lc_merchant_contract_request_meta')) {
    /**
     * @return array{ip:string,user_agent:string}
     */
    function lc_merchant_contract_request_meta()
    {
        return array(
            'ip'         => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500, 'UTF-8') : '',
        );
    }
}

if (!function_exists('lc_merchant_contract_sign')) {
    /**
     * @return array{ok:bool,message:string,alreadySigned?:bool,errors?:array<string,string>,contract?:array<string,mixed>|null,state?:array<string,mixed>|null}
     */
    function lc_merchant_contract_sign($mt_id, array $payload)
    {
        $mt_id = (int) $mt_id;
        $version = lc_merchant_contract_current_version();
        $cleanup_files = array();

        if (lc_merchant_contract_is_signed($mt_id)) {
            $existing = lc_merchant_contract_get($mt_id, $version);

            return array(
                'ok'            => true,
                'message'       => '이미 체결된 계약서입니다.',
                'alreadySigned' => true,
                'contract'      => is_array($existing) ? $existing : null,
                'state'         => lc_merchant_contract_view_to_api($mt_id),
            );
        }

        $current = lc_merchant_contract_get($mt_id, $version);
        if (is_array($current) && ($current['mc_status'] ?? '') === LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING) {
            return array(
                'ok'            => true,
                'message'       => '이미 관리자 승인을 요청한 계약서입니다.',
                'alreadySigned' => false,
                'contract'      => $current,
                'state'         => lc_merchant_contract_view_to_api($mt_id),
            );
        }

        $form = array(
            'companyName'        => lc_merchant_contract_sanitize_text($payload['companyName'] ?? '', 200),
            'representativeName' => lc_merchant_contract_sanitize_text($payload['representativeName'] ?? '', 100),
            'businessNumber'     => lc_merchant_contract_sanitize_text($payload['businessNumber'] ?? '', 20),
            'companyAddress'     => lc_merchant_contract_sanitize_text($payload['companyAddress'] ?? '', 500),
            'companyPhone'       => lc_merchant_contract_sanitize_text($payload['companyPhone'] ?? '', 30),
            'contactName'        => lc_merchant_contract_sanitize_text($payload['contactName'] ?? '', 100),
            'contactPhone'       => lc_merchant_contract_sanitize_text($payload['contactPhone'] ?? '', 30),
            'contactEmail'       => lc_merchant_contract_sanitize_text($payload['contactEmail'] ?? '', 200),
            'signerName'         => lc_merchant_contract_sanitize_text($payload['signerName'] ?? '', 100),
            'signerPosition'     => lc_merchant_contract_sanitize_text($payload['signerPosition'] ?? '', 100),
            'signerPhone'        => lc_merchant_contract_sanitize_text($payload['signerPhone'] ?? '', 30),
            'signerEmail'        => lc_merchant_contract_sanitize_text($payload['signerEmail'] ?? '', 200),
        );
        $negotiated_terms = lc_merchant_contract_sanitize_text($payload['negotiatedTerms'] ?? '', 8000);
        $special_clauses = lc_merchant_contract_sanitize_text($payload['specialClauses'] ?? '', 8000);
        $agreements = isset($payload['agreements']) && is_array($payload['agreements']) ? $payload['agreements'] : array();

        $company_check = lc_merchant_contract_validate_company_form($form);
        if (!$company_check['ok']) {
            return array('ok' => false, 'message' => '광고주 정보를 확인해 주세요.', 'errors' => $company_check['errors']);
        }
        $agreement_check = lc_merchant_contract_validate_agreements($agreements);
        if (!$agreement_check['ok']) {
            return array('ok' => false, 'message' => '필수 동의 항목을 확인해 주세요.', 'errors' => $agreement_check['errors']);
        }

        $signature_path = '';
        $new_signature_file = '';
        $data_url = isset($payload['signatureDataUrl']) ? (string) $payload['signatureDataUrl'] : '';
        if ($data_url !== '' && strpos($data_url, 'data:image/png;base64,') === 0) {
            $binary = base64_decode(substr($data_url, strlen('data:image/png;base64,')), true);
            $saved_sig = lc_merchant_contract_save_signature_png($mt_id, $binary === false ? '' : $binary);
            if (empty($saved_sig['ok'])) {
                return array('ok' => false, 'message' => $saved_sig['message'], 'errors' => array('signature' => $saved_sig['message']));
            }
            $signature_path = (string) $saved_sig['path'];
            $new_signature_file = LC_PLUGIN_PATH . '/' . ltrim($signature_path, '/');
        }

        lc_merchant_contract_create_pending($mt_id);
        $draft = lc_merchant_contract_get($mt_id, $version);
        if ($signature_path === '' && is_array($draft)) {
            $signature_path = (string) ($draft['mc_signature_file_path'] ?? '');
        }
        // 페이로드에 없으면 기존 초안 값 유지
        if ($negotiated_terms === '' && is_array($draft) && isset($draft['mc_negotiated_terms'])) {
            $negotiated_terms = (string) $draft['mc_negotiated_terms'];
        }
        if ($special_clauses === '' && is_array($draft) && isset($draft['mc_special_clauses'])) {
            $special_clauses = (string) $draft['mc_special_clauses'];
        }

        $form['hasSignature'] = $signature_path !== '';
        $signer_check = lc_merchant_contract_validate_signer_form($form, true);
        if (!$signer_check['ok']) {
            return array('ok' => false, 'message' => '계약 담당자 정보와 서명을 확인해 주세요.', 'errors' => $signer_check['errors']);
        }

        $meta = lc_merchant_contract_request_meta();
        $signed_at = date('Y-m-d H:i:s');
        $party_a = array(
            'company_name'        => $form['companyName'],
            'representative_name' => $form['representativeName'],
            'business_number'     => $form['businessNumber'],
            'company_address'     => $form['companyAddress'],
            'company_phone'       => $form['companyPhone'],
        );
        $company_snapshot = lc_merchant_contract_build_company_snapshot_from_form($mt_id, $form);
        $terms_extras = array(
            'negotiatedTerms' => $negotiated_terms,
            'specialClauses'  => $special_clauses,
        );
        $contract_html = function_exists('lc_merchant_contract_render_html')
            ? lc_merchant_contract_render_html($party_a, $terms_extras)
            : '';
        $agreement_snapshot = array(
            'agreements' => array(
                'readAll'      => !empty($agreements['readAll']),
                'hasAuthority' => !empty($agreements['hasAuthority']),
                'electronic'   => !empty($agreements['electronic']),
                'noModify'     => !empty($agreements['noModify']),
            ),
            'agreementCheckedAt' => date('c'),
            'signedAt'           => $signed_at,
            'ip'                 => $meta['ip'],
            'userAgent'          => $meta['user_agent'],
            'negotiatedTerms'    => $negotiated_terms,
            'specialClauses'     => $special_clauses,
        );

        if (!lc_sql_begin()) {
            return array('ok' => false, 'message' => '트랜잭션을 시작하지 못했습니다.');
        }

        $pdf_relative = '';
        $pdf_hash = '';
        $contract_code = '';
        $mc_id = 0;

        try {
            $row = lc_merchant_contract_get_for_update($mt_id, $version);
            if (!is_array($row)) {
                throw new RuntimeException('계약 레코드를 잠그지 못했습니다.');
            }
            if (($row['mc_status'] ?? '') === LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
                lc_sql_commit();

                return array(
                    'ok'            => true,
                    'message'       => '이미 체결된 계약서입니다.',
                    'alreadySigned' => true,
                    'contract'      => $row,
                    'state'         => lc_merchant_contract_view_to_api($mt_id),
                );
            }
            if (($row['mc_status'] ?? '') === LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING) {
                lc_sql_commit();

                return array(
                    'ok'            => true,
                    'message'       => '이미 관리자 승인을 요청한 계약서입니다.',
                    'alreadySigned' => false,
                    'contract'      => $row,
                    'state'         => lc_merchant_contract_view_to_api($mt_id),
                );
            }

            $mc_id = (int) $row['mc_id'];
            $contract_code = (string) ($row['mc_contract_code'] ?? '');
            if ($contract_code === '') {
                $contract_code = lc_merchant_contract_generate_code($mt_id);
            }

            $signature_absolute = $signature_path !== '' ? LC_PLUGIN_PATH . '/' . ltrim($signature_path, '/') : '';
            if ($signature_absolute === '' || !is_file($signature_absolute)) {
                throw new RuntimeException('서명 파일을 찾을 수 없습니다.');
            }

            if (!function_exists('lc_merchant_contract_generate_signed_pdf')) {
                throw new RuntimeException('PDF 생성 모듈이 로드되지 않았습니다.');
            }

            $pdf = lc_merchant_contract_generate_signed_pdf(array(
                'mt_id'               => $mt_id,
                'contract_version'    => $version,
                'contract_code'       => $contract_code,
                'company_name'        => $form['companyName'],
                'representative_name' => $form['representativeName'],
                'business_number'     => $form['businessNumber'],
                'company_address'     => $form['companyAddress'],
                'company_phone'       => $form['companyPhone'],
                'signer_name'         => $form['signerName'],
                'signer_position'     => $form['signerPosition'],
                'signer_phone'        => $form['signerPhone'],
                'signer_email'        => $form['signerEmail'],
                'signed_at'           => $signed_at,
                'signed_ip'           => $meta['ip'],
                'contract_html'       => $contract_html,
                'signature_absolute'  => $signature_absolute,
            ));

            if (empty($pdf['ok'])) {
                throw new RuntimeException($pdf['message'] ?? 'PDF 생성 실패');
            }

            $pdf_relative = (string) $pdf['path'];
            $pdf_hash = (string) $pdf['hash'];
            $cleanup_files[] = (string) $pdf['absolute'];

            $table = lc_merchant_contract_table();
            $status = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING);
            $update = lc_sql_query(" UPDATE `{$table}` SET
                mc_status = '{$status}',
                mc_contract_code = '" . lc_sql_escape($contract_code) . "',
                mc_company_name = '" . lc_sql_escape($form['companyName']) . "',
                mc_representative_name = '" . lc_sql_escape($form['representativeName']) . "',
                mc_business_number = '" . lc_sql_escape($form['businessNumber']) . "',
                mc_company_address = '" . lc_sql_escape($form['companyAddress']) . "',
                mc_company_phone = '" . lc_sql_escape($form['companyPhone']) . "',
                mc_signer_name = '" . lc_sql_escape($form['signerName']) . "',
                mc_signer_position = '" . lc_sql_escape($form['signerPosition']) . "',
                mc_signer_phone = '" . lc_sql_escape($form['signerPhone']) . "',
                mc_signer_email = '" . lc_sql_escape($form['signerEmail']) . "',
                mc_signature_file_path = '" . lc_sql_escape($signature_path) . "',
                mc_signed_at = '" . lc_sql_escape($signed_at) . "',
                mc_signed_ip = '" . lc_sql_escape($meta['ip']) . "',
                mc_user_agent = '" . lc_sql_escape($meta['user_agent']) . "',
                mc_contract_pdf_path = '" . lc_sql_escape($pdf_relative) . "',
                mc_contract_file_hash = '" . lc_sql_escape($pdf_hash) . "',
                mc_company_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($company_snapshot)) . "',
                mc_contract_snapshot = '" . lc_sql_escape($contract_html) . "',
                mc_agreement_snapshot = '" . lc_sql_escape(lc_merchant_contract_encode_snapshot($agreement_snapshot)) . "',
                mc_negotiated_terms = '" . lc_sql_escape($negotiated_terms) . "',
                mc_special_clauses = '" . lc_sql_escape($special_clauses) . "',
                mc_updated_at = NOW()
                WHERE mc_id = '{$mc_id}' AND mc_mt_id = '{$mt_id}'
                  AND mc_status NOT IN ('" . lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_SIGNED) . "','{$status}') ", false);

            if ($update === false) {
                throw new RuntimeException('계약 상태 저장에 실패했습니다.');
            }

            if (function_exists('lc_sql_affected_rows') && lc_sql_affected_rows() === 0) {
                $recheck = lc_merchant_contract_get($mt_id, $version);
                if (is_array($recheck) && in_array(
                    (string) ($recheck['mc_status'] ?? ''),
                    array(LC_MERCHANT_CONTRACT_STATUS_SIGNED, LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING),
                    true
                )) {
                    lc_sql_commit();
                    if (function_exists('lc_merchant_contract_access_cache_clear')) {
                        lc_merchant_contract_access_cache_clear($mt_id);
                    }

                    return array(
                        'ok'            => true,
                        'message'       => ($recheck['mc_status'] ?? '') === LC_MERCHANT_CONTRACT_STATUS_SIGNED
                            ? '이미 승인된 계약서입니다.'
                            : '이미 관리자 승인을 요청한 계약서입니다.',
                        'alreadySigned' => ($recheck['mc_status'] ?? '') === LC_MERCHANT_CONTRACT_STATUS_SIGNED,
                        'contract'      => $recheck,
                        'state'         => lc_merchant_contract_view_to_api($mt_id),
                    );
                }

                throw new RuntimeException('계약 상태 저장에 실패했습니다.');
            }

            lc_merchant_contract_log_write(array(
                'mc_id'            => $mc_id,
                'mt_id'            => $mt_id,
                'contract_code'    => $contract_code,
                'contract_version' => $version,
                'signed_at'        => $signed_at,
                'ip'               => $meta['ip'],
                'user_agent'       => $meta['user_agent'],
                'pdf_path'         => $pdf_relative,
                'pdf_hash'         => $pdf_hash,
                'result'           => 'success',
                'message'          => 'contract_approval_requested',
            ));

            if (!lc_sql_commit()) {
                throw new RuntimeException('트랜잭션 커밋 실패');
            }

            $cleanup_files = array();
            if (function_exists('lc_merchant_contract_access_cache_clear')) {
                lc_merchant_contract_access_cache_clear($mt_id);
            }
            $fresh = lc_merchant_contract_get($mt_id, $version);
            if (function_exists('lc_notification_create')) {
                lc_notification_create(array(
                    'center'  => 'admin',
                    'userId'  => 0,
                    'type'    => 'contract',
                    'title'   => '광고주 계약 승인 요청',
                    'body'    => $form['companyName'] . ' · 계약서 검토가 필요합니다.',
                    'link'    => '/admin/contracts',
                    'refType' => 'merchant_contract',
                    'refId'   => $mc_id,
                ));
            }
            return array(
                'ok'       => true,
                'message'  => '계약서 승인 요청이 완료되었습니다.',
                'contract' => is_array($fresh) ? $fresh : null,
                'state'    => lc_merchant_contract_view_to_api($mt_id),
            );
        } catch (Throwable $e) {
            lc_sql_rollback();
            if ($new_signature_file !== '') {
                $cleanup_files[] = $new_signature_file;
            }
            lc_merchant_contract_cleanup_files($cleanup_files);
            lc_merchant_contract_log_write(array(
                'mc_id'            => $mc_id,
                'mt_id'            => $mt_id,
                'contract_code'    => $contract_code,
                'contract_version' => $version,
                'signed_at'        => null,
                'ip'               => $meta['ip'],
                'user_agent'       => $meta['user_agent'],
                'pdf_path'         => $pdf_relative,
                'pdf_hash'         => $pdf_hash,
                'result'           => 'failed',
                'message'          => $e->getMessage(),
            ));

            return array('ok' => false, 'message' => '계약 체결 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.');
        }
    }
}

if (!function_exists('lc_merchant_contract_send_signed_emails')) {
    function lc_merchant_contract_send_signed_emails($mt_id, array $contract)
    {
        global $config;

        if (!function_exists('mailer') && defined('G5_LIB_PATH') && is_file(G5_LIB_PATH . '/mailer.lib.php')) {
            include_once G5_LIB_PATH . '/mailer.lib.php';
        }
        if (!function_exists('mailer')) {
            error_log('[LinkConnect Contract] mailer unavailable for mt_id=' . (int) $mt_id);
            return false;
        }

        $merchant = lc_get_merchant_by_id((int) $mt_id);
        $member = is_array($merchant) ? lc_merchant_contract_get_member_row((string) ($merchant['mb_id'] ?? '')) : null;
        $merchant_email = is_array($member) ? (string) ($member['mb_email'] ?? '') : '';
        $company = (string) ($contract['mc_company_name'] ?? '');
        $code = (string) ($contract['mc_contract_code'] ?? '');
        $signed_at = (string) ($contract['mc_signed_at'] ?? '');
        $download = LC_PLUGIN_URL . '/merchant/contract-download.php';

        $from_name = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $from_email = !empty($config['cf_admin_email']) ? (string) $config['cf_admin_email'] : lc_contact_email();
        $admin_email = lc_contact_email();

        $merchant_subject = '[' . $from_name . '] CPA 광고 제휴 계약 체결 완료 안내';
        $merchant_body = '<p>' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . ' 담당자님, 계약이 정상적으로 체결되었습니다.</p>';
        $merchant_body .= '<p>계약번호: ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '<br>체결일시: ' . htmlspecialchars($signed_at, ENT_QUOTES, 'UTF-8') . '</p>';
        $merchant_body .= '<p><a href="' . htmlspecialchars($download, ENT_QUOTES, 'UTF-8') . '">계약서 PDF 다운로드</a></p>';

        $admin_subject = '[' . $from_name . '] 새 광고주 계약 체결: ' . $company;
        $admin_body = '<p>광고주 ID ' . (int) $mt_id . ' / ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . ' 계약이 체결되었습니다.</p>';
        $admin_body .= '<p>계약번호: ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</p>';

        $sent = false;
        if ($merchant_email !== '' && filter_var($merchant_email, FILTER_VALIDATE_EMAIL)) {
            try {
                if (@mailer($from_name, $from_email, $merchant_email, $merchant_subject, $merchant_body, 1)) {
                    $sent = true;
                }
            } catch (Throwable $e) {
                error_log('[LinkConnect Contract] merchant mail failed: ' . $e->getMessage());
            }
        }
        if ($admin_email !== '' && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            try {
                if (@mailer($from_name, $from_email, $admin_email, $admin_subject, $admin_body, 1)) {
                    $sent = true;
                }
            } catch (Throwable $e) {
                error_log('[LinkConnect Contract] admin mail failed: ' . $e->getMessage());
            }
        }

        return $sent;
    }
}
