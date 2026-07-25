<?php
require_once dirname(__DIR__) . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_method('GET');
    lc_inquiry_ensure_schema();

    $action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
    $code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
    $email = isset($_GET['email']) ? trim((string) $_GET['email']) : '';

    if ($action === 'lookup' || ($code !== '' && $email !== '')) {
        $result = lc_inquiry_public_lookup($code, $email);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item' => lc_inquiry_to_api($result['inquiry'], true),
        ));
    }

    if ($action === 'mine') {
        $member = isset($GLOBALS['member']) && is_array($GLOBALS['member']) ? $GLOBALS['member'] : null;
        $mb_id = is_array($member) ? trim((string) ($member['mb_id'] ?? '')) : '';
        if ($mb_id === '') {
            lc_api_error('로그인이 필요합니다.', 'LOGIN_REQUIRED', 401);
        }

        $filters = array(
            'center' => 'public',
            'mb_id'  => $mb_id,
        );
        lc_api_success(array(
            'summary' => lc_inquiry_summary($filters),
            'items'   => array_map(function ($row) {
                return lc_inquiry_to_api($row, false);
            }, lc_inquiry_list($filters, 50)),
        ));
    }

    $iq_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($iq_id > 0) {
        $member = isset($GLOBALS['member']) && is_array($GLOBALS['member']) ? $GLOBALS['member'] : null;
        $mb_id = is_array($member) ? trim((string) ($member['mb_id'] ?? '')) : '';
        if ($mb_id === '') {
            lc_api_error('로그인이 필요합니다.', 'LOGIN_REQUIRED', 401);
        }
        $row = lc_inquiry_get_by_id($iq_id);
        if (!$row || (string) ($row['iq_center'] ?? '') !== 'public' || (string) ($row['mb_id'] ?? '') !== $mb_id) {
            lc_api_error('문의를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item' => lc_inquiry_to_api($row, true),
        ));
    }

    lc_api_error('조회 조건이 필요합니다.', 'INVALID_REQUEST', 400);
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    lc_inquiry_ensure_schema();

    $is_multipart = !empty($_FILES) || (isset($_SERVER['CONTENT_TYPE']) && stripos((string) $_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);
    $body = $is_multipart ? $_POST : lc_api_read_json_body();
    if (!is_array($body)) {
        $body = array();
    }

    // post_max_size 초과 등으로 본문·파일이 모두 비는 경우
    $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($is_multipart && $content_length > 0 && empty($_POST) && empty($_FILES)) {
        lc_api_error('업로드 용량이 서버 제한을 초과했을 수 있습니다. 파일 크기를 줄여 다시 시도해 주세요. (최대 10MB)', 'UPLOAD_TOO_LARGE', 400);
    }

    // honeypot (일반 문의·입점 공통)
    if (!empty($body['website']) || !empty($body['companyUrl'])) {
        lc_api_success(array(
            'message' => '문의가 등록되었습니다.',
            'item'    => array(
                'id'     => 'IQ-' . date('ymd') . '-0000',
                'iqId'   => 0,
                'status' => '답변대기',
            ),
        ));
    }

    $member = isset($GLOBALS['member']) && is_array($GLOBALS['member']) ? $GLOBALS['member'] : null;
    $mb_id = is_array($member) ? trim((string) ($member['mb_id'] ?? '')) : '';

    $form_type = isset($body['formType']) ? trim((string) $body['formType']) : '';
    if ($form_type === 'advertiser_apply' || (!empty($body['companyName']) && $is_multipart)) {
        $file = null;
        if (!empty($_FILES['attachment']) && is_array($_FILES['attachment'])) {
            $file = $_FILES['attachment'];
        } elseif (!empty($_FILES['bizLicense']) && is_array($_FILES['bizLicense'])) {
            $file = $_FILES['bizLicense'];
        }

        // 파일이 선택됐는데 서버에 안 도착한 경우 (용량·네트워크)
        if ($file === null && $form_type === 'advertiser_apply') {
            lc_api_error('사업자등록증 첨부는 필수입니다. 파일이 전송되지 않았습니다.', 'ATTACHMENT_REQUIRED', 400);
        }

        $result = lc_inquiry_create_advertiser_apply(array(
            'mb_id'        => $mb_id,
            'companyName'  => $body['companyName'] ?? '',
            'contactName'  => $body['contactName'] ?? '',
            'contactPhone' => $body['contactPhone'] ?? '',
            'contactEmail' => $body['contactEmail'] ?? '',
            'homepage'     => $body['homepage'] ?? '',
            'industry'     => $body['industry'] ?? '',
            'adMethod'     => $body['adMethod'] ?? '',
            'message'      => $body['message'] ?? '',
        ), $file);

        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'INQUIRY_FAILED', 400);
        }

        lc_api_success(array(
            'message'  => $result['message'],
            'item'     => lc_inquiry_to_api($result['inquiry'], true),
            'mailSent' => !empty($result['mailSent']),
        ));
    }

    $contact_name = trim((string) ($body['contactName'] ?? ''));
    $contact_email = trim((string) ($body['contactEmail'] ?? ''));
    $contact_phone = trim((string) ($body['contactPhone'] ?? ''));

    if ($contact_name === '' && is_array($member)) {
        $contact_name = trim((string) ($member['mb_name'] ?? $member['mb_nick'] ?? ''));
    }
    if ($contact_email === '' && is_array($member)) {
        $contact_email = trim((string) ($member['mb_email'] ?? ''));
    }

    $result = lc_inquiry_create(array(
        'center'       => 'public',
        'mb_id'        => $mb_id,
        'category'     => $body['category'] ?? '',
        'subject'      => $body['subject'] ?? '',
        'body'         => $body['body'] ?? '',
        'contactName'  => $contact_name,
        'contactEmail' => $contact_email,
        'contactPhone' => $contact_phone,
    ));

    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'INQUIRY_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'item'    => lc_inquiry_to_api($result['inquiry'], true),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
