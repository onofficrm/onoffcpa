<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
$partner = lc_api_require_active_partner();
$pt_id = (int) $partner['pt_id'];

if ($method === 'GET') {
    $lk_code = '';
    foreach (array('lkCode', 'lk_code', 'code') as $key) {
        if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
            $lk_code = trim((string) $_GET[$key]);
            break;
        }
    }

    $domains = function_exists('lc_embed_partner_allowed_domains')
        ? lc_embed_partner_allowed_domains($pt_id)
        : array();

    $stats = function_exists('lc_embed_stats_for_partner')
        ? lc_embed_stats_for_partner($pt_id, 14)
        : array(
            'embedTotal'    => 0,
            'embedToday'    => 0,
            'embedApproved' => 0,
            'days'          => 14,
            'byDomain'      => array(),
            'daily'         => array(),
        );

    $widget_key = function_exists('lc_embed_partner_widget_key')
        ? lc_embed_partner_widget_key($pt_id)
        : '';

    $options = function_exists('lc_embed_partner_options')
        ? lc_embed_partner_options($pt_id)
        : array();

    $payload = array(
        'domains'           => $domains,
        'domainLock'        => count($domains) > 0,
        'widgetKey'         => $widget_key,
        'hasWidgetKey'      => $widget_key !== '',
        'hasCustomOptions'  => function_exists('lc_embed_partner_has_custom_options')
            ? lc_embed_partner_has_custom_options($pt_id)
            : false,
        'options'           => $options,
        'scriptUrl'         => function_exists('lc_embed_script_url') ? lc_embed_script_url() : '',
        'brandName'         => function_exists('lc_embed_brand_name') ? lc_embed_brand_name() : '',
        'embedTotal'        => (int) ($stats['embedTotal'] ?? 0),
        'embedToday'        => (int) ($stats['embedToday'] ?? 0),
        'embedApproved'     => (int) ($stats['embedApproved'] ?? 0),
        'statsDays'         => (int) ($stats['days'] ?? 14),
        'byDomain'          => isset($stats['byDomain']) && is_array($stats['byDomain']) ? $stats['byDomain'] : array(),
        'daily'             => isset($stats['daily']) && is_array($stats['daily']) ? $stats['daily'] : array(),
    );

    if ($lk_code !== '') {
        $link = function_exists('lc_link_get_with_campaign') ? lc_link_get_with_campaign($lk_code) : null;
        if (!$link || (int) ($link['pt_id'] ?? 0) !== $pt_id) {
            lc_api_error('본인 홍보 링크만 조회할 수 있습니다.', 'FORBIDDEN', 403);
        }
        $config = function_exists('lc_embed_config_for_lk_code')
            ? lc_embed_config_for_lk_code($lk_code, array(
                'check_domain'     => false,
                'check_widget_key' => false,
            ))
            : null;
        if (!is_array($config) || isset($config['_error'])) {
            lc_api_error('유효하지 않은 홍보 링크입니다.', 'INVALID_LINK', 404);
        }
        $payload['config'] = $config;
        $payload['snippet'] = function_exists('lc_embed_snippet_html')
            ? lc_embed_snippet_html($lk_code, 'form', array('widgetKey' => $widget_key))
            : '';
    }

    lc_api_success($payload);
}

if ($method === 'POST') {
    $body = lc_api_read_json_body();
    if (!$body && $_POST) {
        $body = $_POST;
    }
    $action = isset($body['action']) ? (string) $body['action'] : 'save_domains';

    if ($action === 'issue_widget_key' || $action === 'rotate_widget_key') {
        $result = null;
        if ($action === 'issue_widget_key' && function_exists('lc_embed_ensure_partner_widget_key')) {
            $existing = function_exists('lc_embed_partner_widget_key') ? lc_embed_partner_widget_key($pt_id) : '';
            if ($existing !== '') {
                $result = array('ok' => true, 'message' => '이미 발급된 위젯 키가 있습니다.', 'widgetKey' => $existing);
            } else {
                $result = lc_embed_ensure_partner_widget_key($pt_id);
            }
        } elseif (function_exists('lc_embed_rotate_partner_widget_key')) {
            $result = lc_embed_rotate_partner_widget_key($pt_id);
        }
        if (!is_array($result) || empty($result['ok'])) {
            lc_api_error(
                is_array($result) ? ($result['message'] ?? '위젯 키 처리에 실패했습니다.') : '기능을 사용할 수 없습니다.',
                'WIDGET_KEY_FAILED',
                400
            );
        }
        lc_api_success(array(
            'message'      => $result['message'] ?? '위젯 키를 저장했습니다.',
            'widgetKey'    => $result['widgetKey'] ?? '',
            'hasWidgetKey' => !empty($result['widgetKey']),
        ));
    }

    if ($action === 'save_options') {
        $options = isset($body['options']) && is_array($body['options']) ? $body['options'] : $body;
        $result = function_exists('lc_embed_set_partner_options')
            ? lc_embed_set_partner_options($pt_id, $options)
            : array('ok' => false, 'message' => '기능을 사용할 수 없습니다.', 'options' => array());
        if (empty($result['ok'])) {
            lc_api_error($result['message'] ?? '저장에 실패했습니다.', 'SAVE_FAILED', 400);
        }
        $widget_key = function_exists('lc_embed_partner_widget_key')
            ? lc_embed_partner_widget_key($pt_id)
            : '';
        lc_api_success(array(
            'message'      => $result['message'],
            'options'      => $result['options'],
            'widgetKey'    => $widget_key,
            'hasWidgetKey' => $widget_key !== '',
        ));
    }

    $domains = isset($body['domains']) ? $body['domains'] : (isset($body['allowedDomains']) ? $body['allowedDomains'] : array());
    $result = function_exists('lc_embed_set_partner_allowed_domains')
        ? lc_embed_set_partner_allowed_domains($pt_id, $domains)
        : array('ok' => false, 'message' => '기능을 사용할 수 없습니다.', 'domains' => array());

    if (empty($result['ok'])) {
        lc_api_error($result['message'] ?? '저장에 실패했습니다.', 'SAVE_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'domains'    => $result['domains'],
        'domainLock' => count($result['domains']) > 0,
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
