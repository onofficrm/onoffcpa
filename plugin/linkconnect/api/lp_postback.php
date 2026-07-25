<?php
/**
 * 링크프라이스 Reward(POSTBACK) 수신
 *
 * 공식: POST JSON, 타임아웃 최대 10초
 * URL 예:
 *   /plugin/linkconnect/api/lp_postback.php
 *   /api/external/linkprice/postback  (rewrite → 본 파일)
 *
 * 선택 인증: X-LP-SECRET 헤더 또는 ?secret= (lp_networks.postback_secret)
 * 선택 IP: settings lpPostbackIpEnabled + lpPostbackIpAllowlist
 */
require_once dirname(__DIR__) . '/_common.php';

@set_time_limit(8); // 공식 10초 미만으로 응답 목표

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'POST') {
    header('Allow: POST');
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(array('result' => 'error', 'message' => 'method not allowed'), JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    $raw = '';
}
$content_type = isset($_SERVER['CONTENT_TYPE']) ? (string) $_SERVER['CONTENT_TYPE'] : (isset($_SERVER['HTTP_CONTENT_TYPE']) ? (string) $_SERVER['HTTP_CONTENT_TYPE'] : '');

try {
    $out = lc_lp_postback_receive($raw, $content_type, lc_lp_postback_client_ip());
} catch (Throwable $e) {
    // 원본 유실 방지 시도
    try {
        $lpp_id = lc_lp_repo_insert_postback($raw, lc_lp_postback_collect_headers(), lc_lp_postback_client_ip());
        if ($lpp_id > 0) {
            lc_lp_repo_update_postback($lpp_id, array(
                'process_status' => LC_LP_PB_ERROR,
                'error_message'  => 'internal',
            ));
        }
    } catch (Throwable $e2) {
        // ignore
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200); // 원본 저장 우선 — LP 재전송 폭주 완화
    echo json_encode(array('result' => 'success', 'message' => 'accepted'), JSON_UNESCAPED_UNICODE);
    exit;
}

$http = isset($out['http']) ? (int) $out['http'] : 200;
$body = isset($out['body']) && is_array($out['body']) ? $out['body'] : array('result' => 'success');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code($http);
echo json_encode($body, JSON_UNESCAPED_UNICODE);
exit;
