<?php
/**
 * 다중 플랫폼 광고주 DB 거버넌스 — 코어 헬퍼
 *
 * 안전 규칙:
 * - LC_MULTI_PLATFORM_ENABLED=false 이면 모든 함수가 즉시 no-op / false 반환
 * - 기존 CPA 승인·취소·조회 경로는 플래그 OFF 시 변경되지 않음
 * - 원격 플랫폼(링크커넥트) DB를 직접 쓰지 않음 — API/웹훅 어댑터만 사용
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_mp_enabled')) {
    function lc_mp_enabled()
    {
        return defined('LC_MULTI_PLATFORM_ENABLED') && LC_MULTI_PLATFORM_ENABLED;
    }
}

if (!function_exists('lc_mp_local_platform_code')) {
    function lc_mp_local_platform_code()
    {
        return defined('LC_PLATFORM_CODE') ? (string) LC_PLATFORM_CODE : 'ONOFFCPA';
    }
}

if (!function_exists('lc_mp_onoffcpa_url_candidates')) {
    /**
     * 온오프CPA 공개 URL 후보 (신규 도메인 우선, 레거시 폴백).
     *
     * @return array<int,string>
     */
    function lc_mp_onoffcpa_url_candidates()
    {
        $urls = array();
        if (defined('LC_ONOFFCPA_PUBLIC_URL') && LC_ONOFFCPA_PUBLIC_URL !== '') {
            $urls[] = rtrim((string) LC_ONOFFCPA_PUBLIC_URL, '/');
        }
        if (defined('LC_ONOFFCPA_LEGACY_URL') && LC_ONOFFCPA_LEGACY_URL !== '') {
            $urls[] = rtrim((string) LC_ONOFFCPA_LEGACY_URL, '/');
        }
        $urls[] = 'https://onoffcpa.icrm.co.kr';
        $urls[] = 'https://onoffcpa.iwinv.net';

        return array_values(array_unique(array_filter($urls)));
    }
}

if (!function_exists('lc_mp_resolve_onoffcpa_base_url')) {
    /**
     * 헬스 체크에 응답하는 온오프CPA base URL을 고른다.
     * SSL/별칭 미준비 시 레거시(iwinv)로 폴백해 동기화가 끊기지 않게 한다.
     *
     * @return string
     */
    function lc_mp_resolve_onoffcpa_base_url($timeout = 5)
    {
        $candidates = lc_mp_onoffcpa_url_candidates();
        foreach ($candidates as $base) {
            $url = $base . '/plugin/linkconnect/api/platform/health.php';
            if (!function_exists('curl_init')) {
                break;
            }
            $ch = curl_init($url);
            if ($ch === false) {
                continue;
            }
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => max(2, (int) $timeout),
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ));
            $body = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http >= 200 && $http < 300 && is_string($body) && strpos($body, '"ok"') !== false) {
                return $base;
            }
        }

        // 전부 실패하면 레거시(작동 중인) URL로 폴백 — 신규 도메인을 강제하지 않음
        $legacy = defined('LC_ONOFFCPA_LEGACY_URL') ? rtrim((string) LC_ONOFFCPA_LEGACY_URL, '/') : '';
        if ($legacy !== '') {
            return $legacy;
        }

        return !empty($candidates) ? $candidates[count($candidates) - 1] : 'https://onoffcpa.iwinv.net';
    }
}

if (!function_exists('lc_mp_require_enabled')) {
    /**
     * API 진입점용. 비활성 시 JSON 404 후 종료.
     */
    function lc_mp_require_enabled()
    {
        if (lc_mp_enabled()) {
            return;
        }
        if (function_exists('lc_api_error')) {
            lc_api_error('다중 플랫폼 기능이 비활성화되어 있습니다.', 'MP_DISABLED', 404);
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(array('ok' => false, 'code' => 'MP_DISABLED', 'error' => 'multi-platform disabled'), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('lc_mp_status_map_local')) {
    /**
     * 공통 상태 → 로컬 LC_STATUS_*
     */
    function lc_mp_status_map_local($status)
    {
        $status = strtolower(trim((string) $status));
        $map = array(
            'pending'  => defined('LC_STATUS_PENDING') ? LC_STATUS_PENDING : 'pending',
            'approved' => defined('LC_STATUS_APPROVED') ? LC_STATUS_APPROVED : 'approved',
            'rejected' => defined('LC_STATUS_REJECTED') ? LC_STATUS_REJECTED : 'rejected',
            'canceled' => defined('LC_STATUS_REJECTED') ? LC_STATUS_REJECTED : 'rejected',
            'cancelled'=> defined('LC_STATUS_REJECTED') ? LC_STATUS_REJECTED : 'rejected',
        );

        return isset($map[$status]) ? $map[$status] : $status;
    }
}

if (!function_exists('lc_mp_audit')) {
    function lc_mp_audit($action, array $payload = array())
    {
        if (!lc_mp_enabled()) {
            return;
        }
        if (!function_exists('lc_mp_db_table') || !function_exists('lc_db_table_exists')) {
            return;
        }
        $table = lc_mp_db_table('audit_logs');
        if (!lc_db_table_exists($table)) {
            return;
        }
        $action_esc = lc_sql_escape((string) $action);
        $json = lc_sql_escape(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $mb = isset($GLOBALS['member']['mb_id']) ? lc_sql_escape((string) $GLOBALS['member']['mb_id']) : '';
        lc_sql_query(" INSERT INTO `{$table}` (`action`, `payload_json`, `actor_mb_id`, `created_at`)
            VALUES ('{$action_esc}', '{$json}', '{$mb}', NOW()) ", false);
    }
}
