<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_dasibom_campaign_definition')) {
    /**
     * 다시봄 개인회생/파산 CPA 광고상품.
     *
     * @return array<string,mixed>
     */
    function lc_dasibom_campaign_definition()
    {
        return array(
            'code'               => 'CPA-DASIBOM',
            'title'              => '다시봄 개인회생/파산 상담 DB',
            'category'           => '법률',
            'price'              => 30000,
            'approval_rate'      => '68%',
            'avg_time'           => '1.8일',
            'allowed_channels'   => '블로그, 카페, 지식iN, SNS',
            'forbidden_channels' => '허위광고, 브랜드 사칭, 스팸문자',
            'description'        => '다시봄 재정회복센터 개인회생·개인파산 무료 상담 DB. dasibom 랜딩 연동.',
            'badge'              => '추천',
            'recommended'        => true,
            'status'             => LC_STATUS_ACTIVE,
        );
    }
}

if (!function_exists('lc_dasibom_landing_path')) {
    function lc_dasibom_landing_path()
    {
        return '/merchant/dasibom/';
    }
}

if (!function_exists('lc_dasibom_landing_url')) {
    function lc_dasibom_landing_url()
    {
        $path = lc_dasibom_landing_path();
        if (defined('G5_URL') && G5_URL !== '') {
            return rtrim(G5_URL, '/') . $path;
        }

        return $path;
    }
}

if (!function_exists('lc_campaign_ensure_dasibom')) {
    /**
     * 다시봄 CPA 상품을 생성/갱신한다. 다른 캠페인은 종료하지 않음.
     *
     * @param array{advertiser_mb_id?:string,mt_id?:int} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool}
     */
    function lc_campaign_ensure_dasibom(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $def = lc_dasibom_campaign_definition();
        $landing = lc_dasibom_landing_url();
        $tracking_base = 'https://air911.co.kr';
        $table = lc_table('campaigns');

        $mt_id = isset($options['mt_id']) ? (int) $options['mt_id'] : 0;
        if ($mt_id <= 0) {
            $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : '';
            if ($advertiser_mb !== '' && function_exists('lc_get_merchant_by_mb_id')) {
                $merchant = lc_get_merchant_by_mb_id($advertiser_mb);
                $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
            }
        }

        $code_esc = lc_sql_escape((string) $def['code']);
        $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");

        if ($keep) {
            $cp_id = (int) $keep['cp_id'];
            $next_mt = $mt_id > 0 ? $mt_id : (int) $keep['mt_id'];
            lc_sql_query(" UPDATE `{$table}` SET
                mt_id = '{$next_mt}',
                cp_code = '{$code_esc}',
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
                cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "',
                cp_status = '" . lc_sql_escape((string) $def['status']) . "',
                cp_badge = '" . lc_sql_escape((string) $def['badge']) . "',
                cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "',
                cp_updated_at = NOW()
                WHERE cp_id = '{$cp_id}' ", false);

            return array(
                'ok'      => true,
                'message' => '다시봄 캠페인을 갱신했습니다.',
                'cpId'    => $cp_id,
                'created' => false,
            );
        }

        if ($mt_id <= 0) {
            return array(
                'ok'      => false,
                'message' => '광고주(mt_id 또는 advertiser_mb_id)를 지정해 주세요.',
            );
        }

        if (!function_exists('lc_campaign_save')) {
            return array('ok' => false, 'message' => 'lc_campaign_save 를 사용할 수 없습니다.');
        }

        $saved = lc_campaign_save(array(
            'mtId'               => $mt_id,
            'name'               => (string) $def['title'],
            'category'           => (string) $def['category'],
            'type'               => 'cpa',
            'price'              => (int) $def['price'],
            'advertiserPrice'    => (int) $def['price'],
            'approvalRate'       => (string) $def['approval_rate'],
            'avgTime'            => (string) $def['avg_time'],
            'allowedChannels'    => (string) $def['allowed_channels'],
            'forbiddenChannels'  => (string) $def['forbidden_channels'],
            'description'        => (string) $def['description'],
            'landingUrl'         => $landing,
            'trackingBaseUrl'    => $tracking_base,
            'badge'              => (string) $def['badge'],
            'recommended'        => !empty($def['recommended']),
            'statusCode'         => (string) $def['status'],
        ), 0);

        if (empty($saved['ok']) || empty($saved['campaign']['id'])) {
            return array(
                'ok'      => false,
                'message' => isset($saved['message']) ? (string) $saved['message'] : '캠페인 생성에 실패했습니다.',
            );
        }

        $cp_id = (int) $saved['campaign']['id'];
        lc_sql_query(" UPDATE `{$table}` SET
            cp_code = '{$code_esc}',
            cp_landing_url = '" . lc_sql_escape($landing) . "',
            cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "'
            WHERE cp_id = '{$cp_id}' ", false);

        return array(
            'ok'      => true,
            'message' => '다시봄 캠페인을 생성했습니다.',
            'cpId'    => $cp_id,
            'created' => true,
        );
    }
}
