<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_seo_geo_cps_campaign_definition')) {
    /**
     * SEO GEO 강의 홍보랜딩 CPS — 고객 1명당 고정 수익 300,000원.
     *
     * @return array<string,mixed>
     */
    function lc_seo_geo_cps_campaign_definition()
    {
        return array(
            'code'               => 'CPS-SEO-GEO',
            'title'              => 'SEO GEO 강의',
            'category'           => '교육',
            'price'              => 300000,
            'merchant_price'     => 0,
            'approval_rate'      => '30만원',
            'avg_time'           => '구매확정',
            'allowed_channels'   => '블로그, 카페, SNS, 유튜브, 커뮤니티, 뉴스레터',
            'forbidden_channels' => '스팸, 브랜드 사칭, 허위광고, 부정 클릭',
            'description'        => 'SEO/GEO/AEO 실전 강의 홍보랜딩. 고객 1명 구매·이용 확정 시 파트너 수익 300,000원(고정). https://onoff.icrm.co.kr/seo-system/',
            'badge'              => '추천',
            'recommended'        => true,
            'status'             => 'active',
            'platform_service'   => 'CONTENT',
            'product_type'       => 'lifetime',
            'commission_rule_id' => 'cps_seo_geo_fixed_300k',
            'landing_url'        => 'https://onoff.icrm.co.kr/seo-system/',
            'fixed_payout'       => 300000,
        );
    }
}

if (!function_exists('lc_campaign_ensure_seo_geo_cps_core_rule')) {
    /**
     * Core CAMPAIGN 규칙: gross 보고액 × 100% = 고정 수익.
     * 결제 보고 시 amount 를 300000 으로내어 파트너 수익 30만원을 확정한다.
     *
     * @param int $cp_id
     * @return array{ok:bool,message:string}
     */
    function lc_campaign_ensure_seo_geo_cps_core_rule($cp_id)
    {
        $cp_id = (int) $cp_id;
        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => 'cp_id required');
        }
        if (!function_exists('lc_onoff_core_ensure_ready')) {
            return array('ok' => false, 'message' => 'core bridge missing');
        }
        $ready = lc_onoff_core_ensure_ready();
        if (empty($ready['tables'])) {
            return array('ok' => false, 'message' => (string) ($ready['message'] ?? 'core not ready'));
        }
        if (!class_exists('OnoffCore_CommissionRuleService', false)) {
            return array('ok' => false, 'message' => 'rule service missing');
        }

        $def = lc_seo_geo_cps_campaign_definition();
        $rules = new OnoffCore_CommissionRuleService(
            function_exists('lc_onoff_core_test_store') ? lc_onoff_core_test_store() : null
        );
        $upsert = $rules->upsertRule(array(
            'rule_id'         => (string) $def['commission_rule_id'],
            'scope'           => 'CAMPAIGN',
            'campaign_id'     => 'cp_' . $cp_id,
            'commission_rate' => '1.0000',
            'rule_type'       => 'LIFETIME',
            'priority'        => 50,
            'status'          => 'ACTIVE',
            'metadata_json'   => json_encode(array(
                'fixed_payout' => (int) $def['fixed_payout'],
                'note'         => 'Report payment amount=fixed_payout for 300k partner earning',
            ), JSON_UNESCAPED_UNICODE),
        ));

        $ok = is_array($upsert) ? !empty($upsert['ok']) : (bool) $upsert;

        return array(
            'ok'      => $ok,
            'message' => $ok ? 'core campaign rule ready' : 'core rule upsert failed',
        );
    }
}

if (!function_exists('lc_campaign_ensure_seo_geo_cps')) {
    /**
     * SEO GEO 강의 CPS 상품을 생성/갱신한다.
     *
     * @param array{activate?:bool,mt_id?:int} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool}
     */
    function lc_campaign_ensure_seo_geo_cps(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        if (function_exists('lc_db_run_migrations')) {
            lc_db_run_migrations();
        }

        $def = lc_seo_geo_cps_campaign_definition();
        $table = lc_table('campaigns');
        $code_esc = lc_sql_escape((string) $def['code']);
        $landing = (string) $def['landing_url'];
        $mt_id = isset($options['mt_id']) ? (int) $options['mt_id'] : 0;

        $status = (string) $def['status'];
        if (!empty($options['activate'])) {
            $status = LC_STATUS_ACTIVE;
        }

        $has_platform = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'cp_platform_service');
        $has_product = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'cp_product_type');
        $has_rule = function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'cp_commission_rule_id');

        $keep = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
        if ($keep) {
            $cp_id = (int) $keep['cp_id'];
            $next_mt = $mt_id > 0 ? $mt_id : (int) ($keep['mt_id'] ?? 0);
            $next_status = (string) ($keep['cp_status'] ?? '');
            if (!empty($options['activate']) || $next_status === '' || $next_status === LC_STATUS_DRAFT || $next_status === 'paused') {
                $next_status = $status;
            }

            $sets = array(
                "mt_id = '{$next_mt}'",
                "cp_name = '" . lc_sql_escape((string) $def['title']) . "'",
                "cp_category = '" . lc_sql_escape((string) $def['category']) . "'",
                "cp_type = 'cps'",
                "cp_price = '" . (int) $def['price'] . "'",
                "cp_merchant_price = '" . (int) $def['merchant_price'] . "'",
                "cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "'",
                "cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "'",
                "cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "'",
                "cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "'",
                "cp_description = '" . lc_sql_escape((string) $def['description']) . "'",
                "cp_landing_url = '" . lc_sql_escape($landing) . "'",
                "cp_status = '" . lc_sql_escape($next_status) . "'",
                "cp_badge = '" . lc_sql_escape((string) $def['badge']) . "'",
                "cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "'",
                'cp_updated_at = NOW()',
            );
            if ($has_platform) {
                $sets[] = "cp_platform_service = '" . lc_sql_escape((string) $def['platform_service']) . "'";
            }
            if ($has_product) {
                $sets[] = "cp_product_type = '" . lc_sql_escape((string) $def['product_type']) . "'";
            }
            if ($has_rule) {
                $sets[] = "cp_commission_rule_id = '" . lc_sql_escape((string) $def['commission_rule_id']) . "'";
            }

            lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE cp_id = '{$cp_id}' ", false);

            $rule = lc_campaign_ensure_seo_geo_cps_core_rule($cp_id);

            return array(
                'ok'      => true,
                'message' => 'SEO GEO 강의 CPS 캠페인을 갱신했습니다.',
                'cpId'    => $cp_id,
                'created' => false,
                'mtId'    => $next_mt,
                'coreRule'=> $rule,
            );
        }

        $cols = array(
            "mt_id = '{$mt_id}'",
            "cp_code = '{$code_esc}'",
            "cp_name = '" . lc_sql_escape((string) $def['title']) . "'",
            "cp_category = '" . lc_sql_escape((string) $def['category']) . "'",
            "cp_type = 'cps'",
            "cp_price = '" . (int) $def['price'] . "'",
            "cp_merchant_price = '" . (int) $def['merchant_price'] . "'",
            "cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "'",
            "cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "'",
            "cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "'",
            "cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "'",
            "cp_description = '" . lc_sql_escape((string) $def['description']) . "'",
            "cp_landing_url = '" . lc_sql_escape($landing) . "'",
            "cp_tracking_base_url = ''",
            "cp_status = '" . lc_sql_escape($status) . "'",
            "cp_badge = '" . lc_sql_escape((string) $def['badge']) . "'",
            "cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "'",
            'cp_sort = 0',
            'cp_created_at = NOW()',
            'cp_updated_at = NOW()',
        );
        if ($has_platform) {
            $cols[] = "cp_platform_service = '" . lc_sql_escape((string) $def['platform_service']) . "'";
        }
        if ($has_product) {
            $cols[] = "cp_product_type = '" . lc_sql_escape((string) $def['product_type']) . "'";
        }
        if ($has_rule) {
            $cols[] = "cp_commission_rule_id = '" . lc_sql_escape((string) $def['commission_rule_id']) . "'";
        }

        lc_sql_query(' INSERT INTO `' . $table . '` SET ' . implode(', ', $cols) . ' ', false);

        $cp_id = 0;
        if (function_exists('sql_insert_id')) {
            $cp_id = (int) sql_insert_id();
        }
        if ($cp_id <= 0) {
            $row = lc_sql_fetch(" SELECT cp_id FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
            $cp_id = $row ? (int) $row['cp_id'] : 0;
        }

        if ($cp_id <= 0) {
            return array('ok' => false, 'message' => 'SEO GEO 강의 CPS 캠페인 생성에 실패했습니다.');
        }

        $rule = lc_campaign_ensure_seo_geo_cps_core_rule($cp_id);

        return array(
            'ok'      => true,
            'message' => 'SEO GEO 강의 CPS 캠페인을 등록했습니다.',
            'cpId'    => $cp_id,
            'created' => true,
            'mtId'    => $mt_id,
            'coreRule'=> $rule,
        );
    }
}
