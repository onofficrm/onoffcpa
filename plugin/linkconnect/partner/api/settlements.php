<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    $summary = lc_settlement_partner_summary($pt_id);
    $core = function_exists('lc_onoff_core_partner_dashboard')
        ? lc_onoff_core_partner_dashboard($pt_id)
        : null;

    if (is_array($core) && function_exists('lc_onoff_core_settlement_payout_enabled')
        && lc_onoff_core_settlement_payout_enabled()
    ) {
        $core_settleable = (int) ($core['pendingEarnings'] ?? 0) + (int) ($core['availableEarnings'] ?? 0);
        $summary['coreSettleableAmount'] = $core_settleable;
        $summary['cpaAvailableAmount'] = (int) ($summary['availableAmount'] ?? 0);
        // Form max = CPA wallet + Core settleable (separate pots; submit picks source)
        $summary['combinedSettleableAmount'] = (int) $summary['cpaAvailableAmount'] + $core_settleable;
    }

    lc_api_success(array(
        'summary'  => $summary,
        'items'    => array_map('lc_settlement_to_partner_api', lc_settlement_list_for_partner($pt_id)),
        'dbReady'  => lc_db_installed(),
        'core'     => $core,
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $amount = isset($body['amount']) ? (int) $body['amount'] : 0;
    $memo = isset($body['memo']) ? trim((string) $body['memo']) : '';
    $bank = array(
        'bankName'    => isset($body['bankName']) ? (string) $body['bankName'] : '',
        'bankAccount' => isset($body['bankAccount']) ? (string) $body['bankAccount'] : '',
        'bankHolder'  => isset($body['bankHolder']) ? (string) $body['bankHolder'] : '',
    );
    $commission_ids = isset($body['commissionIds']) && is_array($body['commissionIds'])
        ? $body['commissionIds']
        : array();
    $source = strtolower(trim((string) ($body['source'] ?? '')));

    $core_ready = function_exists('lc_onoff_core_integration_enabled')
        && lc_onoff_core_integration_enabled()
        && function_exists('lc_onoff_core_settlement_prepare_request');
    $payout_on = $core_ready && function_exists('lc_onoff_core_settlement_payout_enabled')
        && lc_onoff_core_settlement_payout_enabled();

    $use_core = false;
    if ($core_ready) {
        if ($source === 'core') {
            $use_core = true;
        } elseif ($payout_on && $source !== 'cpa') {
            $summary = lc_settlement_partner_summary($pt_id);
            $cpa_avail = (int) ($summary['availableAmount'] ?? 0);
            $dash = lc_onoff_core_partner_dashboard($pt_id);
            $core_settle = is_array($dash)
                ? ((int) ($dash['pendingEarnings'] ?? 0) + (int) ($dash['availableEarnings'] ?? 0))
                : 0;
            // Prefer Core when amount does not fit CPA wallet but fits Core.
            if ($amount > $cpa_avail && $amount <= $core_settle) {
                $use_core = true;
            }
        }
    }

    if ($use_core) {
        $draft = lc_onoff_core_settlement_prepare_request($pt_id, $amount, $commission_ids);
        if (empty($draft['ok'])) {
            lc_api_error($draft['message'], 'CORE_SETTLEMENT_BLOCKED', 400);
        }

        if (!$payout_on) {
            lc_api_success(array(
                'message' => $draft['message'],
                'draft'   => $draft['draft'],
                'summary' => lc_settlement_partner_summary($pt_id),
                'core'    => lc_onoff_core_partner_dashboard($pt_id),
            ));
        }

        $result = lc_onoff_core_settlement_submit_request($pt_id, $amount, $bank, $memo, $commission_ids);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'CORE_SETTLEMENT_FAILED', 400);
        }

        lc_api_success(array(
            'message'    => $result['message'],
            'settlement' => $result['settlement'],
            'summary'    => lc_settlement_partner_summary($pt_id),
            'core'       => lc_onoff_core_partner_dashboard($pt_id),
        ));
    }

    $result = lc_settlement_request($pt_id, $amount, $bank, $memo);

    if (!$result['ok']) {
        lc_api_error($result['message'], 'SETTLEMENT_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'settlement' => $result['settlement'],
        'summary'    => lc_settlement_partner_summary($pt_id),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
