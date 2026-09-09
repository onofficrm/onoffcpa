<?php
require_once dirname(__DIR__) . '/_common.php';
header('Content-Type: application/json; charset=utf-8');
$expected = 'callbackfill-8a1c3e5f7b9204d6';
$given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
if ($given === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode(array('ok'=>false,'error'=>'FORBIDDEN'));
    exit;
}
$cs = lc_table('call_settings');
$cp = lc_table('campaigns');
$clog = lc_table('call_logs');
// campaigns with matched call logs
$rows = array();
$res = lc_sql_query(" SELECT DISTINCT l.cp_id, l.mt_id, c.cp_name
  FROM `{$clog}` l
  LEFT JOIN `{$cp}` c ON c.cp_id = l.cp_id
  WHERE l.cp_id > 0 AND l.pt_id > 0 ", false);
$updated = array();
while ($res && ($row = sql_fetch_array($res))) {
    $cp_id = (int)$row['cp_id'];
    if ($cp_id <= 0) continue;
    $result = lc_call_settings_save($cp_id, array('enabled' => true), 'merchant');
    $updated[] = array(
        'cpId' => $cp_id,
        'name' => (string)($row['cp_name'] ?? ''),
        'ok' => !empty($result['ok']),
        'message' => (string)($result['message'] ?? ''),
    );
}
echo json_encode(array('ok'=>true,'updated'=>$updated), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
