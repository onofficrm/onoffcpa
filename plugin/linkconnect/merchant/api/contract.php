<?php
require_once __DIR__ . '/_common.php';

if (!function_exists('lc_merchant_contract_api_require_post_csrf')) {
    function lc_merchant_contract_api_require_post_csrf(array $body)
    {
        if (!lc_merchant_contract_api_csrf_ok()) {
            lc_api_error('CSRF 검증에 실패했습니다.', 'CSRF', 403);
        }

        $token = isset($body['csrfToken']) ? (string) $body['csrfToken'] : '';
        if (!lc_merchant_contract_csrf_verify($token)) {
            lc_api_error('보안 토큰이 유효하지 않습니다. 페이지를 새로고침해 주세요.', 'CSRF_TOKEN', 403);
        }
    }
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $merchant = lc_api_require_contract_merchant();
    $mt_id = (int) $merchant['mt_id'];

    if (function_exists('lc_merchant_contract_db_ensure_schema')) {
        lc_merchant_contract_db_ensure_schema();
    }
    if (function_exists('lc_merchant_contract_log_db_ensure_schema')) {
        lc_merchant_contract_log_db_ensure_schema();
    }
    if (function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
        lc_merchant_contract_addendum_db_ensure_schema();
    }

    $mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'write';
    if ($mode === 'read') {
        $version_param = isset($_GET['version']) ? trim((string) $_GET['version']) : '';
        $version = null;
        if ($version_param !== '') {
            $version = lc_merchant_contract_sanitize_version($version_param);
            if ($version === '') {
                lc_api_error('유효하지 않은 계약서 버전입니다.', 'INVALID_VERSION', 400);
            }
        }
        $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : 0;
        $read = lc_merchant_contract_read_view_for_merchant($mt_id, $version, $cp_id);
        if (empty($read['ok'])) {
            lc_api_error($read['message'], 'NOT_FOUND', 404);
        }
        lc_api_success($read['data']);
    }

    lc_api_success(lc_merchant_contract_view_to_api($mt_id));
}

if ($method === 'POST') {
    $merchant = lc_api_require_contract_merchant();
    $mt_id = (int) $merchant['mt_id'];

    $body = lc_api_read_json_body();
    lc_merchant_contract_api_require_post_csrf($body);

    $action = isset($body['action']) ? (string) $body['action'] : 'draft';

    if ($action === 'add_addendum') {
        if (function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
            lc_merchant_contract_addendum_db_ensure_schema();
        }
        $version_param = isset($body['version']) ? trim((string) $body['version']) : '';
        $version = $version_param !== ''
            ? lc_merchant_contract_sanitize_version($version_param)
            : lc_merchant_contract_current_version();
        $contract = lc_merchant_contract_get($mt_id, $version);
        if (!is_array($contract) || !lc_merchant_contract_assert_merchant_owns($contract, $mt_id)) {
            lc_api_error('계약서를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        global $member;
        $result = lc_merchant_contract_addendum_create((int) $contract['mc_id'], array(
            'title'           => isset($body['title']) ? (string) $body['title'] : '특약사항',
            'body'            => isset($body['body']) ? (string) $body['body'] : '',
            'created_by_type' => 'merchant',
            'created_by'      => is_array($member) ? (string) ($member['mb_id'] ?? '') : '',
        ));
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'ADDENDUM_FAILED', 400);
        }
        $read = lc_merchant_contract_read_view_for_merchant($mt_id, $version);
        lc_api_success(array(
            'message'  => $result['message'],
            'addendum' => $result['addendum'] ?? null,
            'data'     => !empty($read['ok']) ? ($read['data'] ?? null) : null,
        ));
    }

    if ($action !== 'sign' && $action !== 'add_addendum' && !lc_merchant_contract_can_write($mt_id)) {
        lc_api_error('이미 체결된 계약서는 수정할 수 없습니다.', 'CONTRACT_SIGNED', 403);
    }

    if ($action === 'draft') {
        $result = lc_merchant_contract_save_draft($mt_id, $body);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'VALIDATION', 400, array(
                'errors' => $result['errors'] ?? array(),
            ));
        }

        lc_api_success(array(
            'message'  => $result['message'],
            'contract' => isset($result['contract']) && is_array($result['contract']) ? lc_merchant_contract_to_api($result['contract']) : null,
            'state'    => lc_merchant_contract_view_to_api($mt_id),
        ));
    }

    if ($action === 'signature') {
        $data_url = isset($body['signatureDataUrl']) ? (string) $body['signatureDataUrl'] : '';
        if ($data_url === '' || strpos($data_url, 'data:image/png;base64,') !== 0) {
            lc_api_error('PNG 서명 데이터가 필요합니다.', 'INVALID_SIGNATURE', 400);
        }

        $binary = base64_decode(substr($data_url, strlen('data:image/png;base64,')), true);
        $saved = lc_merchant_contract_save_signature_png($mt_id, $binary === false ? '' : $binary);
        if (empty($saved['ok'])) {
            lc_api_error($saved['message'], 'SIGNATURE_SAVE', 400);
        }

        lc_merchant_contract_create_pending($mt_id);
        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        if (is_array($contract)) {
            $table = lc_merchant_contract_table();
            $mc_id = (int) $contract['mc_id'];
            $path_esc = lc_sql_escape((string) $saved['path']);
            $status = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS);
            lc_sql_query(" UPDATE `{$table}`
                SET mc_signature_file_path = '{$path_esc}',
                    mc_status = '{$status}',
                    mc_updated_at = NOW()
                WHERE mc_id = '{$mc_id}' AND mc_mt_id = '{$mt_id}' ", false);
        }

        lc_api_success(array(
            'message'      => $saved['message'],
            'hasSignature' => true,
            'state'        => lc_merchant_contract_view_to_api($mt_id),
        ));
    }

    if ($action === 'validate') {
        $has_signature = false;
        $contract = lc_merchant_contract_get($mt_id, lc_merchant_contract_current_version());
        if (is_array($contract) && (string) ($contract['mc_signature_file_path'] ?? '') !== '') {
            $has_signature = true;
        }

        $payload = $body;
        $payload['hasSignature'] = $has_signature;

        $draft = lc_merchant_contract_save_draft($mt_id, array_merge($body, array('step' => 3)));
        if (empty($draft['ok'])) {
            lc_api_error($draft['message'], 'VALIDATION', 400, array(
                'errors' => $draft['errors'] ?? array(),
            ));
        }

        $validated = lc_merchant_contract_validate_submit($mt_id, $payload);
        if (empty($validated['ok'])) {
            lc_api_error($validated['message'], 'VALIDATION', 400, array(
                'errors' => $validated['errors'] ?? array(),
            ));
        }

        lc_api_success(array(
            'message'   => $validated['message'],
            'validated' => true,
            'state'     => lc_merchant_contract_view_to_api($mt_id),
        ));
    }

    if ($action === 'sign') {
        $result = lc_merchant_contract_sign($mt_id, $body);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SIGN_FAILED', 400, array(
                'errors' => $result['errors'] ?? array(),
            ));
        }

        $contract_row = isset($result['contract']) && is_array($result['contract']) ? $result['contract'] : null;

        lc_api_success(array(
            'message'       => $result['message'],
            'alreadySigned' => !empty($result['alreadySigned']),
            'contract'      => $contract_row ? lc_merchant_contract_to_api($contract_row) : null,
            'state'         => isset($result['state']) && is_array($result['state']) ? $result['state'] : lc_merchant_contract_view_to_api($mt_id),
        ));
    }

    lc_api_error('지원하지 않는 요청입니다.', 'BAD_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
