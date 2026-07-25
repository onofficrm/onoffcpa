<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_sample_cpa_items')) {
    function lc_sample_cpa_items()
    {
        return array(
            array('id' => '1', 'category' => '법률', 'title' => '개인회생 무료상담 CPA', 'reward' => 45000, 'approval' => '85%', 'badge' => '고수익', 'status' => LC_STATUS_ACTIVE),
            array('id' => '2', 'category' => '병원', 'title' => '피부클리닉 상담 CPA', 'reward' => 18000, 'approval' => '92%', 'badge' => '인기', 'status' => LC_STATUS_ACTIVE),
            array('id' => '3', 'category' => '보험', 'title' => '보험비교 상담 CPA', 'reward' => 25000, 'approval' => '88%', 'badge' => '승인율 높음', 'status' => LC_STATUS_ACTIVE),
            array('id' => '4', 'category' => '교육', 'title' => '영어학원 상담 CPA', 'reward' => 12000, 'approval' => '95%', 'badge' => '신규', 'status' => LC_STATUS_ACTIVE),
        );
    }
}

if (!function_exists('lc_sample_cps_items')) {
    function lc_sample_cps_items()
    {
        return array(
            array('id' => '1', 'brand' => '건강식품 쇼핑몰 CPS', 'rate' => '12%', 'cookie' => '7일', 'badge' => '인기'),
            array('id' => '2', 'brand' => '뷰티 스킨케어 CPS', 'rate' => '15%', 'cookie' => '14일', 'badge' => '수익률 상향'),
            array('id' => '3', 'brand' => '생활용품 쇼핑몰 CPS', 'rate' => '8%', 'cookie' => '7일', 'badge' => '진행중'),
        );
    }
}

if (!function_exists('lc_sample_events')) {
    function lc_sample_events()
    {
        return array(
            array('id' => '1', 'title' => '신규 파트너 웰컴 보너스', 'period' => '2026.07.01 ~ 2026.07.31', 'badge' => 'HOT'),
            array('id' => '2', 'title' => 'CPA 승인율 90% 이상 캠페인 모음', 'period' => '상시', 'badge' => '추천'),
            array('id' => '3', 'title' => '여름 시즌 CPS 수수료 업', 'period' => '2026.06.15 ~ 2026.08.31', 'badge' => '한정'),
        );
    }
}

if (!function_exists('lc_sample_partner_stats')) {
    function lc_sample_partner_stats()
    {
        return array(
            array('label' => '오늘 클릭 수', 'value' => '1,248', 'tone' => 'blue'),
            array('label' => '오늘 접수 DB', 'value' => '32', 'tone' => 'cyan'),
            array('label' => '승인완료 DB', 'value' => '21', 'tone' => 'emerald'),
            array('label' => '취소/무효 DB', 'value' => '4', 'tone' => 'red'),
            array('label' => '오늘 예상수입', 'value' => '960,000원', 'tone' => 'highlight'),
            array('label' => '이번 달 확정수입', 'value' => '8,430,000원', 'tone' => 'dark'),
        );
    }
}

if (!function_exists('lc_sample_merchant_stats')) {
    function lc_sample_merchant_stats()
    {
        return array(
            array('label' => '오늘 접수 DB', 'value' => '58', 'tone' => 'cyan'),
            array('label' => '승인 대기', 'value' => '12', 'tone' => 'amber'),
            array('label' => '승인 완료', 'value' => '41', 'tone' => 'emerald'),
            array('label' => '광고비 잔액', 'value' => '2,450,000원', 'tone' => 'highlight'),
        );
    }
}

if (!function_exists('lc_sample_admin_stats')) {
    function lc_sample_admin_stats()
    {
        return array(
            array('label' => '전체 파트너', 'value' => '1,284', 'tone' => 'emerald'),
            array('label' => '전체 광고주', 'value' => '326', 'tone' => 'cyan'),
            array('label' => '진행 캠페인', 'value' => '89', 'tone' => 'blue'),
            array('label' => '오늘 접수 DB', 'value' => '412', 'tone' => 'highlight'),
        );
    }
}

if (!function_exists('lc_sample_conversions')) {
    function lc_sample_conversions()
    {
        return array(
            array('id' => 'DB-240701', 'campaign' => '개인회생 무료상담 CPA', 'partner' => 'partner_kim', 'status' => LC_STATUS_PENDING, 'date' => '2026-07-07 09:12'),
            array('id' => 'DB-240702', 'campaign' => '피부클리닉 상담 CPA', 'partner' => 'blog_master', 'status' => LC_STATUS_APPROVED, 'date' => '2026-07-07 08:44'),
            array('id' => 'DB-240703', 'campaign' => '보험비교 상담 CPA', 'partner' => 'yt_creator', 'status' => LC_STATUS_REJECTED, 'date' => '2026-07-06 21:30'),
            array('id' => 'DB-240704', 'campaign' => '영어학원 상담 CPA', 'partner' => 'cafe_admin', 'status' => LC_STATUS_APPROVED, 'date' => '2026-07-06 18:02'),
        );
    }
}

if (!function_exists('lc_sample_links')) {
    function lc_sample_links()
    {
        return array(
            array('campaign' => '개인회생 무료상담 CPA', 'url' => 'https://linkconnect.co.kr/t/lc001', 'clicks' => 842, 'dbs' => 28),
            array('campaign' => '피부클리닉 상담 CPA', 'url' => 'https://linkconnect.co.kr/t/lc002', 'clicks' => 406, 'dbs' => 11),
        );
    }
}

if (!function_exists('lc_sample_partners')) {
    function lc_sample_partners()
    {
        return array(
            array('id' => 'P-1001', 'name' => '김파트너', 'grade' => 'Gold', 'earnings' => '8,430,000', 'status' => LC_STATUS_ACTIVE),
            array('id' => 'P-1002', 'name' => '블로그마스터', 'grade' => 'Silver', 'earnings' => '3,120,000', 'status' => LC_STATUS_ACTIVE),
        );
    }
}

if (!function_exists('lc_sample_merchants')) {
    function lc_sample_merchants()
    {
        return array(
            array('id' => 'M-501', 'name' => '법률네트워크', 'balance' => '1,200,000', 'campaigns' => 4, 'status' => LC_STATUS_ACTIVE),
            array('id' => 'M-502', 'name' => '뷰티브랜드', 'balance' => '850,000', 'campaigns' => 2, 'status' => LC_STATUS_ACTIVE),
        );
    }
}

if (!function_exists('lc_sample_inflow')) {
    function lc_sample_inflow()
    {
        return array(
            array('label' => '네이버 블로그', 'pct' => 45),
            array('label' => '네이버 카페', 'pct' => 25),
            array('label' => '유튜브', 'pct' => 15),
            array('label' => '인스타그램', 'pct' => 10),
            array('label' => '기타', 'pct' => 5),
        );
    }
}

if (!function_exists('lc_sample_home_metrics')) {
    function lc_sample_home_metrics()
    {
        return array(
            array('label' => '진행 CPA 캠페인', 'value' => '89', 'suffix' => '개', 'tone' => 'emerald'),
            array('label' => '활성 파트너', 'value' => '1,284', 'suffix' => '명', 'tone' => 'cyan'),
            array('label' => '평균 승인율', 'value' => '78.2', 'suffix' => '%', 'tone' => 'highlight'),
            array('label' => '이번 달 정산', 'value' => '4.2', 'suffix' => '억', 'tone' => 'dark'),
        );
    }
}

if (!function_exists('lc_sample_cpa_flow')) {
    function lc_sample_cpa_flow()
    {
        return array(
            array('step' => '01', 'title' => '캠페인 선택', 'desc' => '카테고리·단가·승인율 기준으로 홍보할 CPA 상품을 고릅니다.'),
            array('step' => '02', 'title' => '홍보 링크 생성', 'desc' => '전용 트래킹 링크를 발급받아 블로그·SNS·카페에 배포합니다.'),
            array('step' => '03', 'title' => '트래픽 유입', 'desc' => '방문자가 상담·견적·예약 폼을 통해 DB를 접수합니다.'),
            array('step' => '04', 'title' => '광고주 승인', 'desc' => '광고주가 유효 DB를 검수·승인하면 수익이 확정됩니다.'),
            array('step' => '05', 'title' => '정산·출금', 'desc' => '승인 완료 DB 기준으로 정산 후 파트너센터에서 출금합니다.'),
        );
    }
}

if (!function_exists('lc_sample_cpa_categories')) {
    function lc_sample_cpa_categories()
    {
        return array('전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타');
    }
}

if (!function_exists('lc_sample_cpa_campaigns')) {
    function lc_sample_cpa_campaigns()
    {
        return array(
            array('id' => '1', 'title' => '개인회생 상담 DB', 'category' => '법률', 'price' => 30000, 'approval_rate' => '68%', 'avg_time' => '1.8일', 'allowed_channels' => '블로그, 카페, 검색광고, SNS', 'forbidden_channels' => '허위광고, 브랜드 사칭, 스팸문자', 'status' => '진행중', 'badge' => '추천', 'recommended' => true),
            array('id' => '2', 'title' => '자동차 렌트 상담 DB', 'category' => '렌탈', 'price' => 25000, 'approval_rate' => '72%', 'avg_time' => '1.2일', 'allowed_channels' => '블로그, SNS, 커뮤니티', 'forbidden_channels' => '보상형 리워드, 강제 가입', 'status' => '진행중', 'badge' => '인기', 'recommended' => true),
            array('id' => '3', 'title' => '어린이 영어캠프 상담', 'category' => '교육', 'price' => 35000, 'approval_rate' => '55%', 'avg_time' => '2.5일', 'allowed_channels' => '맘카페, 교육 블로그, 인스타그램', 'forbidden_channels' => '과장광고, 관련없는 타겟팅', 'status' => '진행중', 'badge' => '신규', 'recommended' => true),
            array('id' => '4', 'title' => '임플란트/치아교정 상담', 'category' => '병원', 'price' => 40000, 'approval_rate' => '45%', 'avg_time' => '3.0일', 'allowed_channels' => '블로그, 건강카페', 'forbidden_channels' => '의료법 위반 문구, 허위 후기', 'status' => '진행중', 'badge' => '', 'recommended' => false),
            array('id' => '5', 'title' => '소상공인 대출 상담', 'category' => '금융', 'price' => 32000, 'approval_rate' => '60%', 'avg_time' => '1.5일', 'allowed_channels' => '자영업 커뮤니티, 블로그', 'forbidden_channels' => '불법 스팸, 사칭', 'status' => '마감임박', 'badge' => '', 'recommended' => false),
        );
    }
}

if (!function_exists('lc_sample_cps_flow')) {
    function lc_sample_cps_flow()
    {
        return array(
            array('step' => '01', 'title' => '주문 발생', 'desc' => '파트너 링크를 통해 쇼핑몰 방문·주문이 발생합니다.'),
            array('step' => '02', 'title' => '구매 확정', 'desc' => '취소·반품 없이 구매가 확정되면 전환으로 인정됩니다.'),
            array('step' => '03', 'title' => '수수료 산정', 'desc' => '판매금액 × 수수료율로 파트너 수익이 계산됩니다.'),
            array('step' => '04', 'title' => '정산 반영', 'desc' => '쿠키 기간 내 전환 건이 정산 주기에 맞춰 지급됩니다.'),
        );
    }
}

if (!function_exists('lc_sample_event_tabs')) {
    function lc_sample_event_tabs()
    {
        return array('전체', '파트너 이벤트', '광고주 프로모션', '단가 상승', '신규 캠페인', '마감 임박', '리워드 랭킹');
    }
}

if (!function_exists('lc_sample_event_summary')) {
    function lc_sample_event_summary()
    {
        return array(
            array('label' => '진행 중 이벤트', 'value' => '12', 'suffix' => '개', 'icon' => 'megaphone'),
            array('label' => '보너스 지급 캠페인', 'value' => '5', 'suffix' => '개', 'icon' => 'gift'),
            array('label' => '단가 상승 상품', 'value' => '8', 'suffix' => '개', 'icon' => 'trend'),
            array('label' => '마감 임박 이벤트', 'value' => '3', 'suffix' => '개', 'icon' => 'clock'),
        );
    }
}

if (!function_exists('lc_sample_event_recommendations')) {
    function lc_sample_event_recommendations()
    {
        return array(
            array('badge' => '단가 상승', 'dday' => 'D-5', 'title' => '개인회생 상담 DB 단가 상승 이벤트', 'reason' => '최근 법률 카테고리 유입 성과가 좋습니다.', 'product' => '개인회생 상담 DB', 'old_price' => 30000, 'price' => 40000, 'approval_rate' => '68%', 'cta' => '홍보 링크 만들기', 'cta_tone' => 'dark'),
            array('badge' => '고승인율', 'dday' => '', 'title' => '자동차 렌트 상담 DB 안정형 캠페인', 'reason' => '승인율이 높아 초보 파트너에게 적합합니다.', 'product' => '장기렌트카 견적 신청', 'old_price' => 0, 'price' => 25000, 'approval_rate' => '78%', 'cta' => '이벤트 보기', 'cta_tone' => 'outline'),
            array('badge' => '마감임박', 'dday' => 'D-3', 'title' => '영어캠프 상담 DB 시즌 프로모션', 'reason' => '시즌 키워드 검색량이 증가 중입니다.', 'product' => '세부 영어캠프 상담 DB', 'old_price' => 0, 'price' => 35000, 'approval_rate' => '', 'cta' => '지금 참여하기', 'cta_tone' => 'primary'),
        );
    }
}

if (!function_exists('lc_sample_event_progress')) {
    function lc_sample_event_progress()
    {
        return array(
            array('badge' => '참여중', 'period' => '~ 2026.10.31', 'title' => '첫 승인 5건 달성 보너스', 'desc' => '승인 DB 5건 달성 시 100,000원 보너스', 'alert' => '목표까지 승인 DB 2건 남았습니다!', 'pct' => 60, 'current' => 3, 'target' => 5, 'reward' => '100,000원', 'tone' => 'default'),
            array('badge' => '참여중', 'period' => '마감 D-2', 'title' => '개인회생 단가 상승 이벤트', 'desc' => '승인 DB 10건 이상 시 추가 단가 적용', 'alert' => '2건만 더 달성하면 추가 수익 발생!', 'pct' => 80, 'current' => 8, 'target' => 10, 'reward' => '80,000원', 'tone' => 'warning'),
            array('badge' => '달성완료', 'period' => '2026.10.05 달성', 'title' => '신규 파트너 첫 승인 보너스', 'desc' => '첫 승인 DB 1건 발생 완료', 'alert' => '목표를 성공적으로 달성했습니다!', 'pct' => 100, 'current' => 1, 'target' => 1, 'reward' => '50,000원', 'tone' => 'done'),
        );
    }
}

if (!function_exists('lc_sample_event_cards')) {
    function lc_sample_event_cards()
    {
        return array(
            array('badges' => array('진행중', '단가 상승'), 'title' => '개인회생 상담 DB 단가 상승 이벤트', 'desc' => '이벤트 기간 동안 개인회생 상담 DB 승인 1건당 파트너 지급 단가가 30,000원에서 40,000원으로 상승합니다.', 'period' => '2026.10.01 ~ 10.31', 'product' => '개인회생 상담 DB 외 3개', 'benefit' => '승인 1건당 +10,000원', 'ribbon' => ''),
            array('badges' => array('마감임박', '파트너 보너스'), 'title' => '신규 파트너 첫 승인 보너스', 'desc' => '신규 파트너가 첫 승인 DB를 발생시키면 추가 보너스 50,000원을 지급합니다.', 'period' => '~ 2026.10.10 까지', 'product' => '첫 승인 DB 1건 이상', 'benefit' => '50,000원 지급', 'ribbon' => '마감 3일전'),
            array('badges' => array('진행중', '신규 캠페인'), 'title' => '영어캠프 상담 DB 집중 프로모션', 'desc' => '겨울방학 시즌을 맞아 세부 영어캠프 상담 DB 캠페인의 집중 홍보 파트너를 모집합니다.', 'period' => '상시 진행', 'product' => '세부 영어캠프 상담 DB', 'benefit' => '35,000원', 'ribbon' => ''),
        );
    }
}

if (!function_exists('lc_sample_promo_cpa')) {
    function lc_sample_promo_cpa()
    {
        return array(
            array('event' => '10월 단가 상승 이벤트', 'title' => '개인회생 상담 DB', 'category' => '법률/세무', 'approval_rate' => '68%', 'old_price' => 30000, 'price' => 40000, 'bonus' => '+10,000원 상승', 'highlight' => true),
            array('event' => '첫 승인 보너스 5만원', 'title' => '장기렌트카 견적 신청', 'category' => '자동차', 'approval_rate' => '82%', 'old_price' => 0, 'price' => 25000, 'bonus' => '+보너스 혜택 적용', 'highlight' => false),
            array('event' => '영어캠프 시즌 프로모션', 'title' => '세부 영어캠프 상담 DB', 'category' => '교육', 'approval_rate' => '75%', 'old_price' => 30000, 'price' => 35000, 'bonus' => '+5,000원 상승', 'highlight' => false),
        );
    }
}

if (!function_exists('lc_sample_ranking_top')) {
    function lc_sample_ranking_top()
    {
        return array(
            array('rank' => 2, 'partner' => 'PTN-54**', 'dbs' => 96, 'reward' => '500,000원', 'tone' => 'silver'),
            array('rank' => 1, 'partner' => 'PTN-8291', 'dbs' => 128, 'reward' => '1,000,000원', 'earnings' => '3,840,000원', 'tone' => 'gold'),
            array('rank' => 3, 'partner' => 'PTN-30**', 'dbs' => 74, 'reward' => '300,000원', 'tone' => 'bronze'),
        );
    }
}

if (!function_exists('lc_sample_ranking_list')) {
    function lc_sample_ranking_list()
    {
        return array(
            array('rank' => 4, 'partner' => 'PTN-91**', 'dbs' => 68, 'reward' => '10만원'),
            array('rank' => 5, 'partner' => 'PTN-12**', 'dbs' => 61, 'reward' => '10만원'),
            array('rank' => 6, 'partner' => 'PTN-84**', 'dbs' => 55, 'reward' => '10만원'),
            array('rank' => 7, 'partner' => 'PTN-33**', 'dbs' => 52, 'reward' => '10만원'),
        );
    }
}

if (!function_exists('lc_sample_promo_copy')) {
    function lc_sample_promo_copy()
    {
        return array(
            '🔥 [한정 이벤트] 개인회생 상담 DB 단가 40,000원! 승인율 68% 안정 캠페인입니다. 무료 상담 신청 ▶',
            '✨ 신규 파트너 첫 승인 시 5만원 보너스! 지금 홍보 링크를 만들고 수익을 시작하세요.',
            '📈 자동차 렌트 상담 DB — 승인율 78%, 평균 승인 1.2일. 초보 파트너 추천 캠페인!',
        );
    }
}

if (!function_exists('lc_sample_promo_copy_tabs')) {
    function lc_sample_promo_copy_tabs()
    {
        return array(
            array(
                'id' => 'blog',
                'label' => '블로그',
                'copies' => array(
                    array('title' => '개인회생 단가 상승', 'text' => '🔥 [한정 이벤트] 개인회생 무료상담 DB — 이벤트 단가 40,000원! 승인율 68% 안정 캠페인. 지금 무료 상담 신청하세요 ▶ [링크]'),
                    array('title' => '첫 승인 보너스', 'text' => '✨ 링크커넥트 신규 파트너 첫 승인 시 5만원 추가 보너스! 개인회생 상담 CPA 홍보하고 수익 시작하기 → [링크]'),
                ),
            ),
            array(
                'id' => 'sns',
                'label' => 'SNS/숏폼',
                'copies' => array(
                    array('title' => '숏폼용 짧은 문구', 'text' => '💰 상담 1건마다 4만원! 개인회생 무료상담 이벤트 중 #부업 #CPA #링크커넥트'),
                    array('title' => '인스타 스토리', 'text' => '이번 달만 단가 UP ⬆️ 장기렌트 견적 DB 25,000원 + 첫승인 5만 보너스 🎁 프로필 링크에서 신청!'),
                ),
            ),
            array(
                'id' => 'cafe',
                'label' => '카페/커뮤니티',
                'copies' => array(
                    array('title' => '카페 게시글', 'text' => '[정보] 개인회생 무료상담 CPA 이벤트 안내\n- 이벤트 단가: 40,000원 (기존 30,000원)\n- 승인율: 약 68%\n- 기간: 10/31까지\n상담 신청: [링크]'),
                    array('title' => '댓글용', 'text' => '영어캠프 겨울방학 프로모션 진행 중입니다. 세부 영어캠프 상담 DB 35,000원, 시즌 키워드 유입 많아요!'),
                ),
            ),
        );
    }
}

if (!function_exists('lc_sample_event_detail_preview')) {
    function lc_sample_event_detail_preview()
    {
        return array(
            'title' => '개인회생 상담 DB 단가 상승 이벤트',
            'badges' => array('진행중', '단가 상승'),
            'period' => '2026.10.01 ~ 10.31',
            'condition' => '이벤트 기간 내 승인 DB 발생',
            'benefit' => '승인 1건당 +10,000원 (30,000원 → 40,000원)',
            'products' => array('개인회생 상담 DB', '법률 무료상담 CPA', '채무조정 안내 CPA'),
            'dday' => 'D-24',
        );
    }
}

if (!function_exists('lc_sample_advertiser_promo_cards')) {
    function lc_sample_advertiser_promo_cards()
    {
        return array(
            array('icon' => '📈', 'title' => '단가 상승 프로모션', 'desc' => '일정 기간 파트너 단가를 높여 더 많은 파트너 참여를 유도합니다.', 'target' => '빠르게 DB를 확보하고 싶은 광고주', 'benefit' => '이벤트 페이지 + 추천 영역 노출', 'cta' => '신청하기', 'tone' => 'primary'),
            array('icon' => '⭐', 'title' => '신규 캠페인 추천 노출', 'desc' => '새로 등록한 CPA 상품을 파트너에게 추천 캠페인으로 노출합니다.', 'target' => '신규 광고상품을 빠르게 알리고 싶은 광고주', 'benefit' => '메인 추천 캠페인 노출', 'cta' => '상담 신청', 'tone' => 'outline'),
            array('icon' => '🏆', 'title' => '파트너 랭킹 이벤트', 'desc' => '특정 상품 기준으로 파트너 랭킹 이벤트를 운영하여 홍보 경쟁을 유도합니다.', 'target' => '대량 디비가 필요한 광고주', 'benefit' => '랭킹 이벤트 페이지 노출', 'cta' => '이벤트 만들기 문의', 'tone' => 'outline'),
            array('icon' => '🎁', 'title' => '광고비 충전 보너스', 'desc' => '일정 금액 이상 광고비 충전 시 추가 노출 혜택을 제공합니다.', 'target' => '장기 캠페인을 운영하는 광고주', 'benefit' => '추천 상품 우선 노출', 'cta' => '광고비 충전 문의', 'tone' => 'outline'),
        );
    }
}

if (!function_exists('lc_sample_event_rules_checklist')) {
    function lc_sample_event_rules_checklist()
    {
        return array(
            array('text' => '허위·과장 광고로 유입된 DB는 이벤트 실적 및 리워드에서 제외됩니다.', 'critical' => true),
            array('text' => '금지 채널(스팸, 불법 사이트 등) 홍보 시 참여 자격이 박탈될 수 있습니다.', 'critical' => true),
            array('text' => '이벤트 기간 외 발생 DB는 프로모션 혜택이 적용되지 않습니다.', 'critical' => false),
            array('text' => '동일인 중복 신청·장난 신청 DB는 무효 처리됩니다.', 'critical' => false),
            array('text' => '타사 CPA 링크와 혼동될 수 있는 표현은 사용을 자제해주세요.', 'critical' => false),
        );
    }
}

if (!function_exists('lc_sample_event_rules')) {
    function lc_sample_event_rules()
    {
        return array(
            '허위·과장 광고로 유입된 DB는 이벤트 실적 및 리워드에서 제외됩니다.',
            '금지 채널 홍보 시 이벤트 참여 자격이 박탈될 수 있습니다.',
            '이벤트 기간 외 발생 DB는 프로모션 혜택이 적용되지 않습니다.',
            '동일인 중복 신청·장난 신청 DB는 무효 처리됩니다.',
        );
    }
}

if (!function_exists('lc_sample_home_events')) {
    function lc_sample_home_events()
    {
        return array(
            array('title' => '개인회생 상담 DB 단가 상승 이벤트', 'date' => '2026.10.01 ~ 10.31'),
            array('title' => '신규 파트너 첫 승인 보너스 5만원', 'date' => '~ 2026.10.10'),
            array('title' => '이번 달 파트너 리워드 랭킹', 'date' => '2026.10.01 ~ 10.31'),
        );
    }
}

/* ── 파트너센터 샘플 ── */

if (!function_exists('lc_sample_partner_chart_7d')) {
    function lc_sample_partner_chart_7d()
    {
        return array(
            array('date' => '10.01', 'click' => 400, 'db' => 24, 'approval' => 18),
            array('date' => '10.02', 'click' => 300, 'db' => 18, 'approval' => 12),
            array('date' => '10.03', 'click' => 550, 'db' => 35, 'approval' => 25),
            array('date' => '10.04', 'click' => 480, 'db' => 28, 'approval' => 20),
            array('date' => '10.05', 'click' => 600, 'db' => 42, 'approval' => 30),
            array('date' => '10.06', 'click' => 720, 'db' => 55, 'approval' => 41),
            array('date' => '10.07', 'click' => 850, 'db' => 60, 'approval' => 45),
        );
    }
}

if (!function_exists('lc_sample_partner_dbs')) {
    function lc_sample_partner_dbs()
    {
        return array(
            array('id' => 'DB241007-001', 'date' => '10.07 14:22', 'campaign' => '개인회생 상담 DB', 'name' => '김*성', 'phone' => '010-42**-12**', 'channel' => '네이버 블로그', 'status' => '검수중', 'price' => 30000, 'est_revenue' => 30000, 'conf_revenue' => 0, 'comment' => ''),
            array('id' => 'DB241007-002', 'date' => '10.07 13:15', 'campaign' => '어린이 영어캠프', 'name' => '이*희', 'phone' => '010-88**-56**', 'channel' => '인스타그램', 'status' => '승인완료', 'price' => 35000, 'est_revenue' => 35000, 'conf_revenue' => 35000, 'comment' => '상담 예약 완료'),
            array('id' => 'DB241007-003', 'date' => '10.07 11:40', 'campaign' => '자동차 렌트 상담', 'name' => '박*민', 'phone' => '010-21**-99**', 'channel' => '네이버 카페', 'status' => '접수완료', 'price' => 25000, 'est_revenue' => 25000, 'conf_revenue' => 0, 'comment' => ''),
            array('id' => 'DB241007-004', 'date' => '10.07 10:05', 'campaign' => '개인회생 상담 DB', 'name' => '최*훈', 'phone' => '010-55**-33**', 'channel' => '유튜브', 'status' => '취소/무효', 'price' => 30000, 'est_revenue' => 0, 'conf_revenue' => 0, 'comment' => '연락처 결번'),
            array('id' => 'DB241006-005', 'date' => '10.06 18:30', 'campaign' => '소상공인 대출 상담', 'name' => '정*수', 'phone' => '010-77**-11**', 'channel' => '네이버 블로그', 'status' => '정산완료', 'price' => 32000, 'est_revenue' => 32000, 'conf_revenue' => 32000, 'comment' => '', 'settled' => true),
        );
    }
}

if (!function_exists('lc_sample_partner_links_detail')) {
    function lc_sample_partner_links_detail()
    {
        return array(
            array('id' => 'l_001', 'campaign' => '개인회생 상담 DB', 'channel' => '네이버 블로그', 'sub_id' => 'blog_01', 'url' => 'https://linkconnect.co.kr/r/abc123', 'clicks' => 1248, 'received' => 32, 'approved' => 21, 'canceled' => 4, 'est_revenue' => 960000, 'conf_revenue' => 630000, 'status' => '운영중'),
            array('id' => 'l_002', 'campaign' => '자동차 장기렌트 특가', 'channel' => '인스타그램', 'sub_id' => 'insta_bio', 'url' => 'https://linkconnect.co.kr/r/xyz789', 'clicks' => 850, 'received' => 12, 'approved' => 8, 'canceled' => 2, 'est_revenue' => 300000, 'conf_revenue' => 200000, 'status' => '운영중'),
            array('id' => 'l_003', 'campaign' => '어린이 영어캠프 조기등록', 'channel' => '맘카페', 'sub_id' => 'cafe_event', 'url' => 'https://linkconnect.co.kr/r/qwe456', 'clicks' => 320, 'received' => 5, 'approved' => 2, 'canceled' => 0, 'est_revenue' => 175000, 'conf_revenue' => 70000, 'status' => '중지'),
            array('id' => 'l_004', 'campaign' => '소상공인 대출 지원센터', 'channel' => '유튜브', 'sub_id' => 'yt_desc', 'url' => 'https://linkconnect.co.kr/r/rty234', 'clicks' => 2100, 'received' => 45, 'approved' => 30, 'canceled' => 8, 'est_revenue' => 1440000, 'conf_revenue' => 960000, 'status' => '만료'),
        );
    }
}

if (!function_exists('lc_sample_partner_traffic_channels')) {
    function lc_sample_partner_traffic_channels()
    {
        return array(
            array('channel' => '네이버 블로그', 'clicks' => 4520, 'dbs' => 128, 'approved' => 89, 'revenue' => 2670000, 'cvr' => '2.8%'),
            array('channel' => '인스타그램', 'clicks' => 2180, 'dbs' => 54, 'approved' => 38, 'revenue' => 1140000, 'cvr' => '2.5%'),
            array('channel' => '유튜브', 'clicks' => 1890, 'dbs' => 42, 'approved' => 30, 'revenue' => 960000, 'cvr' => '2.2%'),
            array('channel' => '네이버 카페', 'clicks' => 980, 'dbs' => 28, 'approved' => 19, 'revenue' => 570000, 'cvr' => '2.9%'),
        );
    }
}

if (!function_exists('lc_sample_partner_traffic_subid')) {
    function lc_sample_partner_traffic_subid()
    {
        return array(
            array('sub_id' => 'blog_01', 'channel' => '네이버 블로그', 'clicks' => 2840, 'dbs' => 82, 'approved' => 58, 'revenue' => 1740000),
            array('sub_id' => 'insta_bio', 'channel' => '인스타그램', 'clicks' => 1650, 'dbs' => 38, 'approved' => 26, 'revenue' => 780000),
            array('sub_id' => 'yt_desc', 'channel' => '유튜브', 'clicks' => 1420, 'dbs' => 32, 'approved' => 22, 'revenue' => 660000),
            array('sub_id' => 'cafe_event', 'channel' => '맘카페', 'clicks' => 620, 'dbs' => 18, 'approved' => 11, 'revenue' => 385000),
        );
    }
}

if (!function_exists('lc_sample_partner_canceled')) {
    function lc_sample_partner_canceled()
    {
        return array(
            array('id' => 'DB241007-004', 'date' => '10.07 10:05', 'campaign' => '개인회생 상담 DB', 'name' => '최*훈', 'phone' => '010-55**-33**', 'reason' => '연락처 결번', 'comment' => '3회 이상 부재중, 연락 불가로 무효 처리', 'status' => '취소/무효', 'appeal' => true),
            array('id' => 'DB241006-007', 'date' => '10.06 11:10', 'campaign' => '개인회생 상담 DB', 'name' => '윤*철', 'phone' => '010-99**-88**', 'reason' => '중복 디비', 'comment' => '동일 연락처 7일 이내 중복 접수', 'status' => '취소/무효', 'appeal' => false),
            array('id' => 'DB241005-012', 'date' => '10.05 09:22', 'campaign' => '자동차 렌트 상담', 'name' => '한*우', 'phone' => '010-12**-34**', 'reason' => '허위 신청', 'comment' => '장난 신청으로 판단, 광고주 직접 취소', 'status' => '취소/무효', 'appeal' => true),
        );
    }
}

if (!function_exists('lc_sample_partner_earnings')) {
    function lc_sample_partner_earnings()
    {
        return array(
            array('label' => '이번 달 예상수익', 'value' => '9,120,000', 'suffix' => '원', 'icon' => '💹', 'tone' => 'default'),
            array('label' => '이번 달 확정수익', 'value' => '8,430,000', 'suffix' => '원', 'icon' => '✅', 'tone' => 'highlight'),
            array('label' => '취소/무효 수익', 'value' => '690,000', 'suffix' => '원', 'icon' => '✕', 'tone' => 'red'),
            array('label' => '정산 가능 금액', 'value' => '7,230,000', 'suffix' => '원', 'icon' => '💳', 'tone' => 'dark'),
        );
    }
}

if (!function_exists('lc_sample_partner_settlements')) {
    function lc_sample_partner_settlements()
    {
        return array(
            array('id' => 'ST-241001', 'date' => '2026.10.01 10:15', 'req_amount' => 3500000, 'app_amount' => 3500000, 'status' => '지급완료', 'pay_date' => '2026.10.05', 'memo' => '10월 정기 지급건'),
            array('id' => 'ST-240901', 'date' => '2026.09.01 11:20', 'req_amount' => 2800000, 'app_amount' => 2800000, 'status' => '지급완료', 'pay_date' => '2026.09.05', 'memo' => '9월 정기 지급건'),
            array('id' => 'ST-240801', 'date' => '2026.08.01 09:45', 'req_amount' => 420000, 'app_amount' => 0, 'status' => '반려', 'pay_date' => '-', 'memo' => '예금주명 불일치'),
            array('id' => 'ST-240715', 'date' => '2026.07.15 14:30', 'req_amount' => 420000, 'app_amount' => 420000, 'status' => '지급완료', 'pay_date' => '2026.07.20', 'memo' => '7월 중순 수시 지급'),
        );
    }
}

if (!function_exists('lc_sample_partner_inquiries')) {
    function lc_sample_partner_inquiries()
    {
        return array(
            array('id' => 'INQ-1042', 'title' => '정산 지급일 문의', 'date' => '2026.10.05', 'status' => '답변완료', 'category' => '정산'),
            array('id' => 'INQ-1038', 'title' => '취소 디비 이의신청', 'date' => '2026.10.03', 'status' => '답변대기', 'category' => '디비'),
            array('id' => 'INQ-1021', 'title' => '홍보 링크 생성 오류', 'date' => '2026.09.28', 'status' => '답변완료', 'category' => '기술'),
        );
    }
}

if (!function_exists('lc_sample_partner_faq')) {
    function lc_sample_partner_faq()
    {
        return array(
            array('q' => '정산은 언제 지급되나요?', 'a' => '정산 신청 후 영업일 기준 3~5일 내 지급됩니다. (샘플 안내)'),
            array('q' => '취소/무효 디비는 어떻게 확인하나요?', 'a' => '취소/무효 디비 메뉴에서 사유와 광고주 코멘트를 확인할 수 있습니다.'),
            array('q' => '홍보 링크는 몇 개까지 만들 수 있나요?', 'a' => '캠페인·채널·sub_id 조합별로 제한 없이 생성 가능합니다. (샘플)'),
        );
    }
}

/* ── 광고주센터 샘플 ── */

if (!function_exists('lc_sample_merchant_chart_7d')) {
    function lc_sample_merchant_chart_7d()
    {
        return array(
            array('date' => '10.01', 'db' => 24, 'approval' => 18, 'cancel' => 6),
            array('date' => '10.02', 'db' => 18, 'approval' => 12, 'cancel' => 6),
            array('date' => '10.03', 'db' => 35, 'approval' => 25, 'cancel' => 10),
            array('date' => '10.04', 'db' => 28, 'approval' => 20, 'cancel' => 8),
            array('date' => '10.05', 'db' => 42, 'approval' => 30, 'cancel' => 12),
            array('date' => '10.06', 'db' => 55, 'approval' => 41, 'cancel' => 14),
            array('date' => '10.07', 'db' => 60, 'approval' => 45, 'cancel' => 15),
        );
    }
}

if (!function_exists('lc_sample_merchant_dbs')) {
    function lc_sample_merchant_dbs()
    {
        return array(
            array(
                'id' => 'DB241007-001', 'date' => '10.07 14:22', 'campaign' => '개인회생 상담 DB', 'name' => '홍길동', 'phone' => '010-1234-5678',
                'email' => 'hong@example.com', 'region' => '서울', 'inquiry' => '개인회생 상담 원합니다', 'partner' => 'PTN-8291', 'status' => '신규접수',
                'price' => 50000, 'comment' => '', 'needs_action' => true, 'channel' => '네이버 블로그', 'sub_id' => 'event_01',
                'landing' => 'https://linkconnect.co.kr/c/1234', 'referer' => 'https://blog.naver.com/...',
                'history' => array(
                    array('time' => '2026.10.07 14:22', 'text' => '신규 디비 접수'),
                    array('time' => '2026.10.07 14:23', 'text' => '광고주센터 전달'),
                ),
            ),
            array(
                'id' => 'DB241007-002', 'date' => '10.07 13:15', 'campaign' => '어린이 영어캠프', 'name' => '이소희', 'phone' => '010-8812-5644',
                'email' => 'sohee@example.com', 'region' => '경기', 'inquiry' => '무료체험 신청', 'partner' => 'PTN-1022', 'status' => '승인완료',
                'price' => 35000, 'comment' => '상담 예약 완료', 'needs_action' => false, 'channel' => '인스타그램', 'sub_id' => '',
                'landing' => 'https://linkconnect.co.kr/c/9922', 'referer' => 'https://instagram.com/...',
                'history' => array(
                    array('time' => '2026.10.07 13:15', 'text' => '신규 디비 접수'),
                    array('time' => '2026.10.07 14:10', 'text' => '승인완료'),
                ),
            ),
            array(
                'id' => 'DB241007-003', 'date' => '10.07 11:40', 'campaign' => '개인회생 상담 DB', 'name' => '박재민', 'phone' => '010-2199-9922',
                'email' => 'jm@example.com', 'region' => '부산', 'inquiry' => '채무상담', 'partner' => 'PTN-8291', 'status' => '확인중',
                'price' => 50000, 'comment' => '', 'needs_action' => true, 'channel' => '카카오톡', 'sub_id' => 'kakao_01',
                'landing' => 'https://linkconnect.co.kr/c/1234', 'referer' => 'https://kakao.com/...',
                'history' => array(
                    array('time' => '2026.10.07 11:40', 'text' => '신규 디비 접수'),
                    array('time' => '2026.10.07 12:00', 'text' => '확인중 처리'),
                ),
            ),
            array(
                'id' => 'DB241007-004', 'date' => '10.07 10:05', 'campaign' => '자동차 렌트 상담', 'name' => '최지훈', 'phone' => '010-5511-3377',
                'email' => 'hoon@example.com', 'region' => '대구', 'inquiry' => 'G80 렌트', 'partner' => 'PTN-3011', 'status' => '취소/무효',
                'price' => 0, 'comment' => '연락처 결번', 'needs_action' => false, 'channel' => '구글 검색', 'sub_id' => '',
                'landing' => 'https://linkconnect.co.kr/c/5533', 'referer' => 'https://google.com/...',
                'history' => array(
                    array('time' => '2026.10.07 10:05', 'text' => '신규 디비 접수'),
                    array('time' => '2026.10.07 11:00', 'text' => '취소/무효 승인'),
                ),
            ),
            array(
                'id' => 'DB241006-005', 'date' => '10.06 18:30', 'campaign' => '소상공인 대출 상담', 'name' => '정민수', 'phone' => '010-7788-1122',
                'email' => 'min@example.com', 'region' => '인천', 'inquiry' => '정부지원 대출', 'partner' => 'PTN-5044', 'status' => '취소요청',
                'price' => 25000, 'comment' => '조건 불일치', 'needs_action' => true, 'channel' => '네이버 검색', 'sub_id' => '',
                'landing' => 'https://linkconnect.co.kr/c/7744', 'referer' => 'https://search.naver.com/...',
                'history' => array(
                    array('time' => '2026.10.06 18:30', 'text' => '신규 디비 접수'),
                    array('time' => '2026.10.07 09:30', 'text' => '취소요청'),
                ),
            ),
        );
    }
}

if (!function_exists('lc_sample_merchant_campaigns')) {
    function lc_sample_merchant_campaigns()
    {
        return array(
            array('title' => '개인회생 상담 DB', 'category' => '법률', 'price' => 50000, 'today_dbs' => 8, 'approval_rate' => '72%', 'month_spend' => 1250000, 'status' => '진행중'),
            array('title' => '어린이 영어캠프', 'category' => '교육', 'price' => 35000, 'today_dbs' => 3, 'approval_rate' => '68%', 'month_spend' => 420000, 'status' => '진행중'),
            array('title' => '자동차 렌트 상담', 'category' => '렌탈', 'price' => 40000, 'today_dbs' => 2, 'approval_rate' => '61%', 'month_spend' => 680000, 'status' => '진행중'),
            array('title' => '소상공인 대출 상담', 'category' => '금융', 'price' => 45000, 'today_dbs' => 4, 'approval_rate' => '58%', 'month_spend' => 350000, 'status' => '일시중지'),
        );
    }
}

if (!function_exists('lc_sample_merchant_wallet_history')) {
    function lc_sample_merchant_wallet_history()
    {
        return array(
            array('date' => '2026.10.07 09:12', 'type' => '차감', 'amount' => -50000, 'balance' => 2350000, 'memo' => 'DB241007-002 승인 차감'),
            array('date' => '2026.10.06 15:30', 'type' => '차감', 'amount' => -50000, 'balance' => 2400000, 'memo' => 'DB241006-008 승인 차감'),
            array('date' => '2026.10.05 11:00', 'type' => '충전', 'amount' => 1000000, 'balance' => 2450000, 'memo' => '광고비 충전 (신한은행)'),
            array('date' => '2026.10.03 14:22', 'type' => '환급', 'amount' => 50000, 'balance' => 1450000, 'memo' => '취소 디비 환급 DB241003-011'),
            array('date' => '2026.10.01 10:00', 'type' => '충전', 'amount' => 2000000, 'balance' => 1400000, 'memo' => '10월 정기 충전'),
        );
    }
}

if (!function_exists('lc_sample_merchant_report_campaigns')) {
    function lc_sample_merchant_report_campaigns()
    {
        return array(
            array('campaign' => '개인회생 상담 DB', 'received' => 128, 'approved' => 92, 'canceled' => 18, 'approval_rate' => '72%', 'spend' => 4600000),
            array('campaign' => '어린이 영어캠프', 'received' => 45, 'approved' => 31, 'canceled' => 8, 'approval_rate' => '69%', 'spend' => 1085000),
            array('campaign' => '자동차 렌트 상담', 'received' => 38, 'approved' => 23, 'canceled' => 9, 'approval_rate' => '61%', 'spend' => 920000),
        );
    }
}

if (!function_exists('lc_sample_merchant_report_partners')) {
    function lc_sample_merchant_report_partners()
    {
        return array(
            array('partner' => 'PTN-8291', 'received' => 82, 'approved' => 58, 'canceled' => 12, 'approval_rate' => '71%', 'spend' => 2900000),
            array('partner' => 'PTN-1022', 'received' => 34, 'approved' => 24, 'canceled' => 5, 'approval_rate' => '71%', 'spend' => 840000),
            array('partner' => 'PTN-3011', 'received' => 28, 'approved' => 16, 'canceled' => 7, 'approval_rate' => '57%', 'spend' => 640000),
        );
    }
}

if (!function_exists('lc_sample_merchant_inquiries')) {
    function lc_sample_merchant_inquiries()
    {
        return array(
            array('id' => 'MINQ-2201', 'title' => '광고비 충전 입금 확인 요청', 'date' => '2026.10.06', 'status' => '답변완료', 'category' => '광고비'),
            array('id' => 'MINQ-2198', 'title' => '디비 승인 기준 문의', 'date' => '2026.10.04', 'status' => '답변대기', 'category' => '디비'),
            array('id' => 'MINQ-2185', 'title' => '신규 CPA 상품 등록', 'date' => '2026.09.30', 'status' => '답변완료', 'category' => '상품'),
        );
    }
}

if (!function_exists('lc_sample_merchant_faq')) {
    function lc_sample_merchant_faq()
    {
        return array(
            array('q' => '디비 승인 시 광고비는 언제 차감되나요?', 'a' => '승인 처리 즉시 가차감 후 확정 차감됩니다. (샘플 안내)'),
            array('q' => '취소/무효 처리하면 광고비가 환급되나요?', 'a' => '취소 승인 후 환급 내역에 반영됩니다.'),
            array('q' => '광고비 충전은 어떻게 하나요?', 'a' => '광고비 충전/내역 메뉴에서 충전 신청 후 입금 확인을 요청하세요.'),
        );
    }
}

/* ── 관리자센터 샘플 데이터 ── */

if (!function_exists('lc_sample_admin_dashboard_stats')) {
    function lc_sample_admin_dashboard_stats()
    {
        return array(
            array('label' => '오늘 접수 DB', 'value' => '248', 'suffix' => '건', 'icon' => '📥'),
            array('label' => '오늘 승인 DB', 'value' => '173', 'suffix' => '건', 'icon' => '✅', 'tone' => 'highlight', 'color' => 'emerald'),
            array('label' => '오늘 취소 DB', 'value' => '42', 'suffix' => '건', 'icon' => '⚑', 'tone' => 'highlight', 'color' => 'red'),
            array('label' => '오늘 승인율', 'value' => '69.7', 'suffix' => '%', 'icon' => '📊'),
            array('label' => '오늘 매출', 'value' => '8,650,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight', 'color' => 'cyan'),
            array('label' => '파트너 수익', 'value' => '5,190,000', 'suffix' => '원', 'icon' => '👥'),
            array('label' => '관리자 마진', 'value' => '3,460,000', 'suffix' => '원', 'icon' => '◆', 'tone' => 'highlight', 'color' => 'blue'),
            array('label' => '정산 대기', 'value' => '12,800,000', 'suffix' => '원', 'icon' => '💳', 'tone' => 'dark'),
        );
    }
}

if (!function_exists('lc_sample_admin_chart_7d')) {
    function lc_sample_admin_chart_7d()
    {
        return array(
            array('date' => '07.01', 'received' => 210, 'approved' => 145, 'canceled' => 32, 'revenue' => 725),
            array('date' => '07.02', 'received' => 235, 'approved' => 160, 'canceled' => 35, 'revenue' => 800),
            array('date' => '07.03', 'received' => 198, 'approved' => 135, 'canceled' => 28, 'revenue' => 675),
            array('date' => '07.04', 'received' => 250, 'approved' => 172, 'canceled' => 40, 'revenue' => 860),
            array('date' => '07.05', 'received' => 220, 'approved' => 155, 'canceled' => 36, 'revenue' => 775),
            array('date' => '07.06', 'received' => 275, 'approved' => 190, 'canceled' => 45, 'revenue' => 950),
            array('date' => '07.07', 'received' => 248, 'approved' => 173, 'canceled' => 42, 'revenue' => 865),
        );
    }
}

if (!function_exists('lc_sample_admin_alerts')) {
    function lc_sample_admin_alerts()
    {
        return array(
            array('tone' => 'orange', 'icon' => '⚑', 'label' => '취소/무효 검수 대기', 'count' => '18건', 'url' => lc_url('admin/cancel_review.php')),
            array('tone' => 'emerald', 'icon' => '💳', 'label' => '정산 승인 대기', 'count' => '7건', 'url' => lc_url('admin/settlements.php')),
            array('tone' => 'red', 'icon' => '🏢', 'label' => '광고비 부족 광고주', 'count' => '3곳', 'url' => lc_url('admin/wallet.php')),
            array('tone' => 'rose', 'icon' => '{ }', 'label' => 'API 오류', 'count' => '2건', 'url' => lc_url('admin/api.php')),
            array('tone' => 'yellow', 'icon' => '⏱', 'label' => '승인 지연 디비', 'count' => '24건', 'url' => lc_url('admin/conversions.php')),
        );
    }
}

if (!function_exists('lc_sample_admin_campaign_perf')) {
    function lc_sample_admin_campaign_perf()
    {
        return array(
            array('name' => '개인회생 무료상담 지원센터', 'merchant' => '희망법무법인', 'received' => 1250, 'approved' => 850, 'canceled' => 150, 'rate' => '68.0%', 'revenue' => '42,500,000', 'partner_profit' => '25,500,000', 'margin' => '17,000,000', 'status' => '정상'),
            array('name' => '직장인 신용대출 한도조회', 'merchant' => '(주)성공대부', 'received' => 980, 'approved' => 720, 'canceled' => 110, 'rate' => '73.4%', 'revenue' => '21,600,000', 'partner_profit' => '14,400,000', 'margin' => '7,200,000', 'status' => '정상'),
            array('name' => '자동차 장기렌트 특가', 'merchant' => '스피드렌터카', 'received' => 650, 'approved' => 410, 'canceled' => 120, 'rate' => '63.0%', 'revenue' => '16,400,000', 'partner_profit' => '8,200,000', 'margin' => '8,200,000', 'status' => '광고비부족'),
            array('name' => '어린이 화상영어 1주 체험', 'merchant' => '키즈잉글리시', 'received' => 420, 'approved' => 350, 'canceled' => 30, 'rate' => '83.3%', 'revenue' => '10,500,000', 'partner_profit' => '6,300,000', 'margin' => '4,200,000', 'status' => '정상'),
        );
    }
}

if (!function_exists('lc_sample_admin_partners')) {
    function lc_sample_admin_partners()
    {
        return array(
            array('code' => 'PT-8832', 'name' => '김파트너', 'received' => 450, 'approved' => 320, 'rate' => '71.1%', 'profit' => '12,500,000', 'pending' => '1,200,000', 'status' => '운영중'),
            array('code' => 'PT-1029', 'name' => '블로그마스터', 'received' => 380, 'approved' => 290, 'rate' => '76.3%', 'profit' => '9,800,000', 'pending' => '850,000', 'status' => '운영중'),
            array('code' => 'PT-5591', 'name' => '유튜브크리에이터', 'received' => 410, 'approved' => 260, 'rate' => '63.4%', 'profit' => '8,500,000', 'pending' => '2,100,000', 'status' => '일시중지'),
            array('code' => 'PT-2248', 'name' => '카페운영자', 'received' => 290, 'approved' => 210, 'rate' => '72.4%', 'profit' => '6,200,000', 'pending' => '0', 'status' => '운영중'),
            array('code' => 'PT-7731', 'name' => '인플루언서A', 'received' => 250, 'approved' => 180, 'rate' => '72.0%', 'profit' => '5,400,000', 'pending' => '430,000', 'status' => '가입대기'),
        );
    }
}

if (!function_exists('lc_sample_admin_merchants')) {
    function lc_sample_admin_merchants()
    {
        return array(
            array('code' => 'AD-1001', 'name' => '희망법무법인', 'balance' => '14,500,000', 'spent' => '31,000,000', 'rate' => '72.9%', 'cancel_rate' => '17.6%', 'status' => '운영중'),
            array('code' => 'AD-1002', 'name' => '(주)성공대부', 'balance' => '2,500,000', 'spent' => '15,300,000', 'rate' => '70.8%', 'cancel_rate' => '15.3%', 'status' => '운영중'),
            array('code' => 'AD-1003', 'name' => '스피드렌터카', 'balance' => '120,000', 'spent' => '15,600,000', 'rate' => '67.2%', 'cancel_rate' => '20.7%', 'status' => '광고비부족'),
            array('code' => 'AD-1004', 'name' => '라이프보험법인', 'balance' => '8,500,000', 'spent' => '12,400,000', 'rate' => '68.9%', 'cancel_rate' => '18.2%', 'status' => '운영중'),
            array('code' => 'AD-1005', 'name' => '키즈잉글리시', 'balance' => '4,200,000', 'spent' => '8,400,000', 'rate' => '83.3%', 'cancel_rate' => '7.1%', 'status' => '운영중'),
        );
    }
}

if (!function_exists('lc_sample_admin_campaigns')) {
    function lc_sample_admin_campaigns()
    {
        return array(
            array('id' => 'CPA-2401', 'name' => '개인회생 무료상담 지원센터', 'merchant' => '희망법무법인', 'partner_price' => '30,000', 'merchant_price' => '50,000', 'margin' => '20,000', 'rate' => '68.0%', 'status' => '운영중'),
            array('id' => 'CPA-2402', 'name' => '직장인 신용대출 한도조회', 'merchant' => '(주)성공대부', 'partner_price' => '20,000', 'merchant_price' => '30,000', 'margin' => '10,000', 'rate' => '73.4%', 'status' => '운영중'),
            array('id' => 'CPA-2403', 'name' => '자동차 장기렌트 특가', 'merchant' => '스피드렌터카', 'partner_price' => '20,000', 'merchant_price' => '40,000', 'margin' => '20,000', 'rate' => '63.0%', 'status' => '광고비부족'),
            array('id' => 'CPA-2404', 'name' => '어린이 화상영어 1주 체험', 'merchant' => '키즈잉글리시', 'partner_price' => '18,000', 'merchant_price' => '30,000', 'margin' => '12,000', 'rate' => '83.3%', 'status' => '운영중'),
        );
    }
}

if (!function_exists('lc_sample_admin_conversions')) {
    function lc_sample_admin_conversions()
    {
        return array(
            array('id' => 'DB-240801', 'date' => '07.07 14:22', 'customer' => '이*성', 'phone' => '010-****-4521', 'partner' => 'PT-8832', 'merchant' => '희망법무법인', 'campaign' => '개인회생 상담', 'status' => '신규접수', 'partner_profit' => '30,000', 'merchant_cost' => '50,000', 'margin' => '20,000', 'api' => '정상'),
            array('id' => 'DB-240802', 'date' => '07.07 14:18', 'customer' => '박*민', 'phone' => '010-****-8892', 'partner' => 'PT-1029', 'merchant' => '(주)성공대부', 'campaign' => '신용대출 조회', 'status' => '확인중', 'partner_profit' => '20,000', 'merchant_cost' => '30,000', 'margin' => '10,000', 'api' => '정상'),
            array('id' => 'DB-240803', 'date' => '07.07 14:15', 'customer' => '최*훈', 'phone' => '010-****-3310', 'partner' => 'PT-5591', 'merchant' => '스피드렌터카', 'campaign' => '장기렌트 특가', 'status' => '취소요청', 'partner_profit' => '—', 'merchant_cost' => '—', 'margin' => '—', 'api' => '지연'),
            array('id' => 'DB-240804', 'date' => '07.07 14:10', 'customer' => '김*수', 'phone' => '010-****-7721', 'partner' => 'PT-8832', 'merchant' => '희망법무법인', 'campaign' => '개인회생 상담', 'status' => '승인완료', 'partner_profit' => '30,000', 'merchant_cost' => '50,000', 'margin' => '20,000', 'api' => '정상'),
            array('id' => 'DB-240805', 'date' => '07.07 13:55', 'customer' => '정*아', 'phone' => '010-****-1190', 'partner' => 'PT-2248', 'merchant' => '키즈잉글리시', 'campaign' => '화상영어 체험', 'status' => '승인완료', 'partner_profit' => '18,000', 'merchant_cost' => '30,000', 'margin' => '12,000', 'api' => 'API오류'),
        );
    }
}

if (!function_exists('lc_sample_admin_cancel_reviews')) {
    function lc_sample_admin_cancel_reviews()
    {
        return array(
            array('id' => 'DB-240803', 'date' => '07.07 14:15', 'campaign' => '장기렌트 특가', 'merchant' => '스피드렌터카', 'partner' => 'PT-5591', 'reason' => '결번/통화불가', 'merchant_comment' => '3회 통화 시도 실패', 'partner_appeal' => '고객 연락 두절로 취소 요청', 'status' => '검수대기'),
            array('id' => 'DB-240710', 'date' => '07.07 13:20', 'campaign' => '신용대출 조회', 'merchant' => '(주)성공대부', 'partner' => 'PT-1029', 'reason' => '단순변심', 'merchant_comment' => '상담 전 취소', 'partner_appeal' => '—', 'status' => '검수대기'),
            array('id' => 'DB-240698', 'date' => '07.07 12:15', 'campaign' => '화상영어 체험', 'merchant' => '키즈잉글리시', 'partner' => 'PT-2248', 'reason' => '중복신청', 'merchant_comment' => '기존 DB와 중복', 'partner_appeal' => '—', 'status' => '검수완료'),
            array('id' => 'DB-240685', 'date' => '07.07 11:30', 'campaign' => '개인회생 상담', 'merchant' => '희망법무법인', 'partner' => 'PT-8832', 'reason' => '조건미달(나이)', 'merchant_comment' => '만 19세 미만', 'partner_appeal' => '광고 랜딩에 연령 안내 부족', 'status' => '검수반려'),
        );
    }
}

if (!function_exists('lc_sample_admin_wallet_summary')) {
    function lc_sample_admin_wallet_summary()
    {
        return array(
            array('label' => '전체 광고비 잔액', 'value' => '29,820,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'highlight', 'color' => 'cyan'),
            array('label' => '충전 승인 대기', 'value' => '5', 'suffix' => '건', 'icon' => '⏳', 'color' => 'blue'),
            array('label' => '광고비 부족', 'value' => '3', 'suffix' => '곳', 'icon' => '⚠', 'tone' => 'highlight', 'color' => 'red'),
            array('label' => '오늘 충전액', 'value' => '3,500,000', 'suffix' => '원', 'icon' => '➕', 'color' => 'emerald'),
        );
    }
}

if (!function_exists('lc_sample_admin_wallet_merchants')) {
    function lc_sample_admin_wallet_merchants()
    {
        return array(
            array('name' => '희망법무법인', 'balance' => '14,500,000', 'pending_charge' => '0', 'status' => '정상'),
            array('name' => '스피드렌터카', 'balance' => '120,000', 'pending_charge' => '0', 'status' => '광고비부족'),
            array('name' => '(주)성공대부', 'balance' => '2,500,000', 'pending_charge' => '3,000,000', 'status' => '충전대기'),
            array('name' => '키즈잉글리시', 'balance' => '4,200,000', 'pending_charge' => '0', 'status' => '정상'),
        );
    }
}

if (!function_exists('lc_sample_admin_wallet_history')) {
    function lc_sample_admin_wallet_history()
    {
        return array(
            array('date' => '07.07 10:22', 'merchant' => '(주)성공대부', 'type' => '충전신청', 'amount' => '+3,000,000', 'status' => '승인대기'),
            array('date' => '07.06 16:40', 'merchant' => '희망법무법인', 'type' => '수동충전', 'amount' => '+5,000,000', 'status' => '충전완료'),
            array('date' => '07.06 14:12', 'merchant' => '스피드렌터카', 'type' => '광고비차감', 'amount' => '-40,000', 'status' => '차감완료'),
            array('date' => '07.05 11:05', 'merchant' => '키즈잉글리시', 'type' => '환급', 'amount' => '+30,000', 'status' => '환급완료'),
        );
    }
}

if (!function_exists('lc_sample_admin_settlements')) {
    function lc_sample_admin_settlements()
    {
        return array(
            array('id' => 'STL-24071', 'partner' => 'PT-8832 김파트너', 'period' => '2026.06', 'amount' => '8,430,000', 'fee' => '0', 'net' => '8,430,000', 'status' => '승인대기', 'date' => '07.07 09:12'),
            array('id' => 'STL-24072', 'partner' => 'PT-1029 블로그마스터', 'period' => '2026.06', 'amount' => '3,120,000', 'fee' => '0', 'net' => '3,120,000', 'status' => '보류', 'date' => '07.06 18:44'),
            array('id' => 'STL-24068', 'partner' => 'PT-5591 유튜브크리에이터', 'period' => '2026.05', 'amount' => '5,800,000', 'fee' => '0', 'net' => '5,800,000', 'status' => '지급완료', 'date' => '06.10 14:00'),
            array('id' => 'STL-24065', 'partner' => 'PT-2248 카페운영자', 'period' => '2026.05', 'amount' => '2,100,000', 'fee' => '0', 'net' => '2,100,000', 'status' => '반려', 'date' => '06.08 11:30'),
        );
    }
}

if (!function_exists('lc_sample_admin_api_clients')) {
    function lc_sample_admin_api_clients()
    {
        return array(
            array('name' => '희망법무법인 CRM', 'type' => '디비쉐어', 'endpoint' => 'https://api.hope-law.co.kr/lc/webhook', 'status' => '정상', 'last' => '07.07 14:22', 'success_rate' => '99.2%'),
            array('name' => '스피드렌터카 ERP', 'type' => '디비쉐어', 'endpoint' => 'https://erp.speedrent.co.kr/hooks/lc', 'status' => 'API오류', 'last' => '07.07 13:10', 'success_rate' => '87.4%'),
            array('name' => '(주)성공대부 Lead API', 'type' => 'REST', 'endpoint' => 'https://api.success-loan.kr/v1/leads', 'status' => '정상', 'last' => '07.07 14:18', 'success_rate' => '98.8%'),
        );
    }
}

if (!function_exists('lc_sample_admin_api_logs')) {
    function lc_sample_admin_api_logs()
    {
        return array(
            array('time' => '14:25:33', 'client' => '희망법무법인 CRM', 'db_id' => 'DB-240805', 'code' => 'ERR_TIMEOUT', 'msg' => '응답시간 초과 (5000ms)', 'status' => '실패'),
            array('time' => '13:10:12', 'client' => '스피드렌터카 ERP', 'db_id' => 'DB-240803', 'code' => 'ERR_AUTH', 'msg' => '유효하지 않은 API 토큰', 'status' => '실패'),
            array('time' => '14:22:01', 'client' => '희망법무법인 CRM', 'db_id' => 'DB-240801', 'code' => '200 OK', 'msg' => '전송 성공', 'status' => '성공'),
            array('time' => '14:18:44', 'client' => '(주)성공대부 Lead API', 'db_id' => 'DB-240802', 'code' => '200 OK', 'msg' => '전송 성공', 'status' => '성공'),
        );
    }
}

if (!function_exists('lc_sample_admin_events_mgmt')) {
    function lc_sample_admin_events_mgmt()
    {
        return lc_sample_admin_events_list();
    }
}

if (!function_exists('lc_sample_admin_events_list')) {
    function lc_sample_admin_events_list()
    {
        return array(
            array(
                'code' => 'EVT-301', 'title' => '7월 신규 파트너 웰컴 보너스', 'type' => '파트너 보너스',
                'campaigns' => '개인회생 상담 DB, 신용대출 조회 CPA 외 2개', 'period' => '2026.07.01 ~ 07.31',
                'partners' => 842, 'received' => 1280, 'approved' => 890, 'reward_pending' => '4,210,000',
                'status' => '진행중',
            ),
            array(
                'code' => 'EVT-298', 'title' => '개인회생 단가 상승 (10월)', 'type' => '단가 상승',
                'campaigns' => '개인회생 상담 DB', 'period' => '2026.10.01 ~ 10.31',
                'partners' => 1205, 'received' => 2450, 'approved' => 1680, 'reward_pending' => '16,800,000',
                'status' => '마감임박',
            ),
            array(
                'code' => 'EVT-295', 'title' => '10월 파트너 리워드 랭킹', 'type' => '랭킹 이벤트',
                'campaigns' => '전체 CPA 상품', 'period' => '2026.10.01 ~ 10.31',
                'partners' => 980, 'received' => 5120, 'approved' => 3540, 'reward_pending' => '8,500,000',
                'status' => '진행중',
            ),
            array(
                'code' => 'EVT-292', 'title' => '영어캠프 신규 캠페인 런칭', 'type' => '신규 캠페인',
                'campaigns' => '세부 영어캠프 상담 DB', 'period' => '2026.09.15 ~ 12.31',
                'partners' => 456, 'received' => 620, 'approved' => 465, 'reward_pending' => '—',
                'status' => '진행중',
            ),
            array(
                'code' => 'EVT-288', 'title' => '희망법무법인 광고주 프로모션', 'type' => '광고주 프로모션',
                'campaigns' => '개인회생 상담 DB, 채무조정 안내 CPA', 'period' => '2026.08.01 ~ 08.31',
                'partners' => 312, 'received' => 890, 'approved' => 612, 'reward_pending' => '—',
                'status' => '종료',
            ),
            array(
                'code' => 'EVT-285', 'title' => '광고비 500만 충전 보너스', 'type' => '광고비 충전 보너스',
                'campaigns' => '광고주 전용 (전 상품)', 'period' => '2026.07.01 ~ 07.15',
                'partners' => 0, 'received' => 0, 'approved' => 0, 'reward_pending' => '750,000',
                'status' => '예정',
            ),
        );
    }
}

if (!function_exists('lc_sample_admin_event_partners')) {
    function lc_sample_admin_event_partners()
    {
        return array(
            array('partner' => 'PT-8832 김파트너', 'event' => '7월 웰컴 보너스', 'joined' => '07.01', 'received' => 28, 'approved' => 19, 'progress' => '63%', 'reward' => '50,000', 'status' => '달성'),
            array('partner' => 'PT-1029 블로그마스터', 'event' => '단가 상승 (10월)', 'joined' => '10.01', 'received' => 45, 'approved' => 32, 'progress' => '80%', 'reward' => '320,000', 'status' => '진행중'),
            array('partner' => 'PT-5591 유튜브크리에이터', 'event' => '리워드 랭킹', 'joined' => '10.01', 'received' => 38, 'approved' => 26, 'progress' => '18위', 'reward' => '—', 'status' => '진행중'),
            array('partner' => 'PT-2248 카페운영자', 'event' => '영어캠프 런칭', 'joined' => '09.15', 'received' => 12, 'approved' => 9, 'progress' => '75%', 'reward' => '—', 'status' => '진행중'),
        );
    }
}

if (!function_exists('lc_sample_admin_event_performance')) {
    function lc_sample_admin_event_performance()
    {
        return array(
            array('event' => '개인회생 단가 상승', 'campaign' => '개인회생 상담 DB', 'received' => 1250, 'approved' => 850, 'rate' => '68.0%', 'revenue' => '42,500,000', 'bonus_paid' => '8,500,000', 'margin' => '17,000,000'),
            array('event' => '7월 웰컴 보너스', 'campaign' => '신용대출 조회 CPA', 'received' => 380, 'approved' => 290, 'rate' => '76.3%', 'revenue' => '8,700,000', 'bonus_paid' => '1,450,000', 'margin' => '2,900,000'),
            array('event' => '리워드 랭킹', 'campaign' => '전체 CPA', 'received' => 5120, 'approved' => 3540, 'rate' => '69.1%', 'revenue' => '106,200,000', 'bonus_paid' => '3,200,000', 'margin' => '35,400,000'),
        );
    }
}

if (!function_exists('lc_sample_admin_event_rewards')) {
    function lc_sample_admin_event_rewards()
    {
        return array(
            array('id' => 'RWD-4401', 'partner' => 'PT-8832 김파트너', 'event' => '7월 웰컴 보너스', 'amount' => '50,000', 'condition' => '첫 승인 1건 달성', 'status' => '지급대기', 'date' => '07.07'),
            array('id' => 'RWD-4398', 'partner' => 'PT-1029 블로그마스터', 'event' => '단가 상승 (10월)', 'amount' => '100,000', 'condition' => '승인 5건 달성', 'status' => '검수중', 'date' => '07.06'),
            array('id' => 'RWD-4385', 'partner' => 'PT-7731 인플루언서A', 'event' => '7월 웰컴 보너스', 'amount' => '50,000', 'condition' => '첫 승인 1건 달성', 'status' => '지급완료', 'date' => '07.03'),
            array('id' => 'RWD-4372', 'partner' => 'PT-5591 유튜브크리에이터', 'event' => '리워드 랭킹', 'amount' => '300,000', 'condition' => '9월 3위', 'status' => '반려', 'date' => '10.05'),
        );
    }
}

if (!function_exists('lc_sample_admin_event_campaign_options')) {
    function lc_sample_admin_event_campaign_options()
    {
        return array(
            '개인회생 상담 DB (CPA-2401)',
            '직장인 신용대출 한도조회 (CPA-2402)',
            '자동차 장기렌트 특가 (CPA-2403)',
            '어린이 화상영어 1주 체험 (CPA-2404)',
            '세부 영어캠프 상담 DB (CPA-2405)',
        );
    }
}

if (!function_exists('lc_sample_admin_inquiries')) {
    function lc_sample_admin_inquiries()
    {
        return array(
            array('id' => 'INQ-A101', 'center' => '파트너', 'from' => 'PT-8832 김파트너', 'title' => '6월 정산 금액 확인 요청', 'date' => '07.07 08:30', 'status' => '답변대기'),
            array('id' => 'INQ-A102', 'center' => '광고주', 'from' => '희망법무법인', 'title' => 'API 연동 오류 문의', 'date' => '07.06 16:12', 'status' => '처리중'),
            array('id' => 'INQ-A098', 'center' => '파트너', 'from' => 'PT-1029 블로그마스터', 'title' => '취소 반려 사유 문의', 'date' => '07.05 11:44', 'status' => '답변완료'),
            array('id' => 'INQ-A095', 'center' => '광고주', 'from' => '스피드렌터카', 'title' => '광고비 충전 승인 지연', 'date' => '07.04 09:20', 'status' => '답변완료'),
        );
    }
}

if (!function_exists('lc_sample_admin_settings')) {
    function lc_sample_admin_settings()
    {
        return array(
            array('id' => 'cpa', 'title' => 'CPA 운영 설정', 'desc' => '기본 승인 기한, 자동 보류 규칙', 'fields' => array(
                array('label' => '승인 처리 기한 (영업일)', 'value' => '3', 'type' => 'number'),
                array('label' => '자동 보류 (미처리 일수)', 'value' => '5', 'type' => 'number'),
            )),
            array('id' => 'wallet', 'title' => '광고비 정책', 'desc' => '충전·차감·환급 규칙', 'fields' => array(
                array('label' => '최소 충전 금액', 'value' => '500,000', 'type' => 'text'),
                array('label' => '광고비 부족 알림 임계값', 'value' => '200,000', 'type' => 'text'),
            )),
            array('id' => 'settlement', 'title' => '정산 정책', 'desc' => '정산 주기·수수료', 'fields' => array(
                array('label' => '정산 주기', 'value' => '월 2회 (15일, 말일)', 'type' => 'text'),
                array('label' => '최소 정산 금액', 'value' => '50,000', 'type' => 'text'),
            )),
            array('id' => 'cancel', 'title' => '취소/무효 정책', 'desc' => '검수 SLA·이의신청 기한', 'fields' => array(
                array('label' => '취소 검수 기한 (영업일)', 'value' => '2', 'type' => 'number'),
                array('label' => '파트너 이의신청 기한 (일)', 'value' => '7', 'type' => 'number'),
            )),
            array('id' => 'api', 'title' => 'API 보안 설정', 'desc' => '키 로테이션·IP 화이트리스트', 'fields' => array(
                array('label' => 'API 키 자동 만료 (일)', 'value' => '90', 'type' => 'number'),
                array('label' => 'IP 화이트리스트', 'value' => '활성화', 'type' => 'select'),
            )),
            array('id' => 'cps', 'title' => 'CPS 확장 준비', 'desc' => 'CPS 모듈 사전 설정 (비활성)', 'fields' => array(
                array('label' => 'CPS 모듈', 'value' => '준비중 (비활성)', 'type' => 'select'),
                array('label' => '트래킹 픽셀', 'value' => '미설정', 'type' => 'text'),
            )),
        );
    }
}

if (!function_exists('lc_sample_admin_recent_dbs')) {
    function lc_sample_admin_recent_dbs()
    {
        return array(
            array('date' => '07.07 14:22', 'campaign' => '개인회생 상담', 'partner' => 'PT-8832', 'merchant' => '희망법무', 'customer' => '이*성', 'status' => '신규접수'),
            array('date' => '07.07 14:18', 'campaign' => '신용대출 조회', 'partner' => 'PT-1029', 'merchant' => '성공대부', 'customer' => '박*민', 'status' => '확인중'),
            array('date' => '07.07 14:15', 'campaign' => '장기렌트 특가', 'partner' => 'PT-5591', 'merchant' => '스피드렌터', 'customer' => '최*훈', 'status' => '취소요청'),
            array('date' => '07.07 14:10', 'campaign' => '개인회생 상담', 'partner' => 'PT-8832', 'merchant' => '희망법무', 'customer' => '김*수', 'status' => '승인완료'),
        );
    }
}

if (!function_exists('lc_sample_admin_api_errors')) {
    function lc_sample_admin_api_errors()
    {
        return array(
            array('time' => '14:25:33', 'name' => '희망법무법인 CRM', 'code' => 'ERR_TIMEOUT', 'msg' => '응답시간 초과 (5000ms)'),
            array('time' => '13:10:12', 'name' => '스피드렌터카 ERP', 'code' => 'ERR_AUTH', 'msg' => '유효하지 않은 API 토큰'),
        );
    }
}
