<?php
require_once __DIR__ . '/_common.php';

lc_api_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    lc_api_client_ensure_default();

    $al_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($al_id > 0) {
        $table = lc_table('api_logs');
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE al_id = '{$al_id}' LIMIT 1 ");
        if (!$row) {
            lc_api_error('로그를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item' => lc_api_log_to_api($row, true),
        ));
    }

    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $client = isset($_GET['client']) ? trim((string) $_GET['client']) : '';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $errors_only = isset($_GET['errors']) && $_GET['errors'] === '1';

    $filters = array();
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if ($client !== '') {
        $filters['client'] = $client;
    }
    if ($q !== '') {
        $filters['q'] = $q;
    }
    if ($errors_only) {
        $filters['errors_only'] = true;
    }

    $clients = array_map('lc_api_client_to_api', lc_api_client_list());
    $merchant_clients = array();
    $landing_clients = array();
    foreach ($clients as $c) {
        if (($c['type'] ?? '') === 'merchant' || (int) ($c['mtId'] ?? 0) > 0) {
            $merchant_clients[] = $c;
        } else {
            $landing_clients[] = $c;
        }
    }

    $dbshare = null;
    foreach (lc_api_client_list() as $row) {
        if (stripos((string) $row['ac_name'], '디비쉐어') !== false || stripos((string) $row['ac_type'], 'dbshare') !== false) {
            $dbshare = lc_api_client_to_api($row);
            break;
        }
    }

    lc_api_success(array(
        'summary'          => lc_api_log_summary(),
        'clients'          => $clients,
        'landingClients'   => $landing_clients,
        'merchantClients'  => $merchant_clients,
        'dbshare'          => $dbshare,
        'items'            => array_map(function ($row) {
            return lc_api_log_to_api($row, false);
        }, lc_api_log_list($filters, 50)),
        'dbReady'          => lc_db_installed(),
        'merchantApiPath'  => '/plugin/linkconnect/api/v1/merchant/conversions.php',
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lc_api_client_ensure_default();
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'create_client') {
        $result = lc_api_client_create($body);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'CLIENT_CREATE_FAILED', 400);
        }
        $client_row = is_array($result['client'] ?? null) ? $result['client'] : null;
        $client_api = $client_row ? lc_api_client_to_api($client_row) : null;

        $all = array_map('lc_api_client_to_api', lc_api_client_list());
        $merchant_clients = array();
        $landing_clients = array();
        foreach ($all as $c) {
            if (($c['type'] ?? '') === 'merchant' || (int) ($c['mtId'] ?? 0) > 0) {
                $merchant_clients[] = $c;
            } else {
                $landing_clients[] = $c;
            }
        }

        lc_api_success(array(
            'message'         => $result['message'],
            'client'          => $client_api,
            'clients'         => $all,
            'landingClients'  => $landing_clients,
            'merchantClients' => $merchant_clients,
        ));
    }

    $ac_id = isset($body['acId']) ? (int) $body['acId'] : 0;
    if ($ac_id <= 0) {
        lc_api_error('클라이언트 ID가 필요합니다.', 'INVALID_ID', 400);
    }

    if ($action === 'regenerate_key') {
        $result = lc_api_client_regenerate_key($ac_id, 'api_key');
    } elseif ($action === 'regenerate_secret') {
        $result = lc_api_client_regenerate_key($ac_id, 'secret');
    } elseif ($action === 'deactivate') {
        $result = lc_api_client_update_status($ac_id, 'inactive');
    } elseif ($action === 'activate') {
        $result = lc_api_client_update_status($ac_id, 'active');
    } elseif ($action === 'update_ips') {
        $result = lc_api_client_update_ips($ac_id, (string) ($body['allowedIps'] ?? ''));
    } else {
        lc_api_error('유효하지 않은 액션입니다.', 'INVALID_ACTION', 400);
    }

    if (!$result['ok']) {
        lc_api_error($result['message'], 'CLIENT_UPDATE_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'client'  => lc_api_client_to_api($result['client']),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
