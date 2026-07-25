<?php
/**
 * 관리자 — 링크프라이스 CPS 광고주 API
 */
require_once __DIR__ . '/_common.php';

if (!function_exists('lc_lp_admin_api_csrf_ok')) {
    function lc_lp_admin_api_csrf_ok()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $check = function ($url) use ($host) {
            if ($url === '' || $host === '') {
                return false;
            }
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['host'])) {
                return false;
            }
            return strtolower((string) $parts['host']) === $host;
        };
        if ($origin !== '') {
            return $check($origin);
        }
        if ($referer !== '') {
            return $check($referer);
        }
        return false;
    }
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_admin();
    $view = isset($_GET['view']) ? (string) $_GET['view'] : 'merchants';

    if ($view === 'setup') {
        lc_api_success(array_merge(
            array('dbReady' => lc_db_table_exists(lc_table('lp_merchants'))),
            function_exists('lc_lp_setup_snapshot') ? lc_lp_setup_snapshot() : array()
        ));
    }

    if ($view === 'e2e') {
        lc_api_success(array_merge(
            array('dbReady' => lc_db_table_exists(lc_table('lp_merchants'))),
            function_exists('lc_lp_admin_e2e_snapshot') ? lc_lp_admin_e2e_snapshot() : array()
        ));
    }

    if ($view === 'config') {
        lc_api_success(array(
            'config'  => lc_lp_config_to_api(),
            'dbReady' => lc_db_table_exists(lc_table('lp_merchants')),
        ));
    }

    if ($view === 'sync_logs') {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $type = isset($_GET['type']) ? (string) $_GET['type'] : LC_LP_SYNC_MERCHANTS;
        lc_api_success(array(
            'items'   => lc_lp_sync_logs_recent($limit, $type),
            'dbReady' => lc_db_table_exists(lc_table('lp_sync_logs')),
        ));
    }

    if ($view === 'status_map') {
        $map = array();
        foreach (lc_lp_status_map() as $raw => $lc) {
            $map[] = array('rawStatus' => $raw, 'lcStatus' => $lc);
        }
        lc_api_success(array('items' => $map));
    }

    if ($view === 'merchant') {
        $lpm_id = isset($_GET['lpmId']) ? (int) $_GET['lpmId'] : 0;
        if ($lpm_id <= 0) {
            lc_api_error('lpmId가 필요합니다.', 'INVALID_ID', 400);
        }
        $table = lc_table('lp_merchants');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpm_id = {$lpm_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            lc_api_error('광고주를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item' => lc_lp_merchant_to_api($row, true),
        ));
    }

    if ($view === 'postbacks') {
        $filters = array(
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        if (!empty($_GET['status'])) {
            $filters['status'] = (string) $_GET['status'];
        }
        if (!empty($_GET['q'])) {
            $filters['q'] = (string) $_GET['q'];
        }
        if (!empty($_GET['merchant'])) {
            $filters['merchant'] = (string) $_GET['merchant'];
        }
        if (!empty($_GET['order'])) {
            $filters['order'] = (string) $_GET['order'];
        }
        $list = lc_lp_postbacks_list($filters);
        $items = array();
        foreach ($list['items'] as $row) {
            $items[] = lc_lp_postback_to_api($row, false);
        }
        lc_api_success(array(
            'items'   => $items,
            'total'   => $list['total'],
            'dbReady' => lc_db_table_exists(lc_table('lp_postbacks')),
        ));
    }

    if ($view === 'postback') {
        $lpp_id = isset($_GET['lppId']) ? (int) $_GET['lppId'] : 0;
        if ($lpp_id <= 0) {
            lc_api_error('lppId가 필요합니다.', 'INVALID_ID', 400);
        }
        $table = lc_table('lp_postbacks');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpp_id = {$lpp_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            lc_api_error('POSTBACK을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $order = null;
        $lpo_id = (int) ($row['lpo_id'] ?? 0);
        if ($lpo_id > 0 && lc_db_table_exists(lc_table('lp_orders'))) {
            $order = lc_sql_fetch(" SELECT * FROM `" . lc_table('lp_orders') . "` WHERE lpo_id = {$lpo_id} LIMIT 1 ", false);
        }
        lc_api_success(array(
            'item'  => lc_lp_postback_to_api($row, true),
            'order' => is_array($order) ? array(
                'lpoId'            => (int) $order['lpo_id'],
                'ptId'             => (int) $order['pt_id'],
                'lpmId'            => (int) $order['lpm_id'],
                'lcStatus'         => (string) $order['lc_status'],
                'lpCommission'     => (float) $order['lp_commission'],
                'partnerRate'      => (float) $order['partner_rate'],
                'partnerExpected'  => (float) $order['partner_expected'],
                'partnerConfirmed' => (float) $order['partner_confirmed'],
                'platformMargin'   => (float) $order['platform_margin'],
                'merchantCode'     => (string) $order['merchant_code'],
                'orderCode'        => (string) $order['order_code'],
            ) : null,
        ));
    }

    if ($view === 'merchants' || $view === '') {
        $filters = array(
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 200,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        if (isset($_GET['approved']) && $_GET['approved'] !== '') {
            $filters['approved'] = $_GET['approved'] === '1' || $_GET['approved'] === 'true';
        }
        if (isset($_GET['syncActive']) && $_GET['syncActive'] !== '') {
            $filters['sync_active'] = $_GET['syncActive'] === '1' || $_GET['syncActive'] === 'true';
        }
        if (isset($_GET['visible']) && $_GET['visible'] !== '') {
            $filters['visible'] = $_GET['visible'] === '1' || $_GET['visible'] === 'true';
        }
        if (isset($_GET['deeplink']) && ($_GET['deeplink'] === '1' || $_GET['deeplink'] === 'true')) {
            $filters['deeplink'] = true;
        }
        if (!empty($_GET['q'])) {
            $filters['q'] = (string) $_GET['q'];
        }
        if (!empty($_GET['code'])) {
            $filters['code'] = (string) $_GET['code'];
        }

        $list = lc_lp_merchants_list($filters);
        $stats = function_exists('lc_lp_merchant_stats_map') ? lc_lp_merchant_stats_map() : array();
        $items = array();
        foreach ($list['items'] as $row) {
            $id = (int) ($row['lpm_id'] ?? 0);
            $st = $stats[$id] ?? array();
            $row['_stats_clicks'] = $st['clicks'] ?? 0;
            $row['_stats_expected'] = $st['expected'] ?? 0;
            $row['_stats_confirmed'] = $st['confirmed'] ?? 0;
            $row['_stats_canceled'] = $st['canceled'] ?? 0;
            $items[] = lc_lp_merchant_to_api($row, false);
        }

        lc_api_success(array(
            'items'    => $items,
            'total'    => $list['total'],
            'config'   => lc_lp_config_to_api(),
            'syncLogs' => lc_lp_sync_logs_recent(5, LC_LP_SYNC_MERCHANTS),
            'dbReady'  => lc_db_table_exists(lc_table('lp_merchants')),
        ));
    }

    if ($view === 'orders') {
        $filters = array(
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        foreach (array('occurred_from' => 'occurredFrom', 'occurred_to' => 'occurredTo', 'confirmed_from' => 'confirmedFrom', 'confirmed_to' => 'confirmedTo', 'merchant' => 'merchant', 'partner' => 'partner', 'order' => 'order', 'product' => 'product', 'status' => 'status', 'trlog' => 'trlog') as $dbk => $gk) {
            $v = $_GET[$gk] ?? $_GET[$dbk] ?? '';
            if ($v !== '') {
                $filters[$dbk] = (string) $v;
            }
        }
        if (isset($_GET['unmatched']) && $_GET['unmatched'] !== '') {
            $filters['unmatched'] = $_GET['unmatched'] === '1' || $_GET['unmatched'] === 'true';
        }
        $list = lc_lp_orders_list($filters);
        $items = array();
        foreach ($list['items'] as $row) {
            $items[] = lc_lp_order_to_api($row, false, false);
        }
        if (!empty($_GET['format']) && $_GET['format'] === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="lp_orders_' . date('Ymd_His') . '.csv"');
            echo "\xEF\xBB\xBF";
            echo lc_lp_csv_row(array('lpo_id', 'trlog_id', 'partner', 'merchant', 'order_code', 'product', 'sales', 'lp_commission', 'partner_expected', 'partner_confirmed', 'platform_margin', 'raw_status', 'lc_status', 'occurred_at', 'confirmed_at', 'cancelled_at')) . "\n";
            foreach ($list['items'] as $row) {
                echo lc_lp_csv_row(array(
                    $row['lpo_id'] ?? '',
                    $row['trlog_id'] ?? '',
                    ($row['partner_name'] ?? '') . '/' . ($row['partner_code'] ?? ''),
                    ($row['merchant_name'] ?? '') . '/' . ($row['merchant_code'] ?? ''),
                    $row['order_code'] ?? '',
                    $row['product_name'] ?? '',
                    $row['sales_amount'] ?? '',
                    $row['lp_commission'] ?? '',
                    $row['partner_expected'] ?? '',
                    $row['partner_confirmed'] ?? '',
                    $row['platform_margin'] ?? '',
                    $row['raw_status'] ?? '',
                    $row['lc_status'] ?? '',
                    $row['occurred_at'] ?? '',
                    $row['confirmed_at'] ?? '',
                    $row['cancelled_at'] ?? '',
                )) . "\n";
            }
            exit;
        }
        lc_api_success(array(
            'items'   => $items,
            'total'   => $list['total'],
            'dbReady' => lc_db_table_exists(lc_table('lp_orders')),
        ));
    }

    if ($view === 'order') {
        $lpo_id = isset($_GET['lpoId']) ? (int) $_GET['lpoId'] : 0;
        if ($lpo_id <= 0) {
            lc_api_error('lpoId가 필요합니다.', 'INVALID_ID', 400);
        }
        $table = lc_table('lp_orders');
        $row = lc_sql_fetch(" SELECT o.*, m.merchant_name, p.pt_name AS partner_name, p.pt_code AS partner_code
            FROM `{$table}` o
            LEFT JOIN `" . lc_table('lp_merchants') . "` m ON m.lpm_id = o.lpm_id
            LEFT JOIN `" . lc_table('partners') . "` p ON p.pt_id = o.pt_id
            WHERE o.lpo_id = {$lpo_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            lc_api_error('실적을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $click = null;
        $lpc_id = (int) ($row['lpc_id'] ?? 0);
        if ($lpc_id > 0 && lc_db_table_exists(lc_table('lp_clicks'))) {
            $click = lc_sql_fetch(" SELECT * FROM `" . lc_table('lp_clicks') . "` WHERE lpc_id = {$lpc_id} LIMIT 1 ", false);
        }
        lc_api_success(array(
            'item'   => lc_lp_order_to_api($row, false, true),
            'logs'   => lc_lp_order_status_logs($lpo_id),
            'click'  => is_array($click) ? array(
                'lpcId'     => (int) $click['lpc_id'],
                'uId'       => (string) ($click['u_id'] ?? ''),
                'device'    => (string) ($click['device'] ?? ''),
                'ip'        => (string) ($click['ip'] ?? ''),
                'clickedAt' => $click['clicked_at'] ?? null,
            ) : null,
        ));
    }

    if ($view === 'clicks') {
        $filters = array(
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        foreach (array('merchant', 'from', 'to') as $k) {
            if (!empty($_GET[$k])) {
                $filters[$k] = (string) $_GET[$k];
            }
        }
        if (!empty($_GET['ptId'])) {
            $filters['pt_id'] = (int) $_GET['ptId'];
        }
        $list = lc_lp_clicks_list($filters);
        lc_api_success(array(
            'items'   => $list['items'],
            'total'   => $list['total'],
            'summary' => $list['summary'],
            'dbReady' => lc_db_table_exists(lc_table('lp_clicks')),
        ));
    }

    if ($view === 'ledger' || $view === 'settlements') {
        $filters = array(
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        if (!empty($_GET['ptId'])) {
            $filters['pt_id'] = (int) $_GET['ptId'];
        }
        if (!empty($_GET['entryType'])) {
            $filters['entry_type'] = (string) $_GET['entryType'];
        }
        foreach (array('from', 'to') as $k) {
            if (!empty($_GET[$k])) {
                $filters[$k] = (string) $_GET[$k];
            }
        }
        $list = lc_lp_ledger_list($filters);
        lc_api_success(array(
            'items'   => $list['items'],
            'total'   => $list['total'],
            'dbReady' => lc_db_table_exists(lc_table('lp_ledger')),
        ));
    }

    lc_api_error('유효하지 않은 view입니다.', 'INVALID_VIEW', 400);
}

if ($method === 'POST') {
    lc_api_require_admin();
    if (!lc_lp_admin_api_csrf_ok()) {
        lc_api_error('CSRF 검증에 실패했습니다.', 'CSRF', 403);
    }
    lc_api_require_method('POST');

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'bulk_update_merchants') {
        $values = array();
        if (array_key_exists('visible', $body)) {
            $values['visible'] = !empty($body['visible']);
        }
        if (array_key_exists('isRecommended', $body) || array_key_exists('is_recommended', $body)) {
            $values['is_recommended'] = !empty($body['isRecommended'] ?? $body['is_recommended'] ?? false);
        }
        if (!$values) {
            lc_api_error('변경할 항목이 없습니다.', 'INVALID_BODY', 400);
        }

        $scope = isset($body['scope']) ? trim((string) $body['scope']) : '';
        if ($scope !== '') {
            $result = lc_lp_merchant_bulk_update_scope($scope, $values);
        } else {
            $ids = $body['lpmIds'] ?? $body['lpm_ids'] ?? array();
            if (!is_array($ids) || !$ids) {
                lc_api_error('lpmIds 또는 scope가 필요합니다.', 'INVALID_BODY', 400);
            }
            $result = lc_lp_merchant_bulk_update_admin(array_map('intval', $ids), $values);
        }

        $result['ok']
            ? lc_api_success(array(
                'message'   => $result['message'],
                'updated'   => (int) ($result['updated'] ?? 0),
                'merchants' => lc_lp_merchant_admin_stats(),
            ))
            : lc_api_error($result['message'], 'BULK_UPDATE_FAILED', 400, array(
                'updated'   => (int) ($result['updated'] ?? 0),
                'merchants' => lc_lp_merchant_admin_stats(),
            ));
    }

    if ($action === 'run_setup_check') {
        $connection = lc_lp_connection_test(!empty($body['testMode']));
        $snapshot = function_exists('lc_lp_setup_snapshot') ? lc_lp_setup_snapshot() : array();
        lc_api_success(array(
            'message'    => (string) ($connection['message'] ?? ''),
            'connection' => $connection,
            'setup'      => $snapshot,
        ));
    }

    if ($action === 'create_test_click') {
        $result = lc_lp_admin_create_test_click($body);
        $result['ok']
            ? lc_api_success(array(
                'message'  => $result['message'],
                'click'    => $result['click'],
                'promoUrl' => $result['promoUrl'],
                'e2e'      => lc_lp_admin_e2e_snapshot(),
            ))
            : lc_api_error($result['message'], 'TEST_CLICK_FAILED', 400);
    }

    if ($action === 'simulate_postback') {
        $security = function_exists('lc_lp_ui_security_settings') ? lc_lp_ui_security_settings() : array();
        if (empty($security['testMode']) && empty($body['force'])) {
            lc_api_error('테스트 POSTBACK은 테스트 모드에서만 가능합니다. 설정에서 테스트 모드를 켜거나 force=true를 사용하세요.', 'TEST_MODE_REQUIRED', 403);
        }
        $result = lc_lp_admin_simulate_postback($body);
        $payload = array(
            'message'  => $result['message'],
            'result'   => $result['result'],
            'lppId'    => (int) ($result['lppId'] ?? 0),
            'promoUrl' => (string) ($result['promoUrl'] ?? ''),
            'e2e'      => lc_lp_admin_e2e_snapshot(),
        );
        $result['ok'] ? lc_api_success($payload) : lc_api_error($result['message'], 'SIMULATE_FAILED', 400, $payload);
    }

    if ($action === 'save_config') {
        $values = array();
        foreach (array(
            'affiliateCode'        => 'affiliate_code',
            'affiliate_code'       => 'affiliate_code',
            'apiAuthKey'           => 'api_auth_key',
            'api_auth_key'         => 'api_auth_key',
            'postbackSecret'       => 'postback_secret',
            'postback_secret'      => 'postback_secret',
            'clearPostbackSecret'  => 'postback_secret_clear',
            'postbackSecretClear'  => 'postback_secret_clear',
            'apiEnabled'           => 'api_enabled',
            'api_enabled'          => 'api_enabled',
            'defaultPartnerRate'   => 'default_partner_rate',
            'default_partner_rate' => 'default_partner_rate',
            'networkName'          => 'network_name',
            'network_name'         => 'network_name',
        ) as $in => $out) {
            if (array_key_exists($in, $body)) {
                $values[$out] = $body[$in];
            }
        }
        $result = lc_lp_config_save($values);
        $result['ok']
            ? lc_api_success(array('message' => $result['message'], 'config' => lc_lp_config_to_api($result['config'])))
            : lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    if ($action === 'sync_merchants') {
        $result = lc_lp_sync_merchants(array(
            'scope'              => isset($body['scope']) && $body['scope'] === 'all' ? 'all' : 'apr',
            'detail'             => !isset($body['detail']) || !empty($body['detail']),
            'test_mode'          => !empty($body['testMode']),
            'deactivate_missing' => !isset($body['deactivateMissing']) || !empty($body['deactivateMissing']),
        ));
        $payload = array(
            'message'  => $result['message'],
            'sync'     => $result,
            'config'   => lc_lp_config_to_api(),
            'syncLogs' => lc_lp_sync_logs_recent(5, LC_LP_SYNC_MERCHANTS),
        );
        $result['ok'] ? lc_api_success($payload) : lc_api_error($result['message'], 'SYNC_FAILED', 400, $payload);
    }

    if ($action === 'update_merchant') {
        $lpm_id = (int) ($body['lpmId'] ?? $body['lpm_id'] ?? 0);
        $result = lc_lp_merchant_update_admin($lpm_id, $body);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }
        lc_api_success(array(
            'message' => $result['message'],
            'item'    => lc_lp_merchant_to_api($result['merchant'], false),
        ));
    }

    if ($action === 'reprocess_postback') {
        $lpp_id = (int) ($body['lppId'] ?? $body['lpp_id'] ?? 0);
        $result = lc_lp_postback_reprocess($lpp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'REPROCESS_FAILED', 400);
        }
        $table = lc_table('lp_postbacks');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lpp_id = {$lpp_id} LIMIT 1 ", false);
        lc_api_success(array(
            'message' => $result['message'],
            'result'  => $result['result'] ?? null,
            'item'    => is_array($row) ? lc_lp_postback_to_api($row, true) : null,
        ));
    }

    if ($action === 'sync_orders') {
        $options = array(
            'mode' => isset($body['mode']) ? (string) $body['mode'] : 'last7',
            'date' => isset($body['date']) ? (string) $body['date'] : date('Ymd'),
        );
        foreach (array('from', 'to', 'merchant_id', 'order_code', 'cancel_flag') as $k) {
            if (isset($body[$k]) && $body[$k] !== '') {
                $options[$k] = $body[$k];
            }
        }
        // camelCase aliases
        if (isset($body['merchantId'])) {
            $options['merchant_id'] = $body['merchantId'];
        }
        if (isset($body['orderCode'])) {
            $options['order_code'] = $body['orderCode'];
        }
        if (!empty($body['testMode'])) {
            $options['test_mode'] = true;
        }
        $result = lc_lp_sync_orders($options);
        $payload = array(
            'message'  => $result['message'],
            'sync'     => $result,
            'config'   => lc_lp_config_to_api(),
            'syncLogs' => lc_lp_sync_logs_recent(5, LC_LP_SYNC_ORDERS),
        );
        $result['ok'] ? lc_api_success($payload) : lc_api_error($result['message'], 'SYNC_FAILED', 400, $payload);
    }

    if ($action === 'sync_order_one') {
        $lpo_id = (int) ($body['lpoId'] ?? $body['lpo_id'] ?? 0);
        $result = lc_lp_sync_order_one($lpo_id);
        $result['ok']
            ? lc_api_success(array('message' => $result['message'], 'result' => $result['result'] ?? null))
            : lc_api_error($result['message'], 'SYNC_FAILED', 400);
    }

    if ($action === 'test_connection') {
        $result = lc_lp_connection_test(!empty($body['testMode']));
        $result['ok']
            ? lc_api_success(array('message' => $result['message'], 'resultCode' => $result['resultCode']))
            : lc_api_error($result['message'], 'CONNECTION_FAILED', 400, array('resultCode' => $result['resultCode']));
    }

    if ($action === 'link_partner') {
        $lpo_id = (int) ($body['lpoId'] ?? $body['lpo_id'] ?? 0);
        $pt_id = (int) ($body['ptId'] ?? $body['pt_id'] ?? 0);
        global $member;
        $mb = is_array($member ?? null) ? (string) ($member['mb_id'] ?? '') : '';
        $result = lc_lp_order_link_partner($lpo_id, $pt_id, $mb);
        $result['ok']
            ? lc_api_success(array('message' => $result['message']))
            : lc_api_error($result['message'], 'LINK_FAILED', 400);
    }

    if ($action === 'save_postback_security') {
        if (function_exists('lc_settings_save')) {
            $mirror = array();
            if (array_key_exists('lpPostbackIpEnabled', $body) || array_key_exists('postbackIpEnabled', $body)) {
                $on = !empty($body['lpPostbackIpEnabled'] ?? $body['postbackIpEnabled'] ?? false);
                $mirror['lpPostbackIpEnabled'] = $on ? '1' : '0';
            }
            if (array_key_exists('lpPostbackIpAllowlist', $body) || array_key_exists('postbackIpAllowlist', $body)) {
                $mirror['lpPostbackIpAllowlist'] = trim((string) ($body['lpPostbackIpAllowlist'] ?? $body['postbackIpAllowlist'] ?? ''));
            }
            if (array_key_exists('testMode', $body) || array_key_exists('lpTestMode', $body)) {
                $mirror['lpTestMode'] = !empty($body['testMode'] ?? $body['lpTestMode'] ?? false) ? '1' : '0';
            }
            if ($mirror) {
                lc_settings_save($mirror);
            }
        }
        if (array_key_exists('postbackSecret', $body) || array_key_exists('postback_secret', $body) || !empty($body['clearPostbackSecret'] ?? $body['postbackSecretClear'] ?? false)) {
            lc_lp_config_save(array(
                'postback_secret'       => $body['postbackSecret'] ?? $body['postback_secret'] ?? '',
                'postback_secret_clear' => !empty($body['clearPostbackSecret'] ?? $body['postbackSecretClear'] ?? false),
            ));
        }
        lc_api_success(array(
            'message' => 'POSTBACK 보안 설정이 저장되었습니다.',
            'config'  => lc_lp_config_to_api(),
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
