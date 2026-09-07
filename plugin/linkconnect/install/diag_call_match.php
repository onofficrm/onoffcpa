<?php
/**
 * 진단: 배정된 가상번호 / 착신번호→캠페인 매칭 가능 여부
 * /plugin/linkconnect/install/diag_call_match.php?token=callrematch-9f3c2a1b7e84d056
 */
require_once dirname(__DIR__) . '/_common.php';
header('Content-Type: application/json; charset=utf-8');
$expected = 'callrematch-9f3c2a1b7e84d056';
if (!hash_equals($expected, (string) ($_GET['token'] ?? '')) && !(function_exists('lc_is_super_admin') && lc_is_super_admin())) {
    http_response_code(403);
    echo json_encode(array('ok' => false));
    exit;
}

$out = array('ok' => true, 'assigned' => array(), 'forwards' => array(), 'calleeHits' => array());

if (lc_db_table_exists(lc_table('call_requests'))) {
    $res = lc_sql_query(" SELECT car_id, pt_id, cp_id, mt_id, car_virtual_number, cn_id FROM `" . lc_table('call_requests') . "`
        WHERE car_status = 'assigned' ORDER BY car_id DESC LIMIT 100 ", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            $out['assigned'][] = array(
                'carId' => (int) $row['car_id'],
                'ptId' => (int) $row['pt_id'],
                'cpId' => (int) $row['cp_id'],
                'mtId' => (int) $row['mt_id'],
                'virtualNumber' => lc_call_number_normalize((string) $row['car_virtual_number']),
                'cnId' => (int) $row['cn_id'],
            );
        }
    }
}

if (lc_db_table_exists(lc_table('call_settings'))) {
    $res = lc_sql_query(" SELECT cs_id, cp_id, mt_id, cs_forward1, cs_forward2, cs_enabled, cs_admin_enabled FROM `" . lc_table('call_settings') . "`
        WHERE cs_forward1 <> '' OR cs_forward2 <> '' LIMIT 200 ", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            $out['forwards'][] = array(
                'cpId' => (int) $row['cp_id'],
                'mtId' => (int) $row['mt_id'],
                'forward1' => lc_call_number_normalize((string) $row['cs_forward1']),
                'forward2' => lc_call_number_normalize((string) $row['cs_forward2']),
                'enabled' => (int) $row['cs_enabled'],
                'adminEnabled' => (int) $row['cs_admin_enabled'],
            );
        }
    }
}

if (lc_db_table_exists(lc_table('call_logs'))) {
    $res = lc_sql_query(" SELECT clog_callee, COUNT(*) AS cnt FROM `" . lc_table('call_logs') . "`
        WHERE pt_id = '0' AND clog_callee <> ''
        GROUP BY clog_callee ORDER BY cnt DESC LIMIT 30 ", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            $callee = lc_call_number_normalize((string) $row['clog_callee']);
            $hit = null;
            foreach ($out['forwards'] as $f) {
                if ($callee !== '' && ($callee === $f['forward1'] || $callee === $f['forward2'])) {
                    $hit = $f;
                    break;
                }
            }
            $out['calleeHits'][] = array(
                'callee' => $callee,
                'logs' => (int) $row['cnt'],
                'matchedForward' => $hit,
            );
        }
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
