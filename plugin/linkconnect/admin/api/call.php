<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_admin();
    $view = isset($_GET['view']) ? (string) $_GET['view'] : '';

    if ($view === 'numbers') {
        $filters = array();
        if (isset($_GET['status'])) {
            $filters['status'] = (string) $_GET['status'];
        }
        if (isset($_GET['q'])) {
            $filters['q'] = (string) $_GET['q'];
        }
        $rows = array_map('lc_call_number_to_api', lc_call_numbers_list($filters));
        lc_api_success(array('items' => $rows, 'dbReady' => lc_db_installed()));
    }

    if ($view === 'requests') {
        $filters = array();
        if (isset($_GET['status'])) {
            $filters['status'] = (string) $_GET['status'];
        }
        $rows = array_map('lc_call_request_to_api', lc_call_requests_list($filters));
        lc_api_success(array('items' => $rows, 'dbReady' => lc_db_installed()));
    }

    if ($view === 'logs') {
        $filters = array('limit' => 300);
        foreach (array('pt_id', 'cp_id', 'mt_id', 'result') as $k) {
            if (isset($_GET[$k]) && $_GET[$k] !== '') {
                $filters[$k] = $_GET[$k];
            }
        }
        if (isset($_GET['unmatched']) && $_GET['unmatched'] === '1') {
            $filters['unmatched'] = true;
        }
        $rows = array();
        foreach (lc_call_logs_list($filters) as $row) {
            $rows[] = lc_call_log_to_api($row, true, false);
        }
        lc_api_success(array('items' => $rows, 'dbReady' => lc_db_installed()));
    }

    if ($view === 'settings') {
        $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : 0;
        if ($cp_id <= 0) {
            lc_api_error('cpId가 필요합니다.', 'INVALID_ID', 400);
        }
        lc_api_success(array('settings' => lc_call_settings_get($cp_id)));
    }

    if ($view === 'recording') {
        $clog_id = isset($_GET['clogId']) ? (int) $_GET['clogId'] : 0;
        $res = lc_call_recording_url($clog_id);
        if (!$res['ok']) {
            lc_api_error($res['message'], 'NO_RECORDING', 404);
        }
        lc_api_success(array('url' => $res['url']));
    }

    if ($view === 'recording_requests') {
        $filters = array();
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = (string) $_GET['status'];
        }
        $rows = array();
        foreach (lc_call_recording_requests_list($filters) as $row) {
            $rows[] = lc_call_recording_request_to_api($row, true);
        }
        lc_api_success(array(
            'items'       => $rows,
            'dbReady'     => lc_db_installed(),
            'isSuperAdmin' => function_exists('lc_is_super_admin') ? lc_is_super_admin() : false,
        ));
    }

    lc_api_error('유효하지 않은 view입니다.', 'INVALID_VIEW', 400);
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    $is_multipart = !empty($_FILES);
    $body = $is_multipart ? $_POST : lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    if ($action === '' && $is_multipart) {
        $action = 'import_logs';
    }

    if ($action === 'create_number') {
        $result = lc_call_number_create(array(
            'number'   => $body['number'] ?? '',
            'provider' => $body['provider'] ?? '',
            'providerNumberId' => $body['providerNumberId'] ?? '',
            'memo'     => $body['memo'] ?? '',
        ));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_create_number', 'call_number', (int) ($result['cnId'] ?? 0), (string) ($result['message'] ?? ''), array(
                'number' => (string) ($body['number'] ?? ''),
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'CREATE_FAILED', 400);
    }

    if ($action === 'update_number') {
        $result = lc_call_number_update((int) ($body['cnId'] ?? 0), array(
            'status' => $body['status'] ?? null,
            'memo'   => $body['memo'] ?? null,
            'providerNumberId' => $body['providerNumberId'] ?? null,
        ));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_update_number', 'call_number', (int) ($body['cnId'] ?? 0), (string) ($result['message'] ?? ''), array(
                'status' => (string) ($body['status'] ?? ''),
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'UPDATE_FAILED', 400);
    }

    if ($action === 'assign_request') {
        $car_id = (int) ($body['carId'] ?? 0);
        $price = (int) ($body['price'] ?? 0);
        $request = lc_call_request_get($car_id);
        if (!$request) {
            lc_api_error('신청 내역을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        if ($price <= 0) {
            lc_api_error('콜당 디비 단가를 입력하세요.', 'INVALID_PRICE', 400);
        }

        $result = lc_call_request_assign($car_id, (int) ($body['cnId'] ?? 0), (string) ($body['adminMemo'] ?? ''));
        if ($result['ok']) {
            $price_result = lc_call_assign_apply_price((int) ($request['cp_id'] ?? 0), $price);
            if (!$price_result['ok']) {
                lc_api_error($price_result['message'], 'PRICE_SAVE_FAILED', 400);
            }
        }
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_assign_request', 'call_request', $car_id, (string) ($result['message'] ?? ''), array(
                'cnId' => (int) ($body['cnId'] ?? 0),
                'price' => $price,
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'ASSIGN_FAILED', 400);
    }

    if ($action === 'assign_direct') {
        $cp_id = (int) ($body['cpId'] ?? 0);
        $price = (int) ($body['price'] ?? 0);
        if ($price <= 0) {
            lc_api_error('콜당 디비 단가를 입력하세요.', 'INVALID_PRICE', 400);
        }

        $result = lc_call_request_assign_direct(
            (int) ($body['ptId'] ?? 0),
            $cp_id,
            (int) ($body['cnId'] ?? 0),
            (string) ($body['adminMemo'] ?? '')
        );
        if ($result['ok']) {
            $price_result = lc_call_assign_apply_price($cp_id, $price);
            if (!$price_result['ok']) {
                lc_api_error($price_result['message'], 'PRICE_SAVE_FAILED', 400);
            }
        }
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_assign_direct', 'call_request', (int) ($result['carId'] ?? 0), (string) ($result['message'] ?? ''), array(
                'ptId' => (int) ($body['ptId'] ?? 0),
                'cpId' => $cp_id,
                'cnId' => (int) ($body['cnId'] ?? 0),
                'price' => $price,
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'ASSIGN_FAILED', 400);
    }

    if ($action === 'import_logs') {
        $file_key = '';
        foreach (array('file', 'csv', 'excel') as $key) {
            if (!empty($_FILES[$key]) && is_array($_FILES[$key]) && (int) ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $file_key = $key;
                break;
            }
        }
        if ($file_key === '') {
            lc_api_error('업로드 파일이 필요합니다.', 'NO_FILE', 400);
        }

        $file = $_FILES[$file_key];
        $parsed = lc_call_logs_import_parse_file($file['tmp_name'], (string) ($file['name'] ?? 'upload.csv'));
        if (!$parsed['ok']) {
            lc_api_error($parsed['message'], 'PARSE_FAILED', 400);
        }

        $dry_run = !empty($body['dryRun']) || (isset($body['dryRun']) && (string) $body['dryRun'] === '1');
        if ($dry_run) {
            lc_api_success(array(
                'message' => $parsed['message'],
                'dryRun'  => true,
                'total'   => count($parsed['rows'] ?? array()),
                'headers' => $parsed['headers'] ?? array(),
                'preview' => array_slice($parsed['rows'] ?? array(), 0, 5),
            ));
        }

        $skip_conversion = !empty($body['skipConversion']) || (isset($body['skipConversion']) && (string) $body['skipConversion'] === '1');
        $result = lc_call_logs_import_bulk($parsed['rows'] ?? array(), $skip_conversion);
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_import_logs', 'call_log', 0, (string) ($result['message'] ?? '통화내역 업로드'), array(
                'total'     => (int) ($result['total'] ?? 0),
                'imported'  => (int) ($result['imported'] ?? 0),
                'duplicate' => (int) ($result['duplicate'] ?? 0),
                'failed'    => (int) ($result['failed'] ?? 0),
                'unmatched' => (int) ($result['unmatched'] ?? 0),
                'skipConversion' => $skip_conversion,
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'IMPORT_FAILED', 400);
    }

    if ($action === 'reject_request') {
        $result = lc_call_request_reject((int) ($body['carId'] ?? 0), (string) ($body['adminMemo'] ?? ''));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_reject_request', 'call_request', (int) ($body['carId'] ?? 0), (string) ($result['message'] ?? ''));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'REJECT_FAILED', 400);
    }

    if ($action === 'revoke_request') {
        $result = lc_call_request_revoke((int) ($body['carId'] ?? 0), (string) ($body['adminMemo'] ?? ''));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_revoke_request', 'call_request', (int) ($body['carId'] ?? 0), (string) ($result['message'] ?? ''));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'REVOKE_FAILED', 400);
    }

    if ($action === 'save_settings') {
        $price = (int) ($body['price'] ?? 0);
        if ($price <= 0) {
            lc_api_error('콜당 디비 단가를 입력하세요.', 'INVALID_PRICE', 400);
        }
        $body['adminEnabled'] = true;
        $result = lc_call_settings_save((int) ($body['cpId'] ?? 0), $body, 'admin');
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_save_settings', 'campaign', (int) ($body['cpId'] ?? 0), (string) ($result['message'] ?? ''));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    if ($action === 'final_status') {
        $result = lc_conversion_admin_final_status((int) ($body['cvId'] ?? 0), (string) ($body['finalAction'] ?? ''), (string) ($body['memo'] ?? ''));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_final_status', 'conversion', (int) ($body['cvId'] ?? 0), (string) ($result['message'] ?? ''), array(
                'finalAction' => (string) ($body['finalAction'] ?? ''),
            ));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'FINAL_FAILED', 400);
    }

    if ($action === 'upload_recording_wav') {
        $file_key = '';
        foreach (array('wav', 'file') as $key) {
            if (!empty($_FILES[$key]) && is_array($_FILES[$key]) && (int) ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $file_key = $key;
                break;
            }
        }
        if ($file_key === '') {
            lc_api_error('WAV 파일이 필요합니다.', 'NO_FILE', 400);
        }
        $result = lc_call_recording_request_upload_wav(
            (int) ($body['crrId'] ?? 0),
            $_FILES[$file_key],
            (string) ($body['adminMemo'] ?? '')
        );
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_recording_upload', 'call_recording_request', (int) ($body['crrId'] ?? 0), (string) ($result['message'] ?? ''));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'UPLOAD_FAILED', 400);
    }

    if ($action === 'reject_recording_request') {
        $result = lc_call_recording_request_reject((int) ($body['crrId'] ?? 0), (string) ($body['adminMemo'] ?? ''));
        if ($result['ok'] && function_exists('lc_admin_log_write')) {
            lc_admin_log_write('call_recording_reject', 'call_recording_request', (int) ($body['crrId'] ?? 0), (string) ($result['message'] ?? ''));
        }
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'REJECT_FAILED', 400);
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
