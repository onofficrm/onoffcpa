<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_status_log_table')) {
    function lc_merchant_contract_status_log_table()
    {
        return lc_table('merchant_contract_status_logs');
    }
}

if (!function_exists('lc_merchant_contract_status_log_db_ensure_schema')) {
    function lc_merchant_contract_status_log_db_ensure_schema()
    {
        $table = lc_merchant_contract_status_log_table();
        if (lc_db_table_exists($table)) {
            return array('ok' => true, 'message' => 'merchant_contract_status_logs 테이블 준비 완료');
        }

        $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `mcsl_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `mc_id` bigint unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `admin_mb_id` varchar(50) NOT NULL DEFAULT '',
                `mcsl_old_status` varchar(20) NOT NULL DEFAULT '',
                `mcsl_new_status` varchar(20) NOT NULL DEFAULT '',
                `mcsl_reason` varchar(1000) NOT NULL DEFAULT '',
                `mcsl_ip` varchar(45) NOT NULL DEFAULT '',
                `mcsl_user_agent` varchar(500) NOT NULL DEFAULT '',
                `mcsl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`mcsl_id`),
                KEY `idx_mcsl_mc_id` (`mc_id`),
                KEY `idx_mcsl_mt_id` (`mt_id`),
                KEY `idx_mcsl_admin` (`admin_mb_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

        if ($create === false) {
            return array('ok' => false, 'message' => 'merchant_contract_status_logs 테이블 생성 실패: ' . lc_sql_error());
        }

        return array('ok' => true, 'message' => 'merchant_contract_status_logs 테이블 준비 완료');
    }
}

if (!function_exists('lc_merchant_contract_status_log_write')) {
    function lc_merchant_contract_status_log_write(array $data)
    {
        if (!lc_db_table_exists(lc_merchant_contract_status_log_table())) {
            if (function_exists('lc_merchant_contract_status_log_db_ensure_schema')) {
                lc_merchant_contract_status_log_db_ensure_schema();
            }
        }

        if (!lc_db_table_exists(lc_merchant_contract_status_log_table())) {
            return 0;
        }

        global $member;
        $admin_mb_id = isset($data['admin_mb_id']) ? (string) $data['admin_mb_id'] : '';
        if ($admin_mb_id === '' && is_array($member) && isset($member['mb_id'])) {
            $admin_mb_id = (string) $member['mb_id'];
        }

        $ip = isset($data['ip']) ? (string) $data['ip'] : (isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '');
        $ua = isset($data['user_agent']) ? (string) $data['user_agent'] : (isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '');

        $table = lc_merchant_contract_status_log_table();
        lc_sql_query(" INSERT INTO `{$table}`
            SET mc_id = '" . (int) ($data['mc_id'] ?? 0) . "',
                mt_id = '" . (int) ($data['mt_id'] ?? 0) . "',
                admin_mb_id = '" . lc_sql_escape($admin_mb_id) . "',
                mcsl_old_status = '" . lc_sql_escape((string) ($data['old_status'] ?? '')) . "',
                mcsl_new_status = '" . lc_sql_escape((string) ($data['new_status'] ?? '')) . "',
                mcsl_reason = '" . lc_sql_escape((string) ($data['reason'] ?? '')) . "',
                mcsl_ip = '" . lc_sql_escape($ip) . "',
                mcsl_user_agent = '" . lc_sql_escape(mb_substr($ua, 0, 500, 'UTF-8')) . "',
                mcsl_created_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_merchant_contract_status_log_list')) {
    /**
     * @return list<array<string,mixed>>
     */
    function lc_merchant_contract_status_log_list($mc_id)
    {
        $mc_id = (int) $mc_id;
        if ($mc_id <= 0 || !lc_db_table_exists(lc_merchant_contract_status_log_table())) {
            return array();
        }

        $table = lc_merchant_contract_status_log_table();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE mc_id = '{$mc_id}' ORDER BY mcsl_created_at DESC, mcsl_id DESC ", false);
        if (!$result) {
            return array();
        }

        $rows = array();
        while ($row = sql_fetch_array($result)) {
            $rows[] = array(
                'id'         => (int) ($row['mcsl_id'] ?? 0),
                'adminId'    => (string) ($row['admin_mb_id'] ?? ''),
                'oldStatus'  => (string) ($row['mcsl_old_status'] ?? ''),
                'newStatus'  => (string) ($row['mcsl_new_status'] ?? ''),
                'oldLabel'   => lc_merchant_contract_status_label($row['mcsl_old_status'] ?? ''),
                'newLabel'   => lc_merchant_contract_status_label($row['mcsl_new_status'] ?? ''),
                'reason'     => (string) ($row['mcsl_reason'] ?? ''),
                'ip'         => (string) ($row['mcsl_ip'] ?? ''),
                'userAgent'  => (string) ($row['mcsl_user_agent'] ?? ''),
                'createdAt'  => (string) ($row['mcsl_created_at'] ?? ''),
            );
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_contract_admin_allowed_status_targets')) {
    /**
     * @return string[]
     */
    function lc_merchant_contract_admin_allowed_status_targets()
    {
        return array(
            LC_MERCHANT_CONTRACT_STATUS_SIGNED,
            LC_MERCHANT_CONTRACT_STATUS_REJECTED,
            LC_MERCHANT_CONTRACT_STATUS_CANCELLED,
            LC_MERCHANT_CONTRACT_STATUS_EXPIRED,
            LC_MERCHANT_CONTRACT_STATUS_RENEWAL,
        );
    }
}

if (!function_exists('lc_merchant_contract_admin_update_status')) {
    /**
     * @return array{ok:bool,message:string,contract?:array<string,mixed>|null}
     */
    function lc_merchant_contract_admin_update_status($mc_id, $new_status, $reason)
    {
        $mc_id = (int) $mc_id;
        $new_status = (string) $new_status;
        $reason = trim((string) $reason);

        if ($mc_id <= 0) {
            return array('ok' => false, 'message' => '계약 ID가 올바르지 않습니다.');
        }

        if ($reason === '' && $new_status !== LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
            return array('ok' => false, 'message' => '상태 변경 사유를 입력해 주세요.');
        }
        if ($new_status === LC_MERCHANT_CONTRACT_STATUS_SIGNED && $reason === '') {
            $reason = '계약서 검토 및 승인 완료';
        }

        if (!in_array($new_status, lc_merchant_contract_admin_allowed_status_targets(), true)) {
            return array('ok' => false, 'message' => '허용되지 않은 상태 변경입니다.');
        }

        $contract = lc_merchant_contract_get_by_id($mc_id);
        if (!is_array($contract)) {
            return array('ok' => false, 'message' => '계약서를 찾을 수 없습니다.');
        }

        $old_status = (string) ($contract['mc_status'] ?? '');
        if ($old_status === $new_status) {
            return array('ok' => false, 'message' => '이미 동일한 상태입니다.');
        }

        if ($old_status === LC_MERCHANT_CONTRACT_STATUS_SIGNED && !lc_merchant_contract_is_row_fully_signed($contract)) {
            return array('ok' => false, 'message' => '체결 정보가 불완전한 계약은 상태 변경 전 확인이 필요합니다.');
        }

        if (
            in_array($new_status, array(LC_MERCHANT_CONTRACT_STATUS_SIGNED, LC_MERCHANT_CONTRACT_STATUS_REJECTED), true)
            && $old_status !== LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING
        ) {
            return array('ok' => false, 'message' => '승인 대기 중인 계약서만 승인 또는 반려할 수 있습니다.');
        }
        if ($new_status === LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
            $required = array('mc_contract_pdf_path', 'mc_signature_file_path', 'mc_signed_at');
            foreach ($required as $field) {
                if (trim((string) ($contract[$field] ?? '')) === '') {
                    return array('ok' => false, 'message' => '서명 또는 계약 문서가 완성되지 않아 승인할 수 없습니다.');
                }
            }
        }

        $allowed_from = array(
            LC_MERCHANT_CONTRACT_STATUS_SIGNED,
            LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS,
            LC_MERCHANT_CONTRACT_STATUS_PENDING,
            LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING,
            LC_MERCHANT_CONTRACT_STATUS_REJECTED,
            LC_MERCHANT_CONTRACT_STATUS_RENEWAL,
        );
        if (!in_array($old_status, $allowed_from, true)) {
            return array('ok' => false, 'message' => '현재 상태에서는 변경할 수 없습니다.');
        }

        $table = lc_merchant_contract_table();
        $status_esc = lc_sql_escape($new_status);
        $update = lc_sql_query(" UPDATE `{$table}`
            SET mc_status = '{$status_esc}', mc_updated_at = NOW()
            WHERE mc_id = '{$mc_id}' ", false);

        if ($update === false) {
            return array('ok' => false, 'message' => '상태 변경 저장에 실패했습니다.');
        }

        if (function_exists('lc_merchant_contract_access_cache_clear')) {
            lc_merchant_contract_access_cache_clear((int) $contract['mc_mt_id']);
        }

        lc_merchant_contract_status_log_write(array(
            'mc_id'      => $mc_id,
            'mt_id'      => (int) $contract['mc_mt_id'],
            'old_status' => $old_status,
            'new_status' => $new_status,
            'reason'     => $reason,
        ));

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write(
                'contract_status',
                'merchant_contract',
                $mc_id,
                '계약 상태 변경: ' . $old_status . ' → ' . $new_status,
                array(
                    'mtId'   => (int) $contract['mc_mt_id'],
                    'reason' => $reason,
                )
            );
        }

        if (function_exists('lc_notification_create')) {
            $status_labels = array(
                LC_MERCHANT_CONTRACT_STATUS_SIGNED    => '승인 완료',
                LC_MERCHANT_CONTRACT_STATUS_REJECTED  => '승인 반려',
                LC_MERCHANT_CONTRACT_STATUS_CANCELLED => '해지',
                LC_MERCHANT_CONTRACT_STATUS_EXPIRED   => '만료',
                LC_MERCHANT_CONTRACT_STATUS_RENEWAL   => '갱신 필요',
            );
            $label = $status_labels[$new_status] ?? $new_status;
            lc_notification_create(array(
                'center'  => 'merchant',
                'userId'  => (int) $contract['mc_mt_id'],
                'type'    => 'contract',
                'title'   => '계약 상태가 변경되었습니다',
                'body'    => $label . ' · ' . $reason,
                'link'    => '/advertiser/contract',
                'refType' => 'merchant_contract',
                'refId'   => $mc_id,
            ));
        }

        $fresh = lc_merchant_contract_get_by_id($mc_id);
        if (
            $new_status === LC_MERCHANT_CONTRACT_STATUS_SIGNED
            && function_exists('lc_merchant_contract_send_signed_emails')
        ) {
            lc_merchant_contract_send_signed_emails((int) $contract['mc_mt_id'], is_array($fresh) ? $fresh : array());
        }

        return array(
            'ok'       => true,
            'message'  => '계약 상태가 변경되었습니다.',
            'contract' => is_array($fresh) ? $fresh : null,
        );
    }
}

if (!function_exists('lc_merchant_contract_current_company_for_compare')) {
    /**
     * @return array<string,string>
     */
    function lc_merchant_contract_current_company_for_compare($mt_id)
    {
        $mt_id = (int) $mt_id;
        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        if (!is_array($merchant)) {
            return array(
                'companyName'        => '',
                'representativeName' => '',
                'businessNumber'     => '',
                'companyAddress'     => '',
                'companyPhone'       => '',
            );
        }

        return array(
            'companyName'        => (string) ($merchant['mt_company'] ?? ''),
            'representativeName' => '',
            'businessNumber'     => '',
            'companyAddress'     => '',
            'companyPhone'       => '',
        );
    }
}

if (!function_exists('lc_merchant_contract_compare_company')) {
    /**
     * @param array<string,mixed> $contract
     * @return array<string,array{contract:string,current:string,changed:bool}>
     */
    function lc_merchant_contract_compare_company(array $contract)
    {
        $mt_id = (int) ($contract['mc_mt_id'] ?? 0);
        $contract_form = lc_merchant_contract_form_from_row($contract);
        $current = lc_merchant_contract_current_company_for_compare($mt_id);
        $fields = array('companyName', 'representativeName', 'businessNumber', 'companyAddress', 'companyPhone');
        $out = array();

        foreach ($fields as $field) {
            $c_val = (string) ($contract_form[$field] ?? '');
            $n_val = (string) ($current[$field] ?? '');
            $out[$field] = array(
                'contract' => $c_val,
                'current'  => $n_val,
                'changed'  => $c_val !== '' && $n_val !== '' && $c_val !== $n_val,
            );
        }

        return $out;
    }
}

if (!function_exists('lc_merchant_contract_admin_list_item_to_api')) {
    /**
     * @param array<string,mixed> $contract
     * @return array<string,mixed>
     */
    function lc_merchant_contract_admin_list_item_to_api(array $contract)
    {
        return array(
            'id'               => (int) ($contract['mc_id'] ?? 0),
            'advertiserId'     => (int) ($contract['mc_mt_id'] ?? 0),
            'companyName'      => (string) ($contract['mc_company_name'] ?? ''),
            'representativeName' => (string) ($contract['mc_representative_name'] ?? ''),
            'businessNumber'   => (string) ($contract['mc_business_number'] ?? ''),
            'signerName'       => (string) ($contract['mc_signer_name'] ?? ''),
            'contractVersion'  => (string) ($contract['mc_contract_version'] ?? ''),
            'status'           => (string) ($contract['mc_status'] ?? ''),
            'statusLabel'      => lc_merchant_contract_status_label($contract['mc_status'] ?? ''),
            'contractCode'     => (string) ($contract['mc_contract_code'] ?? ''),
            'signedAt'         => (string) ($contract['mc_signed_at'] ?? ''),
            'createdAt'        => (string) ($contract['mc_created_at'] ?? ''),
            'isFullySigned'    => lc_merchant_contract_is_row_fully_signed($contract),
        );
    }
}

if (!function_exists('lc_merchant_contract_admin_list_for_api')) {
    /**
     * @param array<string,mixed> $filters
     * @return array{items:list<array<string,mixed>>,total:int,summary:array<string,int>}
     */
    function lc_merchant_contract_admin_list_for_api(array $filters = array())
    {
        if (!lc_db_installed() || !lc_db_table_exists(lc_merchant_contract_table())) {
            return array(
                'items' => array(),
                'total' => 0,
                'summary' => array(
                    'total' => 0,
                    'signed' => 0,
                    'reviewPending' => 0,
                    'rejected' => 0,
                    'pending' => 0,
                    'inProgress' => 0,
                    'cancelled' => 0,
                    'expired' => 0,
                    'renewal' => 0,
                ),
            );
        }

        $table = lc_merchant_contract_table();
        $where = array('1=1');
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $version = trim((string) ($filters['version'] ?? ''));
        $mt_id = (int) ($filters['mtId'] ?? 0);
        $signed_from = trim((string) ($filters['signedFrom'] ?? ''));
        $signed_to = trim((string) ($filters['signedTo'] ?? ''));

        if ($mt_id > 0) {
            $where[] = "mc_mt_id = '{$mt_id}'";
        }

        if ($status !== '' && $status !== 'all') {
            if ($status === 'unsigned') {
                $where[] = "mc_status IN ('" . lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_PENDING) . "','" . lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS) . "')";
            } else {
                $where[] = "mc_status = '" . lc_sql_escape($status) . "'";
            }
        }

        if ($version !== '') {
            $version = lc_merchant_contract_sanitize_version($version);
            if ($version === '') {
                return array('items' => array(), 'total' => 0, 'summary' => array());
            }
            $where[] = "mc_contract_version = '" . lc_sql_escape($version) . "'";
        }

        if ($signed_from !== '') {
            $where[] = "mc_signed_at >= '" . lc_sql_escape($signed_from . ' 00:00:00') . "'";
        }
        if ($signed_to !== '') {
            $where[] = "mc_signed_at <= '" . lc_sql_escape($signed_to . ' 23:59:59') . "'";
        }

        if ($q !== '') {
            $q_esc = lc_sql_escape('%' . $q . '%');
            $where[] = "(mc_company_name LIKE '{$q_esc}' OR mc_representative_name LIKE '{$q_esc}' OR mc_business_number LIKE '{$q_esc}' OR mc_signer_name LIKE '{$q_esc}' OR mc_contract_code LIKE '{$q_esc}' OR CAST(mc_mt_id AS CHAR) LIKE '{$q_esc}')";
        }

        $where_sql = implode(' AND ', $where);
        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where_sql} ");
        $total = (int) ($count_row['cnt'] ?? 0);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(10, (int) ($filters['limit'] ?? 30)));
        $offset = ($page - 1) * $limit;

        $review_pending = lc_sql_escape(LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING);
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where_sql}
            ORDER BY (mc_status = '{$review_pending}') DESC, mc_updated_at DESC, mc_id DESC
            LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_merchant_contract_admin_list_item_to_api($row);
            }
        }

        $summary = array(
            'total'       => $total,
            'signed'      => 0,
            'reviewPending' => 0,
            'rejected'    => 0,
            'pending'     => 0,
            'inProgress'  => 0,
            'cancelled'   => 0,
            'expired'     => 0,
            'renewal'     => 0,
        );
        $sum_result = lc_sql_query(" SELECT mc_status, COUNT(*) AS cnt FROM `{$table}` GROUP BY mc_status ", false);
        if ($sum_result) {
            while ($row = sql_fetch_array($sum_result)) {
                $st = (string) ($row['mc_status'] ?? '');
                $cnt = (int) ($row['cnt'] ?? 0);
                if ($st === LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
                    $summary['signed'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING) {
                    $summary['reviewPending'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_REJECTED) {
                    $summary['rejected'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_PENDING) {
                    $summary['pending'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_IN_PROGRESS) {
                    $summary['inProgress'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_CANCELLED) {
                    $summary['cancelled'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_EXPIRED) {
                    $summary['expired'] = $cnt;
                } elseif ($st === LC_MERCHANT_CONTRACT_STATUS_RENEWAL) {
                    $summary['renewal'] = $cnt;
                }
            }
        }

        return array(
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'summary' => $summary,
        );
    }
}

if (!function_exists('lc_merchant_contract_admin_detail_for_api')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_admin_detail_for_api($mc_id)
    {
        $contract = lc_merchant_contract_get_by_id($mc_id);
        if (!is_array($contract)) {
            return null;
        }

        $mt_id = (int) ($contract['mc_mt_id'] ?? 0);
        $read = lc_merchant_contract_read_to_api($contract);
        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;

        $history = array();
        foreach (lc_merchant_contract_list_by_merchant($mt_id) as $row) {
            $history[] = lc_merchant_contract_history_item_to_api($row);
        }

        $addenda = function_exists('lc_merchant_contract_addendum_list_for_api')
            ? lc_merchant_contract_addendum_list_for_api($mc_id, true)
            : array();
        if (!empty($read['contractHtml']) && function_exists('lc_merchant_contract_append_addenda_to_html')) {
            $read['contractHtml'] = lc_merchant_contract_append_addenda_to_html(
                (string) $read['contractHtml'],
                $mc_id
            );
        }
        $read['canAddAddendum'] = function_exists('lc_merchant_contract_addendum_can_add')
            ? lc_merchant_contract_addendum_can_add($contract, 'admin')
            : false;

        return array(
            'contract'       => $read,
            'listItem'       => lc_merchant_contract_admin_list_item_to_api($contract),
            'merchant'       => is_array($merchant) ? array(
                'id'      => (int) ($merchant['mt_id'] ?? 0),
                'code'    => (string) ($merchant['mt_code'] ?? ''),
                'company' => (string) ($merchant['mt_company'] ?? ''),
                'status'  => (string) ($merchant['mt_status'] ?? ''),
            ) : null,
            'companyCompare' => lc_merchant_contract_compare_company($contract),
            'companySnapshot'=> lc_merchant_contract_decode_snapshot($contract['mc_company_snapshot'] ?? ''),
            'history'        => $history,
            'addenda'        => $addenda,
            'statusLogs'     => lc_merchant_contract_status_log_list($mc_id),
            'signLogs'       => lc_merchant_contract_sign_logs_for_api($mc_id),
            'documentPreviewUrl' => LC_PLUGIN_URL . '/admin/contract-document.php?mcId=' . (int) $mc_id,
            'documentPdfUrl'     => LC_PLUGIN_URL . '/admin/contract-download.php?mcId=' . (int) $mc_id,
            'signatureUrl'       => LC_PLUGIN_URL . '/admin/contract-signature.php?mcId=' . (int) $mc_id,
        );
    }
}

if (!function_exists('lc_merchant_contract_sign_logs_for_api')) {
    /**
     * @return list<array<string,mixed>>
     */
    function lc_merchant_contract_sign_logs_for_api($mc_id)
    {
        $mc_id = (int) $mc_id;
        if ($mc_id <= 0 || !lc_db_table_exists(lc_merchant_contract_log_table())) {
            return array();
        }

        $table = lc_merchant_contract_log_table();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE mc_id = '{$mc_id}' ORDER BY mcl_created_at DESC, mcl_id DESC ", false);
        if (!$result) {
            return array();
        }

        $rows = array();
        while ($row = sql_fetch_array($result)) {
            $rows[] = array(
                'id'        => (int) ($row['mcl_id'] ?? 0),
                'result'    => (string) ($row['mcl_result'] ?? ''),
                'message'   => (string) ($row['mcl_message'] ?? ''),
                'signedAt'  => (string) ($row['mcl_signed_at'] ?? ''),
                'ip'        => (string) ($row['mcl_ip'] ?? ''),
                'userAgent' => (string) ($row['mcl_user_agent'] ?? ''),
                'pdfHash'   => lc_merchant_contract_mask_hash((string) ($row['mcl_pdf_hash'] ?? '')),
                'createdAt' => (string) ($row['mcl_created_at'] ?? ''),
            );
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_contract_admin_serve_file')) {
    /**
     * @return array{ok:bool,message:string,absolute?:string,filename?:string,hash?:string}
     */
    function lc_merchant_contract_admin_serve_file($mc_id, $type = 'pdf')
    {
        $contract = lc_merchant_contract_get_by_id((int) $mc_id);
        if (!is_array($contract)) {
            return array('ok' => false, 'message' => '계약서를 찾을 수 없습니다.');
        }

        if ($type === 'pdf') {
            $document_statuses = array(
                LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING,
                LC_MERCHANT_CONTRACT_STATUS_REJECTED,
                LC_MERCHANT_CONTRACT_STATUS_SIGNED,
            );
            if (!in_array((string) ($contract['mc_status'] ?? ''), $document_statuses, true)) {
                return array('ok' => false, 'message' => '검토할 계약서 PDF가 없습니다.');
            }
            $relative = (string) ($contract['mc_contract_pdf_path'] ?? '');
            $absolute = lc_merchant_contract_absolute_storage_path($relative);
            if ($absolute === '' || !is_file($absolute)) {
                return array('ok' => false, 'message' => 'PDF 파일을 찾을 수 없습니다.');
            }

            return array(
                'ok'       => true,
                'message'  => '',
                'absolute' => $absolute,
                'filename' => basename($absolute),
                'hash'     => (string) ($contract['mc_contract_file_hash'] ?? ''),
            );
        }

        if ($type === 'signature') {
            $path = (string) ($contract['mc_signature_file_path'] ?? '');
            if ($path === '') {
                return array('ok' => false, 'message' => '서명 파일이 없습니다.');
            }
            $absolute = strpos($path, '/') === 0 ? $path : (LC_PLUGIN_PATH . '/' . ltrim($path, '/'));
            if (!is_file($absolute)) {
                return array('ok' => false, 'message' => '서명 파일을 찾을 수 없습니다.');
            }

            return array(
                'ok'       => true,
                'message'  => '',
                'absolute' => $absolute,
                'filename' => basename($absolute),
            );
        }

        return array('ok' => false, 'message' => '지원하지 않는 파일 유형입니다.');
    }
}
