<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('POST');
lc_api_require_admin();

$body = lc_api_read_json_body();
$action = isset($body['action']) ? (string) $body['action'] : 'summary';

if ($action === 'summary') {
    lc_ai_require_ready('summary');

    $report = lc_ai_admin_summary_data();
    $result = lc_ai_report_summary($report, 'admin');
    if (!$result['ok']) {
        lc_api_error($result['message'], 'AI_SUMMARY_FAILED', 400);
    }

    lc_api_success(array(
        'summary'  => $result['summary'],
        'fallback' => !empty($result['fallback']),
        'report'   => array(
            'summary' => $report['summary'] ?? array(),
        ),
    ));
}

if ($action === 'generate_thumbnail') {
    lc_ai_require_ready('image');

    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : 0;
    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_ID', 400);
    }

    $campaign = function_exists('lc_campaign_get_by_id') ? lc_campaign_get_by_id($cp_id) : null;
    if (!is_array($campaign)) {
        lc_api_error('광고상품을 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    $opts = function_exists('lc_ai_normalize_image_options')
        ? lc_ai_normalize_image_options($body)
        : array(
            'mood'        => trim((string) ($body['mood'] ?? '')),
            'includeText' => !empty($body['includeText']),
            'overlayText' => trim((string) ($body['overlayText'] ?? '')),
            'extra'       => trim((string) ($body['extra'] ?? '')),
        );
    if (!empty($opts['includeText']) && $opts['overlayText'] === '') {
        lc_api_error('텍스트를 넣을 경우 문구를 입력해 주세요.', 'INVALID_TEXT', 400);
    }

    $generated = lc_ai_generate_campaign_image(array(
        'kind'        => 'thumbnail',
        'title'       => (string) ($campaign['cp_name'] ?? ''),
        'category'    => (string) ($campaign['cp_category'] ?? ''),
        'width'       => 1200,
        'height'      => 750,
        'mood'        => $opts['mood'],
        'includeText' => !empty($opts['includeText']),
        'overlayText' => $opts['overlayText'],
        'extra'       => $opts['extra'],
    ));
    if (empty($generated['ok'])) {
        lc_api_error(isset($generated['message']) ? (string) $generated['message'] : 'AI 이미지 생성 실패', 'AI_IMAGE_FAILED', 400);
    }

    $saved = lc_campaign_thumbnail_save_binary(
        $cp_id,
        $generated['binary'],
        isset($generated['mime']) ? (string) $generated['mime'] : 'image/jpeg',
        'ai-thumbnail.' . (isset($generated['ext']) ? (string) $generated['ext'] : 'jpg')
    );
    if (empty($saved['ok'])) {
        lc_api_error($saved['message'], 'THUMBNAIL_SAVE_FAILED', 400);
    }

    $fresh = lc_campaign_get_by_id($cp_id);
    lc_api_success(array(
        'message'      => 'AI 썸네일이 생성되어 등록되었습니다.',
        'thumbnailUrl' => $saved['thumbnailUrl'],
        'prompt'       => isset($generated['prompt']) ? (string) $generated['prompt'] : '',
        'campaign'     => is_array($fresh) && function_exists('lc_campaign_to_admin_api')
            ? lc_campaign_to_admin_api($fresh)
            : null,
    ));
}

if ($action === 'generate_promo_image') {
    lc_ai_require_ready('image');

    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : 0;
    $width = isset($body['width']) ? (int) $body['width'] : 0;
    $height = isset($body['height']) ? (int) $body['height'] : 0;
    $image_title = trim((string) ($body['imageTitle'] ?? ''));
    $opts = function_exists('lc_ai_normalize_image_options')
        ? lc_ai_normalize_image_options($body)
        : array(
            'mood'        => trim((string) ($body['mood'] ?? '')),
            'includeText' => !empty($body['includeText']),
            'overlayText' => trim((string) ($body['overlayText'] ?? '')),
            'extra'       => trim((string) ($body['extra'] ?? '')),
        );
    if (!empty($opts['includeText']) && $opts['overlayText'] === '') {
        lc_api_error('텍스트를 넣을 경우 문구를 입력해 주세요.', 'INVALID_TEXT', 400);
    }

    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_ID', 400);
    }

    $campaign = function_exists('lc_campaign_get_by_id') ? lc_campaign_get_by_id($cp_id) : null;
    if (!is_array($campaign)) {
        lc_api_error('광고상품을 찾을 수 없습니다.', 'NOT_FOUND', 404);
    }

    $mt_id = (int) ($campaign['mt_id'] ?? 0);
    if ($mt_id <= 0) {
        lc_api_error('광고주 정보를 확인할 수 없습니다.', 'INVALID_MERCHANT', 400);
    }

    $generated = lc_ai_generate_campaign_image(array(
        'kind'        => 'promo',
        'title'       => (string) ($campaign['cp_name'] ?? ''),
        'category'    => (string) ($campaign['cp_category'] ?? ''),
        'width'       => $width,
        'height'      => $height,
        'mood'        => $opts['mood'],
        'includeText' => !empty($opts['includeText']),
        'overlayText' => $opts['overlayText'],
        'extra'       => $opts['extra'],
    ));
    if (empty($generated['ok'])) {
        lc_api_error(isset($generated['message']) ? (string) $generated['message'] : 'AI 이미지 생성 실패', 'AI_IMAGE_FAILED', 400);
    }

    if ($image_title === '') {
        $image_title = $width > 0 && $height > 0
            ? 'AI 생성 이미지 (' . $width . ' × ' . $height . ' px)'
            : 'AI 생성 이미지';
    }

    $saved = lc_campaign_promo_guide_save_binary(
        $mt_id,
        $cp_id,
        $generated['binary'],
        isset($generated['mime']) ? (string) $generated['mime'] : 'image/jpeg',
        $image_title,
        'ai-promo.' . (isset($generated['ext']) ? (string) $generated['ext'] : 'jpg'),
        true
    );
    if (empty($saved['ok'])) {
        lc_api_error($saved['message'], 'PROMO_SAVE_FAILED', 400);
    }

    $asset_api = null;
    if (!empty($saved['asset']) && function_exists('lc_campaign_promo_guide_asset_to_api')) {
        $asset_api = lc_campaign_promo_guide_asset_to_api($saved['asset']);
    }

    lc_api_success(array(
        'message' => 'AI 이미지가 생성되어 등록되었습니다.',
        'asset'   => $asset_api,
        'prompt'  => isset($generated['prompt']) ? (string) $generated['prompt'] : '',
    ));
}

lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
