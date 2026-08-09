<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
lc_api_require_admin();

if ($method === 'GET') {
    $pt_id = isset($_GET['ptId']) ? (int) $_GET['ptId'] : (isset($_GET['pt_id']) ? (int) $_GET['pt_id'] : 0);
    if ($pt_id > 0) {
        $detail = function_exists('lc_admin_embed_partner_detail_for_api')
            ? lc_admin_embed_partner_detail_for_api($pt_id)
            : null;
        if (!$detail) {
            lc_api_error('파트너를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success($detail);
    }

    $filters = array(
        'q'     => isset($_GET['q']) ? (string) $_GET['q'] : '',
        'scope' => isset($_GET['scope']) ? (string) $_GET['scope'] : '',
    );
    $data = function_exists('lc_admin_embed_partners_for_api')
        ? lc_admin_embed_partners_for_api($filters)
        : array('items' => array(), 'summary' => array());

    lc_api_success(array(
        'items'   => $data['items'] ?? array(),
        'summary' => $data['summary'] ?? array(),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    if (!lc_db_installed()) {
        lc_api_error('DB가 설치되지 않았습니다.', 'DB_NOT_READY', 400);
    }

    $body = lc_api_read_json_body();
    if (!$body && $_POST) {
        $body = $_POST;
    }
    $action = isset($body['action']) ? (string) $body['action'] : 'save_domains';
    $pt_id = isset($body['ptId']) ? (int) $body['ptId'] : (isset($body['pt_id']) ? (int) $body['pt_id'] : 0);
    if ($pt_id <= 0) {
        lc_api_error('파트너 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    $partner = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($pt_id) : null;
    if (!is_array($partner)) {
        lc_api_error('파트너를 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    if ($action === 'save_domains') {
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
            'partner'    => function_exists('lc_admin_embed_partner_detail_for_api')
                ? lc_admin_embed_partner_detail_for_api($pt_id)
                : null,
        ));
    }

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
            'message'    => $result['message'] ?? '위젯 키를 저장했습니다.',
            'widgetKey'  => $result['widgetKey'] ?? '',
            'partner'    => function_exists('lc_admin_embed_partner_detail_for_api')
                ? lc_admin_embed_partner_detail_for_api($pt_id)
                : null,
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
        lc_api_success(array(
            'message' => $result['message'],
            'options' => $result['options'],
            'partner' => function_exists('lc_admin_embed_partner_detail_for_api')
                ? lc_admin_embed_partner_detail_for_api($pt_id)
                : null,
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
