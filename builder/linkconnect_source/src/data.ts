import { CPAItem } from './types';

export const cpaItems: CPAItem[] = [
  {
    id: '1',
    category: '법률',
    title: '개인회생 무료상담 CPA',
    description: '채무 조정이 필요한 고객 무료상담',
    condition: '상담 DB 승인 시',
    reward: 45000,
    approvalRate: '85%',
    badge: '고수익',
    imageUrl: 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '2',
    category: '병원',
    title: '피부클리닉 상담 CPA',
    description: '여드름/기미 등 피부과 이벤트 상담',
    condition: '상담신청 승인 시',
    reward: 18000,
    approvalRate: '92%',
    badge: '인기',
    imageUrl: 'https://images.unsplash.com/photo-1551076805-e18690c5e53b?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '3',
    category: '보험',
    title: '보험비교 상담 CPA',
    description: '내 보험료 무료 진단 및 비교 상담',
    condition: '상담 DB 승인 시',
    reward: 25000,
    approvalRate: '88%',
    badge: '승인율 높음',
    imageUrl: 'https://images.unsplash.com/photo-1450101499163-c8848c66cb85?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '4',
    category: '교육',
    title: '영어학원 상담 CPA',
    description: '성인 기초영어 수강 할인 이벤트',
    condition: '수강문의 승인 시',
    reward: 12000,
    approvalRate: '95%',
    badge: '신규',
    imageUrl: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '5',
    category: '렌탈',
    title: '렌탈 상담 CPA',
    description: '정수기/공기청정기/비데 종합 렌탈',
    condition: '상담신청 승인 시',
    reward: 20000,
    approvalRate: '90%',
    badge: '진행중',
    imageUrl: 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '6',
    category: '부동산',
    title: '부동산 분양상담 CPA',
    description: '수도권 신규 분양아파트 방문 예약',
    condition: '상담 DB 승인 시',
    reward: 35000,
    approvalRate: '80%',
    badge: '진행중',
    imageUrl: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '7',
    category: '건강',
    title: '다이어트 상담 CPA',
    description: '맞춤형 한방 다이어트 플랜 상담',
    condition: '상담신청 승인 시',
    reward: 15000,
    approvalRate: '91%',
    badge: '진행중',
    imageUrl: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=400&h=200'
  },
  {
    id: '8',
    category: '자동차',
    title: '자동차 상담 CPA',
    description: '신차 장기렌트/리스 최저가 견적',
    condition: '견적문의 승인 시',
    reward: 22000,
    approvalRate: '87%',
    badge: '진행중',
    imageUrl: 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400&h=200'
  }
];

export const categories = [
  '법률', '병원', '보험', '교육', '렌탈', 
  '부동산', '쇼핑몰', '건강식품', '뷰티', '생활서비스'
];

export const events = [
  { id: 1, title: '신규 파트너 첫 전환 보너스 이벤트', date: '2024.11.01 ~ 2024.11.30' },
  { id: 2, title: '이달의 고수익 CPA 상품 특별 기획전', date: '2024.11.15 ~ 2024.11.30' },
  { id: 3, title: '광고주 첫 충전 10% 추가 지급 프로모션', date: '2024.11.01 ~ 2024.12.31' },
];
