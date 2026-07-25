<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_campaign_status_label')) {
    function lc_campaign_status_label($status)
    {
        $labels = array(
            LC_STATUS_DRAFT  => '준비중',
            LC_STATUS_ACTIVE => '진행중',
            'paused'         => '일시중지',
            'ended'          => '종료',
            'closing'        => '마감임박',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_campaign_merchant_price_expr')) {
    function lc_campaign_merchant_price_expr($alias = 'c')
    {
        $alias = preg_replace('/[^a-z_]/', '', (string) $alias);
        if ($alias === '') {
            $alias = 'c';
        }

        return "IF({$alias}.cp_merchant_price > 0, {$alias}.cp_merchant_price, {$alias}.cp_price)";
    }
}

if (!function_exists('lc_campaign_resolve_partner_price')) {
    function lc_campaign_resolve_partner_price(array $row)
    {
        return (int) ($row['cp_price'] ?? 0);
    }
}

if (!function_exists('lc_campaign_resolve_merchant_price')) {
    function lc_campaign_resolve_merchant_price(array $row)
    {
        $merchant_price = (int) ($row['cp_merchant_price'] ?? 0);
        if ($merchant_price > 0) {
            return $merchant_price;
        }

        return (int) ($row['cp_price'] ?? 0);
    }
}

if (!function_exists('lc_campaign_to_api')) {
    function lc_campaign_to_api(array $row)
    {
        return array(
            'id'                => (int) $row['cp_id'],
            'code'              => (string) $row['cp_code'],
            'title'             => (string) $row['cp_name'],
            'category'          => (string) $row['cp_category'],
            'type'              => (string) $row['cp_type'],
            'description'       => (string) ($row['cp_description'] ?? ''),
            'price'             => lc_campaign_resolve_partner_price($row),
            'priceFormatted'    => number_format(lc_campaign_resolve_partner_price($row)),
            'approvalRate'      => (string) $row['cp_approval_rate'],
            'avgTime'           => (string) $row['cp_avg_time'],
            'allowedChannels'   => (string) $row['cp_allowed_channels'],
            'forbiddenChannels' => (string) $row['cp_forbidden_channels'],
            'status'            => lc_campaign_status_label($row['cp_status']),
            'statusCode'        => (string) $row['cp_status'],
            'badge'             => (string) $row['cp_badge'],
            'recommended'       => (bool) (int) ($row['cp_recommended'] ?? 0),
            'landingUrl'        => function_exists('lc_link_apply_tracking_host')
                ? lc_link_apply_tracking_host(
                    (string) $row['cp_landing_url'],
                    isset($row['cp_tracking_base_url']) ? (string) $row['cp_tracking_base_url'] : ''
                )
                : (string) $row['cp_landing_url'],
            'thumbnailUrl'      => function_exists('lc_campaign_thumbnail_public_url')
                ? lc_campaign_thumbnail_public_url((int) $row['cp_id'])
                : '',
        );
    }
}

if (!function_exists('lc_campaign_list_active')) {
    /**
     * @param array{category?:string,q?:string} $filters
     * @return array<int,array>
     */
    function lc_campaign_list_active(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('campaigns');
        $where = " cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' ";

        if (!empty($filters['category']) && $filters['category'] !== '전체') {
            $where .= " AND cp_category = '" . lc_sql_escape($filters['category']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (cp_name LIKE '%{$q}%' OR cp_description LIKE '%{$q}%') ";
        }

        if (!empty($filters['type'])) {
            $type = strtolower((string) $filters['type']);
            $where .= " AND cp_type = '" . lc_sql_escape($type) . "' ";
        }

        if (!empty($filters['id'])) {
            $where .= " AND cp_id = '" . (int) $filters['id'] . "' ";
        }

        if (!empty($filters['code'])) {
            $where .= " AND cp_code = '" . lc_sql_escape(trim((string) $filters['code'])) . "' ";
        }

        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY cp_recommended DESC, cp_sort ASC, cp_id DESC ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_campaign_seed_defaults')) {
    /**
     * @return array{ok:bool,inserted:int}
     */
    function lc_campaign_seed_defaults()
    {
        if (!lc_db_installed() || !function_exists('lc_sample_cpa_campaigns')) {
            return array('ok' => false, 'inserted' => 0);
        }

        $table = lc_table('campaigns');
        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` ");
        if ($count_row && (int) $count_row['cnt'] > 0) {
            return array('ok' => true, 'inserted' => 0);
        }

        $inserted = 0;
        $sort = 0;
        foreach (lc_sample_cpa_campaigns() as $item) {
            $sort++;
            $code = 'CPA-' . str_pad((string) $item['id'], 3, '0', STR_PAD_LEFT);
            $status = LC_STATUS_ACTIVE;
            if (isset($item['status']) && $item['status'] === '마감임박') {
                $status = 'closing';
            }

            $sql = " INSERT INTO `{$table}` SET
                cp_code = '" . lc_sql_escape($code) . "',
                cp_name = '" . lc_sql_escape($item['title']) . "',
                cp_category = '" . lc_sql_escape($item['category']) . "',
                cp_type = 'cpa',
                cp_price = '" . (int) $item['price'] . "',
                cp_approval_rate = '" . lc_sql_escape($item['approval_rate']) . "',
                cp_avg_time = '" . lc_sql_escape($item['avg_time']) . "',
                cp_allowed_channels = '" . lc_sql_escape($item['allowed_channels']) . "',
                cp_forbidden_channels = '" . lc_sql_escape($item['forbidden_channels']) . "',
                cp_description = '" . lc_sql_escape($item['title'] . ' 캠페인') . "',
                cp_status = '" . lc_sql_escape($status) . "',
                cp_badge = '" . lc_sql_escape($item['badge'] ?? '') . "',
                cp_recommended = '" . (!empty($item['recommended']) ? 1 : 0) . "',
                cp_sort = '{$sort}',
                cp_created_at = NOW(),
                cp_updated_at = NOW() ";
            lc_sql_query($sql, false);
            $inserted++;
        }

        return array('ok' => true, 'inserted' => $inserted);
    }
}

if (!function_exists('lc_campaign_list_for_api')) {
    function lc_campaign_list_for_api(array $filters = array())
    {
        $type = !empty($filters['type']) ? strtolower((string) $filters['type']) : '';

        if ($type === 'cps' && function_exists('lc_campaign_cps_linkprice_for_api')) {
            if (lc_db_table_exists(lc_table('lp_merchants'))) {
                return lc_campaign_cps_linkprice_for_api($filters);
            }
            $lp_items = lc_campaign_cps_linkprice_for_api($filters);
            if (!empty($lp_items)) {
                return $lp_items;
            }
        }

        if (lc_db_installed()) {
            $rows = lc_campaign_list_active($filters);

            if ($type === 'cps' && count($rows) === 0 && function_exists('lc_sample_cps_items')) {
                return lc_campaign_cps_sample_for_api($filters);
            }

            $published = function_exists('lc_campaign_promo_guide_published_cp_id_set')
                ? lc_campaign_promo_guide_published_cp_id_set(array_column($rows, 'cp_id'))
                : array();

            $items = array();
            foreach ($rows as $row) {
                $api = lc_campaign_to_api($row);
                $api['hasPublishedGuide'] = !empty($published[(int) ($row['cp_id'] ?? 0)]);
                $items[] = $api;
            }

            return $items;
        }

        if ($type === 'cps' && function_exists('lc_sample_cps_items')) {
            return lc_campaign_cps_sample_for_api($filters);
        }

        if (!function_exists('lc_sample_cpa_campaigns')) {
            return array();
        }

        $items = array();
        foreach (lc_sample_cpa_campaigns() as $item) {
            if (!empty($filters['category']) && $filters['category'] !== '전체' && $item['category'] !== $filters['category']) {
                continue;
            }
            if (!empty($filters['q'])) {
                $q = mb_strtolower((string) $filters['q'], 'UTF-8');
                $hay = mb_strtolower($item['title'] . ' ' . ($item['category'] ?? ''), 'UTF-8');
                if (mb_strpos($hay, $q, 0, 'UTF-8') === false) {
                    continue;
                }
            }

            $items[] = array(
                'id'                => (int) $item['id'],
                'code'              => 'CPA-' . str_pad((string) $item['id'], 3, '0', STR_PAD_LEFT),
                'title'             => (string) $item['title'],
                'category'          => (string) $item['category'],
                'type'              => 'cpa',
                'description'       => (string) $item['title'] . ' 캠페인',
                'price'             => (int) $item['price'],
                'priceFormatted'    => number_format((int) $item['price']),
                'approvalRate'      => (string) $item['approval_rate'],
                'avgTime'           => (string) $item['avg_time'],
                'allowedChannels'   => (string) $item['allowed_channels'],
                'forbiddenChannels' => (string) $item['forbidden_channels'],
                'status'            => (string) $item['status'],
                'statusCode'        => LC_STATUS_ACTIVE,
                'badge'             => (string) ($item['badge'] ?? ''),
                'recommended'       => !empty($item['recommended']),
                'landingUrl'        => '',
            );
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_cps_linkprice_for_api')) {
    /**
     * LinkPrice에서 동기화된 CPS 광고주를 공개 CPS 상품 카드 형식으로 변환합니다.
     *
     * @return array<int,array<string,mixed>>
     */
    function lc_campaign_cps_linkprice_for_api(array $filters = array())
    {
        if (!function_exists('lc_lp_merchants_list') || !lc_db_table_exists(lc_table('lp_merchants'))) {
            return array();
        }

        $id_filter = (int) ($filters['id'] ?? 0);
        $code_filter = trim((string) ($filters['code'] ?? ''));

        if ($id_filter > 0 || $code_filter !== '') {
            $row = null;
            if ($id_filter > 0 && function_exists('lc_lp_repo_get_merchant')) {
                $row = lc_lp_repo_get_merchant($id_filter);
            }
            if (!is_array($row) && $code_filter !== '' && function_exists('lc_lp_repo_get_merchant_by_code')) {
                $row = lc_lp_repo_get_merchant_by_code($code_filter);
            }
            if (!is_array($row) || !lc_lp_merchant_public_listable($row)) {
                return array();
            }
            $mapped = lc_campaign_cps_linkprice_map_row($row);
            return $mapped ? array($mapped) : array();
        }

        $list = lc_lp_merchants_list(array(
            'partner_visible' => true,
            'q'               => trim((string) ($filters['q'] ?? '')),
            'limit'           => 300,
        ));

        $category_filter = trim((string) ($filters['category'] ?? ''));
        $items = array();
        foreach (($list['items'] ?? array()) as $row) {
            if (!lc_lp_merchant_public_listable($row)) {
                continue;
            }

            $category = trim((string) ($row['category_name'] ?? ''));
            if ($category === '') {
                $category = '기타';
            }
            if ($category_filter !== '' && $category_filter !== '전체' && $category !== $category_filter) {
                continue;
            }

            $mapped = lc_campaign_cps_linkprice_map_row($row);
            if ($mapped) {
                $items[] = $mapped;
            }
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_cps_linkprice_map_row')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_campaign_cps_linkprice_map_row(array $row)
    {
        $title = trim((string) ($row['campaign_alias'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($row['merchant_name'] ?? ''));
        }
        if ($title === '') {
            return null;
        }

        $category = trim((string) ($row['category_name'] ?? ''));
        if ($category === '') {
            $category = '기타';
        }

        $commission = function_exists('lc_lp_merchant_partner_display_commission')
            ? lc_lp_merchant_partner_display_commission($row)
            : (function_exists('lc_lp_format_commission_rate_display')
                ? lc_lp_format_commission_rate_display(trim((string) ($row['commission_mobile'] ?? $row['commission_pc'] ?? '')))
                : trim((string) ($row['commission_pc'] ?? $row['commission_mobile'] ?? '')));
        $return_day = (int) ($row['return_day'] ?? 0);
        $notice = trim((string) ($row['partner_notice'] ?? ''));
        if ($notice === '') {
            $notice = trim((string) ($row['notice'] ?? ''));
        }
        $settlement = trim((string) ($row['commission_payment_standard'] ?? ''));
        if (function_exists('lc_lp_public_brand_copy')) {
            $notice = lc_lp_public_brand_copy($notice);
            $settlement = lc_lp_public_brand_copy($settlement);
        }
        $description = $notice !== '' ? $notice : ($settlement !== '' ? $settlement : $title . ' 구매 연동 CPS 캠페인');

        $deny = trim((string) ($row['deny_ad'] ?? ''));
        $deny_product = trim((string) ($row['deny_product'] ?? ''));
        if (function_exists('lc_lp_public_brand_copy')) {
            $deny = lc_lp_public_brand_copy($deny);
            $deny_product = lc_lp_public_brand_copy($deny_product);
        }
        $forbidden = trim(implode(', ', array_filter(array($deny, $deny_product))));
        $code = (string) ($row['merchant_code'] ?? '');
        $when_trans = (string) ($row['when_trans'] ?? '');
        if (function_exists('lc_lp_public_brand_copy')) {
            $when_trans = lc_lp_public_brand_copy($when_trans);
        }

        return array(
            'id'                => (int) ($row['lpm_id'] ?? 0),
            'code'              => $code,
            'merchantCode'      => $code,
            'lpmId'             => (int) ($row['lpm_id'] ?? 0),
            'title'             => $title,
            'category'          => $category,
            'type'              => 'cps',
            'campaignType'      => 'cps',
            'description'       => $description,
            'price'             => 0,
            'priceFormatted'    => $commission,
            'approvalRate'      => $commission,
            'avgTime'           => $return_day > 0 ? $return_day . '일' : '-',
            'returnDay'         => $return_day,
            'allowedChannels'   => '블로그, SNS, 유튜브, 커뮤니티',
            'forbiddenChannels' => $forbidden !== '' ? $forbidden : '스팸, 브랜드 사칭, 허위광고',
            'denyAd'            => $deny,
            'denyProduct'       => $deny_product,
            'settlement'        => $settlement,
            'whenTrans'         => $when_trans,
            'deeplinkYn'        => (string) ($row['deeplink_yn'] ?? 'N'),
            'status'            => '진행중',
            'statusCode'        => LC_STATUS_ACTIVE,
            'badge'             => !empty($row['is_recommended']) ? '추천' : '',
            'recommended'       => !empty($row['is_recommended']),
            'landingUrl'        => (string) ($row['merchant_url'] ?? ''),
            'thumbnailUrl'      => lc_lp_merchant_public_logo($row),
        );
    }
}

if (!function_exists('lc_campaign_cps_linkprice_categories')) {
    /**
     * @return string[]
     */
    function lc_campaign_cps_linkprice_categories()
    {
        $defaults = array('전체', '여행/티켓', '종합쇼핑몰', '건강', '패션', '뷰티', '생활/인테리어', '기타');
        if (!lc_db_table_exists(lc_table('lp_merchants'))) {
            return $defaults;
        }

        $table = lc_table('lp_merchants');
        $rows = lc_sql_query(" SELECT DISTINCT category_name FROM `{$table}`
            WHERE subscript = 'APR'
              AND visible = 1
              AND sync_active = 1
              AND click_url <> ''
            ORDER BY category_name ASC ", false);

        $found = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $cat = trim((string) ($row['category_name'] ?? ''));
                if ($cat !== '' && !in_array($cat, $found, true)) {
                    $found[] = $cat;
                }
            }
        }

        if (count($found) === 0) {
            return $defaults;
        }

        $priority = array('여행/티켓', '종합쇼핑몰', '건강', '패션');
        $categories = array('전체');
        foreach ($priority as $cat) {
            if (in_array($cat, $found, true)) {
                $categories[] = $cat;
            }
        }
        foreach ($found as $cat) {
            if (!in_array($cat, $categories, true)) {
                $categories[] = $cat;
            }
        }

        return $categories;
    }
}

if (!function_exists('lc_campaign_cps_partner_id')) {
    function lc_campaign_cps_partner_id($lpm_id)
    {
        return 900000000 + (int) $lpm_id;
    }
}

if (!function_exists('lc_campaign_cps_partner_lpm_id')) {
    function lc_campaign_cps_partner_lpm_id($campaign_id)
    {
        $id = (int) $campaign_id;
        if ($id < 900000000) {
            return 0;
        }

        return $id - 900000000;
    }
}

if (!function_exists('lc_campaign_cps_partner_for_api')) {
    /**
     * 파트너 검색용 CPS 광고주 → PartnerCampaign 형식
     *
     * @return array<int,array<string,mixed>>
     */
    function lc_campaign_cps_partner_for_api($pt_id, array $filters = array())
    {
        if (!function_exists('lc_lp_partner_list_merchants')) {
            return array();
        }

        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return array();
        }

        $list = lc_lp_partner_list_merchants($pt_id, array(
            'q'     => trim((string) ($filters['q'] ?? '')),
            'limit' => 300,
        ));

        $category_filter = trim((string) ($filters['category'] ?? ''));
        $items = array();
        foreach (($list['items'] ?? array()) as $row) {
            $category = trim((string) ($row['categoryName'] ?? ''));
            if ($category === '') {
                $category = '쇼핑몰';
            }
            if ($category_filter !== '' && $category_filter !== '전체' && $category !== $category_filter) {
                continue;
            }

            $commission = trim((string) ($row['commissionMobile'] ?? $row['commissionPc'] ?? ''));
            $notice = trim((string) ($row['notice'] ?? ''));
            $deny = trim((string) ($row['denyAd'] ?? ''));
            $deny_product = trim((string) ($row['denyProduct'] ?? ''));
            $forbidden = trim(implode(', ', array_filter(array($deny, $deny_product))));

            $items[] = array(
                'id'                => lc_campaign_cps_partner_id((int) ($row['lpmId'] ?? 0)),
                'code'              => (string) ($row['merchantCode'] ?? ''),
                'title'             => (string) ($row['merchantName'] ?? ''),
                'category'          => $category,
                'type'              => 'cps',
                'campaignType'      => 'cps',
                'description'       => $notice !== '' ? $notice : ((string) ($row['merchantName'] ?? '') . ' 구매 연동 CPS'),
                'price'             => 0,
                'priceFormatted'    => $commission !== '' ? $commission : '구매 연동',
                'approvalRate'      => $commission !== '' ? $commission : '-',
                'avgTime'           => '-',
                'allowedChannels'   => '블로그, SNS, 유튜브, 커뮤니티',
                'forbiddenChannels' => $forbidden !== '' ? $forbidden : '스팸, 브랜드 사칭, 허위광고',
                'status'            => '진행중',
                'statusCode'        => LC_STATUS_ACTIVE,
                'badge'             => !empty($row['isRecommended']) ? '추천' : '',
                'recommended'       => !empty($row['isRecommended']),
                'landingUrl'        => '',
                'thumbnailUrl'      => (string) ($row['merchantLogo'] ?? ''),
                'hasPublishedGuide' => false,
                'merchantCode'      => (string) ($row['merchantCode'] ?? ''),
                'promoUrl'          => (string) ($row['promoUrl'] ?? ''),
                'lpmId'             => (int) ($row['lpmId'] ?? 0),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_cps_sample_for_api')) {
    function lc_campaign_cps_sample_for_api(array $filters = array())
    {
        if (!function_exists('lc_sample_cps_items')) {
            return array();
        }

        $category_map = array(
            '건강' => '건강',
            '뷰티' => '뷰티',
            '생활' => '생활',
        );

        $items = array();
        foreach (lc_sample_cps_items() as $item) {
            $title = (string) ($item['brand'] ?? '');
            $category = '쇼핑몰';
            foreach ($category_map as $needle => $cat) {
                if (mb_strpos($title, $needle, 0, 'UTF-8') !== false) {
                    $category = $cat;
                    break;
                }
            }

            if (!empty($filters['category']) && $filters['category'] !== '전체' && $category !== $filters['category']) {
                continue;
            }
            if (!empty($filters['q'])) {
                $q = mb_strtolower((string) $filters['q'], 'UTF-8');
                $hay = mb_strtolower($title . ' ' . $category, 'UTF-8');
                if (mb_strpos($hay, $q, 0, 'UTF-8') === false) {
                    continue;
                }
            }

            $items[] = array(
                'id'                => (int) $item['id'],
                'code'              => 'CPS-' . str_pad((string) $item['id'], 3, '0', STR_PAD_LEFT),
                'title'             => $title,
                'category'          => $category,
                'type'              => 'cps',
                'description'       => $title . ' 구매 연동 CPS 캠페인',
                'price'             => 0,
                'priceFormatted'    => (string) ($item['rate'] ?? ''),
                'approvalRate'      => (string) ($item['rate'] ?? ''),
                'avgTime'           => (string) ($item['cookie'] ?? ''),
                'allowedChannels'   => '블로그, SNS, 유튜브, 커뮤니티',
                'forbiddenChannels' => '스팸, 브랜드 사칭, 허위광고',
                'status'            => (string) ($item['badge'] ?? '진행중'),
                'statusCode'        => LC_STATUS_ACTIVE,
                'badge'             => (string) ($item['badge'] ?? ''),
                'recommended'       => ($item['badge'] ?? '') === '인기',
                'landingUrl'        => '',
            );
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_admin_status_ui')) {
    function lc_campaign_admin_status_ui($status, $low_balance = false)
    {
        if ($low_balance && $status === LC_STATUS_ACTIVE) {
            return '광고비부족';
        }

        $labels = array(
            LC_STATUS_ACTIVE => '운영중',
            'paused'         => '일시중지',
            'ended'          => '종료',
            LC_STATUS_DRAFT  => '검수중',
            'closing'        => '마감임박',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_campaign_merchant_status_ui')) {
    function lc_campaign_merchant_status_ui($status)
    {
        $labels = array(
            LC_STATUS_ACTIVE => '진행중',
            'paused'         => '일시중지',
            'ended'          => '종료',
            LC_STATUS_DRAFT  => '대기중',
            'closing'        => '마감임박',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_campaign_get_by_id')) {
    function lc_campaign_get_by_id($cp_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cp_id = (int) $cp_id;
        $table = lc_table('campaigns');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_campaign_generate_code')) {
    function lc_campaign_generate_code()
    {
        if (!lc_db_installed()) {
            return 'CPA-00001';
        }

        $table = lc_table('campaigns');
        $row = lc_sql_fetch(" SELECT cp_id FROM `{$table}` ORDER BY cp_id DESC LIMIT 1 ");
        $next = $row ? (int) $row['cp_id'] + 1 : 1;

        return 'CPA-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_campaign_list_admin')) {
    /**
     * @param array{status?:string,category?:string,q?:string} $filters
     * @return array<int,array>
     */
    function lc_campaign_list_admin(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $cp_table = lc_table('campaigns');
        $mt_table = lc_table('merchants');
        $cv_table = lc_table('conversions');
        $where = '1=1';

        if (!empty($filters['status'])) {
            $status = (string) $filters['status'];
            if ($status === 'low_balance') {
                $merchant_price_expr = lc_campaign_merchant_price_expr('c');
                $where .= " AND c.cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' AND m.mt_balance < {$merchant_price_expr} ";
            } elseif ($status === LC_STATUS_ACTIVE) {
                $merchant_price_expr = lc_campaign_merchant_price_expr('c');
                $where .= " AND c.cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' AND m.mt_balance >= {$merchant_price_expr} ";
            } else {
                $where .= " AND c.cp_status = '" . lc_sql_escape($status) . "' ";
            }
        }

        if (!empty($filters['category']) && $filters['category'] !== '전체') {
            $where .= " AND c.cp_category = '" . lc_sql_escape($filters['category']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (c.cp_name LIKE '%{$q}%' OR c.cp_code LIKE '%{$q}%' OR m.mt_company LIKE '%{$q}%') ";
        }

        $sql = " SELECT c.*, m.mt_company, m.mt_code, m.mt_balance,
            COUNT(cv.cv_id) AS total_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS canceled_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS spend
            FROM `{$cp_table}` c
            LEFT JOIN `{$mt_table}` m ON m.mt_id = c.mt_id
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
            WHERE {$where}
            GROUP BY c.cp_id
            ORDER BY c.cp_id DESC ";

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

if (!function_exists('lc_campaign_admin_summary')) {
    function lc_campaign_admin_summary()
    {
        if (!lc_db_installed()) {
            return array(
                'total'       => 0,
                'active'      => 0,
                'paused'      => 0,
                'lowBalance'  => 0,
                'avgPrice'    => 0,
                'avgApproval' => 0,
            );
        }

        $cp_table = lc_table('campaigns');
        $mt_table = lc_table('merchants');
        $cv_table = lc_table('conversions');

        $merchant_price_expr = lc_campaign_merchant_price_expr('c');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN c.cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' AND m.mt_balance >= {$merchant_price_expr} THEN 1 ELSE 0 END) AS active_cnt,
            SUM(CASE WHEN c.cp_status = 'paused' THEN 1 ELSE 0 END) AS paused_cnt,
            SUM(CASE WHEN c.cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' AND m.mt_balance < {$merchant_price_expr} THEN 1 ELSE 0 END) AS low_balance_cnt,
            AVG(c.cp_price) AS avg_price
            FROM `{$cp_table}` c
            LEFT JOIN `{$mt_table}` m ON m.mt_id = c.mt_id ");

        $rate_row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt
            FROM `{$cv_table}` ");
        $total_cv = (int) ($rate_row['total_cnt'] ?? 0);
        $approved_cv = (int) ($rate_row['approved_cnt'] ?? 0);

        return array(
            'total'       => (int) ($row['total_cnt'] ?? 0),
            'active'      => (int) ($row['active_cnt'] ?? 0),
            'paused'      => (int) ($row['paused_cnt'] ?? 0),
            'lowBalance'  => (int) ($row['low_balance_cnt'] ?? 0),
            'avgPrice'    => (int) round((float) ($row['avg_price'] ?? 0)),
            'avgApproval' => $total_cv > 0 ? round(($approved_cv / $total_cv) * 100, 1) : 0,
        );
    }
}

if (!function_exists('lc_campaign_to_admin_api')) {
    function lc_campaign_to_admin_api(array $row)
    {
        $total = (int) ($row['total_db'] ?? 0);
        $approved = (int) ($row['approved_db'] ?? 0);
        $canceled = (int) ($row['canceled_db'] ?? 0);
        $rate = $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '-';
        $cancel_rate = $total > 0 ? round(($canceled / $total) * 100, 1) . '%' : '-';
        $partner_price = lc_campaign_resolve_partner_price($row);
        $merchant_price = lc_campaign_resolve_merchant_price($row);
        $balance = (int) ($row['mt_balance'] ?? 0);
        $low_balance = $row['cp_status'] === LC_STATUS_ACTIVE && $balance < $merchant_price;

        return array(
            'id'              => (int) $row['cp_id'],
            'code'            => (string) $row['cp_code'],
            'name'            => (string) $row['cp_name'],
            'advertiser'      => (string) ($row['mt_company'] ?? ''),
            'mtId'            => (int) ($row['mt_id'] ?? 0),
            'category'        => (string) $row['cp_category'],
            'type'            => strtoupper((string) $row['cp_type']),
            'partnerPrice'    => $partner_price,
            'advertiserPrice' => $merchant_price,
            'margin'          => max(0, $merchant_price - $partner_price),
            'totalDb'         => $total,
            'approvedDb'      => $approved,
            'canceledDb'      => $canceled,
            'spend'           => (int) ($row['spend'] ?? 0),
            'rate'            => $rate,
            'cancelRate'      => $cancel_rate,
            'status'          => lc_campaign_admin_status_ui($row['cp_status'], $low_balance),
            'statusCode'      => (string) $row['cp_status'],
            'lowBalance'      => $low_balance,
            'description'     => (string) ($row['cp_description'] ?? ''),
            'approvalRate'    => (string) $row['cp_approval_rate'],
            'avgTime'         => (string) $row['cp_avg_time'],
            'allowedChannels' => (string) $row['cp_allowed_channels'],
            'forbiddenChannels' => (string) $row['cp_forbidden_channels'],
            'landingUrl'      => (string) $row['cp_landing_url'],
            'trackingBaseUrl' => (string) ($row['cp_tracking_base_url'] ?? ''),
            'badge'           => (string) $row['cp_badge'],
            'recommended'     => (bool) (int) ($row['cp_recommended'] ?? 0),
            'thumbnailUrl'    => function_exists('lc_campaign_thumbnail_admin_url')
                ? (lc_campaign_thumbnail_relative_from_row($row) !== '' ? lc_campaign_thumbnail_admin_url((int) $row['cp_id']) : '')
                : '',
        );
    }
}

if (!function_exists('lc_campaign_admin_for_api')) {
    function lc_campaign_admin_for_api(array $filters = array())
    {
        $rows = lc_campaign_list_admin($filters);
        if (count($rows) === 0) {
            return array();
        }

        $cp_ids = array();
        foreach ($rows as $row) {
            $cp_ids[] = (int) ($row['cp_id'] ?? 0);
        }

        $summaries = function_exists('lc_campaign_promo_guide_summaries_for_cp_ids')
            ? lc_campaign_promo_guide_summaries_for_cp_ids($cp_ids)
            : array();

        $items = array();
        foreach ($rows as $row) {
            $api = lc_campaign_to_admin_api($row);
            $cp_id = (int) ($row['cp_id'] ?? 0);
            if (function_exists('lc_campaign_promo_guide_empty_summary')) {
                $api['promoGuide'] = isset($summaries[$cp_id]) ? $summaries[$cp_id] : lc_campaign_promo_guide_empty_summary();
            }
            $items[] = $api;
        }

        return $items;
    }
}

if (!function_exists('lc_campaign_list_for_merchant')) {
    function lc_campaign_list_for_merchant($mt_id, array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $cp_table = lc_table('campaigns');
        $cv_table = lc_table('conversions');
        $pg_table = function_exists('lc_campaign_promo_guide_table')
            ? lc_campaign_promo_guide_table()
            : lc_table('campaign_promo_guides');
        $pg_join = '';
        $pg_select = ", '' AS guide_status, 0 AS guide_exists";
        if ($pg_table && function_exists('lc_db_table_exists') && lc_db_table_exists($pg_table)) {
            $pg_join = " LEFT JOIN `{$pg_table}` g ON g.cpg_cp_id = c.cp_id ";
            $pg_select = ", IFNULL(MAX(g.cpg_status), '') AS guide_status, MAX(CASE WHEN g.cpg_id IS NULL THEN 0 ELSE 1 END) AS guide_exists";
        }
        $where = " c.mt_id = '{$mt_id}' ";

        if (!empty($filters['status'])) {
            $where .= " AND c.cp_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        $sql = " SELECT c.*,
            COUNT(cv.cv_id) AS total_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS spend
            {$pg_select}
            FROM `{$cp_table}` c
            {$pg_join}
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
            WHERE {$where}
            GROUP BY c.cp_id
            ORDER BY c.cp_id DESC ";

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

if (!function_exists('lc_campaign_merchant_to_api')) {
    function lc_campaign_merchant_to_api(array $row)
    {
        $guide_status = isset($row['guide_status']) ? trim((string) $row['guide_status']) : '';
        $guide_exists = !empty($row['guide_exists']);
        $payload = array(
            'id'       => (int) $row['cp_id'],
            'code'     => (string) $row['cp_code'],
            'name'     => (string) $row['cp_name'],
            'type'     => strtoupper((string) $row['cp_type']),
            'status'   => lc_campaign_merchant_status_ui($row['cp_status']),
            'statusCode' => (string) $row['cp_status'],
            'cpa'      => lc_campaign_resolve_merchant_price($row),
            'budget'   => 0,
            'spend'    => (int) ($row['spend'] ?? 0),
            'dbCount'  => (int) ($row['total_db'] ?? 0),
            'category' => (string) $row['cp_category'],
            'guideExists' => $guide_exists,
            'guideStatus' => $guide_status,
        );
        if (function_exists('lc_campaign_promo_guide_status_label') && $guide_status !== '') {
            $payload['guideStatusLabel'] = lc_campaign_promo_guide_status_label($guide_status);
        } else {
            $payload['guideStatusLabel'] = $guide_exists ? $guide_status : '미작성';
        }

        $mt_id = (int) ($row['mt_id'] ?? 0);
        $signed = $mt_id > 0 && function_exists('lc_merchant_contract_is_signed') && lc_merchant_contract_is_signed($mt_id);
        $payload['contractViewable'] = $signed;
        $payload['contractStatusLabel'] = $signed ? '체결완료' : '미체결';

        return $payload;
    }
}

if (!function_exists('lc_campaign_merchant_summary')) {
    function lc_campaign_merchant_summary($mt_id)
    {
        $rows = lc_campaign_list_for_merchant($mt_id);
        $summary = array(
            'total'   => count($rows),
            'active'  => 0,
            'pending' => 0,
            'ended'   => 0,
        );

        foreach ($rows as $row) {
            $status = (string) $row['cp_status'];
            if ($status === LC_STATUS_ACTIVE || $status === 'closing') {
                $summary['active']++;
            } elseif ($status === LC_STATUS_DRAFT) {
                $summary['pending']++;
            } elseif ($status === 'ended') {
                $summary['ended']++;
            }
        }

        return $summary;
    }
}

if (!function_exists('lc_campaign_require_approved_contract')) {
    /**
     * 광고 등록/활성화는 계약 승인 완료 광고주만 가능
     *
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_require_approved_contract($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => '광고주를 선택해주세요.');
        }

        if (!function_exists('lc_merchant_contract_is_fully_signed')) {
            return array('ok' => true, 'message' => '');
        }

        if (!lc_merchant_contract_is_fully_signed($mt_id)) {
            return array(
                'ok'      => false,
                'message' => '계약서 관리자 승인이 완료된 광고주만 광고를 등록하거나 활성화할 수 있습니다.',
            );
        }

        return array('ok' => true, 'message' => '');
    }
}

if (!function_exists('lc_campaign_save')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string,campaign?:array|null}
     */
    function lc_campaign_save(array $payload, $cp_id = 0)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) $cp_id;
        $mt_id = isset($payload['mtId']) ? (int) $payload['mtId'] : 0;
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $category = isset($payload['category']) ? trim((string) $payload['category']) : '';
        $type = isset($payload['type']) ? strtolower(trim((string) $payload['type'])) : 'cpa';
        $partner_price = isset($payload['partnerPrice']) ? (int) $payload['partnerPrice'] : (isset($payload['price']) ? (int) $payload['price'] : 0);
        $merchant_price = isset($payload['advertiserPrice']) ? (int) $payload['advertiserPrice'] : (isset($payload['merchantPrice']) ? (int) $payload['merchantPrice'] : 0);

        if ($name === '') {
            return array('ok' => false, 'message' => '광고상품명을 입력해주세요.');
        }

        if ($cp_id <= 0 && $mt_id <= 0) {
            return array('ok' => false, 'message' => '광고주를 선택해주세요.');
        }

        $target_status = isset($payload['statusCode']) ? (string) $payload['statusCode'] : '';
        $check_mt_id = $mt_id;
        if ($check_mt_id <= 0 && $cp_id > 0) {
            $existing_for_check = lc_campaign_get_by_id($cp_id);
            $check_mt_id = is_array($existing_for_check) ? (int) ($existing_for_check['mt_id'] ?? 0) : 0;
        }
        // 신규 등록 · 활성 저장 · 광고주 재배정 시 승인된 계약 필수
        $needs_approved = $cp_id <= 0 || $target_status === LC_STATUS_ACTIVE || ($cp_id > 0 && $mt_id > 0);
        if ($needs_approved && function_exists('lc_campaign_require_approved_contract')) {
            $contract_check = lc_campaign_require_approved_contract($check_mt_id);
            if (empty($contract_check['ok'])) {
                return array('ok' => false, 'message' => $contract_check['message']);
            }
        }

        if ($partner_price <= 0) {
            return array('ok' => false, 'message' => '파트너 지급 단가를 입력해주세요.');
        }

        if ($merchant_price <= 0) {
            return array('ok' => false, 'message' => '광고주 차감 단가를 입력해주세요.');
        }

        if ($merchant_price < $partner_price) {
            return array('ok' => false, 'message' => '광고주 차감 단가는 파트너 지급 단가 이상이어야 합니다.');
        }

        $table = lc_table('campaigns');
        $fields = array(
            'cp_name'               => $name,
            'cp_category'           => $category,
            'cp_type'               => $type,
            'cp_price'              => $partner_price,
            'cp_merchant_price'     => $merchant_price,
            'cp_approval_rate'      => isset($payload['approvalRate']) ? trim((string) $payload['approvalRate']) : '',
            'cp_avg_time'           => isset($payload['avgTime']) ? trim((string) $payload['avgTime']) : '',
            'cp_allowed_channels'   => isset($payload['allowedChannels']) ? trim((string) $payload['allowedChannels']) : '',
            'cp_forbidden_channels' => isset($payload['forbiddenChannels']) ? trim((string) $payload['forbiddenChannels']) : '',
            'cp_description'        => isset($payload['description']) ? trim((string) $payload['description']) : '',
            'cp_landing_url'        => isset($payload['landingUrl']) ? trim((string) $payload['landingUrl']) : '',
            'cp_tracking_base_url'  => '',
            'cp_badge'              => isset($payload['badge']) ? trim((string) $payload['badge']) : '',
            'cp_recommended'        => !empty($payload['recommended']) ? 1 : 0,
        );

        if (array_key_exists('trackingBaseUrl', $payload)) {
            $tracking_raw = trim((string) $payload['trackingBaseUrl']);
            if ($tracking_raw !== '' && function_exists('lc_settings_normalize_cpa_tracking_base_url')) {
                $norm = lc_settings_normalize_cpa_tracking_base_url($tracking_raw);
                if (empty($norm['ok'])) {
                    return array('ok' => false, 'message' => $norm['message'] !== '' ? $norm['message'] : '독립 도메인 형식이 올바르지 않습니다.');
                }
                $fields['cp_tracking_base_url'] = $norm['url'];
            } else {
                $fields['cp_tracking_base_url'] = '';
            }
        } elseif ($cp_id > 0) {
            unset($fields['cp_tracking_base_url']);
        }

        if (isset($payload['statusCode']) && $payload['statusCode'] !== '') {
            $fields['cp_status'] = (string) $payload['statusCode'];
        }

        $is_update = $cp_id > 0;

        if ($is_update) {
            $existing = lc_campaign_get_by_id($cp_id);
            if (!$existing) {
                return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
            }

            if ($mt_id > 0) {
                $fields['mt_id'] = $mt_id;
            }

            $sets = array();
            foreach ($fields as $key => $value) {
                if ($key === 'cp_recommended' || $key === 'cp_price' || $key === 'cp_merchant_price' || $key === 'mt_id') {
                    $sets[] = "`{$key}` = '" . (int) $value . "'";
                } else {
                    $sets[] = "`{$key}` = '" . lc_sql_escape((string) $value) . "'";
                }
            }
            $sets[] = 'cp_updated_at = NOW()';

            lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE cp_id = '{$cp_id}' ", false);
        } else {
            $code = lc_campaign_generate_code();
            $status = isset($fields['cp_status']) ? $fields['cp_status'] : LC_STATUS_DRAFT;

            lc_sql_query(" INSERT INTO `{$table}` SET
                mt_id = '{$mt_id}',
                cp_code = '" . lc_sql_escape($code) . "',
                cp_name = '" . lc_sql_escape($fields['cp_name']) . "',
                cp_category = '" . lc_sql_escape($fields['cp_category']) . "',
                cp_type = '" . lc_sql_escape($fields['cp_type']) . "',
                cp_price = '" . (int) $fields['cp_price'] . "',
                cp_merchant_price = '" . (int) $fields['cp_merchant_price'] . "',
                cp_approval_rate = '" . lc_sql_escape($fields['cp_approval_rate']) . "',
                cp_avg_time = '" . lc_sql_escape($fields['cp_avg_time']) . "',
                cp_allowed_channels = '" . lc_sql_escape($fields['cp_allowed_channels']) . "',
                cp_forbidden_channels = '" . lc_sql_escape($fields['cp_forbidden_channels']) . "',
                cp_description = '" . lc_sql_escape($fields['cp_description']) . "',
                cp_landing_url = '" . lc_sql_escape($fields['cp_landing_url']) . "',
                cp_tracking_base_url = '" . lc_sql_escape((string) ($fields['cp_tracking_base_url'] ?? '')) . "',
                cp_status = '" . lc_sql_escape($status) . "',
                cp_badge = '" . lc_sql_escape($fields['cp_badge']) . "',
                cp_recommended = '" . (int) $fields['cp_recommended'] . "',
                cp_created_at = NOW(),
                cp_updated_at = NOW() ", false);

            $cp_id = (int) lc_sql_insert_id();
        }

        $saved = lc_campaign_get_by_id($cp_id);
        $api_campaign = null;
        if (is_array($saved)) {
            $saved['mt_company'] = '';
            $saved['mt_code'] = '';
            $saved['mt_balance'] = 0;
            $saved['total_db'] = 0;
            $saved['approved_db'] = 0;
            $saved['canceled_db'] = 0;
            $saved['spend'] = 0;
            if ($saved['mt_id'] > 0 && function_exists('lc_get_merchant_by_id')) {
                $merchant = lc_get_merchant_by_id((int) $saved['mt_id']);
                if (is_array($merchant)) {
                    $saved['mt_company'] = $merchant['mt_company'];
                    $saved['mt_code'] = $merchant['mt_code'];
                    $saved['mt_balance'] = $merchant['mt_balance'];
                }
            }
            $api_campaign = lc_campaign_to_admin_api($saved);
        }

        return array(
            'ok'       => true,
            'message'  => $is_update ? '광고상품이 저장되었습니다.' : '광고상품이 등록되었습니다.',
            'campaign' => $api_campaign,
        );
    }
}

if (!function_exists('lc_campaign_update_status')) {
    /**
     * @return array{ok:bool,message:string,campaign?:array|null}
     */
    function lc_campaign_update_status($cp_id, $status)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $cp_id = (int) $cp_id;
        $allowed = array(LC_STATUS_ACTIVE, 'paused', 'ended', LC_STATUS_DRAFT, 'closing');
        if (!in_array($status, $allowed, true)) {
            return array('ok' => false, 'message' => '유효하지 않은 상태입니다.');
        }

        $existing = lc_campaign_get_by_id($cp_id);
        if (!$existing) {
            return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
        }

        if ($status === LC_STATUS_ACTIVE && function_exists('lc_campaign_require_approved_contract')) {
            $contract_check = lc_campaign_require_approved_contract((int) ($existing['mt_id'] ?? 0));
            if (empty($contract_check['ok'])) {
                return array('ok' => false, 'message' => $contract_check['message']);
            }
        }

        $table = lc_table('campaigns');
        lc_sql_query(" UPDATE `{$table}` SET cp_status = '" . lc_sql_escape($status) . "', cp_updated_at = NOW() WHERE cp_id = '{$cp_id}' ", false);

        $saved = lc_campaign_get_by_id($cp_id);
        $saved['mt_company'] = '';
        $saved['mt_balance'] = 0;
        if ($saved['mt_id'] > 0 && function_exists('lc_get_merchant_by_id')) {
            $merchant = lc_get_merchant_by_id((int) $saved['mt_id']);
            if (is_array($merchant)) {
                $saved['mt_company'] = $merchant['mt_company'];
                $saved['mt_balance'] = $merchant['mt_balance'];
            }
        }

        return array(
            'ok'       => true,
            'message'  => '상태가 변경되었습니다.',
            'campaign' => lc_campaign_to_admin_api($saved),
        );
    }
}

if (!function_exists('lc_campaign_delete')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_delete($cp_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
            return array('ok' => false, 'message' => '삭제 권한이 없습니다.');
        }

        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => '캠페인 ID가 필요합니다.');
        }

        $existing = lc_campaign_get_by_id($cp_id);
        if (!$existing) {
            return array('ok' => false, 'message' => '캠페인을 찾을 수 없습니다.');
        }

        $cv_table = lc_table('conversions');
        $cv_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` WHERE cp_id = '{$cp_id}' ");
        $conversion_count = is_array($cv_row) ? (int) ($cv_row['cnt'] ?? 0) : 0;
        if ($conversion_count > 0) {
            return array('ok' => false, 'message' => '접수 DB가 있는 광고상품은 삭제할 수 없습니다.');
        }

        $cl_table = lc_table('clicks');
        $lk_table = lc_table('links');
        $cs_table = lc_table('call_settings');
        $car_table = lc_table('call_requests');
        $cp_table = lc_table('campaigns');

        lc_sql_query(" DELETE FROM `{$cl_table}` WHERE cp_id = '{$cp_id}' ", false);
        lc_sql_query(" DELETE FROM `{$lk_table}` WHERE cp_id = '{$cp_id}' ", false);

        if (lc_db_table_exists($cs_table)) {
            lc_sql_query(" DELETE FROM `{$cs_table}` WHERE cp_id = '{$cp_id}' ", false);
        }
        if (lc_db_table_exists($car_table)) {
            lc_sql_query(" DELETE FROM `{$car_table}` WHERE cp_id = '{$cp_id}' ", false);
        }

        if (function_exists('lc_campaign_thumbnail_delete')) {
            lc_campaign_thumbnail_delete($cp_id);
        }

        lc_sql_query(" DELETE FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ", false);

        return array('ok' => true, 'message' => '광고상품이 삭제되었습니다.');
    }
}
