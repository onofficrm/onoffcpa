<?php
/**
 * 다중 플랫폼 — 원격 상태 변경 수신 (관리 플랫폼 → 원본 플랫폼)
 *
 * 이 엔드포인트는 "원본 DB를 보유한 플랫폼"에서 실행된다.
 * 관리 플랫폼(예: 온오프CPA)이 승인/반려를 확정하면 이 API 로 푸시하고,
 * 여기서 자신의 로컬 conversion(cv_code = externalLeadId)에 상태를 반영한다.
 *
 * 인증: X-LC-Platform-Token = 발신 플랫폼 레코드의 outbound_token(공유 시크릿)
 * 멱등: X-Idempotency-Key + inbox 저장
 * 플래그 OFF 시 404.
 */
require_once dirname(__DIR__, 2) . '/_common.php';

lc_mp_require_enabled();
lc_api_require_method('POST');

$body = lc_api_read_json_body();

$source_code = isset($body['sourcePlatform']) ? strtoupper(trim((string) $body['sourcePlatform'])) : '';
if ($source_code === '' && isset($_SERVER['HTTP_X_LC_PLATFORM_CODE'])) {
    $source_code = strtoupper(trim((string) $_SERVER['HTTP_X_LC_PLATFORM_CODE']));
}
if ($source_code === '') {
    lc_api_error('sourcePlatform required', 'INVALID_SOURCE', 400);
}

$platform = lc_mp_get_platform_by_code($source_code);
if (!$platform) {
    lc_api_error('unknown platform', 'UNKNOWN_PLATFORM', 404);
}

// 발신 플랫폼이 보낸 토큰 검증 (outbound_token, 없으면 webhook_secret)
$expected = trim((string) ($platform['outbound_token'] ?? ''));
if ($expected === '') {
    $expected = trim((string) ($platform['webhook_secret'] ?? ''));
}
$got = isset($_SERVER['HTTP_X_LC_PLATFORM_TOKEN']) ? (string) $_SERVER['HTTP_X_LC_PLATFORM_TOKEN'] : '';
if ($expected === '' || $got === '' || !hash_equals($expected, $got)) {
    lc_api_error('invalid token', 'UNAUTHORIZED', 401);
}

$command = isset($body['command']) ? trim((string) $body['command']) : 'status_change';
$external_lead_id = isset($body['externalLeadId']) ? trim((string) $body['externalLeadId']) : '';
$status = isset($body['status']) ? strtolower(trim((string) $body['status'])) : '';
$comment = isset($body['comment']) ? trim((string) $body['comment']) : '';
$idem = isset($body['idempotencyKey']) ? trim((string) $body['idempotencyKey']) : '';
if ($idem === '' && isset($_SERVER['HTTP_X_IDEMPOTENCY_KEY'])) {
    $idem = trim((string) $_SERVER['HTTP_X_IDEMPOTENCY_KEY']);
}

if ($external_lead_id === '') {
    lc_api_error('externalLeadId required', 'INVALID_ID', 400);
}
if (!in_array($status, array('approved', 'rejected', 'canceled', 'cancelled', 'pending'), true)) {
    lc_api_error('invalid status', 'INVALID_STATUS', 400);
}
if ($idem === '') {
    $idem = 'remote_status:' . $source_code . ':' . $external_lead_id . ':' . $status;
}

// 멱등 저장 — 중복 수신은 즉시 성공 반환
$inbox = lc_mp_inbox_store((int) $platform['platform_id'], 'remote_status', $idem, $body);
if (empty($inbox['ok'])) {
    lc_api_error($inbox['message'], 'INBOX_FAILED', 500);
}
if (!empty($inbox['duplicate'])) {
    lc_api_success(array('message' => 'duplicate ignored', 'duplicate' => true));
}

$result = lc_mp_apply_remote_status($external_lead_id, $status, $comment);
if (empty($result['ok'])) {
    lc_api_error((string) ($result['message'] ?? 'apply failed'), 'APPLY_FAILED', 409);
}

lc_api_success(array(
    'message' => (string) $result['message'],
    'cvId'    => (int) ($result['cvId'] ?? 0),
    'applied' => !empty($result['applied']),
));
