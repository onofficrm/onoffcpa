<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!defined('LC_MERCHANT_CONTRACT_GUARD_ENABLED')) {
    define('LC_MERCHANT_CONTRACT_GUARD_ENABLED', true);
}

if (!function_exists('lc_merchant_contract_grace_until')) {
    /**
     * 유예 종료일 (Y-m-d). 비어 있으면 즉시 제한.
     */
    function lc_merchant_contract_grace_until()
    {
        if (function_exists('lc_settings_get')) {
            $from_settings = trim((string) lc_settings_get('advertiserContractGraceUntil', ''));
            if ($from_settings !== '') {
                return $from_settings;
            }
        }

        if (defined('ADVERTISER_CONTRACT_GRACE_UNTIL') && (string) ADVERTISER_CONTRACT_GRACE_UNTIL !== '') {
            return (string) ADVERTISER_CONTRACT_GRACE_UNTIL;
        }

        return '';
    }
}

if (!function_exists('lc_merchant_contract_grace_active')) {
    function lc_merchant_contract_grace_active()
    {
        $until = lc_merchant_contract_grace_until();
        if ($until === '') {
            return false;
        }

        $ts = strtotime($until . ' 23:59:59');
        if ($ts === false) {
            return false;
        }

        return time() <= $ts;
    }
}

if (!function_exists('lc_merchant_contract_guard_skip_user')) {
    /**
     * 슈퍼관리자 직접 탐색(가맹점 가장 아님)은 계약 검사 제외
     */
    function lc_merchant_contract_guard_skip_user()
    {
        if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
            return false;
        }

        if (function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant')) {
            return false;
        }

        return true;
    }
}

if (!function_exists('lc_merchant_contract_applies_to_current_user')) {
    function lc_merchant_contract_applies_to_current_user()
    {
        if (!LC_MERCHANT_CONTRACT_GUARD_ENABLED || !lc_db_installed()) {
            return false;
        }

        if (lc_merchant_contract_guard_skip_user()) {
            return false;
        }

        if (function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant')) {
            return true;
        }

        return function_exists('lc_is_merchant') && lc_is_merchant();
    }
}

if (!function_exists('lc_merchant_contract_is_fully_signed')) {
    /**
     * 현재 버전 계약 완료 — 엄격 조건
     */
    function lc_merchant_contract_is_fully_signed($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return false;
        }

        $version = lc_merchant_contract_current_version();
        $contract = lc_merchant_contract_get($mt_id, $version);
        if (!is_array($contract)) {
            return false;
        }

        if ((int) ($contract['mc_mt_id'] ?? 0) !== $mt_id) {
            return false;
        }

        if ((string) ($contract['mc_contract_version'] ?? '') !== $version) {
            return false;
        }

        if ((string) ($contract['mc_status'] ?? '') !== LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
            return false;
        }

        $signed_at = (string) ($contract['mc_signed_at'] ?? '');
        if ($signed_at === '' || $signed_at === '0000-00-00 00:00:00') {
            return false;
        }

        if (trim((string) ($contract['mc_contract_pdf_path'] ?? '')) === '') {
            // 관리자 첨부 계약서: HTML 스냅샷이 커스텀 본문이면 PDF 없이도 체결로 인정
            $snap = (string) ($contract['mc_contract_snapshot'] ?? '');
            if (
                $snap !== ''
                && function_exists('lc_merchant_contract_custom_snapshot_matches')
                && lc_merchant_contract_custom_snapshot_matches($snap)
            ) {
                return true;
            }

            return false;
        }

        return true;
    }
}

if (!function_exists('lc_merchant_contract_access_cache_clear')) {
    function lc_merchant_contract_access_cache_clear($mt_id = null)
    {
        if ($mt_id === null) {
            $merchant = function_exists('lc_get_current_merchant') ? lc_get_current_merchant() : null;
            $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
        }

        $mt_id = (int) $mt_id;
        if ($mt_id > 0) {
            unset($_SESSION['lc_mc_access_' . $mt_id]);
        }
    }
}

if (!function_exists('lc_merchant_contract_access_state')) {
    /**
     * @return array{signed:bool,blocked:bool,graceActive:bool,requiresContract:bool,version:string}
     */
    function lc_merchant_contract_access_state($mt_id)
    {
        static $request_cache = array();

        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return array(
                'signed'            => false,
                'blocked'           => false,
                'graceActive'       => false,
                'requiresContract'  => true,
                'version'           => lc_merchant_contract_current_version(),
                'contractStatus'    => '',
            );
        }

        if (isset($request_cache[$mt_id])) {
            return $request_cache[$mt_id];
        }

        $version = lc_merchant_contract_current_version();

        $signed = lc_merchant_contract_is_fully_signed($mt_id);
        $contract_status = function_exists('lc_merchant_contract_status')
            ? lc_merchant_contract_status($mt_id)
            : '';
        $grace = !$signed && lc_merchant_contract_grace_active();
        $blocked = !$signed && !$grace;

        $state = array(
            'signed'           => $signed,
            'blocked'          => $blocked,
            'graceActive'      => $grace,
            'requiresContract' => !$signed,
            'version'          => $version,
            'contractStatus'   => $contract_status,
        );

        $request_cache[$mt_id] = $state;

        return $state;
    }
}

if (!function_exists('lc_merchant_contract_access_blocked')) {
    function lc_merchant_contract_access_blocked($mt_id)
    {
        if (!lc_merchant_contract_applies_to_current_user()) {
            return false;
        }

        return lc_merchant_contract_access_state((int) $mt_id)['blocked'];
    }
}

if (!function_exists('lc_merchant_contract_access_state_for_auth')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_contract_access_state_for_auth()
    {
        if (!lc_merchant_contract_applies_to_current_user()) {
            return array(
                'applies'          => false,
                'signed'           => true,
                'blocked'          => false,
                'graceActive'      => false,
                'requiresContract' => false,
                'hasSignedHistory' => false,
                'viewable'         => false,
                'contractStatus'   => '',
            );
        }

        $merchant = lc_get_current_merchant();
        $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
        $state = lc_merchant_contract_access_state($mt_id);
        $has_signed_history = $mt_id > 0 && is_array(lc_merchant_contract_latest_signed($mt_id));

        return array_merge($state, array(
            'applies'          => true,
            'hasSignedHistory' => $has_signed_history,
            'viewable'         => !empty($state['signed']) || $has_signed_history,
        ));
    }
}

if (!function_exists('lc_merchant_contract_api_allowed_scripts')) {
    /**
     * 계약 미체결 시에도 허용하는 merchant API
     *
     * @return string[]
     */
    function lc_merchant_contract_api_allowed_scripts()
    {
        return array(
            'contract.php',
            'me.php',
            'apply.php',
            'inquiries.php',
        );
    }
}

if (!function_exists('lc_merchant_contract_api_is_allowed')) {
    function lc_merchant_contract_api_is_allowed($script_name = '')
    {
        if ($script_name === '') {
            $script_name = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        }

        return in_array($script_name, lc_merchant_contract_api_allowed_scripts(), true);
    }
}

if (!function_exists('lc_merchant_contract_guard_api_or_fail')) {
    /**
     * @param array<string,mixed> $merchant
     */
    function lc_merchant_contract_guard_api_or_fail(array $merchant)
    {
        if (!lc_merchant_contract_applies_to_current_user()) {
            return;
        }

        if (lc_merchant_contract_api_is_allowed()) {
            return;
        }

        $mt_id = (int) ($merchant['mt_id'] ?? 0);
        if (!lc_merchant_contract_access_blocked($mt_id)) {
            return;
        }

        lc_api_error(
            'CPA 광고 제휴 계약 체결이 필요합니다.',
            'CONTRACT_REQUIRED',
            403,
            array(
                'redirect' => '/advertiser/contract',
                'message'  => 'CPA 광고 제휴 계약 체결이 필요합니다.',
            )
        );
    }
}

if (!function_exists('lc_merchant_contract_guard_merchant_api')) {
    function lc_merchant_contract_guard_merchant_api()
    {
        if (!lc_merchant_contract_applies_to_current_user()) {
            return;
        }

        if (lc_merchant_contract_api_is_allowed()) {
            return;
        }

        if (!lc_is_logged_in()) {
            return;
        }

        $merchant = lc_get_current_merchant();
        if (!is_array($merchant)) {
            return;
        }

        if (($merchant['mt_status'] ?? '') !== LC_MERCHANT_STATUS_ACTIVE) {
            return;
        }

        lc_merchant_contract_guard_api_or_fail($merchant);
    }
}

if (!function_exists('lc_merchant_contract_redirect_url')) {
    function lc_merchant_contract_redirect_url()
    {
        if (defined('G5_PLUGIN_URL')) {
            return rtrim((string) G5_URL, '/') . '/advertiser/contract';
        }

        return '/advertiser/contract';
    }
}

if (!function_exists('lc_merchant_contract_require_access_for_page')) {
    function lc_merchant_contract_require_access_for_page()
    {
        if (!lc_merchant_contract_applies_to_current_user()) {
            return;
        }

        $merchant = lc_get_current_merchant();
        if (!is_array($merchant) || !lc_is_active_merchant()) {
            return;
        }

        $mt_id = (int) $merchant['mt_id'];
        if (!lc_merchant_contract_access_blocked($mt_id)) {
            return;
        }

        $target = lc_merchant_contract_redirect_url();
        if (function_exists('goto_url')) {
            goto_url($target);
        } else {
            header('Location: ' . $target);
        }
        exit;
    }
}
