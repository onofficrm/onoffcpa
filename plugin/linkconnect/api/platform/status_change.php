<?php
/**
 * 다중 플랫폼 — 외부 lead 상태 변경 요청 (관리 플랫폼 → outbox)
 * 로컬 lc_conversions 승인 API 와 분리. 플래그 OFF 시 404.
 */
require_once dirname(__DIR__, 2) . '/_common.php';

lc_mp_require_enabled();
lc_api_require_method('POST');

$merchant = lc_api_require_active_merchant();
$mt_id = (int) ($merchant['mt_id'] ?? 0);
$body = lc_api_read_json_body();

$lead_ref_id = isset($body['leadRefId']) ? (int) $body['leadRefId'] : 0;
$status = isset($body['status']) ? strtolower(trim((string) $body['status'])) : '';
$comment = isset($body['comment']) ? trim((string) $body['comment']) : '';

if ($lead_ref_id <= 0) {
    lc_api_error('leadRefId required', 'INVALID_ID', 400);
}
if (!in_array($status, array('approved', 'rejected', 'pending'), true)) {
    lc_api_error('invalid status', 'INVALID_STATUS', 400);
}
if (!(function_exists('lc_mp_local_can_mutate_for_mt')
    ? lc_mp_local_can_mutate_for_mt($mt_id)
    : lc_mp_local_is_management_for_mt($mt_id))) {
    lc_api_error('이 광고주는 로컬에서 상태를 변경할 수 없습니다.', 'NOT_ALLOWED', 403);
}

$result = lc_mp_enqueue_status_change($lead_ref_id, $status, $comment, $mt_id);
if (empty($result['ok'])) {
    lc_api_error($result['message'], 'ENQUEUE_FAILED', 400);
}

lc_api_success(array(
    'message'  => $result['message'],
    'outboxId' => (int) ($result['outbox_id'] ?? 0),
));
