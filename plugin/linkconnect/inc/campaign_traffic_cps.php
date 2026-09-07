<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_traffic_cps_campaign_definition')) {
    /**
     * 트래픽 CPS — ONOFF Core TRAFFIC + LIFETIME 규칙.
     *
     * @return array<string,mixed>
     */
    function lc_traffic_cps_campaign_definition()
    {
        return array(
            'code'               => 'CPS-TRAFFIC',
            'title'              => '트래픽',
            'category'           => '디지털상품',
            'price'              => 0,
            'merchant_price'     => 0,
            'approval_rate'      => '10%',
            'avg_time'           => 'LIFETIME',
            'allowed_channels'   => '블로그, 카페, SNS, 유튜브, 커뮤니티',
            'forbidden_channels' => '스팸, 브랜드 사칭, 허위광고, 부정 클릭',
            'description'        => 'ONOFF 트래픽 서비스 홍보. 결제·이용 확정 시 Core LIFETIME 수수료(10%)가 적용됩니다. https://traffic.icrm.co.kr/',
            'badge'              => '신규',
            'recommended'        => true,
            'status'             => 'active',
            'platform_service'   => 'TRAFFIC',
            'product_type'       => 'lifetime',
            'commission_rule_id' => 'global_lifetime_default_10pct',
            'landing_url'        => 'https://traffic.icrm.co.kr/',
        );
    }
}

if (!function_exists('lc_campaign_ensure_traffic_cps')) {
    /**
     * 트래픽 CPS 상품을 생성/갱신한다.
     *
     * @param array{activate?:bool,mt_id?:int} $options
     * @return array{ok:bool,message:string,cpId?:int,created?:bool}
     */
    function lc_campaign_ensure_traffic_cps(array $options = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        if (function_exists('lc_db_run_migrations')) {
            lc_db_run_migrations();
        }

        $def = lc_traffic_cps_campaign_definition();
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

            if (function_exists('lc_onoff_core_ensure_ready')) {
                lc_onoff_core_ensure_ready();
            }

            return array(
                'ok'      => true,
                'message' => '트래픽 CPS 캠페인을 갱신했습니다.',
                'cpId'    => $cp_id,
                'created' => false,
                'mtId'    => $next_mt,
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
            return array('ok' => false, 'message' => '트래픽 CPS 캠페인 생성에 실패했습니다.');
        }

        if (function_exists('lc_onoff_core_ensure_ready')) {
            lc_onoff_core_ensure_ready();
        }

        return array(
            'ok'      => true,
            'message' => '트래픽 CPS 캠페인을 등록했습니다.',
            'cpId'    => $cp_id,
            'created' => true,
            'mtId'    => $mt_id,
        );
    }
}
