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

if (!function_exists('lc_campaign_find_dasibom')) {
    /**
     * 기존 다시봄(dasibom) 개인회생 캠페인 조회.
     *
     * @return array<string,mixed>|null
     */
    function lc_campaign_find_dasibom()
    {
        if (!lc_db_installed()) {
            return null;
        }
        $table = lc_table('campaigns');
        $code_esc = lc_sql_escape('CPA-DASIBOM');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
        if ($row) {
            return $row;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE cp_landing_url LIKE '%/merchant/dasibom/%'
            ORDER BY cp_id ASC LIMIT 1 ");
        if ($row) {
            return $row;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE cp_name LIKE '%다시봄%' OR cp_name LIKE '%개인회생 개인파산%'
            ORDER BY cp_id ASC LIMIT 1 ");
        return $row ?: null;
    }
}

if (!function_exists('lc_campaign_ensure_dasibom')) {
    /**
     * 다시봄 CPA 상품을 생성/갱신한다. 다른 캠페인은 종료하지 않음.
     * 기존 단가·상품명은 유지하고, 랜딩/트래킹/채널/설명 등 계약 표기 필드를 채운다.
     *
     * @param array{advertiser_mb_id?:string,mt_id?:int,keep_cp_id?:int,preserve_price?:bool,preserve_title?:bool} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool,landingUrl?:string}
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
        $preserve_price = !array_key_exists('preserve_price', $options) || !empty($options['preserve_price']);
        $preserve_title = !array_key_exists('preserve_title', $options) || !empty($options['preserve_title']);

        $mt_id = isset($options['mt_id']) ? (int) $options['mt_id'] : 0;
        if ($mt_id <= 0) {
            $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : '';
            if ($advertiser_mb !== '' && function_exists('lc_get_merchant_by_mb_id')) {
                $merchant = lc_get_merchant_by_mb_id($advertiser_mb);
                $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
            }
        }

        $keep = null;
        $keep_cp_id = isset($options['keep_cp_id']) ? (int) $options['keep_cp_id'] : 0;
        if ($keep_cp_id > 0) {
            $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_id = '{$keep_cp_id}' LIMIT 1 ");
        }
        if (!$keep) {
            $keep = lc_campaign_find_dasibom();
        }

        $code_esc = lc_sql_escape((string) $def['code']);

        if ($keep) {
            $cp_id = (int) $keep['cp_id'];
            $next_mt = $mt_id > 0 ? $mt_id : (int) $keep['mt_id'];
            $next_price = $preserve_price && (int) ($keep['cp_price'] ?? 0) > 0
                ? (int) $keep['cp_price']
                : (int) $def['price'];
            $next_title = $preserve_title && trim((string) ($keep['cp_name'] ?? '')) !== ''
                ? (string) $keep['cp_name']
                : (string) $def['title'];
            $next_badge = trim((string) ($keep['cp_badge'] ?? '')) !== ''
                ? (string) $keep['cp_badge']
                : (string) $def['badge'];
            $next_recommended = isset($keep['cp_recommended'])
                ? (int) $keep['cp_recommended']
                : (!empty($def['recommended']) ? 1 : 0);
            // 기존 코드(CPA-00011 등)가 있으면 유지 — 파트너 링크/계약 참조 안정성
            $next_code = trim((string) ($keep['cp_code'] ?? ''));
            if ($next_code === '') {
                $next_code = (string) $def['code'];
            }

            lc_sql_query(" UPDATE `{$table}` SET
                mt_id = '{$next_mt}',
                cp_code = '" . lc_sql_escape($next_code) . "',
                cp_name = '" . lc_sql_escape($next_title) . "',
                cp_category = '" . lc_sql_escape((string) $def['category']) . "',
                cp_type = 'cpa',
                cp_price = '{$next_price}',
                cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "',
                cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "',
                cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "',
                cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "',
                cp_description = '" . lc_sql_escape((string) $def['description']) . "',
                cp_landing_url = '" . lc_sql_escape($landing) . "',
                cp_tracking_base_url = '" . lc_sql_escape($tracking_base) . "',
                cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "',
                cp_badge = '" . lc_sql_escape($next_badge) . "',
                cp_recommended = '{$next_recommended}',
                cp_updated_at = NOW()
                WHERE cp_id = '{$cp_id}' ", false);

            return array(
                'ok'         => true,
                'message'    => '다시봄 캠페인을 갱신했습니다.',
                'cpId'       => $cp_id,
                'created'    => false,
                'landingUrl' => $landing,
                'price'      => $next_price,
                'title'      => $next_title,
                'code'       => $next_code,
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
            'ok'         => true,
            'message'    => '다시봄 캠페인을 생성했습니다.',
            'cpId'       => $cp_id,
            'created'    => true,
            'landingUrl' => $landing,
            'price'      => (int) $def['price'],
            'title'      => (string) $def['title'],
            'code'       => (string) $def['code'],
        );
    }
}

if (!function_exists('lc_campaign_apply_personal_rehab_pair')) {
    /**
     * 온오프CPA용: 기존 개인회생 CPA 2개(banktupt + dasibom) 랜딩·계약 표기 일괄 적용.
     * 다른 캠페인은 종료하지 않으며, 등록된 단가/상품명은 유지한다.
     *
     * @param array{banktupt_cp_id?:int,dasibom_cp_id?:int} $options
     * @return array{ok:bool,message:string,banktupt?:array,dasibom?:array}
     */
    function lc_campaign_apply_personal_rehab_pair(array $options = array())
    {
        $banktupt_opts = array('preserve_price' => true, 'preserve_title' => true);
        $dasibom_opts = array('preserve_price' => true, 'preserve_title' => true);
        if (!empty($options['banktupt_cp_id'])) {
            $banktupt_opts['keep_cp_id'] = (int) $options['banktupt_cp_id'];
        }
        if (!empty($options['dasibom_cp_id'])) {
            $dasibom_opts['keep_cp_id'] = (int) $options['dasibom_cp_id'];
        }

        $banktupt = function_exists('lc_campaign_ensure_banktupt')
            ? lc_campaign_ensure_banktupt($banktupt_opts)
            : array('ok' => false, 'message' => 'lc_campaign_ensure_banktupt 없음');
        $dasibom = function_exists('lc_campaign_ensure_dasibom')
            ? lc_campaign_ensure_dasibom($dasibom_opts)
            : array('ok' => false, 'message' => 'lc_campaign_ensure_dasibom 없음');

        $ok = !empty($banktupt['ok']) && !empty($dasibom['ok']);
        return array(
            'ok'       => $ok,
            'message'  => $ok
                ? '개인회생 CPA 2개 랜딩·계약 표기를 적용했습니다.'
                : '일부 캠페인 적용에 실패했습니다.',
            'banktupt' => $banktupt,
            'dasibom'  => $dasibom,
        );
    }
}
