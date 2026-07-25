<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_settlement_risk_check')) {
    function lc_settlement_risk_check($st_id)
    {
        $st_id = (int) $st_id;
        $settlement = function_exists('lc_settlement_get_by_id') ? lc_settlement_get_by_id($st_id) : null;
        if (!is_array($settlement)) {
            return array('ok' => false, 'message' => '정산 정보를 찾을 수 없습니다.', 'risks' => array());
        }

        $pt_id = (int) ($settlement['pt_id'] ?? 0);
        $partner = $pt_id > 0 && function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($pt_id) : null;
        $risks = array();
        $score = 0;

        if (!is_array($partner)) {
            $risks[] = array('level' => 'high', 'code' => 'no_partner', 'message' => '파트너 정보 없음');
            $score += 50;
        } else {
            if (trim((string) ($partner['pt_bank_account'] ?? '')) === '') {
                $risks[] = array('level' => 'high', 'code' => 'no_bank', 'message' => '정산 계좌 미등록');
                $score += 40;
            }
            $abuse = (int) ($partner['pt_abuse_score'] ?? 0);
            if ($abuse >= 50) {
                $risks[] = array('level' => 'high', 'code' => 'abuse_score', 'message' => '어뷰징 점수 높음 (' . $abuse . '점)');
                $score += 30;
            } elseif ($abuse >= 30) {
                $risks[] = array('level' => 'medium', 'code' => 'abuse_score', 'message' => '어뷰징 점수 주의 (' . $abuse . '점)');
                $score += 15;
            }
        }

        if ($pt_id > 0 && lc_db_installed()) {
            $cv = lc_table('conversions');
            $pending = lc_sql_fetch("
                SELECT COUNT(*) AS cnt FROM `{$cv}`
                WHERE pt_id = {$pt_id}
                  AND cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "'
            ", false);
            $pending_cnt = (int) ($pending['cnt'] ?? 0);
            if ($pending_cnt > 0) {
                $risks[] = array('level' => 'medium', 'code' => 'pending_db', 'message' => '미검수 DB ' . $pending_cnt . '건');
                $score += 20;
            }
        }

        $level = 'low';
        if ($score >= 50) {
            $level = 'high';
        } elseif ($score >= 25) {
            $level = 'medium';
        }

        return array(
            'ok'      => true,
            'score'   => min(100, $score),
            'level'   => $level,
            'risks'   => $risks,
            'blocked' => $score >= 70,
        );
    }
}

if (!function_exists('lc_settlement_risk_check_for_api')) {
    function lc_settlement_risk_check_for_api($st_id)
    {
        $result = lc_settlement_risk_check($st_id);
        return array(
            'score'   => (int) ($result['score'] ?? 0),
            'level'   => (string) ($result['level'] ?? 'low'),
            'risks'   => isset($result['risks']) && is_array($result['risks']) ? $result['risks'] : array(),
            'blocked' => !empty($result['blocked']),
        );
    }
}
