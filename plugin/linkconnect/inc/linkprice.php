<?php
/**
 * LinkConnect × 링크프라이스 CPS (외부 네트워크 모듈)
 *
 * CPA 전환/지갑/캠페인과 완전 분리.
 * 역할 분리:
 *  - LinkpriceConfig     → lc_lp_config_*
 *  - LinkpriceClient     → lc_lp_client_*
 *  - LinkpriceRepository → lc_lp_repo_*
 *  - LinkpriceLogger     → lc_lp_log_*
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceConfig — 네트워크 설정 로드/저장/마스킹
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_config_defaults')) {
    function lc_lp_config_defaults()
    {
        return array(
            'network_code'          => LC_LP_NETWORK_CODE,
            'network_name'          => '링크프라이스',
            'affiliate_code'        => '',
            'api_auth_key'          => '',
            'postback_secret'       => '',
            'api_enabled'           => 0,
            'default_partner_rate'  => 70.00,
            'last_merchant_sync_at' => null,
            'last_order_sync_at'    => null,
        );
    }
}

if (!function_exists('lc_lp_mask_secret')) {
    /**
     * 관리자 화면용 시크릿 마스킹
     */
    function lc_lp_mask_secret($value, $visible = 4)
    {
        $value = (string) $value;
        $len = strlen($value);
        if ($len === 0) {
            return '';
        }
        if ($len <= $visible) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', max(0, $len - $visible)) . substr($value, -$visible);
    }
}

if (!function_exists('lc_lp_config_get')) {
    /**
     * @return array
     */
    function lc_lp_config_get()
    {
        $defaults = lc_lp_config_defaults();
        if (!function_exists('lc_db_table_exists') || !lc_db_table_exists(lc_table('lp_networks'))) {
            return $defaults;
        }

        $table = lc_table('lp_networks');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE network_code = '" . lc_sql_escape(LC_LP_NETWORK_CODE) . "' LIMIT 1 ", false);
        if (!is_array($row)) {
            return $defaults;
        }

        return array(
            'network_id'            => (int) ($row['network_id'] ?? 0),
            'network_code'          => (string) ($row['network_code'] ?? LC_LP_NETWORK_CODE),
            'network_name'          => (string) ($row['network_name'] ?? '링크프라이스'),
            'affiliate_code'        => (string) ($row['affiliate_code'] ?? ''),
            'api_auth_key'          => (string) ($row['api_auth_key'] ?? ''),
            'postback_secret'       => (string) ($row['postback_secret'] ?? ''),
            'api_enabled'           => (int) ($row['api_enabled'] ?? 0),
            'default_partner_rate'  => (float) ($row['default_partner_rate'] ?? 70),
            'last_merchant_sync_at' => $row['last_merchant_sync_at'] ?? null,
            'last_order_sync_at'    => $row['last_order_sync_at'] ?? null,
            'created_at'            => $row['created_at'] ?? null,
            'updated_at'            => $row['updated_at'] ?? null,
        );
    }
}

if (!function_exists('lc_lp_config_to_api')) {
    /**
     * 관리자 API 응답용 (시크릿 마스킹)
     */
    function lc_lp_config_to_api(?array $cfg = null)
    {
        $cfg = is_array($cfg) ? $cfg : lc_lp_config_get();
        $auth = (string) ($cfg['api_auth_key'] ?? '');
        $secret = (string) ($cfg['postback_secret'] ?? '');

        return array(
            'networkId'           => (int) ($cfg['network_id'] ?? 0),
            'networkCode'         => (string) ($cfg['network_code'] ?? LC_LP_NETWORK_CODE),
            'networkName'         => (string) ($cfg['network_name'] ?? '링크프라이스'),
            'affiliateCode'       => (string) ($cfg['affiliate_code'] ?? ''),
            'apiAuthKeyMasked'    => lc_lp_mask_secret($auth),
            'apiAuthKeySet'       => $auth !== '',
            'postbackSecretMasked'=> lc_lp_mask_secret($secret),
            'postbackSecretSet'   => $secret !== '',
            'apiEnabled'          => !empty($cfg['api_enabled']),
            'defaultPartnerRate'  => (float) ($cfg['default_partner_rate'] ?? 70),
            'lastMerchantSyncAt'  => $cfg['last_merchant_sync_at'] ?? null,
            'lastOrderSyncAt'     => $cfg['last_order_sync_at'] ?? null,
            'ready'               => (
                !empty($cfg['api_enabled'])
                && trim((string) ($cfg['affiliate_code'] ?? '')) !== ''
                && $auth !== ''
            ),
            'security'            => function_exists('lc_lp_ui_security_settings') ? lc_lp_ui_security_settings() : array(),
        );
    }
}

if (!function_exists('lc_lp_health_snapshot')) {
    /**
     * 운영 점검용 스냅샷 (시크릿·PII 미포함)
     *
     * @return array{ok:bool,db:array,config:array,counts:array,checks:array}
     */
    function lc_lp_health_snapshot()
    {
        $tables = array(
            'lp_networks',
            'lp_merchants',
            'lp_clicks',
            'lp_postbacks',
            'lp_orders',
            'lp_order_status_logs',
            'lp_sync_logs',
            'lp_ledger',
        );
        $db = array();
        foreach ($tables as $name) {
            $db[$name] = function_exists('lc_db_table_exists') && lc_db_table_exists(lc_table($name));
        }
        $dbReady = !in_array(false, $db, true);

        $cfg = lc_lp_config_to_api();
        $cronSet = function_exists('lc_settings_get')
            && trim((string) lc_settings_get('lpCronToken', '')) !== '';

        $counts = array();
        if ($dbReady) {
            foreach (array(
                'merchants' => 'lp_merchants',
                'clicks'    => 'lp_clicks',
                'postbacks' => 'lp_postbacks',
                'orders'    => 'lp_orders',
                'ledger'    => 'lp_ledger',
            ) as $key => $tbl) {
                $row = lc_sql_fetch(' SELECT COUNT(*) AS cnt FROM `' . lc_table($tbl) . '` ', false);
                $counts[$key] = is_array($row) ? (int) ($row['cnt'] ?? 0) : 0;
            }
        }

        $checks = array(
            'dbReady'              => $dbReady,
            'apiEnabled'           => !empty($cfg['apiEnabled']),
            'affiliateConfigured'  => trim((string) ($cfg['affiliateCode'] ?? '')) !== '',
            'authKeySet'           => !empty($cfg['apiAuthKeySet']),
            'postbackSecretSet'    => !empty($cfg['postbackSecretSet']),
            'cronTokenSet'         => $cronSet,
            'readyForSync'         => !empty($cfg['ready']),
            'postbackAliasOk'      => true,
        );

        return array(
            'ok'     => $dbReady,
            'db'     => $db,
            'config' => array(
                'networkId'          => (int) ($cfg['networkId'] ?? 0),
                'affiliateCode'      => (string) ($cfg['affiliateCode'] ?? ''),
                'apiEnabled'         => !empty($cfg['apiEnabled']),
                'apiAuthKeySet'      => !empty($cfg['apiAuthKeySet']),
                'postbackSecretSet'  => !empty($cfg['postbackSecretSet']),
                'ready'              => !empty($cfg['ready']),
                'lastMerchantSyncAt' => $cfg['lastMerchantSyncAt'] ?? null,
                'lastOrderSyncAt'    => $cfg['lastOrderSyncAt'] ?? null,
            ),
            'counts' => $counts,
            'checks' => $checks,
        );
    }
}

if (!function_exists('lc_lp_site_base_url')) {
    function lc_lp_site_base_url()
    {
        return defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
    }
}

if (!function_exists('lc_lp_merchant_admin_stats')) {
    /**
     * @return array{total:int,apr:int,visible:int,partnerVisible:int,hiddenApr:int,syncActive:int}
     */
    function lc_lp_merchant_admin_stats()
    {
        $defaults = array(
            'total'          => 0,
            'apr'            => 0,
            'visible'        => 0,
            'partnerVisible' => 0,
            'hiddenApr'      => 0,
            'syncActive'     => 0,
        );
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table)) {
            return $defaults;
        }

        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN subscript = 'APR' THEN 1 ELSE 0 END) AS apr_cnt,
            SUM(CASE WHEN visible = 1 THEN 1 ELSE 0 END) AS visible_cnt,
            SUM(CASE WHEN sync_active = 1 THEN 1 ELSE 0 END) AS sync_active_cnt,
            SUM(CASE WHEN subscript = 'APR' AND sync_active = 1 AND visible = 0 THEN 1 ELSE 0 END) AS hidden_apr_cnt,
            SUM(CASE WHEN subscript = 'APR' AND visible = 1 AND sync_active = 1 AND click_url <> '' THEN 1 ELSE 0 END) AS partner_visible_cnt
            FROM `{$table}` ", false);

        if (!is_array($row)) {
            return $defaults;
        }

        return array(
            'total'          => (int) ($row['total'] ?? 0),
            'apr'            => (int) ($row['apr_cnt'] ?? 0),
            'visible'        => (int) ($row['visible_cnt'] ?? 0),
            'partnerVisible' => (int) ($row['partner_visible_cnt'] ?? 0),
            'hiddenApr'      => (int) ($row['hidden_apr_cnt'] ?? 0),
            'syncActive'     => (int) ($row['sync_active_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_lp_postback_recent_stats')) {
    /**
     * @return array{total:int,last24h:int,lastSuccessAt:string|null,lastErrorAt:string|null}
     */
    function lc_lp_postback_recent_stats()
    {
        $out = array(
            'total'         => 0,
            'last24h'       => 0,
            'lastSuccessAt' => null,
            'lastErrorAt'   => null,
        );
        $table = lc_table('lp_postbacks');
        if (!lc_db_table_exists($table)) {
            return $out;
        }

        $row = lc_sql_fetch(" SELECT COUNT(*) AS total,
            SUM(CASE WHEN received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS last24h
            FROM `{$table}` ", false);
        if (is_array($row)) {
            $out['total'] = (int) ($row['total'] ?? 0);
            $out['last24h'] = (int) ($row['last24h'] ?? 0);
        }

        $ok = lc_sql_fetch(" SELECT received_at FROM `{$table}`
            WHERE process_status IN ('" . LC_LP_PB_PROCESSED . "','" . LC_LP_PB_DUPLICATE . "')
            ORDER BY lpp_id DESC LIMIT 1 ", false);
        if (is_array($ok) && !empty($ok['received_at'])) {
            $out['lastSuccessAt'] = (string) $ok['received_at'];
        }

        $err = lc_sql_fetch(" SELECT received_at FROM `{$table}`
            WHERE process_status = '" . LC_LP_PB_ERROR . "'
            ORDER BY lpp_id DESC LIMIT 1 ", false);
        if (is_array($err) && !empty($err['received_at'])) {
            $out['lastErrorAt'] = (string) $err['received_at'];
        }

        return $out;
    }
}

if (!function_exists('lc_lp_setup_urls')) {
    /**
     * @return array{postbackPrimary:string,postbackAlt:string,postbackWithSecret:string,health:string,merchantCron:string,orderCron:string,publicCps:string,partnerCps:string}
     */
    function lc_lp_setup_urls()
    {
        $base = lc_lp_site_base_url();
        $cfg = lc_lp_config_get();
        $secret = trim((string) ($cfg['postback_secret'] ?? ''));
        $token = function_exists('lc_settings_get') ? trim((string) lc_settings_get('lpCronToken', '')) : '';
        if ($token === '' && getenv('LC_LP_CRON_TOKEN')) {
            $token = trim((string) getenv('LC_LP_CRON_TOKEN'));
        }

        $postback = $base . '/api/external/linkprice/postback';
        $postback_secret = $secret !== '' ? $postback . '?secret=' . rawurlencode($secret) : $postback;
        $merchant_cron = $base . '/plugin/linkconnect/cron/linkprice_sync_merchants.php';
        $order_cron = $base . '/plugin/linkconnect/cron/linkprice_sync_conversions.php?mode=last7';
        if ($token !== '') {
            $merchant_cron .= (strpos($merchant_cron, '?') === false ? '?' : '&') . 'token=' . rawurlencode($token);
            $order_cron .= '&token=' . rawurlencode($token);
        }

        return array(
            'postbackPrimary'     => $postback,
            'postbackAlt'         => $base . '/plugin/linkconnect/api/lp_postback.php',
            'postbackWithSecret'  => $postback_secret,
            'health'              => $base . '/plugin/linkconnect/api/lp_health.php' . ($token !== '' ? '?token=' . rawurlencode($token) : ''),
            'merchantCron'        => $merchant_cron,
            'orderCron'           => $order_cron,
            'publicCps'           => function_exists('lc_spa_url') ? lc_spa_url('/cps') : $base . '/cps',
            'partnerCps'          => function_exists('lc_spa_url') ? lc_spa_url('/partner/cps') : $base . '/partner/cps',
        );
    }
}

if (!function_exists('lc_lp_setup_cron_commands')) {
    /**
     * @return array{merchant:string,order:string}
     */
    function lc_lp_setup_cron_commands()
    {
        $root = defined('G5_PATH') ? rtrim((string) G5_PATH, '/') : '/path/to/public_html';

        return array(
            'merchant' => '15 3 * * * cd ' . $root . ' && php cron/linkprice_sync_merchants.php >> /tmp/lp_merchant_cron.log 2>&1',
            'order'    => '*/20 * * * * cd ' . $root . ' && php cron/linkprice_sync_conversions.php --mode=last7 >> /tmp/lp_order_cron.log 2>&1',
        );
    }
}

if (!function_exists('lc_lp_setup_steps')) {
    /**
     * @return array<int,array<string,mixed>>
     */
    function lc_lp_setup_steps()
    {
        $health = lc_lp_health_snapshot();
        $cfg = lc_lp_config_to_api();
        $merchants = lc_lp_merchant_admin_stats();
        $postbacks = lc_lp_postback_recent_stats();
        $security = function_exists('lc_lp_ui_security_settings') ? lc_lp_ui_security_settings() : array();

        $merchant_synced = trim((string) ($cfg['lastMerchantSyncAt'] ?? '')) !== '';
        $order_synced = trim((string) ($cfg['lastOrderSyncAt'] ?? '')) !== '';
        $connection_ok = !empty($cfg['ready']) && ($merchant_synced || $merchants['apr'] > 0);
        $postback_guard_ok = empty($cfg['postbackSecretSet'])
            || !empty($security['postbackIpEnabled']);
        $cron_hint_ok = !empty($health['checks']['cronTokenSet']) || $order_synced;

        $make = function ($id, $title, $done, $description = '', $action = '') {
            return array(
                'id'          => $id,
                'title'       => $title,
                'status'      => $done ? 'done' : 'pending',
                'description' => $description,
                'action'      => $action,
            );
        };

        $steps = array(
            $make(
                'db',
                'DB 스키마 준비',
                !empty($health['checks']['dbReady']),
                'g5_lc_lp_* 테이블이 생성되어 있어야 합니다.'
            ),
            $make(
                'api_config',
                'A코드·API 키·API 활성',
                !empty($cfg['ready']),
                'Affiliate Center에서 발급한 A코드와 auth_key를 저장하고 API를 활성화하세요.',
                'settings'
            ),
            $make(
                'connection',
                '링크프라이스 연결 테스트',
                $connection_ok,
                '광고주 조회 API 연결이 성공해야 동기화가 가능합니다.',
                'test_connection'
            ),
            $make(
                'postback_url',
                'Affiliate Center POSTBACK URL 등록',
                $postbacks['total'] > 0,
                '리워드 API URL에 POSTBACK 주소를 등록하세요. Secret 미사용 시 IP 제한을 권장합니다.',
                'copy_postback'
            ),
            $make(
                'postback_security',
                'POSTBACK 보안 설정',
                $postback_guard_ok,
                'Secret을 쓰지 않을 때는 허용 IP 제한을 켜세요.',
                'settings_security'
            ),
            $make(
                'merchant_sync',
                'CPS 광고주 동기화',
                $merchant_synced && $merchants['apr'] > 0,
                'APR 승인 광고주를 최소 1회 동기화하세요.',
                'sync_merchants'
            ),
            $make(
                'merchant_visible',
                '파트너·공개 노출 광고주 선택',
                $merchants['partnerVisible'] > 0,
                '동기화된 광고주 중 홍보할 몰만 visible=ON 하세요.',
                'merchants'
            ),
            $make(
                'cron',
                '크론(또는 웹 크론) 등록',
                $cron_hint_ok,
                '광고주·실적 동기화 스케줄을 서버에 등록하세요.',
                'copy_cron'
            ),
            $make(
                'postback_received',
                'POSTBACK 수신 확인',
                $postbacks['last24h'] > 0 || $postbacks['total'] > 0,
                '테스트 클릭·구매 후 POSTBACK 로그를 확인하세요.',
                'postbacks'
            ),
            $make(
                'order_sync',
                '실적 동기화 확인',
                $order_synced,
                '확정·취소 상태는 translist API 동기화로 반영됩니다.',
                'sync_orders'
            ),
            $make(
                'public_cps',
                '공개 /cps 페이지 노출',
                $merchants['partnerVisible'] > 0,
                '노출 ON된 광고주가 공개 CPS 목록에 표시됩니다.',
                'public_cps'
            ),
        );

        $done = 0;
        foreach ($steps as $step) {
            if (($step['status'] ?? '') === 'done') {
                $done++;
            }
        }

        return array(
            'items'   => $steps,
            'done'    => $done,
            'total'   => count($steps),
            'percent' => count($steps) > 0 ? (int) round(($done / count($steps)) * 100) : 0,
        );
    }
}

if (!function_exists('lc_lp_setup_snapshot')) {
    /**
     * CPS 운영 온보딩 스냅샷 (관리자)
     *
     * @return array<string,mixed>
     */
    function lc_lp_setup_snapshot()
    {
        $health = lc_lp_health_snapshot();
        $cfg = lc_lp_config_to_api();
        $steps = lc_lp_setup_steps();

        return array(
            'ok'        => !empty($health['ok']),
            'health'    => $health,
            'config'    => $cfg,
            'urls'      => lc_lp_setup_urls(),
            'cron'      => lc_lp_setup_cron_commands(),
            'merchants' => lc_lp_merchant_admin_stats(),
            'postbacks' => lc_lp_postback_recent_stats(),
            'steps'     => $steps,
            'ready'     => ($steps['percent'] ?? 0) >= 80,
        );
    }
}

if (!function_exists('lc_lp_merchant_bulk_update_admin')) {
    /**
     * @param int[] $lpm_ids
     * @return array{ok:bool,message:string,updated:int,failed:int}
     */
    function lc_lp_merchant_bulk_update_admin(array $lpm_ids, array $values)
    {
        $updated = 0;
        $failed = 0;
        foreach ($lpm_ids as $lpm_id) {
            $result = lc_lp_merchant_update_admin((int) $lpm_id, $values);
            if (!empty($result['ok'])) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return array(
            'ok'      => $updated > 0,
            'message' => $updated > 0
                ? ($updated . '건 업데이트' . ($failed > 0 ? ', ' . $failed . '건 실패' : ''))
                : '업데이트된 항목이 없습니다.',
            'updated' => $updated,
            'failed'  => $failed,
        );
    }
}

if (!function_exists('lc_lp_merchant_bulk_update_scope')) {
    /**
     * scope: apr_hidden | apr_all | partner_visible
     *
     * @return array{ok:bool,message:string,updated:int}
     */
    function lc_lp_merchant_bulk_update_scope($scope, array $values)
    {
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => '광고주 테이블이 없습니다.', 'updated' => 0);
        }

        $where = array("subscript = 'APR'", 'sync_active = 1');
        if ($scope === 'apr_hidden') {
            $where[] = 'visible = 0';
        } elseif ($scope === 'partner_visible') {
            $where[] = "visible = 1 AND click_url <> ''";
        } elseif ($scope !== 'apr_all') {
            return array('ok' => false, 'message' => '유효하지 않은 scope입니다.', 'updated' => 0);
        }
        $where[] = "LOWER(merchant_code) NOT IN ('linkprice', 'lp')";

        $rows = lc_sql_query(' SELECT lpm_id FROM `' . $table . '` WHERE ' . implode(' AND ', $where) . ' ', false);
        $ids = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $ids[] = (int) ($row['lpm_id'] ?? 0);
            }
        }

        if (!$ids) {
            return array('ok' => false, 'message' => '대상 광고주가 없습니다.', 'updated' => 0);
        }

        $result = lc_lp_merchant_bulk_update_admin($ids, $values);

        return array(
            'ok'      => !empty($result['ok']),
            'message' => $result['message'],
            'updated' => (int) ($result['updated'] ?? 0),
        );
    }
}

if (!function_exists('lc_lp_admin_first_active_partner_id')) {
    function lc_lp_admin_first_active_partner_id()
    {
        $table = lc_table('partners');
        if (!lc_db_table_exists($table)) {
            return 0;
        }
        $row = lc_sql_fetch(" SELECT pt_id FROM `{$table}`
            WHERE pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "'
            ORDER BY pt_id ASC LIMIT 1 ", false);

        return is_array($row) ? (int) ($row['pt_id'] ?? 0) : 0;
    }
}

if (!function_exists('lc_lp_admin_e2e_snapshot')) {
    /**
     * @return array<string,mixed>
     */
    function lc_lp_admin_e2e_snapshot()
    {
        $setup = function_exists('lc_lp_setup_snapshot') ? lc_lp_setup_snapshot() : array();
        $pt_id = lc_lp_admin_first_active_partner_id();
        $merchant = null;
        $promo_url = '';
        $test_click_url = '';

        if ($pt_id > 0 && lc_db_table_exists(lc_table('lp_merchants'))) {
            $list = lc_lp_merchants_list(array('partner_visible' => true, 'limit' => 1));
            if (!empty($list['items'][0])) {
                $merchant = $list['items'][0];
                if (function_exists('lc_lp_public_promo_url')) {
                    $promo_url = lc_lp_public_promo_url((string) $merchant['merchant_code'], $pt_id);
                }
            }
        }

        $recent_click = null;
        if (lc_db_table_exists(lc_table('lp_clicks'))) {
            $recent_click = lc_sql_fetch(' SELECT lpc_id, pt_id, lpm_id, u_id, clicked_at FROM `' . lc_table('lp_clicks') . '` ORDER BY lpc_id DESC LIMIT 1 ', false);
        }

        $checks = array(
            array(
                'id'    => 'config',
                'label' => 'API 설정 완료',
                'ok'    => !empty($setup['config']['ready']),
            ),
            array(
                'id'    => 'merchants',
                'label' => '노출 광고주 1건 이상',
                'ok'    => (int) (($setup['merchants']['partnerVisible'] ?? 0)) > 0,
            ),
            array(
                'id'    => 'partner',
                'label' => '활성 파트너 계정',
                'ok'    => $pt_id > 0,
            ),
            array(
                'id'    => 'clicks',
                'label' => '클릭 기록 존재',
                'ok'    => (int) ($setup['health']['counts']['clicks'] ?? 0) > 0,
            ),
            array(
                'id'    => 'postbacks',
                'label' => 'POSTBACK 수신',
                'ok'    => (int) (($setup['postbacks']['total'] ?? 0)) > 0,
            ),
            array(
                'id'    => 'orders',
                'label' => 'CPS 실적 생성',
                'ok'    => (int) ($setup['health']['counts']['orders'] ?? 0) > 0,
            ),
        );

        $done = 0;
        foreach ($checks as $c) {
            if (!empty($c['ok'])) {
                $done++;
            }
        }

        return array(
            'setup'       => $setup,
            'partnerId'   => $pt_id,
            'merchant'    => is_array($merchant) ? array(
                'lpmId'        => (int) ($merchant['lpm_id'] ?? 0),
                'merchantCode' => (string) ($merchant['merchant_code'] ?? ''),
                'merchantName' => (string) ($merchant['merchant_name'] ?? ''),
            ) : null,
            'promoUrl'    => $promo_url,
            'recentClick' => is_array($recent_click) ? array(
                'lpcId'     => (int) ($recent_click['lpc_id'] ?? 0),
                'ptId'      => (int) ($recent_click['pt_id'] ?? 0),
                'lpmId'     => (int) ($recent_click['lpm_id'] ?? 0),
                'uId'       => (string) ($recent_click['u_id'] ?? ''),
                'clickedAt' => $recent_click['clicked_at'] ?? null,
            ) : null,
            'checks'      => $checks,
            'checksDone'  => $done,
            'checksTotal' => count($checks),
        );
    }
}

if (!function_exists('lc_lp_admin_create_test_click')) {
    /**
     * @return array{ok:bool,message:string,click:array|null,promoUrl:string}
     */
    function lc_lp_admin_create_test_click(array $options = array())
    {
        $pt_id = (int) ($options['pt_id'] ?? $options['ptId'] ?? 0);
        if ($pt_id <= 0) {
            $pt_id = lc_lp_admin_first_active_partner_id();
        }
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '활성 파트너가 없습니다.', 'click' => null, 'promoUrl' => '');
        }

        $lpm_id = (int) ($options['lpm_id'] ?? $options['lpmId'] ?? 0);
        $merchant = null;
        if ($lpm_id > 0 && lc_db_table_exists(lc_table('lp_merchants'))) {
            $merchant = lc_sql_fetch(' SELECT * FROM `' . lc_table('lp_merchants') . '` WHERE lpm_id = ' . $lpm_id . ' LIMIT 1 ', false);
        } elseif (trim((string) ($options['merchant_code'] ?? $options['merchantCode'] ?? '')) !== '') {
            $merchant = lc_lp_repo_get_merchant_by_code((string) ($options['merchant_code'] ?? $options['merchantCode']));
        } else {
            $list = lc_lp_merchants_list(array('partner_visible' => true, 'limit' => 1));
            $merchant = $list['items'][0] ?? null;
        }

        if (!is_array($merchant) || !lc_lp_merchant_partner_visible($merchant)) {
            return array('ok' => false, 'message' => '홍보 가능한 CPS 광고주가 없습니다.', 'click' => null, 'promoUrl' => '');
        }

        $lpm_id = (int) ($merchant['lpm_id'] ?? 0);
        $created = lc_lp_repo_create_click($pt_id, $lpm_id, (string) ($merchant['merchant_url'] ?? ''));
        if (empty($created['ok'])) {
            return array(
                'ok'       => false,
                'message'  => (string) ($created['message'] ?? '클릭 생성 실패'),
                'click'    => null,
                'promoUrl' => '',
            );
        }

        return array(
            'ok'       => true,
            'message'  => '테스트 클릭이 생성되었습니다.',
            'click'    => $created['click'],
            'promoUrl' => lc_lp_public_promo_url((string) $merchant['merchant_code'], $pt_id),
        );
    }
}

if (!function_exists('lc_lp_admin_simulate_postback')) {
    /**
     * 관리자 E2E — 테스트 POSTBACK 처리 (IP/Secret 검증 우회, process_item 직접 호출)
     *
     * @return array{ok:bool,message:string,result:array|null,lppId:int}
     */
    function lc_lp_admin_simulate_postback(array $options = array())
    {
        $click_result = lc_lp_admin_create_test_click($options);
        if (empty($click_result['ok']) || !is_array($click_result['click'])) {
            return array(
                'ok'      => false,
                'message' => (string) ($click_result['message'] ?? '클릭 생성 실패'),
                'result'  => null,
                'lppId'   => 0,
            );
        }

        $click = $click_result['click'];
        $merchant = lc_sql_fetch(' SELECT * FROM `' . lc_table('lp_merchants') . '` WHERE lpm_id = ' . (int) ($click['lpm_id'] ?? 0) . ' LIMIT 1 ', false);
        if (!is_array($merchant)) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'result' => null, 'lppId' => 0);
        }

        $cfg = lc_lp_config_get();
        $trlog = '9' . date('YmdHis') . mt_rand(1000, 9999);
        $payload = array(
            'day'                => date('Ymd'),
            'time'               => date('His'),
            'merchant_id'        => (string) $merchant['merchant_code'],
            'order_code'         => 'lc_test_' . date('YmdHis') . '_' . mt_rand(100, 999),
            'product_code'       => 'lc_test_product',
            'product_name'       => 'LinkConnect E2E Test Product',
            'category_code'      => 'test',
            'item_count'         => 1,
            'price'              => (int) ($options['price'] ?? 50000),
            'commision'          => (int) ($options['commission'] ?? 2500),
            'affiliate_id'       => (string) ($cfg['affiliate_code'] ?? 'TEST'),
            'affiliate_user_id'  => (string) ($click['u_id'] ?? ''),
            'trlog_id'           => $trlog,
            'uniq_id'            => substr(md5(uniqid('', true)), 0, 14),
            'base_commission'    => '5%',
            'incentive_commission' => '0%',
            'test'               => 'Y',
        );

        $headers_json = json_encode(array('X-LC-E2E' => 'admin'), JSON_UNESCAPED_UNICODE);
        $lpp_id = lc_lp_repo_insert_postback($payload, $headers_json, '127.0.0.1', array(
            'trlog_id'      => (string) $payload['trlog_id'],
            'uniq_id'       => (string) $payload['uniq_id'],
            'merchant_code' => (string) $payload['merchant_id'],
            'order_code'    => (string) $payload['order_code'],
            'u_id'          => (string) $payload['affiliate_user_id'],
        ));

        $result = lc_lp_postback_process_item($payload, $lpp_id);
        $status = (string) ($result['status'] ?? '');
        $ok = in_array($status, array(LC_LP_PB_PROCESSED, LC_LP_PB_UNMATCHED, LC_LP_PB_DUPLICATE), true);

        return array(
            'ok'      => $ok,
            'message' => $ok
                ? ('테스트 POSTBACK 처리: ' . $status)
                : ('POSTBACK 처리 실패: ' . ($result['message'] ?? $status)),
            'result'  => $result,
            'lppId'   => $lpp_id,
            'payload' => $payload,
            'promoUrl'=> (string) ($click_result['promoUrl'] ?? ''),
        );
    }
}

if (!function_exists('lc_lp_config_save')) {
    /**
     * @param array $values affiliate_code, api_auth_key, postback_secret, api_enabled, default_partner_rate, network_name
     *                      빈 문자열 시크릿은 기존 값 유지
     * @return array{ok:bool,message:string,config:array}
     */
    function lc_lp_config_save(array $values)
    {
        if (!lc_db_table_exists(lc_table('lp_networks'))) {
            return array('ok' => false, 'message' => 'lp_networks 테이블이 없습니다. 마이그레이션을 먼저 적용하세요.', 'config' => lc_lp_config_get());
        }

        $current = lc_lp_config_get();
        $table = lc_table('lp_networks');

        $affiliate = array_key_exists('affiliate_code', $values)
            ? trim((string) $values['affiliate_code'])
            : (string) ($current['affiliate_code'] ?? '');
        $name = array_key_exists('network_name', $values)
            ? trim((string) $values['network_name'])
            : (string) ($current['network_name'] ?? '링크프라이스');
        if ($name === '') {
            $name = '링크프라이스';
        }

        $auth = array_key_exists('api_auth_key', $values) ? trim((string) $values['api_auth_key']) : '';
        if ($auth === '') {
            $auth = (string) ($current['api_auth_key'] ?? '');
        }

        $clear_secret = !empty($values['postback_secret_clear']);
        $secret = $clear_secret ? '' : (array_key_exists('postback_secret', $values) ? trim((string) $values['postback_secret']) : '');
        if ($secret === '' && !$clear_secret) {
            $secret = (string) ($current['postback_secret'] ?? '');
        }

        $enabled = array_key_exists('api_enabled', $values)
            ? (!empty($values['api_enabled']) ? 1 : 0)
            : (int) ($current['api_enabled'] ?? 0);

        $rate = array_key_exists('default_partner_rate', $values)
            ? (float) $values['default_partner_rate']
            : (float) ($current['default_partner_rate'] ?? 70);
        if ($rate < 0) {
            $rate = 0;
        }
        if ($rate > 100) {
            $rate = 100;
        }

        $network_id = (int) ($current['network_id'] ?? 0);
        if ($network_id > 0) {
            lc_sql_query(" UPDATE `{$table}` SET
                network_name = '" . lc_sql_escape($name) . "',
                affiliate_code = '" . lc_sql_escape($affiliate) . "',
                api_auth_key = '" . lc_sql_escape($auth) . "',
                postback_secret = '" . lc_sql_escape($secret) . "',
                api_enabled = {$enabled},
                default_partner_rate = '" . lc_sql_escape(number_format($rate, 2, '.', '')) . "',
                updated_at = NOW()
                WHERE network_id = {$network_id} ", false);
        } else {
            lc_sql_query(" INSERT INTO `{$table}` SET
                network_code = '" . lc_sql_escape(LC_LP_NETWORK_CODE) . "',
                network_name = '" . lc_sql_escape($name) . "',
                affiliate_code = '" . lc_sql_escape($affiliate) . "',
                api_auth_key = '" . lc_sql_escape($auth) . "',
                postback_secret = '" . lc_sql_escape($secret) . "',
                api_enabled = {$enabled},
                default_partner_rate = '" . lc_sql_escape(number_format($rate, 2, '.', '')) . "',
                created_at = NOW(),
                updated_at = NOW() ", false);
        }

        // settings 키에도 미러 (관리자 설정 화면 연동용, 빈 값이면 유지)
        if (function_exists('lc_settings_save')) {
            $mirror = array(
                'lpEnabled'            => $enabled ? '1' : '0',
                'lpAffiliateCode'      => $affiliate,
                'lpDefaultPartnerRate' => (string) $rate,
            );
            if (array_key_exists('api_auth_key', $values) && trim((string) $values['api_auth_key']) !== '') {
                $mirror['lpAuthKey'] = trim((string) $values['api_auth_key']);
            }
            if ($clear_secret) {
                $mirror['lpPostbackSecret'] = '';
            } elseif (array_key_exists('postback_secret', $values) && trim((string) $values['postback_secret']) !== '') {
                $mirror['lpPostbackSecret'] = trim((string) $values['postback_secret']);
            }
            lc_settings_save($mirror);
        }

        return array(
            'ok'      => true,
            'message' => '링크프라이스 설정이 저장되었습니다.',
            'config'  => lc_lp_config_get(),
        );
    }
}

if (!function_exists('lc_lp_config_touch_sync')) {
    function lc_lp_config_touch_sync($kind)
    {
        $table = lc_table('lp_networks');
        if (!lc_db_table_exists($table)) {
            return;
        }
        $col = $kind === 'orders' ? 'last_order_sync_at' : 'last_merchant_sync_at';
        lc_sql_query(" UPDATE `{$table}` SET `{$col}` = NOW(), updated_at = NOW()
            WHERE network_code = '" . lc_sql_escape(LC_LP_NETWORK_CODE) . "' ", false);
    }
}

if (!function_exists('lc_lp_enabled')) {
    function lc_lp_enabled()
    {
        $cfg = lc_lp_config_get();
        return !empty($cfg['api_enabled'])
            && trim((string) ($cfg['affiliate_code'] ?? '')) !== ''
            && trim((string) ($cfg['api_auth_key'] ?? '')) !== '';
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceClient — 아웃바운드 HTTP (광고주 조회 / 실적 조회)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_client_redact_url')) {
    /** 로그용 URL에서 auth_key 마스킹 */
    function lc_lp_client_redact_url($url)
    {
        return preg_replace('/([?&]auth_key=)[^&]*/i', '$1***', (string) $url);
    }
}

if (!function_exists('lc_lp_client_request')) {
    /**
     * @param array $options timeout, retries, test_mode
     * @return array{ok:bool,status:int,data:mixed,message:string,raw:string,url:string}
     */
    function lc_lp_client_request($method, $url, array $query = array(), array $options = array())
    {
        if (!function_exists('curl_init')) {
            return array('ok' => false, 'status' => 0, 'data' => null, 'message' => 'PHP curl 확장이 필요합니다.', 'raw' => '', 'url' => $url);
        }

        $method = strtoupper($method);
        $retries = isset($options['retries']) ? max(0, (int) $options['retries']) : 2;
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 30;

        if (!empty($options['test_mode'])) {
            $query['test'] = 'Y';
        }

        if ($query) {
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $sep . http_build_query($query);
        }

        $safe_url = lc_lp_client_redact_url($url);
        $attempt = 0;
        $last = array('ok' => false, 'status' => 0, 'data' => null, 'message' => '요청 실패', 'raw' => '', 'url' => $safe_url);

        while ($attempt <= $retries) {
            $attempt++;
            $headers = array('Accept: application/json', 'User-Agent: LinkConnect-LP/1.0');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, $timeout));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                $last = array(
                    'ok' => false, 'status' => $status, 'data' => null,
                    'message' => 'API 호출 실패: ' . $err, 'raw' => '', 'url' => $safe_url,
                );
                if ($attempt <= $retries) {
                    usleep(300000 * $attempt);
                    continue;
                }
                break;
            }

            $decoded = json_decode((string) $raw, true);
            $json_err = json_last_error() !== JSON_ERROR_NONE;
            $ok = $status >= 200 && $status < 300 && !$json_err;

            $last = array(
                'ok'      => $ok,
                'status'  => $status,
                'data'    => $json_err ? null : $decoded,
                'message' => $json_err
                    ? ('JSON 파싱 오류: ' . json_last_error_msg())
                    : ($ok ? 'OK' : ('API 오류(' . $status . ')')),
                'raw'     => (string) $raw,
                'url'     => $safe_url,
            );

            // 5xx / 타임아웃성만 재시도
            if (!$ok && $status >= 500 && $attempt <= $retries) {
                usleep(300000 * $attempt);
                continue;
            }
            break;
        }

        return $last;
    }
}

if (!function_exists('lc_lp_client_normalize_merchant_list')) {
    /**
     * 광고주 API 응답을 배열 리스트로 정규화 (필드 변경·래핑에 방어적)
     *
     * @return array<int,array>
     */
    function lc_lp_client_normalize_merchant_list($data)
    {
        if (!is_array($data)) {
            return array();
        }

        // { list: [...] } / { merchants: [...] } / { data: [...] } 래핑 대응
        foreach (array('list', 'merchants', 'data', 'items', 'result') as $wrap) {
            if (isset($data[$wrap]) && is_array($data[$wrap])) {
                $inner = $data[$wrap];
                if (isset($inner[0]) || $inner === array()) {
                    $data = $inner;
                    break;
                }
                if (isset($inner['merchant_id']) || isset($inner['merchant_code'])) {
                    return array($inner);
                }
            }
        }

        if (isset($data['merchant_id']) || isset($data['merchant_code'])) {
            return array($data);
        }

        $out = array();
        foreach ($data as $row) {
            if (is_array($row) && (isset($row['merchant_id']) || isset($row['merchant_code']))) {
                $out[] = $row;
            }
        }

        return $out;
    }
}

if (!function_exists('lc_lp_client_fetch_merchants')) {
    /**
     * CPS 광고주 목록 조회 (CPA 경로 절대 사용 금지)
     *
     * 공식: http://api.linkprice.com/ci/service/all_merchant/{A}/{all|apr}/cps[/detail]
     *
     * @param string $scope all|apr
     * @param bool   $detail
     * @param array  $options test_mode, timeout, retries
     * @return array{ok:bool,status:int,data:array,message:string,raw:string,url:string}
     */
    function lc_lp_client_fetch_merchants($scope = 'apr', $detail = true, array $options = array())
    {
        $cfg = lc_lp_config_get();
        $a_id = trim((string) ($cfg['affiliate_code'] ?? ''));
        if ($a_id === '') {
            return array('ok' => false, 'status' => 0, 'data' => array(), 'message' => 'A코드(affiliate_code)가 없습니다.', 'raw' => '', 'url' => '');
        }

        $scope = ($scope === 'all') ? 'all' : 'apr';
        // 하드코딩 cps — /cpa 절대 호출하지 않음
        $path = 'http://api.linkprice.com/ci/service/all_merchant/' . rawurlencode($a_id) . '/' . $scope . '/cps';
        if ($detail) {
            $path .= '/detail';
        }

        $res = lc_lp_client_request('GET', $path, array(), $options);
        if (!$res['ok']) {
            $res['data'] = array();
            return $res;
        }

        $list = lc_lp_client_normalize_merchant_list($res['data']);
        $res['data'] = $list;
        $res['message'] = 'OK (' . count($list) . ' merchants)';

        return $res;
    }
}

if (!function_exists('lc_lp_client_fetch_orders')) {
    /**
     * 실적 조회 API
     *
     * @param string $yyyymmdd YYYYMMDD 또는 YYYYMM
     * @param array  $opts cancel_flag, merchant_id, page, per_page, test
     */
    function lc_lp_client_fetch_orders($yyyymmdd, array $opts = array())
    {
        $cfg = lc_lp_config_get();
        $a_id = trim((string) ($cfg['affiliate_code'] ?? ''));
        $auth = trim((string) ($cfg['api_auth_key'] ?? ''));
        if ($a_id === '' || $auth === '') {
            return array('ok' => false, 'status' => 0, 'data' => array(), 'message' => 'A코드 또는 auth_key가 없습니다.', 'raw' => '', 'url' => '');
        }

        $query = array(
            'a_id'     => $a_id,
            'auth_key' => $auth,
            'yyyymmdd' => (string) $yyyymmdd,
        );
        foreach (array('cancel_flag', 'merchant_id', 'page', 'per_page', 'test', 'currency') as $k) {
            if (isset($opts[$k]) && $opts[$k] !== '' && $opts[$k] !== null) {
                $query[$k] = $opts[$k];
            }
        }

        $res = lc_lp_client_request('GET', 'https://api.linkprice.com/affiliate/translist.php', $query);
        if ($res['ok'] && is_array($res['data'])) {
            $result_code = (string) ($res['data']['result'] ?? '');
            if ($result_code !== '' && $result_code !== '0') {
                $res['ok'] = false;
                $res['message'] = '실적조회 실패 코드: ' . $result_code;
            }
        }

        return $res;
    }
}

if (!function_exists('lc_lp_client_build_click_url')) {
    /**
     * 제휴 클릭 URL에 u_id 부여
     */
    function lc_lp_client_build_click_url($base_click_url, $u_id, $deeplink_url = '')
    {
        $base = trim((string) $base_click_url);
        $u_id = trim((string) $u_id);
        if ($base === '') {
            return '';
        }

        $parts = parse_url($base);
        $query = array();
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        if ($u_id !== '') {
            $query['u_id'] = $u_id;
        }
        if ($deeplink_url !== '') {
            // 딥링크 파라미터명은 머천트/문서에 따라 다를 수 있어 원본 URL 쿼리로 보존
            $query['url'] = $deeplink_url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : 'https://';
        $host = $parts['host'] ?? 'click.linkprice.com';
        $path = $parts['path'] ?? '/click.php';
        $built = $scheme . $host . $path . '?' . http_build_query($query);

        return $built;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceLogger — 동기화/감사 로그
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_log_sync_start')) {
    /**
     * @return int sync log id
     */
    function lc_lp_log_sync_start($sync_type, $request_url = '', $request_body = '')
    {
        $table = lc_table('lp_sync_logs');
        if (!lc_db_table_exists($table)) {
            return 0;
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            sync_type = '" . lc_sql_escape((string) $sync_type) . "',
            request_url = '" . lc_sql_escape(mb_substr((string) $request_url, 0, 1000)) . "',
            request_body = '" . lc_sql_escape((string) $request_body) . "',
            success = 0,
            processed_count = 0,
            started_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_lp_log_sync_finish')) {
    function lc_lp_log_sync_finish($log_id, $success, $response_code = 0, $response_body = '', $processed = 0, $error = '')
    {
        $log_id = (int) $log_id;
        if ($log_id <= 0) {
            return;
        }
        $table = lc_table('lp_sync_logs');
        if (!lc_db_table_exists($table)) {
            return;
        }

        $body = (string) $response_body;
        if (strlen($body) > 65000) {
            $body = substr($body, 0, 65000) . '…';
        }

        lc_sql_query(" UPDATE `{$table}` SET
            response_code = " . (int) $response_code . ",
            response_body = '" . lc_sql_escape($body) . "',
            success = " . ($success ? 1 : 0) . ",
            processed_count = " . (int) $processed . ",
            error_message = '" . lc_sql_escape(mb_substr((string) $error, 0, 500)) . "',
            finished_at = NOW()
            WHERE lps_id = {$log_id} ", false);
    }
}

if (!function_exists('lc_lp_log_status_change')) {
    function lc_lp_log_status_change($lpo_id, $from_status, $to_status, $from_commission, $to_commission, $reason = '', $raw_json = '')
    {
        $table = lc_table('lp_order_status_logs');
        if (!lc_db_table_exists($table)) {
            return 0;
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            lpo_id = " . (int) $lpo_id . ",
            from_status = '" . lc_sql_escape((string) $from_status) . "',
            to_status = '" . lc_sql_escape((string) $to_status) . "',
            from_commission = '" . lc_sql_escape(number_format((float) $from_commission, 2, '.', '')) . "',
            to_commission = '" . lc_sql_escape(number_format((float) $to_commission, 2, '.', '')) . "',
            reason = '" . lc_sql_escape(mb_substr((string) $reason, 0, 500)) . "',
            raw_json = '" . lc_sql_escape((string) $raw_json) . "',
            changed_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceRepository — DB CRUD / 토큰 / 상태 매핑
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_repo_make_click_token')) {
    function lc_lp_repo_make_click_token()
    {
        try {
            return bin2hex(random_bytes(8)); // 16 hex chars
        } catch (Exception $e) {
            return substr(md5(uniqid((string) mt_rand(), true)), 0, 16);
        }
    }
}

if (!function_exists('lc_lp_repo_build_u_id')) {
    /**
     * PII 없는 짧은 u_id: L{pt_id}M{lpm_id}T{token}
     */
    function lc_lp_repo_build_u_id($pt_id, $lpm_id, $click_token)
    {
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $click_token);
        $u_id = 'L' . (int) $pt_id . 'M' . (int) $lpm_id . 'T' . $token;
        if (strlen($u_id) > 80) {
            $u_id = substr($u_id, 0, 80);
        }

        return $u_id;
    }
}

if (!function_exists('lc_lp_repo_parse_u_id')) {
    /**
     * @return array{pt_id:int,lpm_id:int,token:string}|null
     */
    function lc_lp_repo_parse_u_id($u_id)
    {
        if (!preg_match('/^L(\d+)M(\d+)T([A-Za-z0-9]+)$/', (string) $u_id, $m)) {
            return null;
        }

        return array(
            'pt_id'  => (int) $m[1],
            'lpm_id' => (int) $m[2],
            'token'  => (string) $m[3],
        );
    }
}

if (!function_exists('lc_lp_repo_map_raw_status')) {
    /**
     * 링크프라이스 status → LC 내부 상태
     * 원본 코드는 raw_status 에 그대로 저장하고, 이 함수는 내부 상태만 반환.
     *
     * 매핑표:
     *  100 → pending (발생/확인 대기)
     *  200 → review (정산 대기)
     *  210 → approved (최종 확정)
     *  300 → cancel_pending (취소 처리 중)
     *  310 → canceled (최종 취소)
     *  기타 → error (미정의 코드)
     */
    function lc_lp_repo_map_raw_status($raw_status)
    {
        $map = lc_lp_status_map();
        $raw = (string) $raw_status;
        if (isset($map[$raw])) {
            return $map[$raw];
        }
        return LC_LP_ORDER_ERROR;
    }
}

if (!function_exists('lc_lp_status_map')) {
    function lc_lp_status_map()
    {
        return array(
            LC_LP_RAW_NORMAL       => LC_LP_ORDER_PENDING,
            LC_LP_RAW_SETTLE_WAIT  => LC_LP_ORDER_REVIEW,
            LC_LP_RAW_SETTLED      => LC_LP_ORDER_APPROVED,
            LC_LP_RAW_CANCEL_WAIT  => LC_LP_ORDER_CANCEL_PENDING,
            LC_LP_RAW_CANCELLED    => LC_LP_ORDER_CANCELED,
        );
    }
}

if (!function_exists('lc_lp_status_is_approved')) {
    function lc_lp_status_is_approved($lc_status)
    {
        return in_array((string) $lc_status, array(LC_LP_ORDER_APPROVED, LC_LP_ORDER_CONFIRMED), true);
    }
}

if (!function_exists('lc_lp_status_is_canceled')) {
    function lc_lp_status_is_canceled($lc_status)
    {
        return in_array((string) $lc_status, array(LC_LP_ORDER_CANCELED, LC_LP_ORDER_CANCELLED), true);
    }
}

if (!function_exists('lc_lp_repo_calc_shares')) {
    /**
     * @return array{partner_expected:float,partner_confirmed:float,platform_margin:float}
     */
    function lc_lp_repo_calc_shares($lp_commission, $partner_rate, $lc_status)
    {
        $commission = max(0, (float) $lp_commission);
        $rate = max(0, min(100, (float) $partner_rate));
        $partner = round($commission * ($rate / 100), 2);
        $margin = round($commission - $partner, 2);
        // 취소: 예상·확정 모두 0. 확정(approved): confirmed=partner. 그 외: expected만.
        if (lc_lp_status_is_canceled($lc_status)) {
            $partner = 0.0;
            $margin = 0.0;
            $confirmed = 0.0;
        } else {
            $confirmed = lc_lp_status_is_approved($lc_status) ? $partner : 0.0;
        }

        return array(
            'partner_expected'  => $partner,
            'partner_confirmed' => $confirmed,
            'platform_margin'   => $margin,
        );
    }
}

if (!function_exists('lc_lp_repo_pick_field')) {
    /**
     * API 필드명 변경에 대비한 방어적 추출
     */
    function lc_lp_repo_pick_field(array $item, array $keys, $default = '')
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                return $item[$key];
            }
        }

        return $default;
    }
}

if (!function_exists('lc_lp_repo_upsert_merchant')) {
    /**
     * CPS 광고주 INSERT/UPDATE.
     * 관리자 전용 필드(visible, partner_rate, campaign_alias, partner_notice, is_recommended, admin_memo)는 덮어쓰지 않음.
     *
     * @param array $item 광고주 API 한 건
     * @return array{ok:bool,lpm_id:int,is_new:bool,message:string}
     */
    function lc_lp_repo_upsert_merchant(array $item, $network_id = 0, $default_rate = 70.0)
    {
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'lpm_id' => 0, 'is_new' => false, 'message' => 'lp_merchants 없음');
        }

        $code = trim((string) lc_lp_repo_pick_field($item, array('merchant_id', 'merchant_code', 'm_id'), ''));
        if ($code === '') {
            return array('ok' => false, 'lpm_id' => 0, 'is_new' => false, 'message' => 'merchant_id 없음');
        }

        $yn = function ($v, $default = 'N') {
            $s = strtoupper(trim((string) $v));
            if ($s === 'Y' || $s === 'N') {
                return $s;
            }
            return $default;
        };

        $fields = array(
            'network_id'                   => (int) $network_id,
            'merchant_name'                => (string) lc_lp_repo_pick_field($item, array('merchant_name', 'name'), ''),
            'merchant_logo'                => (string) lc_lp_repo_pick_field($item, array('merchant_logo', 'logo'), ''),
            'merchant_url'                 => (string) lc_lp_repo_pick_field($item, array('merchant_url', 'url'), ''),
            'category_id'                  => (string) lc_lp_repo_pick_field($item, array('category_id'), ''),
            'category_name'                => (string) lc_lp_repo_pick_field($item, array('category_name'), ''),
            'commission_pc'                => (string) lc_lp_repo_pick_field($item, array('max_commission_pc', 'commission_pc'), ''),
            'commission_mobile'            => (string) lc_lp_repo_pick_field($item, array('max_commission_mobile', 'commission_mobile'), ''),
            'click_url'                    => (string) lc_lp_repo_pick_field($item, array('click_url'), ''),
            'deeplink_yn'                  => $yn(lc_lp_repo_pick_field($item, array('deeplink_yn'), 'N'), 'N'),
            'mobile_yn'                    => $yn(lc_lp_repo_pick_field($item, array('mobile_yn'), 'Y'), 'Y'),
            'return_day'                   => (int) lc_lp_repo_pick_field($item, array('return_day'), 0),
            'reward_yn'                    => (string) lc_lp_repo_pick_field($item, array('reward_yn'), ''),
            'subscript'                    => strtoupper(trim((string) lc_lp_repo_pick_field($item, array('subscript'), ''))),
            'deny_ad'                      => (string) lc_lp_repo_pick_field($item, array('deny_ad'), ''),
            'deny_product'                 => (string) lc_lp_repo_pick_field($item, array('deny_product'), ''),
            'notice'                       => (string) lc_lp_repo_pick_field($item, array('notice'), ''),
            'when_trans'                   => (string) lc_lp_repo_pick_field($item, array('when_trans'), ''),
            'trans_reposition'             => (string) lc_lp_repo_pick_field($item, array('trans_reposition'), ''),
            'commission_payment_standard'  => (string) lc_lp_repo_pick_field($item, array('commission_payment_standard'), ''),
            'sync_active'                  => 1,
            'raw_json'                     => json_encode($item, JSON_UNESCAPED_UNICODE),
        );

        $existing = lc_sql_fetch(" SELECT lpm_id FROM `{$table}` WHERE merchant_code = '" . lc_sql_escape($code) . "' LIMIT 1 ", false);
        if (is_array($existing) && (int) ($existing['lpm_id'] ?? 0) > 0) {
            $lpm_id = (int) $existing['lpm_id'];
            $sets = array();
            foreach ($fields as $col => $val) {
                $sets[] = "`{$col}` = '" . lc_sql_escape($val) . "'";
            }
            $sets[] = "`synced_at` = NOW()";
            $sets[] = "`updated_at` = NOW()";
            // visible / partner_rate / campaign_alias / partner_notice / is_recommended / admin_memo 는 갱신하지 않음
            $ok = lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE lpm_id = {$lpm_id} ", false);
            return array(
                'ok'      => $ok !== false,
                'lpm_id'  => $lpm_id,
                'is_new'  => false,
                'message' => $ok !== false ? 'updated' : ('update 실패: ' . lc_sql_error()),
            );
        }

        $cols = array('merchant_code', 'partner_rate', 'visible', 'sync_active', 'synced_at', 'created_at', 'updated_at');
        $vals = array(
            "'" . lc_sql_escape($code) . "'",
            "'" . lc_sql_escape(number_format((float) $default_rate, 2, '.', '')) . "'",
            '0', // 신규는 관리자 노출 OFF (수동 승인)
            '1',
            'NOW()',
            'NOW()',
            'NOW()',
        );
        foreach ($fields as $col => $val) {
            if (in_array($col, array('sync_active'), true)) {
                continue; // 이미 포함
            }
            $cols[] = $col;
            $vals[] = "'" . lc_sql_escape($val) . "'";
        }
        $ok = lc_sql_query(" INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ") ", false);
        $lpm_id = (int) lc_sql_insert_id();

        return array(
            'ok'      => $ok !== false && $lpm_id > 0,
            'lpm_id'  => $lpm_id,
            'is_new'  => true,
            'message' => ($ok !== false && $lpm_id > 0) ? 'inserted' : ('insert 실패: ' . lc_sql_error()),
        );
    }
}

if (!function_exists('lc_lp_repo_get_merchant_by_code')) {
    function lc_lp_repo_get_merchant_by_code($merchant_code)
    {
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE merchant_code = '" . lc_sql_escape((string) $merchant_code) . "' LIMIT 1 ", false);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_lp_repo_get_merchant')) {
    function lc_lp_repo_get_merchant($lpm_id)
    {
        $table = lc_table('lp_merchants');
        $lpm_id = (int) $lpm_id;
        if (!lc_db_table_exists($table) || $lpm_id <= 0) {
            return null;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpm_id = {$lpm_id} LIMIT 1 ", false);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_lp_repo_get_order_by_trlog')) {
    function lc_lp_repo_get_order_by_trlog($trlog_id)
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table) || trim((string) $trlog_id) === '') {
            return null;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE trlog_id = '" . lc_sql_escape((string) $trlog_id) . "' LIMIT 1 ", false);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_lp_repo_find_order_business_key')) {
    /**
     * 재생성 머천트용: merchant + order + u_id
     */
    function lc_lp_repo_find_order_business_key($merchant_code, $order_code, $u_id)
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE merchant_code = '" . lc_sql_escape((string) $merchant_code) . "'
              AND order_code = '" . lc_sql_escape((string) $order_code) . "'
              AND u_id = '" . lc_sql_escape((string) $u_id) . "'
            ORDER BY lpo_id DESC LIMIT 1 ", false);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_lp_repo_insert_postback')) {
    /**
     * 원본 POSTBACK 우선 저장 (처리 실패해도 유실 방지)
     *
     * @param string|array $raw_body JSON 문자열 또는 이미 파싱된 배열
     * @return int lpp_id
     */
    function lc_lp_repo_insert_postback($raw_body, $headers_json = '', $ip = '', array $meta = array())
    {
        $table = lc_table('lp_postbacks');
        if (!lc_db_table_exists($table)) {
            return 0;
        }

        if (is_array($raw_body)) {
            $payload = $raw_body;
            $raw = json_encode($raw_body, JSON_UNESCAPED_UNICODE);
        } else {
            $raw = (string) $raw_body;
            $payload = json_decode($raw, true);
            if (!is_array($payload)) {
                $payload = array();
            }
        }

        $trlog = (string) ($meta['trlog_id'] ?? $payload['trlog_id'] ?? '');
        $uniq = (string) ($meta['uniq_id'] ?? $payload['uniq_id'] ?? '');
        $merchant = (string) ($meta['merchant_code'] ?? $payload['merchant_id'] ?? '');
        $order = (string) ($meta['order_code'] ?? $payload['order_code'] ?? '');
        $u_id = (string) ($meta['u_id'] ?? $payload['affiliate_user_id'] ?? $payload['u_id'] ?? '');
        $status = (string) ($meta['process_status'] ?? LC_LP_PB_RECEIVED);

        if (strlen($raw) > 650000) {
            $raw = substr($raw, 0, 650000) . '…';
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            trlog_id = '" . lc_sql_escape(mb_substr($trlog, 0, 30)) . "',
            uniq_id = '" . lc_sql_escape(mb_substr($uniq, 0, 30)) . "',
            merchant_code = '" . lc_sql_escape(mb_substr($merchant, 0, 20)) . "',
            order_code = '" . lc_sql_escape(mb_substr($order, 0, 100)) . "',
            u_id = '" . lc_sql_escape(mb_substr($u_id, 0, 80)) . "',
            request_json = '" . lc_sql_escape($raw) . "',
            request_headers = '" . lc_sql_escape((string) $headers_json) . "',
            request_ip = '" . lc_sql_escape(mb_substr((string) $ip, 0, 45)) . "',
            is_duplicate = 0,
            process_status = '" . lc_sql_escape($status) . "',
            error_message = '',
            match_note = '',
            lpo_id = 0,
            received_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_lp_repo_update_postback')) {
    function lc_lp_repo_update_postback($lpp_id, array $fields)
    {
        $lpp_id = (int) $lpp_id;
        $table = lc_table('lp_postbacks');
        if ($lpp_id <= 0 || !lc_db_table_exists($table)) {
            return;
        }
        $sets = array();
        $map = array(
            'process_status' => 'process_status',
            'error_message'  => 'error_message',
            'match_note'     => 'match_note',
            'lpo_id'         => 'lpo_id',
            'is_duplicate'   => 'is_duplicate',
            'trlog_id'       => 'trlog_id',
            'uniq_id'        => 'uniq_id',
            'merchant_code'  => 'merchant_code',
            'order_code'     => 'order_code',
            'u_id'           => 'u_id',
        );
        foreach ($map as $in => $col) {
            if (!array_key_exists($in, $fields)) {
                continue;
            }
            $val = $fields[$in];
            if ($col === 'lpo_id' || $col === 'is_duplicate') {
                $sets[] = "`{$col}` = " . (int) $val;
            } else {
                $sets[] = "`{$col}` = '" . lc_sql_escape(mb_substr((string) $val, 0, $col === 'error_message' ? 500 : 255)) . "'";
            }
        }
        if (!$sets) {
            return;
        }
        $sets[] = 'processed_at = NOW()';
        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE lpp_id = {$lpp_id} ", false);
    }
}

if (!function_exists('lc_lp_repo_create_click')) {
    /**
     * @return array{ok:bool,message:string,click:array|null}
     */
    function lc_lp_repo_create_click($pt_id, $lpm_id, $landing_url = '')
    {
        $table = lc_table('lp_clicks');
        $merchants = lc_table('lp_merchants');
        if (!lc_db_table_exists($table) || !lc_db_table_exists($merchants)) {
            return array('ok' => false, 'message' => 'CPS 클릭 테이블이 없습니다.', 'click' => null);
        }

        $pt_id = (int) $pt_id;
        $lpm_id = (int) $lpm_id;
        if ($pt_id <= 0 || $lpm_id <= 0) {
            return array('ok' => false, 'message' => '파트너/광고주 정보가 필요합니다.', 'click' => null);
        }

        $merchant = lc_sql_fetch(" SELECT * FROM `{$merchants}` WHERE lpm_id = {$lpm_id} LIMIT 1 ", false);
        if (!is_array($merchant)) {
            return array('ok' => false, 'message' => 'CPS 광고주를 찾을 수 없습니다.', 'click' => null);
        }

        $base_click = trim((string) ($merchant['click_url'] ?? ''));
        if ($base_click === '') {
            return array('ok' => false, 'message' => '광고주 클릭 URL이 없습니다.', 'click' => null);
        }

        $token = lc_lp_repo_make_click_token();
        $u_id = lc_lp_repo_build_u_id($pt_id, $lpm_id, $token);
        $redirect = lc_lp_client_build_click_url(
            $base_click,
            $u_id,
            (string) $landing_url
        );
        if ($redirect === '' || (function_exists('lc_lp_is_safe_redirect_url') && !lc_lp_is_safe_redirect_url($redirect))) {
            return array('ok' => false, 'message' => '안전한 리디렉션 URL을 만들 수 없습니다.', 'click' => null);
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : '';
        $ref = isset($_SERVER['HTTP_REFERER']) ? mb_substr((string) $_SERVER['HTTP_REFERER'], 0, 500) : '';
        $device = 'pc';
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            $device = 'mobile';
        }

        lc_sql_query(" INSERT INTO `{$table}` SET
            click_token = '" . lc_sql_escape($token) . "',
            pt_id = {$pt_id},
            lpm_id = {$lpm_id},
            u_id = '" . lc_sql_escape($u_id) . "',
            landing_url = '" . lc_sql_escape(mb_substr((string) $landing_url, 0, 1000)) . "',
            redirect_url = '" . lc_sql_escape(mb_substr($redirect, 0, 1500)) . "',
            ip = '" . lc_sql_escape($ip) . "',
            user_agent = '" . lc_sql_escape($ua) . "',
            referer = '" . lc_sql_escape($ref) . "',
            device = '" . lc_sql_escape($device) . "',
            clicked_at = NOW() ", false);

        $lpc_id = (int) lc_sql_insert_id();
        $click = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpc_id = {$lpc_id} LIMIT 1 ", false);

        return array('ok' => true, 'message' => '클릭이 생성되었습니다.', 'click' => $click);
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceSync — CPS 광고주 동기화 (CPA 경로 절대 사용 금지)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_sync_lock_path')) {
    function lc_lp_sync_lock_path($name = 'merchants')
    {
        $dir = defined('G5_DATA_PATH') ? G5_DATA_PATH . '/linkconnect' : sys_get_temp_dir() . '/linkconnect';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/lp_sync_' . preg_replace('/[^a-z0-9_]/i', '', (string) $name) . '.lock';
    }
}

if (!function_exists('lc_lp_sync_acquire_lock')) {
    /**
     * @return resource|false
     */
    function lc_lp_sync_acquire_lock($name = 'merchants')
    {
        $path = lc_lp_sync_lock_path($name);
        $fp = @fopen($path, 'c+');
        if (!$fp) {
            return false;
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }
        ftruncate($fp, 0);
        fwrite($fp, (string) getmypid() . ' ' . date('c') . "\n");
        fflush($fp);

        return $fp;
    }
}

if (!function_exists('lc_lp_sync_release_lock')) {
    function lc_lp_sync_release_lock($fp)
    {
        if (is_resource($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}

if (!function_exists('lc_lp_sync_deactivate_missing')) {
    /**
     * API에 없는 광고주는 삭제하지 않고 sync_active=0
     *
     * @param array $seen_codes merchant_code 목록
     * @return int deactivated count
     */
    function lc_lp_sync_deactivate_missing(array $seen_codes, $network_id = 0)
    {
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table) || !$seen_codes) {
            return 0;
        }

        $escaped = array();
        foreach ($seen_codes as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $escaped[] = "'" . lc_sql_escape($code) . "'";
            }
        }
        if (!$escaped) {
            return 0;
        }

        $where_net = $network_id > 0 ? (' AND network_id = ' . (int) $network_id) : '';
        $before = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}`
            WHERE sync_active = 1
              AND merchant_code NOT IN (" . implode(',', $escaped) . ")
              {$where_net} ", false);
        $cnt = (int) ($before['cnt'] ?? 0);
        if ($cnt <= 0) {
            return 0;
        }

        lc_sql_query(" UPDATE `{$table}` SET sync_active = 0, updated_at = NOW()
            WHERE sync_active = 1
              AND merchant_code NOT IN (" . implode(',', $escaped) . ")
              {$where_net} ", false);

        return $cnt;
    }
}

if (!function_exists('lc_lp_sync_merchants')) {
    /**
     * CPS 광고주 동기화 메인
     *
     * @param array $options scope(all|apr), detail, test_mode, deactivate_missing, skip_lock
     * @return array
     */
    function lc_lp_sync_merchants(array $options = array())
    {
        $scope = isset($options['scope']) && $options['scope'] === 'all' ? 'all' : 'apr';
        $detail = !isset($options['detail']) || !empty($options['detail']);
        $test_mode = !empty($options['test_mode']);
        $deactivate = !isset($options['deactivate_missing']) || !empty($options['deactivate_missing']);

        $result = array(
            'ok'            => false,
            'message'       => '',
            'scope'         => $scope,
            'fetched'       => 0,
            'inserted'      => 0,
            'updated'       => 0,
            'failed'        => 0,
            'deactivated'   => 0,
            'errors'        => array(),
            'sample_fields' => array(),
            'cpa_excluded'  => true,
            'api_url'       => '',
            'log_id'        => 0,
            'synced_at'     => null,
        );

        if (!lc_db_table_exists(lc_table('lp_merchants'))) {
            $result['message'] = 'lp_merchants 테이블이 없습니다. 마이그레이션을 먼저 적용하세요.';
            return $result;
        }

        $cfg = lc_lp_config_get();
        if (trim((string) ($cfg['affiliate_code'] ?? '')) === '') {
            $result['message'] = 'A코드(affiliate_code)가 설정되지 않았습니다.';
            return $result;
        }

        $lock = null;
        if (empty($options['skip_lock'])) {
            $lock = lc_lp_sync_acquire_lock('merchants');
            if ($lock === false) {
                $result['message'] = '다른 동기화가 진행 중입니다. 잠시 후 다시 시도하세요.';
                return $result;
            }
        }

        $log_id = 0;
        try {
            $fetch_opts = array(
                'timeout'   => isset($options['timeout']) ? (int) $options['timeout'] : 45,
                'retries'   => isset($options['retries']) ? (int) $options['retries'] : 2,
                'test_mode' => $test_mode,
            );
            $res = lc_lp_client_fetch_merchants($scope, $detail, $fetch_opts);
            $result['api_url'] = (string) ($res['url'] ?? '');
            $log_id = lc_lp_log_sync_start(LC_LP_SYNC_MERCHANTS, $result['api_url'], json_encode(array(
                'scope' => $scope, 'detail' => $detail, 'test_mode' => $test_mode,
            ), JSON_UNESCAPED_UNICODE));
            $result['log_id'] = $log_id;

            // CPA 경로가 URL에 없는지 검증
            if (stripos($result['api_url'], '/cpa') !== false) {
                lc_lp_log_sync_finish($log_id, false, (int) ($res['status'] ?? 0), '', 0, 'CPA 경로 감지 — 중단');
                $result['message'] = '내부 오류: CPA 경로가 감지되어 동기화를 중단했습니다.';
                $result['cpa_excluded'] = false;
                return $result;
            }

            if (empty($res['ok'])) {
                $msg = (string) ($res['message'] ?? 'API 호출 실패');
                lc_lp_log_sync_finish($log_id, false, (int) ($res['status'] ?? 0), (string) ($res['raw'] ?? ''), 0, $msg);
                $result['message'] = $msg;
                // 실패 시 기존 데이터 삭제/비활성 하지 않음
                return $result;
            }

            $list = is_array($res['data']) ? $res['data'] : array();
            $result['fetched'] = count($list);
            if ($list && is_array($list[0])) {
                $result['sample_fields'] = array_keys($list[0]);
            }

            $network_id = (int) ($cfg['network_id'] ?? 0);
            $default_rate = (float) ($cfg['default_partner_rate'] ?? 70);
            $seen = array();

            foreach ($list as $idx => $item) {
                if (!is_array($item)) {
                    $result['failed']++;
                    $result['errors'][] = 'row#' . $idx . ': not an object';
                    continue;
                }
                try {
                    $up = lc_lp_repo_upsert_merchant($item, $network_id, $default_rate);
                    if (empty($up['ok'])) {
                        $result['failed']++;
                        $code = (string) lc_lp_repo_pick_field($item, array('merchant_id', 'merchant_code'), 'row#' . $idx);
                        $result['errors'][] = $code . ': ' . (string) ($up['message'] ?? 'upsert 실패');
                        continue;
                    }
                    $code = trim((string) lc_lp_repo_pick_field($item, array('merchant_id', 'merchant_code'), ''));
                    if ($code !== '') {
                        $seen[] = $code;
                    }
                    if (!empty($up['is_new'])) {
                        $result['inserted']++;
                    } else {
                        $result['updated']++;
                    }
                } catch (Exception $e) {
                    $result['failed']++;
                    $result['errors'][] = 'row#' . $idx . ': ' . $e->getMessage();
                } catch (Throwable $e) {
                    $result['failed']++;
                    $result['errors'][] = 'row#' . $idx . ': ' . $e->getMessage();
                }
            }

            if ($deactivate && $seen) {
                $result['deactivated'] = lc_lp_sync_deactivate_missing($seen, $network_id);
            }

            $processed = $result['inserted'] + $result['updated'];
            $ok = $result['fetched'] > 0 || $processed > 0;
            // 전체 API 성공이면 fetched=0 도 성공으로 간주 (빈 승인 목록)
            if (!empty($res['ok'])) {
                $ok = true;
            }

            $summary = sprintf(
                'fetched=%d inserted=%d updated=%d failed=%d deactivated=%d',
                $result['fetched'], $result['inserted'], $result['updated'], $result['failed'], $result['deactivated']
            );
            $err_tail = $result['errors'] ? implode('; ', array_slice($result['errors'], 0, 10)) : '';
            lc_lp_log_sync_finish(
                $log_id,
                $ok,
                (int) ($res['status'] ?? 200),
                substr((string) ($res['raw'] ?? ''), 0, 8000),
                $processed,
                $err_tail !== '' ? $err_tail : $summary
            );

            if ($ok) {
                lc_lp_config_touch_sync('merchants');
            }

            $result['ok'] = $ok;
            $result['message'] = $ok
                ? ('CPS 광고주 동기화 완료. ' . $summary)
                : ('동기화 부분 실패. ' . $summary);
            $result['synced_at'] = date('Y-m-d H:i:s');
            $result['cpa_excluded'] = true;

            return $result;
        } finally {
            if ($lock) {
                lc_lp_sync_release_lock($lock);
            }
        }
    }
}

if (!function_exists('lc_lp_is_linkprice_brand_merchant')) {
    /**
     * 링크프라이스 네트워크 자체(광고주 엔트리) 여부 — 공개 목록·로고 노출 제외
     */
    function lc_lp_is_linkprice_brand_merchant(array $row)
    {
        $code = strtolower(trim((string) ($row['merchant_code'] ?? '')));
        if (in_array($code, array('linkprice', 'lp'), true)) {
            return true;
        }

        foreach (array('merchant_name', 'campaign_alias') as $key) {
            $name = trim((string) ($row[$key] ?? ''));
            if ($name === '') {
                continue;
            }
            if (preg_match('/^(링크프라이스|link\s*price)$/iu', $name)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('lc_lp_merchant_logo_is_linkprice_brand')) {
    /**
     * merchant_logo URL이 링크프라이스 네트워크 브랜드 이미지인지 판별
     */
    function lc_lp_merchant_logo_is_linkprice_brand($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        $lower = strtolower($url);
        if (preg_match('~/(?:linkprice|lp)[_-]?(?:logo|brand)(?:[._/\-\?#]|$)~i', $lower)) {
            return true;
        }
        if (preg_match('~/(?:logo/)?linkprice\.(?:png|jpe?g|gif|webp|svg)(?:\?|#|$)~i', $lower)) {
            return true;
        }
        if (preg_match('~/(?:default|common)/linkprice~i', $lower)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('lc_lp_merchant_public_logo')) {
    /**
     * 파트너·공개 화면용 로고 — 링크프라이스 브랜드 이미지는 빈 문자열
     */
    function lc_lp_merchant_public_logo(array $row)
    {
        if (lc_lp_is_linkprice_brand_merchant($row)) {
            return '';
        }

        $logo = trim((string) ($row['merchant_logo'] ?? ''));
        if ($logo === '' || lc_lp_merchant_logo_is_linkprice_brand($logo)) {
            return '';
        }

        return $logo;
    }
}

if (!function_exists('lc_lp_merchant_public_listable')) {
    function lc_lp_merchant_public_listable(array $row)
    {
        return !lc_lp_is_linkprice_brand_merchant($row);
    }
}

if (!function_exists('lc_lp_merchant_partner_visible')) {
    /**
     * 파트너 화면 노출 조건:
     * CPS + LP 승인(APR) + LC visible + sync_active + click_url
     */
    function lc_lp_merchant_partner_visible(array $row)
    {
        return strtoupper(trim((string) ($row['subscript'] ?? ''))) === 'APR'
            && !empty($row['visible'])
            && !empty($row['sync_active'])
            && trim((string) ($row['click_url'] ?? '')) !== '';
    }
}

if (!function_exists('lc_lp_merchant_to_api')) {
    function lc_lp_merchant_to_api(array $row, $include_raw = false)
    {
        $item = array(
            'lpmId'              => (int) ($row['lpm_id'] ?? 0),
            'networkId'          => (int) ($row['network_id'] ?? 0),
            'merchantCode'       => (string) ($row['merchant_code'] ?? ''),
            'merchantName'       => (string) ($row['merchant_name'] ?? ''),
            'merchantLogo'       => (string) ($row['merchant_logo'] ?? ''),
            'merchantUrl'        => (string) ($row['merchant_url'] ?? ''),
            'categoryId'         => (string) ($row['category_id'] ?? ''),
            'categoryName'       => (string) ($row['category_name'] ?? ''),
            'commissionPc'       => (string) ($row['commission_pc'] ?? ''),
            'commissionMobile'   => (string) ($row['commission_mobile'] ?? ''),
            'clickUrl'           => (string) ($row['click_url'] ?? ''),
            'deeplinkYn'         => (string) ($row['deeplink_yn'] ?? 'N'),
            'mobileYn'           => (string) ($row['mobile_yn'] ?? 'Y'),
            'returnDay'          => (int) ($row['return_day'] ?? 0),
            'rewardYn'           => (string) ($row['reward_yn'] ?? ''),
            'subscript'          => (string) ($row['subscript'] ?? ''),
            'denyAd'             => (string) ($row['deny_ad'] ?? ''),
            'denyProduct'        => (string) ($row['deny_product'] ?? ''),
            'notice'             => (string) ($row['notice'] ?? ''),
            'whenTrans'          => (string) ($row['when_trans'] ?? ''),
            'transReposition'    => (string) ($row['trans_reposition'] ?? ''),
            'commissionPaymentStandard' => (string) ($row['commission_payment_standard'] ?? ''),
            'visible'            => !empty($row['visible']),
            'syncActive'         => !empty($row['sync_active']),
            'partnerRate'        => (float) ($row['partner_rate'] ?? 70),
            'campaignAlias'      => (string) ($row['campaign_alias'] ?? ''),
            'partnerNotice'      => (string) ($row['partner_notice'] ?? ''),
            'isRecommended'      => !empty($row['is_recommended']),
            'adminMemo'          => (string) ($row['admin_memo'] ?? ''),
            'syncedAt'           => $row['synced_at'] ?? null,
            'createdAt'          => $row['created_at'] ?? null,
            'updatedAt'          => $row['updated_at'] ?? null,
            'partnerVisible'     => lc_lp_merchant_partner_visible($row),
            'clicks'             => (int) ($row['_stats_clicks'] ?? 0),
            'expectedOrders'     => (int) ($row['_stats_expected'] ?? 0),
            'confirmedOrders'    => (int) ($row['_stats_confirmed'] ?? 0),
            'canceledOrders'     => (int) ($row['_stats_canceled'] ?? 0),
            'partnerDisplayCommission' => lc_lp_merchant_partner_display_commission($row),
        );
        if ($include_raw) {
            $raw = (string) ($row['raw_json'] ?? '');
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $item['raw'] = is_array($decoded) ? $decoded : $raw;
        }

        return $item;
    }
}

if (!function_exists('lc_lp_merchants_list')) {
    /**
     * @param array $filters approved, sync_active, visible, deeplink, q, code, limit, offset
     * @return array{items:array,total:int}
     */
    function lc_lp_merchants_list(array $filters = array())
    {
        $table = lc_table('lp_merchants');
        if (!lc_db_table_exists($table)) {
            return array('items' => array(), 'total' => 0);
        }

        $where = array('1=1');
        if (isset($filters['approved']) && $filters['approved'] !== '' && $filters['approved'] !== null) {
            if (!empty($filters['approved'])) {
                $where[] = "subscript = 'APR'";
            }
        }
        if (isset($filters['sync_active']) && $filters['sync_active'] !== '' && $filters['sync_active'] !== null) {
            $where[] = 'sync_active = ' . (!empty($filters['sync_active']) ? 1 : 0);
        }
        if (isset($filters['visible']) && $filters['visible'] !== '' && $filters['visible'] !== null) {
            $where[] = 'visible = ' . (!empty($filters['visible']) ? 1 : 0);
        }
        if (!empty($filters['deeplink'])) {
            $where[] = "deeplink_yn = 'Y'";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape('%' . trim((string) $filters['q']) . '%');
            $where[] = "(merchant_name LIKE '{$q}' OR campaign_alias LIKE '{$q}')";
        }
        if (!empty($filters['code'])) {
            $code = lc_sql_escape(trim((string) $filters['code']));
            $where[] = "merchant_code LIKE '%{$code}%'";
        }
        if (!empty($filters['partner_visible'])) {
            $where[] = "subscript = 'APR' AND visible = 1 AND sync_active = 1 AND click_url <> ''";
            $where[] = "LOWER(merchant_code) NOT IN ('linkprice', 'lp')";
        }

        $where_sql = implode(' AND ', $where);
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where_sql} ", false);
        $total = (int) ($total_row['cnt'] ?? 0);

        $limit = isset($filters['limit']) ? max(1, min(500, (int) $filters['limit'])) : 200;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;

        $rows = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where_sql}
            ORDER BY is_recommended DESC, merchant_name ASC, lpm_id ASC
            LIMIT {$offset}, {$limit} ", false);

        $items = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $items[] = $row;
            }
        }

        return array('items' => $items, 'total' => $total);
    }
}

if (!function_exists('lc_lp_public_brand_copy')) {
    /** 파트너·공개 문구에서 외부망 브랜드명을 링크커넥트로 치환 */
    function lc_lp_public_brand_copy($text)
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        $pairs = array(
            '링크프라이스' => '링크커넥트',
            'LinkPrice'   => '링크커넥트',
            'Linkprice'   => '링크커넥트',
            'linkprice'   => '링크커넥트',
            'LINKPRICE'   => '링크커넥트',
        );

        return strtr($text, $pairs);
    }
}

if (!function_exists('lc_lp_format_commission_rate_display')) {
    /** CPS 수수료는 % 단위로 통일 표기 */
    function lc_lp_format_commission_rate_display($raw)
    {
        $s = trim((string) $raw);
        if ($s === '' || $s === '-') {
            return '-';
        }

        // "3.5% × 70%" → 앞 비율만
        if (preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*%?\s*[×xX]\s*/u', $s, $m)) {
            return $m[1] . '%';
        }

        // "3.5원" / "3.5 원" 처럼 %인데 원으로 오는 경우
        if (preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*원\s*$/u', $s, $m)) {
            return $m[1] . '%';
        }

        // 숫자만 → %
        if (preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*$/u', $s, $m)) {
            return $m[1] . '%';
        }

        // 이미 % 포함이면 잘못된 꼬리 '원'만 제거
        $s = preg_replace('/\s*원\s*$/u', '', $s);
        if (strpos($s, '%') === false && preg_match('/[0-9]/', $s)) {
            // "최대 3.5" 형태
            if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*$/u', $s, $m)) {
                return preg_replace('/([0-9]+(?:\.[0-9]+)?)\s*$/u', $m[1] . '%', $s);
            }
        }

        return trim((string) $s);
    }
}

if (!function_exists('lc_lp_merchant_partner_display_commission')) {
    /** 파트너·공개 노출용 커미션 안내 (머천트 최대 수수료율만, % 통일) */
    function lc_lp_merchant_partner_display_commission(array $row)
    {
        $pc = trim((string) ($row['commission_pc'] ?? ''));
        $mo = trim((string) ($row['commission_mobile'] ?? ''));
        $base = $mo !== '' ? $mo : $pc;

        return lc_lp_format_commission_rate_display($base);
    }
}

if (!function_exists('lc_lp_merchant_update_admin')) {
    /**
     * 관리자 전용 필드만 갱신
     *
     * @return array{ok:bool,message:string,merchant:array|null}
     */
    function lc_lp_merchant_update_admin($lpm_id, array $values)
    {
        $lpm_id = (int) $lpm_id;
        $table = lc_table('lp_merchants');
        if ($lpm_id <= 0 || !lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'merchant' => null);
        }

        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpm_id = {$lpm_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'merchant' => null);
        }

        $sets = array();
        if (array_key_exists('visible', $values)) {
            $sets[] = 'visible = ' . (!empty($values['visible']) ? 1 : 0);
        }
        if (array_key_exists('partner_rate', $values) || array_key_exists('partnerRate', $values)) {
            $rate = (float) ($values['partner_rate'] ?? $values['partnerRate'] ?? 70);
            if ($rate < 0) {
                $rate = 0;
            }
            if ($rate > 100) {
                $rate = 100;
            }
            $sets[] = "partner_rate = '" . lc_sql_escape(number_format($rate, 2, '.', '')) . "'";
        }
        if (array_key_exists('campaign_alias', $values) || array_key_exists('campaignAlias', $values)) {
            $alias = trim((string) ($values['campaign_alias'] ?? $values['campaignAlias'] ?? ''));
            $sets[] = "campaign_alias = '" . lc_sql_escape(mb_substr($alias, 0, 200)) . "'";
        }
        if (array_key_exists('partner_notice', $values) || array_key_exists('partnerNotice', $values)) {
            $notice = (string) ($values['partner_notice'] ?? $values['partnerNotice'] ?? '');
            $sets[] = "partner_notice = '" . lc_sql_escape($notice) . "'";
        }
        if (array_key_exists('is_recommended', $values) || array_key_exists('isRecommended', $values)) {
            $rec = !empty($values['is_recommended'] ?? $values['isRecommended'] ?? false) ? 1 : 0;
            $sets[] = 'is_recommended = ' . $rec;
        }
        if (array_key_exists('admin_memo', $values) || array_key_exists('adminMemo', $values)) {
            $memo = trim((string) ($values['admin_memo'] ?? $values['adminMemo'] ?? ''));
            $sets[] = "admin_memo = '" . lc_sql_escape(mb_substr($memo, 0, 500)) . "'";
        }

        if (!$sets) {
            return array('ok' => false, 'message' => '변경할 항목이 없습니다.', 'merchant' => $row);
        }

        $sets[] = 'updated_at = NOW()';
        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE lpm_id = {$lpm_id} ", false);
        $fresh = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpm_id = {$lpm_id} LIMIT 1 ", false);

        return array('ok' => true, 'message' => '저장되었습니다.', 'merchant' => $fresh);
    }
}

if (!function_exists('lc_lp_sync_logs_recent')) {
    function lc_lp_sync_logs_recent($limit = 10, $sync_type = '')
    {
        $table = lc_table('lp_sync_logs');
        if (!lc_db_table_exists($table)) {
            return array();
        }
        $limit = max(1, min(50, (int) $limit));
        $where = '1=1';
        if ($sync_type !== '') {
            $where .= " AND sync_type = '" . lc_sql_escape($sync_type) . "'";
        }
        $rows = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where} ORDER BY lps_id DESC LIMIT {$limit} ", false);
        $out = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $out[] = array(
                    'lpsId'          => (int) ($row['lps_id'] ?? 0),
                    'syncType'       => (string) ($row['sync_type'] ?? ''),
                    'requestUrl'     => (string) ($row['request_url'] ?? ''),
                    'responseCode'   => (int) ($row['response_code'] ?? 0),
                    'success'        => !empty($row['success']),
                    'processedCount' => (int) ($row['processed_count'] ?? 0),
                    'errorMessage'   => (string) ($row['error_message'] ?? ''),
                    'startedAt'      => $row['started_at'] ?? null,
                    'finishedAt'     => $row['finished_at'] ?? null,
                );
            }
        }

        return $out;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceClick — 파트너 홍보 URL / 클릭 추적 / 딥링크 (CPA /r 과 분리)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_click_secret')) {
    function lc_lp_click_secret()
    {
        $cfg = function_exists('lc_lp_config_get') ? lc_lp_config_get() : array();
        $secret = trim((string) ($cfg['postback_secret'] ?? ''));
        if ($secret !== '') {
            return $secret;
        }
        if (defined('G5_TOKEN_ENCRYPTION_KEY') && G5_TOKEN_ENCRYPTION_KEY !== '') {
            return (string) G5_TOKEN_ENCRYPTION_KEY;
        }
        // 설치별 파생 시크릿 — 고정 문자열 하드코딩 금지
        $parts = array('lc-lp-click');
        if (defined('G5_MYSQL_HOST')) {
            $parts[] = (string) G5_MYSQL_HOST;
        }
        if (defined('G5_MYSQL_DB')) {
            $parts[] = (string) G5_MYSQL_DB;
        }
        if (defined('G5_MYSQL_PASSWORD') && G5_MYSQL_PASSWORD !== '') {
            $parts[] = (string) G5_MYSQL_PASSWORD;
        }
        if (!empty($cfg['affiliate_code'])) {
            $parts[] = (string) $cfg['affiliate_code'];
        }
        return hash('sha256', implode('|', $parts));
    }
}

if (!function_exists('lc_lp_partner_public_token')) {
    /**
     * 조작 방지용 파트너 공개 토큰 (pt_id 단독 사용 금지)
     * 형식: {pt_id}.{hmac8}
     */
    function lc_lp_partner_public_token($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return '';
        }
        $sig = substr(hash_hmac('sha256', 'lp:pt:' . $pt_id, lc_lp_click_secret()), 0, 16);

        return $pt_id . '.' . $sig;
    }
}

if (!function_exists('lc_lp_partner_resolve_token')) {
    /**
     * @return array|null partner row
     */
    function lc_lp_partner_resolve_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }

        $pt_id = 0;
        if (preg_match('/^(\d+)\.([a-f0-9]{16})$/i', $token, $m)) {
            $pt_id = (int) $m[1];
            $expect = substr(hash_hmac('sha256', 'lp:pt:' . $pt_id, lc_lp_click_secret()), 0, 16);
            if (!hash_equals(strtolower($expect), strtolower($m[2]))) {
                return null;
            }
        } elseif (preg_match('/^PTN-\d+$/i', $token)) {
            // 하위 호환: pt_code (서명 토큰 권장)
            $table = lc_table('partners');
            if (!lc_db_table_exists($table)) {
                return null;
            }
            $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE pt_code = '" . lc_sql_escape($token) . "' LIMIT 1 ", false);
            return is_array($row) ? $row : null;
        } else {
            return null;
        }

        return function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($pt_id) : null;
    }
}

if (!function_exists('lc_lp_public_promo_url')) {
    /**
     * 파트너에게 노출하는 LC 추적 URL (원본 LP 링크 비노출)
     */
    function lc_lp_public_promo_url($merchant_code, $pt_id, $deeplink_url = '')
    {
        $merchant_code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $merchant_code);
        $token = lc_lp_partner_public_token((int) $pt_id);
        if ($merchant_code === '' || $token === '') {
            return '';
        }
        $base = (defined('G5_URL') ? rtrim(G5_URL, '/') : '') . '/go/lp/' . rawurlencode($merchant_code);
        $query = array('p' => $token);
        $deeplink_url = trim((string) $deeplink_url);
        if ($deeplink_url !== '') {
            $query['u'] = $deeplink_url;
        }

        return $base . '?' . http_build_query($query);
    }
}

if (!function_exists('lc_lp_host_normalize')) {
    function lc_lp_host_normalize($host)
    {
        $host = strtolower(trim((string) $host));
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }
}

if (!function_exists('lc_lp_validate_deeplink')) {
    /**
     * @return array{ok:bool,message:string,url:string}
     */
    function lc_lp_validate_deeplink($deeplink_url, array $merchant)
    {
        $url = trim((string) $deeplink_url);
        if ($url === '') {
            return array('ok' => true, 'message' => '', 'url' => '');
        }

        if (strtoupper((string) ($merchant['deeplink_yn'] ?? 'N')) !== 'Y') {
            return array('ok' => false, 'message' => '이 광고주는 딥링크를 지원하지 않습니다.', 'url' => '');
        }

        if (preg_match('/^\s*(javascript|data|file|vbscript|about)\s*:/i', $url)) {
            return array('ok' => false, 'message' => '허용되지 않은 URL 스킴입니다.', 'url' => '');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return array('ok' => false, 'message' => '올바른 http(s) URL을 입력하세요.', 'url' => '');
        }
        $scheme = strtolower((string) $parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return array('ok' => false, 'message' => 'http 또는 https URL만 사용할 수 있습니다.', 'url' => '');
        }

        $deeplink_host = lc_lp_host_normalize($parts['host']);
        $merchant_url = trim((string) ($merchant['merchant_url'] ?? ''));
        $allowed = array();
        if ($merchant_url !== '') {
            $mp = parse_url($merchant_url);
            if (!empty($mp['host'])) {
                $allowed[] = lc_lp_host_normalize($mp['host']);
            }
        }
        // click_url 의 m= 파라미터 도메인은 광고주 코드일 수 있어 host 검증에는 merchant_url 우선
        if (!$allowed) {
            return array('ok' => false, 'message' => '광고주 도메인 정보가 없어 딥링크를 검증할 수 없습니다.', 'url' => '');
        }

        $ok_host = false;
        foreach ($allowed as $ah) {
            if ($deeplink_host === $ah || substr($deeplink_host, -strlen('.' . $ah)) === ('.' . $ah)) {
                $ok_host = true;
                break;
            }
            // 광고주 도메인이 딥링크의 상위인 경우 (shop.example.com vs example.com)
            if ($ah !== '' && (substr($ah, -strlen('.' . $deeplink_host)) === ('.' . $deeplink_host))) {
                $ok_host = true;
                break;
            }
        }
        if (!$ok_host) {
            return array(
                'ok'      => false,
                'message' => '상품 URL 도메인이 광고주 도메인(' . $allowed[0] . ')과 일치하지 않습니다.',
                'url'     => '',
            );
        }

        return array('ok' => true, 'message' => '', 'url' => $url);
    }
}

if (!function_exists('lc_lp_is_safe_redirect_url')) {
    /** Open Redirect 방지 — 링크프라이스 클릭 도메인만 허용 */
    function lc_lp_is_safe_redirect_url($url)
    {
        $parts = parse_url((string) $url);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }
        $host = strtolower((string) $parts['host']);
        $allowed = array('click.linkprice.com', 'api.linkprice.com');

        return in_array($host, $allowed, true);
    }
}

if (!function_exists('lc_lp_click_rate_limited')) {
    /**
     * @return array{limited:bool,reuse:array|null}
     */
    function lc_lp_click_rate_limited($pt_id, $lpm_id, $ip, $landing_url = '')
    {
        $table = lc_table('lp_clicks');
        if (!lc_db_table_exists($table)) {
            return array('limited' => false, 'reuse' => null);
        }
        $pt_id = (int) $pt_id;
        $lpm_id = (int) $lpm_id;
        $ip_esc = lc_sql_escape((string) $ip);
        $land_esc = lc_sql_escape(mb_substr((string) $landing_url, 0, 1000));

        // 2초 이내 동일 지문(파트너·광고주·IP·딥링크) → 기존 클릭 재사용
        $recent = lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE pt_id = {$pt_id} AND lpm_id = {$lpm_id} AND ip = '{$ip_esc}'
              AND landing_url = '{$land_esc}'
              AND clicked_at >= DATE_SUB(NOW(), INTERVAL 2 SECOND)
            ORDER BY lpc_id DESC LIMIT 1 ", false);
        if (is_array($recent)) {
            return array('limited' => false, 'reuse' => $recent);
        }

        // 분당 과도 클릭 차단
        $cnt_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}`
            WHERE pt_id = {$pt_id} AND lpm_id = {$lpm_id} AND ip = '{$ip_esc}'
              AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) ", false);
        $cnt = (int) ($cnt_row['cnt'] ?? 0);
        if ($cnt >= 30) {
            return array('limited' => true, 'reuse' => null);
        }

        return array('limited' => false, 'reuse' => null);
    }
}

if (!function_exists('lc_lp_log_redirect_fail')) {
    function lc_lp_log_redirect_fail($reason, array $context = array())
    {
        $dir = defined('G5_DATA_PATH') ? G5_DATA_PATH . '/linkconnect' : sys_get_temp_dir() . '/linkconnect';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = date('c') . ' ' . $reason . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($dir . '/lp_redirect_fail.log', $line, FILE_APPEND);

        if (function_exists('lc_lp_log_sync_start') && function_exists('lc_lp_log_sync_finish')) {
            $id = lc_lp_log_sync_start('redirect_fail', (string) ($context['request'] ?? ''), json_encode($context, JSON_UNESCAPED_UNICODE));
            lc_lp_log_sync_finish($id, false, 0, '', 0, mb_substr((string) $reason, 0, 500));
        }
    }
}

if (!function_exists('lc_lp_handle_public_click')) {
    /**
     * /go/lp/{merchant_code}?p=token&u=deeplink
     *
     * @return array{ok:bool,message:string,redirect:string,click:array|null,http:int}
     */
    function lc_lp_handle_public_click($merchant_code, $partner_token, $deeplink_url = '')
    {
        $merchant_code = trim((string) $merchant_code);
        $partner_token = trim((string) $partner_token);
        $deeplink_url = trim((string) $deeplink_url);

        if ($merchant_code === '' || !preg_match('/^[A-Za-z0-9_-]{1,40}$/', $merchant_code)) {
            lc_lp_log_redirect_fail('invalid_merchant_code', array('merchant' => $merchant_code));
            return array('ok' => false, 'message' => '유효하지 않은 광고주입니다.', 'redirect' => '', 'click' => null, 'http' => 404);
        }
        if ($partner_token === '') {
            lc_lp_log_redirect_fail('missing_partner_token', array('merchant' => $merchant_code));
            return array('ok' => false, 'message' => '파트너 정보가 없습니다.', 'redirect' => '', 'click' => null, 'http' => 400);
        }

        $partner = lc_lp_partner_resolve_token($partner_token);
        if (!is_array($partner)) {
            lc_lp_log_redirect_fail('partner_not_found', array('merchant' => $merchant_code));
            return array('ok' => false, 'message' => '존재하지 않는 파트너입니다.', 'redirect' => '', 'click' => null, 'http' => 403);
        }
        $status = (string) ($partner['pt_status'] ?? '');
        if ($status !== LC_PARTNER_STATUS_ACTIVE) {
            lc_lp_log_redirect_fail('partner_inactive', array('pt_id' => (int) $partner['pt_id'], 'status' => $status));
            return array('ok' => false, 'message' => '정지되었거나 승인되지 않은 파트너입니다.', 'redirect' => '', 'click' => null, 'http' => 403);
        }

        $merchant = lc_lp_repo_get_merchant_by_code($merchant_code);
        if (!is_array($merchant)) {
            lc_lp_log_redirect_fail('merchant_not_found', array('merchant' => $merchant_code));
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.', 'redirect' => '', 'click' => null, 'http' => 404);
        }
        if (!lc_lp_merchant_partner_visible($merchant)) {
            lc_lp_log_redirect_fail('merchant_not_visible', array(
                'merchant' => $merchant_code,
                'subscript' => $merchant['subscript'] ?? '',
                'visible' => $merchant['visible'] ?? 0,
                'sync_active' => $merchant['sync_active'] ?? 0,
            ));
            return array('ok' => false, 'message' => '비활성이거나 승인되지 않은 광고주입니다.', 'redirect' => '', 'click' => null, 'http' => 403);
        }

        $dl = lc_lp_validate_deeplink($deeplink_url, $merchant);
        if (!$dl['ok']) {
            lc_lp_log_redirect_fail('bad_deeplink', array('merchant' => $merchant_code, 'msg' => $dl['message']));
            return array('ok' => false, 'message' => $dl['message'], 'redirect' => '', 'click' => null, 'http' => 400);
        }

        $pt_id = (int) $partner['pt_id'];
        $lpm_id = (int) $merchant['lpm_id'];
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        $rate = lc_lp_click_rate_limited($pt_id, $lpm_id, $ip, (string) $dl['url']);
        if (!empty($rate['limited'])) {
            lc_lp_log_redirect_fail('rate_limited', array('pt_id' => $pt_id, 'lpm_id' => $lpm_id, 'ip' => $ip));
            return array('ok' => false, 'message' => '요청이 너무 많습니다. 잠시 후 다시 시도하세요.', 'redirect' => '', 'click' => null, 'http' => 429);
        }

        if (is_array($rate['reuse']) && !empty($rate['reuse']['redirect_url'])) {
            $redirect = (string) $rate['reuse']['redirect_url'];
            if (!lc_lp_is_safe_redirect_url($redirect)) {
                lc_lp_log_redirect_fail('unsafe_reuse_redirect', array('url' => $redirect));
                return array('ok' => false, 'message' => '리디렉션 대상이 안전하지 않습니다.', 'redirect' => '', 'click' => null, 'http' => 500);
            }
            return array('ok' => true, 'message' => 'OK', 'redirect' => $redirect, 'click' => $rate['reuse'], 'http' => 302);
        }

        $created = lc_lp_repo_create_click($pt_id, $lpm_id, (string) $dl['url']);
        if (empty($created['ok']) || !is_array($created['click'])) {
            lc_lp_log_redirect_fail('create_click_failed', array('msg' => $created['message'] ?? ''));
            return array('ok' => false, 'message' => (string) ($created['message'] ?? '클릭 기록 실패'), 'redirect' => '', 'click' => null, 'http' => 500);
        }

        $redirect = (string) ($created['click']['redirect_url'] ?? '');
        if ($redirect === '' || !lc_lp_is_safe_redirect_url($redirect)) {
            lc_lp_log_redirect_fail('unsafe_or_empty_redirect', array('url' => $redirect, 'lpc_id' => $created['click']['lpc_id'] ?? 0));
            return array('ok' => false, 'message' => '리디렉션 URL 생성에 실패했습니다.', 'redirect' => '', 'click' => $created['click'], 'http' => 500);
        }

        return array('ok' => true, 'message' => 'OK', 'redirect' => $redirect, 'click' => $created['click'], 'http' => 302);
    }
}

if (!function_exists('lc_lp_merchant_to_partner_api')) {
    /**
     * 파트너 노출용 — 원본 click_url 절대 포함하지 않음
     */
    function lc_lp_merchant_to_partner_api(array $row, $pt_id, array $stats = array())
    {
        $name = (string) ($row['campaign_alias'] ?? '');
        if ($name === '') {
            $name = (string) ($row['merchant_name'] ?? '');
        }
        $code = (string) ($row['merchant_code'] ?? '');
        $pt_id = (int) $pt_id;

        $return_day = (int) ($row['return_day'] ?? 0);
        $partner_commission = function_exists('lc_lp_merchant_partner_display_commission')
            ? lc_lp_merchant_partner_display_commission($row)
            : '';
        $notice = (string) (($row['partner_notice'] ?? '') !== '' ? $row['partner_notice'] : ($row['notice'] ?? ''));
        $settlement = (string) ($row['commission_payment_standard'] ?? '');
        $when_trans = (string) ($row['when_trans'] ?? '');
        $deny_ad = (string) ($row['deny_ad'] ?? '');
        $deny_product = (string) ($row['deny_product'] ?? '');

        return array(
            'lpmId'            => (int) ($row['lpm_id'] ?? 0),
            'merchantCode'     => $code,
            'merchantName'     => $name,
            'originalName'     => (string) ($row['merchant_name'] ?? ''),
            'merchantLogo'     => lc_lp_merchant_public_logo($row),
            'merchantUrl'      => (string) ($row['merchant_url'] ?? ''),
            'categoryName'     => (string) ($row['category_name'] ?? ''),
            'commissionPc'     => lc_lp_format_commission_rate_display((string) ($row['commission_pc'] ?? '')),
            'commissionMobile' => lc_lp_format_commission_rate_display((string) ($row['commission_mobile'] ?? '')),
            'partnerCommission'=> $partner_commission,
            'partnerRate'      => (float) ($row['partner_rate'] ?? 70),
            'settlement'       => lc_lp_public_brand_copy($settlement),
            'whenTrans'        => lc_lp_public_brand_copy($when_trans),
            'returnDay'        => $return_day,
            'denyAd'           => lc_lp_public_brand_copy($deny_ad),
            'denyProduct'      => lc_lp_public_brand_copy($deny_product),
            'notice'           => lc_lp_public_brand_copy($notice),
            'deeplinkYn'       => (string) ($row['deeplink_yn'] ?? 'N'),
            'isRecommended'    => !empty($row['is_recommended']),
            'promoUrl'         => lc_lp_public_promo_url($code, $pt_id),
            'partnerToken'     => lc_lp_partner_public_token($pt_id),
            'clicks'           => (int) ($stats['clicks'] ?? 0),
            'expectedOrders'   => (int) ($stats['expected'] ?? 0),
            'confirmedOrders'  => (int) ($stats['confirmed'] ?? 0),
        );
    }
}

if (!function_exists('lc_lp_partner_merchant_stats')) {
    /**
     * @return array<int,array{clicks:int,expected:int,confirmed:int}> keyed by lpm_id
     */
    function lc_lp_partner_merchant_stats($pt_id)
    {
        $pt_id = (int) $pt_id;
        $out = array();
        $clicks_t = lc_table('lp_clicks');
        if (lc_db_table_exists($clicks_t)) {
            $res = lc_sql_query(" SELECT lpm_id, COUNT(*) AS cnt FROM `{$clicks_t}` WHERE pt_id = {$pt_id} GROUP BY lpm_id ", false);
            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $id = (int) $row['lpm_id'];
                    if (!isset($out[$id])) {
                        $out[$id] = array('clicks' => 0, 'expected' => 0, 'confirmed' => 0);
                    }
                    $out[$id]['clicks'] = (int) $row['cnt'];
                }
            }
        }
        $orders_t = lc_table('lp_orders');
        if (lc_db_table_exists($orders_t)) {
            $res = lc_sql_query(" SELECT lpm_id,
                    SUM(CASE WHEN lc_status IN ('" . lc_sql_escape(LC_LP_ORDER_EXPECTED) . "','" . lc_sql_escape(LC_LP_ORDER_PENDING) . "','" . lc_sql_escape(LC_LP_ORDER_REVIEW) . "','" . lc_sql_escape(LC_LP_ORDER_HOLD) . "') THEN 1 ELSE 0 END) AS expected_cnt,
                    SUM(CASE WHEN lc_status IN ('" . lc_sql_escape(LC_LP_ORDER_CONFIRMED) . "','" . lc_sql_escape(LC_LP_ORDER_APPROVED) . "') THEN 1 ELSE 0 END) AS confirmed_cnt
                FROM `{$orders_t}` WHERE pt_id = {$pt_id} GROUP BY lpm_id ", false);
            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $id = (int) $row['lpm_id'];
                    if (!isset($out[$id])) {
                        $out[$id] = array('clicks' => 0, 'expected' => 0, 'confirmed' => 0);
                    }
                    $out[$id]['expected'] = (int) ($row['expected_cnt'] ?? 0);
                    $out[$id]['confirmed'] = (int) ($row['confirmed_cnt'] ?? 0);
                }
            }
        }

        return $out;
    }
}

if (!function_exists('lc_lp_partner_list_merchants')) {
    function lc_lp_partner_list_merchants($pt_id, array $filters = array())
    {
        $filters['partner_visible'] = true;
        $filters['limit'] = isset($filters['limit']) ? (int) $filters['limit'] : 300;
        $list = lc_lp_merchants_list($filters);
        $stats = lc_lp_partner_merchant_stats($pt_id);
        $items = array();
        foreach ($list['items'] as $row) {
            if (!lc_lp_merchant_public_listable($row)) {
                continue;
            }
            $id = (int) ($row['lpm_id'] ?? 0);
            $items[] = lc_lp_merchant_to_partner_api($row, $pt_id, $stats[$id] ?? array());
        }

        return array('items' => $items, 'total' => $list['total']);
    }
}

if (!function_exists('lc_lp_partner_build_deeplink')) {
    /**
     * 딥링크 검증 후 LC 추적 URL 반환 (원본 LP URL 비노출)
     *
     * @return array{ok:bool,message:string,promoUrl:string}
     */
    function lc_lp_partner_build_deeplink($pt_id, $merchant_code, $product_url)
    {
        $pt_id = (int) $pt_id;
        $merchant = lc_lp_repo_get_merchant_by_code($merchant_code);
        if (!is_array($merchant) || !lc_lp_merchant_partner_visible($merchant)) {
            return array('ok' => false, 'message' => '홍보 가능한 광고주가 아닙니다.', 'promoUrl' => '');
        }
        $dl = lc_lp_validate_deeplink($product_url, $merchant);
        if (!$dl['ok']) {
            return array('ok' => false, 'message' => $dl['message'], 'promoUrl' => '');
        }
        $url = lc_lp_public_promo_url((string) $merchant['merchant_code'], $pt_id, $dl['url']);

        return array('ok' => true, 'message' => '딥링크가 생성되었습니다.', 'promoUrl' => $url);
    }
}

if (!function_exists('lc_lp_shortlink_public_url')) {
    function lc_lp_shortlink_public_url($short_code, $public_base = null)
    {
        $short_code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $short_code);
        if ($short_code === '') {
            return '';
        }

        $base = trim((string) $public_base);
        if ($base === '') {
            $base = defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
        } else {
            $base = rtrim($base, '/');
        }

        return $base . '/s/' . rawurlencode($short_code);
    }
}

if (!function_exists('lc_lp_shortlink_generate_code')) {
    function lc_lp_shortlink_generate_code()
    {
        // 읽기 쉬운 짧은 코드 (혼동 문자 제외)
        $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}

if (!function_exists('lc_lp_shortlink_ensure_table')) {
    function lc_lp_shortlink_ensure_table()
    {
        $table = lc_table('lp_shortlinks');
        if (lc_db_table_exists($table)) {
            return true;
        }
        if (function_exists('lc_lp_db_ensure_schema')) {
            lc_lp_db_ensure_schema();
        }

        return lc_db_table_exists($table);
    }
}

if (!function_exists('lc_lp_shortlink_get_by_code')) {
    function lc_lp_shortlink_get_by_code($short_code)
    {
        $short_code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $short_code);
        if ($short_code === '' || !lc_lp_shortlink_ensure_table()) {
            return null;
        }
        $table = lc_table('lp_shortlinks');
        $row = lc_sql_fetch(
            " SELECT * FROM `{$table}` WHERE short_code = '" . lc_sql_escape($short_code) . "' LIMIT 1 ",
            false
        );

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_shortlink_store_for_partner')) {
    /**
     * 파트너 소유 추적 URL → /s/{code} 저장 (동일 target 재사용)
     *
     * @param array{lpm_id?:int,merchant_code?:string,product_url?:string} $meta
     * @return array{ok:bool,message:string,shortUrl:string,promoUrl:string,shortCode:string}
     */
    function lc_shortlink_store_for_partner($pt_id, $target_url, array $meta = array())
    {
        $pt_id = (int) $pt_id;
        $target_url = trim((string) $target_url);
        $empty = array('ok' => false, 'message' => '', 'shortUrl' => '', 'promoUrl' => '', 'shortCode' => '');
        $public_base = isset($meta['public_base']) ? trim((string) $meta['public_base']) : '';

        if ($pt_id <= 0 || $target_url === '' || !preg_match('#^https?://#i', $target_url)) {
            $empty['message'] = '숏링크로 변환할 대상 URL이 올바르지 않습니다.';

            return $empty;
        }
        if (!function_exists('lc_lp_shortlink_ensure_table') || !lc_lp_shortlink_ensure_table()) {
            $empty['message'] = '숏링크 저장소를 준비하지 못했습니다.';

            return $empty;
        }

        $table = lc_table('lp_shortlinks');
        $target_hash = hash('sha256', $target_url);
        $existing = lc_sql_fetch(
            " SELECT * FROM `{$table}`
              WHERE pt_id = {$pt_id} AND target_hash = '" . lc_sql_escape($target_hash) . "'
              LIMIT 1 ",
            false
        );
        if (is_array($existing) && !empty($existing['short_code'])) {
            $code = (string) $existing['short_code'];

            return array(
                'ok'        => true,
                'message'   => '숏링크가 준비되었습니다.',
                'shortUrl'  => lc_lp_shortlink_public_url($code, $public_base),
                'promoUrl'  => $target_url,
                'shortCode' => $code,
            );
        }

        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $candidate = lc_lp_shortlink_generate_code();
            if (!lc_lp_shortlink_get_by_code($candidate)) {
                $code = $candidate;
                break;
            }
        }
        if ($code === '') {
            $empty['message'] = '숏코드 생성에 실패했습니다. 다시 시도해 주세요.';

            return $empty;
        }

        $lpm_id = (int) ($meta['lpm_id'] ?? 0);
        $merchant_code = trim((string) ($meta['merchant_code'] ?? ''));
        $product_url = trim((string) ($meta['product_url'] ?? ''));
        $ok = lc_sql_query(
            " INSERT INTO `{$table}` SET
                pt_id = {$pt_id},
                lpm_id = {$lpm_id},
                merchant_code = '" . lc_sql_escape($merchant_code) . "',
                short_code = '" . lc_sql_escape($code) . "',
                target_url = '" . lc_sql_escape($target_url) . "',
                target_hash = '" . lc_sql_escape($target_hash) . "',
                product_url = '" . lc_sql_escape($product_url) . "',
                created_at = NOW() ",
            false
        );
        if ($ok === false) {
            $existing = lc_sql_fetch(
                " SELECT * FROM `{$table}`
                  WHERE pt_id = {$pt_id} AND target_hash = '" . lc_sql_escape($target_hash) . "'
                  LIMIT 1 ",
                false
            );
            if (is_array($existing) && !empty($existing['short_code'])) {
                $code = (string) $existing['short_code'];

                return array(
                    'ok'        => true,
                    'message'   => '숏링크가 준비되었습니다.',
                    'shortUrl'  => lc_lp_shortlink_public_url($code, $public_base),
                    'promoUrl'  => $target_url,
                    'shortCode' => $code,
                );
            }
            $empty['message'] = '숏링크 저장에 실패했습니다.';

            return $empty;
        }

        return array(
            'ok'        => true,
            'message'   => '숏링크가 생성되었습니다.',
            'shortUrl'  => lc_lp_shortlink_public_url($code, $public_base),
            'promoUrl'  => $target_url,
            'shortCode' => $code,
        );
    }
}

if (!function_exists('lc_lp_partner_create_shortlink')) {
    /**
     * CPS 대표/딥링크 홍보 URL을 /s/{code} 숏링크로 변환 (동일 대상 재사용)
     *
     * @return array{ok:bool,message:string,shortUrl:string,promoUrl:string,shortCode:string}
     */
    function lc_lp_partner_create_shortlink($pt_id, $merchant_code, $product_url = '')
    {
        $pt_id = (int) $pt_id;
        $merchant_code = trim((string) $merchant_code);
        $product_url = trim((string) $product_url);
        $empty = array('ok' => false, 'message' => '', 'shortUrl' => '', 'promoUrl' => '', 'shortCode' => '');

        if ($pt_id <= 0 || $merchant_code === '') {
            $empty['message'] = '광고주 정보가 올바르지 않습니다.';

            return $empty;
        }

        $merchant = lc_lp_repo_get_merchant_by_code($merchant_code);
        if (!is_array($merchant) || !lc_lp_merchant_partner_visible($merchant)) {
            $empty['message'] = '홍보 가능한 광고주가 아닙니다.';

            return $empty;
        }

        $deeplink = '';
        if ($product_url !== '') {
            $dl = lc_lp_validate_deeplink($product_url, $merchant);
            if (!$dl['ok']) {
                $empty['message'] = $dl['message'];

                return $empty;
            }
            $deeplink = $dl['url'];
        }

        $promo_url = lc_lp_public_promo_url((string) $merchant['merchant_code'], $pt_id, $deeplink);
        if ($promo_url === '') {
            $empty['message'] = '홍보 링크를 만들 수 없습니다.';

            return $empty;
        }

        return lc_shortlink_store_for_partner($pt_id, $promo_url, array(
            'lpm_id'         => (int) ($merchant['lpm_id'] ?? 0),
            'merchant_code'  => (string) $merchant['merchant_code'],
            'product_url'    => $deeplink,
        ));
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpricePostback — Reward API 수신 / 예상 실적 등록
 * 공식: POST JSON, 타임아웃 최대 10초, 필드명 commision(공식 오타)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_postback_collect_headers')) {
    function lc_lp_postback_collect_headers()
    {
        $headers = array();
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                // 시크릿/키 마스킹
                if (preg_match('/secret|auth|token|key/i', $name)) {
                    $v = '***';
                }
                $headers[$name] = is_string($v) ? mb_substr($v, 0, 500) : $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        return json_encode($headers, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('lc_lp_postback_client_ip')) {
    function lc_lp_postback_client_ip()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    }
}

if (!function_exists('lc_lp_postback_ip_allowed')) {
    function lc_lp_postback_ip_allowed($ip)
    {
        if (!function_exists('lc_settings_get_bool') || !lc_settings_get_bool('lpPostbackIpEnabled')) {
            return true; // 기본 비활성
        }
        $list = function_exists('lc_settings_get') ? (string) lc_settings_get('lpPostbackIpAllowlist', '') : '';
        $list = trim($list);
        if ($list === '') {
            return false;
        }
        $allowed = preg_split('/[\s,;]+/', $list, -1, PREG_SPLIT_NO_EMPTY);
        $ip = trim((string) $ip);
        foreach ($allowed as $a) {
            $a = trim($a);
            if ($a === $ip) {
                return true;
            }
            // CIDR 간단 지원 (IPv4)
            if (strpos($a, '/') !== false && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                list($subnet, $mask) = explode('/', $a, 2);
                $mask = (int) $mask;
                if ($mask >= 0 && $mask <= 32 && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ip_long = ip2long($ip);
                    $sub_long = ip2long($subnet);
                    $mask_long = -1 << (32 - $mask);
                    if (($ip_long & $mask_long) === ($sub_long & $mask_long)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}

if (!function_exists('lc_lp_postback_secret_ok')) {
    /**
     * 공식 문서에 서명 필드는 없음. Secret을 설정한 경우에만 헤더/쿼리로 검증.
     *  - 헤더 X-LP-SECRET / X-Linkprice-Secret
     *  - 쿼리 ?secret=  (POSTBACK URL에 포함 가능)
     * Secret 미설정 시 공식 POSTBACK 그대로 수신한다. 필요하면 IP allowlist로 제한한다.
     */
    function lc_lp_postback_secret_ok()
    {
        $cfg = lc_lp_config_get();
        $expected = trim((string) ($cfg['postback_secret'] ?? ''));
        if ($expected === '' && function_exists('lc_settings_get')) {
            $expected = trim((string) lc_settings_get('lpPostbackSecret', ''));
        }

        if ($expected === '') {
            return true;
        }
        $provided = '';
        if (isset($_SERVER['HTTP_X_LP_SECRET'])) {
            $provided = trim((string) $_SERVER['HTTP_X_LP_SECRET']);
        } elseif (isset($_SERVER['HTTP_X_LINKPRICE_SECRET'])) {
            $provided = trim((string) $_SERVER['HTTP_X_LINKPRICE_SECRET']);
        } elseif (isset($_GET['secret'])) {
            $provided = trim((string) $_GET['secret']);
        }
        return $provided !== '' && hash_equals($expected, $provided);
    }
}

if (!function_exists('lc_lp_postback_pick_commission')) {
    /** 공식 필드명 commision + 오타 보정 commission */
    function lc_lp_postback_pick_commission(array $item)
    {
        if (array_key_exists('commision', $item)) {
            return $item['commision'];
        }
        if (array_key_exists('commission', $item)) {
            return $item['commission'];
        }
        return null;
    }
}

if (!function_exists('lc_lp_postback_normalize_items')) {
    /**
     * 단일 객체 또는 배열(복수 상품) → 아이템 리스트
     *
     * @return array<int,array>
     */
    function lc_lp_postback_normalize_items($decoded)
    {
        if (!is_array($decoded)) {
            return array();
        }
        // { orders: [...] } / { items: [...] } / { data: [...] }
        foreach (array('orders', 'items', 'data', 'list') as $wrap) {
            if (isset($decoded[$wrap]) && is_array($decoded[$wrap]) && (isset($decoded[$wrap][0]) || $decoded[$wrap] === array())) {
                $decoded = $decoded[$wrap];
                break;
            }
        }
        if (isset($decoded['trlog_id']) || isset($decoded['merchant_id']) || isset($decoded['order_code'])) {
            return array($decoded);
        }
        $out = array();
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }
        return $out;
    }
}

if (!function_exists('lc_lp_postback_decode_product_name')) {
    function lc_lp_postback_decode_product_name($name)
    {
        $name = (string) $name;
        if ($name === '') {
            return '';
        }
        // URL ENCODING 된 상품명 복원 (공식)
        $decoded = rawurldecode($name);
        if ($decoded !== '' && $decoded !== $name) {
            return $decoded;
        }
        $decoded2 = urldecode($name);
        return $decoded2 !== '' ? $decoded2 : $name;
    }
}

if (!function_exists('lc_lp_postback_validate_item')) {
    /**
     * @return array{ok:bool,errors:array,normalized:array}
     */
    function lc_lp_postback_validate_item(array $item)
    {
        $errors = array();
        $merchant = trim((string) ($item['merchant_id'] ?? ''));
        $order = trim((string) ($item['order_code'] ?? ''));
        $trlog = trim((string) ($item['trlog_id'] ?? ''));
        $u_id = trim((string) ($item['affiliate_user_id'] ?? $item['u_id'] ?? ''));
        $product_code = trim((string) ($item['product_code'] ?? ''));
        $product_name = lc_lp_postback_decode_product_name($item['product_name'] ?? '');
        $day = trim((string) ($item['day'] ?? ''));
        $time = trim((string) ($item['time'] ?? '000000'));

        if ($merchant === '') {
            $errors[] = 'merchant_id 누락';
        }
        if ($order === '') {
            $errors[] = 'order_code 누락';
        }
        if ($trlog === '') {
            $errors[] = 'trlog_id 누락';
        }
        if ($u_id === '') {
            $errors[] = 'affiliate_user_id 누락';
        }
        if ($product_code === '') {
            $errors[] = 'product_code 누락';
        }
        if ($product_name === '') {
            $errors[] = 'product_name 누락';
        }
        if ($day === '' || !preg_match('/^\d{8}$/', $day)) {
            $errors[] = 'day(YYYYMMDD) 누락/형식오류';
        }

        $item_count = isset($item['item_count']) ? (int) $item['item_count'] : 0;
        if ($item_count <= 0) {
            $errors[] = 'item_count 누락/오류';
        }

        $price = isset($item['price']) ? (float) $item['price'] : null;
        if ($price === null) {
            $errors[] = 'price 누락';
        } elseif ($price < 0) {
            $errors[] = 'price 음수 불가';
        }

        $comm_raw = lc_lp_postback_pick_commission($item);
        if ($comm_raw === null || $comm_raw === '') {
            $errors[] = 'commision 누락';
        }
        $commission = (float) $comm_raw;
        if ($commission < 0) {
            $errors[] = 'commision 음수 불가';
        }

        $occurred = null;
        if (preg_match('/^\d{8}$/', $day)) {
            $hhmmss = preg_match('/^\d{6}$/', $time) ? $time : '000000';
            $occurred = substr($day, 0, 4) . '-' . substr($day, 4, 2) . '-' . substr($day, 6, 2)
                . ' ' . substr($hhmmss, 0, 2) . ':' . substr($hhmmss, 2, 2) . ':' . substr($hhmmss, 4, 2);
        }

        $normalized = array(
            'merchant_id'          => $merchant,
            'order_code'           => $order,
            'product_code'         => $product_code,
            'product_name'         => mb_substr($product_name, 0, 300),
            'category_code'        => (string) ($item['category_code'] ?? ''),
            'item_count'           => max(0, $item_count),
            'price'                => (float) $price,
            'commision'            => $commission,
            'affiliate_id'         => (string) ($item['affiliate_id'] ?? ''),
            'affiliate_user_id'    => $u_id,
            'trlog_id'             => $trlog,
            'uniq_id'              => (string) ($item['uniq_id'] ?? ''),
            'base_commission'      => (string) ($item['base_commission'] ?? ''),
            'incentive_commission' => (string) ($item['incentive_commission'] ?? ''),
            'day'                  => $day,
            'time'                 => $time,
            'occurred_at'          => $occurred,
        );

        return array(
            'ok'         => count($errors) === 0,
            'errors'     => $errors,
            'normalized' => $normalized,
        );
    }
}

if (!function_exists('lc_lp_repo_find_click_by_u_id')) {
    function lc_lp_repo_find_click_by_u_id($u_id)
    {
        $table = lc_table('lp_clicks');
        if (!lc_db_table_exists($table) || trim((string) $u_id) === '') {
            return null;
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE u_id = '" . lc_sql_escape((string) $u_id) . "' ORDER BY lpc_id DESC LIMIT 1 ", false);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('lc_lp_postback_find_duplicate')) {
    /**
     * 1차: trlog_id (재전송)
     * 2차: trlog 없을 때만 uniq_id + merchant + order + product + u_id
     * 주의: 동일 주문의 새 trlog_id 는 신규 실적(재생성)으로 허용
     *
     * @return array|null existing order
     */
    function lc_lp_postback_find_duplicate(array $n)
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        $trlog = trim((string) $n['trlog_id']);
        if ($trlog !== '') {
            $by_trlog = lc_lp_repo_get_order_by_trlog($trlog);
            if (is_array($by_trlog)) {
                return $by_trlog;
            }
            // 새 trlog → 신규 허용 (동일 주문 재생성)
            return null;
        }

        $uniq = trim((string) $n['uniq_id']);
        if ($uniq !== '') {
            $row = lc_sql_fetch(" SELECT * FROM `{$table}`
                WHERE uniq_id = '" . lc_sql_escape($uniq) . "'
                  AND merchant_code = '" . lc_sql_escape($n['merchant_id']) . "'
                  AND order_code = '" . lc_sql_escape($n['order_code']) . "'
                  AND product_code = '" . lc_sql_escape($n['product_code']) . "'
                  AND u_id = '" . lc_sql_escape($n['affiliate_user_id']) . "'
                ORDER BY lpo_id DESC LIMIT 1 ", false);
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('lc_lp_repo_insert_expected_order')) {
    /**
     * POSTBACK → 예상 실적 (확정수익 0, 잔액 미반영)
     *
     * @return array{ok:bool,lpo_id:int,message:string,matched:bool}
     */
    function lc_lp_repo_insert_expected_order(array $n, array $raw_item)
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'lpo_id' => 0, 'message' => 'lp_orders 없음', 'matched' => false);
        }

        $u_id = (string) $n['affiliate_user_id'];
        $click = lc_lp_repo_find_click_by_u_id($u_id);
        $parsed = function_exists('lc_lp_repo_parse_u_id') ? lc_lp_repo_parse_u_id($u_id) : null;

        $pt_id = 0;
        $lpm_id = 0;
        $lpc_id = 0;
        $match_note = '';
        $matched = false;

        if (is_array($click)) {
            $pt_id = (int) ($click['pt_id'] ?? 0);
            $lpm_id = (int) ($click['lpm_id'] ?? 0);
            $lpc_id = (int) ($click['lpc_id'] ?? 0);
            $matched = true;
            $match_note = 'click';
        } elseif (is_array($parsed)) {
            $pt_id = (int) ($parsed['pt_id'] ?? 0);
            $lpm_id = (int) ($parsed['lpm_id'] ?? 0);
            $match_note = 'u_id_parse';
        } else {
            $match_note = 'unmatched_uid';
        }

        $merchant = lc_lp_repo_get_merchant_by_code($n['merchant_id']);
        if (is_array($merchant)) {
            if ($lpm_id <= 0) {
                $lpm_id = (int) $merchant['lpm_id'];
            } elseif ((int) $merchant['lpm_id'] !== $lpm_id) {
                // 클릭 광고주와 POSTBACK 광고주 불일치 → 미매칭 처리하되 저장
                $match_note = 'merchant_mismatch';
                $matched = false;
                $pt_id = 0; // 잘못된 파트너 귀속 방지
            }
        } else {
            $match_note = ($match_note === 'click' || $match_note === 'u_id_parse')
                ? $match_note . '+unknown_merchant'
                : 'unknown_merchant';
            // 광고주 없어도 원본 실적은 저장 (미매칭)
            $matched = false;
        }

        $partner_rate = 70.0;
        if (is_array($merchant)) {
            $partner_rate = (float) ($merchant['partner_rate'] ?? 70);
        } else {
            $cfg = lc_lp_config_get();
            $partner_rate = (float) ($cfg['default_partner_rate'] ?? 70);
        }

        $shares = lc_lp_repo_calc_shares($n['commision'], $partner_rate, LC_LP_ORDER_EXPECTED);
        // 확정수익은 0 유지
        $partner_confirmed = 0.0;

        $raw_json = json_encode($raw_item, JSON_UNESCAPED_UNICODE);
        $occurred_sql = $n['occurred_at']
            ? ("'" . lc_sql_escape($n['occurred_at']) . "'")
            : 'NULL';

        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            trlog_id = '" . lc_sql_escape(mb_substr($n['trlog_id'], 0, 30)) . "',
            uniq_id = '" . lc_sql_escape(mb_substr($n['uniq_id'], 0, 30)) . "',
            pt_id = " . (int) $pt_id . ",
            lpm_id = " . (int) $lpm_id . ",
            lpc_id = " . (int) $lpc_id . ",
            u_id = '" . lc_sql_escape(mb_substr($u_id, 0, 80)) . "',
            merchant_code = '" . lc_sql_escape(mb_substr($n['merchant_id'], 0, 20)) . "',
            order_code = '" . lc_sql_escape(mb_substr($n['order_code'], 0, 100)) . "',
            product_code = '" . lc_sql_escape(mb_substr($n['product_code'], 0, 100)) . "',
            product_name = '" . lc_sql_escape($n['product_name']) . "',
            item_count = " . (int) $n['item_count'] . ",
            sales_amount = '" . lc_sql_escape(number_format((float) $n['price'], 2, '.', '')) . "',
            lp_commission = '" . lc_sql_escape(number_format((float) $n['commision'], 2, '.', '')) . "',
            partner_rate = '" . lc_sql_escape(number_format($partner_rate, 2, '.', '')) . "',
            partner_expected = '" . lc_sql_escape(number_format((float) $shares['partner_expected'], 2, '.', '')) . "',
            partner_confirmed = '" . lc_sql_escape(number_format($partner_confirmed, 2, '.', '')) . "',
            platform_margin = '" . lc_sql_escape(number_format((float) $shares['platform_margin'], 2, '.', '')) . "',
            raw_status = '',
            lc_status = '" . lc_sql_escape(LC_LP_ORDER_EXPECTED) . "',
            occurred_at = {$occurred_sql},
            last_synced_at = NOW(),
            raw_json = '" . lc_sql_escape((string) $raw_json) . "',
            created_at = NOW(),
            updated_at = NOW() ", false);

        $lpo_id = (int) lc_sql_insert_id();
        if ($ok === false || $lpo_id <= 0) {
            return array('ok' => false, 'lpo_id' => 0, 'message' => 'order insert 실패: ' . lc_sql_error(), 'matched' => $matched, 'match_note' => $match_note);
        }

        if (function_exists('lc_lp_log_status_change')) {
            lc_lp_log_status_change($lpo_id, '', LC_LP_ORDER_EXPECTED, 0, $n['commision'], 'postback:' . $match_note, $raw_json);
        }

        return array(
            'ok'         => true,
            'lpo_id'     => $lpo_id,
            'message'    => $matched ? 'expected' : 'unmatched',
            'matched'    => $matched && $pt_id > 0,
            'match_note' => $match_note,
            'pt_id'      => $pt_id,
        );
    }
}

if (!function_exists('lc_lp_postback_process_item')) {
    /**
     * 단일 실적 라인 처리 (원본 postback row 이미 저장된 뒤 호출)
     *
     * @return array{status:string,lpo_id:int,message:string}
     */
    function lc_lp_postback_process_item(array $item, $lpp_id = 0)
    {
        $v = lc_lp_postback_validate_item($item);
        if (!$v['ok']) {
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array(
                    'process_status' => LC_LP_PB_ERROR,
                    'error_message'  => implode('; ', $v['errors']),
                    'merchant_code'  => $v['normalized']['merchant_id'] ?? '',
                    'order_code'     => $v['normalized']['order_code'] ?? '',
                    'u_id'           => $v['normalized']['affiliate_user_id'] ?? '',
                    'trlog_id'       => $v['normalized']['trlog_id'] ?? '',
                    'uniq_id'        => $v['normalized']['uniq_id'] ?? '',
                ));
            }
            return array('status' => LC_LP_PB_ERROR, 'lpo_id' => 0, 'message' => implode('; ', $v['errors']));
        }

        $n = $v['normalized'];
        $dup = lc_lp_postback_find_duplicate($n);
        if (is_array($dup)) {
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array(
                    'process_status' => LC_LP_PB_DUPLICATE,
                    'is_duplicate'   => 1,
                    'lpo_id'         => (int) ($dup['lpo_id'] ?? 0),
                    'error_message'  => 'duplicate trlog/uniq/composite',
                    'match_note'     => 'duplicate',
                    'merchant_code'  => $n['merchant_id'],
                    'order_code'     => $n['order_code'],
                    'u_id'           => $n['affiliate_user_id'],
                    'trlog_id'       => $n['trlog_id'],
                    'uniq_id'        => $n['uniq_id'],
                ));
            }
            return array('status' => LC_LP_PB_DUPLICATE, 'lpo_id' => (int) ($dup['lpo_id'] ?? 0), 'message' => 'duplicate');
        }

        $ins = lc_lp_repo_insert_expected_order($n, $item);
        if (empty($ins['ok'])) {
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array(
                    'process_status' => LC_LP_PB_ERROR,
                    'error_message'  => (string) ($ins['message'] ?? 'insert failed'),
                    'merchant_code'  => $n['merchant_id'],
                    'order_code'     => $n['order_code'],
                    'u_id'           => $n['affiliate_user_id'],
                    'trlog_id'       => $n['trlog_id'],
                    'uniq_id'        => $n['uniq_id'],
                ));
            }
            return array('status' => LC_LP_PB_ERROR, 'lpo_id' => 0, 'message' => (string) ($ins['message'] ?? 'error'));
        }

        $status = !empty($ins['matched']) ? LC_LP_PB_PROCESSED : LC_LP_PB_UNMATCHED;
        if ($lpp_id > 0) {
            lc_lp_repo_update_postback($lpp_id, array(
                'process_status' => $status,
                'lpo_id'         => (int) $ins['lpo_id'],
                'match_note'     => (string) ($ins['match_note'] ?? ''),
                'error_message'  => '',
                'merchant_code'  => $n['merchant_id'],
                'order_code'     => $n['order_code'],
                'u_id'           => $n['affiliate_user_id'],
                'trlog_id'       => $n['trlog_id'],
                'uniq_id'        => $n['uniq_id'],
            ));
        }

        return array(
            'status'  => $status,
            'lpo_id'  => (int) $ins['lpo_id'],
            'message' => (string) ($ins['message'] ?? $status),
        );
    }
}

if (!function_exists('lc_lp_postback_receive')) {
    /**
     * 수신 메인: 원본 저장 → 빠른 ACK용 결과 → 상세 처리
     *
     * @return array{http:int,body:array,lpp_ids:array,results:array}
     */
    function lc_lp_postback_receive($raw_body, $content_type = '', $ip = '')
    {
        $started = microtime(true);
        $ip = $ip !== '' ? $ip : lc_lp_postback_client_ip();
        $headers_json = lc_lp_postback_collect_headers();

        // 크기 제한 ~512KB
        if (strlen((string) $raw_body) > 524288) {
            return array(
                'http' => 413,
                'body' => array('result' => 'error', 'message' => 'payload too large'),
                'lpp_ids' => array(),
                'results' => array(),
            );
        }

        if (!lc_lp_postback_ip_allowed($ip)) {
            // 원본은 남김
            $lpp_id = lc_lp_repo_insert_postback($raw_body, $headers_json, $ip, array('process_status' => LC_LP_PB_ERROR));
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array('process_status' => LC_LP_PB_ERROR, 'error_message' => 'ip_denied'));
            }
            return array(
                'http' => 403,
                'body' => array('result' => 'error', 'message' => 'forbidden'),
                'lpp_ids' => array($lpp_id),
                'results' => array(),
            );
        }

        if (!lc_lp_postback_secret_ok()) {
            $lpp_id = lc_lp_repo_insert_postback($raw_body, $headers_json, $ip);
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array('process_status' => LC_LP_PB_ERROR, 'error_message' => 'secret_denied'));
            }
            return array(
                'http' => 401,
                'body' => array('result' => 'error', 'message' => 'unauthorized'),
                'lpp_ids' => array($lpp_id),
                'results' => array(),
            );
        }

        $ct = strtolower((string) $content_type);
        // Content-Type 비어 있거나 json/text 허용 (게이트웨이 편차)
        if ($ct !== '' && strpos($ct, 'json') === false && strpos($ct, 'text/') === false && strpos($ct, 'octet-stream') === false) {
            $lpp_id = lc_lp_repo_insert_postback($raw_body, $headers_json, $ip);
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array('process_status' => LC_LP_PB_ERROR, 'error_message' => 'bad_content_type'));
            }
            return array(
                'http' => 415,
                'body' => array('result' => 'success', 'message' => 'accepted'), // 원본 보존 우선
                'lpp_ids' => array($lpp_id),
                'results' => array(),
            );
        }

        $decoded = json_decode((string) $raw_body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $lpp_id = lc_lp_repo_insert_postback($raw_body, $headers_json, $ip);
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array('process_status' => LC_LP_PB_ERROR, 'error_message' => 'invalid_json'));
            }
            // 링크프라이스 재전송 유도 — 4xx
            return array(
                'http' => 400,
                'body' => array('result' => 'error', 'message' => 'invalid json'),
                'lpp_ids' => array($lpp_id),
                'results' => array(),
            );
        }

        $items = lc_lp_postback_normalize_items($decoded);
        if (!$items) {
            $lpp_id = lc_lp_repo_insert_postback($raw_body, $headers_json, $ip);
            if ($lpp_id > 0) {
                lc_lp_repo_update_postback($lpp_id, array('process_status' => LC_LP_PB_ERROR, 'error_message' => 'empty_items'));
            }
            return array(
                'http' => 400,
                'body' => array('result' => 'error', 'message' => 'empty'),
                'lpp_ids' => array($lpp_id),
                'results' => array(),
            );
        }

        $lpp_ids = array();
        $results = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            // 원본 라인별 저장 (복수 상품 주문)
            $lpp_id = lc_lp_repo_insert_postback($item, $headers_json, $ip, array(
                'trlog_id'      => (string) ($item['trlog_id'] ?? ''),
                'uniq_id'       => (string) ($item['uniq_id'] ?? ''),
                'merchant_code' => (string) ($item['merchant_id'] ?? ''),
                'order_code'    => (string) ($item['order_code'] ?? ''),
                'u_id'          => (string) ($item['affiliate_user_id'] ?? $item['u_id'] ?? ''),
            ));
            $lpp_ids[] = $lpp_id;
            $results[] = lc_lp_postback_process_item($item, $lpp_id);
        }

        $elapsed_ms = (int) round((microtime(true) - $started) * 1000);

        // 링크프라이스에는 빠르게 성공 응답 (원본은 이미 저장됨)
        // 처리 오류가 있어도 수신 자체는 200 — 재전송 폭주/유실 방지. 단 invalid 는 위에서 4xx.
        return array(
            'http'    => 200,
            'body'    => array(
                'result'  => 'success',
                'count'   => count($results),
                'elapsed' => $elapsed_ms,
            ),
            'lpp_ids' => $lpp_ids,
            'results' => $results,
        );
    }
}

if (!function_exists('lc_lp_postback_reprocess')) {
    function lc_lp_postback_reprocess($lpp_id)
    {
        $lpp_id = (int) $lpp_id;
        $table = lc_table('lp_postbacks');
        if ($lpp_id <= 0 || !lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => 'POSTBACK을 찾을 수 없습니다.');
        }
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpp_id = {$lpp_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => 'POSTBACK을 찾을 수 없습니다.');
        }
        $item = json_decode((string) ($row['request_json'] ?? ''), true);
        if (!is_array($item)) {
            return array('ok' => false, 'message' => '원본 JSON이 유효하지 않습니다.');
        }
        // 재처리 시 기존 duplicate 플래그 초기화 후 다시 판정
        lc_lp_repo_update_postback($lpp_id, array(
            'process_status' => LC_LP_PB_RECEIVED,
            'is_duplicate'   => 0,
            'error_message'  => '',
            'lpo_id'         => 0,
        ));
        $result = lc_lp_postback_process_item($item, $lpp_id);
        return array('ok' => true, 'message' => '재처리 완료: ' . $result['status'], 'result' => $result);
    }
}

if (!function_exists('lc_lp_postback_to_api')) {
    function lc_lp_postback_to_api(array $row, $include_raw = false)
    {
        $item = array(
            'lppId'          => (int) ($row['lpp_id'] ?? 0),
            'trlogId'        => (string) ($row['trlog_id'] ?? ''),
            'uniqId'         => (string) ($row['uniq_id'] ?? ''),
            'merchantCode'   => (string) ($row['merchant_code'] ?? ''),
            'orderCode'      => (string) ($row['order_code'] ?? ''),
            'uId'            => (string) ($row['u_id'] ?? ''),
            'requestIp'      => (string) ($row['request_ip'] ?? ''),
            'isDuplicate'    => !empty($row['is_duplicate']),
            'processStatus'  => (string) ($row['process_status'] ?? ''),
            'errorMessage'   => (string) ($row['error_message'] ?? ''),
            'matchNote'      => (string) ($row['match_note'] ?? ''),
            'lpoId'          => (int) ($row['lpo_id'] ?? 0),
            'receivedAt'     => $row['received_at'] ?? null,
            'processedAt'    => $row['processed_at'] ?? null,
        );
        if ($include_raw) {
            $raw = (string) ($row['request_json'] ?? '');
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $item['raw'] = is_array($decoded) ? $decoded : $raw;
            $hdr = (string) ($row['request_headers'] ?? '');
            $item['headers'] = $hdr !== '' ? json_decode($hdr, true) : null;
        }
        return $item;
    }
}

if (!function_exists('lc_lp_postbacks_list')) {
    function lc_lp_postbacks_list(array $filters = array())
    {
        $table = lc_table('lp_postbacks');
        if (!lc_db_table_exists($table)) {
            return array('items' => array(), 'total' => 0);
        }
        $where = array('1=1');
        if (!empty($filters['status'])) {
            $where[] = "process_status = '" . lc_sql_escape((string) $filters['status']) . "'";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape('%' . trim((string) $filters['q']) . '%');
            $where[] = "(merchant_code LIKE '{$q}' OR order_code LIKE '{$q}' OR trlog_id LIKE '{$q}' OR u_id LIKE '{$q}')";
        }
        if (!empty($filters['merchant'])) {
            $where[] = "merchant_code = '" . lc_sql_escape((string) $filters['merchant']) . "'";
        }
        if (!empty($filters['order'])) {
            $where[] = "order_code LIKE '%" . lc_sql_escape((string) $filters['order']) . "%'";
        }
        $where_sql = implode(' AND ', $where);
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where_sql} ", false);
        $total = (int) ($total_row['cnt'] ?? 0);
        $limit = isset($filters['limit']) ? max(1, min(200, (int) $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $rows = lc_sql_query(" SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY lpp_id DESC LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $items[] = $row;
            }
        }
        return array('items' => $items, 'total' => $total);
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * LinkpriceOrderSync — 실적 조회 API 확정·취소·금액변경 동기화
 * 공식: https://api.linkprice.com/affiliate/translist.php
 * 권장 주기: 과도 호출 자제 (15~30분, 일/월 단위 재조회)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_order_normalize_api_row')) {
    /**
     * translist order_list 한 건 → 정규화 (필드명 변경 방어)
     */
    function lc_lp_order_normalize_api_row(array $row)
    {
        $trlog = trim((string) ($row['trlog_id'] ?? ''));
        $merchant = trim((string) ($row['m_id'] ?? $row['merchant_id'] ?? ''));
        $order = trim((string) ($row['o_cd'] ?? $row['order_code'] ?? ''));
        $product = trim((string) ($row['p_cd'] ?? $row['product_code'] ?? ''));
        $name = (string) ($row['p_nm'] ?? $row['product_name'] ?? '');
        $u_id = trim((string) ($row['user_id'] ?? $row['affiliate_user_id'] ?? $row['u_id'] ?? ''));
        $status = trim((string) ($row['status'] ?? ''));
        $sales = (float) ($row['sales'] ?? $row['price'] ?? 0);
        $commission = (float) ($row['commission'] ?? $row['commision'] ?? 0);
        $cnt = (int) ($row['it_cnt'] ?? $row['item_count'] ?? 1);
        $day = trim((string) ($row['yyyymmdd'] ?? $row['day'] ?? ''));
        $time = trim((string) ($row['hhmiss'] ?? $row['time'] ?? '000000'));
        $occurred = null;
        if (preg_match('/^\d{8}$/', $day)) {
            $hh = preg_match('/^\d{6}$/', $time) ? $time : '000000';
            $occurred = substr($day, 0, 4) . '-' . substr($day, 4, 2) . '-' . substr($day, 6, 2)
                . ' ' . substr($hh, 0, 2) . ':' . substr($hh, 2, 2) . ':' . substr($hh, 4, 2);
        }
        $known = array_keys(lc_lp_status_map());
        $unknown_status = ($status !== '' && !in_array($status, $known, true));

        return array(
            'trlog_id'        => $trlog,
            'merchant_code'   => $merchant,
            'order_code'      => $order,
            'product_code'    => $product,
            'product_name'    => mb_substr($name, 0, 300),
            'item_count'      => max(1, $cnt),
            'u_id'            => $u_id,
            'raw_status'      => $status,
            'lc_status'       => lc_lp_repo_map_raw_status($status),
            'sales_amount'    => $sales,
            'lp_commission'   => $commission,
            'occurred_at'     => $occurred,
            'trans_comment'   => (string) ($row['trans_comment'] ?? ''),
            'unknown_status'  => $unknown_status,
            'raw'             => $row,
        );
    }
}

if (!function_exists('lc_lp_order_find_existing')) {
    /**
     * 매칭 우선순위:
     * 1) 링크프라이스 고유 실적 ID (trlog_id)
     * 2) uniq_id (POSTBACK 연계)
     * 3) 광고주코드 + 주문번호 + 상품코드 + u_id
     */
    function lc_lp_order_find_existing(array $n)
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return null;
        }
        if ($n['trlog_id'] !== '') {
            $by = lc_lp_repo_get_order_by_trlog($n['trlog_id']);
            if (is_array($by)) {
                return $by;
            }
        }
        $uniq = trim((string) ($n['uniq_id'] ?? ($n['raw']['uniq_id'] ?? '')));
        if ($uniq !== '') {
            $by_uniq = lc_sql_fetch(" SELECT * FROM `{$table}`
                WHERE uniq_id = '" . lc_sql_escape($uniq) . "'
                ORDER BY lpo_id DESC LIMIT 1 ", false);
            if (is_array($by_uniq)) {
                return $by_uniq;
            }
        }
        if ($n['merchant_code'] !== '' && $n['order_code'] !== '' && $n['u_id'] !== '') {
            $row = lc_sql_fetch(" SELECT * FROM `{$table}`
                WHERE merchant_code = '" . lc_sql_escape($n['merchant_code']) . "'
                  AND order_code = '" . lc_sql_escape($n['order_code']) . "'
                  AND product_code = '" . lc_sql_escape($n['product_code']) . "'
                  AND u_id = '" . lc_sql_escape($n['u_id']) . "'
                ORDER BY lpo_id DESC LIMIT 1 ", false);
            if (is_array($row)) {
                return $row;
            }
        }
        return null;
    }
}

if (!function_exists('lc_lp_ledger_write')) {
    /**
     * UNIQUE idempotency_key — 동일 전표 중복 생성 방지
     * 순서: INSERT 성공 후에만 pt_balance 갱신 (레이스 시 잔액 이중 반영 방지)
     *
     * @return array{ok:bool,skipped:bool,lpl_id:int,message:string}
     */
    function lc_lp_ledger_write($pt_id, $lpo_id, $entry_type, $amount, $idempotency_key, $memo = '')
    {
        $pt_id = (int) $pt_id;
        $lpo_id = (int) $lpo_id;
        $amount = round(abs((float) $amount), 2);
        $entry_type = strtoupper((string) $entry_type);
        $key = mb_substr(trim((string) $idempotency_key), 0, 80);

        if ($pt_id <= 0 || $amount <= 0 || $key === '') {
            return array('ok' => false, 'skipped' => true, 'lpl_id' => 0, 'message' => 'skip');
        }
        if (!in_array($entry_type, array(LC_LP_LEDGER_CREDIT, LC_LP_LEDGER_DEBIT, LC_LP_LEDGER_REVERSAL), true)) {
            return array('ok' => false, 'skipped' => true, 'lpl_id' => 0, 'message' => 'bad type');
        }

        $table = lc_table('lp_ledger');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'skipped' => false, 'lpl_id' => 0, 'message' => 'no ledger table');
        }

        $exists = lc_sql_fetch(" SELECT lpl_id FROM `{$table}` WHERE idempotency_key = '" . lc_sql_escape($key) . "' LIMIT 1 ", false);
        if (is_array($exists)) {
            return array('ok' => true, 'skipped' => true, 'lpl_id' => (int) $exists['lpl_id'], 'message' => 'duplicate ledger');
        }

        // 1) 전표 INSERT 먼저 — UNIQUE 충돌 시 잔액 미변경
        $ok = lc_sql_query(" INSERT INTO `{$table}` SET
            pt_id = {$pt_id},
            lpo_id = {$lpo_id},
            entry_type = '" . lc_sql_escape($entry_type) . "',
            amount = '" . lc_sql_escape(number_format($amount, 2, '.', '')) . "',
            balance_after = '0.00',
            idempotency_key = '" . lc_sql_escape($key) . "',
            memo = '" . lc_sql_escape(mb_substr((string) $memo, 0, 500)) . "',
            created_at = NOW() ", false);

        if ($ok === false) {
            $again = lc_sql_fetch(" SELECT lpl_id FROM `{$table}` WHERE idempotency_key = '" . lc_sql_escape($key) . "' LIMIT 1 ", false);
            if (is_array($again)) {
                return array('ok' => true, 'skipped' => true, 'lpl_id' => (int) $again['lpl_id'], 'message' => 'race duplicate');
            }
            return array('ok' => false, 'skipped' => false, 'lpl_id' => 0, 'message' => lc_sql_error());
        }

        $lpl_id = (int) lc_sql_insert_id();
        $signed = ($entry_type === LC_LP_LEDGER_CREDIT) ? $amount : -$amount;
        $partners = lc_table('partners');
        $bal_after = 0.0;
        if (lc_db_table_exists($partners)) {
            // NOTE: pt_balance 는 CPA와 공유. CPS 전표는 g5_lc_lp_ledger 로 구분.
            $delta_int = (int) round($signed);
            lc_sql_query(" UPDATE `{$partners}` SET pt_balance = pt_balance + ({$delta_int}), pt_updated_at = NOW() WHERE pt_id = {$pt_id} ", false);
            $prow = lc_sql_fetch(" SELECT pt_balance FROM `{$partners}` WHERE pt_id = {$pt_id} LIMIT 1 ", false);
            $bal_after = (float) ($prow['pt_balance'] ?? 0);
            lc_sql_query(" UPDATE `{$table}` SET balance_after = '" . lc_sql_escape(number_format($bal_after, 2, '.', '')) . "' WHERE lpl_id = {$lpl_id} ", false);
        }

        return array('ok' => true, 'skipped' => false, 'lpl_id' => $lpl_id, 'message' => 'written');
    }
}

if (!function_exists('lc_lp_order_notify')) {
    function lc_lp_order_notify($type, $title, $body, $ref_id = 0)
    {
        if (!function_exists('lc_notification_create')) {
            return;
        }
        if (function_exists('lc_notification_recent_exists') && lc_notification_recent_exists('admin', 0, $type, 6)) {
            return;
        }
        lc_notification_create(array(
            'center'  => 'admin',
            'userId'  => 0,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'link'    => '/admin/linkprice',
            'refType' => 'lp_order',
            'refId'   => (int) $ref_id,
        ));
    }
}

if (!function_exists('lc_lp_order_apply_ledger_delta')) {
    /**
     * 확정액 변화분만큼 장부 반영 (이전 confirmed → 새 confirmed)
     *
     * @param string $entry_type_override '' | CREDIT | DEBIT | REVERSAL
     */
    function lc_lp_order_apply_ledger_delta($pt_id, $lpo_id, $from_confirmed, $to_confirmed, $reason, $entry_type_override = '')
    {
        $pt_id = (int) $pt_id;
        $lpo_id = (int) $lpo_id;
        $from = round((float) $from_confirmed, 2);
        $to = round((float) $to_confirmed, 2);
        $diff = round($to - $from, 2);
        if ($pt_id <= 0 || abs($diff) < 0.005) {
            return array('ok' => true, 'skipped' => true, 'message' => 'no delta');
        }

        $override = strtoupper(trim((string) $entry_type_override));
        if ($diff > 0) {
            $type = ($override === LC_LP_LEDGER_CREDIT || $override === '') ? LC_LP_LEDGER_CREDIT : $override;
            if ($type !== LC_LP_LEDGER_CREDIT) {
                $type = LC_LP_LEDGER_CREDIT;
            }
            $key = 'lp:' . $lpo_id . ':CREDIT:' . number_format($to, 2, '.', '') . ':' . md5($reason . '|' . $from);
            return lc_lp_ledger_write($pt_id, $lpo_id, $type, $diff, $key, $reason);
        }

        if ($override === LC_LP_LEDGER_REVERSAL || $override === LC_LP_LEDGER_DEBIT) {
            $type = $override;
        } else {
            $type = (strpos((string) $reason, 'cancel') !== false) ? LC_LP_LEDGER_REVERSAL : LC_LP_LEDGER_DEBIT;
        }
        $key = 'lp:' . $lpo_id . ':' . $type . ':' . number_format($to, 2, '.', '') . ':' . md5($reason . '|' . $from);
        return lc_lp_ledger_write($pt_id, $lpo_id, $type, abs($diff), $key, $reason);
    }
}

if (!function_exists('lc_lp_order_upsert_from_api')) {
    /**
     * API 한 건 반영
     *
     * @return array{ok:bool,action:string,lpo_id:int,message:string,alerts:array}
     */
    function lc_lp_order_upsert_from_api(array $api_row)
    {
        $n = lc_lp_order_normalize_api_row($api_row);
        $alerts = array();
        if ($n['trlog_id'] === '') {
            return array('ok' => false, 'action' => 'skip', 'lpo_id' => 0, 'message' => 'trlog_id 없음', 'alerts' => array('missing_trlog'));
        }
        if ($n['unknown_status']) {
            $alerts[] = 'unknown_status';
            lc_lp_order_notify('lp_unknown_status', 'LP 미정의 상태코드', 'status=' . $n['raw_status'] . ' trlog=' . $n['trlog_id'], 0);
        }
        if ($n['lp_commission'] < 0) {
            $alerts[] = 'negative_commission';
            lc_lp_order_notify('lp_negative_commission', 'LP 음수 커미션', 'trlog=' . $n['trlog_id'], 0);
            $n['lp_commission'] = 0;
        }

        static $schema_checked = false;
        if (!$schema_checked && is_array($n['raw'])) {
            $schema_checked = true;
            $expected_keys = array('trlog_id', 'm_id', 'o_cd', 'p_cd', 'p_nm', 'it_cnt', 'user_id', 'status', 'sales', 'commission', 'yyyymmdd');
            $missing = array();
            foreach ($expected_keys as $ek) {
                if (!array_key_exists($ek, $n['raw']) && !($ek === 'commission' && array_key_exists('commision', $n['raw']))) {
                    $missing[] = $ek;
                }
            }
            if ($missing) {
                $alerts[] = 'schema_change';
                lc_lp_order_notify('lp_schema_change', 'LP API 응답 필드 변경 감지', 'missing: ' . implode(',', $missing), 0);
            }
        }

        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'action' => 'error', 'lpo_id' => 0, 'message' => 'no orders table', 'alerts' => $alerts);
        }

        $existing = lc_lp_order_find_existing($n);
        $merchant = lc_lp_repo_get_merchant_by_code($n['merchant_code']);
        $partner_rate = is_array($merchant) ? (float) ($merchant['partner_rate'] ?? 70) : (float) (lc_lp_config_get()['default_partner_rate'] ?? 70);
        $shares = lc_lp_repo_calc_shares($n['lp_commission'], $partner_rate, $n['lc_status']);

        $pt_id = 0;
        $lpm_id = is_array($merchant) ? (int) $merchant['lpm_id'] : 0;
        $lpc_id = 0;
        $lc_status = $n['lc_status'];

        if ($n['u_id'] !== '') {
            $click = lc_lp_repo_find_click_by_u_id($n['u_id']);
            if (is_array($click)) {
                $pt_id = (int) $click['pt_id'];
                $lpc_id = (int) $click['lpc_id'];
                if ($lpm_id <= 0) {
                    $lpm_id = (int) $click['lpm_id'];
                }
            } else {
                $parsed = lc_lp_repo_parse_u_id($n['u_id']);
                if (is_array($parsed)) {
                    $pt_id = (int) $parsed['pt_id'];
                    if ($lpm_id <= 0) {
                        $lpm_id = (int) $parsed['lpm_id'];
                    }
                }
            }
        }

        if ($pt_id <= 0 && !lc_lp_status_is_canceled($lc_status)) {
            // 미매칭이어도 상태 동기화는 진행 (unmatched 플래그는 알림)
            $alerts[] = 'unmatched';
        }

        $raw_json = json_encode($api_row, JSON_UNESCAPED_UNICODE);
        $occurred_sql = $n['occurred_at'] ? ("'" . lc_sql_escape($n['occurred_at']) . "'") : 'NULL';

        if (!is_array($existing)) {
            // 신규 (POSTBACK 없이 API만 온 경우)
            if ($pt_id <= 0) {
                $lc_status = LC_LP_ORDER_UNMATCHED;
            }
            $shares = lc_lp_repo_calc_shares($n['lp_commission'], $partner_rate, $lc_status);
            $ok = lc_sql_query(" INSERT INTO `{$table}` SET
                trlog_id = '" . lc_sql_escape($n['trlog_id']) . "',
                uniq_id = '',
                pt_id = {$pt_id},
                lpm_id = {$lpm_id},
                lpc_id = {$lpc_id},
                u_id = '" . lc_sql_escape(mb_substr($n['u_id'], 0, 80)) . "',
                merchant_code = '" . lc_sql_escape($n['merchant_code']) . "',
                order_code = '" . lc_sql_escape($n['order_code']) . "',
                product_code = '" . lc_sql_escape($n['product_code']) . "',
                product_name = '" . lc_sql_escape($n['product_name']) . "',
                item_count = " . (int) $n['item_count'] . ",
                sales_amount = '" . lc_sql_escape(number_format($n['sales_amount'], 2, '.', '')) . "',
                lp_commission = '" . lc_sql_escape(number_format($n['lp_commission'], 2, '.', '')) . "',
                partner_rate = '" . lc_sql_escape(number_format($partner_rate, 2, '.', '')) . "',
                partner_expected = '" . lc_sql_escape(number_format($shares['partner_expected'], 2, '.', '')) . "',
                partner_confirmed = '" . lc_sql_escape(number_format($shares['partner_confirmed'], 2, '.', '')) . "',
                platform_margin = '" . lc_sql_escape(number_format($shares['platform_margin'], 2, '.', '')) . "',
                raw_status = '" . lc_sql_escape($n['raw_status']) . "',
                lc_status = '" . lc_sql_escape($lc_status) . "',
                occurred_at = {$occurred_sql},
                confirmed_at = " . (lc_lp_status_is_approved($lc_status) ? 'NOW()' : 'NULL') . ",
                cancelled_at = " . (lc_lp_status_is_canceled($lc_status) ? 'NOW()' : 'NULL') . ",
                last_synced_at = NOW(),
                raw_json = '" . lc_sql_escape((string) $raw_json) . "',
                created_at = NOW(),
                updated_at = NOW() ", false);
            $lpo_id = (int) lc_sql_insert_id();
            if (!$ok || $lpo_id <= 0) {
                return array('ok' => false, 'action' => 'error', 'lpo_id' => 0, 'message' => 'insert fail', 'alerts' => $alerts);
            }
            lc_lp_log_status_change($lpo_id, '', $lc_status, 0, $n['lp_commission'], 'api_sync:insert', $raw_json);
            if (lc_lp_status_is_approved($lc_status) && $pt_id > 0 && $shares['partner_confirmed'] > 0) {
                lc_lp_order_apply_ledger_delta($pt_id, $lpo_id, 0, $shares['partner_confirmed'], 'api_sync:approve');
            }
            if (in_array('unmatched', $alerts, true)) {
                lc_lp_order_notify('lp_unmatched', 'LP 미매칭 실적', 'trlog=' . $n['trlog_id'], $lpo_id);
            }
            return array('ok' => true, 'action' => 'inserted', 'lpo_id' => $lpo_id, 'message' => $lc_status, 'alerts' => $alerts);
        }

        // 업데이트
        $lpo_id = (int) $existing['lpo_id'];
        $from_status = (string) ($existing['lc_status'] ?? '');
        $from_comm = (float) ($existing['lp_commission'] ?? 0);
        $from_confirmed = (float) ($existing['partner_confirmed'] ?? 0);
        if ($pt_id <= 0) {
            $pt_id = (int) ($existing['pt_id'] ?? 0);
        }
        if ($lpm_id <= 0) {
            $lpm_id = (int) ($existing['lpm_id'] ?? 0);
        }
        $partner_rate = (float) ($existing['partner_rate'] ?? $partner_rate);
        $shares = lc_lp_repo_calc_shares($n['lp_commission'], $partner_rate, $lc_status);
        $to_confirmed = (float) $shares['partner_confirmed'];

        // 동일 데이터 반복 동기화 — 변경 없으면 스킵
        if (
            (string) ($existing['raw_status'] ?? '') === $n['raw_status']
            && abs($from_comm - $n['lp_commission']) < 0.005
            && $from_status === $lc_status
            && abs($from_confirmed - $to_confirmed) < 0.005
        ) {
            lc_sql_query(" UPDATE `{$table}` SET last_synced_at = NOW() WHERE lpo_id = {$lpo_id} ", false);
            return array('ok' => true, 'action' => 'unchanged', 'lpo_id' => $lpo_id, 'message' => 'noop', 'alerts' => $alerts);
        }

        $sets = array(
            "raw_status = '" . lc_sql_escape($n['raw_status']) . "'",
            "lc_status = '" . lc_sql_escape($lc_status) . "'",
            "sales_amount = '" . lc_sql_escape(number_format($n['sales_amount'], 2, '.', '')) . "'",
            "lp_commission = '" . lc_sql_escape(number_format($n['lp_commission'], 2, '.', '')) . "'",
            "partner_expected = '" . lc_sql_escape(number_format($shares['partner_expected'], 2, '.', '')) . "'",
            "partner_confirmed = '" . lc_sql_escape(number_format($to_confirmed, 2, '.', '')) . "'",
            "platform_margin = '" . lc_sql_escape(number_format($shares['platform_margin'], 2, '.', '')) . "'",
            "raw_json = '" . lc_sql_escape((string) $raw_json) . "'",
            "last_synced_at = NOW()",
            "updated_at = NOW()",
        );
        if ($n['trlog_id'] !== '' && (string) ($existing['trlog_id'] ?? '') !== $n['trlog_id']) {
            // 기존 행을 복합키로 찾았고 새 trlog — trlog 갱신 (UNIQUE 충돌 시 스킵)
            $sets[] = "trlog_id = '" . lc_sql_escape($n['trlog_id']) . "'";
        }
        if (lc_lp_status_is_approved($lc_status)) {
            $sets[] = 'confirmed_at = COALESCE(confirmed_at, NOW())';
        }
        if (lc_lp_status_is_canceled($lc_status)) {
            $sets[] = 'cancelled_at = COALESCE(cancelled_at, NOW())';
        }

        lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE lpo_id = {$lpo_id} ", false);
        lc_lp_log_status_change($lpo_id, $from_status, $lc_status, $from_comm, $n['lp_commission'], 'api_sync:' . $from_status . '->' . $lc_status, $raw_json);

        // 장부: 확정액 차액만 (한 번만 호출)
        if ($pt_id > 0) {
            $reason = 'api_sync';
            $entry_override = '';
            if (lc_lp_status_is_approved($lc_status) && !lc_lp_status_is_approved($from_status)) {
                $reason = 'api_sync:approve';
                $entry_override = LC_LP_LEDGER_CREDIT;
            } elseif (lc_lp_status_is_canceled($lc_status) && lc_lp_status_is_approved($from_status)) {
                $reason = 'api_sync:cancel_after_confirm';
                $entry_override = LC_LP_LEDGER_REVERSAL;
                lc_lp_order_notify('lp_cancel_after_confirm', '확정 후 취소', 'trlog=' . $n['trlog_id'] . ' lpo=' . $lpo_id, $lpo_id);
            } elseif (abs($from_comm - $n['lp_commission']) >= 0.005) {
                $reason = 'api_sync:commission_change';
                lc_lp_order_notify('lp_commission_change', '커미션 변경', 'trlog=' . $n['trlog_id'] . ' ' . $from_comm . '→' . $n['lp_commission'], $lpo_id);
            }
            lc_lp_order_apply_ledger_delta($pt_id, $lpo_id, $from_confirmed, $to_confirmed, $reason, $entry_override);
        }

        return array('ok' => true, 'action' => 'updated', 'lpo_id' => $lpo_id, 'message' => $from_status . '→' . $lc_status, 'alerts' => $alerts);
    }
}

if (!function_exists('lc_lp_client_fetch_orders_paged')) {
    /**
     * 페이지네이션 포함 전체 조회
     *
     * @return array{ok:bool,message:string,orders:array,list_count:int,pages:int,raw_sample:string}
     */
    function lc_lp_client_fetch_orders_paged($yyyymmdd, array $opts = array())
    {
        $per_page = isset($opts['per_page']) ? max(1, min(1000, (int) $opts['per_page'])) : 1000;
        $page = 1;
        $all = array();
        $list_count = 0;
        $pages = 0;
        $raw_sample = '';
        $max_pages = 50;

        while ($page <= $max_pages) {
            $call = array_merge($opts, array('page' => $page, 'per_page' => $per_page));
            $res = lc_lp_client_fetch_orders($yyyymmdd, $call);
            if (empty($res['ok'])) {
                return array(
                    'ok' => false,
                    'message' => (string) ($res['message'] ?? 'API 실패'),
                    'orders' => $all,
                    'list_count' => $list_count,
                    'pages' => $pages,
                    'raw_sample' => (string) ($res['raw'] ?? ''),
                    'result_code' => is_array($res['data'] ?? null) ? (string) (($res['data']['result'] ?? '')) : '',
                );
            }
            $data = is_array($res['data']) ? $res['data'] : array();
            $list_count = (int) ($data['list_count'] ?? 0);
            $chunk = isset($data['order_list']) && is_array($data['order_list']) ? $data['order_list'] : array();
            if ($page === 1) {
                $raw_sample = substr((string) ($res['raw'] ?? ''), 0, 4000);
            }
            foreach ($chunk as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }
            $pages = $page;
            if (count($chunk) < $per_page || count($all) >= $list_count) {
                break;
            }
            $page++;
            usleep(200000); // API 호출 제한 완화
        }

        return array(
            'ok' => true,
            'message' => 'OK',
            'orders' => $all,
            'list_count' => $list_count,
            'pages' => $pages,
            'raw_sample' => $raw_sample,
            'result_code' => '0',
        );
    }
}

if (!function_exists('lc_lp_sync_orders_dates')) {
    /**
     * 날짜 목록 생성: day | month | range | last7 | this_month | prev_month
     *
     * @return string[] YYYYMMDD or YYYYMM
     */
    function lc_lp_sync_orders_dates(array $options)
    {
        $mode = (string) ($options['mode'] ?? 'day');
        $date = (string) ($options['date'] ?? date('Ymd'));
        $out = array();

        if ($mode === 'month' || $mode === 'this_month') {
            $out[] = ($mode === 'this_month') ? date('Ym') : (preg_match('/^\d{6}$/', $date) ? $date : substr($date, 0, 6));
        } elseif ($mode === 'prev_month') {
            $out[] = date('Ym', strtotime('first day of last month'));
        } elseif ($mode === 'last7') {
            for ($i = 0; $i < 7; $i++) {
                $out[] = date('Ymd', strtotime("-{$i} days"));
            }
        } elseif ($mode === 'range' && !empty($options['from']) && !empty($options['to'])) {
            $from = strtotime((string) $options['from']);
            $to = strtotime((string) $options['to']);
            if ($from && $to && $from <= $to) {
                for ($t = $from; $t <= $to; $t += 86400) {
                    $out[] = date('Ymd', $t);
                    if (count($out) > 62) {
                        break;
                    }
                }
            }
        } else {
            // day
            $out[] = preg_match('/^\d{8}$/', $date) ? $date : date('Ymd');
        }

        return array_values(array_unique($out));
    }
}

if (!function_exists('lc_lp_sync_orders')) {
    /**
     * @param array $options mode, date, from, to, merchant_id, cancel_flag, test_mode, skip_lock, lpo_id(단건 재조회용 아님—날짜 기반)
     */
    function lc_lp_sync_orders(array $options = array())
    {
        $result = array(
            'ok' => false,
            'message' => '',
            'dates' => array(),
            'fetched' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'pages' => 0,
            'errors' => array(),
            'alerts' => array(),
            'log_id' => 0,
        );

        $cfg = lc_lp_config_get();
        if (trim((string) ($cfg['affiliate_code'] ?? '')) === '' || trim((string) ($cfg['api_auth_key'] ?? '')) === '') {
            $result['message'] = 'A코드 또는 auth_key가 없습니다.';
            lc_lp_order_notify('lp_auth_fail', 'LP 실적동기화 인증 실패', $result['message']);
            return $result;
        }

        $lock = null;
        if (empty($options['skip_lock'])) {
            $lock = lc_lp_sync_acquire_lock('orders');
            if ($lock === false) {
                $result['message'] = '다른 실적 동기화가 진행 중입니다.';
                return $result;
            }
        }

        try {
            $dates = lc_lp_sync_orders_dates($options);
            $result['dates'] = $dates;
            $log_id = lc_lp_log_sync_start(LC_LP_SYNC_ORDERS, 'translist.php', json_encode($options, JSON_UNESCAPED_UNICODE));
            $result['log_id'] = $log_id;

            $opts = array();
            if (isset($options['cancel_flag']) && $options['cancel_flag'] !== '') {
                $opts['cancel_flag'] = $options['cancel_flag'];
            }
            if (!empty($options['merchant_id'])) {
                $opts['merchant_id'] = $options['merchant_id'];
            }
            if (!empty($options['test_mode'])) {
                $opts['test'] = 'Y';
            }
            if (!empty($options['per_page'])) {
                $opts['per_page'] = (int) $options['per_page'];
            }

            foreach ($dates as $ymd) {
                $page_res = lc_lp_client_fetch_orders_paged($ymd, $opts);
                if (empty($page_res['ok'])) {
                    $result['failed']++;
                    $result['errors'][] = $ymd . ': ' . $page_res['message'];
                    if (($page_res['result_code'] ?? '') === '300') {
                        lc_lp_order_notify('lp_auth_fail', 'LP auth_key 오류', $page_res['message']);
                    }
                    continue; // 한 날짜 실패로 전체 중단하지 않음
                }
                $result['pages'] += (int) $page_res['pages'];
                $result['fetched'] += count($page_res['orders']);

                foreach ($page_res['orders'] as $row) {
                    try {
                        // 주문번호 필터
                        if (!empty($options['order_code']) && (string) ($row['o_cd'] ?? '') !== (string) $options['order_code']) {
                            continue;
                        }
                        $up = lc_lp_order_upsert_from_api($row);
                        if (empty($up['ok'])) {
                            $result['failed']++;
                            $result['errors'][] = ($row['trlog_id'] ?? '?') . ': ' . ($up['message'] ?? 'fail');
                            continue;
                        }
                        if (($up['action'] ?? '') === 'inserted') {
                            $result['inserted']++;
                        } elseif (($up['action'] ?? '') === 'updated') {
                            $result['updated']++;
                        } else {
                            $result['unchanged']++;
                        }
                        if (!empty($up['alerts'])) {
                            $result['alerts'] = array_values(array_unique(array_merge($result['alerts'], $up['alerts'])));
                        }
                    } catch (Throwable $e) {
                        $result['failed']++;
                        $result['errors'][] = ($row['trlog_id'] ?? '?') . ': ' . $e->getMessage();
                    }
                }
            }

            $processed = $result['inserted'] + $result['updated'] + $result['unchanged'];
            $ok = $result['failed'] === 0 || $processed > 0;
            $summary = sprintf(
                'fetched=%d inserted=%d updated=%d unchanged=%d failed=%d pages=%d',
                $result['fetched'], $result['inserted'], $result['updated'], $result['unchanged'], $result['failed'], $result['pages']
            );
            lc_lp_log_sync_finish(
                $log_id,
                $ok,
                200,
                substr(json_encode(array('dates' => $dates, 'alerts' => $result['alerts']), JSON_UNESCAPED_UNICODE), 0, 8000),
                $processed,
                $result['errors'] ? implode('; ', array_slice($result['errors'], 0, 8)) : $summary
            );
            if ($ok) {
                lc_lp_config_touch_sync('orders');
            } else {
                lc_lp_order_notify('lp_sync_fail', 'LP 실적 동기화 실패', $summary);
            }

            $result['ok'] = $ok;
            $result['message'] = $ok ? ('실적 동기화 완료. ' . $summary) : ('실적 동기화 부분 실패. ' . $summary);
            return $result;
        } finally {
            if ($lock) {
                lc_lp_sync_release_lock($lock);
            }
        }
    }
}

if (!function_exists('lc_lp_sync_order_one')) {
    /**
     * 단건: DB 주문 기준 발생일로 API 재조회 후 해당 trlog만 반영
     */
    function lc_lp_sync_order_one($lpo_id)
    {
        $lpo_id = (int) $lpo_id;
        $table = lc_table('lp_orders');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpo_id = {$lpo_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            return array('ok' => false, 'message' => '실적을 찾을 수 없습니다.');
        }
        $day = '';
        if (!empty($row['occurred_at'])) {
            $day = date('Ymd', strtotime($row['occurred_at']));
        }
        if ($day === '') {
            $day = date('Ymd');
        }
        $page_res = lc_lp_client_fetch_orders_paged($day, array(
            'merchant_id' => (string) ($row['merchant_code'] ?? ''),
        ));
        if (empty($page_res['ok'])) {
            return array('ok' => false, 'message' => $page_res['message']);
        }
        $trlog = (string) ($row['trlog_id'] ?? '');
        $found = null;
        foreach ($page_res['orders'] as $api) {
            if ((string) ($api['trlog_id'] ?? '') === $trlog) {
                $found = $api;
                break;
            }
            if (
                (string) ($api['o_cd'] ?? '') === (string) ($row['order_code'] ?? '')
                && (string) ($api['p_cd'] ?? '') === (string) ($row['product_code'] ?? '')
                && (string) ($api['user_id'] ?? '') === (string) ($row['u_id'] ?? '')
            ) {
                $found = $api;
                break;
            }
        }
        if (!$found) {
            return array('ok' => false, 'message' => 'API에서 해당 실적을 찾지 못했습니다.');
        }
        $up = lc_lp_order_upsert_from_api($found);
        return array('ok' => !empty($up['ok']), 'message' => $up['message'] ?? '', 'result' => $up);
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * Linkprice UI — 관리자/파트너 목록·통계·CSV·연결테스트 (7단계)
 * ═══════════════════════════════════════════════════════════════════════════ */

if (!function_exists('lc_lp_mask_order_code')) {
    function lc_lp_mask_order_code($code)
    {
        $code = (string) $code;
        $len = mb_strlen($code);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        if ($len <= 8) {
            return mb_substr($code, 0, 2) . str_repeat('*', $len - 4) . mb_substr($code, -2);
        }
        return mb_substr($code, 0, 3) . str_repeat('*', min(6, $len - 6)) . mb_substr($code, -3);
    }
}

if (!function_exists('lc_lp_csv_safe')) {
    /** CSV 수식 삽입 방지 */
    function lc_lp_csv_safe($value)
    {
        $v = (string) $value;
        if ($v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) {
            return "'" . $v;
        }
        return $v;
    }
}

if (!function_exists('lc_lp_csv_row')) {
    function lc_lp_csv_row(array $cols)
    {
        $out = array();
        foreach ($cols as $c) {
            $v = lc_lp_csv_safe($c);
            $v = str_replace('"', '""', $v);
            $out[] = '"' . $v . '"';
        }
        return implode(',', $out);
    }
}

if (!function_exists('lc_lp_order_to_api')) {
    /**
     * @param bool $partner_view 파트너용 — 주문번호 마스킹, 원본 JSON 비노출
     */
    function lc_lp_order_to_api(array $row, $partner_view = false, $include_raw = false)
    {
        $order_code = (string) ($row['order_code'] ?? '');
        $item = array(
            'lpoId'            => (int) ($row['lpo_id'] ?? 0),
            'trlogId'          => (string) ($row['trlog_id'] ?? ''),
            'uniqId'           => (string) ($row['uniq_id'] ?? ''),
            'ptId'             => (int) ($row['pt_id'] ?? 0),
            'partnerName'      => (string) ($row['partner_name'] ?? $row['pt_name'] ?? ''),
            'partnerCode'      => (string) ($row['partner_code'] ?? $row['pt_code'] ?? ''),
            'lpmId'            => (int) ($row['lpm_id'] ?? 0),
            'lpcId'            => (int) ($row['lpc_id'] ?? 0),
            'uId'              => (string) ($row['u_id'] ?? ''),
            'merchantCode'     => (string) ($row['merchant_code'] ?? ''),
            'merchantName'     => (string) ($row['merchant_name'] ?? ''),
            'orderCode'        => $partner_view ? lc_lp_mask_order_code($order_code) : $order_code,
            'productCode'      => (string) ($row['product_code'] ?? ''),
            'productName'      => (string) ($row['product_name'] ?? ''),
            'itemCount'        => (int) ($row['item_count'] ?? 1),
            'salesAmount'      => (float) ($row['sales_amount'] ?? 0),
            'lpCommission'     => (float) ($row['lp_commission'] ?? 0),
            'partnerRate'      => (float) ($row['partner_rate'] ?? 0),
            'partnerExpected'  => (float) ($row['partner_expected'] ?? 0),
            'partnerConfirmed' => (float) ($row['partner_confirmed'] ?? 0),
            'platformMargin'   => $partner_view ? null : (float) ($row['platform_margin'] ?? 0),
            'rawStatus'        => (string) ($row['raw_status'] ?? ''),
            'lcStatus'         => (string) ($row['lc_status'] ?? ''),
            'occurredAt'       => $row['occurred_at'] ?? null,
            'confirmedAt'      => $row['confirmed_at'] ?? null,
            'cancelledAt'      => $row['cancelled_at'] ?? null,
            'lastSyncedAt'     => $row['last_synced_at'] ?? null,
            'unmatched'        => ((int) ($row['pt_id'] ?? 0) <= 0) || ((string) ($row['lc_status'] ?? '') === LC_LP_ORDER_UNMATCHED),
            'settleHint'       => lc_lp_order_settle_hint((string) ($row['lc_status'] ?? '')),
        );
        if ($include_raw && !$partner_view) {
            $raw = (string) ($row['raw_json'] ?? '');
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $item['rawJson'] = is_array($decoded) ? $decoded : $raw;
        }
        return $item;
    }
}

if (!function_exists('lc_lp_order_settle_hint')) {
    function lc_lp_order_settle_hint($lc_status)
    {
        $s = (string) $lc_status;
        if (lc_lp_status_is_approved($s)) {
            return '확정 완료 — 출금 가능 수익에 반영됩니다.';
        }
        if (lc_lp_status_is_canceled($s) || $s === LC_LP_ORDER_CANCEL_PENDING) {
            return '취소 처리 — 수익에서 제외됩니다.';
        }
        if ($s === LC_LP_ORDER_REVIEW || $s === LC_LP_ORDER_HOLD) {
            return '정산 대기 — 광고주·링크커넥트 검수 후 확정됩니다.';
        }
        if ($s === LC_LP_ORDER_UNMATCHED) {
            return '미매칭 — 관리자 확인이 필요합니다.';
        }
        return '예상 실적 — 반품·취소·검수에 따라 변경될 수 있습니다.';
    }
}

if (!function_exists('lc_lp_orders_list')) {
    /**
     * @param array $filters pt_id(필수 파트너 스코프), occurred_from/to, confirmed_from/to, merchant, partner, order, product, status, unmatched, limit, offset
     */
    function lc_lp_orders_list(array $filters = array())
    {
        $table = lc_table('lp_orders');
        if (!lc_db_table_exists($table)) {
            return array('items' => array(), 'total' => 0);
        }
        $partners = lc_table('partners');
        $merchants = lc_table('lp_merchants');
        $where = array('1=1');

        if (isset($filters['pt_id']) && (int) $filters['pt_id'] > 0) {
            $where[] = 'o.pt_id = ' . (int) $filters['pt_id'];
        }
        if (!empty($filters['occurred_from'])) {
            $where[] = "o.occurred_at >= '" . lc_sql_escape($filters['occurred_from']) . " 00:00:00'";
        }
        if (!empty($filters['occurred_to'])) {
            $where[] = "o.occurred_at <= '" . lc_sql_escape($filters['occurred_to']) . " 23:59:59'";
        }
        if (!empty($filters['confirmed_from'])) {
            $where[] = "o.confirmed_at >= '" . lc_sql_escape($filters['confirmed_from']) . " 00:00:00'";
        }
        if (!empty($filters['confirmed_to'])) {
            $where[] = "o.confirmed_at <= '" . lc_sql_escape($filters['confirmed_to']) . " 23:59:59'";
        }
        if (!empty($filters['merchant'])) {
            $m = lc_sql_escape('%' . trim((string) $filters['merchant']) . '%');
            $where[] = "(o.merchant_code LIKE '{$m}' OR m.merchant_name LIKE '{$m}')";
        }
        if (!empty($filters['partner'])) {
            $p = lc_sql_escape('%' . trim((string) $filters['partner']) . '%');
            $where[] = "(p.pt_name LIKE '{$p}' OR p.pt_code LIKE '{$p}' OR o.pt_id = '" . lc_sql_escape(trim((string) $filters['partner'])) . "')";
        }
        if (!empty($filters['order'])) {
            $where[] = "o.order_code LIKE '%" . lc_sql_escape(trim((string) $filters['order'])) . "%'";
        }
        if (!empty($filters['product'])) {
            $pr = lc_sql_escape('%' . trim((string) $filters['product']) . '%');
            $where[] = "(o.product_name LIKE '{$pr}' OR o.product_code LIKE '{$pr}')";
        }
        if (!empty($filters['status'])) {
            $where[] = "o.lc_status = '" . lc_sql_escape((string) $filters['status']) . "'";
        }
        if (isset($filters['unmatched']) && $filters['unmatched'] !== '' && $filters['unmatched'] !== null) {
            if (!empty($filters['unmatched'])) {
                $where[] = "(o.pt_id = 0 OR o.lc_status = '" . lc_sql_escape(LC_LP_ORDER_UNMATCHED) . "')";
            } else {
                $where[] = "o.pt_id > 0 AND o.lc_status <> '" . lc_sql_escape(LC_LP_ORDER_UNMATCHED) . "'";
            }
        }
        if (!empty($filters['trlog'])) {
            $where[] = "o.trlog_id = '" . lc_sql_escape(trim((string) $filters['trlog'])) . "'";
        }

        $where_sql = implode(' AND ', $where);
        $join = " FROM `{$table}` o
            LEFT JOIN `{$merchants}` m ON m.lpm_id = o.lpm_id
            LEFT JOIN `{$partners}` p ON p.pt_id = o.pt_id ";
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt {$join} WHERE {$where_sql} ", false);
        $total = (int) ($total_row['cnt'] ?? 0);
        $limit = isset($filters['limit']) ? max(1, min(500, (int) $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;

        $rows = lc_sql_query(" SELECT o.*, m.merchant_name, p.pt_name AS partner_name, p.pt_code AS partner_code
            {$join} WHERE {$where_sql}
            ORDER BY o.occurred_at DESC, o.lpo_id DESC
            LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $items[] = $row;
            }
        }
        return array('items' => $items, 'total' => $total);
    }
}

if (!function_exists('lc_lp_order_status_logs')) {
    function lc_lp_order_status_logs($lpo_id, $limit = 50)
    {
        $lpo_id = (int) $lpo_id;
        $table = lc_table('lp_order_status_logs');
        if ($lpo_id <= 0 || !lc_db_table_exists($table)) {
            return array();
        }
        $limit = max(1, min(200, (int) $limit));
        $rows = lc_sql_query(" SELECT * FROM `{$table}` WHERE lpo_id = {$lpo_id} ORDER BY lpsl_id DESC LIMIT {$limit} ", false);
        $out = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $out[] = array(
                    'lpslId'         => (int) ($row['lpsl_id'] ?? 0),
                    'fromStatus'     => (string) ($row['from_status'] ?? ''),
                    'toStatus'       => (string) ($row['to_status'] ?? ''),
                    'fromCommission' => (float) ($row['from_commission'] ?? 0),
                    'toCommission'   => (float) ($row['to_commission'] ?? 0),
                    'reason'         => (string) ($row['reason'] ?? ''),
                    'changedAt'      => $row['changed_at'] ?? null,
                );
            }
        }
        return $out;
    }
}

if (!function_exists('lc_lp_order_link_partner')) {
    /**
     * 미매칭 실적에 파트너 수동 연결 (관리자)
     */
    function lc_lp_order_link_partner($lpo_id, $pt_id, $admin_mb_id = '')
    {
        $lpo_id = (int) $lpo_id;
        $pt_id = (int) $pt_id;
        $table = lc_table('lp_orders');
        if ($lpo_id <= 0 || $pt_id <= 0 || !lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => '잘못된 요청입니다.');
        }
        $order = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpo_id = {$lpo_id} LIMIT 1 ", false);
        if (!is_array($order)) {
            return array('ok' => false, 'message' => '실적을 찾을 수 없습니다.');
        }
        $partners = lc_table('partners');
        $partner = lc_db_table_exists($partners)
            ? lc_sql_fetch(" SELECT pt_id, pt_name, pt_code FROM `{$partners}` WHERE pt_id = {$pt_id} LIMIT 1 ", false)
            : null;
        if (!is_array($partner)) {
            return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
        }
        $from = (string) ($order['lc_status'] ?? '');
        $to = ($from === LC_LP_ORDER_UNMATCHED || $from === '') ? LC_LP_ORDER_PENDING : $from;
        $prev_pt = (int) ($order['pt_id'] ?? 0);
        lc_sql_query(" UPDATE `{$table}` SET
            pt_id = {$pt_id},
            lc_status = '" . lc_sql_escape($to) . "',
            updated_at = NOW()
            WHERE lpo_id = {$lpo_id} ", false);
        $reason = 'admin_link_partner:' . $pt_id . ($admin_mb_id !== '' ? (':' . $admin_mb_id) : '');
        lc_lp_log_status_change($lpo_id, $from, $to, (float) ($order['lp_commission'] ?? 0), (float) ($order['lp_commission'] ?? 0), $reason, '');
        // 이미 확정된 미매칭 실적이면 연결 시 CREDIT (이전 pt 없으면 0→confirmed)
        $confirmed = (float) ($order['partner_confirmed'] ?? 0);
        if ($prev_pt <= 0 && $confirmed > 0 && lc_lp_status_is_approved($to)) {
            lc_lp_order_apply_ledger_delta($pt_id, $lpo_id, 0, $confirmed, $reason, LC_LP_LEDGER_CREDIT);
        } elseif ($prev_pt <= 0 && $confirmed <= 0 && lc_lp_status_is_approved($from)) {
            // raw approved 인데 confirmed 미계산된 경우 재계산
            $shares = lc_lp_repo_calc_shares(
                (float) ($order['lp_commission'] ?? 0),
                (float) ($order['partner_rate'] ?? 70),
                $to
            );
            if ($shares['partner_confirmed'] > 0) {
                lc_sql_query(" UPDATE `{$table}` SET
                    partner_expected = '" . lc_sql_escape(number_format($shares['partner_expected'], 2, '.', '')) . "',
                    partner_confirmed = '" . lc_sql_escape(number_format($shares['partner_confirmed'], 2, '.', '')) . "',
                    platform_margin = '" . lc_sql_escape(number_format($shares['platform_margin'], 2, '.', '')) . "'
                    WHERE lpo_id = {$lpo_id} ", false);
                lc_lp_order_apply_ledger_delta($pt_id, $lpo_id, 0, $shares['partner_confirmed'], $reason, LC_LP_LEDGER_CREDIT);
            }
        }
        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write('lp_order_link_partner', 'lp_order', $lpo_id, '미매칭 파트너 연결', array('pt_id' => $pt_id));
        }
        return array('ok' => true, 'message' => '파트너가 연결되었습니다.');
    }
}

if (!function_exists('lc_lp_clicks_list')) {
    function lc_lp_clicks_list(array $filters = array())
    {
        $table = lc_table('lp_clicks');
        if (!lc_db_table_exists($table)) {
            return array('items' => array(), 'total' => 0, 'summary' => array('today' => 0, 'month' => 0, 'total' => 0));
        }
        $merchants = lc_table('lp_merchants');
        $partners = lc_table('partners');
        $where = array('1=1');
        if (isset($filters['pt_id']) && (int) $filters['pt_id'] > 0) {
            $where[] = 'c.pt_id = ' . (int) $filters['pt_id'];
        }
        if (!empty($filters['merchant'])) {
            $m = lc_sql_escape('%' . trim((string) $filters['merchant']) . '%');
            $where[] = "(m.merchant_code LIKE '{$m}' OR m.merchant_name LIKE '{$m}')";
        }
        if (!empty($filters['from'])) {
            $where[] = "c.clicked_at >= '" . lc_sql_escape($filters['from']) . " 00:00:00'";
        }
        if (!empty($filters['to'])) {
            $where[] = "c.clicked_at <= '" . lc_sql_escape($filters['to']) . " 23:59:59'";
        }
        $where_sql = implode(' AND ', $where);
        $join = " FROM `{$table}` c
            LEFT JOIN `{$merchants}` m ON m.lpm_id = c.lpm_id
            LEFT JOIN `{$partners}` p ON p.pt_id = c.pt_id ";
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt {$join} WHERE {$where_sql} ", false);
        $total = (int) ($total_row['cnt'] ?? 0);
        $limit = isset($filters['limit']) ? max(1, min(500, (int) $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $rows = lc_sql_query(" SELECT c.*, m.merchant_name, m.merchant_code, p.pt_name AS partner_name, p.pt_code AS partner_code
            {$join} WHERE {$where_sql}
            ORDER BY c.clicked_at DESC, c.lpc_id DESC
            LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $items[] = array(
                    'lpcId'        => (int) ($row['lpc_id'] ?? 0),
                    'ptId'         => (int) ($row['pt_id'] ?? 0),
                    'partnerName'  => (string) ($row['partner_name'] ?? ''),
                    'partnerCode'  => (string) ($row['partner_code'] ?? ''),
                    'lpmId'        => (int) ($row['lpm_id'] ?? 0),
                    'merchantCode' => (string) ($row['merchant_code'] ?? ''),
                    'merchantName' => (string) ($row['merchant_name'] ?? ''),
                    'uId'          => (string) ($row['u_id'] ?? ''),
                    'device'       => (string) ($row['device'] ?? ''),
                    'ip'           => (string) ($row['ip'] ?? ''),
                    'clickedAt'    => $row['clicked_at'] ?? null,
                );
            }
        }
        $pt_scope = (isset($filters['pt_id']) && (int) $filters['pt_id'] > 0) ? (' AND pt_id = ' . (int) $filters['pt_id']) : '';
        $today = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE clicked_at >= CURDATE(){$pt_scope} ", false);
        $month = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE clicked_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01'){$pt_scope} ", false);
        $all = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE 1=1{$pt_scope} ", false);
        return array(
            'items' => $items,
            'total' => $total,
            'summary' => array(
                'today' => (int) ($today['cnt'] ?? 0),
                'month' => (int) ($month['cnt'] ?? 0),
                'total' => (int) ($all['cnt'] ?? 0),
            ),
        );
    }
}

if (!function_exists('lc_lp_merchant_stats_map')) {
    /** 관리자 광고주 목록용 클릭/실적 집계 */
    function lc_lp_merchant_stats_map()
    {
        $out = array();
        $clicks_t = lc_table('lp_clicks');
        if (lc_db_table_exists($clicks_t)) {
            $res = lc_sql_query(" SELECT lpm_id, COUNT(*) AS cnt FROM `{$clicks_t}` GROUP BY lpm_id ", false);
            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $id = (int) $row['lpm_id'];
                    $out[$id] = array('clicks' => (int) $row['cnt'], 'expected' => 0, 'confirmed' => 0, 'canceled' => 0);
                }
            }
        }
        $orders_t = lc_table('lp_orders');
        if (lc_db_table_exists($orders_t)) {
            $res = lc_sql_query(" SELECT lpm_id,
                SUM(CASE WHEN lc_status IN ('expected','pending','review','hold') THEN 1 ELSE 0 END) AS expected_cnt,
                SUM(CASE WHEN lc_status IN ('confirmed','approved') THEN 1 ELSE 0 END) AS confirmed_cnt,
                SUM(CASE WHEN lc_status IN ('canceled','cancelled','cancel_pending') THEN 1 ELSE 0 END) AS canceled_cnt
                FROM `{$orders_t}` GROUP BY lpm_id ", false);
            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $id = (int) $row['lpm_id'];
                    if (!isset($out[$id])) {
                        $out[$id] = array('clicks' => 0, 'expected' => 0, 'confirmed' => 0, 'canceled' => 0);
                    }
                    $out[$id]['expected'] = (int) ($row['expected_cnt'] ?? 0);
                    $out[$id]['confirmed'] = (int) ($row['confirmed_cnt'] ?? 0);
                    $out[$id]['canceled'] = (int) ($row['canceled_cnt'] ?? 0);
                }
            }
        }
        return $out;
    }
}

if (!function_exists('lc_lp_partner_dashboard_stats')) {
    function lc_lp_partner_dashboard_stats($pt_id)
    {
        $pt_id = (int) $pt_id;
        $empty = array(
            'clicksToday' => 0, 'clicksMonth' => 0,
            'expectedOrders' => 0, 'confirmedOrders' => 0, 'canceledOrders' => 0,
            'expectedEarnings' => 0.0, 'confirmedEarnings' => 0.0, 'withdrawable' => 0.0,
        );
        if ($pt_id <= 0) {
            return $empty;
        }
        $clicks = lc_lp_clicks_list(array('pt_id' => $pt_id, 'limit' => 1));
        $orders_t = lc_table('lp_orders');
        if (!lc_db_table_exists($orders_t)) {
            return array_merge($empty, array(
                'clicksToday' => $clicks['summary']['today'],
                'clicksMonth' => $clicks['summary']['month'],
            ));
        }
        $row = lc_sql_fetch(" SELECT
            SUM(CASE WHEN lc_status IN ('expected','pending','review','hold') THEN 1 ELSE 0 END) AS expected_cnt,
            SUM(CASE WHEN lc_status IN ('confirmed','approved') THEN 1 ELSE 0 END) AS confirmed_cnt,
            SUM(CASE WHEN lc_status IN ('canceled','cancelled','cancel_pending') THEN 1 ELSE 0 END) AS canceled_cnt,
            SUM(CASE WHEN lc_status IN ('expected','pending','review','hold') THEN partner_expected ELSE 0 END) AS expected_sum,
            SUM(CASE WHEN lc_status IN ('confirmed','approved') THEN partner_confirmed ELSE 0 END) AS confirmed_sum
            FROM `{$orders_t}` WHERE pt_id = {$pt_id} ", false);
        $confirmed = (float) ($row['confirmed_sum'] ?? 0);
        return array(
            'clicksToday'       => (int) $clicks['summary']['today'],
            'clicksMonth'       => (int) $clicks['summary']['month'],
            'expectedOrders'    => (int) ($row['expected_cnt'] ?? 0),
            'confirmedOrders'   => (int) ($row['confirmed_cnt'] ?? 0),
            'canceledOrders'    => (int) ($row['canceled_cnt'] ?? 0),
            'expectedEarnings'  => round((float) ($row['expected_sum'] ?? 0), 2),
            'confirmedEarnings' => round($confirmed, 2),
            'withdrawable'      => round($confirmed, 2),
        );
    }
}

if (!function_exists('lc_lp_ledger_list')) {
    function lc_lp_ledger_list(array $filters = array())
    {
        $table = lc_table('lp_ledger');
        if (!lc_db_table_exists($table)) {
            return array('items' => array(), 'total' => 0);
        }
        $partners = lc_table('partners');
        $where = array('1=1');
        if (isset($filters['pt_id']) && (int) $filters['pt_id'] > 0) {
            $where[] = 'l.pt_id = ' . (int) $filters['pt_id'];
        }
        if (!empty($filters['entry_type'])) {
            $where[] = "l.entry_type = '" . lc_sql_escape((string) $filters['entry_type']) . "'";
        }
        if (!empty($filters['from'])) {
            $where[] = "l.created_at >= '" . lc_sql_escape($filters['from']) . " 00:00:00'";
        }
        if (!empty($filters['to'])) {
            $where[] = "l.created_at <= '" . lc_sql_escape($filters['to']) . " 23:59:59'";
        }
        $where_sql = implode(' AND ', $where);
        $join = " FROM `{$table}` l LEFT JOIN `{$partners}` p ON p.pt_id = l.pt_id ";
        $total_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt {$join} WHERE {$where_sql} ", false);
        $limit = isset($filters['limit']) ? max(1, min(500, (int) $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $rows = lc_sql_query(" SELECT l.*, p.pt_name AS partner_name, p.pt_code AS partner_code
            {$join} WHERE {$where_sql}
            ORDER BY l.lpl_id DESC LIMIT {$offset}, {$limit} ", false);
        $items = array();
        if ($rows) {
            while ($row = sql_fetch_array($rows)) {
                $items[] = array(
                    'lplId'         => (int) ($row['lpl_id'] ?? 0),
                    'ptId'          => (int) ($row['pt_id'] ?? 0),
                    'partnerName'   => (string) ($row['partner_name'] ?? ''),
                    'partnerCode'   => (string) ($row['partner_code'] ?? ''),
                    'lpoId'         => (int) ($row['lpo_id'] ?? 0),
                    'entryType'     => (string) ($row['entry_type'] ?? ''),
                    'amount'        => (float) ($row['amount'] ?? 0),
                    'balanceAfter'  => (float) ($row['balance_after'] ?? 0),
                    'memo'          => (string) ($row['memo'] ?? ''),
                    'createdAt'     => $row['created_at'] ?? null,
                );
            }
        }
        return array('items' => $items, 'total' => (int) ($total_row['cnt'] ?? 0));
    }
}

if (!function_exists('lc_lp_connection_test')) {
    function lc_lp_connection_test($test_mode = false)
    {
        $cfg = lc_lp_config_get();
        if (trim((string) ($cfg['affiliate_code'] ?? '')) === '' || trim((string) ($cfg['api_auth_key'] ?? '')) === '') {
            return array('ok' => false, 'message' => 'A코드 또는 인증키가 없습니다.', 'resultCode' => '');
        }
        $ymd = date('Ymd');
        $opts = array('page' => 1, 'per_page' => 1);
        if ($test_mode) {
            $opts['test'] = 'Y';
        }
        $res = lc_lp_client_fetch_orders($ymd, $opts);
        $code = '';
        if (is_array($res['data'] ?? null)) {
            $code = (string) ($res['data']['result'] ?? '');
        }
        if (!empty($res['ok'])) {
            return array('ok' => true, 'message' => '연결 성공 (실적조회 API)', 'resultCode' => $code !== '' ? $code : '0');
        }
        $msg = (string) ($res['message'] ?? '연결 실패');
        if ($code === '300') {
            $msg = '인증키(auth_key)가 올바르지 않습니다.';
        }
        return array('ok' => false, 'message' => $msg, 'resultCode' => $code);
    }
}

if (!function_exists('lc_lp_ui_security_settings')) {
    function lc_lp_ui_security_settings()
    {
        $test = function_exists('lc_settings_get') ? (string) lc_settings_get('lpTestMode', '0') : '0';
        return array(
            'testMode'            => $test === '1' || $test === 'true',
            'postbackIpEnabled'   => function_exists('lc_settings_get_bool') ? lc_settings_get_bool('lpPostbackIpEnabled') : false,
            'postbackIpAllowlist' => function_exists('lc_settings_get') ? (string) lc_settings_get('lpPostbackIpAllowlist', '') : '',
            'cronTokenSet'        => function_exists('lc_settings_get') ? trim((string) lc_settings_get('lpCronToken', '')) !== '' : false,
        );
    }
}
