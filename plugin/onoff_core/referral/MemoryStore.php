<?php
if (!class_exists('OnoffCore_MemoryReferralStore', false)) {
    final class OnoffCore_MemoryReferralStore
    {
        /** @var array<string,array> */
        public $codes = array();
        /** @var array<string,array> */
        public $memberReferrals = array();
        /** @var array<string,array> */
        public $rules = array();
        /** @var array<string,array> */
        public $commissions = array();
        /** @var array<string,string> */
        private $codeIndex = array();
        /** @var array<string,string> */
        private $customerIndex = array();
        /** @var array<string,string> */
        private $paymentIndex = array();

        public function insertCode(array $row)
        {
            $c = (string) $row['referral_code'];
            if (isset($this->codeIndex[$c])) {
                return array('ok' => false, 'error' => 'duplicate_code');
            }
            $this->codes[$c] = $row;
            $this->codeIndex[$c] = $c;
            return array('ok' => true);
        }

        public function getCode($referral_code)
        {
            $referral_code = (string) $referral_code;
            return isset($this->codes[$referral_code]) ? $this->codes[$referral_code] : null;
        }

        public function insertMemberReferral(array $row)
        {
            $cust = (string) $row['customer_mb_id'];
            if (isset($this->customerIndex[$cust])) {
                return array('ok' => false, 'error' => 'duplicate_customer');
            }
            $this->memberReferrals[$cust] = $row;
            $this->customerIndex[$cust] = $cust;
            return array('ok' => true);
        }

        public function getMemberReferralByCustomer($customer_mb_id)
        {
            $customer_mb_id = (string) $customer_mb_id;
            return isset($this->memberReferrals[$customer_mb_id]) ? $this->memberReferrals[$customer_mb_id] : null;
        }

        public function insertRule(array $row)
        {
            $rid = (string) $row['rule_id'];
            $this->rules[$rid] = $row;
            return array('ok' => true);
        }

        /** @return array<int,array> */
        public function listRules()
        {
            return array_values($this->rules);
        }

        public function insertCommission(array $row)
        {
            $pid = (string) $row['payment_id'];
            if (isset($this->paymentIndex[$pid])) {
                return array('ok' => false, 'error' => 'duplicate_payment');
            }
            $cid = (string) $row['commission_id'];
            $this->commissions[$cid] = $row;
            $this->paymentIndex[$pid] = $cid;
            return array('ok' => true);
        }

        public function getCommissionByPayment($payment_id)
        {
            $payment_id = (string) $payment_id;
            if (!isset($this->paymentIndex[$payment_id])) {
                return null;
            }
            return $this->commissions[$this->paymentIndex[$payment_id]];
        }

        public function updateCommission($commission_id, array $fields)
        {
            if (!isset($this->commissions[$commission_id])) {
                return false;
            }
            foreach ($fields as $k => $v) {
                $this->commissions[$commission_id][$k] = $v;
            }
            return true;
        }

        /** @return array<int,array> */
        public function listCommissionsByReferrer($referrer_mb_id)
        {
            $out = array();
            foreach ($this->commissions as $row) {
                if ((string) $row['referrer_mb_id'] === (string) $referrer_mb_id) {
                    $out[] = $row;
                }
            }
            return $out;
        }
    }
}
