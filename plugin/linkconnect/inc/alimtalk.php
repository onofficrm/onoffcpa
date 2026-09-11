<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * Solapi 카카오 알림톡 (DB 유입 등).
 * 관리자 설정: alimtalkEnabled + solapiApiKey/Secret + alimtalkPfId + templateId
 */

if (!function_exists('lc_alimtalk_enabled')) {
    function lc_alimtalk_enabled()
    {
        if (!function_exists('lc_settings_get_bool') || !lc_settings_get_bool('alimtalkEnabled')) {
            return false;
        }
        $key = trim((string) lc_settings_get('solapiApiKey', ''));
        $secret = trim((string) lc_settings_get('solapiApiSecret', ''));
        $pf = trim((string) lc_settings_get('alimtalkPfId', ''));
        $tpl = trim((string) lc_settings_get('alimtalkDbTemplateId', ''));
        return $key !== '' && $secret !== '' && $pf !== '' && $tpl !== '';
    }
}

if (!function_exists('lc_alimtalk_normalize_phone')) {
    function lc_alimtalk_normalize_phone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === null || $digits === '') {
            return '';
        }
        if (strpos($digits, '82') === 0 && strlen($digits) >= 11) {
            $digits = '0' . substr($digits, 2);
        }
        if (strlen($digits) < 10 || strlen($digits) > 11) {
            return '';
        }
        if ($digits[0] !== '0') {
            return '';
        }
        return $digits;
    }
}

if (!function_exists('lc_alimtalk_mask_name')) {
    /** 예: 이분*, 서미* */
    function lc_alimtalk_mask_name($name)
    {
        $name = trim(preg_replace('/\s+/u', '', (string) $name));
        if ($name === '') {
            return '고객*';
        }
        $len = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($len <= 1) {
            return $name . '*';
        }
        if ($len === 2) {
            $first = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
            return $first . '*';
        }
        $head = function_exists('mb_substr') ? mb_substr($name, 0, 2, 'UTF-8') : substr($name, 0, 2);
        return $head . '*';
    }
}

if (!function_exists('lc_alimtalk_member_phone')) {
    function lc_alimtalk_member_phone($mb_id)
    {
        global $g5;
        $mb_id = trim((string) $mb_id);
        if ($mb_id === '' || empty($g5['member_table'])) {
            return '';
        }
        $row = lc_sql_fetch(" SELECT mb_hp, mb_tel FROM `{$g5['member_table']}` WHERE mb_id = '" . lc_sql_escape($mb_id) . "' LIMIT 1 ");
        if (!is_array($row)) {
            return '';
        }
        $phone = lc_alimtalk_normalize_phone($row['mb_hp'] ?? '');
        if ($phone === '') {
            $phone = lc_alimtalk_normalize_phone($row['mb_tel'] ?? '');
        }
        return $phone;
    }
}

if (!function_exists('lc_alimtalk_resolve_center_phone')) {
    /**
     * @return array{phone:string,name:string}
     */
    function lc_alimtalk_resolve_center_phone($center, $user_id)
    {
        $user_id = (int) $user_id;
        if ($center === 'admin') {
            $raw = trim((string) lc_settings_get('alimtalkAdminPhones', ''));
            $parts = preg_split('/[,;\s]+/', $raw) ?: array();
            $phones = array();
            foreach ($parts as $p) {
                $n = lc_alimtalk_normalize_phone($p);
                if ($n !== '') {
                    $phones[] = $n;
                }
            }
            return array(
                'phones' => array_values(array_unique($phones)),
                'name'   => '최고관리자',
            );
        }

        if ($user_id <= 0) {
            return array('phones' => array(), 'name' => '');
        }

        if ($center === 'merchant') {
            $row = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($user_id) : null;
            if (!is_array($row)) {
                return array('phones' => array(), 'name' => '');
            }
            $phone = lc_alimtalk_member_phone((string) ($row['mb_id'] ?? ''));
            return array(
                'phones' => $phone !== '' ? array($phone) : array(),
                'name'   => (string) ($row['mt_company'] ?? $row['mt_code'] ?? '광고주'),
            );
        }

        $row = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($user_id) : null;
        if (!is_array($row)) {
            return array('phones' => array(), 'name' => '');
        }
        $phone = lc_alimtalk_member_phone((string) ($row['mb_id'] ?? ''));
        return array(
            'phones' => $phone !== '' ? array($phone) : array(),
            'name'   => (string) ($row['pt_name'] ?? $row['pt_code'] ?? '파트너'),
        );
    }
}

if (!function_exists('lc_alimtalk_auth_header')) {
    function lc_alimtalk_auth_header($api_key, $api_secret)
    {
        $date = gmdate('Y-m-d\TH:i:s\Z');
        $salt = bin2hex(random_bytes(16));
        $signature = hash_hmac('sha256', $date . $salt, $api_secret);
        return 'HMAC-SHA256 apiKey=' . $api_key . ', date=' . $date . ', salt=' . $salt . ', signature=' . $signature;
    }
}

if (!function_exists('lc_alimtalk_send_one')) {
    /**
     * @param array<string,string> $variables keys must include #{} like "#{이름}"
     * @return array{ok:bool,message:string,response?:mixed}
     */
    function lc_alimtalk_send_one($to, array $variables, $template_id = '')
    {
        $to = lc_alimtalk_normalize_phone($to);
        if ($to === '') {
            return array('ok' => false, 'message' => '수신번호가 없습니다.');
        }

        $api_key = trim((string) lc_settings_get('solapiApiKey', ''));
        $api_secret = trim((string) lc_settings_get('solapiApiSecret', ''));
        $pf_id = trim((string) lc_settings_get('alimtalkPfId', ''));
        if ($template_id === '') {
            $template_id = trim((string) lc_settings_get('alimtalkDbTemplateId', ''));
        }
        if ($api_key === '' || $api_secret === '' || $pf_id === '' || $template_id === '') {
            return array('ok' => false, 'message' => '알림톡 API 설정이 완료되지 않았습니다.');
        }

        $kakao = array(
            'pfId'       => $pf_id,
            'templateId' => $template_id,
            'variables'  => $variables,
            'disableSms' => lc_settings_get_bool('alimtalkDisableSms', true),
        );

        $message = array(
            'to'           => $to,
            'kakaoOptions' => $kakao,
        );

        $from = lc_alimtalk_normalize_phone(lc_settings_get('alimtalkSmsFrom', ''));
        if ($from !== '' && empty($kakao['disableSms'])) {
            $message['from'] = $from;
        }

        $payload = array('messages' => array($message));
        $auth = lc_alimtalk_auth_header($api_key, $api_secret);

        $ch = curl_init('https://api.solapi.com/messages/v4/send-many/detail');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(
                'Authorization: ' . $auth,
                'Content-Type: application/json',
            ),
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
        ));
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array('ok' => false, 'message' => '알림톡 연결 실패: ' . $err);
        }

        $decoded = json_decode((string) $raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($decoded) ? (string) ($decoded['errorMessage'] ?? $decoded['message'] ?? $raw) : (string) $raw;
            return array('ok' => false, 'message' => '알림톡 발송 실패(' . $code . '): ' . $msg, 'response' => $decoded);
        }

        return array('ok' => true, 'message' => '발송 요청 완료', 'response' => $decoded);
    }
}

if (!function_exists('lc_alimtalk_build_db_variables')) {
    /**
     * @return array<string,string>
     */
    function lc_alimtalk_build_db_variables(array $conversion, $role_label = '')
    {
        $name_key = trim((string) lc_settings_get('alimtalkVarName', '#{이름}'));
        $campaign_key = trim((string) lc_settings_get('alimtalkVarCampaign', '#{캠페인}'));
        $site_key = trim((string) lc_settings_get('alimtalkVarSite', '#{사이트}'));
        $role_key = trim((string) lc_settings_get('alimtalkVarRole', '#{역할}'));

        if ($name_key === '') {
            $name_key = '#{이름}';
        }
        if ($campaign_key === '') {
            $campaign_key = '#{캠페인}';
        }
        if ($site_key === '') {
            $site_key = '#{사이트}';
        }

        $raw_name = (string) ($conversion['cv_name'] ?? $conversion['name'] ?? '');
        $campaign = (string) ($conversion['cp_name'] ?? $conversion['campaign'] ?? '캠페인');
        $site = function_exists('lc_settings_get') ? (string) lc_settings_get('siteName', 'CPA') : 'CPA';

        $vars = array(
            $name_key     => lc_alimtalk_mask_name($raw_name),
            $campaign_key => $campaign !== '' ? $campaign : '캠페인',
            $site_key     => $site !== '' ? $site : 'CPA',
        );
        if ($role_key !== '' && $role_label !== '') {
            $vars[$role_key] = $role_label;
        }

        return $vars;
    }
}

if (!function_exists('lc_alimtalk_log')) {
    function lc_alimtalk_log($action, $center, $user_id, $message, $ok = true)
    {
        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write(
                $ok ? 'alimtalk_ok' : 'alimtalk_fail',
                (string) $center,
                (int) $user_id,
                '[' . $action . '] ' . $message
            );
        }
    }
}

if (!function_exists('lc_alimtalk_notify_conversion_received')) {
    /**
     * DB 신규 접수 → 광고주 / 파트너 / 최고관리자 알림톡
     */
    function lc_alimtalk_notify_conversion_received(array $conversion)
    {
        if (!lc_alimtalk_enabled()) {
            return array('ok' => false, 'skipped' => true, 'message' => '알림톡 비활성 또는 미설정');
        }

        $mt_id = (int) ($conversion['mt_id'] ?? 0);
        $pt_id = (int) ($conversion['pt_id'] ?? 0);
        $results = array();

        $targets = array();
        if ($mt_id > 0 && lc_settings_get_bool('alimtalkNotifyMerchant', true)) {
            $targets[] = array('center' => 'merchant', 'userId' => $mt_id, 'role' => '광고주');
        }
        if ($pt_id > 0 && lc_settings_get_bool('alimtalkNotifyPartner', true)) {
            $targets[] = array('center' => 'partner', 'userId' => $pt_id, 'role' => '파트너');
        }
        if (lc_settings_get_bool('alimtalkNotifyAdmin', true)) {
            $targets[] = array('center' => 'admin', 'userId' => 0, 'role' => '관리자');
        }

        $sent = 0;
        $failed = 0;
        foreach ($targets as $target) {
            $resolved = lc_alimtalk_resolve_center_phone($target['center'], $target['userId']);
            $phones = $resolved['phones'] ?? array();
            if (empty($phones)) {
                lc_alimtalk_log('db_received', $target['center'], $target['userId'], '수신번호 없음', false);
                $failed++;
                continue;
            }
            $vars = lc_alimtalk_build_db_variables($conversion, (string) $target['role']);
            foreach ($phones as $phone) {
                $res = lc_alimtalk_send_one($phone, $vars);
                $results[] = array(
                    'center' => $target['center'],
                    'phone'  => $phone,
                    'ok'     => !empty($res['ok']),
                    'message'=> (string) ($res['message'] ?? ''),
                );
                lc_alimtalk_log(
                    'db_received',
                    $target['center'],
                    $target['userId'],
                    $phone . ' · ' . (string) ($res['message'] ?? ''),
                    !empty($res['ok'])
                );
                if (!empty($res['ok'])) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        return array(
            'ok'      => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'results' => $results,
            'message' => "알림톡 발송 {$sent}건" . ($failed > 0 ? " / 실패 {$failed}건" : ''),
        );
    }
}
