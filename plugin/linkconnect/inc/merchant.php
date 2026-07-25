<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_status_label')) {
    function lc_merchant_status_label($status)
    {
        $labels = array(
            LC_MERCHANT_STATUS_PENDING   => '승인 대기',
            LC_MERCHANT_STATUS_ACTIVE    => '운영중',
            LC_MERCHANT_STATUS_SUSPENDED => '정지',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_merchant_generate_code')) {
    function lc_merchant_generate_code($mt_id)
    {
        return 'ADV-' . str_pad((string) (int) $mt_id, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_get_merchant_by_mb_id')) {
    function lc_get_merchant_by_mb_id($mb_id)
    {
        if (!lc_db_installed() || $mb_id === '') {
            return null;
        }

        $mb_id = lc_sql_escape($mb_id);
        $table = lc_table('merchants');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mb_id = '{$mb_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_get_merchant_by_id')) {
    function lc_get_merchant_by_id($mt_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('merchants');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mt_id = '{$mt_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_get_current_merchant')) {
    function lc_get_current_merchant()
    {
        if (function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant')) {
            $record = lc_impersonate_record();
            if (is_array($record)) {
                return $record;
            }
        }

        global $member;

        if (!lc_is_logged_in() || !isset($member['mb_id'])) {
            return null;
        }

        return lc_get_merchant_by_mb_id($member['mb_id']);
    }
}

if (!function_exists('lc_is_merchant')) {
    function lc_is_merchant()
    {
        return lc_get_current_merchant() !== null;
    }
}

if (!function_exists('lc_is_active_merchant')) {
    function lc_is_active_merchant()
    {
        $merchant = lc_get_current_merchant();

        return is_array($merchant) && isset($merchant['mt_status']) && $merchant['mt_status'] === LC_MERCHANT_STATUS_ACTIVE;
    }
}

if (!function_exists('lc_merchant_to_api')) {
    function lc_merchant_to_api(array $merchant)
    {
        return array(
            'id'          => (int) $merchant['mt_id'],
            'code'        => (string) $merchant['mt_code'],
            'company'     => (string) $merchant['mt_company'],
            'status'      => (string) $merchant['mt_status'],
            'statusLabel' => lc_merchant_status_label($merchant['mt_status']),
            'balance'     => (int) $merchant['mt_balance'],
            'createdAt'   => (string) $merchant['mt_created_at'],
        );
    }
}

if (!function_exists('lc_merchant_create')) {
    /**
     * @return array{ok:bool,message:string,merchant:array|null}
     */
    function lc_merchant_create($mb_id, $company = '', $status = LC_MERCHANT_STATUS_ACTIVE, $initial_balance = 0)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'merchant' => null);
        }

        if ($mb_id === '') {
            return array('ok' => false, 'message' => '회원 ID가 필요합니다.', 'merchant' => null);
        }

        $existing = lc_get_merchant_by_mb_id($mb_id);
        if ($existing) {
            // 기존 승인대기 계정은 즉시 활성화 (관리자 승인 없이 이용)
            if (($existing['mt_status'] ?? '') === LC_MERCHANT_STATUS_PENDING) {
                lc_merchant_update_status((int) $existing['mt_id'], LC_MERCHANT_STATUS_ACTIVE);
                $merchant = lc_get_merchant_by_id((int) $existing['mt_id']);
                return array('ok' => true, 'message' => '광고주가 활성화되었습니다.', 'merchant' => $merchant);
            }
            return array('ok' => false, 'message' => '이미 광고주 신청 또는 등록이 있습니다.', 'merchant' => $existing);
        }

        $company = trim((string) $company);
        if ($company === '') {
            global $member;
            if (isset($member['mb_id']) && $member['mb_id'] === $mb_id) {
                $company = isset($member['mb_name']) ? (string) $member['mb_name'] : $mb_id;
            } else {
                $company = $mb_id;
            }
        }

        $table = lc_table('merchants');
        $mb_id_esc = lc_sql_escape($mb_id);
        $company_esc = lc_sql_escape($company);
        $status_esc = lc_sql_escape($status);
        $balance = (int) $initial_balance;
        $temp_code = 'TMP-' . uniqid();

        lc_sql_query(" INSERT INTO `{$table}`
            SET mb_id = '{$mb_id_esc}',
                mt_code = '" . lc_sql_escape($temp_code) . "',
                mt_company = '{$company_esc}',
                mt_status = '{$status_esc}',
                mt_balance = '{$balance}',
                mt_created_at = NOW(),
                mt_updated_at = NOW() ", false);

        $mt_id = (int) lc_sql_insert_id();
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => '광고주 생성에 실패했습니다.', 'merchant' => null);
        }

        $code = lc_merchant_generate_code($mt_id);
        lc_sql_query(" UPDATE `{$table}` SET mt_code = '" . lc_sql_escape($code) . "' WHERE mt_id = '{$mt_id}' ", false);

        if ($balance > 0) {
            lc_wallet_record($mt_id, 'charge', $balance, '초기 광고비 지급', 'setup', $mt_id);
        }

        $merchant = lc_get_merchant_by_id($mt_id);

        return array('ok' => true, 'message' => '광고주가 등록되었습니다.', 'merchant' => $merchant);
    }
}

if (!function_exists('lc_merchant_update_status')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_merchant_update_status($mt_id, $status)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $allowed = array(LC_MERCHANT_STATUS_PENDING, LC_MERCHANT_STATUS_ACTIVE, LC_MERCHANT_STATUS_SUSPENDED);
        if (!in_array($status, $allowed, true)) {
            return array('ok' => false, 'message' => '유효하지 않은 상태입니다.');
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('merchants');
        $status_esc = lc_sql_escape($status);

        lc_sql_query(" UPDATE `{$table}` SET mt_status = '{$status_esc}', mt_updated_at = NOW() WHERE mt_id = '{$mt_id}' ", false);

        return array('ok' => true, 'message' => '광고주 상태가 변경되었습니다.');
    }
}

if (!function_exists('lc_merchant_assign_campaigns')) {
    function lc_merchant_assign_campaigns($mt_id)
    {
        if (!lc_db_installed()) {
            return 0;
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('campaigns');

        lc_sql_query(" UPDATE `{$table}` SET mt_id = '{$mt_id}' WHERE mt_id = 0 ", false);

        return lc_sql_affected_rows();
    }
}

if (!function_exists('lc_merchant_seed_defaults')) {
    /**
     * @return array{ok:bool,merchant:array|null}
     */
    function lc_merchant_seed_defaults($mb_id, $company = '', $balance = 0)
    {
        $create = lc_merchant_create($mb_id, $company, LC_MERCHANT_STATUS_ACTIVE, $balance);
        if ($create['ok'] && is_array($create['merchant'])) {
            lc_merchant_assign_campaigns((int) $create['merchant']['mt_id']);
            if (function_exists('lc_conversion_seed_for_merchant')) {
                lc_conversion_seed_for_merchant((int) $create['merchant']['mt_id']);
            }

            return array('ok' => true, 'merchant' => lc_get_merchant_by_id((int) $create['merchant']['mt_id']));
        }

        if (!$create['ok'] && is_array($create['merchant'])) {
            $mt_id = (int) $create['merchant']['mt_id'];
            lc_merchant_update_status($mt_id, LC_MERCHANT_STATUS_ACTIVE);
            lc_merchant_assign_campaigns($mt_id);
            if (function_exists('lc_conversion_seed_for_merchant')) {
                lc_conversion_seed_for_merchant($mt_id);
            }

            return array('ok' => true, 'merchant' => lc_get_merchant_by_id($mt_id));
        }

        return array('ok' => false, 'merchant' => null);
    }
}

if (!function_exists('lc_require_merchant_access')) {
    function lc_require_merchant_access()
    {
        if (!LC_MERCHANT_GUARD_ENABLED || !lc_db_installed()) {
            return;
        }

        if (lc_is_super_admin()) {
            if (!(function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant'))) {
                return;
            }
        }

        if (!lc_is_logged_in()) {
            $return = defined('G5_URL') && isset($_SERVER['REQUEST_URI'])
                ? G5_URL . $_SERVER['REQUEST_URI']
                : lc_url('merchant/dashboard.php');
            goto_url(lc_login_url($return));
        }

        if (!lc_is_merchant()) {
            lc_render_merchant_gate('not_merchant');
            exit;
        }

        if (!lc_is_active_merchant()) {
            lc_render_merchant_gate('pending');
            exit;
        }

        if (function_exists('lc_merchant_contract_require_access_for_page')) {
            lc_merchant_contract_require_access_for_page();
        }
    }
}

if (!function_exists('lc_render_merchant_gate')) {
    function lc_render_merchant_gate($reason = 'not_merchant')
    {
        global $lc_page_title, $lc_body_class, $lc_center;

        $lc_center = LC_CENTER_MERCHANT;
        $lc_page_title = '광고주센터 이용 안내';
        $lc_body_class = 'lc-app lc-app--center lc-app--merchant';

        $merchant = lc_get_current_merchant();
        $status = is_array($merchant) && isset($merchant['mt_status']) ? $merchant['mt_status'] : '';

        include LC_LAYOUT_PATH . '/header.php';
        echo '<div class="lc-shell lc-shell--merchant"><div class="lc-content lc-content--merchant">';
        echo '<div class="lc-center-body"><div class="lc-panel lc-panel--merchant" style="max-width:640px;margin:2rem auto;">';

        if ($reason === 'pending' || $status === LC_MERCHANT_STATUS_PENDING) {
            echo '<h1 class="lc-panel__title">광고주 등록 확인</h1>';
            echo '<p class="lc-muted">이전에 신청하신 내역이 있습니다. 아래 버튼을 누르면 즉시 활성화되어 광고주센터를 이용할 수 있습니다.</p>';
            if ($merchant) {
                echo '<p>광고주 코드: <code>' . lc_h($merchant['mt_code']) . '</code></p>';
            }
            echo '<form method="post" action="' . lc_h(lc_url('merchant/api/apply.php')) . '" style="margin-top:1.5rem;">';
            echo '<button type="submit" class="lc-btn lc-btn--primary">광고주 활성화하기</button>';
            echo '</form>';
        } elseif ($status === LC_MERCHANT_STATUS_SUSPENDED) {
            echo '<h1 class="lc-panel__title">광고주 계정 정지</h1>';
            echo '<p class="lc-muted">계정이 정지되었습니다. 고객센터로 문의해 주세요.</p>';
        } else {
            echo '<h1 class="lc-panel__title">광고주 등록이 필요합니다</h1>';
            echo '<p class="lc-muted">광고주센터를 이용하려면 광고주로 등록해 주세요. 등록 즉시 이용할 수 있습니다.</p>';
            echo '<form method="post" action="' . lc_h(lc_url('merchant/api/apply.php')) . '" style="margin-top:1.5rem;">';
            echo '<button type="submit" class="lc-btn lc-btn--primary">광고주 등록하기</button>';
            echo '</form>';
        }

        echo '<p style="margin-top:1.5rem"><a class="lc-btn lc-btn--ghost" href="' . lc_h(lc_public_home_url()) . '">홈으로</a></p>';
        echo '</div></div></div></div>';
        $lc_show_footer = false;
        include LC_LAYOUT_PATH . '/footer.php';
    }
}
