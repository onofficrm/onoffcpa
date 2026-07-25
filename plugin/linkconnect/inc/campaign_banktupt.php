<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_banktupt_campaign_definition')) {
    /**
     * 운영용 개인회생 CPA 광고상품 (단일).
     *
     * @return array<string,mixed>
     */
    function lc_banktupt_campaign_definition()
    {
        return array(
            'code'              => 'CPA-BANKTUPT',
            'title'             => '개인회생 상담 DB',
            'category'          => '법률',
            'price'             => 30000,
            'approval_rate'     => '68%',
            'avg_time'          => '1.8일',
            'allowed_channels'  => '블로그, 카페, 지식iN, SNS',
            'forbidden_channels'=> '허위광고, 브랜드 사칭, 스팸문자',
            'description'       => '개인회생·개인파산·채권추심 무료 상담 DB. banktupt 랜딩 연동.',
            'badge'             => '추천',
            'recommended'       => true,
            'status'            => LC_STATUS_ACTIVE,
        );
    }
}

if (!function_exists('lc_banktupt_landing_path')) {
    function lc_banktupt_landing_path()
    {
        return '/merchant/banktupt/';
    }
}

if (!function_exists('lc_banktupt_landing_url')) {
    function lc_banktupt_landing_url()
    {
        $path = lc_banktupt_landing_path();
        if (defined('G5_URL') && G5_URL !== '') {
            return rtrim(G5_URL, '/') . $path;
        }

        return $path;
    }
}

if (!function_exists('lc_campaign_apply_banktupt_only')) {
    /**
     * 데모 CPA 광고상품을 정리하고 banktupt 개인회생 상품 1개만 활성화.
     *
     * @param array{advertiser_mb_id?:string,keep_cp_id?:int} $options
     * @return array{ok:bool,message:string,keptCpId?:int,ended?:int,linksUpdated?:int}
     */
    function lc_campaign_apply_banktupt_only(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : 'lc_advertiser';
        $keep_cp_id = isset($options['keep_cp_id']) ? (int) $options['keep_cp_id'] : 0;
        $def = lc_banktupt_campaign_definition();
        $landing = lc_banktupt_landing_url();
        $table = lc_table('campaigns');

        $merchant = function_exists('lc_get_merchant_by_mb_id') ? lc_get_merchant_by_mb_id($advertiser_mb) : null;
        $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;

        $keep = null;
        if ($keep_cp_id > 0) {
            $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_id = '{$keep_cp_id}' LIMIT 1 ");
        }
        if (!$keep && $mt_id > 0) {
            $name_esc = lc_sql_escape((string) $def['title']);
            $keep = lc_sql_fetch(" SELECT * FROM `{$table}`
                WHERE mt_id = '{$mt_id}' AND cp_name = '{$name_esc}'
                ORDER BY cp_id ASC LIMIT 1 ");
        }
        if (!$keep) {
            $code_esc = lc_sql_escape((string) $def['code']);
            $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
        }
        if (!$keep && $mt_id > 0) {
            $name_esc = lc_sql_escape((string) $def['title']);
            $keep = lc_sql_fetch(" SELECT * FROM `{$table}`
                WHERE mt_id = '{$mt_id}' AND cp_name LIKE '%개인회생%'
                ORDER BY cp_id ASC LIMIT 1 ");
        }

        if ($keep) {
            $cp_id = (int) $keep['cp_id'];
            lc_sql_query(" UPDATE `{$table}` SET
                mt_id = '" . ($mt_id > 0 ? $mt_id : (int) $keep['mt_id']) . "',
                cp_code = '" . lc_sql_escape((string) $def['code']) . "',
                cp_name = '" . lc_sql_escape((string) $def['title']) . "',
                cp_category = '" . lc_sql_escape((string) $def['category']) . "',
                cp_type = 'cpa',
                cp_price = '" . (int) $def['price'] . "',
                cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "',
                cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "',
                cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "',
                cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "',
                cp_description = '" . lc_sql_escape((string) $def['description']) . "',
                cp_landing_url = '" . lc_sql_escape($landing) . "',
                cp_status = '" . lc_sql_escape((string) $def['status']) . "',
                cp_badge = '" . lc_sql_escape((string) $def['badge']) . "',
                cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "',
                cp_sort = 1,
                cp_updated_at = NOW()
                WHERE cp_id = '{$cp_id}' ", false);
        } elseif ($mt_id > 0 && function_exists('lc_campaign_save')) {
            $saved = lc_campaign_save(array(
                'mtId'              => $mt_id,
                'name'              => (string) $def['title'],
                'category'          => (string) $def['category'],
                'type'              => 'cpa',
                'price'             => (int) $def['price'],
                'approvalRate'      => (string) $def['approval_rate'],
                'avgTime'           => (string) $def['avg_time'],
                'allowedChannels'   => (string) $def['allowed_channels'],
                'forbiddenChannels' => (string) $def['forbidden_channels'],
                'description'       => (string) $def['description'],
                'landingUrl'        => $landing,
                'badge'             => (string) $def['badge'],
                'recommended'       => !empty($def['recommended']),
                'statusCode'        => (string) $def['status'],
            ), 0);
            if (!$saved['ok'] || empty($saved['campaign']['id'])) {
                return array('ok' => false, 'message' => $saved['message']);
            }
            $cp_id = (int) $saved['campaign']['id'];
            lc_sql_query(" UPDATE `{$table}` SET cp_code = '" . lc_sql_escape((string) $def['code']) . "', cp_sort = 1 WHERE cp_id = '{$cp_id}' ", false);
        } else {
            return array('ok' => false, 'message' => '활성화할 광고주 또는 캠페인을 찾을 수 없습니다.');
        }

        $ended = 0;
        $result = lc_sql_query(" UPDATE `{$table}` SET cp_status = 'ended', cp_updated_at = NOW()
            WHERE cp_id != '{$cp_id}' AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' ", false);
        if ($result && function_exists('sql_affected_rows')) {
            $ended = (int) sql_affected_rows();
        }

        $links_updated = 0;
        $lk_table = lc_table('links');
        $lk_result = lc_sql_query(" UPDATE `{$lk_table}` SET cp_id = '{$cp_id}', lk_updated_at = NOW()
            WHERE cp_id != '{$cp_id}' AND lk_status = 'active' ", false);
        if ($lk_result && function_exists('sql_affected_rows')) {
            $links_updated = (int) sql_affected_rows();
        }

        return array(
            'ok'           => true,
            'message'      => '개인회생(banktupt) CPA 광고상품 1개만 활성화했습니다. 종료 ' . $ended . '건, 링크 갱신 ' . $links_updated . '건.',
            'keptCpId'     => $cp_id,
            'keptCode'     => (string) $def['code'],
            'landingUrl'   => $landing,
            'ended'        => $ended,
            'linksUpdated' => $links_updated,
        );
    }
}
