<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if (!function_exists('lc_merchant_notification_prefs_payload')) {
    /**
     * @return array<string,mixed>
     */
    function lc_merchant_notification_prefs_payload($mt_id)
    {
        $mt_id = (int) $mt_id;
        $recipient = lc_email_notify_resolve_recipient('merchant', $mt_id);
        $system = lc_email_notify_system_status();
        $issues = $system['issues'];
        if ($recipient['email'] === '') {
            $issues[] = '광고주 계정에 수신 이메일이 없습니다. 회원정보 이메일을 확인하세요.';
        }

        return array(
            'prefs'     => lc_email_notify_get_prefs('merchant', $mt_id),
            'meta'      => lc_email_notify_prefs_meta('merchant'),
            'recipient' => array(
                'email' => $recipient['email'],
                'name'  => $recipient['name'],
            ),
            'system'    => array(
                'ready'          => !empty($system['ready']) && $recipient['email'] !== '',
                'mailer'         => !empty($system['mailer']),
                'emailUse'       => !empty($system['emailUse']),
                'fromConfigured' => !empty($system['fromConfigured']),
                'fromEmail'      => (string) ($system['fromEmail'] ?? ''),
                'issues'         => array_values($issues),
            ),
            'dbReady'   => lc_db_installed(),
        );
    }
}

if ($method === 'GET') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    lc_api_success(lc_merchant_notification_prefs_payload($mt_id));
}

if ($method === 'POST') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? trim((string) $body['action']) : 'save';

    if ($action === 'test') {
        $recipient = lc_email_notify_resolve_recipient('merchant', $mt_id);
        if ($recipient['email'] === '') {
            lc_api_error('수신 이메일이 없습니다. 회원정보 이메일을 먼저 등록해 주세요.', 'NO_EMAIL', 400);
        }
        $system = lc_email_notify_system_status();
        if (empty($system['ready'])) {
            $msg = !empty($system['issues']) ? implode(' ', $system['issues']) : '메일 발송 환경이 준비되지 않았습니다.';
            lc_api_error($msg, 'MAILER_NOT_READY', 400);
        }

        $site = function_exists('lc_site_name') ? lc_site_name() : 'OnOff CPA';
        $base = defined('G5_URL') ? G5_URL : '';
        $rows = lc_email_notify_row('광고주', $recipient['name'])
            . lc_email_notify_row('수신', $recipient['email'])
            . lc_email_notify_row('시각', date('Y-m-d H:i:s'));
        $sent = lc_email_notify_send(
            $recipient['email'],
            '[' . $site . '] 신규 DB 알림 테스트',
            lc_email_notify_wrap('테스트 메일입니다. 실제 신규 DB 알림도 이 주소로 발송됩니다.', $rows, '광고주센터 열기', $base . '/advertiser/settings')
        );
        if (!$sent) {
            lc_api_error('테스트 메일 발송에 실패했습니다. 관리자에게 메일 설정을 문의해 주세요.', 'SEND_FAILED', 500);
        }

        $payload = lc_merchant_notification_prefs_payload($mt_id);
        $payload['message'] = '테스트 메일을 ' . $recipient['email'] . ' 로 발송했습니다.';
        lc_api_success($payload);
    }

    $prefs = isset($body['prefs']) && is_array($body['prefs']) ? $body['prefs'] : $body;
    unset($prefs['action']);
    $result = lc_email_notify_save_prefs('merchant', $mt_id, is_array($prefs) ? $prefs : array());
    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }
    $payload = lc_merchant_notification_prefs_payload($mt_id);
    $payload['message'] = $result['message'];
    $payload['prefs'] = $result['prefs'];
    lc_api_success($payload);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
