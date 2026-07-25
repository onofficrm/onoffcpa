<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('POST');
$partner = lc_api_require_active_partner();
lc_ai_require_ready('promo');

$body = lc_api_read_json_body();
$action = isset($body['action']) ? (string) $body['action'] : 'promo';

if ($action !== 'promo') {
    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

$campaign_id = isset($body['campaignId']) ? (int) $body['campaignId'] : 0;
$payload = array(
    'title'             => (string) ($body['title'] ?? ''),
    'category'          => (string) ($body['category'] ?? ''),
    'price'             => (string) ($body['price'] ?? ''),
    'approvalRate'      => (string) ($body['approvalRate'] ?? ''),
    'allowedChannels'   => (string) ($body['allowedChannels'] ?? ''),
    'forbiddenChannels' => (string) ($body['forbiddenChannels'] ?? ''),
    'channel'           => (string) ($body['channel'] ?? 'all'),
    'eventTitle'        => (string) ($body['eventTitle'] ?? ''),
);

if ($campaign_id > 0 && function_exists('lc_campaign_get_by_id')) {
    $campaign = lc_campaign_get_by_id($campaign_id);
    if (is_array($campaign)) {
        if ($payload['title'] === '') {
            $payload['title'] = (string) ($campaign['cp_name'] ?? '');
        }
        if ($payload['category'] === '') {
            $payload['category'] = (string) ($campaign['cp_category'] ?? '');
        }
        if ($payload['price'] === '' && isset($campaign['cp_price'])) {
            $payload['price'] = number_format((int) $campaign['cp_price']) . '원';
        }
    }
}

$result = lc_ai_promo_generate($payload);
if (!$result['ok']) {
    lc_api_error($result['message'], 'AI_PROMO_FAILED', 400);
}

lc_api_success(array(
    'copies'   => $result['copies'],
    'fallback' => !empty($result['fallback']),
    'message'  => isset($result['message']) ? (string) $result['message'] : '',
));
