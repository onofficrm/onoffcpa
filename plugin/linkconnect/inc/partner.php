<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_partner_status_label')) {
    function lc_partner_status_label($status)
    {
        $labels = array(
            LC_PARTNER_STATUS_PENDING   => '승인 대기',
            LC_PARTNER_STATUS_ACTIVE    => '운영중',
            LC_PARTNER_STATUS_SUSPENDED => '정지',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_partner_generate_code')) {
    function lc_partner_generate_code($pt_id)
    {
        return 'PTN-' . str_pad((string) (int) $pt_id, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_get_partner_by_mb_id')) {
    function lc_get_partner_by_mb_id($mb_id)
    {
        if (!lc_db_installed() || $mb_id === '') {
            return null;
        }

        $mb_id = lc_sql_escape($mb_id);
        $table = lc_table('partners');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mb_id = '{$mb_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_get_partner_by_id')) {
    function lc_get_partner_by_id($pt_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $pt_id = (int) $pt_id;
        $table = lc_table('partners');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_get_current_partner')) {
    function lc_get_current_partner()
    {
        if (function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('partner')) {
            $record = lc_impersonate_record();
            if (is_array($record)) {
                return $record;
            }
        }

        global $member;

        if (!lc_is_logged_in() || !isset($member['mb_id'])) {
            return null;
        }

        return lc_get_partner_by_mb_id($member['mb_id']);
    }
}

if (!function_exists('lc_is_partner')) {
    /** 파트너 레코드 존재 여부 (상태 무관) */
    function lc_is_partner()
    {
        return lc_get_current_partner() !== null;
    }
}

if (!function_exists('lc_is_active_partner')) {
    function lc_is_active_partner()
    {
        $partner = lc_get_current_partner();

        return is_array($partner) && isset($partner['pt_status']) && $partner['pt_status'] === LC_PARTNER_STATUS_ACTIVE;
    }
}

if (!function_exists('lc_partner_to_api')) {
    function lc_partner_to_api(array $partner)
    {
        $entity_type = (string) ($partner['pt_entity_type'] ?? '');
        return array(
            'id'                 => (int) $partner['pt_id'],
            'code'               => (string) $partner['pt_code'],
            'name'               => (string) $partner['pt_name'],
            'status'             => (string) $partner['pt_status'],
            'statusLabel'        => lc_partner_status_label($partner['pt_status']),
            'balance'            => (int) $partner['pt_balance'],
            'entityType'         => $entity_type,
            'entityTypeLabel'    => lc_partner_entity_type_label($entity_type),
            'residentNo'         => (string) ($partner['pt_resident_no'] ?? ''),
            'companyName'        => (string) ($partner['pt_company_name'] ?? ''),
            'businessNumber'     => (string) ($partner['pt_business_number'] ?? ''),
            'representativeName' => (string) ($partner['pt_representative_name'] ?? ''),
            'companyAddress'     => (string) ($partner['pt_company_address'] ?? ''),
            'bankName'           => (string) $partner['pt_bank_name'],
            'bankAccount'        => (string) $partner['pt_bank_account'],
            'bankHolder'         => (string) $partner['pt_bank_holder'],
            'createdAt'          => (string) $partner['pt_created_at'],
        );
    }
}

if (!function_exists('lc_partner_entity_type_label')) {
    function lc_partner_entity_type_label($type)
    {
        if ($type === 'individual') {
            return '개인';
        }
        if ($type === 'business') {
            return '사업자';
        }
        return '';
    }
}

if (!function_exists('lc_partner_normalize_identity')) {
    /**
     * @param array $input
     * @return array{ok:bool,message:string,errors?:array<string,string>,identity?:array}
     */
    function lc_partner_normalize_identity(array $input)
    {
        $entity_type = trim((string) ($input['entityType'] ?? $input['entity_type'] ?? ''));
        $resident_no = preg_replace('/\D+/', '', (string) ($input['residentNo'] ?? $input['resident_no'] ?? ''));
        $company_name = trim((string) ($input['companyName'] ?? $input['company_name'] ?? ''));
        $business_number_raw = (string) ($input['businessNumber'] ?? $input['business_number'] ?? '');
        $business_digits = preg_replace('/\D+/', '', $business_number_raw);
        $representative_name = trim((string) ($input['representativeName'] ?? $input['representative_name'] ?? ''));
        $company_address = trim((string) ($input['companyAddress'] ?? $input['company_address'] ?? ''));

        $errors = array();
        if ($entity_type !== 'individual' && $entity_type !== 'business') {
            $errors['entityType'] = '개인 또는 사업자를 선택해 주세요.';
        }

        if ($entity_type === 'individual') {
            if (strlen((string) $resident_no) !== 13) {
                $errors['residentNo'] = '주민등록번호 13자리를 입력해 주세요.';
            }
            $company_name = '';
            $business_digits = '';
            $representative_name = '';
            $company_address = '';
        }

        if ($entity_type === 'business') {
            if ($company_name === '') {
                $errors['companyName'] = '회사명을 입력해 주세요.';
            }
            if (strlen((string) $business_digits) !== 10) {
                $errors['businessNumber'] = '사업자등록번호는 000-00-00000 형식으로 입력해 주세요.';
            }
            if ($representative_name === '') {
                $errors['representativeName'] = '대표자명을 입력해 주세요.';
            }
            if ($company_address === '') {
                $errors['companyAddress'] = '사업장 주소를 입력해 주세요.';
            }
            $resident_no = '';
        }

        if (!empty($errors)) {
            return array(
                'ok' => false,
                'message' => reset($errors),
                'errors' => $errors,
            );
        }

        $resident_fmt = $resident_no !== ''
            ? substr($resident_no, 0, 6) . '-' . substr($resident_no, 6)
            : '';
        $business_fmt = $business_digits !== ''
            ? substr($business_digits, 0, 3) . '-' . substr($business_digits, 3, 2) . '-' . substr($business_digits, 5)
            : '';

        return array(
            'ok' => true,
            'message' => '',
            'identity' => array(
                'entity_type'         => $entity_type,
                'resident_no'         => $resident_fmt,
                'company_name'        => mb_substr($company_name, 0, 200),
                'business_number'     => $business_fmt,
                'representative_name' => mb_substr($representative_name, 0, 100),
                'company_address'     => mb_substr($company_address, 0, 300),
            ),
        );
    }
}

if (!function_exists('lc_partner_apply_identity_sql')) {
    function lc_partner_apply_identity_sql(array $identity)
    {
        return "pt_entity_type = '" . lc_sql_escape($identity['entity_type']) . "',
                pt_resident_no = '" . lc_sql_escape($identity['resident_no']) . "',
                pt_company_name = '" . lc_sql_escape($identity['company_name']) . "',
                pt_business_number = '" . lc_sql_escape($identity['business_number']) . "',
                pt_representative_name = '" . lc_sql_escape($identity['representative_name']) . "',
                pt_company_address = '" . lc_sql_escape($identity['company_address']) . "'";
    }
}

if (!function_exists('lc_partner_create')) {
    /**
     * @param array|null $identity Normalized identity fields from lc_partner_normalize_identity
     * @return array{ok:bool,message:string,partner:array|null,errors?:array}
     */
    function lc_partner_create($mb_id, $name = '', $status = LC_PARTNER_STATUS_ACTIVE, $identity = null)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'partner' => null);
        }

        if ($mb_id === '') {
            return array('ok' => false, 'message' => '회원 ID가 필요합니다.', 'partner' => null);
        }

        $has_identity = is_array($identity) && !empty($identity['entity_type']);

        $existing = lc_get_partner_by_mb_id($mb_id);
        if ($existing) {
            // 기존 승인대기 계정은 즉시 활성화 (관리자 승인 없이 이용)
            if (($existing['pt_status'] ?? '') === LC_PARTNER_STATUS_PENDING) {
                $table = lc_table('partners');
                $pt_id = (int) $existing['pt_id'];
                $sets = array("pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "'", 'pt_updated_at = NOW()');
                if ($has_identity) {
                    $sets[] = lc_partner_apply_identity_sql($identity);
                }
                $name = trim((string) $name);
                if ($name !== '') {
                    $sets[] = "pt_name = '" . lc_sql_escape($name) . "'";
                } elseif ($has_identity && $identity['entity_type'] === 'business' && $identity['company_name'] !== '') {
                    $sets[] = "pt_name = '" . lc_sql_escape($identity['company_name']) . "'";
                }
                lc_sql_query(
                    " UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE pt_id = '{$pt_id}' ",
                    false
                );
                $partner = lc_get_partner_by_id($pt_id);
                return array('ok' => true, 'message' => '파트너가 활성화되었습니다.', 'partner' => $partner);
            }
            return array('ok' => false, 'message' => '이미 파트너 신청 또는 등록이 있습니다.', 'partner' => $existing);
        }

        $name = trim((string) $name);
        if ($name === '' && $has_identity && $identity['entity_type'] === 'business' && $identity['company_name'] !== '') {
            $name = $identity['company_name'];
        }
        if ($name === '') {
            global $member;
            if (isset($member['mb_id']) && $member['mb_id'] === $mb_id) {
                $name = isset($member['mb_name']) ? (string) $member['mb_name'] : $mb_id;
            } else {
                $name = $mb_id;
            }
        }

        $table = lc_table('partners');
        $mb_id_esc = lc_sql_escape($mb_id);
        $name_esc = lc_sql_escape($name);
        $status_esc = lc_sql_escape($status);
        $temp_code = 'TMP-' . uniqid();
        $identity_sql = $has_identity ? (', ' . lc_partner_apply_identity_sql($identity)) : '';

        lc_sql_query(" INSERT INTO `{$table}`
            SET mb_id = '{$mb_id_esc}',
                pt_code = '" . lc_sql_escape($temp_code) . "',
                pt_name = '{$name_esc}',
                pt_status = '{$status_esc}'
                {$identity_sql},
                pt_created_at = NOW(),
                pt_updated_at = NOW() ", false);

        $pt_id = (int) lc_sql_insert_id();
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 생성에 실패했습니다.', 'partner' => null);
        }

        $code = lc_partner_generate_code($pt_id);
        lc_sql_query(" UPDATE `{$table}` SET pt_code = '" . lc_sql_escape($code) . "' WHERE pt_id = '{$pt_id}' ", false);

        $partner = lc_get_partner_by_id($pt_id);

        return array('ok' => true, 'message' => '파트너가 등록되었습니다.', 'partner' => $partner);
    }
}

if (!function_exists('lc_partner_update_status')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_partner_update_status($pt_id, $status)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $allowed = array(LC_PARTNER_STATUS_PENDING, LC_PARTNER_STATUS_ACTIVE, LC_PARTNER_STATUS_SUSPENDED);
        if (!in_array($status, $allowed, true)) {
            return array('ok' => false, 'message' => '유효하지 않은 상태입니다.');
        }

        $pt_id = (int) $pt_id;
        $table = lc_table('partners');
        $status_esc = lc_sql_escape($status);

        lc_sql_query(" UPDATE `{$table}` SET pt_status = '{$status_esc}', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' ", false);

        return array('ok' => true, 'message' => '파트너 상태가 변경되었습니다.');
    }
}

if (!function_exists('lc_partner_delete')) {
    /**
     * 파트너 삭제. 접수 DB가 있으면 기본적으로 거부. $force=true 이면 전환 연결을 끊고 삭제.
     *
     * @return array{ok:bool,message:string,conversionCount?:int}
     */
    function lc_partner_delete($pt_id, $force = false)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
            return array('ok' => false, 'message' => '삭제 권한이 없습니다.');
        }

        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 ID가 필요합니다.');
        }

        $partner = lc_get_partner_by_id($pt_id);
        if (!is_array($partner)) {
            return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
        }

        $cv_table = lc_table('conversions');
        $cv_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` WHERE pt_id = '{$pt_id}' ");
        $conversion_count = is_array($cv_row) ? (int) ($cv_row['cnt'] ?? 0) : 0;

        if ($conversion_count > 0 && !$force) {
            return array(
                'ok'              => false,
                'message'         => '접수 DB가 ' . number_format($conversion_count) . '건 있는 파트너는 삭제할 수 없습니다. 전체 디비 초기화 후 다시 시도하거나 강제 삭제를 사용하세요.',
                'conversionCount' => $conversion_count,
            );
        }

        if ($force && $conversion_count > 0) {
            lc_sql_query(" UPDATE `{$cv_table}` SET pt_id = 0 WHERE pt_id = '{$pt_id}' ", false);
        }

        $lk_table = lc_table('links');
        if (lc_db_table_exists($lk_table)) {
            lc_sql_query(" DELETE FROM `{$lk_table}` WHERE pt_id = '{$pt_id}' ", false);
        }

        $st_table = lc_table('settlements');
        if (lc_db_table_exists($st_table)) {
            lc_sql_query(" DELETE FROM `{$st_table}` WHERE pt_id = '{$pt_id}' ", false);
        }

        $pt_table = lc_table('partners');
        lc_sql_query(" DELETE FROM `{$pt_table}` WHERE pt_id = '{$pt_id}' LIMIT 1 ", false);

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('partner_delete', 'partner', $pt_id, '파트너 삭제: ' . (string) ($partner['pt_name'] ?? ''), array(
                'mb_id'            => (string) ($partner['mb_id'] ?? ''),
                'conversion_count' => $conversion_count,
                'force'            => $force ? 1 : 0,
            ));
        }

        return array(
            'ok'              => true,
            'message'         => '파트너가 삭제되었습니다.',
            'conversionCount' => $conversion_count,
        );
    }
}

if (!function_exists('lc_require_partner_access')) {
    function lc_require_partner_access()
    {
        if (!LC_PARTNER_GUARD_ENABLED || !lc_db_installed()) {
            return;
        }

        if (lc_is_super_admin()) {
            return;
        }

        if (!lc_is_logged_in()) {
            $return = defined('G5_URL') && isset($_SERVER['REQUEST_URI'])
                ? G5_URL . $_SERVER['REQUEST_URI']
                : lc_url('partner/dashboard.php');
            goto_url(lc_login_url($return));
        }

        if (!lc_is_partner()) {
            lc_render_partner_gate('not_partner');
            exit;
        }

        if (!lc_is_active_partner()) {
            lc_render_partner_gate('pending');
            exit;
        }
    }
}

if (!function_exists('lc_render_partner_gate')) {
    function lc_render_partner_gate($reason = 'not_partner')
    {
        global $lc_page_title, $lc_body_class, $lc_center;

        $lc_center = LC_CENTER_PARTNER;
        $lc_page_title = '파트너센터 이용 안내';
        $lc_body_class = 'lc-app lc-app--center lc-app--partner';

        $partner = lc_get_current_partner();
        $status = is_array($partner) && isset($partner['pt_status']) ? $partner['pt_status'] : '';

        include LC_LAYOUT_PATH . '/header.php';
        echo '<div class="lc-shell lc-shell--partner"><div class="lc-content lc-content--partner">';
        echo '<div class="lc-center-body"><div class="lc-panel lc-panel--partner" style="max-width:640px;margin:2rem auto;">';

        if ($reason === 'pending' || $status === LC_PARTNER_STATUS_PENDING) {
            echo '<h1 class="lc-panel__title">파트너 등록 확인</h1>';
            echo '<p class="lc-muted">이전에 신청하신 내역이 있습니다. 개인/사업자 정보를 입력한 뒤 활성화해 주세요.</p>';
            if ($partner) {
                echo '<p>파트너 코드: <code>' . lc_h($partner['pt_code']) . '</code></p>';
            }
            echo '<form method="post" action="' . lc_h(lc_url('partner/api/apply.php')) . '" style="margin-top:1.5rem;" class="lc-form">';
            echo '<div style="margin-bottom:1rem;"><label>가입 유형</label><select name="entityType" required>';
            echo '<option value="">선택</option><option value="individual">개인</option><option value="business">사업자</option></select></div>';
            echo '<div style="margin-bottom:1rem;"><label>주민등록번호 (개인)</label><input type="text" name="residentNo" placeholder="000000-0000000"></div>';
            echo '<div style="margin-bottom:1rem;"><label>회사명 (사업자)</label><input type="text" name="companyName"></div>';
            echo '<div style="margin-bottom:1rem;"><label>사업자등록번호</label><input type="text" name="businessNumber" placeholder="000-00-00000"></div>';
            echo '<div style="margin-bottom:1rem;"><label>대표자명</label><input type="text" name="representativeName"></div>';
            echo '<div style="margin-bottom:1rem;"><label>사업장 주소</label><input type="text" name="companyAddress"></div>';
            echo '<button type="submit" class="lc-btn lc-btn--primary">파트너 활성화하기</button>';
            echo '</form>';
        } elseif ($status === LC_PARTNER_STATUS_SUSPENDED) {
            echo '<h1 class="lc-panel__title">파트너 계정 정지</h1>';
            echo '<p class="lc-muted">계정이 정지되었습니다. 고객센터로 문의해 주세요.</p>';
        } else {
            echo '<h1 class="lc-panel__title">파트너 등록이 필요합니다</h1>';
            echo '<p class="lc-muted">개인 또는 사업자를 선택한 뒤 필수 정보를 입력하면 즉시 이용할 수 있습니다.</p>';
            echo '<form method="post" action="' . lc_h(lc_url('partner/api/apply.php')) . '" style="margin-top:1.5rem;" class="lc-form">';
            echo '<div style="margin-bottom:1rem;"><label>가입 유형</label><select name="entityType" required>';
            echo '<option value="">선택</option><option value="individual">개인</option><option value="business">사업자</option></select></div>';
            echo '<div style="margin-bottom:1rem;"><label>주민등록번호 (개인)</label><input type="text" name="residentNo" placeholder="000000-0000000"></div>';
            echo '<div style="margin-bottom:1rem;"><label>회사명 (사업자)</label><input type="text" name="companyName"></div>';
            echo '<div style="margin-bottom:1rem;"><label>사업자등록번호</label><input type="text" name="businessNumber" placeholder="000-00-00000"></div>';
            echo '<div style="margin-bottom:1rem;"><label>대표자명</label><input type="text" name="representativeName"></div>';
            echo '<div style="margin-bottom:1rem;"><label>사업장 주소</label><input type="text" name="companyAddress"></div>';
            echo '<button type="submit" class="lc-btn lc-btn--primary">파트너 등록하기</button>';
            echo '</form>';
        }

        echo '<p style="margin-top:1.5rem"><a class="lc-btn lc-btn--ghost" href="' . lc_h(lc_public_home_url()) . '">홈으로</a></p>';
        echo '</div></div></div></div>';
        $lc_show_footer = false;
        include LC_LAYOUT_PATH . '/footer.php';
    }
}
