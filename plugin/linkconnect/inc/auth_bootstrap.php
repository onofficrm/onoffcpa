<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_auth_state')) {
    /**
     * @return array{
     *   loggedIn:bool,
     *   isSuperAdmin:bool,
     *   isPartner:bool,
     *   isActivePartner:bool,
     *   partnerId:int|null,
     *   partnerCode:string|null,
     *   partnerName:string|null,
     *   partnerStatus:string|null,
     *   dbReady:boolean,
     *   isMerchant:bool,
     *   isActiveMerchant:bool,
     *   merchantId:int|null,
     *   merchantCode:string|null,
     *   merchantCompany:string|null,
     *   merchantStatus:string|null,
     *   merchantBalance:int|null
     * }
     */
    function lc_auth_state()
    {
        global $member;

        $logged_in = function_exists('lc_is_logged_in') ? lc_is_logged_in() : false;
        $partner = function_exists('lc_get_current_partner') ? lc_get_current_partner() : null;
        $merchant = function_exists('lc_get_current_merchant') ? lc_get_current_merchant() : null;
        $impersonate = function_exists('lc_impersonate_state_for_api') ? lc_impersonate_state_for_api() : array('active' => false);
        $contract_access = function_exists('lc_merchant_contract_access_state_for_auth')
            ? lc_merchant_contract_access_state_for_auth()
            : array(
                'applies'          => false,
                'signed'           => true,
                'blocked'          => false,
                'graceActive'      => false,
                'requiresContract' => false,
            );

        return array(
            'loggedIn'          => $logged_in,
            'isSuperAdmin'      => function_exists('lc_is_super_admin') ? lc_is_super_admin() : false,
            'isImpersonating'   => !empty($impersonate['active']),
            'impersonateType'   => !empty($impersonate['active']) ? (string) ($impersonate['type'] ?? '') : null,
            'impersonateId'     => !empty($impersonate['active']) ? (int) ($impersonate['id'] ?? 0) : null,
            'impersonateLabel'  => !empty($impersonate['active']) ? (string) ($impersonate['label'] ?? '') : '',
            'isPartner'         => is_array($partner),
            'isActivePartner'   => function_exists('lc_is_active_partner') ? lc_is_active_partner() : false,
            'partnerId'         => is_array($partner) ? (int) $partner['pt_id'] : null,
            'partnerCode'       => is_array($partner) ? (string) $partner['pt_code'] : null,
            'partnerName'       => is_array($partner) ? (string) $partner['pt_name'] : null,
            'partnerStatus'     => is_array($partner) ? (string) $partner['pt_status'] : null,
            'dbReady'           => function_exists('lc_db_installed') ? lc_db_installed() : false,
            'isMerchant'        => is_array($merchant),
            'isActiveMerchant'  => function_exists('lc_is_active_merchant') ? lc_is_active_merchant() : false,
            'merchantId'        => is_array($merchant) ? (int) $merchant['mt_id'] : null,
            'merchantCode'      => is_array($merchant) ? (string) $merchant['mt_code'] : null,
            'merchantCompany'   => is_array($merchant) ? (string) $merchant['mt_company'] : null,
            'merchantStatus'    => is_array($merchant) ? (string) $merchant['mt_status'] : null,
            'merchantBalance'   => is_array($merchant) ? (int) $merchant['mt_balance'] : null,
            'isLinkconnectAdmin'=> function_exists('lc_is_linkconnect_admin') ? lc_is_linkconnect_admin() : false,
            'canAccessAdmin'    => function_exists('lc_can_access_admin') ? lc_can_access_admin() : false,
            'memberId'          => $logged_in && is_array($member) && isset($member['mb_id']) ? (string) $member['mb_id'] : null,
            'memberName'        => $logged_in && is_array($member) && isset($member['mb_name']) ? (string) $member['mb_name'] : null,
            'memberNick'        => $logged_in && is_array($member) && isset($member['mb_nick']) ? (string) $member['mb_nick'] : null,
            'memberEmail'       => $logged_in && is_array($member) && isset($member['mb_email']) ? (string) $member['mb_email'] : null,
            'bbsUrl'            => defined('G5_BBS_URL') ? (string) G5_BBS_URL : '/bbs',
            'siteUrl'           => defined('G5_URL') ? rtrim((string) G5_URL, '/') : '',
            'merchantContractApplies'    => !empty($contract_access['applies']),
            'merchantContractSigned'       => !empty($contract_access['signed']),
            'merchantContractBlocked'      => !empty($contract_access['blocked']),
            'merchantContractGraceActive'  => !empty($contract_access['graceActive']),
            'merchantContractRequires'     => !empty($contract_access['requiresContract']),
            'merchantContractHasSignedHistory' => !empty($contract_access['hasSignedHistory']),
            'merchantContractViewable'     => !empty($contract_access['viewable']),
            'merchantContractStatus'       => (string) ($contract_access['contractStatus'] ?? ''),
        );
    }
}

if (!function_exists('lc_auth_bootstrap_script')) {
    function lc_auth_bootstrap_script()
    {
        $json = json_encode(lc_auth_state(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return '<script>window.__LC_AUTH__=' . $json . ';</script>';
    }
}

if (!function_exists('lc_inject_auth_bootstrap')) {
    /**
     * @param string $html
     * @return string
     */
    function lc_inject_auth_bootstrap($html)
    {
        $script = lc_auth_bootstrap_script();
        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $script . '</head>', $html, 1);
        }

        return $script . $html;
    }
}
