<?php
/**
 * LinkConnect 콜디비(Call DB) — 수동 운영
 *
 * - 관리자: 가상번호 풀 등록, 파트너 신청 배정, 통화내역 엑셀 업로드
 * - 파트너: 가상번호 신청 → 배정 번호로 홍보 → 통화내역 조회
 * - 광고주: 착신번호·콜디비 설정, 담당 가상번호 기준 통화내역 조회
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

/* ───────────────────────────── 설정 ───────────────────────────── */

if (!function_exists('lc_call_enabled')) {
    function lc_call_enabled()
    {
        return lc_settings_get_bool('callEnabled', false);
    }
}

/* ───────────────────────────── 가상번호 풀 ───────────────────────────── */

if (!function_exists('lc_call_number_normalize')) {
    function lc_call_number_normalize($number)
    {
        return preg_replace('/[^0-9]/', '', (string) $number);
    }
}

if (!function_exists('lc_call_numbers_list')) {
    function lc_call_numbers_list(array $filters = array())
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('call_numbers'))) {
            return array();
        }

        $table = lc_table('call_numbers');
        $where = ' 1=1 ';
        if (!empty($filters['status'])) {
            $where .= " AND cn_status = '" . lc_sql_escape($filters['status']) . "' ";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (cn_number LIKE '%{$q}%' OR cn_memo LIKE '%{$q}%') ";
        }

        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY cn_id DESC LIMIT 500 ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_call_number_get')) {
    function lc_call_number_get($cn_id)
    {
        if (!lc_db_installed()) {
            return null;
        }
        $table = lc_table('call_numbers');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cn_id = '" . (int) $cn_id . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_call_number_create')) {
    /**
     * @return array{ok:bool,message:string,cnId?:int}
     */
    function lc_call_number_create(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $number = lc_call_number_normalize($payload['number'] ?? '');
        if ($number === '') {
            return array('ok' => false, 'message' => '가상번호를 입력하세요.');
        }

        $table = lc_table('call_numbers');
        $exists = lc_sql_fetch(" SELECT cn_id FROM `{$table}` WHERE cn_number = '" . lc_sql_escape($number) . "' LIMIT 1 ");
        if ($exists) {
            return array('ok' => false, 'message' => '이미 등록된 번호입니다.');
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            cn_number = '" . lc_sql_escape($number) . "',
            cn_provider = '" . lc_sql_escape($payload['provider'] ?? lc_settings_get('callProvider', '')) . "',
            cn_provider_number_id = '" . lc_sql_escape($payload['providerNumberId'] ?? '') . "',
            cn_status = '" . lc_sql_escape($payload['status'] ?? LC_CALL_NUMBER_AVAILABLE) . "',
            cn_memo = '" . lc_sql_escape($payload['memo'] ?? '') . "',
            cn_created_at = NOW(),
            cn_updated_at = NOW() ", false);

        return array('ok' => true, 'message' => '가상번호가 등록되었습니다.', 'cnId' => (int) lc_sql_insert_id());
    }
}

if (!function_exists('lc_call_number_update')) {
    function lc_call_number_update($cn_id, array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $cn_id = (int) $cn_id;
        $number = lc_call_number_get($cn_id);
        if (!$number) {
            return array('ok' => false, 'message' => '번호를 찾을 수 없습니다.');
        }

        $table = lc_table('call_numbers');
        $sets = array();
        if (isset($payload['status'])) {
            $sets[] = "cn_status = '" . lc_sql_escape($payload['status']) . "'";
        }
        if (isset($payload['memo'])) {
            $sets[] = "cn_memo = '" . lc_sql_escape($payload['memo']) . "'";
        }
        if (isset($payload['providerNumberId'])) {
            $sets[] = "cn_provider_number_id = '" . lc_sql_escape($payload['providerNumberId']) . "'";
        }
        if (!$sets) {
            return array('ok' => true, 'message' => '변경사항이 없습니다.');
        }
        $sets[] = 'cn_updated_at = NOW()';

        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE cn_id = '{$cn_id}' ", false);

        return array('ok' => true, 'message' => '수정되었습니다.');
    }
}

/* ───────────────────────────── 캠페인 콜 설정 ───────────────────────────── */

if (!function_exists('lc_call_settings_defaults')) {
    function lc_call_settings_defaults($cp_id = 0, $mt_id = 0)
    {
        return array(
            'cs_id'             => 0,
            'cp_id'             => (int) $cp_id,
            'mt_id'             => (int) $mt_id,
            'cs_enabled'        => 0,
            'cs_alias'          => '',
            'cs_forward1'       => '',
            'cs_forward2'       => '',
            'cs_admin_enabled'  => 1,
            'cs_recording_mode' => lc_settings_get('callRecordingMode', 'normal'),
            'cs_coloring'       => '',
            'cs_call_ment'      => '',
            'cs_business_start' => '00:00',
            'cs_business_end'   => '23:59',
            'cs_holiday_weeks'  => '',
            'cs_holiday_days'   => '',
            'cs_price'          => 0,
            'cs_min_duration'   => (int) lc_settings_get_int('callMinDuration', 0),
            'cs_memo'           => '',
        );
    }
}

if (!function_exists('lc_call_settings_get')) {
    function lc_call_settings_get($cp_id, $mt_id = 0)
    {
        $cp_id = (int) $cp_id;
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('call_settings'))) {
            return lc_call_settings_defaults($cp_id, $mt_id);
        }

        $table = lc_table('call_settings');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        if (!$row) {
            return lc_call_settings_defaults($cp_id, $mt_id);
        }

        return $row;
    }
}

if (!function_exists('lc_call_settings_save')) {
    /**
     * 저장 범위(scope): 'merchant' = 광고주 편집 필드만, 'admin' = 관리자 필드만.
     */
    function lc_call_settings_save($cp_id, array $payload, $scope = 'admin')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '캠페인 정보가 없습니다.');
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT cp_id, mt_id FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        if (!$campaign) {
            return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
        }
        $mt_id = (int) $campaign['mt_id'];

        $table = lc_table('call_settings');
        $before = lc_call_settings_get($cp_id, $mt_id);
        $existing = !empty($before['cs_id']);

        // 광고주가 편집 가능한 필드 (관리자도 수신번호 입력 가능)
        $merchant_fields = array(
            'cs_enabled'  => isset($payload['enabled']) ? (!empty($payload['enabled']) ? 1 : 0) : null,
            'cs_alias'    => isset($payload['alias']) ? (string) $payload['alias'] : null,
            'cs_forward1' => isset($payload['forward1']) ? lc_call_number_normalize($payload['forward1']) : null,
            'cs_forward2' => isset($payload['forward2']) ? lc_call_number_normalize($payload['forward2']) : null,
        );

        // 관리자 전용 필드
        $admin_fields = array(
            'cs_admin_enabled'  => isset($payload['adminEnabled']) ? (!empty($payload['adminEnabled']) ? 1 : 0) : null,
            'cs_recording_mode' => isset($payload['recordingMode']) ? (string) $payload['recordingMode'] : null,
            'cs_coloring'       => isset($payload['coloring']) ? (string) $payload['coloring'] : null,
            'cs_call_ment'      => isset($payload['callMent']) ? (string) $payload['callMent'] : null,
            'cs_business_start' => isset($payload['businessStart']) ? (string) $payload['businessStart'] : null,
            'cs_business_end'   => isset($payload['businessEnd']) ? (string) $payload['businessEnd'] : null,
            'cs_holiday_weeks'  => isset($payload['holidayWeeks']) ? (string) $payload['holidayWeeks'] : null,
            'cs_holiday_days'   => isset($payload['holidayDays']) ? (string) $payload['holidayDays'] : null,
            'cs_price'          => isset($payload['price']) ? (int) $payload['price'] : null,
            'cs_min_duration'   => isset($payload['minDuration']) ? (int) $payload['minDuration'] : null,
            'cs_memo'           => isset($payload['memo']) ? (string) $payload['memo'] : null,
        );

        $fields = array();
        if ($scope === 'merchant' || $scope === 'all') {
            $fields = array_merge($fields, $merchant_fields);
        }
        if ($scope === 'admin' || $scope === 'all') {
            $fields = array_merge($fields, $admin_fields);
            // 관리자도 수신번호 1·2 설정 가능
            foreach (array('cs_forward1', 'cs_forward2', 'cs_alias', 'cs_enabled') as $fwd_col) {
                if (array_key_exists($fwd_col, $merchant_fields) && $merchant_fields[$fwd_col] !== null) {
                    $fields[$fwd_col] = $merchant_fields[$fwd_col];
                }
            }
        }

        $sets = array();
        foreach ($fields as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (is_int($val)) {
                $sets[] = "`{$col}` = '" . (int) $val . "'";
            } else {
                $sets[] = "`{$col}` = '" . lc_sql_escape($val) . "'";
            }
        }

        if ($existing) {
            if (!$sets) {
                return array('ok' => true, 'message' => '변경사항이 없습니다.');
            }
            $sets[] = 'cs_updated_at = NOW()';
            lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE cp_id = '{$cp_id}' ", false);
        } else {
            $base = lc_call_settings_defaults($cp_id, $mt_id);
            $merged = array();
            foreach ($fields as $col => $val) {
                if ($val !== null) {
                    $merged[$col] = $val;
                }
            }
            $insert = array(
                'cp_id' => $cp_id,
                'mt_id' => $mt_id,
            );
            foreach (array('cs_enabled','cs_alias','cs_forward1','cs_forward2','cs_admin_enabled','cs_recording_mode','cs_coloring','cs_call_ment','cs_business_start','cs_business_end','cs_holiday_weeks','cs_holiday_days','cs_price','cs_min_duration','cs_memo') as $col) {
                $insert[$col] = array_key_exists($col, $merged) ? $merged[$col] : $base[$col];
            }
            $cols = array();
            foreach ($insert as $col => $val) {
                if (is_int($val)) {
                    $cols[] = "`{$col}` = '" . (int) $val . "'";
                } else {
                    $cols[] = "`{$col}` = '" . lc_sql_escape($val) . "'";
                }
            }
            $cols[] = 'cs_created_at = NOW()';
            $cols[] = 'cs_updated_at = NOW()';
            lc_sql_query(" INSERT INTO `{$table}` SET " . implode(', ', $cols) . " ", false);
        }

        $after = lc_call_settings_get($cp_id, $mt_id);

        if ($scope === 'merchant' && function_exists('lc_call_notify_admin_forward_changed')) {
            lc_call_notify_admin_forward_changed($cp_id, $mt_id, $before, $after);
        }

        return array('ok' => true, 'message' => '콜 설정이 저장되었습니다.', 'settings' => $after);
    }
}

if (!function_exists('lc_call_notify_admin_forward_changed')) {
    /**
     * 광고주가 수신번호(착신번호)를 변경하면 관리자에게 중요알림.
     */
    function lc_call_notify_admin_forward_changed($cp_id, $mt_id, array $before, array $after)
    {
        if (!function_exists('lc_notification_create')) {
            return;
        }

        $old1 = (string) ($before['cs_forward1'] ?? '');
        $old2 = (string) ($before['cs_forward2'] ?? '');
        $new1 = (string) ($after['cs_forward1'] ?? '');
        $new2 = (string) ($after['cs_forward2'] ?? '');

        if ($old1 === $new1 && $old2 === $new2) {
            return;
        }

        $cp_id = (int) $cp_id;
        $mt_id = (int) $mt_id;
        $cp_name = '';
        $mt_name = '';

        $cp_table = lc_table('campaigns');
        $cp = lc_sql_fetch(" SELECT cp_name FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        if ($cp) {
            $cp_name = (string) $cp['cp_name'];
        }

        if ($mt_id > 0) {
            $mt_table = lc_table('merchants');
            $mt = lc_sql_fetch(" SELECT mt_company FROM `{$mt_table}` WHERE mt_id = '{$mt_id}' LIMIT 1 ");
            if ($mt) {
                $mt_name = (string) $mt['mt_company'];
            }
        }

        $fmt = function ($n) {
            $n = trim((string) $n);
            return $n !== '' ? $n : '(없음)';
        };

        $body = sprintf(
            '%s / %s — 수신1: %s → %s, 수신2: %s → %s',
            $mt_name !== '' ? $mt_name : ('광고주#' . $mt_id),
            $cp_name !== '' ? $cp_name : ('캠페인#' . $cp_id),
            $fmt($old1),
            $fmt($new1),
            $fmt($old2),
            $fmt($new2)
        );
        if (function_exists('mb_substr')) {
            $body = mb_substr($body, 0, 480);
        } else {
            $body = substr($body, 0, 480);
        }

        lc_notification_create(array(
            'center'   => 'admin',
            'userId'   => 0,
            'type'     => 'call',
            'priority' => 'critical',
            'title'    => '광고주 콜디비 수신번호 변경',
            'body'     => $body,
            'link'     => '/admin/call',
            'refType'  => 'campaign',
            'refId'    => $cp_id,
        ));
    }
}

/* ───────────────────────────── 가상번호 신청 / 배정 ───────────────────────────── */

if (!function_exists('lc_call_request_get')) {
    function lc_call_request_get($car_id)
    {
        if (!lc_db_installed()) {
            return null;
        }
        $table = lc_table('call_requests');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE car_id = '" . (int) $car_id . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_call_requests_list')) {
    function lc_call_requests_list(array $filters = array())
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('call_requests'))) {
            return array();
        }

        $car = lc_table('call_requests');
        $pt = lc_table('partners');
        $cp = lc_table('campaigns');

        $where = ' 1=1 ';
        if (!empty($filters['status'])) {
            $where .= " AND r.car_status = '" . lc_sql_escape($filters['status']) . "' ";
        }
        if (!empty($filters['pt_id'])) {
            $where .= " AND r.pt_id = '" . (int) $filters['pt_id'] . "' ";
        }
        if (!empty($filters['cp_id'])) {
            $where .= " AND r.cp_id = '" . (int) $filters['cp_id'] . "' ";
        }

        $rows = array();
        $sql = " SELECT r.*, p.pt_code, p.pt_name, c.cp_name
            FROM `{$car}` r
            LEFT JOIN `{$pt}` p ON p.pt_id = r.pt_id
            LEFT JOIN `{$cp}` c ON c.cp_id = r.cp_id
            WHERE {$where}
            ORDER BY r.car_id DESC LIMIT 500 ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_call_request_create')) {
    /**
     * 파트너가 캠페인에 대해 가상번호 신청.
     */
    function lc_call_request_create($pt_id, $cp_id, $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        if ($pt_id <= 0 || $cp_id <= 0) {
            return array('ok' => false, 'message' => '파트너/캠페인 정보가 필요합니다.');
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT cp_id, mt_id FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        if (!$campaign) {
            return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
        }

        $table = lc_table('call_requests');
        $dup = lc_sql_fetch(" SELECT car_id FROM `{$table}` WHERE pt_id = '{$pt_id}' AND cp_id = '{$cp_id}' AND car_status IN ('" . LC_CALL_REQ_PENDING . "','" . LC_CALL_REQ_ASSIGNED . "') LIMIT 1 ");
        if ($dup) {
            return array('ok' => false, 'message' => '이미 신청했거나 배정된 캠페인입니다.');
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            mt_id = '" . (int) $campaign['mt_id'] . "',
            car_status = '" . LC_CALL_REQ_PENDING . "',
            car_request_memo = '" . lc_sql_escape($memo) . "',
            car_created_at = NOW() ", false);

        $car_id = (int) lc_sql_insert_id();

        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'admin',
                'type'    => 'call',
                'title'   => '가상번호 신청',
                'body'    => '파트너 #' . $pt_id . ' · 캠페인 #' . $cp_id,
                'link'    => '/admin/call',
                'refType' => 'call_request',
                'refId'   => $car_id,
            ));
        }

        return array('ok' => true, 'message' => '가상번호를 신청했습니다. 관리자 배정을 기다려주세요.', 'carId' => $car_id);
    }
}

if (!function_exists('lc_call_request_assign')) {
    /**
     * 관리자가 신청에 가상번호 배정.
     */
    function lc_call_request_assign($car_id, $cn_id, $admin_memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $car_id = (int) $car_id;
        $cn_id = (int) $cn_id;

        $request = lc_call_request_get($car_id);
        if (!$request) {
            return array('ok' => false, 'message' => '신청 내역을 찾을 수 없습니다.');
        }
        if ($request['car_status'] === LC_CALL_REQ_ASSIGNED) {
            return array('ok' => false, 'message' => '이미 배정된 신청입니다.');
        }

        $number = lc_call_number_get($cn_id);
        if (!$number) {
            return array('ok' => false, 'message' => '가상번호를 찾을 수 없습니다.');
        }
        if ($number['cn_status'] !== LC_CALL_NUMBER_AVAILABLE) {
            return array('ok' => false, 'message' => '사용 가능한 번호가 아닙니다. (현재: ' . $number['cn_status'] . ')');
        }

        $car_table = lc_table('call_requests');
        $cn_table = lc_table('call_numbers');

        lc_sql_query(" UPDATE `{$car_table}` SET
            car_status = '" . LC_CALL_REQ_ASSIGNED . "',
            cn_id = '{$cn_id}',
            car_virtual_number = '" . lc_sql_escape($number['cn_number']) . "',
            car_admin_memo = '" . lc_sql_escape($admin_memo) . "',
            car_processed_at = NOW()
            WHERE car_id = '{$car_id}' ", false);

        lc_sql_query(" UPDATE `{$cn_table}` SET cn_status = '" . LC_CALL_NUMBER_ASSIGNED . "', cn_updated_at = NOW() WHERE cn_id = '{$cn_id}' ", false);

        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'partner',
                'userId'  => (int) $request['pt_id'],
                'type'    => 'call',
                'title'   => '가상번호 배정 완료',
                'body'    => $number['cn_number'] . ' 번호가 배정되었습니다.',
                'link'    => '/partner/call',
                'refType' => 'call_request',
                'refId'   => $car_id,
            ));
        }

        return array('ok' => true, 'message' => '가상번호를 배정했습니다.', 'number' => $number['cn_number'], 'cpId' => (int) $request['cp_id']);
    }
}

if (!function_exists('lc_call_assign_apply_price')) {
    /**
     * 가상번호 배정 시 캠페인 콜 단가·활성화 적용.
     *
     * @return array{ok:bool,message:string}
     */
    function lc_call_assign_apply_price($cp_id, $price)
    {
        $cp_id = (int) $cp_id;
        $price = (int) $price;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '캠페인 정보가 없습니다.');
        }
        if ($price <= 0) {
            return array('ok' => false, 'message' => '콜당 디비 단가를 입력하세요.');
        }

        return lc_call_settings_save($cp_id, array(
            'adminEnabled' => true,
            'price'        => $price,
        ), 'admin');
    }
}

if (!function_exists('lc_call_request_reject')) {
    function lc_call_request_reject($car_id, $admin_memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $car_id = (int) $car_id;
        $request = lc_call_request_get($car_id);
        if (!$request) {
            return array('ok' => false, 'message' => '신청 내역을 찾을 수 없습니다.');
        }

        $table = lc_table('call_requests');
        lc_sql_query(" UPDATE `{$table}` SET
            car_status = '" . LC_CALL_REQ_REJECTED . "',
            car_admin_memo = '" . lc_sql_escape($admin_memo) . "',
            car_processed_at = NOW()
            WHERE car_id = '{$car_id}' ", false);

        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'partner',
                'userId'  => (int) $request['pt_id'],
                'type'    => 'call',
                'title'   => '가상번호 신청 반려',
                'body'    => $admin_memo !== '' ? $admin_memo : '신청이 반려되었습니다.',
                'link'    => '/partner/call',
                'refType' => 'call_request',
                'refId'   => $car_id,
            ));
        }

        return array('ok' => true, 'message' => '신청을 반려했습니다.');
    }
}

if (!function_exists('lc_call_request_revoke')) {
    /**
     * 배정된 번호 회수 → 번호를 다시 available 로.
     */
    function lc_call_request_revoke($car_id, $admin_memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }
        $car_id = (int) $car_id;
        $request = lc_call_request_get($car_id);
        if (!$request) {
            return array('ok' => false, 'message' => '신청 내역을 찾을 수 없습니다.');
        }

        $car_table = lc_table('call_requests');
        $cn_table = lc_table('call_numbers');

        lc_sql_query(" UPDATE `{$car_table}` SET car_status = '" . LC_CALL_REQ_REVOKED . "', car_admin_memo = '" . lc_sql_escape($admin_memo) . "', car_processed_at = NOW() WHERE car_id = '{$car_id}' ", false);
        if ((int) $request['cn_id'] > 0) {
            lc_sql_query(" UPDATE `{$cn_table}` SET cn_status = '" . LC_CALL_NUMBER_AVAILABLE . "', cn_updated_at = NOW() WHERE cn_id = '" . (int) $request['cn_id'] . "' ", false);
        }

        return array('ok' => true, 'message' => '번호를 회수했습니다.');
    }
}

if (!function_exists('lc_call_assignment_by_number')) {
    /**
     * 활성 배정(가상번호 → 파트너/캠페인) 조회.
     */
    function lc_call_assignment_by_number($virtual_number)
    {
        if (!lc_db_installed()) {
            return null;
        }
        $number = lc_call_number_normalize($virtual_number);
        if ($number === '') {
            return null;
        }
        $table = lc_table('call_requests');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE car_virtual_number = '" . lc_sql_escape($number) . "' AND car_status = '" . LC_CALL_REQ_ASSIGNED . "' ORDER BY car_id DESC LIMIT 1 ");
    }
}

/* ───────────────────────────── 통화로그 / 전환 생성 ───────────────────────────── */

if (!function_exists('lc_call_normalize_result')) {
    function lc_call_normalize_result($result, $duration)
    {
        $result = strtolower(trim((string) $result));
        $map = array(
            'success' => LC_CALL_RESULT_SUCCESS, 'answered' => LC_CALL_RESULT_SUCCESS, 'completed' => LC_CALL_RESULT_SUCCESS, 'connect' => LC_CALL_RESULT_SUCCESS,
            'missed' => LC_CALL_RESULT_MISSED, 'noanswer' => LC_CALL_RESULT_MISSED, 'no-answer' => LC_CALL_RESULT_MISSED,
            'busy' => LC_CALL_RESULT_BUSY,
            'fail' => LC_CALL_RESULT_FAIL, 'failed' => LC_CALL_RESULT_FAIL, 'canceled' => LC_CALL_RESULT_FAIL,
        );
        if (isset($map[$result])) {
            return $map[$result];
        }

        return (int) $duration > 0 ? LC_CALL_RESULT_SUCCESS : LC_CALL_RESULT_MISSED;
    }
}

if (!function_exists('lc_call_ingest_log')) {
    /**
     * 콜업체 웹훅/조회로 받은 통화 1건 적재 + 조건 충족 시 전환 생성.
     *
     * @param array $payload {
     *   providerCallId, virtualNumber, caller, callee,
     *   startedAt, duration, result, recordingUrl, recordingId
     * }
     * @return array{ok:bool,message:string,clogId?:int,cvCode?:string,duplicate?:bool}
     */
    function lc_call_ingest_log(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $provider_call_id = trim((string) ($payload['providerCallId'] ?? $payload['callId'] ?? ''));
        $virtual_number = lc_call_number_normalize($payload['virtualNumber'] ?? $payload['calledNumber'] ?? $payload['did'] ?? '');
        $caller = lc_call_number_normalize($payload['caller'] ?? $payload['from'] ?? $payload['callerNumber'] ?? '');
        $callee = lc_call_number_normalize($payload['callee'] ?? $payload['to'] ?? '');
        $duration = (int) ($payload['duration'] ?? $payload['durationSec'] ?? 0);
        $result = lc_call_normalize_result($payload['result'] ?? $payload['status'] ?? '', $duration);
        $recording_url = trim((string) ($payload['recordingUrl'] ?? $payload['recordUrl'] ?? ''));
        $recording_id = trim((string) ($payload['recordingId'] ?? $payload['recordId'] ?? ''));

        $started_at = '';
        $raw_started = (string) ($payload['startedAt'] ?? $payload['startTime'] ?? $payload['calledAt'] ?? '');
        if ($raw_started !== '') {
            $ts = strtotime($raw_started);
            if ($ts !== false) {
                $started_at = date('Y-m-d H:i:s', $ts);
            }
        }
        if ($started_at === '') {
            $started_at = date('Y-m-d H:i:s');
        }

        if ($virtual_number === '') {
            return array('ok' => false, 'message' => '가상번호(virtualNumber)가 필요합니다.');
        }

        $clog_table = lc_table('call_logs');

        // 중복 통화 방지 (provider call id)
        if ($provider_call_id !== '') {
            $exists = lc_sql_fetch(" SELECT clog_id FROM `{$clog_table}` WHERE clog_provider_call_id = '" . lc_sql_escape($provider_call_id) . "' LIMIT 1 ");
            if ($exists) {
                return array('ok' => true, 'message' => '이미 수신된 통화입니다.', 'clogId' => (int) $exists['clog_id'], 'duplicate' => true);
            }
        } else {
            // provider call id 미제공 시 UNIQUE 충돌 방지용 합성 키 생성
            $provider_call_id = 'auto-' . date('YmdHis') . '-' . substr(md5(uniqid('', true) . $virtual_number . $caller . $started_at), 0, 12);
        }

        $assignment = lc_call_assignment_by_number($virtual_number);
        $pt_id = $assignment ? (int) $assignment['pt_id'] : 0;
        $cp_id = $assignment ? (int) $assignment['cp_id'] : 0;
        $mt_id = $assignment ? (int) $assignment['mt_id'] : 0;
        $cn_id = $assignment ? (int) $assignment['cn_id'] : 0;
        $car_id = $assignment ? (int) $assignment['car_id'] : 0;

        lc_sql_query(" INSERT INTO `{$clog_table}` SET
            clog_provider_call_id = '" . lc_sql_escape($provider_call_id) . "',
            cn_id = '{$cn_id}',
            car_id = '{$car_id}',
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            mt_id = '{$mt_id}',
            clog_virtual_number = '" . lc_sql_escape($virtual_number) . "',
            clog_caller = '" . lc_sql_escape($caller) . "',
            clog_callee = '" . lc_sql_escape($callee) . "',
            clog_started_at = '" . lc_sql_escape($started_at) . "',
            clog_duration = '{$duration}',
            clog_result = '" . lc_sql_escape($result) . "',
            clog_recording_url = '" . lc_sql_escape($recording_url) . "',
            clog_recording_id = '" . lc_sql_escape($recording_id) . "',
            cv_id = '0',
            clog_created_at = NOW() ", false);

        $clog_id = (int) lc_sql_insert_id();

        // INSERT 실패(동시성/UNIQUE 충돌) 방어: 기존 로그 재조회
        if ($clog_id <= 0) {
            $again = lc_sql_fetch(" SELECT clog_id FROM `{$clog_table}` WHERE clog_provider_call_id = '" . lc_sql_escape($provider_call_id) . "' LIMIT 1 ");
            if ($again) {
                return array('ok' => true, 'message' => '이미 수신된 통화입니다.', 'clogId' => (int) $again['clog_id'], 'duplicate' => true);
            }

            return array('ok' => false, 'message' => '통화 기록 저장에 실패했습니다.');
        }

        $out = array('ok' => true, 'message' => '통화가 기록되었습니다.', 'clogId' => $clog_id);

        // 배정 없음 → 미매칭 로그만 남김 (관리자 확인용)
        if (!$assignment) {
            $out['message'] = '배정되지 않은 가상번호 통화입니다. (미매칭)';

            return $out;
        }

        if (!empty($payload['skipConversion'])) {
            $out['message'] = '통화 기록됨 (전환 생성 생략)';

            return $out;
        }

        // 전환 생성 조건 판정
        $create = lc_call_should_create_conversion($cp_id, $result, $duration);
        if (!$create['create']) {
            $out['message'] = '통화 기록됨 (전환 생성 제외: ' . $create['reason'] . ')';

            return $out;
        }

        $conv = lc_call_conversion_create(array(
            'clog_id'   => $clog_id,
            'pt_id'     => $pt_id,
            'cp_id'     => $cp_id,
            'caller'    => $caller,
            'duration'  => $duration,
            'result'    => $result,
            'started_at' => $started_at,
            'price'     => $create['price'],
        ));

        if ($conv['ok'] && !empty($conv['cvId'])) {
            lc_sql_query(" UPDATE `{$clog_table}` SET cv_id = '" . (int) $conv['cvId'] . "' WHERE clog_id = '{$clog_id}' ", false);
            $out['cvCode'] = $conv['cvCode'] ?? '';
            $out['message'] = '통화 기록 및 콜DB 전환이 생성되었습니다.';
        } else {
            $out['message'] = '통화 기록됨 (전환 생성 실패: ' . ($conv['message'] ?? '') . ')';
        }

        return $out;
    }
}

if (!function_exists('lc_call_should_create_conversion')) {
    /**
     * @return array{create:bool,reason:string,price:int}
     */
    function lc_call_should_create_conversion($cp_id, $result, $duration)
    {
        $settings = lc_call_settings_get((int) $cp_id);

        if (empty($settings['cs_admin_enabled'])) {
            return array('create' => false, 'reason' => '관리자 콜설정 비활성', 'price' => 0);
        }
        if (empty($settings['cs_enabled'])) {
            return array('create' => false, 'reason' => '광고주 콜디비 수신 OFF', 'price' => 0);
        }

        $min = (int) ($settings['cs_min_duration'] ?? 0);
        $create_on_missed = lc_settings_get_bool('callCreateOnMissed', false);

        if ($result === LC_CALL_RESULT_SUCCESS) {
            if ($min > 0 && (int) $duration < $min) {
                return array('create' => false, 'reason' => '최소 통화시간 미달(' . $duration . 's/' . $min . 's)', 'price' => 0);
            }
        } elseif ($result === LC_CALL_RESULT_MISSED) {
            if (!$create_on_missed) {
                return array('create' => false, 'reason' => '부재중 제외', 'price' => 0);
            }
        } else {
            return array('create' => false, 'reason' => '통화실패/통화중', 'price' => 0);
        }

        // 단가: 콜설정 cs_price > 캠페인 cp_price > 전역 callDefaultPrice
        $price = (int) ($settings['cs_price'] ?? 0);
        if ($price <= 0) {
            $cp_table = lc_table('campaigns');
            $campaign = lc_sql_fetch(" SELECT cp_price FROM `{$cp_table}` WHERE cp_id = '" . (int) $cp_id . "' LIMIT 1 ");
            $price = $campaign ? (int) $campaign['cp_price'] : 0;
        }
        if ($price <= 0) {
            $price = (int) lc_settings_get_int('callDefaultPrice', 0);
        }

        return array('create' => true, 'reason' => '', 'price' => $price);
    }
}

if (!function_exists('lc_call_conversion_create')) {
    /**
     * 콜 통화 기반 CPA 전환 생성 (cv_source = call).
     */
    function lc_call_conversion_create(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) ($payload['cp_id'] ?? 0);
        $pt_id = (int) ($payload['pt_id'] ?? 0);
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '캠페인 정보가 없습니다.');
        }

        $caller = (string) ($payload['caller'] ?? '');
        $duration = (int) ($payload['duration'] ?? 0);
        $result = (string) ($payload['result'] ?? '');
        $price = (int) ($payload['price'] ?? 0);
        $clog_id = (int) ($payload['clog_id'] ?? 0);
        $started_at = (string) ($payload['started_at'] ?? date('Y-m-d H:i:s'));

        $cv_code = lc_conversion_generate_code();
        $table = lc_table('conversions');
        $name = $caller !== '' ? lc_conversion_mask_phone($caller) : '콜인입';
        $mm = floor($duration / 60);
        $ss = $duration % 60;
        $inquiry = '콜디비 통화 ' . sprintf('%d분 %d초', $mm, $ss) . ' (' . $result . ')';
        $campaign = function_exists('lc_campaign_get_by_id') ? lc_campaign_get_by_id($cp_id) : null;
        $partner_price = is_array($campaign) ? lc_campaign_resolve_partner_price($campaign) : $price;

        lc_sql_query(" INSERT INTO `{$table}` SET
            cv_code = '" . lc_sql_escape($cv_code) . "',
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            lk_id = '0',
            cv_name = '" . lc_sql_escape($name) . "',
            cv_phone = '" . lc_sql_escape($caller) . "',
            cv_email = '',
            cv_region = '',
            cv_inquiry = '" . lc_sql_escape($inquiry) . "',
            cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "',
            cv_price = '{$price}',
            cv_partner_price = '" . (int) $partner_price . "',
            cv_channel = '콜디비',
            cv_sub_id = '',
            cv_comment = '',
            cv_source = '" . LC_SOURCE_CALL . "',
            cv_call_id = '{$clog_id}',
            cv_call_duration = '{$duration}',
            cv_call_result = '" . lc_sql_escape($result) . "',
            cv_created_at = '" . lc_sql_escape($started_at) . "',
            cv_updated_at = NOW() ", false);

        $cv_id = (int) lc_sql_insert_id();
        if ($cv_id <= 0) {
            return array('ok' => false, 'message' => '콜DB 전환 생성 실패');
        }

        if (function_exists('lc_notification_emit_conversion')) {
            $meta = function_exists('lc_conversion_with_meta') ? lc_conversion_with_meta($cv_id) : lc_conversion_get_by_id($cv_id);
            if (is_array($meta)) {
                lc_notification_emit_conversion($meta, 'received');
            }
        }

        return array('ok' => true, 'message' => '콜DB 전환 생성됨', 'cvId' => $cv_id, 'cvCode' => $cv_code);
    }
}

if (!function_exists('lc_call_request_assign_direct')) {
    /**
     * API 연동 전 수동 운영: 관리자가 파트너·캠페인에 가상번호를 직접 배정.
     * 파트너 신청이 없으면 신청 레코드를 만들고 즉시 배정합니다.
     */
    function lc_call_request_assign_direct($pt_id, $cp_id, $cn_id, $admin_memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        $cn_id = (int) $cn_id;
        if ($pt_id <= 0 || $cp_id <= 0 || $cn_id <= 0) {
            return array('ok' => false, 'message' => '파트너, 캠페인, 가상번호를 모두 선택해주세요.');
        }

        $pt_table = lc_table('partners');
        $partner = lc_sql_fetch(" SELECT pt_id FROM `{$pt_table}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
        if (!$partner) {
            return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT cp_id, mt_id FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
        if (!$campaign) {
            return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
        }

        $car_table = lc_table('call_requests');
        $existing = lc_sql_fetch(" SELECT car_id, car_status FROM `{$car_table}`
            WHERE pt_id = '{$pt_id}' AND cp_id = '{$cp_id}'
              AND car_status IN ('" . LC_CALL_REQ_PENDING . "','" . LC_CALL_REQ_ASSIGNED . "')
            ORDER BY car_id DESC LIMIT 1 ");

        if ($existing && $existing['car_status'] === LC_CALL_REQ_ASSIGNED) {
            return array('ok' => false, 'message' => '이미 배정된 파트너·캠페인입니다.');
        }

        $car_id = 0;
        if ($existing && $existing['car_status'] === LC_CALL_REQ_PENDING) {
            $car_id = (int) $existing['car_id'];
        } else {
            lc_sql_query(" INSERT INTO `{$car_table}` SET
                pt_id = '{$pt_id}',
                cp_id = '{$cp_id}',
                mt_id = '" . (int) $campaign['mt_id'] . "',
                car_status = '" . LC_CALL_REQ_PENDING . "',
                car_request_memo = '관리자 직접 배정',
                car_created_at = NOW() ", false);
            $car_id = (int) lc_sql_insert_id();
            if ($car_id <= 0) {
                return array('ok' => false, 'message' => '배정 요청 생성에 실패했습니다.');
            }
        }

        return lc_call_request_assign($car_id, $cn_id, $admin_memo !== '' ? $admin_memo : '관리자 직접 배정');
    }
}

if (!function_exists('lc_call_logs_import_column_aliases')) {
    function lc_call_logs_import_column_aliases()
    {
        return array(
            'virtualNumber'  => array('가상번호', 'virtualnumber', 'virtual_number', 'did', 'callednumber', 'called_number', '착신번호', '수신번호', '050'),
            'caller'         => array('발신번호', 'caller', 'from', '발신', '고객번호', '고객 전화번호', 'caller_number'),
            'startedAt'      => array('통화시작', '통화일시', '시작일시', 'startedat', 'starttime', 'start_time', 'calledat', '일시'),
            'duration'       => array('통화시간', '통화시간초', 'duration', 'durationsec', 'duration_sec', '초', '통화(초)'),
            'result'         => array('결과', 'result', 'status', '통화결과', '상태'),
            'providerCallId' => array('통화id', '통화고유id', 'providercallid', 'callid', 'call_id', '고유id'),
            'recordingUrl'   => array('녹취url', 'recordingurl', 'recording_url', 'recordurl', '녹취', '녹취주소'),
        );
    }
}

if (!function_exists('lc_call_logs_import_normalize_header')) {
    function lc_call_logs_import_normalize_header($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/u', '', $value);
        $value = str_replace(array('_', '-', '.'), '', $value);

        return $value;
    }
}

if (!function_exists('lc_call_logs_import_map_headers')) {
  /**
   * @param array<int,string> $headers
   * @return array<string,int>
   */
    function lc_call_logs_import_map_headers(array $headers)
    {
        $aliases = lc_call_logs_import_column_aliases();
        $map = array();
        foreach ($headers as $idx => $header) {
            $norm = lc_call_logs_import_normalize_header($header);
            if ($norm === '') {
                continue;
            }
            foreach ($aliases as $field => $names) {
                if (isset($map[$field])) {
                    continue;
                }
                foreach ($names as $name) {
                    if ($norm === lc_call_logs_import_normalize_header($name)) {
                        $map[$field] = (int) $idx;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('lc_call_logs_import_parse_rows')) {
    /**
     * 업로드 파일(xlsx/xls/csv)을 통화 ingest payload 배열로 변환.
     *
     * @return array{ok:bool,message:string,rows?:array<int,array<string,mixed>>,headers?:array<int,string>}
     */
    function lc_call_logs_import_parse_file($tmp_path, $filename)
    {
        if (!is_readable($tmp_path)) {
            return array('ok' => false, 'message' => '파일을 읽을 수 없습니다.');
        }

        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $matrix = array();

        if (in_array($ext, array('xlsx', 'xls'), true) && defined('G5_LIB_PATH') && is_file(G5_LIB_PATH . '/PHPExcel/IOFactory.php')) {
            include_once G5_LIB_PATH . '/PHPExcel/IOFactory.php';
            try {
                $obj = PHPExcel_IOFactory::load($tmp_path);
                $sheet = $obj->getSheet(0);
                $highest_row = (int) $sheet->getHighestRow();
                $highest_col = $sheet->getHighestColumn();
                $col_count = PHPExcel_Cell::columnIndexFromString($highest_col);
                for ($r = 1; $r <= $highest_row; $r++) {
                    $row = array();
                    for ($c = 0; $c < $col_count; $c++) {
                        $cell = $sheet->getCellByColumnAndRow($c, $r);
                        $value = $cell ? trim((string) $cell->getFormattedValue()) : '';
                        $row[] = $value;
                    }
                    if (implode('', $row) !== '') {
                        $matrix[] = $row;
                    }
                }
            } catch (Exception $e) {
                return array('ok' => false, 'message' => '엑셀 파일을 읽지 못했습니다.');
            }
        } else {
            $raw = file_get_contents($tmp_path);
            if ($raw === false) {
                return array('ok' => false, 'message' => '파일을 읽을 수 없습니다.');
            }
            if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
                $raw = substr($raw, 3);
            }
            $delimiter = (substr_count($raw, "\t") > substr_count($raw, ',')) ? "\t" : ',';
            $lines = preg_split('/\r\n|\r|\n/', $raw);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = str_getcsv($line, $delimiter);
                if ($row && implode('', $row) !== '') {
                    $matrix[] = $row;
                }
            }
        }

        if (count($matrix) < 2) {
            return array('ok' => false, 'message' => '헤더와 데이터 행이 필요합니다.');
        }

        $headers = array_map('trim', $matrix[0]);
        $map = lc_call_logs_import_map_headers($headers);
        if (!isset($map['virtualNumber'])) {
            return array('ok' => false, 'message' => '가상번호 열을 찾을 수 없습니다. (가상번호 / virtualNumber 등)');
        }

        $rows = array();
        for ($i = 1, $n = count($matrix); $i < $n; $i++) {
            $line = $matrix[$i];
            $virtual = trim((string) ($line[$map['virtualNumber']] ?? ''));
            if ($virtual === '') {
                continue;
            }

            $payload = array(
                'virtualNumber' => $virtual,
                'caller'        => isset($map['caller']) ? (string) ($line[$map['caller']] ?? '') : '',
                'startedAt'     => isset($map['startedAt']) ? (string) ($line[$map['startedAt']] ?? '') : '',
                'duration'      => isset($map['duration']) ? (string) ($line[$map['duration']] ?? '') : '',
                'result'        => isset($map['result']) ? (string) ($line[$map['result']] ?? '') : '',
                'providerCallId'=> isset($map['providerCallId']) ? (string) ($line[$map['providerCallId']] ?? '') : '',
                'recordingUrl'  => isset($map['recordingUrl']) ? (string) ($line[$map['recordingUrl']] ?? '') : '',
                'importRow'     => $i + 1,
            );

            if ($payload['providerCallId'] === '') {
                $payload['providerCallId'] = 'import-' . date('Ymd') . '-' . $i . '-' . substr(md5($virtual . $payload['caller'] . $payload['startedAt']), 0, 10);
            }

            $rows[] = $payload;
        }

        if (!$rows) {
            return array('ok' => false, 'message' => '등록할 통화 데이터가 없습니다.');
        }

        return array('ok' => true, 'message' => count($rows) . '건 파싱됨', 'rows' => $rows, 'headers' => $headers);
    }
}

if (!function_exists('lc_call_logs_import_bulk')) {
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{ok:bool,message:string,total:int,imported:int,duplicate:int,failed:int,unmatched:int,items:array<int,array<string,mixed>>}
     */
    function lc_call_logs_import_bulk(array $rows, $skip_conversion = false)
    {
        $summary = array(
            'ok'        => true,
            'message'   => '',
            'total'     => count($rows),
            'imported'  => 0,
            'duplicate' => 0,
            'failed'    => 0,
            'unmatched' => 0,
            'items'     => array(),
        );

        foreach ($rows as $row) {
            if ($skip_conversion) {
                $row['skipConversion'] = true;
            }
            $res = lc_call_ingest_log($row);
            $item = array(
                'row'     => (int) ($row['importRow'] ?? 0),
                'virtualNumber' => (string) ($row['virtualNumber'] ?? ''),
                'ok'      => !empty($res['ok']),
                'message' => (string) ($res['message'] ?? ''),
                'clogId'  => (int) ($res['clogId'] ?? 0),
                'duplicate' => !empty($res['duplicate']),
            );
            $summary['items'][] = $item;

            if (!$res['ok']) {
                $summary['failed']++;
                continue;
            }
            if (!empty($res['duplicate'])) {
                $summary['duplicate']++;
                continue;
            }
            if (strpos((string) $res['message'], '미매칭') !== false) {
                $summary['unmatched']++;
            }
            $summary['imported']++;
        }

        $summary['message'] = sprintf(
            '총 %d건 · 신규 %d건 · 중복 %d건 · 실패 %d건 · 미매칭 %d건',
            $summary['total'],
            $summary['imported'],
            $summary['duplicate'],
            $summary['failed'],
            $summary['unmatched']
        );

        return $summary;
    }
}

/* ───────────────────────────── 통화로그 조회 ───────────────────────────── */

if (!function_exists('lc_call_logs_list')) {
    function lc_call_logs_list(array $filters = array())
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_table('call_logs'))) {
            return array();
        }

        $clog = lc_table('call_logs');
        $cp = lc_table('campaigns');
        $pt = lc_table('partners');

        $where = ' 1=1 ';
        if (!empty($filters['pt_id'])) {
            $where .= " AND l.pt_id = '" . (int) $filters['pt_id'] . "' ";
        }
        if (!empty($filters['cp_id'])) {
            $where .= " AND l.cp_id = '" . (int) $filters['cp_id'] . "' ";
        }
        if (!empty($filters['mt_id'])) {
            $where .= " AND l.mt_id = '" . (int) $filters['mt_id'] . "' ";
        }
        if (!empty($filters['result'])) {
            $where .= " AND l.clog_result = '" . lc_sql_escape($filters['result']) . "' ";
        }
        if (isset($filters['unmatched']) && $filters['unmatched']) {
            $where .= " AND l.pt_id = '0' ";
        }
        if (!empty($filters['virtual_number'])) {
            $vn = lc_call_number_normalize($filters['virtual_number']);
            if ($vn !== '') {
                $where .= " AND l.clog_virtual_number = '" . lc_sql_escape($vn) . "' ";
            }
        }

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 200;

        $rows = array();
        $sql = " SELECT l.*, c.cp_name, p.pt_code
            FROM `{$clog}` l
            LEFT JOIN `{$cp}` c ON c.cp_id = l.cp_id
            LEFT JOIN `{$pt}` p ON p.pt_id = l.pt_id
            WHERE {$where}
            ORDER BY l.clog_id DESC LIMIT {$limit} ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_call_log_get')) {
    function lc_call_log_get($clog_id)
    {
        if (!lc_db_installed()) {
            return null;
        }
        $table = lc_table('call_logs');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE clog_id = '" . (int) $clog_id . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_call_recording_url')) {
    /**
     * 녹취 URL (엑셀 업로드 시 저장된 URL만 사용)
     */
    function lc_call_recording_url($clog_id)
    {
        $log = lc_call_log_get($clog_id);
        if (!$log) {
            return array('ok' => false, 'message' => '통화 기록을 찾을 수 없습니다.');
        }
        if (!empty($log['clog_recording_url'])) {
            return array('ok' => true, 'url' => (string) $log['clog_recording_url']);
        }

        return array('ok' => false, 'message' => '녹취 URL이 없습니다. 엑셀 업로드 시 녹취URL 열을 포함해 주세요.');
    }
}

/* ───────────────────────────── to_api ───────────────────────────── */

if (!function_exists('lc_call_number_to_api')) {
    function lc_call_number_to_api(array $row)
    {
        return array(
            'cnId'       => (int) $row['cn_id'],
            'number'     => (string) $row['cn_number'],
            'provider'   => (string) $row['cn_provider'],
            'status'     => (string) $row['cn_status'],
            'memo'       => (string) $row['cn_memo'],
            'createdAt'  => date('Y.m.d', strtotime($row['cn_created_at'])),
        );
    }
}

if (!function_exists('lc_call_request_to_api')) {
    function lc_call_request_to_api(array $row)
    {
        return array(
            'carId'        => (int) $row['car_id'],
            'ptId'         => (int) $row['pt_id'],
            'partner'      => (string) ($row['pt_code'] ?? ($row['pt_name'] ?? '')),
            'cpId'         => (int) $row['cp_id'],
            'campaign'     => (string) ($row['cp_name'] ?? ''),
            'status'       => (string) $row['car_status'],
            'virtualNumber' => (string) $row['car_virtual_number'],
            'requestMemo'  => (string) $row['car_request_memo'],
            'adminMemo'    => (string) $row['car_admin_memo'],
            'createdAt'    => date('Y.m.d H:i', strtotime($row['car_created_at'])),
            'processedAt'  => !empty($row['car_processed_at']) ? date('Y.m.d H:i', strtotime($row['car_processed_at'])) : '',
        );
    }
}

if (!function_exists('lc_call_log_to_api')) {
    /**
     * @param bool $with_recording 관리자만 true (녹취 노출)
     */
    function lc_call_log_to_api(array $row, $with_recording = false, $mask = true)
    {
        $caller = (string) $row['clog_caller'];
        $out = array(
            'clogId'        => (int) $row['clog_id'],
            'virtualNumber' => (string) $row['clog_virtual_number'],
            'caller'        => $mask ? lc_conversion_mask_phone($caller) : $caller,
            'campaign'      => (string) ($row['cp_name'] ?? ''),
            'partner'       => (string) ($row['pt_code'] ?? '-'),
            'startedAt'     => !empty($row['clog_started_at']) ? date('Y.m.d H:i', strtotime($row['clog_started_at'])) : '',
            'duration'      => (int) $row['clog_duration'],
            'result'        => (string) $row['clog_result'],
            'cvId'          => (int) $row['cv_id'],
            'hasRecording'  => !empty($row['clog_recording_url']),
        );
        if ($with_recording) {
            $out['recordingUrl'] = (string) ($row['clog_recording_url'] ?? '');
        }

        return $out;
    }
}
