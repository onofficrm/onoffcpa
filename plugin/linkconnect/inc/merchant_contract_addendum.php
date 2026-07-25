<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_addendum_table')) {
    function lc_merchant_contract_addendum_table()
    {
        return lc_table('merchant_contract_addenda');
    }
}

if (!function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_merchant_contract_addendum_db_ensure_schema()
    {
        $table = lc_merchant_contract_addendum_table();
        if (lc_db_table_exists($table)) {
            return array('ok' => true, 'message' => 'merchant_contract_addenda 테이블 준비 완료');
        }

        $create = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `mca_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `mc_id` bigint unsigned NOT NULL DEFAULT 0,
                `mt_id` int unsigned NOT NULL DEFAULT 0,
                `mca_seq` int unsigned NOT NULL DEFAULT 1,
                `mca_title` varchar(200) NOT NULL DEFAULT '특약사항',
                `mca_body` mediumtext NOT NULL,
                `mca_status` varchar(20) NOT NULL DEFAULT 'active',
                `mca_created_by_type` varchar(20) NOT NULL DEFAULT 'admin',
                `mca_created_by` varchar(100) NOT NULL DEFAULT '',
                `mca_voided_at` datetime DEFAULT NULL,
                `mca_void_reason` varchar(500) NOT NULL DEFAULT '',
                `mca_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `mca_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`mca_id`),
                KEY `idx_mca_mc_id` (`mc_id`),
                KEY `idx_mca_mt_id` (`mt_id`),
                KEY `idx_mca_status` (`mca_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

        if ($create === false) {
            return array('ok' => false, 'message' => 'merchant_contract_addenda 테이블 생성 실패: ' . lc_sql_error());
        }

        return array('ok' => true, 'message' => 'merchant_contract_addenda 테이블 준비 완료');
    }
}

if (!function_exists('lc_merchant_contract_addendum_can_add')) {
    /**
     * 체결(승인 완료) 또는 서명 후 승인 대기 중인 계약에 특약 추가 가능
     */
    function lc_merchant_contract_addendum_can_add(array $contract, $actor = 'admin')
    {
        $status = (string) ($contract['mc_status'] ?? '');
        if ($status === LC_MERCHANT_CONTRACT_STATUS_SIGNED) {
            return true;
        }
        if ($actor === 'admin' && $status === LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING) {
            return true;
        }

        return false;
    }
}

if (!function_exists('lc_merchant_contract_addendum_next_seq')) {
    function lc_merchant_contract_addendum_next_seq($mc_id)
    {
        $mc_id = (int) $mc_id;
        $table = lc_merchant_contract_addendum_table();
        if ($mc_id <= 0 || !lc_db_table_exists($table)) {
            return 1;
        }

        $row = lc_sql_fetch(" SELECT MAX(mca_seq) AS max_seq FROM `{$table}` WHERE mc_id = '{$mc_id}' ");
        $max = is_array($row) ? (int) ($row['max_seq'] ?? 0) : 0;

        return $max > 0 ? $max + 1 : 1;
    }
}

if (!function_exists('lc_merchant_contract_addendum_to_api')) {
    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    function lc_merchant_contract_addendum_to_api(array $row)
    {
        return array(
            'id'            => (int) ($row['mca_id'] ?? 0),
            'contractId'    => (int) ($row['mc_id'] ?? 0),
            'advertiserId'  => (int) ($row['mt_id'] ?? 0),
            'seq'           => (int) ($row['mca_seq'] ?? 0),
            'title'         => (string) ($row['mca_title'] ?? ''),
            'body'          => (string) ($row['mca_body'] ?? ''),
            'status'        => (string) ($row['mca_status'] ?? ''),
            'createdByType' => (string) ($row['mca_created_by_type'] ?? ''),
            'createdBy'     => (string) ($row['mca_created_by'] ?? ''),
            'voidedAt'      => (string) ($row['mca_voided_at'] ?? ''),
            'voidReason'    => (string) ($row['mca_void_reason'] ?? ''),
            'createdAt'     => (string) ($row['mca_created_at'] ?? ''),
            'updatedAt'     => (string) ($row['mca_updated_at'] ?? ''),
        );
    }
}

if (!function_exists('lc_merchant_contract_addendum_list')) {
    /**
     * @return list<array<string,mixed>>
     */
    function lc_merchant_contract_addendum_list($mc_id, $include_voided = true)
    {
        $mc_id = (int) $mc_id;
        $table = lc_merchant_contract_addendum_table();
        if ($mc_id <= 0 || !lc_db_table_exists($table)) {
            return array();
        }

        $where = "mc_id = '{$mc_id}'";
        if (!$include_voided) {
            $where .= " AND mca_status = 'active'";
        }

        $result = lc_sql_query(
            " SELECT * FROM `{$table}` WHERE {$where} ORDER BY mca_seq ASC, mca_id ASC ",
            false
        );
        if (!$result) {
            return array();
        }

        $rows = array();
        while ($row = sql_fetch_array($result)) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_contract_addendum_list_for_api')) {
    /**
     * @return list<array<string,mixed>>
     */
    function lc_merchant_contract_addendum_list_for_api($mc_id, $include_voided = true)
    {
        $items = array();
        foreach (lc_merchant_contract_addendum_list($mc_id, $include_voided) as $row) {
            $items[] = lc_merchant_contract_addendum_to_api($row);
        }

        return $items;
    }
}

if (!function_exists('lc_merchant_contract_addendum_get')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_merchant_contract_addendum_get($mca_id)
    {
        $mca_id = (int) $mca_id;
        $table = lc_merchant_contract_addendum_table();
        if ($mca_id <= 0 || !lc_db_table_exists($table)) {
            return null;
        }

        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE mca_id = '{$mca_id}' ");

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_merchant_contract_addendum_create')) {
    /**
     * @param array{title?:string,body:string,created_by_type?:string,created_by?:string} $data
     * @return array{ok:bool,message:string,addendum?:array<string,mixed>}
     */
    function lc_merchant_contract_addendum_create($mc_id, array $data)
    {
        if (function_exists('lc_merchant_contract_addendum_db_ensure_schema')) {
            lc_merchant_contract_addendum_db_ensure_schema();
        }

        $mc_id = (int) $mc_id;
        $contract = function_exists('lc_merchant_contract_get_by_id')
            ? lc_merchant_contract_get_by_id($mc_id)
            : null;
        if (!is_array($contract)) {
            return array('ok' => false, 'message' => '계약서를 찾을 수 없습니다.');
        }

        $actor = (string) ($data['created_by_type'] ?? 'admin');
        if (!lc_merchant_contract_addendum_can_add($contract, $actor === 'merchant' ? 'merchant' : 'admin')) {
            return array('ok' => false, 'message' => '체결(또는 승인 대기)된 계약에만 특약을 추가할 수 있습니다.');
        }

        $body = function_exists('lc_merchant_contract_sanitize_text')
            ? lc_merchant_contract_sanitize_text($data['body'] ?? '', 8000)
            : trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            return array('ok' => false, 'message' => '특약 내용을 입력해 주세요.');
        }

        $title = function_exists('lc_merchant_contract_sanitize_text')
            ? lc_merchant_contract_sanitize_text($data['title'] ?? '특약사항', 200)
            : trim((string) ($data['title'] ?? '특약사항'));
        if ($title === '') {
            $title = '특약사항';
        }

        $created_by_type = $actor === 'merchant' ? 'merchant' : 'admin';
        $created_by = trim((string) ($data['created_by'] ?? ''));
        if ($created_by === '') {
            global $member;
            if (is_array($member) && !empty($member['mb_id'])) {
                $created_by = (string) $member['mb_id'];
            }
        }

        $seq = lc_merchant_contract_addendum_next_seq($mc_id);
        $mt_id = (int) ($contract['mc_mt_id'] ?? 0);
        $table = lc_merchant_contract_addendum_table();

        $ok = lc_sql_query(" INSERT INTO `{$table}`
            SET mc_id = '{$mc_id}',
                mt_id = '{$mt_id}',
                mca_seq = '{$seq}',
                mca_title = '" . lc_sql_escape($title) . "',
                mca_body = '" . lc_sql_escape($body) . "',
                mca_status = 'active',
                mca_created_by_type = '" . lc_sql_escape($created_by_type) . "',
                mca_created_by = '" . lc_sql_escape($created_by) . "',
                mca_created_at = NOW(),
                mca_updated_at = NOW() ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '특약 저장에 실패했습니다.');
        }

        $mca_id = (int) lc_sql_insert_id();
        $row = lc_merchant_contract_addendum_get($mca_id);

        return array(
            'ok'       => true,
            'message'  => '특약이 추가되었습니다.',
            'addendum' => is_array($row) ? lc_merchant_contract_addendum_to_api($row) : null,
        );
    }
}

if (!function_exists('lc_merchant_contract_addendum_void')) {
    /**
     * @return array{ok:bool,message:string,addendum?:array<string,mixed>}
     */
    function lc_merchant_contract_addendum_void($mca_id, $reason = '')
    {
        $mca_id = (int) $mca_id;
        $row = lc_merchant_contract_addendum_get($mca_id);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '특약을 찾을 수 없습니다.');
        }
        if (($row['mca_status'] ?? '') === 'void') {
            return array('ok' => false, 'message' => '이미 무효 처리된 특약입니다.');
        }

        $reason = function_exists('lc_merchant_contract_sanitize_text')
            ? lc_merchant_contract_sanitize_text($reason, 500)
            : trim((string) $reason);

        $table = lc_merchant_contract_addendum_table();
        $ok = lc_sql_query(" UPDATE `{$table}`
            SET mca_status = 'void',
                mca_voided_at = NOW(),
                mca_void_reason = '" . lc_sql_escape($reason) . "',
                mca_updated_at = NOW()
            WHERE mca_id = '{$mca_id}' ", false);

        if ($ok === false) {
            return array('ok' => false, 'message' => '특약 무효 처리에 실패했습니다.');
        }

        $updated = lc_merchant_contract_addendum_get($mca_id);

        return array(
            'ok'       => true,
            'message'  => '특약이 무효 처리되었습니다.',
            'addendum' => is_array($updated) ? lc_merchant_contract_addendum_to_api($updated) : null,
        );
    }
}

if (!function_exists('lc_merchant_contract_addendum_html')) {
    /**
     * @param list<array<string,mixed>> $rows
     */
    function lc_merchant_contract_addendum_html(array $rows)
    {
        $active = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $status = (string) ($row['mca_status'] ?? $row['status'] ?? '');
            if ($status !== 'active') {
                continue;
            }
            $active[] = $row;
        }
        if (count($active) === 0) {
            return '';
        }

        $esc = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $sections = '';
        foreach ($active as $row) {
            $seq = (int) ($row['mca_seq'] ?? $row['seq'] ?? 0);
            $title = (string) ($row['mca_title'] ?? $row['title'] ?? '특약사항');
            $body = (string) ($row['mca_body'] ?? $row['body'] ?? '');
            $created_at = (string) ($row['mca_created_at'] ?? $row['createdAt'] ?? '');
            $by_type = (string) ($row['mca_created_by_type'] ?? $row['createdByType'] ?? '');
            $by_label = $by_type === 'merchant' ? '광고주' : '관리자';
            $body_html = nl2br($esc($body));
            $meta = $esc($by_label);
            if ($created_at !== '') {
                $meta .= ' · ' . $esc($created_at);
            }

            $sections .= <<<HTML

  <section class="lc-contract-article lc-contract-extra-terms lc-contract-addendum">
    <h2>특약 {$esc((string) $seq)} [ {$esc($title)} ]</h2>
    <p class="lc-contract-addendum-meta">본 특약은 원 계약 체결 이후 추가된 부속 합의이며, 원 계약과 충돌하는 경우 본 특약이 우선한다. ({$meta})</p>
    <div class="lc-contract-extra-body">{$body_html}</div>
  </section>
HTML;
        }

        return $sections;
    }
}

if (!function_exists('lc_merchant_contract_append_addenda_to_html')) {
    /**
     * @param list<array<string,mixed>>|null $rows
     */
    function lc_merchant_contract_append_addenda_to_html($html, $mc_id_or_rows = null)
    {
        $html = (string) $html;
        if ($html === '') {
            return $html;
        }

        if (is_array($mc_id_or_rows)) {
            $rows = $mc_id_or_rows;
        } else {
            $mc_id = (int) $mc_id_or_rows;
            $rows = $mc_id > 0 ? lc_merchant_contract_addendum_list($mc_id, false) : array();
        }

        $addenda_html = lc_merchant_contract_addendum_html($rows);
        if ($addenda_html === '') {
            return $html;
        }

        if (stripos($html, '</div>') !== false) {
            $pos = strripos($html, '</div>');
            if ($pos !== false) {
                return substr($html, 0, $pos) . $addenda_html . substr($html, $pos);
            }
        }

        return $html . $addenda_html;
    }
}
