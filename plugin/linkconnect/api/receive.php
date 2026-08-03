<?php
require_once dirname(__DIR__) . '/_common.php';

if (function_exists('lc_api_handle_cors_preflight')) {
    lc_api_handle_cors_preflight();
}
if (function_exists('lc_api_allow_public_cors')) {
    lc_api_allow_public_cors();
}

lc_api_require_method('POST');

$body = lc_api_read_json_body();
if (!$body && $_POST) {
    $body = $_POST;
}

$payload = array(
    'name'    => isset($body['name']) ? (string) $body['name'] : '',
    'phone'   => isset($body['phone']) ? (string) $body['phone'] : '',
    'email'   => isset($body['email']) ? (string) $body['email'] : '',
    'region'  => isset($body['region']) ? (string) $body['region'] : '',
    'inquiry' => isset($body['inquiry']) ? (string) $body['inquiry'] : '',
    'channel' => isset($body['channel']) ? (string) $body['channel'] : (isset($body['utm_source']) ? (string) $body['utm_source'] : ''),
    'sub_id'  => isset($body['sub_id']) ? (string) $body['sub_id'] : (isset($body['utm_campaign']) ? (string) $body['utm_campaign'] : ''),
);

if (!function_exists('lc_receive_public_host')) {
    function lc_receive_public_host()
    {
        $candidates = array(
            $_SERVER['HTTP_X_LC_PUBLIC_HOST'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '',
        );
        foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $key) {
            if (!empty($_SERVER[$key])) {
                $candidates[] = (string) parse_url((string) $_SERVER[$key], PHP_URL_HOST);
            }
        }
        if (function_exists('lc_link_request_host')) {
            $candidates[] = lc_link_request_host();
        }
        foreach ($candidates as $raw) {
            $host = strtolower(preg_replace('/:\d+$/', '', trim(explode(',', (string) $raw)[0])));
            if ($host !== '') {
                return $host;
            }
        }
        return '';
    }
}

if (!function_exists('lc_receive_campaign_matches_request')) {
    function lc_receive_campaign_matches_request(array $campaign, $public_host)
    {
        $public_host = strtolower(preg_replace('/:\d+$/', '', trim((string) $public_host)));
        if ($public_host === '') {
            return false;
        }

        $tracking_host = function_exists('lc_link_host_from_base_url')
            ? lc_link_host_from_base_url((string) ($campaign['cp_tracking_base_url'] ?? ''))
            : strtolower((string) parse_url((string) ($campaign['cp_tracking_base_url'] ?? ''), PHP_URL_HOST));
        if ($tracking_host !== '') {
            $aliases = function_exists('lc_link_host_with_www_aliases')
                ? lc_link_host_with_www_aliases($tracking_host)
                : array($tracking_host, 'www.' . $tracking_host);
            if (in_array($public_host, $aliases, true)) {
                return true;
            }
        }

        $landing_url = trim((string) ($campaign['cp_landing_url'] ?? ''));
        $landing_host = strtolower((string) parse_url($landing_url, PHP_URL_HOST));
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $referer_host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        $referer_path = (string) parse_url($referer, PHP_URL_PATH);
        $landing_path = (string) parse_url($landing_url, PHP_URL_PATH);

        if ($landing_host !== '' && $landing_host !== $public_host) {
            return false;
        }
        if ($referer_host !== '' && $referer_host !== $public_host) {
            return false;
        }

        return $landing_path !== ''
            && $referer_path !== ''
            && strpos(rtrim($referer_path, '/') . '/', rtrim($landing_path, '/') . '/') === 0;
    }
}

$lk_code = isset($body['lkCode']) ? trim((string) $body['lkCode']) : (isset($body['lk_code']) ? trim((string) $body['lk_code']) : '');

if ($lk_code !== '') {
    $link = lc_link_get_with_campaign($lk_code);
    if (!$link || $link['lk_status'] !== 'active' || $link['cp_status'] !== LC_STATUS_ACTIVE) {
        lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
    }

    $result = lc_conversion_create_from_link($link, $payload);
    if (!$result['ok']) {
        $err_code = isset($result['code']) ? (string) $result['code'] : 'CREATE_FAILED';
        if ($err_code === 'DUPLICATE_RECENT') {
            lc_api_success(array('message' => $result['message'], 'duplicate' => true));
        }
        lc_api_error($result['message'], $err_code, 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'code'       => is_array($result['conversion']) ? (string) $result['conversion']['cv_code'] : '',
        'conversion' => is_array($result['conversion']) ? lc_conversion_to_api_merchant(
            array_merge($result['conversion'], array('cp_name' => $link['cp_name'], 'pt_code' => '')),
            false
        ) : null,
    ));
}

$campaign_ref = '';
foreach (array('campaignId', 'campaign_id', 'cid', 'campaign_code', 'cpCode', 'cp_code') as $key) {
    if (isset($body[$key]) && trim((string) $body[$key]) !== '') {
        $campaign_ref = trim((string) $body[$key]);
        break;
    }
}

$campaign = null;
$public_host = lc_receive_public_host();
if ($campaign_ref !== '' && lc_db_installed()) {
    $cp_table = lc_table('campaigns');
    if (ctype_digit($campaign_ref)) {
        $campaign = lc_sql_fetch(
            " SELECT * FROM `{$cp_table}`
              WHERE cp_id = '" . (int) $campaign_ref . "'
                AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "'
              LIMIT 1 "
        );
    } else {
        $campaign = lc_sql_fetch(
            " SELECT * FROM `{$cp_table}`
              WHERE cp_code = '" . lc_sql_escape($campaign_ref) . "'
                AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "'
              LIMIT 1 "
        );
    }
}

if (!is_array($campaign) || !lc_receive_campaign_matches_request($campaign, $public_host)) {
    lc_api_error(
        '홍보 링크 또는 올바른 광고상품 랜딩에서 신청해 주세요.',
        'INVALID_LINK',
        400
    );
}

$result = lc_conversion_create_from_seo_campaign($campaign, $payload);
if (!$result['ok']) {
    $err_code = isset($result['code']) ? (string) $result['code'] : 'CREATE_FAILED';
    if ($err_code === 'DUPLICATE_RECENT') {
        lc_api_success(array('message' => $result['message'], 'duplicate' => true));
    }
    lc_api_error($result['message'], $err_code, 400);
}

lc_api_success(array(
    'message'    => $result['message'],
    'code'       => is_array($result['conversion']) ? (string) $result['conversion']['cv_code'] : '',
    'conversion' => is_array($result['conversion']) ? lc_conversion_to_api_merchant(
        array_merge(
            $result['conversion'],
            array('cp_name' => (string) ($campaign['cp_name'] ?? ''), 'pt_code' => 'SEO')
        ),
        false
    ) : null,
));
