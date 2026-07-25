<?php
/**
 * 콜디비 녹음 파일 요청 · 최고관리자 WAV 업로드
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_call_recording_storage_dir')) {
    function lc_call_recording_storage_dir()
    {
        if (!defined('G5_DATA_PATH') || (string) G5_DATA_PATH === '') {
            return '';
        }

        $dir = rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/call_recordings';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return is_dir($dir) ? $dir : '';
    }
}

if (!function_exists('lc_call_recording_request_get')) {
    function lc_call_recording_request_get($crr_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $table = lc_table('call_recording_requests');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE crr_id = '" . (int) $crr_id . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_call_recording_request_latest_for_log')) {
    /**
     * @return array|null
     */
    function lc_call_recording_request_latest_for_log($clog_id, $requester_type, $requester_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $clog_id = (int) $clog_id;
        $requester_id = (int) $requester_id;
        $type = $requester_type === 'merchant' ? 'merchant' : 'partner';
        if ($clog_id <= 0 || $requester_id <= 0) {
            return null;
        }

        $table = lc_table('call_recording_requests');
        $pt_clause = $type === 'partner'
            ? " AND requester_type = 'partner' AND pt_id = '{$requester_id}' "
            : " AND requester_type = 'merchant' AND mt_id = '{$requester_id}' ";

        return lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE clog_id = '{$clog_id}' {$pt_clause}
            ORDER BY crr_id DESC LIMIT 1 ");
    }
}

if (!function_exists('lc_call_recording_requests_list')) {
    function lc_call_recording_requests_list(array $filters = array())
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('call_recording_requests'))) {
            return array();
        }

        $crr = lc_table('call_recording_requests');
        $clog = lc_table('call_logs');
        $cp = lc_table('campaigns');
        $pt = lc_table('partners');
        $mt = lc_table('merchants');

        $where = ' 1=1 ';
        if (!empty($filters['status'])) {
            $where .= " AND r.crr_status = '" . lc_sql_escape($filters['status']) . "' ";
        }
        if (!empty($filters['pt_id'])) {
            $where .= " AND r.pt_id = '" . (int) $filters['pt_id'] . "' ";
        }
        if (!empty($filters['mt_id'])) {
            $where .= " AND r.mt_id = '" . (int) $filters['mt_id'] . "' ";
        }
        if (!empty($filters['requester_type'])) {
            $where .= " AND r.requester_type = '" . lc_sql_escape($filters['requester_type']) . "' ";
        }

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 200;
        $rows = array();
        $sql = " SELECT r.*, l.clog_virtual_number, l.clog_caller, l.clog_started_at, l.clog_duration, l.clog_result,
                    c.cp_name, p.pt_code, p.pt_name, m.mt_company
            FROM `{$crr}` r
            INNER JOIN `{$clog}` l ON l.clog_id = r.clog_id
            LEFT JOIN `{$cp}` c ON c.cp_id = l.cp_id
            LEFT JOIN `{$pt}` p ON p.pt_id = r.pt_id
            LEFT JOIN `{$mt}` m ON m.mt_id = r.mt_id
            WHERE {$where}
            ORDER BY r.crr_id DESC LIMIT {$limit} ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_call_recording_request_create')) {
    /**
     * @return array{ok:bool,message:string,crrId?:int}
     */
    function lc_call_recording_request_create($clog_id, $requester_type, $requester_id, $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $clog_id = (int) $clog_id;
        $requester_id = (int) $requester_id;
        $type = $requester_type === 'merchant' ? 'merchant' : 'partner';
        if ($clog_id <= 0 || $requester_id <= 0) {
            return array('ok' => false, 'message' => '잘못된 요청입니다.');
        }

        $log = lc_call_log_get($clog_id);
        if (!$log) {
            return array('ok' => false, 'message' => '통화 내역을 찾을 수 없습니다.');
        }

        if ($type === 'partner') {
            if ((int) ($log['pt_id'] ?? 0) !== $requester_id) {
                return array('ok' => false, 'message' => '본인 통화 내역만 녹음을 요청할 수 있습니다.');
            }
        } else {
            if ((int) ($log['mt_id'] ?? 0) !== $requester_id) {
                return array('ok' => false, 'message' => '본인 상품 통화 내역만 녹음을 요청할 수 있습니다.');
            }
        }

        $existing = lc_call_recording_request_latest_for_log($clog_id, $type, $requester_id);
        if (is_array($existing)) {
            $st = (string) ($existing['crr_status'] ?? '');
            if ($st === LC_CALL_REC_REQ_PENDING) {
                return array('ok' => false, 'message' => '이미 녹음 요청이 접수되어 있습니다.');
            }
            if ($st === LC_CALL_REC_REQ_FULFILLED) {
                return array('ok' => false, 'message' => '이미 녹음 파일이 등록되어 있습니다.');
            }
        }

        $table = lc_table('call_recording_requests');
        $pt_id = $type === 'partner' ? $requester_id : 0;
        $mt_id = $type === 'merchant' ? $requester_id : (int) ($log['mt_id'] ?? 0);

        lc_sql_query(" INSERT INTO `{$table}` SET
            clog_id = '{$clog_id}',
            requester_type = '" . lc_sql_escape($type) . "',
            pt_id = '{$pt_id}',
            mt_id = '{$mt_id}',
            crr_status = '" . LC_CALL_REC_REQ_PENDING . "',
            crr_request_memo = '" . lc_sql_escape($memo) . "',
            crr_requested_at = NOW() ", false);

        $crr_id = (int) lc_sql_insert_id();

        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'call',
                'title'   => '콜 녹음 파일 요청',
                'body'    => '통화 #' . $clog_id . ' 녹음 파일 업로드가 필요합니다.',
                'link'    => '/admin/call',
                'refType' => 'call_recording_request',
                'refId'   => $crr_id,
            ));
        }

        return array('ok' => true, 'message' => '녹음 파일 요청이 접수되었습니다. 관리자가 업로드하면 재생할 수 있습니다.', 'crrId' => $crr_id);
    }
}

if (!function_exists('lc_call_recording_request_reject')) {
    function lc_call_recording_request_reject($crr_id, $admin_memo = '')
    {
        if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
            return array('ok' => false, 'message' => '최고관리자만 녹음 요청을 반려할 수 있습니다.');
        }

        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $crr_id = (int) $crr_id;
        $row = lc_call_recording_request_get($crr_id);
        if (!$row) {
            return array('ok' => false, 'message' => '요청을 찾을 수 없습니다.');
        }
        if (($row['crr_status'] ?? '') !== LC_CALL_REC_REQ_PENDING) {
            return array('ok' => false, 'message' => '대기 중인 요청만 반려할 수 있습니다.');
        }

        $table = lc_table('call_recording_requests');
        lc_sql_query(" UPDATE `{$table}` SET
            crr_status = '" . LC_CALL_REC_REQ_REJECTED . "',
            crr_admin_memo = '" . lc_sql_escape($admin_memo) . "',
            crr_processed_at = NOW()
            WHERE crr_id = '{$crr_id}' ", false);

        return array('ok' => true, 'message' => '녹음 요청을 반려했습니다.');
    }
}

if (!function_exists('lc_call_recording_request_upload_wav')) {
    /**
     * 최고관리자 WAV 업로드
     *
     * @param array<string,mixed> $file $_FILES entry
     * @return array{ok:bool,message:string}
     */
    function lc_call_recording_request_upload_wav($crr_id, array $file, $admin_memo = '')
    {
        if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
            return array('ok' => false, 'message' => '최고관리자만 녹음 파일을 업로드할 수 있습니다.');
        }

        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $crr_id = (int) $crr_id;
        $row = lc_call_recording_request_get($crr_id);
        if (!$row) {
            return array('ok' => false, 'message' => '요청을 찾을 수 없습니다.');
        }
        if (($row['crr_status'] ?? '') !== LC_CALL_REC_REQ_PENDING) {
            return array('ok' => false, 'message' => '대기 중인 요청에만 업로드할 수 있습니다.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'message' => '업로드된 파일이 없습니다.');
        }
        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'message' => '파일 업로드에 실패했습니다.');
        }

        $max_bytes = 25 * 1024 * 1024;
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0 || $size > $max_bytes) {
            return array('ok' => false, 'message' => '파일 크기는 25MB 이하여야 합니다.');
        }

        $orig = isset($file['name']) ? (string) $file['name'] : 'recording.wav';
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($ext !== 'wav') {
            return array('ok' => false, 'message' => 'WAV 파일만 업로드할 수 있습니다.');
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowed_mimes = array('audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave');
        if ($mime !== '' && !in_array($mime, $allowed_mimes, true) && strpos($mime, 'audio/') !== 0) {
            return array('ok' => false, 'message' => '유효한 오디오 파일이 아닙니다.');
        }

        $base = lc_call_recording_storage_dir();
        if ($base === '') {
            return array('ok' => false, 'message' => '저장 경로를 준비하지 못했습니다.');
        }

        $subdir = $base . '/' . $crr_id;
        if (!is_dir($subdir)) {
            @mkdir($subdir, 0755, true);
        }

        $stored = 'recording_' . bin2hex(random_bytes(8)) . '.wav';
        $full = $subdir . '/' . $stored;
        if (!@move_uploaded_file($file['tmp_name'], $full)) {
            return array('ok' => false, 'message' => '파일 저장에 실패했습니다.');
        }

        @chmod($full, 0644);
        $relative = 'linkconnect/call_recordings/' . $crr_id . '/' . $stored;

        $table = lc_table('call_recording_requests');
        lc_sql_query(" UPDATE `{$table}` SET
            crr_status = '" . LC_CALL_REC_REQ_FULFILLED . "',
            crr_file_path = '" . lc_sql_escape($relative) . "',
            crr_original_name = '" . lc_sql_escape($orig) . "',
            crr_admin_memo = '" . lc_sql_escape($admin_memo) . "',
            crr_processed_at = NOW()
            WHERE crr_id = '{$crr_id}' ", false);

        $clog_id = (int) ($row['clog_id'] ?? 0);
        if ($clog_id > 0 && function_exists('lc_notification_create')) {
            $center = ($row['requester_type'] ?? '') === 'merchant' ? 'merchant' : 'partner';
            $uid = $center === 'merchant' ? (int) ($row['mt_id'] ?? 0) : (int) ($row['pt_id'] ?? 0);
            if ($uid > 0) {
                lc_notification_create(array(
                    'center'  => $center,
                    'userId'  => $uid,
                    'type'    => 'call',
                    'title'   => '녹음 파일 등록 완료',
                    'body'    => '요청하신 통화 녹음 파일을 재생할 수 있습니다.',
                    'link'    => $center === 'merchant' ? '/advertiser/call' : '/partner/call',
                    'refType' => 'call_recording_request',
                    'refId'   => $crr_id,
                ));
            }
        }

        return array('ok' => true, 'message' => '녹음 파일이 등록되었습니다.');
    }
}

if (!function_exists('lc_call_recording_request_absolute_path')) {
    function lc_call_recording_request_absolute_path(array $row)
    {
        $path = (string) ($row['crr_file_path'] ?? '');
        if ($path === '') {
            return '';
        }
        if (strpos($path, '/') === 0) {
            return $path;
        }
        if (defined('G5_DATA_PATH') && (string) G5_DATA_PATH !== '') {
            return rtrim((string) G5_DATA_PATH, '/') . '/' . ltrim($path, '/');
        }

        return '';
    }
}

if (!function_exists('lc_call_recording_request_can_access')) {
    function lc_call_recording_request_can_access(array $row)
    {
        if (($row['crr_status'] ?? '') !== LC_CALL_REC_REQ_FULFILLED) {
            return false;
        }
        if (function_exists('lc_is_super_admin') && lc_is_super_admin()) {
            return true;
        }
        if (function_exists('lc_is_linkconnect_admin') && lc_is_linkconnect_admin()) {
            return true;
        }

        $type = (string) ($row['requester_type'] ?? '');
        if ($type === 'partner') {
            $partner = function_exists('lc_get_current_partner') ? lc_get_current_partner() : null;

            return is_array($partner) && (int) $partner['pt_id'] === (int) ($row['pt_id'] ?? 0);
        }
        if ($type === 'merchant') {
            $merchant = function_exists('lc_get_current_merchant') ? lc_get_current_merchant() : null;

            return is_array($merchant) && (int) $merchant['mt_id'] === (int) ($row['mt_id'] ?? 0);
        }

        return false;
    }
}

if (!function_exists('lc_call_recording_request_to_api')) {
    function lc_call_recording_request_to_api(array $row, $include_play = false)
    {
        $status = (string) ($row['crr_status'] ?? '');
        $out = array(
            'crrId'          => (int) ($row['crr_id'] ?? 0),
            'clogId'         => (int) ($row['clog_id'] ?? 0),
            'requesterType'  => (string) ($row['requester_type'] ?? ''),
            'ptId'           => (int) ($row['pt_id'] ?? 0),
            'mtId'           => (int) ($row['mt_id'] ?? 0),
            'status'         => $status,
            'statusLabel'    => lc_call_recording_request_status_label($status),
            'requestMemo'    => (string) ($row['crr_request_memo'] ?? ''),
            'adminMemo'      => (string) ($row['crr_admin_memo'] ?? ''),
            'originalName'   => (string) ($row['crr_original_name'] ?? ''),
            'requestedAt'    => !empty($row['crr_requested_at']) ? date('Y.m.d H:i', strtotime($row['crr_requested_at'])) : '',
            'processedAt'    => !empty($row['crr_processed_at']) ? date('Y.m.d H:i', strtotime($row['crr_processed_at'])) : '',
            'virtualNumber'  => (string) ($row['clog_virtual_number'] ?? ''),
            'caller'         => (string) ($row['clog_caller'] ?? ''),
            'campaign'       => (string) ($row['cp_name'] ?? ''),
            'partner'        => (string) ($row['pt_code'] ?? ($row['pt_name'] ?? '')),
            'merchant'       => (string) ($row['mt_company'] ?? ''),
            'startedAt'      => !empty($row['clog_started_at']) ? date('Y.m.d H:i', strtotime($row['clog_started_at'])) : '',
            'duration'       => (int) ($row['clog_duration'] ?? 0),
            'callResult'     => (string) ($row['clog_result'] ?? ''),
            'canPlay'        => $status === LC_CALL_REC_REQ_FULFILLED && (string) ($row['crr_file_path'] ?? '') !== '',
        );
        if ($include_play && $out['canPlay']) {
            $out['playUrl'] = lc_call_recording_request_play_url((int) $out['crrId']);
        }

        return $out;
    }
}

if (!function_exists('lc_call_recording_request_status_label')) {
    function lc_call_recording_request_status_label($status)
    {
        $map = array(
            LC_CALL_REC_REQ_PENDING   => '요청 대기',
            LC_CALL_REC_REQ_FULFILLED => '등록 완료',
            LC_CALL_REC_REQ_REJECTED  => '반려',
        );

        return $map[$status] ?? $status;
    }
}

if (!function_exists('lc_call_recording_request_play_url')) {
    function lc_call_recording_request_play_url($crr_id)
    {
        $crr_id = (int) $crr_id;
        if ($crr_id <= 0) {
            return '';
        }

        return lc_url('api/call-recording-file.php?crrId=' . $crr_id);
    }
}

if (!function_exists('lc_call_recording_request_meta_for_log')) {
    /**
     * @return array{crrId:int,status:string,statusLabel:string,canPlay:bool,playUrl:string,canRequest:bool}
     */
    function lc_call_recording_request_meta_for_log($clog_id, $requester_type, $requester_id)
    {
        $default = array(
            'crrId'       => 0,
            'status'      => '',
            'statusLabel' => '',
            'canPlay'     => false,
            'playUrl'     => '',
            'canRequest'  => true,
        );

        $row = lc_call_recording_request_latest_for_log($clog_id, $requester_type, $requester_id);
        if (!is_array($row)) {
            return $default;
        }

        $status = (string) ($row['crr_status'] ?? '');
        $can_play = $status === LC_CALL_REC_REQ_FULFILLED && (string) ($row['crr_file_path'] ?? '') !== '';

        return array(
            'crrId'       => (int) ($row['crr_id'] ?? 0),
            'status'      => $status,
            'statusLabel' => lc_call_recording_request_status_label($status),
            'canPlay'     => $can_play,
            'playUrl'     => $can_play ? lc_call_recording_request_play_url((int) $row['crr_id']) : '',
            'canRequest'  => !in_array($status, array(LC_CALL_REC_REQ_PENDING, LC_CALL_REC_REQ_FULFILLED), true),
        );
    }
}

if (!function_exists('lc_call_recording_request_send_file')) {
    function lc_call_recording_request_send_file($crr_id)
    {
        $row = lc_call_recording_request_get((int) $crr_id);
        if (!is_array($row)) {
            header('HTTP/1.1 404 Not Found');
            exit('요청을 찾을 수 없습니다.');
        }
        if (!lc_call_recording_request_can_access($row)) {
            header('HTTP/1.1 403 Forbidden');
            exit('접근 권한이 없습니다.');
        }

        $absolute = lc_call_recording_request_absolute_path($row);
        if ($absolute === '' || !is_file($absolute)) {
            header('HTTP/1.1 404 Not Found');
            exit('녹음 파일을 찾을 수 없습니다.');
        }

        $name = (string) ($row['crr_original_name'] ?? 'recording.wav');
        if ($name === '') {
            $name = 'recording.wav';
        }

        header('Content-Type: audio/wav');
        header('Content-Disposition: inline; filename="' . rawurlencode($name) . '"');
        header('Content-Length: ' . (string) filesize($absolute));
        header('Cache-Control: private, max-age=3600');
        readfile($absolute);
        exit;
    }
}
