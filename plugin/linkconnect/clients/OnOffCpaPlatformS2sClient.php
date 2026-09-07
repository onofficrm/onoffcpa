<?php
/**
 * Shared ONOFFCPA Platform S2S client (PHP).
 * Used by iCRM / product extends. HMAC headers: X-LC-Platform-*.
 */
if (!class_exists('OnOffCpa_PlatformS2sClient', false)) {
    final class OnOffCpa_PlatformS2sClient
    {
        /** @var array{enabled?:bool,baseUrl?:string,keyId?:string,secret?:string,sourceService?:string,sourceReference?:string} */
        private static $config = array();

        /**
         * @param array{enabled?:bool,baseUrl?:string,keyId?:string,secret?:string,sourceService?:string,sourceReference?:string} $config
         */
        public static function configure(array $config)
        {
            self::$config = array_merge(self::$config, $config);
        }

        public static function isEnabled()
        {
            if (array_key_exists('enabled', self::$config)) {
                return !empty(self::$config['enabled']);
            }
            $v = strtolower(trim((string) self::env('ONOFFCPA_PLATFORM_S2S_ENABLED', 'false')));
            return in_array($v, array('1', 'true', 'yes', 'on'), true);
        }

        public static function baseUrl()
        {
            if (!empty(self::$config['baseUrl'])) {
                return rtrim((string) self::$config['baseUrl'], '/');
            }
            return rtrim(self::env(
                'ONOFFCPA_PLATFORM_S2S_BASE_URL',
                'https://onoffcpa.icrm.co.kr/plugin/linkconnect/platform-api'
            ), '/');
        }

        public static function keyId()
        {
            return trim((string) (!empty(self::$config['keyId'])
                ? self::$config['keyId']
                : self::env('ONOFFCPA_PLATFORM_S2S_KEY_ID', '')));
        }

        public static function secret()
        {
            return trim((string) (!empty(self::$config['secret'])
                ? self::$config['secret']
                : self::env('ONOFFCPA_PLATFORM_S2S_SECRET', '')));
        }

        public static function defaultSourceService()
        {
            $svc = trim((string) (!empty(self::$config['sourceService'])
                ? self::$config['sourceService']
                : self::env('ONOFFCPA_PLATFORM_S2S_SOURCE_SERVICE', 'TRAFFIC')));
            return strtoupper($svc);
        }

        /**
         * @param array<string,mixed> $body
         * @return array{ok:bool,http?:int,data?:array,error?:string}
         */
        public static function post($endpoint, array $body)
        {
            if (!self::isEnabled()) {
                return array('ok' => false, 'error' => 's2s_disabled');
            }
            $key = self::keyId();
            $secret = self::secret();
            if ($key === '' || $secret === '') {
                return array('ok' => false, 'error' => 's2s_credentials_missing');
            }

            $path = '/plugin/linkconnect/platform-api/' . ltrim((string) $endpoint, '/');
            $url = self::baseUrl() . '/' . ltrim((string) $endpoint, '/');
            $raw = json_encode($body, JSON_UNESCAPED_UNICODE);
            if (!is_string($raw)) {
                return array('ok' => false, 'error' => 'json_encode_failed');
            }

            $ts = (string) time();
            $nonce = bin2hex(random_bytes(16));
            $body_hash = hash('sha256', $raw);
            $canonical = "POST\n{$path}\n{$ts}\n{$nonce}\n{$body_hash}";
            $sig = hash_hmac('sha256', $canonical, $secret);

            $ch = curl_init($url);
            if ($ch === false) {
                return array('ok' => false, 'error' => 'curl_init_failed');
            }
            curl_setopt_array($ch, array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $raw,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json',
                    'X-LC-Platform-Key: ' . $key,
                    'X-LC-Platform-Timestamp: ' . $ts,
                    'X-LC-Platform-Nonce: ' . $nonce,
                    'X-LC-Platform-Signature: ' . $sig,
                ),
            ));
            $resp = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if (!is_string($resp)) {
                return array('ok' => false, 'http' => $http, 'error' => $err !== '' ? $err : 'empty_response');
            }
            $decoded = json_decode($resp, true);
            if (!is_array($decoded)) {
                return array('ok' => false, 'http' => $http, 'error' => 'invalid_json', 'raw' => substr($resp, 0, 300));
            }
            $ok = ($http >= 200 && $http < 300) && !empty($decoded['ok']);
            return array(
                'ok'    => $ok,
                'http'  => $http,
                'data'  => $decoded,
                'error' => $ok ? null : (string) ($decoded['error'] ?? $decoded['message'] ?? 'request_failed'),
            );
        }

        /**
         * @return array{ok:bool,http?:int,data?:array,error?:string}
         */
        public static function bindReferral($customer_mb_id, $referral_code, $source_service = '', $source_reference = '')
        {
            $svc = strtoupper(trim((string) ($source_service !== '' ? $source_service : self::defaultSourceService())));
            $ref = trim((string) $source_reference);
            if ($ref === '') {
                $ref = !empty(self::$config['sourceReference'])
                    ? (string) self::$config['sourceReference']
                    : strtolower($svc) . '.icrm.co.kr';
            }
            return self::post('referral_bind.php', array(
                'customerMbId'    => (string) $customer_mb_id,
                'referralCode'    => (string) $referral_code,
                'sourceService'   => $svc,
                'sourceReference' => $ref,
            ));
        }

        /**
         * @return array{ok:bool,http?:int,data?:array,error?:string}
         */
        public static function reportPayment($payment_id, $customer_mb_id, $amount_krw, $source_service = '', $approved_at = '', array $extra = array())
        {
            $svc = strtoupper(trim((string) ($source_service !== '' ? $source_service : self::defaultSourceService())));
            $body = array_merge(array(
                'paymentId'     => (string) $payment_id,
                'customerMbId'  => (string) $customer_mb_id,
                'amount'        => (int) $amount_krw,
                'sourceService' => $svc,
                'approvedAt'    => $approved_at !== '' ? (string) $approved_at : date('Y-m-d H:i:s'),
            ), $extra);
            return self::post('payment_report.php', $body);
        }

        public static function tryBindFromCookie($mb_id, $source_service = '')
        {
            $mb_id = trim((string) $mb_id);
            if ($mb_id === '' || !self::isEnabled()) {
                return;
            }
            if (!class_exists('OnOffCpa_ReferralCapture', false)) {
                return;
            }
            $code = OnOffCpa_ReferralCapture::get();
            if ($code === '') {
                return;
            }
            self::bindReferral($mb_id, $code, $source_service);
        }

        private static function env($key, $default = '')
        {
            $v = getenv($key);
            return ($v === false || $v === null || $v === '') ? $default : (string) $v;
        }
    }
}

if (!class_exists('OnOffCpa_ReferralCapture', false)) {
    final class OnOffCpa_ReferralCapture
    {
        const COOKIE = 'onoff_cpa_ref';
        const TTL = 7776000; // 90d

        public static function captureFromRequest()
        {
            $ref = isset($_GET['ref']) ? strtoupper(trim((string) $_GET['ref'])) : '';
            if ($ref === '' || !preg_match('/^[A-Z0-9_\-]{4,32}$/', $ref)) {
                return;
            }
            if (self::get() !== '') {
                return;
            }
            self::set($ref);
        }

        public static function get()
        {
            $v = isset($_COOKIE[self::COOKIE]) ? strtoupper(trim((string) $_COOKIE[self::COOKIE])) : '';
            if ($v === '' || !preg_match('/^[A-Z0-9_\-]{4,32}$/', $v)) {
                return '';
            }
            return $v;
        }

        public static function set($code)
        {
            $code = strtoupper(trim((string) $code));
            if ($code === '') {
                return;
            }
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie(self::COOKIE, $code, array(
                'expires'  => time() + self::TTL,
                'path'     => '/',
                'domain'   => '.icrm.co.kr',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
            $_COOKIE[self::COOKIE] = $code;
        }
    }
}
