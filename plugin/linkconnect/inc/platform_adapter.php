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

        if ($code === (defined('LC_PLATFORM_LINKCONNECT') ? LC_PLATFORM_LINKCONNECT : 'LINKCONNECT')) {
            return lc_mp_adapter_linkconnect_push($platform, $command);
        }

        // 향후 독립 플랫폼: platform_code 별 어댑터 분기
        return array('ok' => false, 'message' => 'adapter not implemented for ' . $code);
    }
}

if (!function_exists('lc_mp_adapter_linkconnect_push')) {
    /**
     * 링크커넥트 원격 승인/취소 푸시.
     * api_base_url / outbound_token 미설정 시 실패만 반환 — 절대 로컬 상태를 강제 성공 처리하지 않음.
     *
     * 예상 엔드포인트 (링크커넥트에 별도 배포 전까지 stub):
     *   POST {api_base_url}/plugin/linkconnect/api/platform/remote_status.php
     */
    function lc_mp_adapter_linkconnect_push(array $platform, array $command)
    {
        $base = trim((string) ($platform['api_base_url'] ?? ''));
        $token = trim((string) ($platform['outbound_token'] ?? ''));
        if ($base === '' || $token === '') {
            return array(
                'ok' => false,
                'message' => 'LinkConnect adapter not configured (api_base_url / outbound_token)',
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
