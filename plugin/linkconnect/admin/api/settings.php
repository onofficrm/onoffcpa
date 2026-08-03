<?php
require_once __DIR__ . '/_common.php';

lc_api_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = lc_settings_get_all();

    lc_api_success(array(
        'settings' => lc_settings_to_api($settings),
        'raw'      => lc_settings_raw_for_admin($settings),
        'dbReady'  => lc_db_installed(),
    ));
}

if (!function_exists('lc_settings_api_success_payload')) {
    function lc_settings_api_success_payload($message)
    {
        $settings = lc_settings_get_all();

        return array(
            'message'  => $message,
            'settings' => lc_settings_to_api($settings),
            'raw'      => lc_settings_raw_for_admin($settings),
        );
    }
}

if (!function_exists('lc_settings_normalize_secret_value')) {
    function lc_settings_normalize_secret_value($value)
    {
        $key_val = trim((string) $value);
        if ($key_val === '' || strpos($key_val, '*') !== false) {
            return '';
        }

        return $key_val;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = lc_api_read_json_body();
    $values = isset($body['settings']) && is_array($body['settings']) ? $body['settings'] : $body;

    if (isset($body['action']) && $body['action'] === 'reset') {
        $table = lc_table('settings');
        if (lc_db_table_exists($table)) {
            lc_sql_query(" DELETE FROM `{$table}` ", false);
        }
        lc_api_success(lc_settings_api_success_payload('기본값으로 복원되었습니다.'));
    }

    if (isset($body['action']) && $body['action'] === 'save_gemini_key') {
        $gemini_key = lc_settings_normalize_secret_value($body['geminiApiKey'] ?? '');
        if ($gemini_key === '') {
            lc_api_error('유효한 Gemini API 키를 입력하세요. 마스킹된 값(****)은 다시 입력할 수 없습니다.', 'GEMINI_KEY_INVALID', 400);
        }

        $result = lc_settings_save(array('geminiApiKey' => $gemini_key));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SETTINGS_SAVE_FAILED', 400);
        }

        $saved_key = trim((string) lc_settings_get('geminiApiKey', ''));
        if ($saved_key === '') {
            lc_api_error('API 키 저장에 실패했습니다. DB 연결 및 settings 테이블 상태를 확인하세요.', 'GEMINI_KEY_NOT_PERSISTED', 500);
        }

        lc_api_success(lc_settings_api_success_payload('Gemini API 키가 저장되었습니다.'));
    }

    if (isset($body['action']) && $body['action'] === 'save_openai_key') {
        $openai_key = lc_settings_normalize_secret_value($body['openaiApiKey'] ?? '');
        if ($openai_key === '') {
            lc_api_error('유효한 OpenAI API 키를 입력하세요. 마스킹된 값(****)은 다시 입력할 수 없습니다.', 'OPENAI_KEY_INVALID', 400);
        }

        $result = lc_settings_save(array('openaiApiKey' => $openai_key));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SETTINGS_SAVE_FAILED', 400);
        }

        $saved_key = trim((string) lc_settings_get('openaiApiKey', ''));
        if ($saved_key === '') {
            lc_api_error('API 키 저장에 실패했습니다. DB 연결 및 settings 테이블 상태를 확인하세요.', 'OPENAI_KEY_NOT_PERSISTED', 500);
        }

        lc_api_success(lc_settings_api_success_payload('OpenAI API 키가 저장되었습니다.'));
    }

    if (isset($body['action']) && $body['action'] === 'test_email') {
        if (!function_exists('lc_board_mail_send_test')) {
            lc_api_error('메일 테스트 기능을 사용할 수 없습니다.', 'MAIL_TEST_UNAVAILABLE', 500);
        }
        $to = isset($body['to']) ? trim((string) $body['to']) : '';
        $test = lc_board_mail_send_test($to);
        if (empty($test['ok'])) {
            lc_api_error(
                isset($test['message']) ? (string) $test['message'] : '테스트 메일 발송에 실패했습니다.',
                'MAIL_TEST_FAILED',
                400
            );
        }
        $payload = lc_settings_api_success_payload($test['message']);
        $payload['test'] = $test;
        lc_api_success($payload);
    }

    $flat = array();
    if (isset($values['general']) && is_array($values['general'])) {
        $flat = array_merge($flat, $values['general']);
    }
    if (isset($values['cpa']) && is_array($values['cpa'])) {
        foreach ($values['cpa'] as $key => $value) {
            if (is_bool($value)) {
                $flat[$key] = $value ? '1' : '0';
            } else {
                $flat[$key] = $value;
            }
        }
    }
    if (isset($values['billing']) && is_array($values['billing'])) {
        $flat = array_merge($flat, $values['billing']);
    }
    if (isset($values['partner']) && is_array($values['partner'])) {
        foreach ($values['partner'] as $key => $value) {
            $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : $value;
        }
    }
    if (isset($values['cancel']) && is_array($values['cancel'])) {
        foreach ($values['cancel'] as $key => $value) {
            $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : $value;
        }
    }
    if (isset($values['api']) && is_array($values['api'])) {
        foreach ($values['api'] as $key => $value) {
            $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : $value;
        }
    }
    if (isset($values['ai']) && is_array($values['ai'])) {
        foreach ($values['ai'] as $key => $value) {
            if ($key === 'geminiApiKey' || $key === 'openaiApiKey') {
                $key_val = lc_settings_normalize_secret_value($value);
                if ($key_val !== '') {
                    $flat[$key] = $key_val;
                }
                continue;
            }
            if (
                $key === 'geminiApiKeySet' || $key === 'geminiApiKeyMasked'
                || $key === 'openaiApiKeySet' || $key === 'openaiApiKeyMasked'
            ) {
                continue;
            }
            $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : $value;
        }
    }

    if (isset($values['call']) && is_array($values['call'])) {
        foreach ($values['call'] as $key => $value) {
            if (in_array($key, array('callApiKey', 'callApiSecret', 'callWebhookToken'), true)) {
                $key_val = trim((string) $value);
                if ($key_val !== '' && strpos($key_val, '*') === false) {
                    $flat[$key] = $key_val;
                }
                continue;
            }
            if (in_array($key, array('callApiKeySet', 'callApiSecretSet', 'callWebhookTokenSet'), true)) {
                continue;
            }
            $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : $value;
        }
    }

    $mail_values = array();
    if (isset($values['mail']) && is_array($values['mail'])) {
        $mail_values = $values['mail'];
    }
    foreach (array('mailEmailUse', 'mailFromEmail', 'mailFromName', 'emailUse', 'fromEmail', 'fromName') as $mail_key) {
        if (array_key_exists($mail_key, $body) && !is_array($body[$mail_key])) {
            $mail_values[$mail_key] = $body[$mail_key];
        }
        if (array_key_exists($mail_key, $values) && !is_array($values[$mail_key])) {
            $mail_values[$mail_key] = $values[$mail_key];
        }
    }

    $secret_keys = function_exists('lc_settings_secret_keys') ? lc_settings_secret_keys() : array('geminiApiKey');
    foreach ($body as $key => $value) {
        if (!is_string($key) || !array_key_exists($key, lc_settings_defaults())) {
            continue;
        }
        if (is_array($value) || is_object($value)) {
            continue;
        }
        if (in_array($key, $secret_keys, true)) {
            $key_val = lc_settings_normalize_secret_value($value);
            if ($key_val === '') {
                continue;
            }
            $flat[$key] = $key_val;
            continue;
        }
        $flat[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    $requested_gemini_key = isset($flat['geminiApiKey']) ? (string) $flat['geminiApiKey'] : '';
    $requested_openai_key = isset($flat['openaiApiKey']) ? (string) $flat['openaiApiKey'] : '';

    $result = lc_settings_save($flat);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'SETTINGS_SAVE_FAILED', 400);
    }

    if ($mail_values) {
        if (!function_exists('lc_board_mail_settings_save')) {
            lc_api_error('메일 설정을 저장할 수 없습니다.', 'MAIL_SAVE_UNAVAILABLE', 500);
        }
        $mail_result = lc_board_mail_settings_save($mail_values);
        if (empty($mail_result['ok'])) {
            lc_api_error(
                isset($mail_result['message']) ? (string) $mail_result['message'] : '메일 설정 저장에 실패했습니다.',
                'MAIL_SETTINGS_SAVE_FAILED',
                400
            );
        }
    }

    if ($requested_gemini_key !== '') {
        $saved_key = trim((string) lc_settings_get('geminiApiKey', ''));
        if ($saved_key === '') {
            lc_api_error('Gemini API 키 저장에 실패했습니다. DB 연결 및 settings 테이블 상태를 확인하세요.', 'GEMINI_KEY_NOT_PERSISTED', 500);
        }
    }
    if ($requested_openai_key !== '') {
        $saved_key = trim((string) lc_settings_get('openaiApiKey', ''));
        if ($saved_key === '') {
            lc_api_error('OpenAI API 키 저장에 실패했습니다. DB 연결 및 settings 테이블 상태를 확인하세요.', 'OPENAI_KEY_NOT_PERSISTED', 500);
        }
    }

    lc_api_success(lc_settings_api_success_payload($result['message']));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
