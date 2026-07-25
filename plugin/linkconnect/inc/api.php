<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_api_json')) {
    function lc_api_json(array $payload, $status = 200)
    {
        if (!headers_sent()) {
            http_response_code((int) $status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        exit;
    }
}

if (!function_exists('lc_api_success')) {
    function lc_api_success($data = null, $meta = array())
    {
        $payload = array('ok' => true);
        if ($data !== null) {
            $payload['data'] = $data;
        }
        if ($meta) {
            $payload['meta'] = $meta;
        }

        lc_api_json($payload, 200);
    }
}

if (!function_exists('lc_api_error')) {
    function lc_api_error($message, $code = 'ERROR', $status = 400, array $extra = array())
    {
        $payload = array(
            'ok'    => false,
            'error' => (string) $message,
            'code'  => (string) $code,
        );
        if ($extra) {
            $payload['data'] = $extra;
        }

        lc_api_json($payload, (int) $status);
    }
}

if (!function_exists('lc_api_require_method')) {
    function lc_api_require_method($method)
    {
        $current = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        if ($current !== strtoupper($method)) {
            lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
        }
    }
}

if (!function_exists('lc_api_require_login')) {
    function lc_api_require_login()
    {
        if (!lc_is_logged_in()) {
            lc_api_error('로그인이 필요합니다.', 'UNAUTHORIZED', 401);
        }
    }
}

if (!function_exists('lc_api_require_active_partner')) {
    function lc_api_require_active_partner()
    {
        lc_api_require_login();

        if (lc_is_super_admin()) {
            return lc_get_current_partner();
        }

        if (!LC_PARTNER_GUARD_ENABLED || !lc_db_installed()) {
            return null;
        }

        $partner = lc_get_current_partner();
        if (!$partner) {
            lc_api_error('파트너 등록이 필요합니다.', 'NOT_PARTNER', 403);
        }

        if ($partner['pt_status'] !== LC_PARTNER_STATUS_ACTIVE) {
            lc_api_error('파트너 승인 후 이용할 수 있습니다.', 'PARTNER_NOT_ACTIVE', 403, array(
                'status' => $partner['pt_status'],
            ));
        }

        return $partner;
    }
}

if (!function_exists('lc_api_require_active_merchant')) {
    function lc_api_require_active_merchant()
    {
        lc_api_require_login();

        if (function_exists('lc_merchant_contract_guard_skip_user') && lc_merchant_contract_guard_skip_user()) {
            return lc_get_current_merchant();
        }

        if (!LC_MERCHANT_GUARD_ENABLED || !lc_db_installed()) {
            return null;
        }

        $merchant = lc_get_current_merchant();
        if (!$merchant) {
            lc_api_error('광고주 등록이 필요합니다.', 'NOT_MERCHANT', 403);
        }

        if ($merchant['mt_status'] !== LC_MERCHANT_STATUS_ACTIVE) {
            lc_api_error('광고주 승인 후 이용할 수 있습니다.', 'MERCHANT_NOT_ACTIVE', 403, array(
                'status' => $merchant['mt_status'],
            ));
        }

        if (function_exists('lc_merchant_contract_guard_api_or_fail')) {
            lc_merchant_contract_guard_api_or_fail($merchant);
        }

        return $merchant;
    }
}

if (!function_exists('lc_merchant_api_use_strict_guard')) {
    /**
     * 슈퍼관리자 직접 탐색(가맹점 가장 아님)은 샘플·완화 모드
     */
    function lc_merchant_api_use_strict_guard()
    {
        if (!LC_MERCHANT_GUARD_ENABLED || !lc_db_installed()) {
            return false;
        }

        if (function_exists('lc_merchant_contract_guard_skip_user') && lc_merchant_contract_guard_skip_user()) {
            return false;
        }

        return true;
    }
}

if (!function_exists('lc_api_require_contract_merchant')) {
    /**
     * 계약서 API 전용 — 세션의 현재 광고주만 허용 (관리자 직접 접근 불가)
     *
     * @return array<string,mixed>
     */
    function lc_api_require_contract_merchant()
    {
        lc_api_require_login();

        if (lc_is_super_admin() && !(function_exists('lc_impersonate_is_active') && lc_impersonate_is_active('merchant'))) {
            lc_api_error('광고주 계정으로 로그인해 주세요.', 'NOT_MERCHANT', 403);
        }

        $merchant = lc_get_current_merchant();
        if (!is_array($merchant)) {
            lc_api_error('광고주 등록이 필요합니다.', 'NOT_MERCHANT', 403);
        }

        if (($merchant['mt_status'] ?? '') === LC_MERCHANT_STATUS_SUSPENDED) {
            lc_api_error('정지된 광고주 계정입니다.', 'MERCHANT_SUSPENDED', 403);
        }

        return $merchant;
    }
}

if (!function_exists('lc_api_require_admin')) {
    function lc_api_require_admin()
    {
        lc_api_require_login();

        if (!LC_ADMIN_GUARD_ENABLED) {
            return true;
        }

        if (lc_can_access_admin()) {
            return true;
        }

        lc_api_error('관리자 권한이 필요합니다.', 'FORBIDDEN', 403);
    }
}

if (!function_exists('lc_api_read_json_body')) {
    function lc_api_read_json_body()
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return array();
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : array();
    }
}
