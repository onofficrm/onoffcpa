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

// 허니팟: 봇이 채운 경우 성공처럼 응답하고 저장하지 않음
$honeypot = trim((string) ($body['website'] ?? $body['company_url'] ?? ''));
if ($honeypot !== '') {
    lc_api_success(array('message' => '상담 신청이 접수되었습니다.', 'duplicate' => false));
}

$channel = isset($body['channel']) ? trim((string) $body['channel']) : (isset($body['utm_source']) ? trim((string) $body['utm_source']) : '');
$source = isset($body['source']) ? trim((string) $body['source']) : '';
if ($source === '' && in_array(strtolower($channel), array('embed', 'wordpress', 'widget', 'external'), true)) {
    $source = defined('LC_SOURCE_EMBED') ? LC_SOURCE_EMBED : 'embed';
}
if ($source === '') {
    $source = defined('LC_SOURCE_FORM') ? LC_SOURCE_FORM : 'form';
}

$inquiry = isset($body['inquiry']) ? trim((string) $body['inquiry']) : '';
$page_url = isset($body['page_url']) ? trim((string) $body['page_url']) : (isset($body['pageUrl']) ? trim((string) $body['pageUrl']) : '');
if ($page_url !== '') {
    $page_url = function_exists('mb_substr') ? mb_substr($page_url, 0, 500) : substr($page_url, 0, 500);
}

$referer = isset($body['referer']) ? trim((string) $body['referer']) : (isset($body['referrer']) ? trim((string) $body['referrer']) : '');
if ($referer === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = trim((string) $_SERVER['HTTP_REFERER']);
}
if ($referer !== '') {
    $referer = function_exists('mb_substr') ? mb_substr($referer, 0, 500) : substr($referer, 0, 500);
}

$utm_source = isset($body['utm_source']) ? trim((string) $body['utm_source']) : (isset($body['utmSource']) ? trim((string) $body['utmSource']) : '');
$utm_medium = isset($body['utm_medium']) ? trim((string) $body['utm_medium']) : (isset($body['utmMedium']) ? trim((string) $body['utmMedium']) : '');
$utm_campaign = isset($body['utm_campaign']) ? trim((string) $body['utm_campaign']) : (isset($body['utmCampaign']) ? trim((string) $body['utmCampaign']) : '');
if (($utm_source === '' || $utm_medium === '' || $utm_campaign === '') && $page_url !== '' && function_exists('lc_embed_parse_utm_from_url')) {
    $parsed = lc_embed_parse_utm_from_url($page_url);
    if ($utm_source === '') {
        $utm_source = (string) ($parsed['utm_source'] ?? '');
    }
    if ($utm_medium === '') {
        $utm_medium = (string) ($parsed['utm_medium'] ?? '');
    }
    if ($utm_campaign === '') {
        $utm_campaign = (string) ($parsed['utm_campaign'] ?? '');
    }
}

$sub_id = isset($body['sub_id']) ? trim((string) $body['sub_id']) : '';
if ($sub_id === '' && $utm_campaign !== '') {
    $sub_id = $utm_campaign;
}

$payload = array(
    'name'         => isset($body['name']) ? (string) $body['name'] : '',
    'phone'        => isset($body['phone']) ? (string) $body['phone'] : '',
    'email'        => isset($body['email']) ? (string) $body['email'] : '',
    'region'       => isset($body['region']) ? (string) $body['region'] : '',
    'inquiry'      => $inquiry,
    'channel'      => $channel,
    'sub_id'       => $sub_id,
    'source'       => $source,
    'page_url'     => $page_url,
    'referer'      => $referer,
    'utm_source'   => $utm_source,
    'utm_medium'   => $utm_medium,
    'utm_campaign' => $utm_campaign,
);

if (!function_exists('lc_receive_campaign_matches_request')) {
    /**
     * 직접 유입 캠페인이 현재 독립도메인 또는 해당 캠페인의 랜딩에서 온 요청인지 확인.
     */
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
        // 상대경로(/merchant/…) 만 저장된 경우 parse_url PATH가 비지 않도록 보정
        if ($landing_url !== '' && strpos($landing_url, '://') === false && strpos($landing_url, '/') === 0) {
            $landing_host = '';
            $landing_path = $landing_url;
        } else {
            $landing_host = strtolower((string) parse_url($landing_url, PHP_URL_HOST));
            $landing_path = (string) parse_url($landing_url, PHP_URL_PATH);
        }
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $referer_host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        $referer_path = (string) parse_url($referer, PHP_URL_PATH);

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

// 1) 파트너 홍보 링크가 있으면 기존 흐름 (채널은 링크/요청값 유지)
if ($lk_code !== '') {
    $link = lc_link_get_with_campaign($lk_code);
    if (!$link || $link['lk_status'] !== 'active' || $link['cp_status'] !== LC_STATUS_ACTIVE) {
        lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
    }

    // 외부 위젯: 허용 도메인·위젯 키 검증
    $is_embed = ($source === (defined('LC_SOURCE_EMBED') ? LC_SOURCE_EMBED : 'embed'))
        || in_array(strtolower($channel), array('embed', 'wordpress', 'widget', 'external'), true);
    if ($is_embed) {
        $request_host = '';
        if ($page_url !== '' && function_exists('lc_embed_host_from_url')) {
            $request_host = lc_embed_host_from_url($page_url);
        }
        if ($request_host === '' && function_exists('lc_embed_request_host')) {
            $request_host = lc_embed_request_host();
        }
        if (function_exists('lc_embed_domain_allowed')
            && !lc_embed_domain_allowed((int) $link['pt_id'], $request_host !== '' ? $request_host : null)
        ) {
            lc_api_error('등록된 허용 도메인에서만 상담을 접수할 수 있습니다.', 'DOMAIN_NOT_ALLOWED', 403);
        }

        $widget_key = isset($body['widgetKey'])
            ? trim((string) $body['widgetKey'])
            : (isset($body['widget_key']) ? trim((string) $body['widget_key']) : '');
        if (function_exists('lc_embed_widget_key_allowed')
            && !lc_embed_widget_key_allowed((int) $link['pt_id'], $widget_key)
        ) {
            lc_api_error('위젯 키가 올바르지 않습니다. 설치 코드를 다시 복사해 주세요.', 'WIDGET_KEY_INVALID', 403);
        }

        if ($payload['channel'] === '') {
            $payload['channel'] = 'embed';
        }
        $payload['source'] = defined('LC_SOURCE_EMBED') ? LC_SOURCE_EMBED : 'embed';

        // IP 기준 rate limit (10분에 8회)
        if (function_exists('lc_embed_rate_limit_check')) {
            $rate = lc_embed_rate_limit_check($lk_code, 8, 600);
            if (empty($rate['ok'])) {
                lc_api_error($rate['message'] ?? '잠시 후 다시 시도해 주세요.', 'RATE_LIMITED', 429);
            }
        }
    }

    $result = lc_conversion_create_from_link($link, $payload);
    if (!$result['ok']) {
        $err_code = isset($result['code']) ? (string) $result['code'] : 'CREATE_FAILED';
        if ($err_code === 'DUPLICATE_RECENT') {
            lc_api_success(array(
                'message'   => $result['message'],
                'duplicate' => true,
            ));
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

// 2) 직접 유입 → 캠페인 매칭 후 유입경로 SEO
//    명시적 campaignId가 있으면 동일 호스트의 다른 캠페인(tracking host)보다 우선한다.
$campaign = null;
$public_host = function_exists('lc_request_public_host') ? lc_request_public_host() : '';
$campaign_ref = '';
foreach (array('campaignId', 'campaign_id', 'cid', 'campaign_code', 'cpCode', 'cp_code') as $key) {
    if (isset($body[$key]) && trim((string) $body[$key]) !== '') {
        $campaign_ref = trim((string) $body[$key]);
        break;
    }
}

if ($campaign_ref !== '' && lc_db_installed()) {
    $cp_table = lc_table('campaigns');
    if (ctype_digit($campaign_ref)) {
        $campaign_row = lc_sql_fetch(
            " SELECT * FROM `{$cp_table}` WHERE cp_id = '" . (int) $campaign_ref . "' LIMIT 1 "
        );
    } else {
        $campaign_row = lc_sql_fetch(
            " SELECT * FROM `{$cp_table}` WHERE cp_code = '" . lc_sql_escape($campaign_ref) . "' LIMIT 1 "
        );
    }

    if (!is_array($campaign_row)) {
        lc_api_error('광고상품(캠페인)을 찾을 수 없습니다: ' . $campaign_ref, 'CAMPAIGN_NOT_FOUND', 404);
    }

    $cp_status = (string) ($campaign_row['cp_status'] ?? '');
    if ($cp_status !== LC_STATUS_ACTIVE) {
        lc_api_error(
            '광고상품이 운영중(진행중) 상태가 아닙니다. 관리자에서 캠페인을 활성화해 주세요. (' . $campaign_ref . ')',
            'CAMPAIGN_PAUSED',
            400
        );
    }

    if (!lc_receive_campaign_matches_request($campaign_row, $public_host)) {
        lc_api_error(
            '랜딩 페이지와 광고상품 연결이 맞지 않습니다. 캠페인 랜딩 URL(/merchant/…)과 접속 경로를 확인해 주세요.',
            'LANDING_MISMATCH',
            400
        );
    }

    $campaign = $campaign_row;
}

if (!$campaign && $public_host !== '' && function_exists('lc_campaign_find_active_by_tracking_host')) {
    $campaign = lc_campaign_find_active_by_tracking_host($public_host);
}

if (!is_array($campaign)) {
    lc_api_error(
        '독립도메인 또는 홍보 링크가 필요합니다. 파트너 링크(/r/코드)로 접속하거나 광고상품 독립도메인으로 신청해 주세요.',
        'INVALID_LINK',
        400
    );
}

$result = lc_conversion_create_from_seo_campaign($campaign, $payload);
if (!$result['ok']) {
    $err_code = isset($result['code']) ? (string) $result['code'] : 'CREATE_FAILED';
    if ($err_code === 'DUPLICATE_RECENT') {
        lc_api_success(array(
            'message'   => $result['message'],
            'duplicate' => true,
        ));
    }
    lc_api_error($result['message'], $err_code, 400);
}

lc_api_success(array(
    'message'    => $result['message'],
    'code'       => is_array($result['conversion']) ? (string) $result['conversion']['cv_code'] : '',
    'conversion' => is_array($result['conversion']) ? lc_conversion_to_api_merchant(
        array_merge(
            $result['conversion'],
            array(
                'cp_name' => (string) ($campaign['cp_name'] ?? ''),
                'pt_code' => 'SEO',
            )
        ),
        false
    ) : null,
));
