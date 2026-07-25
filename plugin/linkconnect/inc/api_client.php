<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_api_client_generate_code')) {
    function lc_api_client_generate_code()
    {
        return 'AC-' . date('ymd') . '-' . str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_api_client_generate_key')) {
    function lc_api_client_generate_key($prefix = 'sk_live_')
    {
        return $prefix . bin2hex(random_bytes(16));
    }
}

if (!function_exists('lc_api_client_key_prefix_for_type')) {
    function lc_api_client_key_prefix_for_type($type)
    {
        $type = trim((string) $type);
        if ($type === 'merchant') {
            return 'sk_mt_';
        }

        return 'sk_live_';
    }
}

if (!function_exists('lc_api_client_get_by_id')) {
    function lc_api_client_get_by_id($ac_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $ac_id = (int) $ac_id;
        $table = lc_table('api_clients');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ac_id = '{$ac_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_api_client_get_by_key')) {
    function lc_api_client_get_by_key($api_key)
    {
        if (!lc_db_installed() || trim((string) $api_key) === '') {
            return null;
        }

        $table = lc_table('api_clients');
        $key = lc_sql_escape(trim((string) $api_key));

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ac_api_key = '{$key}' AND ac_status = 'active' LIMIT 1 ");
    }
}

if (!function_exists('lc_api_client_list')) {
    function lc_api_client_list()
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('api_clients');
        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` ORDER BY ac_id DESC ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_api_client_ensure_schema')) {
    function lc_api_client_ensure_schema()
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $table = lc_table('api_clients');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => 'api_clients 테이블이 없습니다.');
        }

        if (function_exists('lc_db_column_exists') && !lc_db_column_exists($table, 'ac_mt_id')) {
            lc_sql_query(" ALTER TABLE `{$table}` ADD COLUMN `ac_mt_id` int unsigned NOT NULL DEFAULT 0 AFTER `ac_type`, ADD KEY `idx_ac_mt_id` (`ac_mt_id`) ", false);
            if (!lc_db_column_exists($table, 'ac_mt_id')) {
                return array('ok' => false, 'message' => 'ac_mt_id 컬럼 추가에 실패했습니다.');
            }
        }

        return array('ok' => true, 'message' => 'ok');
    }
}

if (!function_exists('lc_api_client_ensure_default')) {
    function lc_api_client_ensure_default()
    {
        if (!lc_db_installed()) {
            return null;
        }

        lc_api_client_ensure_schema();

        $table = lc_table('api_clients');
        // 랜딩용 클라이언트가 없으면 생성 (광고주 키만 있어도 랜딩 기본키는 별도로 유지)
        $existing = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ac_type <> 'merchant' ORDER BY ac_id ASC LIMIT 1 ", false);
        if (is_array($existing) && !empty($existing['ac_id'])) {
            return $existing;
        }

        $code = lc_api_client_generate_code();
        $api_key = lc_api_client_generate_key();
        $has_mt = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'ac_mt_id');
        $mt_sql = $has_mt ? ", ac_mt_id = '0'" : '';
        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            ac_code = '" . lc_sql_escape($code) . "',
            ac_name = '랜딩페이지',
            ac_type = 'landing',
            ac_api_key = '" . lc_sql_escape($api_key) . "',
            ac_api_secret = '" . lc_sql_escape(bin2hex(random_bytes(12))) . "',
            ac_allowed_ips = '',
            ac_status = 'active',
            ac_created_at = NOW()
            {$mt_sql} ", false);

        if ($ok === false) {
            return null;
        }

        $ac_id = (int) lc_sql_insert_id();
        if ($ac_id > 0) {
            return lc_api_client_get_by_id($ac_id);
        }

        return lc_api_client_get_by_key($api_key);
    }
}

if (!function_exists('lc_api_client_create')) {
    /**
     * @return array{ok:bool,message:string,client:array|null}
     */
    function lc_api_client_create(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'client' => null);
        }

        $schema = lc_api_client_ensure_schema();
        if (empty($schema['ok'])) {
            return array('ok' => false, 'message' => $schema['message'] ?? '스키마 준비 실패', 'client' => null);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return array('ok' => false, 'message' => '클라이언트명은 필수입니다.', 'client' => null);
        }

        $type = trim((string) ($payload['type'] ?? 'landing'));
        if ($type === '') {
            $type = 'landing';
        }
        $mt_id = (int) ($payload['mtId'] ?? $payload['mt_id'] ?? 0);

        if ($type === 'merchant') {
            if ($mt_id <= 0) {
                return array('ok' => false, 'message' => '광고주(mtId)가 필요합니다.', 'client' => null);
            }
            if (!function_exists('lc_get_merchant_by_id') || !lc_get_merchant_by_id($mt_id)) {
                return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'client' => null);
            }
        } else {
            $mt_id = 0;
        }

        $table = lc_table('api_clients');
        $code = lc_api_client_generate_code();
        $api_key = lc_api_client_generate_key(lc_api_client_key_prefix_for_type($type));

        $has_mt = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'ac_mt_id');
        if ($type === 'merchant' && !$has_mt) {
            return array('ok' => false, 'message' => '광고주 연동용 ac_mt_id 컬럼이 없습니다. DB 마이그레이션을 실행해 주세요.', 'client' => null);
        }

        $sets = array(
            "ac_code = '" . lc_sql_escape($code) . "'",
            "ac_name = '" . lc_sql_escape($name) . "'",
            "ac_type = '" . lc_sql_escape($type) . "'",
            "ac_api_key = '" . lc_sql_escape($api_key) . "'",
            "ac_api_secret = '" . lc_sql_escape(bin2hex(random_bytes(12))) . "'",
            "ac_allowed_ips = '" . lc_sql_escape($payload['allowedIps'] ?? '') . "'",
            "ac_status = 'active'",
            'ac_created_at = NOW()',
        );
        if ($has_mt) {
            $sets[] = "ac_mt_id = '" . (int) $mt_id . "'";
        }

        $ok = lc_sql_query(' INSERT INTO `' . $table . '` SET ' . implode(', ', $sets) . ' ', false);
        if ($ok === false) {
            $err = function_exists('lc_sql_error') ? trim((string) lc_sql_error()) : '';

            return array(
                'ok'      => false,
                'message' => 'API 클라이언트 저장에 실패했습니다.' . ($err !== '' ? " ({$err})" : ''),
                'client'  => null,
            );
        }

        $ac_id = (int) lc_sql_insert_id();
        $client = $ac_id > 0 ? lc_api_client_get_by_id($ac_id) : null;
        if (!is_array($client)) {
            $client = lc_api_client_get_by_key($api_key);
        }
        if (!is_array($client)) {
            return array('ok' => false, 'message' => 'API 클라이언트 생성 후 조회에 실패했습니다.', 'client' => null);
        }

        return array(
            'ok'      => true,
            'message' => 'API 클라이언트가 생성되었습니다.',
            'client'  => $client,
        );
    }
}

if (!function_exists('lc_api_client_regenerate_key')) {
    function lc_api_client_regenerate_key($ac_id, $field = 'api_key')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'client' => null);
        }

        $client = lc_api_client_get_by_id($ac_id);
        if (!$client) {
            return array('ok' => false, 'message' => '클라이언트를 찾을 수 없습니다.', 'client' => null);
        }

        $table = lc_table('api_clients');
        if ($field === 'secret') {
            $value = bin2hex(random_bytes(12));
            lc_sql_query(" UPDATE `{$table}` SET ac_api_secret = '" . lc_sql_escape($value) . "' WHERE ac_id = '" . (int) $ac_id . "' LIMIT 1 ", false);
        } else {
            $prefix = lc_api_client_key_prefix_for_type((string) ($client['ac_type'] ?? 'landing'));
            $value = lc_api_client_generate_key($prefix);
            lc_sql_query(" UPDATE `{$table}` SET ac_api_key = '" . lc_sql_escape($value) . "' WHERE ac_id = '" . (int) $ac_id . "' LIMIT 1 ", false);
        }

        return array(
            'ok'      => true,
            'message' => '키가 재발급되었습니다.',
            'client'  => lc_api_client_get_by_id($ac_id),
        );
    }
}

if (!function_exists('lc_api_client_update_status')) {
    function lc_api_client_update_status($ac_id, $status)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'client' => null);
        }

        $status = in_array($status, array('active', 'inactive'), true) ? $status : 'inactive';
        $table = lc_table('api_clients');
        lc_sql_query(" UPDATE `{$table}` SET ac_status = '" . lc_sql_escape($status) . "' WHERE ac_id = '" . (int) $ac_id . "' LIMIT 1 ", false);

        return array(
            'ok'      => true,
            'message' => $status === 'active' ? '활성화되었습니다.' : '비활성화되었습니다.',
            'client'  => lc_api_client_get_by_id($ac_id),
        );
    }
}

if (!function_exists('lc_api_client_update_ips')) {
    /**
     * @return array{ok:bool,message:string,client:array|null}
     */
    function lc_api_client_update_ips($ac_id, $allowed_ips)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'client' => null);
        }

        $client = lc_api_client_get_by_id($ac_id);
        if (!$client) {
            return array('ok' => false, 'message' => '클라이언트를 찾을 수 없습니다.', 'client' => null);
        }

        $table = lc_table('api_clients');
        lc_sql_query(" UPDATE `{$table}` SET ac_allowed_ips = '" . lc_sql_escape((string) $allowed_ips) . "' WHERE ac_id = '" . (int) $ac_id . "' LIMIT 1 ", false);

        return array(
            'ok'      => true,
            'message' => '허용 IP가 저장되었습니다.',
            'client'  => lc_api_client_get_by_id($ac_id),
        );
    }
}

if (!function_exists('lc_api_client_touch')) {
    function lc_api_client_touch($ac_id)
    {
        if (!lc_db_installed()) {
            return;
        }

        $table = lc_table('api_clients');
        lc_sql_query(" UPDATE `{$table}` SET ac_last_call_at = NOW() WHERE ac_id = '" . (int) $ac_id . "' LIMIT 1 ", false);
    }
}

if (!function_exists('lc_api_client_validate_request')) {
    /**
     * @return array{ok:bool,message:string,client:array|null}
     */
    function lc_api_client_validate_request($api_key, $client_ip = '')
    {
        if (!lc_settings_get_bool('apiKeyEnabled', true)) {
            return array('ok' => true, 'message' => '', 'client' => null);
        }

        $client = lc_api_client_get_by_key($api_key);
        if (!$client) {
            return array('ok' => false, 'message' => 'Invalid API Key', 'client' => null);
        }

        $check_ip = lc_settings_get_bool('apiIpRestrict') || trim((string) ($client['ac_type'] ?? '')) === 'merchant';
        if ($check_ip && trim((string) $client['ac_allowed_ips']) !== '') {
            $allowed = array_filter(array_map('trim', explode(',', (string) $client['ac_allowed_ips'])));
            $ip_ok = false;
            foreach ($allowed as $pattern) {
                if ($pattern === $client_ip || ($pattern !== '' && strpos($pattern, '*') !== false && fnmatch($pattern, $client_ip))) {
                    $ip_ok = true;
                    break;
                }
            }
            if (!$ip_ok && $client_ip !== '') {
                return array('ok' => false, 'message' => 'IP not allowed', 'client' => $client);
            }
        }

        return array('ok' => true, 'message' => '', 'client' => $client);
    }
}

if (!function_exists('lc_api_extract_key_from_request')) {
    function lc_api_extract_key_from_request(array $body = array())
    {
        if (isset($_SERVER['HTTP_X_API_KEY']) && trim((string) $_SERVER['HTTP_X_API_KEY']) !== '') {
            return trim((string) $_SERVER['HTTP_X_API_KEY']);
        }
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
            if (stripos($auth, 'Bearer ') === 0) {
                return trim(substr($auth, 7));
            }
        }
        if (isset($body['api_key'])) {
            return trim((string) $body['api_key']);
        }
        if (isset($body['apiKey'])) {
            return trim((string) $body['apiKey']);
        }

        return '';
    }
}

if (!function_exists('lc_api_require_merchant_client')) {
    /**
     * 광고주 외부 API 인증. 성공 시 client 배열 반환, 실패 시 응답 종료.
     *
     * @return array
     */
    function lc_api_require_merchant_client(array $body = array())
    {
        $api_key = lc_api_extract_key_from_request($body);
        if ($api_key === '') {
            lc_api_error('API Key가 필요합니다.', 'UNAUTHORIZED', 401);
        }

        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $client = lc_api_client_get_by_key($api_key);
        if (!$client) {
            lc_api_error('Invalid API Key', 'UNAUTHORIZED', 401);
        }

        // 광고주 키는 허용 IP가 있으면 항상 검사
        if (trim((string) ($client['ac_allowed_ips'] ?? '')) !== '') {
            $auth = lc_api_client_validate_request($api_key, $client_ip);
            if (!$auth['ok']) {
                lc_api_error($auth['message'] !== '' ? $auth['message'] : 'IP not allowed', 'UNAUTHORIZED', 401);
            }
        }

        if ((string) ($client['ac_type'] ?? '') !== 'merchant') {
            lc_api_error('Merchant API Key가 필요합니다.', 'FORBIDDEN', 403);
        }

        $mt_id = (int) ($client['ac_mt_id'] ?? 0);
        if ($mt_id <= 0) {
            lc_api_error('API Key에 광고주가 연결되어 있지 않습니다.', 'FORBIDDEN', 403);
        }

        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        if (!$merchant) {
            lc_api_error('광고주를 찾을 수 없습니다.', 'FORBIDDEN', 403);
        }

        if (function_exists('lc_api_client_touch')) {
            lc_api_client_touch((int) $client['ac_id']);
        }

        $client['_merchant'] = $merchant;
        $client['_mt_id'] = $mt_id;

        return $client;
    }
}

if (!function_exists('lc_api_log_mask_body')) {
    function lc_api_log_mask_body($body)
    {
        if (!lc_settings_get_bool('apiMaskPii', true) || !is_string($body) || $body === '') {
            return (string) $body;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $body;
        }

        foreach (array('name', 'phone', 'email') as $field) {
            if (!isset($decoded[$field])) {
                continue;
            }
            $value = (string) $decoded[$field];
            if ($field === 'phone' && strlen($value) > 4) {
                $decoded[$field] = substr($value, 0, 3) . '-****-' . substr($value, -4);
            } elseif ($field === 'name' && function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > 1) {
                $decoded[$field] = mb_substr($value, 0, 1, 'UTF-8') . '*';
            } else {
                $decoded[$field] = '***';
            }
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('lc_api_log_write')) {
    function lc_api_log_write(array $payload)
    {
        if (!lc_db_installed() || !lc_settings_get_bool('apiLogEnabled', true)) {
            return 0;
        }

        $table = lc_table('api_logs');
        $request = lc_api_log_mask_body((string) ($payload['requestBody'] ?? ''));
        $response = (string) ($payload['responseBody'] ?? '');

        lc_sql_query(" INSERT INTO `{$table}` SET
            ac_id = '" . (int) ($payload['acId'] ?? 0) . "',
            al_client_name = '" . lc_sql_escape($payload['clientName'] ?? '') . "',
            al_direction = '" . lc_sql_escape($payload['direction'] ?? 'receive') . "',
            al_endpoint = '" . lc_sql_escape($payload['endpoint'] ?? '') . "',
            al_ext_id = '" . lc_sql_escape($payload['extId'] ?? '') . "',
            al_int_code = '" . lc_sql_escape($payload['intCode'] ?? '') . "',
            al_status_code = '" . (int) ($payload['statusCode'] ?? 200) . "',
            al_status = '" . lc_sql_escape($payload['status'] ?? 'success') . "',
            al_error = '" . lc_sql_escape($payload['error'] ?? '') . "',
            al_request_body = '" . lc_sql_escape($request) . "',
            al_response_body = '" . lc_sql_escape($response) . "',
            al_created_at = NOW() ", false);

        if (!empty($payload['acId'])) {
            lc_api_client_touch((int) $payload['acId']);
        }

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_api_log_status_label')) {
    function lc_api_log_status_label($status)
    {
        $labels = array(
            'success'  => '성공',
            'failed'   => '실패',
            'duplicate'=> '중복',
            'auth'     => '인증오류',
            'validate' => '검증오류',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_api_log_list')) {
    function lc_api_log_list(array $filters = array(), $limit = 50)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('api_logs');
        $where = ' 1=1 ';

        if (!empty($filters['status'])) {
            $where .= " AND al_status = '" . lc_sql_escape($filters['status']) . "' ";
        }
        if (!empty($filters['client'])) {
            $where .= " AND al_client_name = '" . lc_sql_escape($filters['client']) . "' ";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (al_endpoint LIKE '%{$q}%' OR al_int_code LIKE '%{$q}%' OR al_ext_id LIKE '%{$q}%') ";
        }
        if (!empty($filters['errors_only'])) {
            $where .= " AND al_status != 'success' ";
        }
        if (!empty($filters['since_hours'])) {
            $hours = max(1, (int) $filters['since_hours']);
            $where .= " AND al_created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR) ";
        }

        $limit = max(1, min(200, (int) $limit));
        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY al_id DESC LIMIT {$limit} ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_api_log_to_api')) {
    function lc_api_log_to_api(array $row, $detail = false)
    {
        $item = array(
            'id'         => 'LOG-' . str_pad((string) ($row['al_id'] ?? 0), 3, '0', STR_PAD_LEFT),
            'alId'       => (int) ($row['al_id'] ?? 0),
            'time'       => date('Y.m.d H:i:s', strtotime($row['al_created_at'] ?? 'now')),
            'client'     => (string) ($row['al_client_name'] ?? ''),
            'direction'  => (string) ($row['al_direction'] ?? ''),
            'endpoint'   => (string) ($row['al_endpoint'] ?? ''),
            'extId'      => (string) ($row['al_ext_id'] ?? ''),
            'intId'      => (string) ($row['al_int_code'] ?? ''),
            'statusCode' => (int) ($row['al_status_code'] ?? 0),
            'status'     => lc_api_log_status_label($row['al_status'] ?? ''),
            'statusCodeRaw' => (string) ($row['al_status'] ?? ''),
            'error'      => (string) ($row['al_error'] ?? ''),
        );

        if ($detail) {
            $item['requestBody'] = (string) ($row['al_request_body'] ?? '');
            $item['responseBody'] = (string) ($row['al_response_body'] ?? '');
        }

        return $item;
    }
}

if (!function_exists('lc_api_log_summary')) {
    function lc_api_log_summary()
    {
        if (!lc_db_installed()) {
            return array(
                'todayTotal' => 0,
                'todaySuccess' => 0,
                'todayFailed' => 0,
                'todayDuplicate' => 0,
                'dbshareTotal' => 0,
                'lastReceiveTime' => '-',
            );
        }

        $table = lc_table('api_logs');
        $today = date('Y-m-d');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN al_status = 'success' THEN 1 ELSE 0 END) AS success_cnt,
            SUM(CASE WHEN al_status = 'failed' THEN 1 ELSE 0 END) AS failed_cnt,
            SUM(CASE WHEN al_status = 'duplicate' THEN 1 ELSE 0 END) AS duplicate_cnt,
            SUM(CASE WHEN al_client_name LIKE '%디비쉐어%' OR al_client_name LIKE '%dbshare%' THEN 1 ELSE 0 END) AS dbshare_cnt,
            MAX(al_created_at) AS last_at
            FROM `{$table}` WHERE DATE(al_created_at) = '{$today}' ");

        $last = !empty($row['last_at']) ? date('H:i', strtotime($row['last_at'])) : '-';

        return array(
            'todayTotal'      => (int) ($row['total_cnt'] ?? 0),
            'todaySuccess'    => (int) ($row['success_cnt'] ?? 0),
            'todayFailed'     => (int) ($row['failed_cnt'] ?? 0),
            'todayDuplicate'  => (int) ($row['duplicate_cnt'] ?? 0),
            'dbshareTotal'    => (int) ($row['dbshare_cnt'] ?? 0),
            'lastReceiveTime' => $last,
        );
    }
}

if (!function_exists('lc_api_client_to_api')) {
    function lc_api_client_to_api(array $row)
    {
        $mt_id = (int) ($row['ac_mt_id'] ?? 0);
        $type = trim((string) ($row['ac_type'] ?? ''));
        if ($type === '' && $mt_id > 0) {
            $type = 'merchant';
        }
        if ($type === '') {
            $type = 'landing';
        }

        $merchant_name = '';
        if ($mt_id > 0 && function_exists('lc_get_merchant_by_id')) {
            $mt = lc_get_merchant_by_id($mt_id);
            if ($mt) {
                $merchant_name = (string) ($mt['mt_company'] ?? '');
            }
        }

        return array(
            'id'            => (int) ($row['ac_id'] ?? 0),
            'code'          => (string) ($row['ac_code'] ?? ''),
            'name'          => (string) ($row['ac_name'] ?? ''),
            'type'          => $type,
            'mtId'          => $mt_id,
            'merchantName'  => $merchant_name,
            'apiKey'        => (string) ($row['ac_api_key'] ?? ''),
            'allowedIps'    => (string) ($row['ac_allowed_ips'] ?? ''),
            'status'        => (string) ($row['ac_status'] ?? '') === 'active' ? '정상' : '비활성',
            'statusCode'    => (string) ($row['ac_status'] ?? ''),
            'lastCallAt'    => !empty($row['ac_last_call_at']) ? date('Y.m.d H:i', strtotime($row['ac_last_call_at'])) : '-',
        );
    }
}
