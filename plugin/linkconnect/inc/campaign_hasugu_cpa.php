<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_hasugu_cpa_campaign_definition')) {
    /**
     * 하수구/배관 CPA 광고상품 정의 (광고주 연결 전에도 등록 가능).
     *
     * @return array<string,mixed>
     */
    function lc_hasugu_cpa_campaign_definition()
    {
        return array(
            'code'               => 'CPA-HASUGU',
            'title'              => '하수구·배관 상담 DB',
            'category'           => '생활서비스',
            'price'              => 25000,
            'merchant_price'     => 35000,
            'approval_rate'      => '70%',
            'avg_time'           => '1.5일',
            'allowed_channels'   => '블로그, 카페, 지식iN, SNS',
            'forbidden_channels' => '허위광고, 브랜드 사칭, 스팸문자',
            'description'        => '하수구막힘·싱크대막힘·변기막힘·악취·역류 상담 DB. hasugu_cpa 랜딩 연동.',
            'badge'              => '신규',
            'recommended'        => true,
            // 광고주 미연결 시 일시중지 — 연결 후 운영중으로 전환
            'status'             => 'paused',
        );
    }
}

if (!function_exists('lc_hasugu_cpa_landing_path')) {
    function lc_hasugu_cpa_landing_path()
    {
        return '/merchant/hasugu_cpa/';
    }
}

if (!function_exists('lc_hasugu_cpa_landing_url')) {
    function lc_hasugu_cpa_landing_url()
    {
        $path = lc_hasugu_cpa_landing_path();
        if (defined('G5_URL') && G5_URL !== '') {
            return rtrim(G5_URL, '/') . $path;
        }

        return $path;
    }
}

if (!function_exists('lc_campaign_ensure_hasugu_cpa')) {
    /**
     * 하수구 CPA 상품을 생성/갱신한다. 광고주(mt_id) 없어도 등록 가능(일시중지).
     *
     * @param array{advertiser_mb_id?:string,mt_id?:int,activate?:bool} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool}
     */
    function lc_campaign_ensure_hasugu_cpa(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $def = lc_hasugu_cpa_campaign_definition();
        $landing = lc_hasugu_cpa_landing_url();
        $tracking_base = defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
        $table = lc_table('campaigns');

        $mt_id = isset($options['mt_id']) ? (int) $options['mt_id'] : 0;
        if ($mt_id <= 0) {
            $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : '';
            if ($advertiser_mb !== '' && function_exists('lc_get_merchant_by_mb_id')) {
                $merchant = lc_get_merchant_by_mb_id($advertiser_mb);
                $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
            }
        }

        $status = (string) $def['status'];
        if ($mt_id > 0 && !empty($options['activate'])) {
            $status = LC_STATUS_ACTIVE;
        }

        $code_esc = lc_sql_escape((string) $def['code']);
        $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");

        if ($keep) {
            $cp_id = (int) $keep['cp_id'];
            $next_mt = $mt_id > 0 ? $mt_id : (int) $keep['mt_id'];
            // 이미 운영중이면 상태 유지, 신규 옵션으로 activate만 올릴 때 갱신
            $next_status = (string) $keep['cp_status'];
            if (!empty($options['activate']) && $next_mt > 0) {
                $next_status = LC_STATUS_ACTIVE;
            } elseif ($next_status === '' || $next_status === LC_STATUS_DRAFT) {
                $next_status = $status;
            }

            lc_sql_query(" UPDATE `{$table}` SET
                mt_id = '{$next_mt}',
                cp_code = '{$code_esc}',
                cp_name = '" . lc_sql_escape((string) $def['title']) . "',
                cp_category = '" . lc_sql_escape((string) $def['category']) . "',
                cp_type = 'cpa',
                cp_price = '" . (int) $def['price'] . "',
                cp_merchant_price = '" . (int) $def['merchant_price'] . "',
                cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "',
                cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "',
                cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "',
                cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "',
                cp_description = '" . lc_sql_escape((string) $def['description']) . "',
                cp_landing_url = '" . lc_sql_escape($landing) . "',
                cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "',
                cp_status = '" . lc_sql_escape($next_status) . "',
                cp_badge = '" . lc_sql_escape((string) $def['badge']) . "',
                cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "',
                cp_updated_at = NOW()
                WHERE cp_id = '{$cp_id}' ", false);

            return array(
                'ok'      => true,
                'message' => '하수구 CPA 캠페인을 갱신했습니다.',
                'cpId'    => $cp_id,
                'created' => false,
            );
        }

        // 광고주 없어도 직접 INSERT (계약 검증 우회) — 관리자에서 광고주 배정 후 활성화
        lc_sql_query(" INSERT INTO `{$table}` SET
            mt_id = '{$mt_id}',
            cp_code = '{$code_esc}',
            cp_name = '" . lc_sql_escape((string) $def['title']) . "',
            cp_category = '" . lc_sql_escape((string) $def['category']) . "',
            cp_type = 'cpa',
            cp_price = '" . (int) $def['price'] . "',
            cp_merchant_price = '" . (int) $def['merchant_price'] . "',
            cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "',
            cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "',
            cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "',
            cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "',
            cp_description = '" . lc_sql_escape((string) $def['description']) . "',
            cp_landing_url = '" . lc_sql_escape($landing) . "',
            cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "',
            cp_status = '" . lc_sql_escape($status) . "',
            cp_badge = '" . lc_sql_escape((string) $def['badge']) . "',
            cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "',
            cp_sort = 0,
            cp_created_at = NOW(),
            cp_updated_at = NOW() ", false);

        $cp_id = 0;
        if (function_exists('sql_insert_id')) {
            $cp_id = (int) sql_insert_id();
        }
        if ($cp_id <= 0) {
            $row = lc_sql_fetch(" SELECT cp_id FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
            $cp_id = $row ? (int) $row['cp_id'] : 0;
        }

        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '하수구 CPA 캠페인 생성에 실패했습니다.');
        }

        return array(
            'ok'      => true,
            'message' => $mt_id > 0
                ? '하수구 CPA 캠페인을 생성했습니다.'
                : '하수구 CPA 캠페인을 광고주 미연결(일시중지) 상태로 등록했습니다.',
            'cpId'    => $cp_id,
            'created' => true,
        );
    }
}
