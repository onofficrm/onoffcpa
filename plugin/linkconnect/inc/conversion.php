<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_conversion_status_label')) {
    function lc_conversion_status_label($status)
    {
        $labels = array(
            LC_STATUS_PENDING  => '신규접수',
            LC_STATUS_APPROVED => '승인완료',
            LC_STATUS_REJECTED => '취소/무효',
            LC_STATUS_SETTLED  => '정산완료',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_conversion_mask_phone')) {
    function lc_conversion_mask_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        if (strlen($phone) < 8) {
            return (string) $phone;
        }

        return substr($phone, 0, 3) . '-****-' . substr($phone, -4);
    }
}

if (!function_exists('lc_conversion_format_phone')) {
    function lc_conversion_format_phone($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7);
        }
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
        }

        return trim((string) $phone);
    }
}

if (!function_exists('lc_conversion_merchant_history')) {
    function lc_conversion_merchant_history(array $row)
    {
        $history = array();
        $created = (string) ($row['cv_created_at'] ?? '');
        if ($created !== '') {
            $history[] = array(
                'time' => date('Y.m.d H:i', strtotime($created)),
                'text' => '신규 디비 접수',
            );
        }

        $status = (string) ($row['cv_status'] ?? '');
        $updated = (string) ($row['cv_updated_at'] ?? '');
        if ($updated !== '' && $updated !== $created && $status !== LC_STATUS_PENDING) {
            $history[] = array(
                'time' => date('Y.m.d H:i', strtotime($updated)),
                'text' => lc_conversion_status_label($status) . ' 처리',
            );
        }

        return $history;
    }
}

if (!function_exists('lc_conversion_needs_action')) {
    function lc_conversion_needs_action($status)
    {
        return $status === LC_STATUS_PENDING;
    }
}

if (!function_exists('lc_conversion_get_by_id')) {
    function lc_conversion_get_by_id($cv_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cv_id = (int) $cv_id;
        $table = lc_table('conversions');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cv_id = '{$cv_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_conversion_get_by_code')) {
    function lc_conversion_get_by_code($cv_code)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cv_code = trim((string) $cv_code);
        if ($cv_code === '') {
            return null;
        }

        $table = lc_table('conversions');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cv_code = '" . lc_sql_escape($cv_code) . "' LIMIT 1 ");
    }
}

if (!function_exists('lc_conversion_reject_reasons')) {
    function lc_conversion_reject_reasons()
    {
        return array(
            '연락불가',
            '중복디비',
            '장난접수',
            '조건불일치',
            '지역불가',
            '이미상담',
            '기타',
        );
    }
}

if (!function_exists('lc_conversion_to_api_v1')) {
    /**
     * 외부 광고주 플랫폼용 디비 페이로드.
     */
    function lc_conversion_to_api_v1(array $row)
    {
        $status = (string) ($row['cv_status'] ?? '');
        $created = (string) ($row['cv_created_at'] ?? '');

        return array(
            'code'           => (string) ($row['cv_code'] ?? ''),
            'status'         => $status,
            'statusLabel'    => function_exists('lc_conversion_status_label') ? lc_conversion_status_label($status) : $status,
            'campaign'       => (string) ($row['cp_name'] ?? ''),
            'campaignId'     => (int) ($row['cp_id'] ?? 0),
            'name'           => (string) ($row['cv_name'] ?? ''),
            'phone'          => function_exists('lc_conversion_format_phone')
                ? lc_conversion_format_phone((string) ($row['cv_phone'] ?? ''))
                : (string) ($row['cv_phone'] ?? ''),
            'email'          => (string) ($row['cv_email'] ?? ''),
            'region'         => (string) ($row['cv_region'] ?? ''),
            'inquiry'        => (string) ($row['cv_inquiry'] ?? ''),
            'channel'        => (string) ($row['cv_channel'] ?? ''),
            'subId'          => (string) ($row['cv_sub_id'] ?? ''),
            'price'          => (int) ($row['cv_price'] ?? 0),
            'comment'        => (string) ($row['cv_comment'] ?? ''),
            'rejectReason'   => (string) ($row['cv_reject_reason'] ?? ''),
            'qualityScore'   => (int) ($row['cv_quality_score'] ?? 0),
            'qualityTags'    => function_exists('lc_conversion_decode_quality_tags')
                ? lc_conversion_decode_quality_tags($row['cv_quality_tags'] ?? '')
                : array(),
            'partnerVisible' => !isset($row['cv_partner_visible']) || (int) $row['cv_partner_visible'] === 1,
            'finalLocked'    => !empty($row['cv_final_locked']),
            'createdAt'      => $created,
            'updatedAt'      => (string) ($row['cv_updated_at'] ?? $created),
            'needsAction'    => function_exists('lc_conversion_needs_action')
                ? lc_conversion_needs_action($status)
                : ($status === LC_STATUS_PENDING),
        );
    }
}

if (!function_exists('lc_conversion_merchant_campaign_ids')) {
    function lc_conversion_merchant_campaign_ids($mt_id)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('campaigns');
        $ids = array();
        $result = lc_sql_query(" SELECT cp_id FROM `{$table}` WHERE mt_id = '{$mt_id}' ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $ids[] = (int) $row['cp_id'];
            }
        }

        return $ids;
    }
}

if (!function_exists('lc_conversion_list_for_merchant')) {
    function lc_conversion_list_for_merchant($mt_id, array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);
        if (!$campaign_ids) {
            return array();
        }

        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');

        $where = ' c.cp_id IN (' . implode(',', array_map('intval', $campaign_ids)) . ') ';

        if (!empty($filters['status'])) {
            $where .= " AND cv.cv_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (cv.cv_name LIKE '%{$q}%' OR cv.cv_phone LIKE '%{$q}%' OR cv.cv_code LIKE '%{$q}%') ";
        }

        if (!empty($filters['needs_action'])) {
            $where .= " AND cv.cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' ";
        }

        $sql = " SELECT cv.*, c.cp_name, c.cp_landing_url, c.cp_tracking_base_url, p.pt_code, lk.lk_code,
            (SELECT cl.cl_referer FROM `" . lc_table('clicks') . "` cl
                WHERE cl.lk_id = cv.lk_id AND cl.lk_id > 0
                ORDER BY cl.cl_id DESC LIMIT 1) AS cl_referer
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            LEFT JOIN `{$pt_table}` p ON p.pt_id = cv.pt_id
            LEFT JOIN `" . lc_table('links') . "` lk ON lk.lk_id = cv.lk_id
            WHERE {$where}
            ORDER BY cv.cv_id DESC
            LIMIT 200 ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_conversion_to_api_merchant')) {
    function lc_conversion_to_api_merchant(array $row, $mask_phone = false)
    {
        $status = (string) $row['cv_status'];
        $raw_phone = (string) ($row['cv_phone'] ?? '');
        $phone = $mask_phone ? lc_conversion_mask_phone($raw_phone) : lc_conversion_format_phone($raw_phone);
        $lk_code = trim((string) ($row['lk_code'] ?? ''));
        $landing_url = trim((string) ($row['cp_landing_url'] ?? ''));
        if ($landing_url !== '' && function_exists('lc_link_apply_tracking_host')) {
            $landing_url = lc_link_apply_tracking_host(
                $landing_url,
                (string) ($row['cp_tracking_base_url'] ?? '')
            );
        }
        if ($landing_url === '' && $lk_code !== '' && function_exists('lc_landing_public_url')) {
            $landing_url = lc_landing_public_url($lk_code, (string) ($row['cp_tracking_base_url'] ?? ''));
        }

        return array(
            'id'          => (string) $row['cv_code'],
            'cvId'        => (int) $row['cv_id'],
            'date'        => date('Y.m.d H:i', strtotime($row['cv_created_at'])),
            'campaign'    => (string) ($row['cp_name'] ?? ''),
            'name'        => (string) $row['cv_name'],
            'phone'       => $phone,
            'email'       => (string) ($row['cv_email'] ?? ''),
            'region'      => (string) ($row['cv_region'] ?? ''),
            'inquiry'     => (string) ($row['cv_inquiry'] ?? ''),
            'partner'     => (string) ($row['pt_code'] ?? '-'),
            'status'      => lc_conversion_status_label($status),
            'statusCode'  => $status,
            'price'       => (int) $row['cv_price'],
            'comment'     => (string) $row['cv_comment'],
            'needsAction' => lc_conversion_needs_action($status),
            'channel'     => (string) $row['cv_channel'],
            'subId'       => (string) $row['cv_sub_id'],
            'qualityScore'=> (int) ($row['cv_quality_score'] ?? 0),
            'qualityTags' => lc_conversion_decode_quality_tags($row['cv_quality_tags'] ?? ''),
            'partnerVisible' => !isset($row['cv_partner_visible']) || (int) $row['cv_partner_visible'] === 1,
            'landingUrl'  => $landing_url,
            'referer'     => (string) ($row['cl_referer'] ?? ''),
            'utmSource'   => '',
            'utmMedium'   => '',
            'utmCampaign' => (string) ($row['cv_sub_id'] ?? ''),
            'approvalCriteria' => '상담 가능 고객은 승인 처리해 주세요.',
            'cancelCriteria'   => '연락불가, 중복, 조건불일치, 장난/허위 접수는 취소/무효 처리할 수 있습니다.',
            'adminComment'     => '',
            'partnerPublic'    => !isset($row['cv_partner_visible']) || (int) $row['cv_partner_visible'] === 1,
            'history'     => lc_conversion_merchant_history($row),
        );
    }
}

if (!function_exists('lc_conversion_list_for_api')) {
    function lc_conversion_list_for_api($mt_id, array $filters = array())
    {
        if (lc_db_installed()) {
            $rows = lc_conversion_list_for_merchant($mt_id, $filters);

            return array_map(function ($row) {
                return lc_conversion_to_api_merchant($row, false);
            }, $rows);
        }

        if (!function_exists('lc_sample_merchant_dbs')) {
            return array();
        }

        $items = array();
        foreach (lc_sample_merchant_dbs() as $row) {
            $status_map = array(
                '신규접수' => LC_STATUS_PENDING,
                '확인중'   => LC_STATUS_PENDING,
                '승인완료' => LC_STATUS_APPROVED,
                '취소/무효' => LC_STATUS_REJECTED,
                '취소요청' => LC_STATUS_PENDING,
            );
            $status_code = isset($status_map[$row['status']]) ? $status_map[$row['status']] : LC_STATUS_PENDING;

            if (!empty($filters['status']) && $filters['status'] !== $status_code) {
                continue;
            }

            $items[] = array(
                'id'          => (string) $row['id'],
                'cvId'        => 0,
                'date'        => (string) $row['date'],
                'campaign'    => (string) $row['campaign'],
                'name'        => (string) $row['name'],
                'phone'       => lc_conversion_format_phone($row['phone']),
                'email'       => (string) ($row['email'] ?? ''),
                'region'      => (string) ($row['region'] ?? ''),
                'inquiry'     => (string) ($row['inquiry'] ?? ''),
                'partner'     => (string) $row['partner'],
                'status'      => (string) $row['status'],
                'statusCode'  => $status_code,
                'price'       => (int) $row['price'],
                'comment'     => (string) ($row['comment'] ?? ''),
                'needsAction' => !empty($row['needs_action']),
                'channel'     => (string) ($row['channel'] ?? ''),
                'subId'       => (string) ($row['sub_id'] ?? ''),
                'landingUrl'  => (string) ($row['landing'] ?? ''),
                'referer'     => (string) ($row['referer'] ?? ''),
                'utmSource'   => '',
                'utmMedium'   => '',
                'utmCampaign' => (string) ($row['sub_id'] ?? ''),
                'approvalCriteria' => '상담 가능 고객은 승인 처리해 주세요.',
                'cancelCriteria'   => '연락불가, 중복, 조건불일치, 장난/허위 접수는 취소/무효 처리할 수 있습니다.',
                'adminComment'     => '',
                'partnerPublic'    => true,
                'history'     => isset($row['history']) && is_array($row['history']) ? $row['history'] : array(),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_conversion_belongs_to_merchant')) {
    function lc_conversion_belongs_to_merchant(array $conversion, $mt_id)
    {
        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);

        return in_array((int) $conversion['cp_id'], $campaign_ids, true);
    }
}

if (!function_exists('lc_conversion_decode_quality_tags')) {
    function lc_conversion_decode_quality_tags($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

if (!function_exists('lc_conversion_encode_quality_tags')) {
    function lc_conversion_encode_quality_tags($tags)
    {
        if (!is_array($tags)) {
            return '';
        }

        $clean = array_values(array_filter(array_map('trim', array_map('strval', $tags))));

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('lc_conversion_with_meta')) {
    function lc_conversion_with_meta($cv_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cv_id = (int) $cv_id;
        $cv = lc_table('conversions');
        $cp = lc_table('campaigns');

        return lc_sql_fetch("
            SELECT cv.*, c.cp_name, c.mt_id
            FROM `{$cv}` cv
            LEFT JOIN `{$cp}` c ON c.cp_id = cv.cp_id
            WHERE cv.cv_id = {$cv_id}
            LIMIT 1
        ", false);
    }
}

if (!function_exists('lc_conversion_apply_quality_feedback')) {
    function lc_conversion_apply_quality_feedback($cv_id, array $opts = array())
    {
        if (!lc_db_installed()) {
            return;
        }

        $cv_id = (int) $cv_id;
        $score = isset($opts['qualityScore']) ? max(0, min(5, (int) $opts['qualityScore'])) : null;
        $tags = isset($opts['qualityTags']) ? lc_conversion_encode_quality_tags($opts['qualityTags']) : null;
        $visible = isset($opts['partnerVisible']) ? (!empty($opts['partnerVisible']) ? 1 : 0) : null;

        $sets = array();
        if ($score !== null) {
            $sets[] = "cv_quality_score = {$score}";
        }
        if ($tags !== null) {
            $sets[] = "cv_quality_tags = '" . lc_sql_escape($tags) . "'";
        }
        if ($visible !== null) {
            $sets[] = "cv_partner_visible = {$visible}";
        }
        if (!$sets) {
            return;
        }

        $table = lc_table('conversions');
        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . ", cv_updated_at = NOW() WHERE cv_id = {$cv_id} ", false);
    }
}

if (!function_exists('lc_conversion_update_status')) {
    /**
     * @return array{ok:bool,message:string,conversion?:array}
     */
    function lc_conversion_update_status($cv_id, $mt_id, $new_status, $comment = '', array $opts = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $conversion = lc_conversion_get_by_id($cv_id);
        if (!$conversion) {
            return array('ok' => false, 'message' => '디비를 찾을 수 없습니다.');
        }

        if (!lc_conversion_belongs_to_merchant($conversion, $mt_id)) {
            return array('ok' => false, 'message' => '접근 권한이 없습니다.');
        }

        if (!empty($conversion['cv_final_locked'])) {
            return array('ok' => false, 'message' => '관리자 최종확정으로 잠긴 디비입니다.');
        }

        if ($conversion['cv_status'] !== LC_STATUS_PENDING) {
            return array('ok' => false, 'message' => '이미 처리된 디비입니다.');
        }

        $allowed = array(LC_STATUS_APPROVED, LC_STATUS_REJECTED);
        if (!in_array($new_status, $allowed, true)) {
            return array('ok' => false, 'message' => '유효하지 않은 상태입니다.');
        }

        if ($new_status === LC_STATUS_APPROVED) {
            $deduct = lc_wallet_deduct_for_conversion(
                $mt_id,
                $cv_id,
                (int) $conversion['cv_price'],
                $conversion['cv_code'] . ' 승인 차감'
            );
            if (!$deduct['ok']) {
                return $deduct;
            }

            if (function_exists('lc_partner_credit_for_conversion')) {
                lc_partner_credit_for_conversion($conversion);
            }
        }

        $table = lc_table('conversions');
        $status_esc = lc_sql_escape($new_status);
        $comment_esc = lc_sql_escape($comment);

        lc_sql_query(" UPDATE `{$table}` SET
            cv_status = '{$status_esc}',
            cv_comment = '{$comment_esc}',
            cv_review_status = '" . ($new_status === LC_STATUS_REJECTED ? lc_sql_escape('pending') : lc_sql_escape('')) . "',
            cv_reject_reason = '" . ($new_status === LC_STATUS_REJECTED ? $comment_esc : '') . "',
            cv_updated_at = NOW()
            WHERE cv_id = '" . (int) $cv_id . "' ", false);

        if ($opts) {
            lc_conversion_apply_quality_feedback($cv_id, $opts);
        }

        $updated = lc_conversion_with_meta($cv_id);
        if (!$updated) {
            $updated = lc_conversion_get_by_id($cv_id);
        }

        if (function_exists('lc_notification_emit_conversion') && is_array($updated)) {
            lc_notification_emit_conversion($updated, $new_status === LC_STATUS_APPROVED ? 'approved' : 'rejected');
        }

        if ($new_status === LC_STATUS_APPROVED && function_exists('lc_event_on_conversion_approved') && is_array($updated)) {
            lc_event_on_conversion_approved($updated);
        }

        if (is_array($updated) && function_exists('lc_abuse_check_cancel_spike')) {
            lc_abuse_check_cancel_spike((int) ($updated['pt_id'] ?? 0), 0);
        }

        if ($new_status === LC_STATUS_REJECTED && is_array($updated) && function_exists('lc_abuse_refresh_partner_score')) {
            lc_abuse_refresh_partner_score((int) ($updated['pt_id'] ?? 0));
            lc_abuse_check_cancel_spike((int) ($updated['pt_id'] ?? 0), 0);
        }

        if ($new_status === LC_STATUS_APPROVED && !empty($opts['qualityScore']) && (int) $opts['qualityScore'] <= 3) {
            $pt_id = (int) ($updated['pt_id'] ?? 0);
            if ($pt_id > 0 && function_exists('lc_notification_create')) {
                lc_notification_create(array(
                    'center'  => 'partner',
                    'userId'  => $pt_id,
                    'type'    => 'conversion',
                    'title'   => '리드 품질 피드백',
                    'body'    => (string) ($updated['cp_name'] ?? '캠페인') . ' · 품질 ' . (int) $opts['qualityScore'] . '점',
                    'link'    => '/partner/db-status',
                    'refType' => 'conversion',
                    'refId'   => (int) $cv_id,
                ));
            }
        }

        return array(
            'ok'         => true,
            'message'    => $new_status === LC_STATUS_APPROVED ? '승인 처리되었습니다.' : '취소/무효 처리되었습니다.',
            'conversion' => $updated ?: lc_conversion_get_by_id($cv_id),
        );
    }
}

if (!function_exists('lc_conversion_admin_final_status')) {
    /**
     * 관리자 최종확정 — 광고주 승인/취소 위에서 최종 승인/취소불가(락) 처리.
     *
     * @param string $action approve|reject|lock|unlock
     * @return array{ok:bool,message:string,conversion?:array}
     */
    function lc_conversion_admin_final_status($cv_id, $action, $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cv_id = (int) $cv_id;
        $conversion = lc_conversion_get_by_id($cv_id);
        if (!$conversion) {
            return array('ok' => false, 'message' => '디비를 찾을 수 없습니다.');
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT mt_id FROM `{$cp_table}` WHERE cp_id = '" . (int) $conversion['cp_id'] . "' LIMIT 1 ");
        $mt_id = $campaign ? (int) $campaign['mt_id'] : 0;

        $table = lc_table('conversions');

        if ($action === 'unlock') {
            lc_sql_query(" UPDATE `{$table}` SET cv_final_locked = '0', cv_updated_at = NOW() WHERE cv_id = '{$cv_id}' ", false);

            return array('ok' => true, 'message' => '잠금을 해제했습니다.', 'conversion' => lc_conversion_get_by_id($cv_id));
        }

        if ($action === 'lock') {
            $final = $conversion['cv_status'] === LC_STATUS_APPROVED ? LC_FINAL_APPROVED : ($conversion['cv_status'] === LC_STATUS_REJECTED ? LC_FINAL_REJECTED : '');
            lc_sql_query(" UPDATE `{$table}` SET cv_final_status = '" . lc_sql_escape($final) . "', cv_final_locked = '1', cv_updated_at = NOW() WHERE cv_id = '{$cv_id}' ", false);

            return array('ok' => true, 'message' => '현재 상태로 최종확정(잠금)했습니다.', 'conversion' => lc_conversion_get_by_id($cv_id));
        }

        if ($action !== 'approve' && $action !== 'reject') {
            return array('ok' => false, 'message' => '유효하지 않은 최종확정 action입니다.');
        }

        $current = (string) $conversion['cv_status'];
        $target_status = $action === 'approve' ? LC_STATUS_APPROVED : LC_STATUS_REJECTED;
        $final_status = $action === 'approve' ? LC_FINAL_APPROVED : LC_FINAL_REJECTED;
        $memo_text = $memo !== '' ? $memo : '관리자 최종확정';
        $price = (int) $conversion['cv_price'];

        // 이미 목표 상태와 동일하면 정산 이동 없이 최종확정(잠금)만.
        if ($current === $target_status) {
            lc_sql_query(" UPDATE `{$table}` SET
                cv_final_status = '" . lc_sql_escape($final_status) . "',
                cv_final_locked = '1',
                cv_updated_at = NOW()
                WHERE cv_id = '{$cv_id}' ", false);

            return array(
                'ok'         => true,
                'message'    => $action === 'approve' ? '최종 승인(잠금) 처리했습니다.' : '최종 취소불가(잠금) 처리했습니다.',
                'conversion' => lc_conversion_get_by_id($cv_id),
            );
        }

        // 검수중(pending)에서의 전환은 정식 파이프라인(지갑 차감·파트너 적립·알림)을 태운다.
        if ($current === LC_STATUS_PENDING && $mt_id > 0) {
            $result = lc_conversion_update_status($cv_id, $mt_id, $target_status, $memo_text);
            if (!$result['ok']) {
                return $result;
            }
        } elseif ($action === 'approve') {
            // 취소/무효 → 승인: 광고비 차감 + 파트너 적립
            if ($mt_id > 0) {
                $deduct = lc_wallet_deduct_for_conversion($mt_id, $cv_id, $price, $conversion['cv_code'] . ' 관리자 최종승인 차감');
                if (!$deduct['ok']) {
                    return $deduct;
                }
            }
            if (function_exists('lc_partner_credit_for_conversion')) {
                lc_partner_credit_for_conversion($conversion);
            }
            lc_sql_query(" UPDATE `{$table}` SET cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "', cv_comment = '" . lc_sql_escape($memo_text) . "', cv_review_status = '', cv_reject_reason = '', cv_updated_at = NOW() WHERE cv_id = '{$cv_id}' ", false);
        } else {
            // 승인 → 취소/무효: 광고비 환급 + 파트너 적립 회수
            if ($current === LC_STATUS_APPROVED) {
                if ($mt_id > 0 && function_exists('lc_wallet_record')) {
                    lc_wallet_record($mt_id, 'refund', abs($price), $conversion['cv_code'] . ' 관리자 최종취소 환급', 'conversion', $cv_id);
                }
                if (function_exists('lc_partner_debit_for_conversion')) {
                    lc_partner_debit_for_conversion($conversion);
                }
            }
            lc_sql_query(" UPDATE `{$table}` SET cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "', cv_comment = '" . lc_sql_escape($memo_text) . "', cv_reject_reason = '" . lc_sql_escape($memo_text) . "', cv_updated_at = NOW() WHERE cv_id = '{$cv_id}' ", false);
        }

        lc_sql_query(" UPDATE `{$table}` SET
            cv_final_status = '" . lc_sql_escape($final_status) . "',
            cv_final_locked = '1',
            cv_updated_at = NOW()
            WHERE cv_id = '{$cv_id}' ", false);

        return array(
            'ok'         => true,
            'message'    => $action === 'approve' ? '최종 승인(잠금) 처리했습니다.' : '최종 취소불가(잠금) 처리했습니다.',
            'conversion' => lc_conversion_get_by_id($cv_id),
        );
    }
}

if (!function_exists('lc_conversion_resolve_partner_price')) {
    function lc_conversion_resolve_partner_price(array $conversion)
    {
        $partner_price = (int) ($conversion['cv_partner_price'] ?? 0);
        if ($partner_price > 0) {
            return $partner_price;
        }

        return (int) ($conversion['cv_price'] ?? 0);
    }
}

if (!function_exists('lc_partner_credit_for_conversion')) {
    function lc_partner_credit_for_conversion(array $conversion)
    {
        if (!lc_db_installed() || empty($conversion['pt_id'])) {
            return;
        }

        $pt_id = (int) $conversion['pt_id'];
        $amount = lc_conversion_resolve_partner_price($conversion);
        if (function_exists('lc_get_partner_by_id')) {
            $partner = lc_get_partner_by_id($pt_id);
            if (is_array($partner) && function_exists('lc_partner_tier_bonus_rate')) {
                $tier = lc_partner_tier_label($partner);
                $bonus = lc_partner_tier_bonus_rate($tier);
                if ($bonus > 0) {
                    $amount += (int) round($amount * $bonus);
                }
            }
        }
        $table = lc_table('partners');

        lc_sql_query(" UPDATE `{$table}` SET pt_balance = pt_balance + '{$amount}', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' ", false);
    }
}

if (!function_exists('lc_partner_debit_for_conversion')) {
    /**
     * 파트너 적립 역처리 — 승인이 최종 취소로 뒤집힐 때 적립금 회수.
     * (적립 당시와 동일한 등급 보너스 기준으로 계산; 등급 변동 시 오차 가능)
     */
    function lc_partner_debit_for_conversion(array $conversion)
    {
        if (!lc_db_installed() || empty($conversion['pt_id'])) {
            return;
        }

        $pt_id = (int) $conversion['pt_id'];
        $amount = lc_conversion_resolve_partner_price($conversion);
        if (function_exists('lc_get_partner_by_id')) {
            $partner = lc_get_partner_by_id($pt_id);
            if (is_array($partner) && function_exists('lc_partner_tier_bonus_rate')) {
                $tier = lc_partner_tier_label($partner);
                $bonus = lc_partner_tier_bonus_rate($tier);
                if ($bonus > 0) {
                    $amount += (int) round($amount * $bonus);
                }
            }
        }
        $table = lc_table('partners');

        // 잔액이 음수로 내려가지 않도록 보호
        lc_sql_query(" UPDATE `{$table}` SET pt_balance = GREATEST(0, pt_balance - '{$amount}'), pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' ", false);
    }
}

if (!function_exists('lc_conversion_merchant_summary')) {
    function lc_conversion_merchant_summary($mt_id)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);
        if (!$campaign_ids) {
            return array(
                'pending'      => 0,
                'approved'     => 0,
                'rejected'     => 0,
                'needsAction'  => 0,
                'todayReceived'=> 0,
                'todaySpend'   => 0,
            );
        }

        $cv_table = lc_table('conversions');
        $in = implode(',', array_map('intval', $campaign_ids));
        $today = date('Y-m-d');

        $row = lc_sql_fetch(" SELECT
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN DATE(cv_created_at) = '{$today}' THEN 1 ELSE 0 END) AS today_received,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' AND DATE(cv_updated_at) = '{$today}' THEN cv_price ELSE 0 END) AS today_spend
            FROM `{$cv_table}` WHERE cp_id IN ({$in}) ");

        return array(
            'pending'       => (int) ($row['pending_cnt'] ?? 0),
            'approved'      => (int) ($row['approved_cnt'] ?? 0),
            'rejected'      => (int) ($row['rejected_cnt'] ?? 0),
            'needsAction'   => (int) ($row['pending_cnt'] ?? 0),
            'todayReceived' => (int) ($row['today_received'] ?? 0),
            'todaySpend'    => (int) ($row['today_spend'] ?? 0),
        );
    }
}

if (!function_exists('lc_conversion_merchant_chart_7d')) {
    function lc_conversion_merchant_chart_7d($mt_id)
    {
        if (!lc_db_installed()) {
            return function_exists('lc_sample_merchant_chart_7d') ? lc_sample_merchant_chart_7d() : array();
        }

        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);
        if (!$campaign_ids) {
            return array();
        }

        $cv_table = lc_table('conversions');
        $in = implode(',', array_map('intval', $campaign_ids));
        $items = array();

        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $label = date('m.d', strtotime($day));
            $row = lc_sql_fetch(" SELECT
                COUNT(*) AS db_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approval_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS cancel_cnt
                FROM `{$cv_table}`
                WHERE cp_id IN ({$in}) AND DATE(cv_created_at) = '{$day}' ");

            $items[] = array(
                'date'     => $label,
                'db'       => (int) ($row['db_cnt'] ?? 0),
                'approval' => (int) ($row['approval_cnt'] ?? 0),
                'cancel'   => (int) ($row['cancel_cnt'] ?? 0),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_conversion_generate_code')) {
    function lc_conversion_generate_code()
    {
        return 'DB' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}

if (!function_exists('lc_conversion_seed_for_merchant')) {
    function lc_conversion_seed_for_merchant($mt_id)
    {
        if (!lc_db_installed() || !function_exists('lc_sample_merchant_dbs')) {
            return 0;
        }

        $mt_id = (int) $mt_id;
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');

        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE c.mt_id = '{$mt_id}' ");
        if ($count_row && (int) $count_row['cnt'] > 0) {
            return 0;
        }

        $status_map = array(
            '신규접수' => LC_STATUS_PENDING,
            '확인중'   => LC_STATUS_PENDING,
            '승인완료' => LC_STATUS_APPROVED,
            '취소/무효' => LC_STATUS_REJECTED,
            '취소요청' => LC_STATUS_PENDING,
        );

        $inserted = 0;
        foreach (lc_sample_merchant_dbs() as $sample) {
            $campaign = lc_sql_fetch(" SELECT cp_id, cp_price FROM `{$cp_table}` WHERE cp_name = '" . lc_sql_escape($sample['campaign']) . "' AND mt_id = '{$mt_id}' LIMIT 1 ");
            if (!$campaign) {
                continue;
            }

            $partner = lc_sql_fetch(" SELECT pt_id FROM `{$pt_table}` WHERE pt_code = '" . lc_sql_escape($sample['partner']) . "' LIMIT 1 ");
            $pt_id = $partner ? (int) $partner['pt_id'] : 0;
            $status = isset($status_map[$sample['status']]) ? $status_map[$sample['status']] : LC_STATUS_PENDING;
            $price = $status === LC_STATUS_APPROVED ? (int) $campaign['cp_price'] : (int) $sample['price'];

            lc_sql_query(" INSERT INTO `{$cv_table}` SET
                cv_code = '" . lc_sql_escape($sample['id']) . "',
                pt_id = '{$pt_id}',
                cp_id = '" . (int) $campaign['cp_id'] . "',
                cv_name = '" . lc_sql_escape($sample['name']) . "',
                cv_phone = '" . lc_sql_escape($sample['phone']) . "',
                cv_email = '" . lc_sql_escape($sample['email'] ?? '') . "',
                cv_region = '" . lc_sql_escape($sample['region'] ?? '') . "',
                cv_inquiry = '" . lc_sql_escape($sample['inquiry'] ?? '') . "',
                cv_status = '" . lc_sql_escape($status) . "',
                cv_price = '{$price}',
                cv_channel = '" . lc_sql_escape($sample['channel'] ?? '') . "',
                cv_sub_id = '" . lc_sql_escape($sample['sub_id'] ?? '') . "',
                cv_comment = '" . lc_sql_escape($sample['comment'] ?? '') . "',
                cv_created_at = NOW(),
                cv_updated_at = NOW() ", false);
            $inserted++;
        }

        return $inserted;
    }
}

if (!function_exists('lc_merchant_dashboard_for_api')) {
    function lc_merchant_dashboard_for_api($mt_id)
    {
        $merchant = lc_get_merchant_by_id($mt_id);
        $summary = lc_conversion_merchant_summary($mt_id);
        $chart = lc_conversion_merchant_chart_7d($mt_id);
        $recent = array_slice(lc_conversion_list_for_api($mt_id), 0, 5);

        $wallet = function_exists('lc_wallet_merchant_summary')
            ? lc_wallet_merchant_summary($mt_id)
            : array(
                'monthlyCharge'    => 0,
                'monthlySpend'     => 0,
                'availableBalance' => is_array($merchant) ? (int) $merchant['mt_balance'] : 0,
            );

        return array(
            'balance'       => is_array($merchant) ? (int) $merchant['mt_balance'] : 0,
            'balanceFormatted' => is_array($merchant) ? number_format((int) $merchant['mt_balance']) : '0',
            'summary'       => $summary,
            'wallet'        => array(
                'monthlyCharge'    => (int) ($wallet['monthlyCharge'] ?? 0),
                'monthlySpend'     => (int) ($wallet['monthlySpend'] ?? 0),
                'availableBalance' => (int) ($wallet['availableBalance'] ?? 0),
            ),
            'chart7d'       => $chart,
            'recent'        => $recent,
            'pendingAction' => (int) ($summary['needsAction'] ?? 0),
        );
    }
}

if (!function_exists('lc_conversion_partner_status_label')) {
    function lc_conversion_partner_status_label($status)
    {
        $labels = array(
            LC_STATUS_PENDING  => '검수중',
            LC_STATUS_APPROVED => '승인완료',
            LC_STATUS_REJECTED => '취소/무효',
            LC_STATUS_SETTLED  => '정산완료',
        );

        return isset($labels[$status]) ? $labels[$status] : lc_conversion_status_label($status);
    }
}

if (!function_exists('lc_conversion_list_for_partner')) {
    function lc_conversion_list_for_partner($pt_id, array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');

        $where = " cv.pt_id = '{$pt_id}' ";

        if (!empty($filters['status'])) {
            $where .= " AND cv.cv_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (cv.cv_name LIKE '%{$q}%' OR cv.cv_phone LIKE '%{$q}%' OR cv.cv_code LIKE '%{$q}%') ";
        }

        if (!empty($filters['rejected_only'])) {
            $where .= " AND cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' ";
        }

        $sql = " SELECT cv.*, c.cp_name
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE {$where}
            ORDER BY cv.cv_id DESC
            LIMIT 200 ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_conversion_to_api_partner')) {
    function lc_conversion_to_api_partner(array $row)
    {
        $status = (string) $row['cv_status'];
        $price = (int) $row['cv_price'];
        $approved = $status === LC_STATUS_APPROVED;
        $partner_visible = !isset($row['cv_partner_visible']) || (int) $row['cv_partner_visible'] === 1;
        $quality_score = (int) ($row['cv_quality_score'] ?? 0);

        return array(
            'id'          => (string) $row['cv_code'],
            'cvId'        => (int) $row['cv_id'],
            'date'        => date('Y.m.d H:i', strtotime($row['cv_created_at'])),
            'campaign'    => (string) ($row['cp_name'] ?? ''),
            'name'        => lc_conversion_mask_name($row['cv_name']),
            'phone'       => lc_conversion_mask_phone($row['cv_phone']),
            'channel'     => (string) $row['cv_channel'],
            'subId'       => (string) $row['cv_sub_id'],
            'status'      => lc_conversion_partner_status_label($status),
            'statusCode'  => $status,
            'price'       => $price,
            'estRevenue'  => $status === LC_STATUS_REJECTED ? 0 : $price,
            'confRevenue' => $approved ? $price : 0,
            'comment'     => $partner_visible ? (string) $row['cv_comment'] : '',
            'reason'      => $partner_visible ? (string) ($row['cv_reject_reason'] ?? '') : '',
            'appeal'      => (string) ($row['cv_partner_appeal'] ?? ''),
            'hasAppeal'   => trim((string) ($row['cv_partner_appeal'] ?? '')) !== '',
            'qualityScore'=> $approved && $quality_score > 0 ? $quality_score : 0,
            'qualityTags' => $approved ? lc_conversion_decode_quality_tags($row['cv_quality_tags'] ?? '') : array(),
        );
    }
}

if (!function_exists('lc_conversion_mask_name')) {
    function lc_conversion_mask_name($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        if (function_exists('mb_strlen') && mb_strlen($name, 'UTF-8') > 1) {
            return mb_substr($name, 0, 1, 'UTF-8') . '*';
        }

        return $name . '*';
    }
}

if (!function_exists('lc_conversion_list_for_partner_api')) {
    function lc_conversion_list_for_partner_api($pt_id, array $filters = array())
    {
        $rows = lc_conversion_list_for_partner($pt_id, $filters);

        return array_map('lc_conversion_to_api_partner', $rows);
    }
}

if (!function_exists('lc_conversion_partner_summary')) {
    function lc_conversion_partner_summary($pt_id)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $cv_table = lc_table('conversions');
        $today = date('Y-m-d');

        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN DATE(cv_created_at) = '{$today}' THEN 1 ELSE 0 END) AS today_received,
            SUM(CASE WHEN cv_status != '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN cv_price ELSE 0 END) AS est_revenue,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS conf_revenue,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' AND DATE(cv_updated_at) = '{$today}' THEN cv_price ELSE 0 END) AS today_est_revenue
            FROM `{$cv_table}` WHERE pt_id = '{$pt_id}' ");

        return array(
            'total'         => (int) ($row['total_cnt'] ?? 0),
            'pending'       => (int) ($row['pending_cnt'] ?? 0),
            'approved'      => (int) ($row['approved_cnt'] ?? 0),
            'rejected'      => (int) ($row['rejected_cnt'] ?? 0),
            'todayReceived' => (int) ($row['today_received'] ?? 0),
            'estRevenue'    => (int) ($row['est_revenue'] ?? 0),
            'confRevenue'   => (int) ($row['conf_revenue'] ?? 0),
            'todayEstRevenue' => (int) ($row['today_est_revenue'] ?? 0),
        );
    }
}

if (!function_exists('lc_conversion_partner_chart_7d')) {
    function lc_conversion_partner_chart_7d($pt_id)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $cv_table = lc_table('conversions');
        $cl_table = lc_table('clicks');
        $items = array();

        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $label = date('m.d', strtotime($day));

            $click_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cl_table}` WHERE pt_id = '{$pt_id}' AND DATE(cl_created_at) = '{$day}' ");
            $cv_row = lc_sql_fetch(" SELECT
                COUNT(*) AS db_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approval_cnt
                FROM `{$cv_table}`
                WHERE pt_id = '{$pt_id}' AND DATE(cv_created_at) = '{$day}' ");

            $items[] = array(
                'date'     => $label,
                'click'    => (int) ($click_row['cnt'] ?? 0),
                'db'       => (int) ($cv_row['db_cnt'] ?? 0),
                'approval' => (int) ($cv_row['approval_cnt'] ?? 0),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_conversion_create')) {
    /**
     * @return array{ok:bool,message:string,conversion:array|null}
     */
    function lc_conversion_create(array $payload)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'conversion' => null);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        if ($name === '' || $phone === '') {
            return array('ok' => false, 'message' => '이름과 연락처는 필수입니다.', 'conversion' => null);
        }

        $pt_id = (int) ($payload['pt_id'] ?? 0);
        $cp_id = (int) ($payload['cp_id'] ?? 0);
        $lk_id = (int) ($payload['lk_id'] ?? 0);

        if (function_exists('lc_abuse_check_recent_duplicate')) {
            $recent = lc_abuse_check_recent_duplicate($payload, 24);
            if (!empty($recent['duplicate'])) {
                return array(
                    'ok'         => false,
                    'message'    => '이미 접수된 연락처입니다. 담당자가 순차적으로 연락드리겠습니다.',
                    'code'       => 'DUPLICATE_RECENT',
                    'conversion' => null,
                );
            }
        }

        $duplicate = false;
        $abuse_score = 0;
        if (function_exists('lc_abuse_check_duplicate')) {
            $dup = lc_abuse_check_duplicate($payload);
            $duplicate = !empty($dup['duplicate']);
        }
        if (function_exists('lc_abuse_compute_conversion_score')) {
            $abuse_score = lc_abuse_compute_conversion_score($payload, $duplicate);
        }

        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '캠페인 정보가 없습니다.', 'conversion' => null);
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' LIMIT 1 ");
        if (!$campaign) {
            return array('ok' => false, 'message' => '진행 중인 캠페인이 아닙니다.', 'conversion' => null);
        }

        $cv_code = lc_conversion_generate_code();
        $table = lc_table('conversions');
        $merchant_price = lc_campaign_resolve_merchant_price($campaign);
        $partner_price = lc_campaign_resolve_partner_price($campaign);

        lc_sql_query(" INSERT INTO `{$table}` SET
            cv_code = '" . lc_sql_escape($cv_code) . "',
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            lk_id = '{$lk_id}',
            cv_name = '" . lc_sql_escape($name) . "',
            cv_phone = '" . lc_sql_escape($phone) . "',
            cv_email = '" . lc_sql_escape($payload['email'] ?? '') . "',
            cv_region = '" . lc_sql_escape($payload['region'] ?? '') . "',
            cv_inquiry = '" . lc_sql_escape($payload['inquiry'] ?? '') . "',
            cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "',
            cv_price = '" . (int) $merchant_price . "',
            cv_partner_price = '" . (int) $partner_price . "',
            cv_channel = '" . lc_sql_escape($payload['channel'] ?? '') . "',
            cv_sub_id = '" . lc_sql_escape($payload['sub_id'] ?? '') . "',
            cv_comment = '',
            cv_created_at = NOW(),
            cv_updated_at = NOW() ", false);

        $cv_id = (int) lc_sql_insert_id();
        if ($cv_id <= 0) {
            return array('ok' => false, 'message' => '디비 접수에 실패했습니다.', 'conversion' => null);
        }

        $conversion = lc_conversion_get_by_id($cv_id);

        if (function_exists('lc_notification_emit_conversion')) {
            $meta = lc_conversion_with_meta($cv_id);
            if (is_array($meta)) {
                lc_notification_emit_conversion($meta, 'received');
            }
        }

        if (function_exists('lc_abuse_on_conversion_created')) {
            lc_abuse_on_conversion_created($cv_id, $payload, $duplicate, $abuse_score);
        }

        return array(
            'ok'         => true,
            'message'    => '상담 신청이 접수되었습니다.',
            'conversion' => $conversion,
        );
    }
}

if (!function_exists('lc_conversion_create_from_link')) {
    /**
     * @return array{ok:bool,message:string,conversion:array|null}
     */
    function lc_conversion_create_from_link(array $link, array $payload)
    {
        $payload['pt_id'] = (int) $link['pt_id'];
        $payload['cp_id'] = (int) $link['cp_id'];
        $payload['lk_id'] = (int) $link['lk_id'];
        if (empty($payload['channel'])) {
            $payload['channel'] = (string) ($link['lk_channel'] ?? '');
        }
        if (empty($payload['sub_id'])) {
            $payload['sub_id'] = (string) ($link['lk_sub_id'] ?? '');
        }

        return lc_conversion_create($payload);
    }
}

if (!function_exists('lc_partner_dashboard_for_api')) {
    function lc_partner_dashboard_for_api($pt_id)
    {
        $partner = lc_get_partner_by_id($pt_id);
        $summary = lc_conversion_partner_summary($pt_id);
        $clicks = function_exists('lc_link_partner_click_summary') ? lc_link_partner_click_summary($pt_id) : array('today' => 0);
        $chart = lc_conversion_partner_chart_7d($pt_id);
        $channels = function_exists('lc_link_partner_channel_stats') ? lc_link_partner_channel_stats($pt_id) : array();
        $recent = array_slice(lc_conversion_list_for_partner_api($pt_id), 0, 5);

        return array(
            'balance'          => is_array($partner) ? (int) $partner['pt_balance'] : 0,
            'balanceFormatted' => is_array($partner) ? number_format((int) $partner['pt_balance']) : '0',
            'summary'          => array_merge($summary, array(
                'todayClicks' => (int) ($clicks['today'] ?? 0),
            )),
            'chart7d'          => $chart,
            'channels'         => $channels,
            'recent'           => $recent,
        );
    }
}

if (!function_exists('lc_conversion_review_status_label')) {
    function lc_conversion_review_status_label($status, $review_status, $appeal = '')
    {
        if ($appeal !== '') {
            return '이의신청중';
        }

        if ($status === LC_STATUS_REJECTED) {
            if ($review_status === 'confirmed') {
                return '취소승인';
            }
            if ($review_status === 'restored') {
                return '취소반려';
            }

            return '검수대기';
        }

        return lc_conversion_status_label($status);
    }
}

if (!function_exists('lc_conversion_list_for_inspection')) {
    function lc_conversion_list_for_inspection(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');
        $mt_table = lc_table('merchants');
        $where = " cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' ";

        if (!empty($filters['status'])) {
            $status = (string) $filters['status'];
            if ($status === 'pending') {
                $where .= " AND (cv.cv_review_status = 'pending' OR cv.cv_review_status = '') ";
            } elseif ($status === 'appeal') {
                $where .= " AND cv.cv_partner_appeal != '' ";
            } elseif ($status === 'confirmed') {
                $where .= " AND cv.cv_review_status = 'confirmed' ";
            } elseif ($status === 'restored') {
                $where .= " AND cv.cv_review_status = 'restored' ";
            }
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (cv.cv_name LIKE '%{$q}%' OR cv.cv_phone LIKE '%{$q}%' OR cv.cv_code LIKE '%{$q}%' OR p.pt_name LIKE '%{$q}%') ";
        }

        $rows = array();
        $sql = " SELECT cv.*, c.cp_name, p.pt_code, p.pt_name, m.mt_company
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            INNER JOIN `{$pt_table}` p ON p.pt_id = cv.pt_id
            LEFT JOIN `{$mt_table}` m ON m.mt_id = c.mt_id
            WHERE {$where}
            ORDER BY cv.cv_id DESC
            LIMIT 100 ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_conversion_inspection_summary')) {
    function lc_conversion_inspection_summary()
    {
        if (!lc_db_installed()) {
            return array(
                'pending'     => 0,
                'todayCancel' => 0,
                'confirmed'   => 0,
                'restored'    => 0,
                'appeals'     => 0,
                'cancelRate'  => 0,
            );
        }

        $cv_table = lc_table('conversions');
        $today = date('Y-m-d');
        $row = lc_sql_fetch(" SELECT
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' AND (cv_review_status = 'pending' OR cv_review_status = '') THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' AND DATE(cv_updated_at) = '{$today}' THEN 1 ELSE 0 END) AS today_cnt,
            SUM(CASE WHEN cv_review_status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_cnt,
            SUM(CASE WHEN cv_review_status = 'restored' THEN 1 ELSE 0 END) AS restored_cnt,
            SUM(CASE WHEN cv_partner_appeal != '' THEN 1 ELSE 0 END) AS appeal_cnt,
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt
            FROM `{$cv_table}` ");

        $total = (int) ($row['total_cnt'] ?? 0);
        $rejected = (int) ($row['rejected_cnt'] ?? 0);

        return array(
            'pending'     => (int) ($row['pending_cnt'] ?? 0),
            'todayCancel' => (int) ($row['today_cnt'] ?? 0),
            'confirmed'   => (int) ($row['confirmed_cnt'] ?? 0),
            'restored'    => (int) ($row['restored_cnt'] ?? 0),
            'appeals'     => (int) ($row['appeal_cnt'] ?? 0),
            'cancelRate'  => $total > 0 ? round(($rejected / $total) * 100, 1) : 0,
        );
    }
}

if (!function_exists('lc_conversion_to_inspection_api')) {
    function lc_conversion_to_inspection_api(array $row)
    {
        $review_status = (string) ($row['cv_review_status'] ?? '');
        $appeal = (string) ($row['cv_partner_appeal'] ?? '');

        return array(
            'id'              => (string) $row['cv_code'],
            'cvId'            => (int) $row['cv_id'],
            'date'            => date('Y.m.d H:i', strtotime($row['cv_updated_at'])),
            'campaign'        => (string) ($row['cp_name'] ?? ''),
            'advertiser'      => (string) ($row['mt_company'] ?? ''),
            'partner'         => (string) ($row['pt_name'] ?? '') . ' (' . (string) ($row['pt_code'] ?? '') . ')',
            'customer'        => lc_conversion_mask_name($row['cv_name']),
            'phone'           => lc_conversion_mask_phone($row['cv_phone']),
            'reason'          => (string) ($row['cv_reject_reason'] !== '' ? $row['cv_reject_reason'] : $row['cv_comment']),
            'comment'         => (string) $row['cv_comment'],
            'objection'       => $appeal !== '',
            'objectionComment'=> $appeal,
            'status'          => lc_conversion_review_status_label($row['cv_status'], $review_status, $appeal),
            'statusCode'      => $review_status !== '' ? $review_status : 'pending',
            'price'           => (int) $row['cv_price'],
        );
    }
}

if (!function_exists('lc_conversion_admin_review')) {
    /**
     * @return array{ok:bool,message:string,conversion?:array|null}
     */
    function lc_conversion_admin_review($cv_id, $action, $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cv_id = (int) $cv_id;
        $conversion = lc_conversion_get_by_id($cv_id);
        if (!$conversion || $conversion['cv_status'] !== LC_STATUS_REJECTED) {
            return array('ok' => false, 'message' => '검수 대상 디비를 찾을 수 없습니다.');
        }

        $table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT c.*, m.mt_id FROM `{$cp_table}` c LEFT JOIN `" . lc_table('merchants') . "` m ON m.mt_id = c.mt_id WHERE c.cp_id = '" . (int) $conversion['cp_id'] . "' LIMIT 1 ");
        $mt_id = is_array($campaign) ? (int) ($campaign['mt_id'] ?? 0) : 0;

        if ($action === 'confirm') {
            lc_sql_query(" UPDATE `{$table}` SET cv_review_status = 'confirmed', cv_comment = CONCAT(cv_comment, ' / ', '" . lc_sql_escape($memo !== '' ? $memo : '관리자 취소 승인') . "'), cv_updated_at = NOW() WHERE cv_id = '{$cv_id}' ", false);
        } elseif ($action === 'restore') {
            if ($mt_id > 0) {
                $deduct = lc_wallet_deduct_for_conversion($mt_id, $cv_id, (int) $conversion['cv_price'], $conversion['cv_code'] . ' 취소 반려 복원');
                if (!$deduct['ok']) {
                    return array('ok' => false, 'message' => $deduct['message']);
                }
            }
            if (function_exists('lc_partner_credit_for_conversion')) {
                lc_partner_credit_for_conversion($conversion);
            }
            lc_sql_query(" UPDATE `{$table}` SET
                cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "',
                cv_review_status = 'restored',
                cv_comment = CONCAT(cv_comment, ' / ', '" . lc_sql_escape($memo !== '' ? $memo : '관리자 취소 반려') . "'),
                cv_updated_at = NOW()
                WHERE cv_id = '{$cv_id}' ", false);
        } else {
            return array('ok' => false, 'message' => '유효하지 않은 action입니다.');
        }

        $updated = lc_conversion_get_by_id($cv_id);
        $updated['cp_name'] = is_array($campaign) ? $campaign['cp_name'] : '';
        $updated['pt_code'] = '';
        $updated['pt_name'] = '';
        $updated['mt_company'] = '';
        $partner = lc_get_partner_by_id((int) $conversion['pt_id']);
        if (is_array($partner)) {
            $updated['pt_code'] = $partner['pt_code'];
            $updated['pt_name'] = $partner['pt_name'];
        }
        if ($mt_id > 0 && function_exists('lc_get_merchant_by_id')) {
            $merchant = lc_get_merchant_by_id($mt_id);
            if (is_array($merchant)) {
                $updated['mt_company'] = $merchant['mt_company'];
            }
        }

        return array(
            'ok'         => true,
            'message'    => $action === 'confirm' ? '취소가 승인되었습니다.' : '디비가 승인 상태로 복원되었습니다.',
            'conversion' => lc_conversion_to_inspection_api($updated),
        );
    }
}

if (!function_exists('lc_conversion_partner_campaign_stats')) {
    function lc_conversion_partner_campaign_stats($pt_id, $limit = 10)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $limit = max(1, min(20, (int) $limit));
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $cl_table = lc_table('clicks');
        $rows = array();
        $result = lc_sql_query(" SELECT c.cp_name AS campaign,
            COUNT(DISTINCT cl.cl_id) AS clicks,
            COUNT(cv.cv_id) AS received,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS conf_rev
            FROM `{$cp_table}` c
            INNER JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id AND cv.pt_id = '{$pt_id}'
            LEFT JOIN `{$cl_table}` cl ON cl.cp_id = c.cp_id AND cl.pt_id = '{$pt_id}'
            GROUP BY c.cp_id
            ORDER BY conf_rev DESC
            LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $received = (int) $row['received'];
                $approved = (int) $row['approved'];
                $rows[] = array(
                    'campaign' => (string) $row['campaign'],
                    'clicks'   => (int) $row['clicks'],
                    'received' => $received,
                    'approved' => $approved,
                    'appRate'  => $received > 0 ? round(($approved / $received) * 100, 1) . '%' : '-',
                    'confRev'  => (int) $row['conf_rev'],
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_conversion_partner_analytics_for_api')) {
    function lc_conversion_partner_analytics_for_api($pt_id)
    {
        $summary = lc_conversion_partner_summary($pt_id);
        $clicks = function_exists('lc_link_partner_click_summary') ? lc_link_partner_click_summary($pt_id) : array('total' => 0);
        $total = (int) ($summary['total'] ?? 0);
        $approved = (int) ($summary['approved'] ?? 0);
        $rejected = (int) ($summary['rejected'] ?? 0);
        $total_clicks = (int) ($clicks['total'] ?? 0);

        return array(
            'summary' => array(
                'totalClicks'   => $total_clicks,
                'totalDb'       => $total,
                'approvedDb'    => $approved,
                'rejectedDb'    => $rejected,
                'avgConvRate'     => $total_clicks > 0 ? round(($total / $total_clicks) * 100, 1) : 0,
                'avgApprovalRate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            ),
            'chart7d'  => lc_conversion_partner_chart_7d($pt_id),
            'channels' => function_exists('lc_link_partner_channel_stats') ? lc_link_partner_channel_stats($pt_id, 10) : array(),
            'campaigns'=> lc_conversion_partner_campaign_stats($pt_id),
        );
    }
}

if (!function_exists('lc_conversion_partner_report_for_api')) {
    function lc_conversion_partner_report_for_api($pt_id)
    {
        $summary = lc_conversion_partner_summary($pt_id);
        $settlement = function_exists('lc_settlement_partner_summary') ? lc_settlement_partner_summary($pt_id) : array('availableAmount' => 0);
        $monthly = array();
        $cv_table = lc_table('conversions');
        $pt_id = (int) $pt_id;

        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime('-' . $i . ' months'));
            $label = (int) date('n', strtotime($month . '-01')) . '월';
            $start = $month . '-01';
            $end = date('Y-m-t', strtotime($start));
            $row = lc_sql_fetch(" SELECT COALESCE(SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END), 0) AS revenue
                FROM `{$cv_table}` WHERE pt_id = '{$pt_id}' AND DATE(cv_updated_at) BETWEEN '{$start}' AND '{$end}' ");
            $monthly[] = array(
                'month'  => $label,
                'value'  => (int) ($row['revenue'] ?? 0),
            );
        }

        $max = 0;
        foreach ($monthly as $item) {
            if ($item['value'] > $max) {
                $max = $item['value'];
            }
        }
        foreach ($monthly as &$item) {
            $item['pct'] = $max > 0 ? (int) round(($item['value'] / $max) * 100) : 0;
        }
        unset($item);

        return array(
            'summary' => array(
                'estRevenue'      => (int) ($summary['estRevenue'] ?? 0),
                'confRevenue'     => (int) ($summary['confRevenue'] ?? 0),
                'availableAmount' => (int) ($settlement['availableAmount'] ?? 0),
                'rejectedAmount'  => (int) ($summary['rejected'] ?? 0) * 0,
            ),
            'breakdown' => array(
                array('label' => '승인완료 수익', 'value' => (int) ($summary['confRevenue'] ?? 0)),
                array('label' => '검수중 예상', 'value' => max(0, (int) ($summary['estRevenue'] ?? 0) - (int) ($summary['confRevenue'] ?? 0))),
                array('label' => '취소/무효', 'value' => (int) ($summary['rejected'] ?? 0)),
            ),
            'monthly'   => $monthly,
            'campaigns' => lc_conversion_partner_campaign_stats($pt_id, 5),
        );
    }
}

if (!function_exists('lc_conversion_partner_cancel_summary')) {
    function lc_conversion_partner_cancel_summary($pt_id)
    {
        if (!lc_db_installed()) {
            return array('total' => 0, 'week' => 0, 'monthRate' => 0, 'topReason' => '-', 'reasons' => array());
        }

        $pt_id = (int) $pt_id;
        $cv_table = lc_table('conversions');
        $week_start = date('Y-m-d', strtotime('-7 days'));
        $month_start = date('Y-m-01');

        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}`
            WHERE pt_id = '{$pt_id}' AND cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' ");
        $week_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}`
            WHERE pt_id = '{$pt_id}' AND cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "'
            AND DATE(cv_created_at) >= '{$week_start}' ");
        $month_total = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}`
            WHERE pt_id = '{$pt_id}' AND DATE(cv_created_at) >= '{$month_start}' ");
        $month_rejected = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}`
            WHERE pt_id = '{$pt_id}' AND cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "'
            AND DATE(cv_created_at) >= '{$month_start}' ");

        $reasons = array();
        $top_reason = '-';
        $result = lc_sql_query(" SELECT cv_reject_reason AS reason, COUNT(*) AS cnt FROM `{$cv_table}`
            WHERE pt_id = '{$pt_id}' AND cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "'
            AND cv_reject_reason != ''
            GROUP BY cv_reject_reason ORDER BY cnt DESC LIMIT 5 ", false);
        if ($result) {
            $total_reason = 0;
            while ($row = sql_fetch_array($result)) {
                $cnt = (int) ($row['cnt'] ?? 0);
                $total_reason += $cnt;
                $reasons[] = array(
                    'reason' => (string) ($row['reason'] ?? ''),
                    'count'  => $cnt,
                );
            }
            if ($reasons) {
                $top_reason = $reasons[0]['reason'];
                foreach ($reasons as &$item) {
                    $item['percentage'] = $total_reason > 0 ? (int) round(($item['count'] / $total_reason) * 100) : 0;
                }
                unset($item);
            }
        }

        $month_total_cnt = (int) ($month_total['cnt'] ?? 0);
        $month_rejected_cnt = (int) ($month_rejected['cnt'] ?? 0);

        return array(
            'total'     => (int) ($total_row['cnt'] ?? 0),
            'week'      => (int) ($week_row['cnt'] ?? 0),
            'monthRate' => $month_total_cnt > 0 ? round(($month_rejected_cnt / $month_total_cnt) * 100, 1) : 0,
            'topReason' => $top_reason,
            'reasons'   => $reasons,
        );
    }
}

if (!function_exists('lc_conversion_partner_appeal')) {
    /**
     * @return array{ok:bool,message:string,conversion:array|null}
     */
    function lc_conversion_partner_appeal($pt_id, $cv_id, $appeal)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'conversion' => null);
        }

        if (!lc_settings_get_bool('partnerAppealAllowed', true)) {
            return array('ok' => false, 'message' => '현재 이의신청이 허용되지 않습니다.', 'conversion' => null);
        }

        $appeal = trim((string) $appeal);
        if ($appeal === '') {
            return array('ok' => false, 'message' => '이의신청 사유를 입력해주세요.', 'conversion' => null);
        }

        $pt_id = (int) $pt_id;
        $cv_id = (int) $cv_id;
        $table = lc_table('conversions');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cv_id = '{$cv_id}' AND pt_id = '{$pt_id}' LIMIT 1 ");
        if (!$row) {
            return array('ok' => false, 'message' => '디비를 찾을 수 없습니다.', 'conversion' => null);
        }
        if ((string) $row['cv_status'] !== LC_STATUS_REJECTED) {
            return array('ok' => false, 'message' => '취소/무효 디비만 이의신청할 수 있습니다.', 'conversion' => null);
        }
        if (trim((string) ($row['cv_partner_appeal'] ?? '')) !== '') {
            return array('ok' => false, 'message' => '이미 이의신청이 접수된 디비입니다.', 'conversion' => null);
        }

        lc_sql_query(" UPDATE `{$table}` SET
            cv_partner_appeal = '" . lc_sql_escape($appeal) . "',
            cv_updated_at = NOW()
            WHERE cv_id = '{$cv_id}' LIMIT 1 ", false);

        $updated = lc_conversion_get_by_id($cv_id);
        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT cp_name FROM `{$cp_table}` WHERE cp_id = '" . (int) $updated['cp_id'] . "' LIMIT 1 ");
        $updated['cp_name'] = $campaign['cp_name'] ?? '';

        return array(
            'ok'         => true,
            'message'    => '이의신청이 접수되었습니다.',
            'conversion' => lc_conversion_to_api_partner($updated),
        );
    }
}

if (!function_exists('lc_conversion_merchant_report_for_api')) {
    function lc_conversion_merchant_report_for_api($mt_id, array $filters = array())
    {
        if (!lc_db_installed()) {
            return array(
                'summary'   => array('total' => 0, 'approved' => 0, 'rejected' => 0, 'avgRate' => 0, 'totalSpend' => 0, 'avgPrice' => 0),
                'dbChart7d' => array(),
                'spendChart7d' => array(),
                'campaigns' => array(),
                'partners'  => array(),
            );
        }

        $mt_id = (int) $mt_id;
        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);
        if (!$campaign_ids) {
            return array(
                'summary'   => array('total' => 0, 'approved' => 0, 'rejected' => 0, 'avgRate' => 0, 'totalSpend' => 0, 'avgPrice' => 0),
                'dbChart7d' => lc_conversion_merchant_chart_7d($mt_id),
                'spendChart7d' => array(),
                'campaigns' => array(),
                'partners'  => array(),
            );
        }

        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');
        $in = implode(',', array_map('intval', $campaign_ids));

        $summary_row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS spend_total
            FROM `{$cv_table}` WHERE cp_id IN ({$in}) ");

        $total = (int) ($summary_row['total_cnt'] ?? 0);
        $approved = (int) ($summary_row['approved_cnt'] ?? 0);
        $rejected = (int) ($summary_row['rejected_cnt'] ?? 0);
        $spend = (int) ($summary_row['spend_total'] ?? 0);

        $db_chart = array();
        $spend_chart = array();
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $label = date('m.d', strtotime($day));
            $row = lc_sql_fetch(" SELECT
                COUNT(*) AS db_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approval_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS cancel_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' THEN cv_price ELSE 0 END) AS hold_spend,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS conf_spend,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN cv_price ELSE 0 END) AS refund_spend
                FROM `{$cv_table}` WHERE cp_id IN ({$in}) AND DATE(cv_created_at) = '{$day}' ");
            $db_chart[] = array(
                'date'    => $label,
                'received'=> (int) ($row['db_cnt'] ?? 0),
                'approved'=> (int) ($row['approval_cnt'] ?? 0),
                'rejected'=> (int) ($row['cancel_cnt'] ?? 0),
            );
            $spend_chart[] = array(
                'date'     => $label,
                'holdSpend'=> (int) ($row['hold_spend'] ?? 0),
                'confSpend'=> (int) ($row['conf_spend'] ?? 0),
                'refund'   => (int) ($row['refund_spend'] ?? 0),
            );
        }

        $campaigns = array();
        $result = lc_sql_query(" SELECT c.cp_id, c.cp_name, c.cp_status,
            COUNT(cv.cv_id) AS total_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS spend_total,
            AVG(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE NULL END) AS avg_price
            FROM `{$cp_table}` c
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
            WHERE c.mt_id = '{$mt_id}'
            GROUP BY c.cp_id
            ORDER BY spend_total DESC ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $c_total = (int) ($row['total_cnt'] ?? 0);
                $c_approved = (int) ($row['approved_cnt'] ?? 0);
                $c_rejected = (int) ($row['rejected_cnt'] ?? 0);
                $campaigns[] = array(
                    'id'           => (int) $row['cp_id'],
                    'name'         => (string) $row['cp_name'],
                    'total'        => $c_total,
                    'approved'     => $c_approved,
                    'canceled'     => $c_rejected,
                    'approvalRate' => $c_total > 0 ? round(($c_approved / $c_total) * 100, 1) : 0,
                    'cancelRate'   => $c_total > 0 ? round(($c_rejected / $c_total) * 100, 1) : 0,
                    'spend'        => (int) ($row['spend_total'] ?? 0),
                    'avgPrice'     => (int) round((float) ($row['avg_price'] ?? 0)),
                    'status'       => (string) $row['cp_status'] === LC_STATUS_ACTIVE ? '진행중' : '일시중지',
                );
            }
        }

        $partners = array();
        $result = lc_sql_query(" SELECT p.pt_code, p.pt_name,
            COUNT(cv.cv_id) AS total_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS spend_total
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            INNER JOIN `{$pt_table}` p ON p.pt_id = cv.pt_id
            WHERE c.mt_id = '{$mt_id}'
            GROUP BY p.pt_id
            ORDER BY spend_total DESC
            LIMIT 10 ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $p_total = (int) ($row['total_cnt'] ?? 0);
                $p_approved = (int) ($row['approved_cnt'] ?? 0);
                $p_rejected = (int) ($row['rejected_cnt'] ?? 0);
                $partners[] = array(
                    'code'         => (string) $row['pt_code'],
                    'name'         => (string) $row['pt_name'],
                    'total'        => $p_total,
                    'approved'     => $p_approved,
                    'canceled'     => $p_rejected,
                    'approvalRate' => $p_total > 0 ? round(($p_approved / $p_total) * 100, 1) : 0,
                    'spend'        => (int) ($row['spend_total'] ?? 0),
                    'note'         => $p_rejected > 0 && $p_total > 0 && ($p_rejected / $p_total) > 0.25 ? '취소율 모니터링' : '-',
                );
            }
        }

        return array(
            'summary' => array(
                'total'      => $total,
                'approved'   => $approved,
                'rejected'   => $rejected,
                'avgRate'    => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                'totalSpend' => $spend,
                'avgPrice'   => $approved > 0 ? (int) round($spend / $approved) : 0,
            ),
            'dbChart7d'    => $db_chart,
            'spendChart7d' => $spend_chart,
            'campaigns'    => $campaigns,
            'partners'     => $partners,
        );
    }
}
