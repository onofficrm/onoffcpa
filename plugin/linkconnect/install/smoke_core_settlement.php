<?php
/**
 * Smoke: Core CPS settlement submit (pt_id=1) for a small exact commission amount.
 * Web: ?action=run&amount=5000
 */
require_once dirname(__DIR__) . '/_common.php';

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'run') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "usage: ?action=run&amount=5000&pay=0\n";
    exit;
}

$pt_id = isset($_REQUEST['ptId']) ? (int) $_REQUEST['ptId'] : 1;
$amount = isset($_REQUEST['amount']) ? (int) $_REQUEST['amount'] : 5000;
$do_pay = isset($_REQUEST['pay']) && (string) $_REQUEST['pay'] === '1';

$partner = lc_get_partner_by_id($pt_id);
$bank = array(
    'bankName'    => is_array($partner) ? (string) ($partner['pt_bank_name'] ?? '테스트은행') : '테스트은행',
    'bankAccount' => is_array($partner) ? (string) ($partner['pt_bank_account'] ?? '000-0000-0000') : '000-0000-0000',
    'bankHolder'  => is_array($partner) ? (string) ($partner['pt_bank_holder'] ?? '테스트') : '테스트',
);
if ($bank['bankName'] === '') {
    $bank['bankName'] = '테스트은행';
}
if ($bank['bankAccount'] === '') {
    $bank['bankAccount'] = '000-0000-0000';
}
if ($bank['bankHolder'] === '') {
    $bank['bankHolder'] = '테스트';
}

$before = lc_onoff_core_partner_dashboard($pt_id);
$prep = lc_onoff_core_settlement_prepare_request($pt_id, $amount);
$submit = null;
$pay = null;
if (!empty($prep['ok']) && lc_onoff_core_settlement_payout_enabled()) {
    $submit = lc_onoff_core_settlement_submit_request(
        $pt_id,
        $amount,
        $bank,
        'E2E smoke core settlement',
        array()
    );
    if ($do_pay && !empty($submit['ok']) && !empty($submit['settlement']['id'])) {
        $st_id = (int) $submit['settlement']['id'];
        $approve = lc_settlement_admin_update($st_id, 'approve', array('approvedAmount' => $amount));
        $pay = lc_settlement_admin_update($st_id, 'pay', array());
        $pay['approve'] = $approve;
    }
}
$after = lc_onoff_core_partner_dashboard($pt_id);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'ok' => !empty($submit['ok']),
    'payoutEnabled' => lc_onoff_core_settlement_payout_enabled(),
    'before' => $before,
    'prepare' => $prep,
    'submit' => $submit,
    'pay' => $pay,
    'after' => $after,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
