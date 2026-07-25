<?php
require_once dirname(__DIR__) . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_method('GET');
    lc_api_success(lc_ai_status_for_api());
}

if ($method === 'POST') {
    lc_api_require_method('POST');

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'chat';

    if ($action === 'chat') {
        lc_ai_require_ready('chat');

        $message = isset($body['message']) ? (string) $body['message'] : '';
        $history = isset($body['history']) && is_array($body['history']) ? $body['history'] : array();
        $context = isset($body['context']) && is_array($body['context']) ? $body['context'] : array();

        $result = lc_ai_chat($message, $history, $context);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'AI_CHAT_FAILED', 400);
        }

        lc_api_success(array(
            'reply'    => $result['reply'],
            'fallback' => !empty($result['fallback']),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
