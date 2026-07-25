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
