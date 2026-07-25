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
        return array(
            'id'          => (int) $partner['pt_id'],
            'code'        => (string) $partner['pt_code'],
            'name'        => (string) $partner['pt_name'],
            'status'      => (string) $partner['pt_status'],
            'statusLabel' => lc_partner_status_label($partner['pt_status']),
            'balance'     => (int) $partner['pt_balance'],
            'bankName'    => (string) $partner['pt_bank_name'],
            'bankAccount' => (string) $partner['pt_bank_account'],
            'bankHolder'  => (string) $partner['pt_bank_holder'],
            'createdAt'   => (string) $partner['pt_created_at'],
        );
    }
}

if (!function_exists('lc_partner_create')) {
    /**
     * @return array{ok:bool,message:string,partner:array|null}
     */
    function lc_partner_create($mb_id, $name = '', $status = LC_PARTNER_STATUS_ACTIVE)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'partner' => null);
        }

        if ($mb_id === '') {
            return array('ok' => false, 'message' => '회원 ID가 필요합니다.', 'partner' => null);
        }

        $existing = lc_get_partner_by_mb_id($mb_id);
        if ($existing) {
            // 기존 승인대기 계정은 즉시 활성화 (관리자 승인 없이 이용)
            if (($existing['pt_status'] ?? '') === LC_PARTNER_STATUS_PENDING) {
                lc_partner_update_status((int) $existing['pt_id'], LC_PARTNER_STATUS_ACTIVE);
                $partner = lc_get_partner_by_id((int) $existing['pt_id']);
                return array('ok' => true, 'message' => '파트너가 활성화되었습니다.', 'partner' => $partner);
            }
            return array('ok' => false, 'message' => '이미 파트너 신청 또는 등록이 있습니다.', 'partner' => $existing);
        }

        $name = trim((string) $name);
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

        lc_sql_query(" INSERT INTO `{$table}`
            SET mb_id = '{$mb_id_esc}',
                pt_code = '" . lc_sql_escape($temp_code) . "',
                pt_name = '{$name_esc}',
                pt_status = '{$status_esc}',
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
            echo '<p class="lc-muted">이전에 신청하신 내역이 있습니다. 아래 버튼을 누르면 즉시 활성화되어 파트너센터를 이용할 수 있습니다.</p>';
            if ($partner) {
                echo '<p>파트너 코드: <code>' . lc_h($partner['pt_code']) . '</code></p>';
            }
            echo '<form method="post" action="' . lc_h(lc_url('partner/api/apply.php')) . '" style="margin-top:1.5rem;">';
            echo '<button type="submit" class="lc-btn lc-btn--primary">파트너 활성화하기</button>';
            echo '</form>';
        } elseif ($status === LC_PARTNER_STATUS_SUSPENDED) {
            echo '<h1 class="lc-panel__title">파트너 계정 정지</h1>';
            echo '<p class="lc-muted">계정이 정지되었습니다. 고객센터로 문의해 주세요.</p>';
        } else {
            echo '<h1 class="lc-panel__title">파트너 등록이 필요합니다</h1>';
            echo '<p class="lc-muted">파트너센터를 이용하려면 파트너로 등록해 주세요. 등록 즉시 이용할 수 있습니다.</p>';
            echo '<form method="post" action="' . lc_h(lc_url('partner/api/apply.php')) . '" style="margin-top:1.5rem;">';
            echo '<button type="submit" class="lc-btn lc-btn--primary">파트너 등록하기</button>';
            echo '</form>';
        }

        echo '<p style="margin-top:1.5rem"><a class="lc-btn lc-btn--ghost" href="' . lc_h(lc_public_home_url()) . '">홈으로</a></p>';
        echo '</div></div></div></div>';
        $lc_show_footer = false;
        include LC_LAYOUT_PATH . '/footer.php';
    }
}
