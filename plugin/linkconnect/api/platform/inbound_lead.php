<?php
/**
 * 다중 플랫폼 — 원격 DB 유입 웹훅
 * POST JSON + X-LC-Platform-Secret
 *
 * LC_MULTI_PLATFORM_ENABLED=false 이면 404.
 */
require_once dirname(__DIR__, 2) . '/_common.php';

lc_mp_require_enabled();
lc_api_require_method('POST');

$body = lc_api_read_json_body();
$source_code = isset($body['sourcePlatform']) ? strtoupper(trim((string) $body['sourcePlatform'])) : '';
if ($source_code === '') {
    $source_code = isset($_SERVER['HTTP_X_LC_PLATFORM_CODE']) ? strtoupper(trim((string) $_SERVER['HTTP_X_LC_PLATFORM_CODE'])) : '';
}
if ($source_code === '') {
    lc_api_error('sourcePlatform required', 'INVALID_SOURCE', 400);
}

$platform = lc_mp_get_platform_by_code($source_code);
if (!$platform) {
    lc_api_error('unknown platform', 'UNKNOWN_PLATFORM', 404);
}
if (!lc_mp_verify_webhook_secret($platform)) {
    lc_api_error('invalid secret', 'UNAUTHORIZED', 401);
}

$event = isset($body['eventType']) ? trim((string) $body['eventType']) : 'lead.upsert';
$idem = isset($body['idempotencyKey']) ? trim((string) $body['idempotencyKey']) : '';
if ($idem === '') {
    $idem = $source_code . ':' . (string) ($body['externalLeadId'] ?? '') . ':' . (string) ($body['version'] ?? '1');
}

$inbox = lc_mp_inbox_store((int) $platform['platform_id'], $event, $idem, $body);
if (empty($inbox['ok'])) {
    lc_api_error($inbox['message'], 'INBOX_FAILED', 500);
}

$lead = lc_mp_upsert_lead_ref_from_inbound($platform, $body);
if (empty($lead['ok'])) {
    lc_api_error($lead['message'], 'LEAD_UPSERT_FAILED', 400);
}

$lead_ref_id = (int) ($lead['lead_ref_id'] ?? 0);

// 로컬이 관리 플랫폼이면 로컬 conversion 을 생성/연결(광고주가 온오프CPA에서 승인/반려 가능).
$local_cv_id = 0;
$local_conv_msg = '';
if ($lead_ref_id > 0 && function_exists('lc_mp_ensure_local_conversion_for_lead')) {
    $leads_table = lc_mp_db_table('lead_refs');
    $ref_row = sql_fetch(" SELECT * FROM `{$leads_table}` WHERE lead_ref_id = '{$lead_ref_id}' LIMIT 1 ");
    if (is_array($ref_row)) {
        $conv = lc_mp_ensure_local_conversion_for_lead($ref_row, $body);
        $local_conv_msg = (string) ($conv['message'] ?? '');
        if (!empty($conv['ok'])) {
            $local_cv_id = (int) ($conv['cvId'] ?? 0);
        }
    }
}

lc_mp_audit('inbound.lead', array(
    'source' => $source_code,
    'lead_ref_id' => $lead_ref_id,
    'local_cv_id' => $local_cv_id,
    'duplicate' => !empty($inbox['duplicate']),
));

lc_api_success(array(
    'message'      => $lead['message'],
    'leadRefId'    => $lead_ref_id,
    'localCvId'    => $local_cv_id,
    'localConvMsg' => $local_conv_msg,
    'duplicate'    => !empty($inbox['duplicate']),
));
