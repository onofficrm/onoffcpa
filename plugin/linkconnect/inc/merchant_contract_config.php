<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * 광고주 CPA 계약서 공통 설정
 *
 * 계약서 버전·갑(을) 정보·상태값은 이 파일에서만 관리합니다.
 * 코드 여러 곳에 버전 문자열을 직접 입력하지 마세요.
 */

if (!defined('LC_MERCHANT_CONTRACT_CURRENT_VERSION')) {
    define('LC_MERCHANT_CONTRACT_CURRENT_VERSION', 'CPA-CONTRACT-2026-07');
}

if (!defined('LC_MERCHANT_CONTRACT_STATUS_PENDING')) {
    define('LC_MERCHANT_CONTRACT_STATUS_PENDING', 'pending');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS')) {
    define('LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS', 'in_progress');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_SIGNED')) {
    define('LC_MERCHANT_CONTRACT_STATUS_SIGNED', 'signed');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING')) {
    define('LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING', 'review_pending');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_REJECTED')) {
    define('LC_MERCHANT_CONTRACT_STATUS_REJECTED', 'rejected');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_CANCELLED')) {
    define('LC_MERCHANT_CONTRACT_STATUS_CANCELLED', 'cancelled');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_EXPIRED')) {
    define('LC_MERCHANT_CONTRACT_STATUS_EXPIRED', 'expired');
}
if (!defined('LC_MERCHANT_CONTRACT_STATUS_RENEWAL')) {
    define('LC_MERCHANT_CONTRACT_STATUS_RENEWAL', 'renewal');
}

if (!function_exists('lc_merchant_contract_config')) {
    /**
     * @return array{
     *   current_version:string,
     *   party_b:array<string,string>,
     *   statuses:array<string,string>
     * }
     */
    function lc_merchant_contract_config()
    {
        static $config = null;

        if (is_array($config)) {
            return $config;
        }

        $config = array(
            'current_version' => LC_MERCHANT_CONTRACT_CURRENT_VERSION,
            'party_b'         => array(
                'company_name'        => '비마이피스',
                'representative_name' => '박민우',
                'business_number'     => '831-51-00825',
                'company_address'     => '경기도 용인시 수지구 포은대로 59번길 37, 1009호',
                'company_phone'       => '010-9765-4073',
            ),
            'statuses' => array(
                LC_MERCHANT_CONTRACT_STATUS_PENDING     => '계약 미체결',
                LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS => '계약 작성 중',
                LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING => '관리자 승인 대기',
                LC_MERCHANT_CONTRACT_STATUS_REJECTED    => '승인 반려',
                LC_MERCHANT_CONTRACT_STATUS_SIGNED      => '관리자 승인 완료',
                LC_MERCHANT_CONTRACT_STATUS_CANCELLED   => '관리자에 의해 계약 취소',
                LC_MERCHANT_CONTRACT_STATUS_EXPIRED     => '계약 만료',
                LC_MERCHANT_CONTRACT_STATUS_RENEWAL     => '새 계약서 버전으로 재계약 필요',
            ),
        );

        return $config;
    }
}

if (!function_exists('lc_merchant_contract_current_version')) {
    function lc_merchant_contract_current_version()
    {
        return (string) lc_merchant_contract_config()['current_version'];
    }
}

if (!function_exists('lc_merchant_contract_party_b')) {
    /**
     * @return array<string,string>
     */
    function lc_merchant_contract_party_b()
    {
        return lc_merchant_contract_config()['party_b'];
    }
}

if (!function_exists('lc_merchant_contract_status_label')) {
    function lc_merchant_contract_status_label($status)
    {
        $statuses = lc_merchant_contract_config()['statuses'];

        return isset($statuses[$status]) ? $statuses[$status] : (string) $status;
    }
}

if (!function_exists('lc_merchant_contract_statuses')) {
    /**
     * @return string[]
     */
    function lc_merchant_contract_statuses()
    {
        return array_keys(lc_merchant_contract_config()['statuses']);
    }
}
