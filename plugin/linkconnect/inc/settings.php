<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_settings_defaults')) {
    function lc_settings_defaults()
    {
        return array(
            'siteName'              => '링크커넥트',
            'siteStatus'            => 'active',
            'adminEmail'            => 'support2580_@linkconnect.co.kr',
            'supportEmail'          => 'support2580_@linkconnect.co.kr',
            'supportPhone'          => '070-8098-6824',
            'timezone'              => 'Asia/Seoul',
            'duplicateDays'         => 30,
            'defaultCvStatus'       => 'pending',
            'duplicateByPhone'      => '1',
            'duplicateByCampaign'   => '1',
            'duplicateByMerchant'   => '0',
            'merchantProcessDays'   => 7,
            'billingDeductMode'     => 'on_receive',
            'billingLowMode'        => 'hold',
            'minChargeAmount'       => 500000,
            'chargeApprovalMode'    => 'manual',
            'showEstRevenue'        => '1',
            'confirmOnApprove'      => '1',
            'excludeCanceled'       => '1',
            'minSettlementAmount'   => 50000,
            'settlementPeriod'      => '매월 1일 ~ 5일',
            'merchantInstantCancel' => '0',
            'adminReviewRequired'   => '1',
            'cancelReasonRequired'  => '1',
            'cancelCommentRequired' => '1',
            'partnerAppealAllowed'  => '1',
            'apiKeyEnabled'         => '1',
            'apiSecretEnabled'      => '1',
            'apiIpRestrict'         => '1',
            'apiLogEnabled'         => '1',
            'apiMaskPii'            => '1',
            'apiRetryCount'         => 3,
            'geminiApiKey'          => '',
            'geminiEnabled'         => '1',
            'geminiModel'           => 'gemini-2.5-flash',
            'geminiImageModel'      => 'gemini-2.5-flash-image',
            'openaiApiKey'          => '',
            'openaiImageEnabled'    => '1',
            'openaiImageModel'      => 'gpt-image-2',
            'openaiImageQuality'    => 'medium',
            'aiChatDailyLimit'      => 30,
            'aiPromoDailyLimit'     => 20,
            'aiSummaryDailyLimit'   => 10,
            'aiImageDailyLimit'     => 15,
            'aiImagePromptThumbnail'=> '',
            'aiImagePromptPromo'    => '',
            'advertiserContractGraceUntil' => '',
            'promoGuideMaxImageBytes'   => 2097152,
            'promoGuideSkipReview'      => '0',
            'cpaTrackingDomainEnabled'  => '0',
            'cpaTrackingBaseUrl'        => '',
            'cpaLandingSeoTitle'        => '{campaign} 상담 신청 | {site}',
            'cpaLandingSeoDescription'  => '{campaign} 무료 상담 신청 페이지입니다. 지금 바로 상담을 신청해 보세요.',
            'cpaLandingSeoKeywords'     => '',
            'cpaLandingSeoOgImage'      => '',
            'cpaLandingSeoRobots'       => 'index,follow',
            'notifyLowBalanceEmail' => '1',
            'notifyLowBalanceSms'   => '0',
            'notifyLowBalanceKakao' => '0',
            'notifyLowBalanceEmailTpl' => '[{site}] {company}님, 광고비 잔액이 {balance}원입니다. (기준 {threshold}원) 충전을 진행해 주세요.',
            'notifyLowBalanceSmsTpl'   => '[{site}] 광고비 잔액 {balance}원. 충전 필요.',
            'notifyLowBalanceKakaoTpl' => '{company}님, 광고비 잔액 {balance}원입니다. 충전해 주세요.',
            'callEnabled'           => '0',
            'callProvider'          => '',
            'callApiBaseUrl'        => '',
            'callApiKey'            => '',
            'callApiSecret'         => '',
            'callWebhookToken'      => '',
            'callDefaultPrice'      => 0,
            'callMinDuration'       => 0,
            'callCreateOnMissed'    => '0',
            'callRecordingMode'     => 'normal',
            // 링크프라이스 CPS (외부 네트워크 — CPA와 분리)
            'lpEnabled'             => '0',
            'lpAffiliateCode'       => '',
            'lpAuthKey'             => '',
            'lpPostbackSecret'      => '',
            'lpDefaultPartnerRate'  => '70',
            'lpCronToken'           => '',
            'lpPostbackIpEnabled'   => '0',
            'lpPostbackIpAllowlist' => '',
        );
    }
}

if (!function_exists('lc_settings_get_all')) {
    function lc_settings_get_all()
    {
        $defaults = lc_settings_defaults();
        if (!function_exists('lc_db_installed') || !function_exists('lc_db_table_exists') || !function_exists('lc_table')) {
            return $defaults;
        }
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('settings'))) {
            return $defaults;
        }

        $table = lc_table('settings');
        $result = lc_sql_query(" SELECT st_key, st_value FROM `{$table}` ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $key = (string) ($row['st_key'] ?? '');
                if ($key !== '' && array_key_exists($key, $defaults)) {
                    $defaults[$key] = (string) ($row['st_value'] ?? '');
                }
            }
        }

        return $defaults;
    }
}

if (!function_exists('lc_settings_get')) {
    function lc_settings_get($key, $default = null)
    {
        $all = lc_settings_get_all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default;
    }
}

if (!function_exists('lc_settings_get_int')) {
    function lc_settings_get_int($key, $default = 0)
    {
        return (int) lc_settings_get($key, (string) $default);
    }
}

if (!function_exists('lc_settings_get_bool')) {
    function lc_settings_get_bool($key, $default = false)
    {
        $value = lc_settings_get($key, $default ? '1' : '0');

        return in_array((string) $value, array('1', 'true', 'yes', 'on'), true);
    }
}

if (!function_exists('lc_settings_secret_keys')) {
    function lc_settings_secret_keys()
    {
        return array('geminiApiKey', 'openaiApiKey', 'callApiKey', 'callApiSecret', 'callWebhookToken', 'lpAuthKey', 'lpPostbackSecret');
    }
}

if (!function_exists('lc_settings_ensure_table')) {
    function lc_settings_ensure_table()
    {
        if (!function_exists('lc_db_table_exists') || !function_exists('lc_table') || !function_exists('lc_sql_query')) {
            return false;
        }

        $table = lc_table('settings');
        if (lc_db_table_exists($table)) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `st_key` varchar(100) NOT NULL,
            `st_value` text,
            `st_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`st_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        return (bool) lc_sql_query($sql, false);
    }
}

if (!function_exists('lc_settings_save_row')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_settings_save_row($key, $value)
    {
        $defaults = lc_settings_defaults();
        if (!array_key_exists($key, $defaults)) {
            return array('ok' => false, 'message' => '알 수 없는 설정 키입니다.');
        }

        if (!lc_settings_ensure_table()) {
            return array('ok' => false, 'message' => '설정 테이블을 준비하지 못했습니다.');
        }

        $table = lc_table('settings');
        $key_esc = lc_sql_escape((string) $key);
        $val_esc = lc_sql_escape(is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        $ok = lc_sql_query(" INSERT INTO `{$table}` (st_key, st_value) VALUES ('{$key_esc}', '{$val_esc}')
            ON DUPLICATE KEY UPDATE st_value = '{$val_esc}', st_updated_at = NOW() ", false);

        if (!$ok) {
            $err = function_exists('lc_sql_error') ? lc_sql_error() : 'DB 오류';

            return array('ok' => false, 'message' => '설정 저장 실패 (' . $key . '): ' . $err);
        }

        return array('ok' => true, 'message' => '');
    }
}

if (!function_exists('lc_settings_save')) {
    /**
     * @return array{ok:bool,message:string,settings:array}
     */
    function lc_settings_save(array $values)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'settings' => lc_settings_defaults());
        }

        if (!lc_settings_ensure_table()) {
            return array('ok' => false, 'message' => '설정 테이블을 준비하지 못했습니다.', 'settings' => lc_settings_get_all());
        }

        $defaults = lc_settings_defaults();
        $secret_keys = lc_settings_secret_keys();

        if (function_exists('lc_settings_validate_before_save')) {
            $validated = lc_settings_validate_before_save($values);
            if (empty($validated['ok'])) {
                return array('ok' => false, 'message' => $validated['message'], 'settings' => lc_settings_get_all());
            }
            $values = $validated['values'];
        }

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }
            if (in_array($key, $secret_keys, true)) {
                $key_val = trim((string) $value);
                if ($key_val === '' || strpos($key_val, '*') !== false) {
                    continue;
                }
                $saved = lc_settings_save_row($key, $key_val);
                if (!$saved['ok']) {
                    return array('ok' => false, 'message' => $saved['message'], 'settings' => lc_settings_get_all());
                }
                continue;
            }
            $saved = lc_settings_save_row($key, $value);
            if (!$saved['ok']) {
                return array('ok' => false, 'message' => $saved['message'], 'settings' => lc_settings_get_all());
            }
        }

        return array(
            'ok'       => true,
            'message'  => '설정이 저장되었습니다.',
            'settings' => lc_settings_get_all(),
        );
    }
}

if (!function_exists('lc_settings_normalize_cpa_tracking_base_url')) {
    /**
     * @return array{ok:bool,message:string,url:string}
     */
    function lc_settings_normalize_cpa_tracking_base_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return array('ok' => true, 'message' => '', 'url' => '');
        }

        if (!preg_match('#^https?://#i', $url)) {
            return array('ok' => false, 'message' => '홍보 링크 도메인은 http:// 또는 https://로 시작해야 합니다.', 'url' => '');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return array('ok' => false, 'message' => '올바른 홍보 링크 도메인을 입력하세요.', 'url' => '');
        }

        if (!empty($parts['path']) && $parts['path'] !== '/') {
            return array('ok' => false, 'message' => '도메인만 입력하세요. 경로(/r 등)는 포함하지 마세요.', 'url' => '');
        }
        if (!empty($parts['query']) || !empty($parts['fragment'])) {
            return array('ok' => false, 'message' => '쿼리스트링이나 # 앵커는 사용할 수 없습니다.', 'url' => '');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        return array(
            'ok'      => true,
            'message' => '',
            'url'     => $scheme . '://' . $host . $port,
        );
    }
}

if (!function_exists('lc_settings_validate_before_save')) {
    /**
     * @return array{ok:bool,message:string,values:array}
     */
    function lc_settings_validate_before_save(array $values)
    {
        $enabled = false;
        if (isset($values['cpaTrackingDomainEnabled'])) {
            $enabled = in_array((string) $values['cpaTrackingDomainEnabled'], array('1', 'true', 'yes', 'on'), true);
        } elseif (function_exists('lc_settings_get_bool')) {
            $enabled = lc_settings_get_bool('cpaTrackingDomainEnabled');
        }

        if (array_key_exists('cpaTrackingBaseUrl', $values)) {
            $norm = lc_settings_normalize_cpa_tracking_base_url($values['cpaTrackingBaseUrl']);
            if (empty($norm['ok'])) {
                return array('ok' => false, 'message' => $norm['message'], 'values' => $values);
            }
            $values['cpaTrackingBaseUrl'] = $norm['url'];
            if ($enabled && $norm['url'] === '') {
                return array('ok' => false, 'message' => '독립 도메인 사용 시 홍보 링크 기본 URL을 입력하세요.', 'values' => $values);
            }
        }

        if (array_key_exists('cpaLandingSeoOgImage', $values)) {
            $og = trim((string) $values['cpaLandingSeoOgImage']);
            if ($og !== '' && !preg_match('#^https?://#i', $og)) {
                return array('ok' => false, 'message' => 'OG 이미지는 http(s) URL이어야 합니다.', 'values' => $values);
            }
            $values['cpaLandingSeoOgImage'] = $og;
        }

        foreach (array('cpaLandingSeoTitle', 'cpaLandingSeoDescription', 'cpaLandingSeoKeywords', 'cpaLandingSeoRobots') as $seo_key) {
            if (array_key_exists($seo_key, $values)) {
                $values[$seo_key] = trim((string) $values[$seo_key]);
            }
        }

        return array('ok' => true, 'message' => '', 'values' => $values);
    }
}

if (!function_exists('lc_settings_raw_for_admin')) {
    /**
     * 관리자 설정 API용 flat raw (비밀값 제거, Set/Masked 플래그 문자열)
     *
     * @return array<string, string>
     */
    function lc_settings_raw_for_admin(array $settings)
    {
        $raw = $settings;
        unset(
            $raw['geminiApiKey'],
            $raw['openaiApiKey'],
            $raw['callApiKey'],
            $raw['callApiSecret'],
            $raw['callWebhookToken'],
            $raw['lpAuthKey'],
            $raw['lpPostbackSecret']
        );

        $raw['geminiApiKeySet'] = trim((string) ($settings['geminiApiKey'] ?? '')) !== '' ? '1' : '0';
        $raw['geminiApiKeyMasked'] = function_exists('lc_gemini_mask_key')
            ? lc_gemini_mask_key((string) ($settings['geminiApiKey'] ?? ''))
            : '';
        $raw['openaiApiKeySet'] = trim((string) ($settings['openaiApiKey'] ?? '')) !== '' ? '1' : '0';
        $raw['openaiApiKeyMasked'] = function_exists('lc_openai_mask_key')
            ? lc_openai_mask_key((string) ($settings['openaiApiKey'] ?? ''))
            : '';
        $raw['callApiKeySet'] = trim((string) ($settings['callApiKey'] ?? '')) !== '' ? '1' : '0';
        $raw['callApiSecretSet'] = trim((string) ($settings['callApiSecret'] ?? '')) !== '' ? '1' : '0';
        $raw['callWebhookTokenSet'] = trim((string) ($settings['callWebhookToken'] ?? '')) !== '' ? '1' : '0';

        return $raw;
    }
}

if (!function_exists('lc_settings_to_api')) {
    function lc_settings_to_api(array $settings)
    {
        return array(
            'general' => array(
                'siteName'     => (string) ($settings['siteName'] ?? ''),
                'siteStatus'   => (string) ($settings['siteStatus'] ?? 'active'),
                'adminEmail'   => (string) ($settings['adminEmail'] ?? ''),
                'supportEmail' => (string) ($settings['supportEmail'] ?? ''),
                'supportPhone' => (string) ($settings['supportPhone'] ?? ''),
                'timezone'     => (string) ($settings['timezone'] ?? 'Asia/Seoul'),
            ),
            'cpa' => array(
                'duplicateDays'       => (int) ($settings['duplicateDays'] ?? 30),
                'defaultCvStatus'     => (string) ($settings['defaultCvStatus'] ?? 'pending'),
                'duplicateByPhone'    => lc_settings_get_bool('duplicateByPhone'),
                'duplicateByCampaign' => lc_settings_get_bool('duplicateByCampaign'),
                'duplicateByMerchant' => lc_settings_get_bool('duplicateByMerchant'),
                'merchantProcessDays' => (int) ($settings['merchantProcessDays'] ?? 7),
                'advertiserContractGraceUntil' => (string) ($settings['advertiserContractGraceUntil'] ?? ''),
                'cpaTrackingDomainEnabled' => lc_settings_get_bool('cpaTrackingDomainEnabled'),
                'cpaTrackingBaseUrl'       => (string) ($settings['cpaTrackingBaseUrl'] ?? ''),
                'cpaTrackingPreviewUrl'    => function_exists('lc_link_tracking_base_url') ? lc_link_tracking_base_url() : rtrim((string) G5_URL, '/'),
                'cpaLandingSeoTitle'       => (string) ($settings['cpaLandingSeoTitle'] ?? ''),
                'cpaLandingSeoDescription' => (string) ($settings['cpaLandingSeoDescription'] ?? ''),
                'cpaLandingSeoKeywords'    => (string) ($settings['cpaLandingSeoKeywords'] ?? ''),
                'cpaLandingSeoOgImage'     => (string) ($settings['cpaLandingSeoOgImage'] ?? ''),
                'cpaLandingSeoRobots'      => (string) ($settings['cpaLandingSeoRobots'] ?? 'index,follow'),
            ),
            'billing' => array(
                'billingDeductMode'  => (string) ($settings['billingDeductMode'] ?? 'on_receive'),
                'billingLowMode'     => (string) ($settings['billingLowMode'] ?? 'hold'),
                'minChargeAmount'    => (int) ($settings['minChargeAmount'] ?? 500000),
                'chargeApprovalMode' => (string) ($settings['chargeApprovalMode'] ?? 'manual'),
                'notifyLowBalanceEmail' => lc_settings_get_bool('notifyLowBalanceEmail', true),
                'notifyLowBalanceSms'   => lc_settings_get_bool('notifyLowBalanceSms'),
                'notifyLowBalanceKakao' => lc_settings_get_bool('notifyLowBalanceKakao'),
                'notifyLowBalanceEmailTpl' => (string) ($settings['notifyLowBalanceEmailTpl'] ?? ''),
                'notifyLowBalanceSmsTpl'   => (string) ($settings['notifyLowBalanceSmsTpl'] ?? ''),
                'notifyLowBalanceKakaoTpl' => (string) ($settings['notifyLowBalanceKakaoTpl'] ?? ''),
            ),
            'partner' => array(
                'showEstRevenue'      => lc_settings_get_bool('showEstRevenue'),
                'confirmOnApprove'    => lc_settings_get_bool('confirmOnApprove'),
                'excludeCanceled'     => lc_settings_get_bool('excludeCanceled'),
                'minSettlementAmount' => (int) ($settings['minSettlementAmount'] ?? 50000),
                'settlementPeriod'    => (string) ($settings['settlementPeriod'] ?? ''),
            ),
            'cancel' => array(
                'merchantInstantCancel' => lc_settings_get_bool('merchantInstantCancel'),
                'adminReviewRequired'   => lc_settings_get_bool('adminReviewRequired'),
                'cancelReasonRequired'  => lc_settings_get_bool('cancelReasonRequired'),
                'cancelCommentRequired' => lc_settings_get_bool('cancelCommentRequired'),
                'partnerAppealAllowed'  => lc_settings_get_bool('partnerAppealAllowed'),
            ),
            'api' => array(
                'apiKeyEnabled'    => lc_settings_get_bool('apiKeyEnabled'),
                'apiSecretEnabled' => lc_settings_get_bool('apiSecretEnabled'),
                'apiIpRestrict'    => lc_settings_get_bool('apiIpRestrict'),
                'apiLogEnabled'    => lc_settings_get_bool('apiLogEnabled'),
                'apiMaskPii'       => lc_settings_get_bool('apiMaskPii'),
                'apiRetryCount'    => (int) ($settings['apiRetryCount'] ?? 3),
            ),
            'ai' => array(
                'geminiEnabled'       => lc_settings_get_bool('geminiEnabled', true),
                'geminiModel'         => (string) ($settings['geminiModel'] ?? 'gemini-2.5-flash'),
                'geminiImageModel'    => (string) ($settings['geminiImageModel'] ?? 'gemini-2.5-flash-image'),
                'geminiApiKeySet'     => trim((string) ($settings['geminiApiKey'] ?? '')) !== '',
                'geminiApiKeyMasked'  => function_exists('lc_gemini_mask_key')
                    ? lc_gemini_mask_key((string) ($settings['geminiApiKey'] ?? ''))
                    : '',
                'openaiImageEnabled'  => lc_settings_get_bool('openaiImageEnabled', true),
                'openaiImageModel'    => (string) ($settings['openaiImageModel'] ?? 'gpt-image-2'),
                'openaiImageQuality'  => (string) ($settings['openaiImageQuality'] ?? 'medium'),
                'openaiApiKeySet'     => trim((string) ($settings['openaiApiKey'] ?? '')) !== '',
                'openaiApiKeyMasked'  => function_exists('lc_openai_mask_key')
                    ? lc_openai_mask_key((string) ($settings['openaiApiKey'] ?? ''))
                    : '',
                'aiChatDailyLimit'    => (int) ($settings['aiChatDailyLimit'] ?? 30),
                'aiPromoDailyLimit'   => (int) ($settings['aiPromoDailyLimit'] ?? 20),
                'aiSummaryDailyLimit' => (int) ($settings['aiSummaryDailyLimit'] ?? 10),
                'aiImageDailyLimit'   => (int) ($settings['aiImageDailyLimit'] ?? 15),
                'aiImagePromptThumbnail' => (string) ($settings['aiImagePromptThumbnail'] ?? ''),
                'aiImagePromptPromo'     => (string) ($settings['aiImagePromptPromo'] ?? ''),
            ),
            'call' => array(
                'callEnabled'        => lc_settings_get_bool('callEnabled'),
                'callProvider'       => (string) ($settings['callProvider'] ?? ''),
                'callApiBaseUrl'     => (string) ($settings['callApiBaseUrl'] ?? ''),
                'callApiKeySet'      => trim((string) ($settings['callApiKey'] ?? '')) !== '',
                'callApiSecretSet'   => trim((string) ($settings['callApiSecret'] ?? '')) !== '',
                'callWebhookTokenSet' => trim((string) ($settings['callWebhookToken'] ?? '')) !== '',
                'callDefaultPrice'   => (int) ($settings['callDefaultPrice'] ?? 0),
                'callMinDuration'    => (int) ($settings['callMinDuration'] ?? 0),
                'callCreateOnMissed' => lc_settings_get_bool('callCreateOnMissed'),
                'callRecordingMode'  => (string) ($settings['callRecordingMode'] ?? 'normal'),
            ),
            'linkprice' => array(
                'lpEnabled'            => lc_settings_get_bool('lpEnabled'),
                'lpAffiliateCode'      => (string) ($settings['lpAffiliateCode'] ?? ''),
                'lpAuthKeySet'         => trim((string) ($settings['lpAuthKey'] ?? '')) !== '',
                'lpAuthKeyMasked'      => function_exists('lc_lp_mask_secret')
                    ? lc_lp_mask_secret((string) ($settings['lpAuthKey'] ?? ''))
                    : '',
                'lpPostbackSecretSet'  => trim((string) ($settings['lpPostbackSecret'] ?? '')) !== '',
                'lpPostbackSecretMasked' => function_exists('lc_lp_mask_secret')
                    ? lc_lp_mask_secret((string) ($settings['lpPostbackSecret'] ?? ''))
                    : '',
                'lpDefaultPartnerRate' => (float) ($settings['lpDefaultPartnerRate'] ?? 70),
                'lpCronTokenSet'       => trim((string) ($settings['lpCronToken'] ?? '')) !== '',
                'lpPostbackIpEnabled'  => lc_settings_get_bool('lpPostbackIpEnabled'),
                'lpPostbackIpAllowlist'=> (string) ($settings['lpPostbackIpAllowlist'] ?? ''),
                'config'               => function_exists('lc_lp_config_to_api') ? lc_lp_config_to_api() : null,
            ),
        );
    }
}
