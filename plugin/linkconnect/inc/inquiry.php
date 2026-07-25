<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('LC_INQUIRY_WAITING', 'waiting');
define('LC_INQUIRY_OPEN', 'open');
define('LC_INQUIRY_PROCESSING', 'processing');
define('LC_INQUIRY_HOLD', 'hold');
define('LC_INQUIRY_CLOSED', 'closed');

if (!function_exists('lc_inquiry_status_label')) {
    function lc_inquiry_status_label($status)
    {
        $labels = array(
            LC_INQUIRY_WAITING    => '답변대기',
            LC_INQUIRY_OPEN       => '접수완료',
            LC_INQUIRY_PROCESSING => '처리중',
            LC_INQUIRY_HOLD       => '보류',
            LC_INQUIRY_CLOSED     => '답변완료',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_inquiry_generate_code')) {
    function lc_inquiry_generate_code()
    {
        return 'IQ-' . date('ymd') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_inquiry_ensure_schema')) {
    function lc_inquiry_ensure_schema()
    {
        if (!lc_db_installed()) {
            return;
        }

        $table = lc_table('inquiries');
        if (!lc_db_table_exists($table)) {
            return;
        }

        $cols = array(
            'iq_contact_name'      => "varchar(100) NOT NULL DEFAULT '' AFTER `iq_cv_code`",
            'iq_contact_email'     => "varchar(120) NOT NULL DEFAULT '' AFTER `iq_contact_name`",
            'iq_contact_phone'     => "varchar(40) NOT NULL DEFAULT '' AFTER `iq_contact_email`",
            'iq_attachment_path'   => "varchar(500) NOT NULL DEFAULT '' AFTER `iq_contact_phone`",
            'iq_attachment_name'   => "varchar(255) NOT NULL DEFAULT '' AFTER `iq_attachment_path`",
            'iq_attachment_mime'   => "varchar(120) NOT NULL DEFAULT '' AFTER `iq_attachment_name`",
        );
        foreach ($cols as $col => $definition) {
            if (!lc_db_column_exists($table, $col)) {
                lc_sql_query(" ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition} ", false);
            }
        }
    }
}

if (!function_exists('lc_inquiry_attachment_dir')) {
    function lc_inquiry_attachment_dir()
    {
        if (defined('G5_DATA_PATH') && (string) G5_DATA_PATH !== '') {
            return rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/inquiry_attachments';
        }

        return dirname(__DIR__) . '/data/inquiry_attachments';
    }
}

if (!function_exists('lc_inquiry_attachment_ensure_dir')) {
    function lc_inquiry_attachment_ensure_dir()
    {
        $dir = lc_inquiry_attachment_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\nDeny from all\n");
        }

        return is_dir($dir) && is_writable($dir);
    }
}

if (!function_exists('lc_inquiry_format_advertiser_apply_body')) {
    /**
     * @param array<string,string> $fields
     */
    function lc_inquiry_format_advertiser_apply_body(array $fields)
    {
        $lines = array(
            '<링크커넥트 광고주 입점 신청>',
            '업체명: ' . trim((string) ($fields['companyName'] ?? '')),
            '담당자명: ' . trim((string) ($fields['contactName'] ?? '')),
            '연락처: ' . trim((string) ($fields['contactPhone'] ?? '')),
            '이메일: ' . trim((string) ($fields['contactEmail'] ?? '')),
            '홈페이지 또는 랜딩페이지: ' . trim((string) ($fields['homepage'] ?? '')),
            '광고 업종: ' . trim((string) ($fields['industry'] ?? '')),
            '희망 광고 방식 (CPA / CPS): ' . trim((string) ($fields['adMethod'] ?? '')),
            '',
            '간단한 소개 및 문의 내용:',
            trim((string) ($fields['message'] ?? '')),
        );

        return implode("\n", $lines);
    }
}

if (!function_exists('lc_inquiry_store_attachment')) {
    /**
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     * @return array{ok:bool,message:string,path:string,name:string,mime:string}
     */
    function lc_inquiry_store_attachment(array $file, $iq_id)
    {
        $empty = array('ok' => false, 'message' => '', 'path' => '', 'name' => '', 'mime' => '');
        $iq_id = (int) $iq_id;
        if ($iq_id <= 0) {
            $empty['message'] = '문의 ID가 올바르지 않습니다.';

            return $empty;
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $empty['message'] = '첨부파일을 업로드하지 못했습니다.';

            return $empty;
        }
        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            $empty['message'] = '첨부파일 업로드 오류가 발생했습니다.';

            return $empty;
        }

        $max_bytes = 10 * 1024 * 1024;
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0 || $size > $max_bytes) {
            $empty['message'] = '첨부파일은 10MB 이하만 가능합니다.';

            return $empty;
        }

        $original = isset($file['name']) ? (string) $file['name'] : 'attachment';
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed_ext = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($ext, $allowed_ext, true)) {
            $empty['message'] = '사업자등록증은 PDF 또는 이미지(jpg, png 등)만 첨부할 수 있습니다.';

            return $empty;
        }

        $finfo_mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $finfo_mime = (string) finfo_file($fi, $file['tmp_name']);
                finfo_close($fi);
            }
        }
        $allowed_mime = array(
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        );
        if ($finfo_mime !== '' && !in_array($finfo_mime, $allowed_mime, true)) {
            $empty['message'] = '허용되지 않는 첨부파일 형식입니다.';

            return $empty;
        }

        if (!lc_inquiry_attachment_ensure_dir()) {
            $empty['message'] = '첨부파일 저장 폴더를 준비하지 못했습니다.';

            return $empty;
        }

        $subdir = lc_inquiry_attachment_dir() . '/' . $iq_id;
        if (!is_dir($subdir) && !@mkdir($subdir, 0755, true)) {
            $empty['message'] = '첨부파일 저장 폴더를 만들지 못했습니다.';

            return $empty;
        }

        $safe_base = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($original, PATHINFO_FILENAME));
        if ($safe_base === '' || $safe_base === null) {
            $safe_base = 'biz_reg';
        }
        $stored_name = $safe_base . '_' . date('YmdHis') . '.' . $ext;
        $full = $subdir . '/' . $stored_name;
        if (!@move_uploaded_file($file['tmp_name'], $full)) {
            $empty['message'] = '첨부파일을 저장하지 못했습니다.';

            return $empty;
        }

        $relative = 'linkconnect/inquiry_attachments/' . $iq_id . '/' . $stored_name;

        return array(
            'ok'      => true,
            'message' => '',
            'path'    => $relative,
            'name'    => $original,
            'mime'    => $finfo_mime !== '' ? $finfo_mime : (string) ($file['type'] ?? ''),
        );
    }
}

if (!function_exists('lc_inquiry_create_advertiser_apply')) {
    /**
     * 광고주 입점 신청 (공개 문의 + 사업자등록증 첨부)
     *
     * @param array<string,mixed> $payload
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int}|null $file
     * @return array{ok:bool,message:string,inquiry:array|null}
     */
    function lc_inquiry_create_advertiser_apply(array $payload, $file = null)
    {
        $company = trim((string) ($payload['companyName'] ?? ''));
        $contact_name = trim((string) ($payload['contactName'] ?? ''));
        $contact_phone = trim((string) ($payload['contactPhone'] ?? ''));
        $contact_email = trim((string) ($payload['contactEmail'] ?? ''));
        $homepage = trim((string) ($payload['homepage'] ?? ''));
        $industry = trim((string) ($payload['industry'] ?? ''));
        $ad_method = trim((string) ($payload['adMethod'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));

        if ($company === '' || $contact_name === '' || $contact_phone === '' || $contact_email === '') {
            return array('ok' => false, 'message' => '업체명, 담당자명, 연락처, 이메일은 필수입니다.', 'inquiry' => null);
        }
        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'message' => '올바른 이메일 주소를 입력해주세요.', 'inquiry' => null);
        }
        if ($homepage === '' || $industry === '' || $ad_method === '' || $message === '') {
            return array('ok' => false, 'message' => '홈페이지, 광고 업종, 희망 광고 방식, 소개/문의 내용은 필수입니다.', 'inquiry' => null);
        }
        if (!in_array($ad_method, array('CPA', 'CPS', 'CPA/CPS'), true)) {
            return array('ok' => false, 'message' => '희망 광고 방식을 선택해주세요. (CPA / CPS)', 'inquiry' => null);
        }
        if (!is_array($file) || empty($file['tmp_name'])) {
            $upload_err = is_array($file) ? (int) ($file['error'] ?? 0) : -1;
            if ($upload_err === UPLOAD_ERR_INI_SIZE || $upload_err === UPLOAD_ERR_FORM_SIZE) {
                return array('ok' => false, 'message' => '사업자등록증 파일이 너무 큽니다. 10MB 이하로 첨부해 주세요.', 'inquiry' => null);
            }

            return array('ok' => false, 'message' => '사업자등록증 첨부는 필수입니다.', 'inquiry' => null);
        }

        $body = lc_inquiry_format_advertiser_apply_body(array(
            'companyName'  => $company,
            'contactName'  => $contact_name,
            'contactPhone' => $contact_phone,
            'contactEmail' => $contact_email,
            'homepage'     => $homepage,
            'industry'     => $industry,
            'adMethod'     => $ad_method,
            'message'      => $message,
        ));

        $result = lc_inquiry_create(array(
            'center'       => 'public',
            'mb_id'        => (string) ($payload['mb_id'] ?? ''),
            'category'     => '광고주 가입',
            'subject'      => '[입점신청] ' . $company,
            'body'         => $body,
            'contactName'  => $contact_name,
            'contactEmail' => $contact_email,
            'contactPhone' => $contact_phone,
            'campaign'     => $ad_method,
            'skipNotify'   => true,
        ));

        if (empty($result['ok']) || !is_array($result['inquiry'])) {
            return $result;
        }

        $iq_id = (int) ($result['inquiry']['iq_id'] ?? 0);
        $stored = lc_inquiry_store_attachment($file, $iq_id);
        if (empty($stored['ok'])) {
            // 문의는 남기고 첨부 실패만 안내 — 재첨부 유도보다 일관성 위해 실패 처리
            $table = lc_table('inquiries');
            lc_sql_query(" DELETE FROM `{$table}` WHERE iq_id = '{$iq_id}' LIMIT 1 ", false);

            return array('ok' => false, 'message' => $stored['message'], 'inquiry' => null);
        }

        $table = lc_table('inquiries');
        lc_sql_query(" UPDATE `{$table}` SET
            iq_attachment_path = '" . lc_sql_escape($stored['path']) . "',
            iq_attachment_name = '" . lc_sql_escape($stored['name']) . "',
            iq_attachment_mime = '" . lc_sql_escape($stored['mime']) . "'
            WHERE iq_id = '{$iq_id}' ", false);

        $inquiry = lc_inquiry_get_by_id($iq_id);
        // 사업자등록증 첨부 완료 후 support2580_@linkconnect.co.kr 로 알림
        $mail_sent = false;
        if (is_array($inquiry)) {
            $mail_sent = lc_inquiry_notify_admin_new($inquiry);
        }

        return array(
            'ok'       => true,
            'message'  => '광고주 입점 신청이 접수되었습니다. 검토 후 안내드리겠습니다.',
            'inquiry'  => $inquiry,
            'mailSent' => $mail_sent,
        );
    }
}

if (!function_exists('lc_inquiry_attachment_full_path')) {
    function lc_inquiry_attachment_full_path($relative)
    {
        $relative = trim((string) $relative);
        if ($relative === '' || strpos($relative, '..') !== false) {
            return '';
        }
        if (defined('G5_DATA_PATH') && (string) G5_DATA_PATH !== '') {
            return rtrim((string) G5_DATA_PATH, '/') . '/' . ltrim($relative, '/');
        }

        return dirname(__DIR__) . '/data/' . ltrim(preg_replace('#^linkconnect/#', '', $relative), '/');
    }
}

if (!function_exists('lc_inquiry_get_by_id')) {
    function lc_inquiry_get_by_id($iq_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $iq_id = (int) $iq_id;
        $table = lc_table('inquiries');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE iq_id = '{$iq_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_inquiry_get_by_code')) {
    function lc_inquiry_get_by_code($code)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $table = lc_table('inquiries');
        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE iq_code = '" . lc_sql_escape($code) . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_inquiry_mailer_ready')) {
    function lc_inquiry_mailer_ready()
    {
        if (!function_exists('mailer') && defined('G5_LIB_PATH') && is_file(G5_LIB_PATH . '/mailer.lib.php')) {
            include_once G5_LIB_PATH . '/mailer.lib.php';
        }

        return function_exists('mailer');
    }
}

if (!function_exists('lc_inquiry_admin_recipient_email')) {
    /**
     * 문의·입점신청 관리자 수신 메일 (고정)
     */
    function lc_inquiry_admin_recipient_email()
    {
        return 'support2580_@linkconnect.co.kr';
    }
}

if (!function_exists('lc_inquiry_center_label')) {
    function lc_inquiry_center_label($center)
    {
        $center = (string) $center;
        if ($center === 'partner') {
            return '파트너';
        }
        if ($center === 'merchant') {
            return '광고주';
        }
        if ($center === 'public') {
            return '공개';
        }
        return $center;
    }
}

if (!function_exists('lc_inquiry_send_admin_email')) {
    function lc_inquiry_send_admin_email(array $inquiry)
    {
        if (!lc_inquiry_mailer_ready()) {
            return false;
        }

        global $config;

        $from_name = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $admin_email = lc_inquiry_admin_recipient_email();
        // 입점/문의 알림은 운영 메일 도메인으로 From/To 통일 (발송 실패 감소)
        $from_email = $admin_email;
        if (!empty($config['cf_admin_email']) && filter_var((string) $config['cf_admin_email'], FILTER_VALIDATE_EMAIL)) {
            // 그누보드 관리자 메일이 같은 도메인이면 From으로 사용
            $cf = (string) $config['cf_admin_email'];
            if (stripos($cf, '@linkconnect.co.kr') !== false) {
                $from_email = $cf;
            }
        }

        if ($admin_email === '' || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $from_email = $admin_email;
        }

        $code = (string) ($inquiry['iq_code'] ?? '');
        $subject_text = (string) ($inquiry['iq_subject'] ?? '');
        $center_label = lc_inquiry_center_label($inquiry['iq_center'] ?? '');
        $category = (string) ($inquiry['iq_category'] ?? '');
        $is_advertiser_apply = ($category === '광고주 가입' || strpos($subject_text, '[입점신청]') === 0);
        $author = (string) ($inquiry['iq_contact_name'] ?? '');
        $email = (string) ($inquiry['iq_contact_email'] ?? '');
        $phone = (string) ($inquiry['iq_contact_phone'] ?? '');
        $body_text = (string) ($inquiry['iq_body'] ?? '');
        $attach_name = (string) ($inquiry['iq_attachment_name'] ?? '');
        $admin_url = (defined('G5_URL') ? G5_URL : '') . '/admin/support';

        $esc = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };

        $subject = $is_advertiser_apply
            ? '[' . $from_name . '] 광고주 입점 신청 접수: ' . preg_replace('/^\[입점신청\]\s*/u', '', $subject_text)
            : '[' . $from_name . '] 신규 문의 접수: ' . $subject_text;
        $html = $is_advertiser_apply
            ? '<p><strong>광고주 입점 신청이 접수되었습니다.</strong></p>'
            : '<p><strong>새 문의가 접수되었습니다.</strong></p>';
        $html .= '<table style="border-collapse:collapse;width:100%;max-width:560px;font-size:14px;">';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">문의번호</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($code) . '</td></tr>';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">구분</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($center_label) . '</td></tr>';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">유형</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($category) . '</td></tr>';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">제목</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($subject_text) . '</td></tr>';
        if ($author !== '') {
            $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">이름</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($author) . '</td></tr>';
        }
        if ($email !== '') {
            $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">이메일</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($email) . '</td></tr>';
        }
        if ($phone !== '') {
            $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">연락처</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($phone) . '</td></tr>';
        }
        if ($attach_name !== '') {
            $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">첨부</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($attach_name) . ' (관리자 문의에서 확인)</td></tr>';
        }
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;vertical-align:top;">내용</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . nl2br($esc($body_text)) . '</td></tr>';
        $html .= '</table>';
        $html .= '<p style="margin-top:16px;"><a href="' . $esc($admin_url) . '">관리자에서 문의 확인</a></p>';
        $html .= '<p style="margin-top:8px;color:#64748b;font-size:12px;">수신: ' . $esc($admin_email) . '</p>';

        try {
            return (bool) @mailer($from_name, $from_email, $admin_email, $subject, $html, 1);
        } catch (Throwable $e) {
            error_log('[LinkConnect Inquiry] admin mail failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('lc_inquiry_send_reply_email')) {
    function lc_inquiry_send_reply_email(array $inquiry)
    {
        if (!lc_inquiry_mailer_ready()) {
            return false;
        }

        $to = strtolower(trim((string) ($inquiry['iq_contact_email'] ?? '')));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        global $config;

        $from_name = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $from_email = !empty($config['cf_admin_email']) ? (string) $config['cf_admin_email'] : (function_exists('lc_contact_email') ? lc_contact_email() : '');
        if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $esc = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };

        $code = (string) ($inquiry['iq_code'] ?? '');
        $subject_text = (string) ($inquiry['iq_subject'] ?? '');
        $reply = (string) ($inquiry['iq_reply'] ?? '');
        $name = (string) ($inquiry['iq_contact_name'] ?? '');
        $lookup_url = (defined('G5_URL') ? G5_URL : '') . '/inquiry';

        $subject = '[' . $from_name . '] 문의 답변이 등록되었습니다';
        $html = '<p>' . ($name !== '' ? $esc($name) . '님, ' : '') . '문의하신 내용에 대한 답변이 등록되었습니다.</p>';
        $html .= '<table style="border-collapse:collapse;width:100%;max-width:560px;font-size:14px;">';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">문의번호</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($code) . '</td></tr>';
        $html .= '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">제목</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . $esc($subject_text) . '</td></tr>';
        $html .= '</table>';
        $html .= '<div style="margin-top:16px;padding:14px 16px;background:#f0fdff;border:1px solid #cffafe;border-radius:10px;">';
        $html .= '<div style="font-weight:bold;color:#0e7490;margin-bottom:8px;">관리자 답변</div>';
        $html .= '<div style="color:#334155;">' . nl2br($esc($reply)) . '</div>';
        $html .= '</div>';
        $html .= '<p style="margin-top:16px;"><a href="' . $esc($lookup_url) . '">문의 내역 확인하기</a> (문의번호와 이메일로 조회)</p>';

        try {
            return (bool) @mailer($from_name, $from_email, $to, $subject, $html, 1);
        } catch (Throwable $e) {
            error_log('[LinkConnect Inquiry] reply mail failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('lc_inquiry_notify_admin_new')) {
    /**
     * @return bool 관리자 메일 발송 성공 여부 (인앱 알림과 무관)
     */
    function lc_inquiry_notify_admin_new(array $inquiry)
    {
        if (function_exists('lc_notification_create')) {
            $code = (string) ($inquiry['iq_code'] ?? '');
            $subject = (string) ($inquiry['iq_subject'] ?? '');
            $center_label = lc_inquiry_center_label($inquiry['iq_center'] ?? '');
            $iq_id = (int) ($inquiry['iq_id'] ?? 0);

            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'inquiry',
                'title'   => '신규 문의 접수',
                'body'    => $center_label . ' · ' . ($code !== '' ? $code . ' · ' : '') . $subject,
                'link'    => '/admin/support',
                'refType' => 'inquiry',
                'refId'   => $iq_id,
            ));
        }

        $mail_ok = lc_inquiry_send_admin_email($inquiry);
        if (!$mail_ok) {
            error_log('[LinkConnect Inquiry] admin email failed for ' . (string) ($inquiry['iq_code'] ?? '') . ' → ' . lc_inquiry_admin_recipient_email());
        }

        return (bool) $mail_ok;
    }
}

if (!function_exists('lc_inquiry_create')) {
    /**
     * @return array{ok:bool,message:string,inquiry:array|null}
     */
    function lc_inquiry_create(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'inquiry' => null);
        }

        lc_inquiry_ensure_schema();

        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $category = trim((string) ($payload['category'] ?? ''));
        if ($subject === '' || $body === '' || $category === '') {
            return array('ok' => false, 'message' => '문의 유형, 제목, 내용은 필수입니다.', 'inquiry' => null);
        }

        $center = trim((string) ($payload['center'] ?? ''));
        $pt_id = (int) ($payload['pt_id'] ?? 0);
        $mt_id = (int) ($payload['mt_id'] ?? 0);
        $mb_id = trim((string) ($payload['mb_id'] ?? ''));
        $contact_name = trim((string) ($payload['contactName'] ?? ''));
        $contact_email = trim((string) ($payload['contactEmail'] ?? ''));
        $contact_phone = trim((string) ($payload['contactPhone'] ?? ''));

        if ($center === 'public') {
            if ($contact_name === '' || $contact_email === '') {
                return array('ok' => false, 'message' => '이름과 이메일은 필수입니다.', 'inquiry' => null);
            }
            if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                return array('ok' => false, 'message' => '올바른 이메일 주소를 입력해주세요.', 'inquiry' => null);
            }
        }

        $table = lc_table('inquiries');
        $code = lc_inquiry_generate_code();

        lc_sql_query(" INSERT INTO `{$table}` SET
            iq_code = '" . lc_sql_escape($code) . "',
            pt_id = '{$pt_id}',
            mt_id = '{$mt_id}',
            mb_id = '" . lc_sql_escape($mb_id) . "',
            iq_center = '" . lc_sql_escape($center) . "',
            iq_category = '" . lc_sql_escape($category) . "',
            iq_subject = '" . lc_sql_escape($subject) . "',
            iq_body = '" . lc_sql_escape($body) . "',
            iq_campaign = '" . lc_sql_escape($payload['campaign'] ?? '') . "',
            iq_cv_code = '" . lc_sql_escape($payload['cvCode'] ?? '') . "',
            iq_contact_name = '" . lc_sql_escape($contact_name) . "',
            iq_contact_email = '" . lc_sql_escape($contact_email) . "',
            iq_contact_phone = '" . lc_sql_escape($contact_phone) . "',
            iq_status = '" . lc_sql_escape(LC_INQUIRY_WAITING) . "',
            iq_created_at = NOW() ", false);

        $iq_id = (int) lc_sql_insert_id();
        if ($iq_id <= 0) {
            return array('ok' => false, 'message' => '문의 등록에 실패했습니다.', 'inquiry' => null);
        }

        $inquiry = lc_inquiry_get_by_id($iq_id);
        if (is_array($inquiry) && empty($payload['skipNotify'])) {
            lc_inquiry_notify_admin_new($inquiry);
        }

        return array(
            'ok'      => true,
            'message' => '문의가 등록되었습니다.',
            'inquiry' => $inquiry,
        );
    }
}

if (!function_exists('lc_inquiry_public_lookup')) {
    /**
     * @return array{ok:bool,message:string,inquiry:array|null}
     */
    function lc_inquiry_public_lookup($code, $email)
    {
        lc_inquiry_ensure_schema();

        $code = trim((string) $code);
        $email = strtolower(trim((string) $email));
        if ($code === '' || $email === '') {
            return array('ok' => false, 'message' => '문의번호와 이메일을 입력해주세요.', 'inquiry' => null);
        }

        $row = lc_inquiry_get_by_code($code);
        if (!$row || (string) ($row['iq_center'] ?? '') !== 'public') {
            return array('ok' => false, 'message' => '문의를 찾을 수 없습니다.', 'inquiry' => null);
        }

        $stored = strtolower(trim((string) ($row['iq_contact_email'] ?? '')));
        if ($stored === '' || $stored !== $email) {
            return array('ok' => false, 'message' => '문의를 찾을 수 없습니다.', 'inquiry' => null);
        }

        return array('ok' => true, 'message' => '', 'inquiry' => $row);
    }
}

if (!function_exists('lc_inquiry_list')) {
    function lc_inquiry_list(array $filters = array(), $limit = 100)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('inquiries');
        $pt_table = lc_table('partners');
        $mt_table = lc_table('merchants');
        $where = ' 1=1 ';

        if (!empty($filters['pt_id'])) {
            $where .= " AND iq.pt_id = '" . (int) $filters['pt_id'] . "' ";
        }
        if (!empty($filters['mt_id'])) {
            $where .= " AND iq.mt_id = '" . (int) $filters['mt_id'] . "' ";
        }
        if (!empty($filters['mb_id'])) {
            $where .= " AND iq.mb_id = '" . lc_sql_escape((string) $filters['mb_id']) . "' ";
        }
        if (!empty($filters['center'])) {
            $where .= " AND iq.iq_center = '" . lc_sql_escape($filters['center']) . "' ";
        }
        if (!empty($filters['status'])) {
            $where .= " AND iq.iq_status = '" . lc_sql_escape($filters['status']) . "' ";
        }
        if (!empty($filters['category'])) {
            $where .= " AND iq.iq_category = '" . lc_sql_escape($filters['category']) . "' ";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (iq.iq_subject LIKE '%{$q}%' OR iq.iq_body LIKE '%{$q}%' OR iq.iq_code LIKE '%{$q}%') ";
        }

        $limit = max(1, min(200, (int) $limit));
        $rows = array();
        $sql = " SELECT iq.*, p.pt_code, p.pt_name, m.mt_code, m.mt_company
            FROM `{$table}` iq
            LEFT JOIN `{$pt_table}` p ON p.pt_id = iq.pt_id
            LEFT JOIN `{$mt_table}` m ON m.mt_id = iq.mt_id
            WHERE {$where}
            ORDER BY iq.iq_id DESC
            LIMIT {$limit} ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_inquiry_summary')) {
    function lc_inquiry_summary(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array('total' => 0, 'waiting' => 0, 'processing' => 0, 'closed' => 0, 'today' => 0);
        }

        $table = lc_table('inquiries');
        $where = ' 1=1 ';
        if (!empty($filters['pt_id'])) {
            $where .= " AND pt_id = '" . (int) $filters['pt_id'] . "' ";
        }
        if (!empty($filters['mt_id'])) {
            $where .= " AND mt_id = '" . (int) $filters['mt_id'] . "' ";
        }
        if (!empty($filters['mb_id'])) {
            $where .= " AND mb_id = '" . lc_sql_escape((string) $filters['mb_id']) . "' ";
        }
        if (!empty($filters['center'])) {
            $where .= " AND iq_center = '" . lc_sql_escape($filters['center']) . "' ";
        }

        $today = date('Y-m-d');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN iq_status = '" . lc_sql_escape(LC_INQUIRY_WAITING) . "' THEN 1 ELSE 0 END) AS waiting_cnt,
            SUM(CASE WHEN iq_status = '" . lc_sql_escape(LC_INQUIRY_PROCESSING) . "' THEN 1 ELSE 0 END) AS processing_cnt,
            SUM(CASE WHEN iq_status = '" . lc_sql_escape(LC_INQUIRY_CLOSED) . "' THEN 1 ELSE 0 END) AS closed_cnt,
            SUM(CASE WHEN DATE(iq_created_at) = '{$today}' THEN 1 ELSE 0 END) AS today_cnt
            FROM `{$table}` WHERE {$where} ");

        return array(
            'total'      => (int) ($row['total_cnt'] ?? 0),
            'waiting'    => (int) ($row['waiting_cnt'] ?? 0),
            'processing' => (int) ($row['processing_cnt'] ?? 0),
            'closed'     => (int) ($row['closed_cnt'] ?? 0),
            'today'      => (int) ($row['today_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_inquiry_to_api')) {
    function lc_inquiry_to_api(array $row, $detail = false)
    {
        $center = (string) ($row['iq_center'] ?? '');
        $contact_name = (string) ($row['iq_contact_name'] ?? '');
        $author = '';
        if ($center === 'partner') {
            $author = trim((string) ($row['pt_name'] ?? '') . ' (' . (string) ($row['pt_code'] ?? '') . ')');
        } elseif ($center === 'merchant') {
            $author = (string) ($row['mt_company'] ?? $row['mt_code'] ?? '');
        } elseif ($center === 'public') {
            $author = $contact_name !== '' ? $contact_name : (string) ($row['mb_id'] ?? '비회원');
        }

        $center_label = '공개';
        if ($center === 'partner') {
            $center_label = '파트너';
        } elseif ($center === 'merchant') {
            $center_label = '광고주';
        } elseif ($center !== 'public' && $center !== '') {
            $center_label = $center;
        }

        $item = array(
            'id'           => (string) ($row['iq_code'] ?? ''),
            'iqId'         => (int) ($row['iq_id'] ?? 0),
            'date'         => date('Y.m.d H:i', strtotime($row['iq_created_at'] ?? 'now')),
            'center'       => $center_label,
            'centerCode'   => $center,
            'author'       => $author,
            'contactName'  => $contact_name,
            'contactEmail' => (string) ($row['iq_contact_email'] ?? ''),
            'contactPhone' => (string) ($row['iq_contact_phone'] ?? ''),
            'category'     => (string) ($row['iq_category'] ?? ''),
            'title'        => (string) ($row['iq_subject'] ?? ''),
            'campaign'     => (string) ($row['iq_campaign'] ?? ''),
            'cvCode'       => (string) ($row['iq_cv_code'] ?? ''),
            'status'       => lc_inquiry_status_label($row['iq_status'] ?? ''),
            'statusCode'   => (string) ($row['iq_status'] ?? ''),
            'replyDate'    => !empty($row['iq_replied_at']) ? date('Y.m.d H:i', strtotime($row['iq_replied_at'])) : '-',
        );

        if ($detail) {
            $item['content'] = (string) ($row['iq_body'] ?? '');
            $item['reply'] = (string) ($row['iq_reply'] ?? '');
            $item['adminMemo'] = (string) ($row['iq_admin_memo'] ?? '');
            $att_path = trim((string) ($row['iq_attachment_path'] ?? ''));
            $att_name = trim((string) ($row['iq_attachment_name'] ?? ''));
            if ($att_path !== '') {
                $item['attachmentName'] = $att_name !== '' ? $att_name : '첨부파일';
                $item['hasAttachment'] = true;
            } else {
                $item['attachmentName'] = '';
                $item['hasAttachment'] = false;
            }
        }

        return $item;
    }
}

if (!function_exists('lc_inquiry_admin_reply')) {
    /**
     * @return array{ok:bool,message:string,inquiry:array|null}
     */
    function lc_inquiry_admin_reply($iq_id, array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'inquiry' => null);
        }

        $inquiry = lc_inquiry_get_by_id($iq_id);
        if (!$inquiry) {
            return array('ok' => false, 'message' => '문의를 찾을 수 없습니다.', 'inquiry' => null);
        }

        $action = isset($payload['action']) ? (string) $payload['action'] : 'reply';
        $table = lc_table('inquiries');
        $sets = array();

        if ($action === 'reply' || $action === 'close') {
            $reply = trim((string) ($payload['reply'] ?? ''));
            if ($reply === '') {
                return array('ok' => false, 'message' => '답변 내용을 입력해주세요.', 'inquiry' => null);
            }
            $sets[] = "iq_reply = '" . lc_sql_escape($reply) . "'";
            $sets[] = "iq_status = '" . lc_sql_escape(LC_INQUIRY_CLOSED) . "'";
            $sets[] = "iq_replied_at = NOW()";
        } elseif ($action === 'status') {
            $status = trim((string) ($payload['status'] ?? ''));
            $allowed = array(LC_INQUIRY_WAITING, LC_INQUIRY_OPEN, LC_INQUIRY_PROCESSING, LC_INQUIRY_HOLD, LC_INQUIRY_CLOSED);
            if (!in_array($status, $allowed, true)) {
                return array('ok' => false, 'message' => '유효하지 않은 상태입니다.', 'inquiry' => null);
            }
            $sets[] = "iq_status = '" . lc_sql_escape($status) . "'";
            $reply = trim((string) ($payload['reply'] ?? ''));
            if ($reply !== '') {
                $sets[] = "iq_reply = '" . lc_sql_escape($reply) . "'";
                if ($status === LC_INQUIRY_CLOSED) {
                    $sets[] = "iq_replied_at = NOW()";
                }
            }
        } else {
            return array('ok' => false, 'message' => '유효하지 않은 액션입니다.', 'inquiry' => null);
        }

        if (isset($payload['adminMemo'])) {
            $sets[] = "iq_admin_memo = '" . lc_sql_escape((string) $payload['adminMemo']) . "'";
        }

        if (!$sets) {
            return array('ok' => false, 'message' => '변경할 내용이 없습니다.', 'inquiry' => null);
        }

        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE iq_id = '" . (int) $iq_id . "' LIMIT 1 ", false);

        $updated = lc_inquiry_get_by_id($iq_id);

        $reply_saved = trim((string) ($updated['iq_reply'] ?? ''));
        $reply_added = $reply_saved !== '' && $reply_saved !== trim((string) ($inquiry['iq_reply'] ?? ''));
        if ($reply_added && is_array($updated) && (string) ($updated['iq_center'] ?? '') === 'public') {
            lc_inquiry_send_reply_email($updated);
        }

        return array(
            'ok'      => true,
            'message' => '문의가 처리되었습니다.',
            'inquiry' => $updated,
        );
    }
}
