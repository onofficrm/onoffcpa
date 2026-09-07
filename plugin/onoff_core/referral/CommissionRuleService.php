<?php
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/MemoryStore.php';

if (!class_exists('OnoffCore_CommissionRuleService', false)) {
    final class OnoffCore_CommissionRuleService
    {
        /** @var OnoffCore_MemoryReferralStore|null */
        private $store;

        public function __construct($store = null)
        {
            $this->store = $store;
        }

        /**
         * Priority: INDIVIDUAL > CAMPAIGN > GLOBAL.
         *
         * @return array{ok:bool,rule?:array,error?:string}
         */
        public function resolveRule($referrer_mb_id, $campaign_id = '', $at = null)
        {
            $at = $at ? strtotime($at) : time();
            $rules = $this->loadActiveRules($at);
            $referrer_mb_id = trim((string) $referrer_mb_id);
            $campaign_id = trim((string) $campaign_id);

            $best = null;
            $bestPri = -1;
            foreach ($rules as $rule) {
                if (strtoupper((string) $rule['status']) !== 'ACTIVE') {
                    continue;
                }
                $scope = strtoupper((string) $rule['scope']);
                $match = false;
                $pri = (int) $rule['priority'];
                if ($scope === 'INDIVIDUAL' && (string) $rule['referrer_mb_id'] === $referrer_mb_id) {
                    $match = true;
                    $pri += 1000;
                } elseif ($scope === 'CAMPAIGN' && $campaign_id !== '' && (string) $rule['campaign_id'] === $campaign_id) {
                    $match = true;
                    $pri += 500;
                } elseif ($scope === 'GLOBAL' && (string) $rule['referrer_mb_id'] === '' && (string) $rule['campaign_id'] === '') {
                    $match = true;
                    $pri += 100;
                }
                if ($match && $pri > $bestPri) {
                    $bestPri = $pri;
                    $best = $rule;
                }
            }
            if (!$best) {
                return array('ok' => false, 'error' => 'RULE_NOT_FOUND');
            }
            return array('ok' => true, 'rule' => $best);
        }

        public function upsertRule(array $rule)
        {
            $now = date('Y-m-d H:i:s');
            $row = array_merge(array(
                'rule_id' => isset($rule['rule_id']) ? $rule['rule_id'] : 'rule_' . bin2hex(random_bytes(8)),
                'scope' => isset($rule['scope']) ? strtoupper($rule['scope']) : 'GLOBAL',
                'referrer_mb_id' => isset($rule['referrer_mb_id']) ? $rule['referrer_mb_id'] : '',
                'campaign_id' => isset($rule['campaign_id']) ? $rule['campaign_id'] : '',
                'commission_rate' => isset($rule['commission_rate']) ? $rule['commission_rate'] : '0.0000',
                'rule_type' => 'LIFETIME',
                'active_from' => isset($rule['active_from']) ? $rule['active_from'] : $now,
                'active_until' => isset($rule['active_until']) ? $rule['active_until'] : null,
                'status' => 'ACTIVE',
                'priority' => isset($rule['priority']) ? (int) $rule['priority'] : 0,
                'metadata_json' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ), $rule);

            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                return $this->store->insertRule($row);
            }
            if (!function_exists('sql_query')) {
                return array('ok' => false);
            }
            $tbl = OnoffCore_ReferralSchema::tableCommissionRule();
            $until = $row['active_until'] ? "'" . sql_escape_string($row['active_until']) . "'" : 'NULL';
            $sql = "INSERT INTO `{$tbl}` (`rule_id`,`scope`,`referrer_mb_id`,`campaign_id`,`commission_rate`,`rule_type`,`active_from`,`active_until`,`status`,`priority`,`created_at`,`updated_at`)
                VALUES ('" . sql_escape_string($row['rule_id']) . "','"
                . sql_escape_string($row['scope']) . "','"
                . sql_escape_string($row['referrer_mb_id']) . "','"
                . sql_escape_string($row['campaign_id']) . "',"
                . (float) $row['commission_rate'] . ",'LIFETIME','"
                . sql_escape_string($row['active_from']) . "',{$until},'ACTIVE',"
                . (int) $row['priority'] . ",'{$now}','{$now}')
                ON DUPLICATE KEY UPDATE commission_rate=VALUES(commission_rate), status=VALUES(status), updated_at=VALUES(updated_at)";
            return array('ok' => (bool) sql_query($sql, false), 'rule' => $row);
        }

        /** @return array<int,array> */
        private function loadActiveRules($atTs)
        {
            if ($this->store instanceof OnoffCore_MemoryReferralStore) {
                $out = array();
                foreach ($this->store->listRules() as $rule) {
                    if (strtotime($rule['active_from']) > $atTs) {
                        continue;
                    }
                    if (!empty($rule['active_until']) && strtotime($rule['active_until']) < $atTs) {
                        continue;
                    }
                    $out[] = $rule;
                }
                return $out;
            }
            if (!function_exists('sql_fetch')) {
                return array();
            }
            $tbl = OnoffCore_ReferralSchema::tableCommissionRule();
            $at = date('Y-m-d H:i:s', $atTs);
            $at_s = sql_escape_string($at);
            $res = sql_query("SELECT * FROM `{$tbl}` WHERE status = 'ACTIVE' AND active_from <= '{$at_s}' AND (active_until IS NULL OR active_until >= '{$at_s}')", false);
            $out = array();
            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $out[] = $row;
                }
            }
            return $out;
        }
    }
}
