<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    $view = isset($_GET['view']) ? trim((string) $_GET['view']) : 'summary';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';

    if ($view === 'balances') {
        lc_api_success(array(
            'items'   => lc_wallet_admin_merchant_balances($q),
            'summary' => lc_wallet_admin_summary(),
            'dbReady' => lc_db_installed(),
        ));
    }

    if ($view === 'history') {
        lc_api_success(array(
            'items'   => lc_wallet_admin_transactions(array('q' => $q, 'type' => $type)),
            'summary' => lc_wallet_admin_summary(),
            'dbReady' => lc_db_installed(),
        ));
    }

    $items = array();
    if (lc_db_installed() && function_exists('lc_wallet_list_pending_charges')) {
        foreach (lc_wallet_list_pending_charges(50) as $row) {
            $items[] = lc_wallet_pending_to_api($row);
        }
    }

    lc_api_success(array(
        'summary' => lc_wallet_admin_summary(),
        'items'   => $items,
        'pending' => count($items),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    if (!lc_db_installed()) {
        lc_api_error('DB가 설치되지 않았습니다.', 'DB_NOT_READY', 400);
    }

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    $wt_id = isset($body['wtId']) ? (int) $body['wtId'] : 0;
    $memo = isset($body['memo']) ? (string) $body['memo'] : '';

    if ($action === 'approve') {
        $result = lc_wallet_approve_transaction($wt_id);
    } elseif ($action === 'reject') {
        $result = lc_wallet_reject_transaction($wt_id, $memo);
    } elseif ($action === 'adjust') {
        $mt_id = isset($body['mtId']) ? (int) $body['mtId'] : 0;
        $type = isset($body['type']) ? (string) $body['type'] : '';
        $amount = isset($body['amount']) ? (int) $body['amount'] : 0;
        if ($mt_id <= 0) {
            lc_api_error('광고주 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }
        $result = lc_wallet_admin_adjust($mt_id, $type, $amount, $memo);
    } else {
        lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
    }

    if (!$result['ok']) {
        lc_api_error($result['message'], 'UPDATE_FAILED', 400);
    }

    if (function_exists('lc_admin_log_write') && $action === 'adjust') {
        lc_admin_log_write('wallet_adjust', 'merchant', (int) ($body['mtId'] ?? 0), $result['message'], array(
            'type'   => (string) ($body['type'] ?? ''),
            'amount' => (int) ($body['amount'] ?? 0),
            'memo'   => $memo,
        ));
    }

    lc_api_success(array(
        'message' => $result['message'],
        'summary' => lc_wallet_admin_summary(),
    ));
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
