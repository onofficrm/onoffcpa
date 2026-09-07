<?php
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/MemoryStore.php';

if (!class_exists('OnoffCore_PartnerEarnings', false)) {
    final class OnoffCore_PartnerEarnings
    {
        /**
         * Read contract for ONOFFCPA (no UI changes in C4).
         *
         * @param string $referrer_mb_id
         * @param OnoffCore_MemoryReferralStore|null $store
         * @return array
         */
        public static function summary($referrer_mb_id, $store = null)
        {
            $referrer_mb_id = trim((string) $referrer_mb_id);
            $referred = 0;
            $commissions = array();
            if ($store instanceof OnoffCore_MemoryReferralStore) {
                foreach ($store->memberReferrals as $mr) {
                    if ((string) $mr['referrer_mb_id'] === $referrer_mb_id) {
                        $referred++;
                    }
                }
                $commissions = $store->listCommissionsByReferrer($referrer_mb_id);
            } elseif (function_exists('sql_fetch')) {
                $mrTbl = OnoffCore_ReferralSchema::tableMemberReferral();
                $mb = sql_escape_string($referrer_mb_id);
                $row = sql_fetch("SELECT COUNT(*) AS c FROM `{$mrTbl}` WHERE referrer_mb_id = '{$mb}'", false);
                $referred = $row ? (int) $row['c'] : 0;
                $cTbl = OnoffCore_ReferralSchema::tableCommission();
                $res = sql_query("SELECT * FROM `{$cTbl}` WHERE referrer_mb_id = '{$mb}'", false);
                if ($res) {
                    while ($r = sql_fetch_array($res)) {
                        $commissions[] = $r;
                    }
                }
            }

            $successfulPaymentTotal = 0;
            $pending = 0;
            $available = 0;
            $paid = 0;
            $history = array();
            foreach ($commissions as $c) {
                $successfulPaymentTotal += (int) $c['gross_amount'];
                $amt = (int) $c['commission_amount'];
                $st = (string) $c['status'];
                if ($st === 'PENDING' || $st === 'APPROVED') {
                    $pending += $amt;
                } elseif ($st === 'AVAILABLE') {
                    $available += $amt;
                } elseif ($st === 'PAID') {
                    $paid += $amt;
                }
                $history[] = array(
                    'commission_id' => $c['commission_id'],
                    'payment_id' => $c['payment_id'],
                    'customer_mb_id' => $c['customer_mb_id'],
                    'gross_amount' => (int) $c['gross_amount'],
                    'commission_amount' => $amt,
                    'status' => $st,
                    'created_at' => $c['created_at'],
                );
            }

            return array(
                'referrer_mb_id' => $referrer_mb_id,
                'referred_member_count' => $referred,
                'successful_payment_total' => $successfulPaymentTotal,
                'pending_earnings' => $pending,
                'available_earnings' => $available,
                'paid_earnings' => $paid,
                'commission_history' => $history,
            );
        }
    }
}
