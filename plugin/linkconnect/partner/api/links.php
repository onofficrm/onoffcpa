<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $items = array_map('lc_link_to_api', lc_link_list_for_partner($pt_id));

    lc_api_success(array(
        'items' => $items,
        'total' => count($items),
    ));
}

if ($method === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? trim((string) $body['action']) : 'create';

    if ($action === 'shortlink' || $action === 'short_link' || $action === 'create_shortlink') {
        $lk_id = isset($body['linkId']) ? (int) $body['linkId'] : (isset($body['lk_id']) ? (int) $body['lk_id'] : 0);
        $cp_id = isset($body['campaignId']) ? (int) $body['campaignId'] : (isset($body['cp_id']) ? (int) $body['cp_id'] : 0);

        if ($lk_id <= 0 && function_exists('lc_link_get_by_code')) {
            $code = trim((string) ($body['code'] ?? $body['lkCode'] ?? $body['lk_code'] ?? ''));
            if ($code !== '') {
                $row = lc_link_get_by_code($code);
                if (is_array($row) && (int) ($row['pt_id'] ?? 0) === $pt_id) {
                    $lk_id = (int) ($row['lk_id'] ?? 0);
                }
            }
        }

        if ($lk_id <= 0 && $cp_id > 0 && function_exists('lc_cpa_partner_shortlink_for_campaign')) {
            $result = lc_cpa_partner_shortlink_for_campaign($pt_id, $cp_id);
            if (!$result['ok']) {
                lc_api_error($result['message'], 'SHORTLINK_INVALID', 400);
            }
            lc_api_success(array(
                'message'   => $result['message'],
                'shortUrl'  => $result['shortUrl'],
                'promoUrl'  => $result['promoUrl'],
                'shortCode' => $result['shortCode'],
                'link'      => $result['link'],
            ));
        }

        if (!function_exists('lc_cpa_partner_create_shortlink')) {
            lc_api_error('숏링크 기능을 사용할 수 없습니다.', 'NOT_AVAILABLE', 500);
        }
        $result = lc_cpa_partner_create_shortlink($pt_id, $lk_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SHORTLINK_INVALID', 400);
        }
        lc_api_success(array(
            'message'   => $result['message'],
            'shortUrl'  => $result['shortUrl'],
            'promoUrl'  => $result['promoUrl'],
            'shortCode' => $result['shortCode'],
        ));
    }

    // 기본: 링크 생성
    $cp_id = isset($body['campaignId']) ? (int) $body['campaignId'] : (isset($body['cp_id']) ? (int) $body['cp_id'] : 0);
    $channel = isset($body['channel']) ? trim((string) $body['channel']) : '';
    $sub_id = isset($body['subId']) ? trim((string) $body['subId']) : (isset($body['sub_id']) ? trim((string) $body['sub_id']) : '');

    if ($cp_id <= 0) {
        lc_api_error('캠페인 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
    }

    $result = lc_link_create($pt_id, $cp_id, $channel, $sub_id);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'CREATE_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'link'    => $result['link'],
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
