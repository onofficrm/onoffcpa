<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_modemo_campaign_definition')) {
    /**
     * 모두의철거 CPA 광고상품 정의 (ADV-0008).
     *
     * @return array<string,mixed>
     */
    function lc_modemo_campaign_definition()
    {
        return array(
            'code'               => 'CPA-MODEMO',
            'title'              => '철거·원상복구 상담 DB',
            'category'           => '생활서비스',
            'price'              => 30000,
            'merchant_price'     => 45000,
            'approval_rate'      => '70%',
            'avg_time'           => '1.5일',
            'allowed_channels'   => '블로그, 카페, 지식iN, SNS',
            'forbidden_channels' => '허위광고, 브랜드 사칭, 스팸문자',
            'description'        => '상가·주택 철거, 사무실 원상복구, 폐기물 처리 상담 DB. modemo 랜딩 연동.',
            'badge'              => '신규',
            'recommended'        => true,
            'status'             => 'paused',
        );
    }
}

if (!function_exists('lc_modemo_landing_path')) {
    function lc_modemo_landing_path()
    {
        return '/merchant/modemo/';
    }
}

if (!function_exists('lc_modemo_landing_url')) {
    function lc_modemo_landing_url()
    {
        $path = lc_modemo_landing_path();
        if (defined('G5_URL') && G5_URL !== '') {
            return rtrim(G5_URL, '/') . $path;
        }

        return $path;
    }
}

if (!function_exists('lc_campaign_ensure_modemo')) {
    /**
     * 모두의철거 CPA 상품을 생성/갱신한다.
     *
     * @param array{advertiser_mb_id?:string,mt_id?:int,activate?:bool} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool}
     */
    function lc_campaign_ensure_modemo(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $def = lc_modemo_campaign_definition();
        $landing = lc_modemo_landing_url();
        // 철거 랜딩 독립 도메인 (다시봄 air911 과 동일 패턴)
        $tracking_base = 'https://yevely.kr';
        $table = lc_table('campaigns');

        $mt_id = isset($options['mt_id']) ? (int) $options['mt_id'] : 0;
        if ($mt_id <= 0) {
            $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : '';
            if ($advertiser_mb !== '' && function_exists('lc_get_merchant_by_mb_id')) {
                $merchant = lc_get_merchant_by_mb_id($advertiser_mb);
                $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
            }
        }

        // ADV-0008(모두의철거) 자동 연결 시도
        if ($mt_id <= 0 && function_exists('lc_sql_fetch')) {
            $merchants = lc_table('merchants');
            $adv = lc_sql_fetch(" SELECT mt_id FROM `{$merchants}` WHERE mt_code = 'ADV-0008' LIMIT 1 ");
            if ($adv) {
                $mt_id = (int) $adv['mt_id'];
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
            $next_status = (string) $keep['cp_status'];
            if (!empty($options['activate']) && $next_mt > 0) {
                $next_status = LC_STATUS_ACTIVE;
            } elseif ($next_status === '' || $next_status === LC_STATUS_DRAFT) {
                $next_status = $status;
            }

            lc_sql_query(" UPDATE `{$table}` SET
                mt_id = '{$next_mt}',
                cp_code = '{$code_esc}',
                cp_landing_url = '" . lc_sql_escape($landing) . "',
                cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "',
                cp_status = '" . lc_sql_escape($next_status) . "',
                cp_updated_at = NOW()
                WHERE cp_id = '{$cp_id}' ", false);

            return array(
                'ok'      => true,
                'message' => '모두의철거 CPA 캠페인을 갱신했습니다. (독립도메인 yevely.kr)',
                'cpId'    => $cp_id,
                'created' => false,
                'trackingBaseUrl' => $tracking_base,
            );
        }

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
            return array('ok' => false, 'message' => '모두의철거 CPA 캠페인 생성에 실패했습니다.');
        }

        return array(
            'ok'      => true,
            'message' => $mt_id > 0
                ? '모두의철거 CPA 캠페인을 생성했습니다. (독립도메인 yevely.kr)'
                : '모두의철거 CPA 캠페인을 광고주 미연결(일시중지) 상태로 등록했습니다.',
            'cpId'    => $cp_id,
            'created' => true,
            'trackingBaseUrl' => $tracking_base,
        );
    }
}
