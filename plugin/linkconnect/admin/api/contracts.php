<?php
require_once __DIR__ . '/_common.php';

lc_api_require_admin();

if (function_exists('lc_merchant_contract_db_ensure_schema')) {
    lc_merchant_contract_db_ensure_schema();
}
if (function_exists('lc_merchant_contract_status_log_db_ensure_schema')) {
    lc_merchant_contract_status_log_db_ensure_schema();
}
if (function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
    lc_merchant_contract_addendum_db_ensure_schema();
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $mc_id = isset($_GET['mcId']) ? (int) $_GET['mcId'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

    if ($mc_id > 0) {
        // 상세 조회 시에도 ADV-0008 커스텀 계약 미적용이면 보정
        if (function_exists('lc_merchant_contract_custom_ensure_adv0008')) {
            $row = function_exists('lc_merchant_contract_get_by_id')
                ? lc_merchant_contract_get_by_id($mc_id)
                : null;
            $mt_id = is_array($row) ? (int) ($row['mc_mt_id'] ?? 0) : 0;
            if ($mt_id > 0) {
                $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
                $code = is_array($merchant) ? strtoupper((string) ($merchant['mt_code'] ?? '')) : '';
                if ($code === 'ADV-0008' || $mt_id === 8) {
                    lc_merchant_contract_custom_ensure_adv0008(false);
                }
            }
        }

        $detail = lc_merchant_contract_admin_detail_for_api($mc_id);
        if ($detail === null) {
            lc_api_error('계약서를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success($detail);
    }

    $filters = array(
        'q'          => isset($_GET['q']) ? (string) $_GET['q'] : '',
        'status'     => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'version'    => isset($_GET['version']) ? (string) $_GET['version'] : '',
        'mtId'       => isset($_GET['mtId']) ? (int) $_GET['mtId'] : 0,
        'signedFrom' => isset($_GET['signedFrom']) ? (string) $_GET['signedFrom'] : '',
        'signedTo'   => isset($_GET['signedTo']) ? (string) $_GET['signedTo'] : '',
        'page'       => isset($_GET['page']) ? (int) $_GET['page'] : 1,
        'limit'      => isset($_GET['limit']) ? (int) $_GET['limit'] : 30,
    );

    // ADV-0008(김장수) 모두의철거 첨부 계약서가 미적용이면 목록 조회 시 자동 적용
    $ensure_adv0008 = null;
    if (function_exists('lc_merchant_contract_custom_ensure_adv0008')) {
        $force_ensure = isset($_GET['ensureAdv0008']) && (string) $_GET['ensureAdv0008'] === '1';
        $ensure_adv0008 = lc_merchant_contract_custom_ensure_adv0008($force_ensure);
    }

    $data = lc_merchant_contract_admin_list_for_api($filters);
    $data['dbReady'] = lc_db_installed();
    $data['currentVersion'] = lc_merchant_contract_current_version();
    if (is_array($ensure_adv0008)) {
        $data['customEnsureAdv0008'] = $ensure_adv0008;
    }

    lc_api_success($data);
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'seed_demo') {
        if (!function_exists('lc_demo_contract_seed_for_merchant')) {
            if (is_file(LC_PLUGIN_PATH . '/inc/demo_seed.php')) {
                require_once LC_PLUGIN_PATH . '/inc/demo_seed.php';
            }
        }

        $mt_id = isset($body['mtId']) ? (int) $body['mtId'] : 0;
        if ($mt_id <= 0 && function_exists('lc_demo_default_advertiser_mb_id') && function_exists('lc_get_merchant_by_mb_id')) {
            $demo = lc_get_merchant_by_mb_id(lc_demo_default_advertiser_mb_id());
            if (is_array($demo)) {
                $mt_id = (int) ($demo['mt_id'] ?? 0);
            }
        }
        if ($mt_id <= 0) {
            lc_api_error('광고주 ID(mtId)가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_demo_contract_seed_for_merchant($mt_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'SEED_FAILED', 400);
        }

        lc_api_success(array(
            'message'      => $result['message'],
            'skipped'      => !empty($result['skipped']),
            'contractCode' => (string) ($result['contractCode'] ?? ''),
            'mtId'         => $mt_id,
        ));
    }

    if ($action === 'apply_custom_document') {
        if (!function_exists('lc_merchant_contract_admin_apply_custom_document')) {
            lc_api_error('커스텀 계약 모듈을 사용할 수 없습니다.', 'NOT_AVAILABLE', 500);
        }

        $result = lc_merchant_contract_admin_apply_custom_document(array(
            'mtId'         => isset($body['mtId']) ? (int) $body['mtId'] : 0,
            'mtCode'       => isset($body['mtCode']) ? (string) $body['mtCode'] : '',
            'documentKey'  => isset($body['documentKey']) ? (string) $body['documentKey'] : 'adv-0008-moduicheolge',
            'force'        => !empty($body['force']),
            'partyOverrides' => array(
                'company_name'        => '모두의철거',
                'representative_name' => '김장수',
                'signer_name'         => '김장수',
                'signer_position'     => '대표',
            ),
        ));

        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }

        $mc_id = (int) ($result['mcId'] ?? 0);
        lc_api_success(array(
            'message'      => $result['message'],
            'skipped'      => !empty($result['skipped']),
            'contractCode' => (string) ($result['contractCode'] ?? ''),
            'mtId'         => (int) ($result['mtId'] ?? 0),
            'mcId'         => $mc_id,
            'detail'       => $mc_id > 0 ? lc_merchant_contract_admin_detail_for_api($mc_id) : null,
        ));
    }

    $mc_id = isset($body['mcId']) ? (int) $body['mcId'] : 0;
    $reason = isset($body['reason']) ? (string) $body['reason'] : '';

    if ($action === 'add_addendum') {
        if ($mc_id <= 0) {
            lc_api_error('계약 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }
        global $member;
        $result = lc_merchant_contract_addendum_create($mc_id, array(
            'title'           => isset($body['title']) ? (string) $body['title'] : '특약사항',
            'body'            => isset($body['body']) ? (string) $body['body'] : '',
            'created_by_type' => 'admin',
            'created_by'      => is_array($member) ? (string) ($member['mb_id'] ?? '') : '',
        ));
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'ADDENDUM_FAILED', 400);
        }
        lc_api_success(array(
            'message'  => $result['message'],
            'addendum' => $result['addendum'] ?? null,
            'detail'   => lc_merchant_contract_admin_detail_for_api($mc_id),
        ));
    }

    if ($action === 'void_addendum') {
        $mca_id = isset($body['addendumId']) ? (int) $body['addendumId'] : 0;
        if ($mca_id <= 0) {
            lc_api_error('특약 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }
        $existing = lc_merchant_contract_addendum_get($mca_id);
        if (!is_array($existing)) {
            lc_api_error('특약을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $result = lc_merchant_contract_addendum_void($mca_id, $reason);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'VOID_FAILED', 400);
        }
        $owner_mc_id = (int) ($existing['mc_id'] ?? 0);
        lc_api_success(array(
            'message'  => $result['message'],
            'addendum' => $result['addendum'] ?? null,
            'detail'   => $owner_mc_id > 0 ? lc_merchant_contract_admin_detail_for_api($owner_mc_id) : null,
        ));
    }

    if ($mc_id <= 0) {
        lc_api_error('계약 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    $status_map = array(
        'approve'       => LC_MERCHANT_CONTRACT_STATUS_SIGNED,
        'reject'        => LC_MERCHANT_CONTRACT_STATUS_REJECTED,
        'cancel'        => LC_MERCHANT_CONTRACT_STATUS_CANCELLED,
        'expire'        => LC_MERCHANT_CONTRACT_STATUS_EXPIRED,
        'requireRenewal'=> LC_MERCHANT_CONTRACT_STATUS_RENEWAL,
    );

    if (!isset($status_map[$action])) {
        lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
    }

    $result = lc_merchant_contract_admin_update_status($mc_id, $status_map[$action], $reason);
    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'UPDATE_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'detail'  => lc_merchant_contract_admin_detail_for_api($mc_id),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
