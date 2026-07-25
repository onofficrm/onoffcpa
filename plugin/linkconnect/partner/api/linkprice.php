<?php
/**
 * 파트너 — 링크프라이스 CPS (광고주 / 클릭 / 실적 / 수익)
 * 모든 조회는 로그인 파트너 pt_id 로 강제 스코프
 */
require_once __DIR__ . '/_common.php';

if (!function_exists('lc_lp_partner_api_csrf_ok')) {
    function lc_lp_partner_api_csrf_ok()
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
        // Origin·Referer 모두 없으면 쿠키 CSRF에 취약 → 거부
        return false;
    }
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $view = isset($_GET['view']) ? (string) $_GET['view'] : 'merchants';

    if ($view === 'merchants' || $view === '') {
        $filters = array();
        if (!empty($_GET['q'])) {
            $filters['q'] = (string) $_GET['q'];
        }
        if (!empty($_GET['code'])) {
            $filters['code'] = (string) $_GET['code'];
        }
        if (isset($_GET['deeplink']) && ($_GET['deeplink'] === '1' || $_GET['deeplink'] === 'true')) {
            $filters['deeplink'] = true;
        }
        $list = lc_lp_partner_list_merchants($pt_id, $filters);
        lc_api_success(array(
            'items'        => $list['items'],
            'total'        => $list['total'],
            'partnerToken' => lc_lp_partner_public_token($pt_id),
            'stats'        => lc_lp_partner_dashboard_stats($pt_id),
            'dbReady'      => lc_db_table_exists(lc_table('lp_merchants')),
        ));
    }

    if ($view === 'dashboard' || $view === 'stats') {
        lc_api_success(array(
            'stats'   => lc_lp_partner_dashboard_stats($pt_id),
            'dbReady' => lc_db_table_exists(lc_table('lp_orders')),
        ));
    }

    if ($view === 'promo_url') {
        $code = isset($_GET['merchantCode']) ? (string) $_GET['merchantCode'] : '';
        $merchant = lc_lp_repo_get_merchant_by_code($code);
        if (!is_array($merchant) || !lc_lp_merchant_partner_visible($merchant)) {
            lc_api_error('홍보 가능한 광고주가 아닙니다.', 'NOT_AVAILABLE', 404);
        }
        lc_api_success(array(
            'promoUrl'     => lc_lp_public_promo_url((string) $merchant['merchant_code'], $pt_id),
            'merchantCode' => (string) $merchant['merchant_code'],
        ));
    }

    if ($view === 'merchant') {
        $code = isset($_GET['merchantCode']) ? trim((string) $_GET['merchantCode']) : '';
        if ($code === '' && !empty($_GET['code'])) {
            $code = trim((string) $_GET['code']);
        }
        $lpm_id = isset($_GET['lpmId']) ? (int) $_GET['lpmId'] : 0;
        $merchant = null;
        if ($lpm_id > 0 && function_exists('lc_lp_repo_get_merchant')) {
            $merchant = lc_lp_repo_get_merchant($lpm_id);
        }
        if (!is_array($merchant) && $code !== '') {
            $merchant = lc_lp_repo_get_merchant_by_code($code);
        }
        if (!is_array($merchant) || !lc_lp_merchant_public_listable($merchant)) {
            lc_api_error('홍보 가능한 광고주가 아닙니다.', 'NOT_AVAILABLE', 404);
        }
        $stats = lc_lp_partner_merchant_stats($pt_id);
        $id = (int) ($merchant['lpm_id'] ?? 0);
        lc_api_success(array(
            'item'         => lc_lp_merchant_to_partner_api($merchant, $pt_id, $stats[$id] ?? array()),
            'partnerToken' => lc_lp_partner_public_token($pt_id),
            'dbReady'      => true,
        ));
    }

    if ($view === 'clicks') {
        $filters = array(
            'pt_id'  => $pt_id,
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        foreach (array('merchant', 'from', 'to') as $k) {
            if (!empty($_GET[$k])) {
                $filters[$k] = (string) $_GET[$k];
            }
        }
        $list = lc_lp_clicks_list($filters);
        lc_api_success(array(
            'items'   => $list['items'],
            'total'   => $list['total'],
            'summary' => $list['summary'],
            'stats'   => lc_lp_partner_dashboard_stats($pt_id),
            'dbReady' => true,
        ));
    }

    if ($view === 'orders') {
        $filters = array(
            'pt_id'  => $pt_id,
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        foreach (array('occurredFrom' => 'occurred_from', 'occurredTo' => 'occurred_to', 'merchant' => 'merchant', 'order' => 'order', 'product' => 'product', 'status' => 'status') as $gk => $dbk) {
            if (!empty($_GET[$gk])) {
                $filters[$dbk] = (string) $_GET[$gk];
            }
        }
        // URL ID 변조 차단: 요청 ptId 무시, 세션 파트너만
        $list = lc_lp_orders_list($filters);
        $items = array();
        foreach ($list['items'] as $row) {
            if ((int) ($row['pt_id'] ?? 0) !== $pt_id) {
                continue;
            }
            $items[] = lc_lp_order_to_api($row, true, false);
        }
        lc_api_success(array(
            'items'   => $items,
            'total'   => $list['total'],
            'stats'   => lc_lp_partner_dashboard_stats($pt_id),
            'dbReady' => true,
        ));
    }

    if ($view === 'order') {
        $lpo_id = isset($_GET['lpoId']) ? (int) $_GET['lpoId'] : 0;
        $table = lc_table('lp_orders');
        $row = lc_sql_fetch(" SELECT o.*, m.merchant_name
            FROM `{$table}` o
            LEFT JOIN `" . lc_table('lp_merchants') . "` m ON m.lpm_id = o.lpm_id
            WHERE o.lpo_id = {$lpo_id} AND o.pt_id = {$pt_id} LIMIT 1 ", false);
        if (!is_array($row)) {
            lc_api_error('실적을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item' => lc_lp_order_to_api($row, true, false),
        ));
    }

    if ($view === 'earnings' || $view === 'ledger') {
        $filters = array(
            'pt_id'  => $pt_id,
            'limit'  => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        );
        foreach (array('from', 'to', 'entryType') as $k) {
            if ($k === 'entryType' && !empty($_GET['entryType'])) {
                $filters['entry_type'] = (string) $_GET['entryType'];
            } elseif ($k !== 'entryType' && !empty($_GET[$k])) {
                $filters[$k] = (string) $_GET[$k];
            }
        }
        $list = lc_lp_ledger_list($filters);
        lc_api_success(array(
            'items'   => $list['items'],
            'total'   => $list['total'],
            'stats'   => lc_lp_partner_dashboard_stats($pt_id),
            'dbReady' => lc_db_table_exists(lc_table('lp_ledger')),
        ));
    }

    lc_api_error('유효하지 않은 view입니다.', 'INVALID_VIEW', 400);
}

if ($method === 'POST') {
    $partner = lc_api_require_active_partner();
    if (!lc_lp_partner_api_csrf_ok()) {
        lc_api_error('CSRF 검증에 실패했습니다.', 'CSRF', 403);
    }
    lc_api_require_method('POST');
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'deeplink';

    if ($action === 'deeplink' || $action === 'build_deeplink') {
        $code = (string) ($body['merchantCode'] ?? $body['merchant_code'] ?? '');
        $product = (string) ($body['productUrl'] ?? $body['product_url'] ?? $body['url'] ?? '');
        $result = lc_lp_partner_build_deeplink($pt_id, $code, $product);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'DEEPLINK_INVALID', 400);
        }
        lc_api_success(array(
            'message'  => $result['message'],
            'promoUrl' => $result['promoUrl'],
        ));
    }

    if ($action === 'shortlink' || $action === 'short_link' || $action === 'create_shortlink') {
        $code = (string) ($body['merchantCode'] ?? $body['merchant_code'] ?? '');
        $product = (string) ($body['productUrl'] ?? $body['product_url'] ?? $body['url'] ?? '');
        $result = lc_lp_partner_create_shortlink($pt_id, $code, $product);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SHORTLINK_INVALID', 400);
        }
        lc_api_success(array(
            'message'   => $result['message'],
            'shortUrl'  => $result['shortUrl'],
            'promoUrl'  => $result['promoUrl'],
            'shortCode' => $result['shortCode'],
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
