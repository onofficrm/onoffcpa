<?php
/**
 * 플랫폼 어댑터 — 원격 상태 변경은 HTTP API만 사용 (원격 DB 직접 접근 금지)
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_mp_adapter_push_status')) {
    /**
     * @param array $platform mp_platforms row
     * @param array $command  outbox payload fields
     * @return array{ok:bool,message:string,http?:int,body?:string}
     */
    function lc_mp_adapter_push_status(array $platform, array $command)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }

        $code = strtoupper((string) ($platform['platform_code'] ?? ''));
        if (!empty($platform['is_local']) || $code === lc_mp_local_platform_code()) {
            return array('ok' => true, 'message' => 'local platform — no outbound push');
        }

        // 모든 원격 피어는 동일 remote_status API 를 사용 (링크커넥트/온오프CPA/향후 CPA)
        return lc_mp_adapter_http_push_remote_status($platform, $command);
    }
}

if (!function_exists('lc_mp_adapter_linkconnect_push')) {
    /** @deprecated 범용 HTTP 푸시로 위임 */
    function lc_mp_adapter_linkconnect_push(array $platform, array $command)
    {
        return lc_mp_adapter_http_push_remote_status($platform, $command);
    }
}

if (!function_exists('lc_mp_adapter_http_push_remote_status')) {
    /**
     * 원격 플랫폼 상태 ACK 푸시.
     * POST {api_base_url}/plugin/linkconnect/api/platform/remote_status.php
     */
    function lc_mp_adapter_http_push_remote_status(array $platform, array $command)
    {
        $base = trim((string) ($platform['api_base_url'] ?? ''));
        $token = trim((string) ($platform['outbound_token'] ?? ''));
        if ($base === '' || $token === '') {
            return array(
                'ok' => false,
                'message' => 'peer adapter not configured (api_base_url / outbound_token)',
            );
        }

        $url = rtrim($base, '/') . '/plugin/linkconnect/api/platform/remote_status.php';
        $body = json_encode(array(
            'command'         => (string) ($command['command'] ?? ''),
            'externalLeadId'  => (string) ($command['external_lead_id'] ?? ''),
            'status'          => (string) ($command['status'] ?? ''),
            'comment'         => (string) ($command['comment'] ?? ''),
            'version'         => (int) ($command['version'] ?? 0),
            'idempotencyKey'  => (string) ($command['idempotency_key'] ?? ''),
            'sourcePlatform'  => lc_mp_local_platform_code(),
        ), JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        if ($ch === false) {
            return array('ok' => false, 'message' => 'curl init failed');
        }
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'X-LC-Platform-Token: ' . $token,
                'X-LC-Platform-Code: ' . lc_mp_local_platform_code(),
                'X-Idempotency-Key: ' . (string) ($command['idempotency_key'] ?? ''),
            ),
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
        ));
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array('ok' => false, 'message' => 'curl error: ' . $err, 'http' => $http);
        }
        if ($http < 200 || $http >= 300) {
            return array('ok' => false, 'message' => 'remote HTTP ' . $http, 'http' => $http, 'body' => (string) $resp);
        }

        $decoded = json_decode((string) $resp, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return array('ok' => false, 'message' => 'remote rejected', 'http' => $http, 'body' => (string) $resp);
        }

        return array('ok' => true, 'message' => 'pushed', 'http' => $http, 'body' => (string) $resp);
    }
}

if (!function_exists('lc_mp_adapter_push_inbound_lead')) {
    /**
     * 원본 플랫폼(링크커넥트) → 관리 플랫폼(온오프CPA) 신규 DB 유입 푸시.
     *
     * @return array{ok:bool,message:string,http?:int,body?:string}
     */
    function lc_mp_adapter_push_inbound_lead(array $target_platform, array $payload)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }
        $base = trim((string) ($target_platform['api_base_url'] ?? ''));
        $secret = trim((string) ($target_platform['webhook_secret'] ?? ''));
        if ($base === '' || $secret === '') {
            return array('ok' => false, 'message' => 'target adapter not configured (api_base_url / webhook_secret)');
        }

        $url = rtrim($base, '/') . '/plugin/linkconnect/api/platform/inbound_lead.php';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        if ($ch === false) {
            return array('ok' => false, 'message' => 'curl init failed');
        }
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'X-LC-Platform-Secret: ' . $secret,
                'X-LC-Platform-Code: ' . lc_mp_local_platform_code(),
            ),
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
        ));
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array('ok' => false, 'message' => 'curl error: ' . $err, 'http' => $http);
        }
        if ($http < 200 || $http >= 300) {
            return array('ok' => false, 'message' => 'remote HTTP ' . $http, 'http' => $http, 'body' => (string) $resp);
        }
        $decoded = json_decode((string) $resp, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return array('ok' => false, 'message' => 'remote rejected', 'http' => $http, 'body' => (string) $resp);
        }

        return array('ok' => true, 'message' => 'pushed', 'http' => $http, 'body' => (string) $resp);
    }
}

if (!function_exists('lc_mp_adapter_fetch_wallet_balance')) {
    /**
     * 피어 플랫폼 광고주 잔액 조회
     * POST {api_base_url}/plugin/linkconnect/api/platform/remote_status.php
     * body.command = wallet_balance
     *
     * @return array{ok:bool,message:string,balance?:int,platformCode?:string,http?:int}
     */
    function lc_mp_adapter_fetch_wallet_balance(array $platform, array $lookup)
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }

        $code = strtoupper((string) ($platform['platform_code'] ?? ''));
        if (!empty($platform['is_local']) || $code === lc_mp_local_platform_code()) {
            $resolved = function_exists('lc_mp_resolve_local_mt_for_balance_lookup')
                ? lc_mp_resolve_local_mt_for_balance_lookup($lookup)
                : array('ok' => false, 'message' => 'resolver missing');
            if (empty($resolved['ok'])) {
                return array('ok' => false, 'message' => (string) ($resolved['message'] ?? 'resolve failed'));
            }
            $bal = function_exists('lc_wallet_get_balance')
                ? lc_wallet_get_balance((int) $resolved['mt_id'])
                : 0;

            return array(
                'ok' => true,
                'message' => 'local',
                'balance' => (int) $bal,
                'platformCode' => lc_mp_local_platform_code(),
            );
        }

        $base = trim((string) ($platform['api_base_url'] ?? ''));
        $token = trim((string) ($platform['outbound_token'] ?? ''));
        if ($token === '') {
            $token = trim((string) ($platform['webhook_secret'] ?? ''));
        }
        if ($base === '' || $token === '') {
            return array('ok' => false, 'message' => 'peer adapter not configured (api_base_url / token)');
        }

        $url = rtrim($base, '/') . '/plugin/linkconnect/api/platform/remote_status.php';
        $body = json_encode(array(
            'command'              => 'wallet_balance',
            'groupCode'            => (string) ($lookup['groupCode'] ?? ''),
            'externalMerchantId'   => (string) ($lookup['externalMerchantId'] ?? ''),
            'mtId'                 => (int) ($lookup['mtId'] ?? 0),
            'sourcePlatform'       => lc_mp_local_platform_code(),
        ), JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        if ($ch === false) {
            return array('ok' => false, 'message' => 'curl init failed');
        }
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'X-LC-Platform-Token: ' . $token,
                'X-LC-Platform-Code: ' . lc_mp_local_platform_code(),
            ),
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ));
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array('ok' => false, 'message' => 'curl error: ' . $err, 'http' => $http);
        }
        if ($http < 200 || $http >= 300) {
            return array('ok' => false, 'message' => 'remote HTTP ' . $http, 'http' => $http);
        }
        $decoded = json_decode((string) $resp, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return array('ok' => false, 'message' => 'remote rejected', 'http' => $http);
        }
        $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;

        return array(
            'ok'           => true,
            'message'      => 'ok',
            'balance'      => (int) ($data['balance'] ?? 0),
            'platformCode' => (string) ($data['platformCode'] ?? $code),
            'http'         => $http,
        );
    }
}
