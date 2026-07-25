<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_demo_default_partner_mb_id')) {
    function lc_demo_default_partner_mb_id()
    {
        return 'lc_partner';
    }
}

if (!function_exists('lc_demo_default_advertiser_mb_id')) {
    function lc_demo_default_advertiser_mb_id()
    {
        return 'lc_advertiser';
    }
}

if (!function_exists('lc_demo_default_password')) {
    function lc_demo_default_password()
    {
        return 'demo1234!';
    }
}

if (!function_exists('lc_demo_member_exists')) {
    function lc_demo_member_exists($mb_id)
    {
        global $g5;

        if ($mb_id === '' || !isset($g5['member_table'])) {
            return false;
        }

        $mb_id_esc = sql_escape_string($mb_id);
        $row = sql_fetch(" SELECT mb_id FROM {$g5['member_table']} WHERE mb_id = '{$mb_id_esc}' LIMIT 1 ", false);

        return is_array($row) && !empty($row['mb_id']);
    }
}

if (!function_exists('lc_demo_member_ensure')) {
    /**
     * @return array{ok:bool,message:string,created:bool,mb_id:string}
     */
    function lc_demo_member_ensure($mb_id, $name, $password = '')
    {
        if ($mb_id === '') {
            return array('ok' => false, 'message' => '회원 ID가 필요합니다.', 'created' => false, 'mb_id' => '');
        }

        if (lc_demo_member_exists($mb_id)) {
            return array('ok' => true, 'message' => '이미 존재하는 회원입니다.', 'created' => false, 'mb_id' => $mb_id);
        }

        global $g5;

        if (!isset($g5['member_table']) || !function_exists('get_encrypt_string')) {
            return array('ok' => false, 'message' => '그누보드 회원 테이블을 사용할 수 없습니다.', 'created' => false, 'mb_id' => $mb_id);
        }

        $password = $password !== '' ? $password : lc_demo_default_password();
        $name = trim((string) $name);
        if ($name === '') {
            $name = $mb_id;
        }

        $mb_id_esc = sql_escape_string($mb_id);
        $name_esc = sql_escape_string($name);
        $nick_esc = sql_escape_string($name);
        $password_esc = sql_escape_string(get_encrypt_string($password));
        $email_esc = sql_escape_string($mb_id . '@linkconnect.demo');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^0-9a-fA-F:\.]/', '', $_SERVER['REMOTE_ADDR']) : '127.0.0.1';
        $ip_esc = sql_escape_string($ip);
        $memo_esc = sql_escape_string('LinkConnect 데모 계정');

        sql_query(" INSERT INTO {$g5['member_table']}
            SET mb_id = '{$mb_id_esc}',
                mb_password = '{$password_esc}',
                mb_name = '{$name_esc}',
                mb_nick = '{$nick_esc}',
                mb_email = '{$email_esc}',
                mb_homepage = '',
                mb_level = '2',
                mb_sex = '',
                mb_birth = '',
                mb_tel = '',
                mb_hp = '',
                mb_certify = '',
                mb_adult = '0',
                mb_zip1 = '',
                mb_zip2 = '',
                mb_addr1 = '',
                mb_addr2 = '',
                mb_addr3 = '',
                mb_addr_jibeon = '',
                mb_signature = '',
                mb_recommend = '',
                mb_point = '0',
                mb_today_login = '0000-00-00 00:00:00',
                mb_login_ip = '',
                mb_datetime = '" . G5_TIME_YMDHIS . "',
                mb_ip = '{$ip_esc}',
                mb_leave_date = '',
                mb_intercept_date = '',
                mb_email_certify = '" . G5_TIME_YMDHIS . "',
                mb_memo = '{$memo_esc}',
                mb_lost_certify = '',
                mb_mailling = '0',
                mb_sms = '0',
                mb_open = '0',
                mb_open_date = '" . G5_TIME_YMD . "',
                mb_profile = '{$memo_esc}',
                mb_memo_call = '',
                mb_1 = 'linkconnect_demo',
                mb_2 = '',
                mb_10 = '' ", false);

        if (!lc_demo_member_exists($mb_id)) {
            return array('ok' => false, 'message' => '회원 생성에 실패했습니다.', 'created' => false, 'mb_id' => $mb_id);
        }

        return array('ok' => true, 'message' => '회원이 생성되었습니다.', 'created' => true, 'mb_id' => $mb_id);
    }
}

if (!function_exists('lc_demo_partner_ensure')) {
    /**
     * @return array{ok:bool,message:string,partner:array|null,created:bool}
     */
    function lc_demo_partner_ensure($mb_id, $name = '')
    {
        $existing = lc_get_partner_by_mb_id($mb_id);
        if ($existing) {
            lc_partner_update_status((int) $existing['pt_id'], LC_PARTNER_STATUS_ACTIVE);
            $existing = lc_get_partner_by_mb_id($mb_id);
            lc_demo_partner_apply_profile((int) $existing['pt_id']);

            return array('ok' => true, 'message' => '기존 파트너를 활성화했습니다.', 'partner' => $existing, 'created' => false);
        }

        $name = $name !== '' ? $name : '데모파트너';
        $create = lc_partner_create($mb_id, $name, LC_PARTNER_STATUS_ACTIVE);
        if (!$create['ok'] || !is_array($create['partner'])) {
            return array('ok' => false, 'message' => $create['message'], 'partner' => null, 'created' => false);
        }

        lc_demo_partner_apply_profile((int) $create['partner']['pt_id']);

        return array(
            'ok'      => true,
            'message' => '파트너가 생성되었습니다.',
            'partner' => lc_get_partner_by_id((int) $create['partner']['pt_id']),
            'created' => true,
        );
    }
}

if (!function_exists('lc_demo_partner_apply_profile')) {
    function lc_demo_partner_apply_profile($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return;
        }

        $table = lc_table('partners');
        lc_sql_query(" UPDATE `{$table}` SET
            pt_bank_name = '국민은행',
            pt_bank_account = '123-456-789012',
            pt_bank_holder = '데모파트너',
            pt_balance = 7230000,
            pt_updated_at = NOW()
            WHERE pt_id = '{$pt_id}' ", false);
    }
}

if (!function_exists('lc_demo_campaign_seed_for_merchant')) {
    function lc_demo_campaign_seed_for_merchant($mt_id)
    {
        if (!lc_db_installed() || !function_exists('lc_sample_cpa_campaigns')) {
            return 0;
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('campaigns');
        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE mt_id = '{$mt_id}' ");
        if ($count_row && (int) $count_row['cnt'] > 0) {
            return 0;
        }

        $inserted = 0;
        $def = function_exists('lc_banktupt_campaign_definition')
            ? lc_banktupt_campaign_definition()
            : array(
                'code' => 'CPA-BANKTUPT',
                'title' => '개인회생 상담 DB',
                'category' => '법률',
                'price' => 30000,
                'approval_rate' => '68%',
                'avg_time' => '1.8일',
                'allowed_channels' => '블로그, 카페, 지식iN, SNS',
                'forbidden_channels' => '허위광고, 브랜드 사칭, 스팸문자',
                'description' => '개인회생·개인파산·채권추심 무료 상담 DB',
                'badge' => '추천',
                'recommended' => true,
                'status' => LC_STATUS_ACTIVE,
            );
        $landing = function_exists('lc_banktupt_landing_url') ? lc_banktupt_landing_url() : '';

        lc_sql_query(" INSERT INTO `{$table}` SET
            mt_id = '{$mt_id}',
            cp_code = '" . lc_sql_escape((string) $def['code']) . "',
            cp_name = '" . lc_sql_escape((string) $def['title']) . "',
            cp_category = '" . lc_sql_escape((string) $def['category']) . "',
            cp_type = 'cpa',
            cp_price = '" . (int) $def['price'] . "',
            cp_approval_rate = '" . lc_sql_escape((string) $def['approval_rate']) . "',
            cp_avg_time = '" . lc_sql_escape((string) $def['avg_time']) . "',
            cp_allowed_channels = '" . lc_sql_escape((string) $def['allowed_channels']) . "',
            cp_forbidden_channels = '" . lc_sql_escape((string) $def['forbidden_channels']) . "',
            cp_description = '" . lc_sql_escape((string) $def['description']) . "',
            cp_landing_url = '" . lc_sql_escape($landing) . "',
            cp_status = '" . lc_sql_escape((string) $def['status']) . "',
            cp_badge = '" . lc_sql_escape((string) ($def['badge'] ?? '')) . "',
            cp_recommended = '" . (!empty($def['recommended']) ? 1 : 0) . "',
            cp_sort = 1,
            cp_created_at = NOW(),
            cp_updated_at = NOW() ", false);
        $inserted = 1;

        return $inserted;
    }
}

if (!function_exists('lc_demo_merchant_ensure')) {
    /**
     * @return array{ok:bool,message:string,merchant:array|null,created:bool,campaigns:int,conversions:int,wallet:int}
     */
    function lc_demo_merchant_ensure($mb_id, $company = '', $balance = 2350000)
    {
        $company = $company !== '' ? $company : '데모광고주';
        $existing = lc_get_merchant_by_mb_id($mb_id);
        $created = false;

        if (!$existing) {
            $create = lc_merchant_create($mb_id, $company, LC_MERCHANT_STATUS_ACTIVE, $balance);
            if (!$create['ok'] || !is_array($create['merchant'])) {
                return array(
                    'ok'          => false,
                    'message'     => $create['message'],
                    'merchant'    => null,
                    'created'     => false,
                    'campaigns'   => 0,
                    'conversions' => 0,
                    'wallet'      => 0,
                );
            }
            $existing = $create['merchant'];
            $created = true;
        } else {
            lc_merchant_update_status((int) $existing['mt_id'], LC_MERCHANT_STATUS_ACTIVE);
            $existing = lc_get_merchant_by_id((int) $existing['mt_id']);
        }

        $mt_id = (int) $existing['mt_id'];
        $campaigns = lc_demo_campaign_seed_for_merchant($mt_id);

        return array(
            'ok'          => true,
            'message'     => $created ? '광고주가 생성되었습니다.' : '기존 광고주에 데모 데이터를 반영했습니다.',
            'merchant'    => lc_get_merchant_by_id($mt_id),
            'created'     => $created,
            'campaigns'   => $campaigns,
            'conversions' => 0,
            'wallet'      => 0,
        );
    }
}

if (!function_exists('lc_demo_conversion_rows')) {
    function lc_demo_conversion_rows()
    {
        return array(
            array(
                'id' => 'DB260701-001', 'campaign' => '개인회생 상담 DB', 'name' => '홍길동', 'phone' => '010-1234-5678',
                'email' => 'hong@example.com', 'region' => '서울', 'inquiry' => '개인회생 상담 원합니다', 'status' => '신규접수',
                'price' => 30000, 'channel' => '네이버 블로그', 'sub_id' => 'blog_01', 'comment' => '',
            ),
            array(
                'id' => 'DB260701-002', 'campaign' => '어린이 영어캠프 상담', 'name' => '이소희', 'phone' => '010-8812-5644',
                'email' => 'sohee@example.com', 'region' => '경기', 'inquiry' => '무료체험 신청', 'status' => '승인완료',
                'price' => 35000, 'channel' => '인스타그램', 'sub_id' => 'insta_bio', 'comment' => '상담 예약 완료',
            ),
            array(
                'id' => 'DB260701-003', 'campaign' => '개인회생 상담 DB', 'name' => '박재민', 'phone' => '010-2199-9922',
                'email' => 'jm@example.com', 'region' => '부산', 'inquiry' => '채무상담', 'status' => '확인중',
                'price' => 30000, 'channel' => '카카오톡', 'sub_id' => 'kakao_01', 'comment' => '',
            ),
            array(
                'id' => 'DB260701-004', 'campaign' => '자동차 렌트 상담 DB', 'name' => '최지훈', 'phone' => '010-5511-3377',
                'email' => 'hoon@example.com', 'region' => '대구', 'inquiry' => 'G80 렌트', 'status' => '취소/무효',
                'price' => 0, 'channel' => '구글 검색', 'sub_id' => '', 'comment' => '연락처 결번',
            ),
            array(
                'id' => 'DB260630-005', 'campaign' => '소상공인 대출 상담', 'name' => '정민수', 'phone' => '010-7788-1122',
                'email' => 'min@example.com', 'region' => '인천', 'inquiry' => '정부지원 대출', 'status' => '취소요청',
                'price' => 32000, 'channel' => '네이버 검색', 'sub_id' => '', 'comment' => '조건 불일치',
            ),
        );
    }
}

if (!function_exists('lc_demo_conversion_seed_for_pair')) {
    function lc_demo_conversion_seed_for_pair($mt_id, $pt_id, $lk_map = array())
    {
        if (!lc_db_installed()) {
            return 0;
        }

        $mt_id = (int) $mt_id;
        $pt_id = (int) $pt_id;
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');

        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE c.mt_id = '{$mt_id}' ");
        if ($count_row && (int) $count_row['cnt'] > 0) {
            return 0;
        }

        $status_map = array(
            '신규접수' => LC_STATUS_PENDING,
            '확인중'   => LC_STATUS_PENDING,
            '승인완료' => LC_STATUS_APPROVED,
            '취소/무효' => LC_STATUS_REJECTED,
            '취소요청' => LC_STATUS_PENDING,
        );

        $inserted = 0;
        foreach (lc_demo_conversion_rows() as $sample) {
            $campaign = lc_sql_fetch(" SELECT cp_id, cp_price FROM `{$cp_table}`
                WHERE cp_name = '" . lc_sql_escape($sample['campaign']) . "' AND mt_id = '{$mt_id}' LIMIT 1 ");
            if (!$campaign) {
                continue;
            }

            $lk_id = 0;
            if (!empty($lk_map[$sample['campaign']])) {
                $lk_id = (int) $lk_map[$sample['campaign']];
            }

            $status = isset($status_map[$sample['status']]) ? $status_map[$sample['status']] : LC_STATUS_PENDING;
            $price = $status === LC_STATUS_APPROVED ? (int) $campaign['cp_price'] : (int) $sample['price'];

            lc_sql_query(" INSERT INTO `{$cv_table}` SET
                cv_code = '" . lc_sql_escape($sample['id']) . "',
                pt_id = '{$pt_id}',
                cp_id = '" . (int) $campaign['cp_id'] . "',
                lk_id = '{$lk_id}',
                cv_name = '" . lc_sql_escape($sample['name']) . "',
                cv_phone = '" . lc_sql_escape($sample['phone']) . "',
                cv_email = '" . lc_sql_escape($sample['email'] ?? '') . "',
                cv_region = '" . lc_sql_escape($sample['region'] ?? '') . "',
                cv_inquiry = '" . lc_sql_escape($sample['inquiry'] ?? '') . "',
                cv_status = '" . lc_sql_escape($status) . "',
                cv_price = '{$price}',
                cv_channel = '" . lc_sql_escape($sample['channel'] ?? '') . "',
                cv_sub_id = '" . lc_sql_escape($sample['sub_id'] ?? '') . "',
                cv_comment = '" . lc_sql_escape($sample['comment'] ?? '') . "',
                cv_created_at = NOW(),
                cv_updated_at = NOW() ", false);
            $inserted++;
        }

        return $inserted;
    }
}

if (!function_exists('lc_demo_link_seed_for_partner')) {
    function lc_demo_link_seed_for_partner($pt_id, $mt_id)
    {
        if (!lc_db_installed()) {
            return array('inserted' => 0, 'map' => array());
        }

        $pt_id = (int) $pt_id;
        $mt_id = (int) $mt_id;
        $lk_table = lc_table('links');
        $cp_table = lc_table('campaigns');

        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$lk_table}` WHERE pt_id = '{$pt_id}' ");
        if ($count_row && (int) $count_row['cnt'] > 0) {
            return array('inserted' => 0, 'map' => array());
        }

        $links = array(
            array('campaign' => '개인회생 상담 DB', 'code' => 'demo_blog01', 'channel' => '네이버 블로그', 'sub_id' => 'blog_01'),
        );

        $inserted = 0;
        $map = array();
        foreach ($links as $link) {
            $campaign = lc_sql_fetch(" SELECT cp_id, cp_name FROM `{$cp_table}`
                WHERE cp_name = '" . lc_sql_escape($link['campaign']) . "' AND mt_id = '{$mt_id}' LIMIT 1 ");
            if (!$campaign) {
                continue;
            }

            lc_sql_query(" INSERT INTO `{$lk_table}` SET
                pt_id = '{$pt_id}',
                cp_id = '" . (int) $campaign['cp_id'] . "',
                lk_code = '" . lc_sql_escape($link['code']) . "',
                lk_channel = '" . lc_sql_escape($link['channel']) . "',
                lk_sub_id = '" . lc_sql_escape($link['sub_id']) . "',
                lk_status = 'active',
                lk_created_at = NOW(),
                lk_updated_at = NOW() ", false);

            $lk_id = (int) lc_sql_insert_id();
            if ($lk_id > 0) {
                $map[(string) $campaign['cp_name']] = $lk_id;
            }
            $inserted++;
        }

        return array('inserted' => $inserted, 'map' => $map);
    }
}

if (!function_exists('lc_demo_wallet_seed_for_merchant')) {
    function lc_demo_wallet_seed_for_merchant($mt_id)
    {
        if (!lc_db_installed() || !function_exists('lc_sample_merchant_wallet_history')) {
            return 0;
        }

        $mt_id = (int) $mt_id;
        $table = lc_table('wallet_transactions');
        $count_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE mt_id = '{$mt_id}' ");
        if ($count_row && (int) $count_row['cnt'] > 1) {
            return 0;
        }

        $inserted = 0;
        foreach (lc_sample_merchant_wallet_history() as $row) {
            $result = lc_wallet_record(
                $mt_id,
                $row['type'] === '충전' ? 'charge' : 'deduct',
                (int) $row['amount'],
                (string) $row['memo'],
                'demo',
                0
            );
            if ($result['ok']) {
                $inserted++;
            }
        }

        return $inserted;
    }
}

if (!function_exists('lc_demo_contract_default_form')) {
    /**
     * 데모·샘플 계약서에 채울 광고주 정보
     *
     * @return array<string,string>
     */
    function lc_demo_contract_default_form($mt_id)
    {
        $mt_id = (int) $mt_id;
        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        $member = null;
        if (is_array($merchant) && !empty($merchant['mb_id'])) {
            $member = lc_merchant_contract_get_member_row((string) $merchant['mb_id']);
        }

        $company = trim((string) (is_array($merchant) ? ($merchant['mt_company'] ?? '') : ''));
        if ($company === '') {
            $company = '데모광고주';
        }
        if (mb_strpos($company, '(주)', 0, 'UTF-8') === false && mb_strpos($company, '주)', 0, 'UTF-8') === false) {
            $company .= ' (주)';
        }

        $mb_id = is_array($merchant) ? (string) ($merchant['mb_id'] ?? '') : '';
        $email = is_array($member) && !empty($member['mb_email'])
            ? (string) $member['mb_email']
            : ($mb_id !== '' ? $mb_id . '@linkconnect.demo' : 'demo@linkconnect.demo');
        $contact_name = is_array($member) && !empty($member['mb_name']) ? (string) $member['mb_name'] : '이마케팅';

        return array(
            'companyName'        => $company,
            'representativeName' => '김데모',
            'businessNumber'     => '123-45-67890',
            'companyAddress'     => '서울특별시 강남구 테헤란로 123, 링크커넥트빌딩 10층',
            'companyPhone'       => '02-1234-5678',
            'contactName'        => $contact_name,
            'contactPhone'       => '010-9876-5432',
            'contactEmail'       => $email,
            'signerName'         => '김데모',
            'signerPosition'     => '대표이사',
            'signerPhone'        => '010-9876-5432',
            'signerEmail'        => $email,
        );
    }
}

if (!function_exists('lc_demo_contract_signature_png')) {
    function lc_demo_contract_signature_png()
    {
        $binary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);

        return $binary === false ? '' : $binary;
    }
}

if (!function_exists('lc_demo_contract_seed_for_merchant')) {
    /**
     * 데모·샘플용 체결 완료 계약서 생성 (전자서명 + PDF 포함)
     *
     * @return array{ok:bool,message:string,skipped?:bool,contractCode?:string}
     */
    function lc_demo_contract_seed_for_merchant($mt_id)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => '광고주 ID가 필요합니다.');
        }

        if (!function_exists('lc_merchant_contract_sign')) {
            return array('ok' => false, 'message' => '계약 모듈을 사용할 수 없습니다.');
        }

        if (function_exists('lc_merchant_contract_db_ensure_schema')) {
            $schema = lc_merchant_contract_db_ensure_schema();
            if (empty($schema['ok'])) {
                return array('ok' => false, 'message' => $schema['message'] ?? '계약 테이블 준비 실패');
            }
        }

        if (lc_merchant_contract_is_signed($mt_id)) {
            $existing = lc_merchant_contract_get($mt_id);
            $code = is_array($existing) ? (string) ($existing['mc_contract_code'] ?? '') : '';

            return array(
                'ok'           => true,
                'message'      => '이미 체결된 계약이 있습니다.' . ($code !== '' ? " ({$code})" : ''),
                'skipped'      => true,
                'contractCode' => $code,
            );
        }

        $form = lc_demo_contract_default_form($mt_id);
        $png = lc_demo_contract_signature_png();
        if ($png === '') {
            return array('ok' => false, 'message' => '샘플 서명 이미지를 준비하지 못했습니다.');
        }

        $payload = array_merge($form, array(
            'agreements' => array(
                'readAll'      => true,
                'hasAuthority' => true,
                'electronic'   => true,
                'noModify'     => true,
            ),
            'signatureDataUrl' => 'data:image/png;base64,' . base64_encode($png),
        ));

        $result = lc_merchant_contract_sign($mt_id, $payload);
        if (empty($result['ok'])) {
            return array(
                'ok'      => false,
                'message' => (string) ($result['message'] ?? '샘플 계약 생성에 실패했습니다.'),
            );
        }

        $contract = isset($result['contract']) && is_array($result['contract']) ? $result['contract'] : lc_merchant_contract_get($mt_id);
        $code = is_array($contract) ? (string) ($contract['mc_contract_code'] ?? '') : '';

        return array(
            'ok'           => true,
            'message'      => '샘플 계약이 체결되었습니다.' . ($code !== '' ? " ({$code})" : ''),
            'skipped'      => false,
            'contractCode' => $code,
        );
    }
}

if (!function_exists('lc_demo_seed_run')) {
    /**
     * @return array{ok:bool,message:string,details:array}
     */
    function lc_demo_seed_run(array $options = array())
    {
        if (!lc_db_installed()) {
            return array(
                'ok'      => false,
                'message' => 'LinkConnect DB가 설치되지 않았습니다. 먼저 install을 실행하세요.',
                'details' => array(),
            );
        }

        $partner_mb = isset($options['partner_mb_id']) ? trim((string) $options['partner_mb_id']) : lc_demo_default_partner_mb_id();
        $advertiser_mb = isset($options['advertiser_mb_id']) ? trim((string) $options['advertiser_mb_id']) : lc_demo_default_advertiser_mb_id();
        $password = isset($options['password']) ? (string) $options['password'] : lc_demo_default_password();
        $partner_name = isset($options['partner_name']) ? (string) $options['partner_name'] : '데모파트너';
        $advertiser_company = isset($options['advertiser_company']) ? (string) $options['advertiser_company'] : '데모광고주';

        $member_partner = lc_demo_member_ensure($partner_mb, $partner_name, $password);
        if (!$member_partner['ok']) {
            return array('ok' => false, 'message' => $member_partner['message'], 'details' => array());
        }

        $member_advertiser = lc_demo_member_ensure($advertiser_mb, $advertiser_company, $password);
        if (!$member_advertiser['ok']) {
            return array('ok' => false, 'message' => $member_advertiser['message'], 'details' => array());
        }

        $partner = lc_demo_partner_ensure($partner_mb, $partner_name);
        if (!$partner['ok'] || !is_array($partner['partner'])) {
            return array('ok' => false, 'message' => $partner['message'], 'details' => array());
        }

        $merchant = lc_demo_merchant_ensure($advertiser_mb, $advertiser_company);
        if (!$merchant['ok'] || !is_array($merchant['merchant'])) {
            return array('ok' => false, 'message' => $merchant['message'], 'details' => array());
        }

        $pt_id = (int) $partner['partner']['pt_id'];
        $mt_id = (int) $merchant['merchant']['mt_id'];

        $links = lc_demo_link_seed_for_partner($pt_id, $mt_id);
        $conversions = lc_demo_conversion_seed_for_pair($mt_id, $pt_id, $links['map']);
        $wallet = lc_demo_wallet_seed_for_merchant($mt_id);
        $contract = lc_demo_contract_seed_for_merchant($mt_id);

        $message = "데모 계정 준비 완료\n";
        $message .= "파트너: {$partner['partner']['pt_code']} ({$partner_mb})\n";
        $message .= "광고주: {$merchant['merchant']['mt_code']} ({$advertiser_mb})\n";
        $message .= "비밀번호: {$password}\n";
        $message .= "캠페인: " . (int) $merchant['campaigns'] . "건, 전환: " . (int) $conversions . "건, 링크: " . (int) $links['inserted'] . "건\n";
        $message .= "계약: " . ($contract['message'] ?? '—');

        return array(
            'ok'      => true,
            'message' => $message,
            'details' => array(
                'password'         => $password,
                'partnerMbId'      => $partner_mb,
                'advertiserMbId'   => $advertiser_mb,
                'partner'          => $partner['partner'],
                'merchant'         => $merchant['merchant'],
                'partnerMemberCreated'   => $member_partner['created'],
                'advertiserMemberCreated' => $member_advertiser['created'],
                'campaignsInserted'      => (int) $merchant['campaigns'],
                'conversionsInserted'    => (int) $conversions,
                'linksInserted'          => (int) $links['inserted'],
                'walletInserted'         => (int) $wallet,
                'contract'               => $contract,
            ),
        );
    }
}
