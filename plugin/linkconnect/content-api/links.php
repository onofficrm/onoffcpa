<?php
/**
 * GET/POST /plugin/linkconnect/content-api/links.php
 *
 * GET  scope: content.links.read   → lc_link_list_for_partner
 * POST scope: content.links.create → lc_link_create (no domain rewrite)
 *
 * Body (create): { campaignId, channel?, subId }
 * channel default: content
 * subId: content:{id} | content:draft:{uuid}  (max 100 chars)
 */
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $auth = lc_content_s2s_require('content.links.read');
    $pt_id = (int) $auth['pt_id'];

    $override = lc_content_s2s_reject_client_pt_override($_GET, $pt_id);
    if (empty($override['ok'])) {
        lc_api_error($override['message'], $override['code'], 403);
    }

    $items = array_map('lc_content_s2s_link_response', array_map('lc_link_to_api', lc_link_list_for_partner($pt_id)));

    lc_api_success(array(
        'items'     => $items,
        'total'     => count($items),
        'partnerId' => $pt_id,
    ));
}

if ($method === 'POST') {
    $auth = lc_content_s2s_require('content.links.create');
    $pt_id = (int) $auth['pt_id'];
    $raw = (string) $auth['raw_body'];
    $body = $raw !== '' ? json_decode($raw, true) : array();
    if (!is_array($body)) {
        $body = array();
    }

    $override = lc_content_s2s_reject_client_pt_override($body, $pt_id);
    if (empty($override['ok'])) {
        lc_api_error($override['message'], $override['code'], 403);
    }

    // Deny non-create write actions (shortlink ok as optional convenience; still ownership-bound).
    $action = isset($body['action']) ? trim((string) $body['action']) : 'create';
    if ($action !== '' && $action !== 'create') {
        lc_api_error('Only link create is allowed on Content S2S bridge.', 'WRITE_FORBIDDEN', 403);
    }

    $cp_id = isset($body['campaignId']) ? (int) $body['campaignId'] : (isset($body['cp_id']) ? (int) $body['cp_id'] : 0);
    $channel = lc_content_s2s_normalize_channel(isset($body['channel']) ? $body['channel'] : '');
    $sub_raw = isset($body['subId']) ? $body['subId'] : (isset($body['sub_id']) ? $body['sub_id'] : '');
    $sub = lc_content_s2s_normalize_sub_id($sub_raw);
    if (empty($sub['ok'])) {
        lc_api_error($sub['message'], $sub['code'], 400);
    }

    if ($cp_id <= 0) {
        lc_api_error('campaignId is required.', 'INVALID_CAMPAIGN', 400);
    }

    $result = lc_link_create($pt_id, $cp_id, $channel, $sub['subId']);
    if (empty($result['ok'])) {
        lc_api_error((string) ($result['message'] ?? 'Link create failed.'), 'CREATE_FAILED', 400);
    }

    $link = is_array($result['link'] ?? null) ? lc_content_s2s_link_response($result['link']) : null;

    lc_api_success(array(
        'message'   => (string) ($result['message'] ?? ''),
        'link'      => $link,
        'partnerId' => $pt_id,
    ));
}

lc_api_error('Method not allowed.', 'METHOD_NOT_ALLOWED', 405);
