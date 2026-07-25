/**
 * 일러스트 제작 브리프 — 사장님과 홍보왕의 제휴마케팅 입문기
 * 총 15컷 · 세로형 웹툰 패널 (권장 720×960px, 3:4)
 *
 * 캐릭터 고정 설정:
 * - 김사장: 40대 남성, 짙은 정장, 갈색 머리, 쇼핑몰 사장
 * - 홍보왕 민지: 20대 여성, 민트/에메랄드 톤, 스마트폰·노트북
 * - 손님: 30대 성인, 캐주얼, 쇼핑백
 * - 링크커넥트: 인디고 로봇 마스코트, 가슴에 체인 링크 아이콘, 안테나
 */

export interface IllustrationBrief {
  id: string;
  episode: number;
  panel: number;
  title: string;
  composition: string;
  characters: string[];
  background: string;
  mood: string;
  colorPalette: string;
  notes: string;
  imagePath: string;
}

export const illustrationBriefs: IllustrationBrief[] = [
  {
    id: 'ep1-p1',
    episode: 1,
    panel: 1,
    title: '광고비 고민하는 김사장',
    composition: '김사장 중앙 전신, 뒤로 작은 쇼핑몰 간판. 손에 광고비 영수증 뭉치. 표정 걱정.',
    characters: ['김사장 (걱정, 땀방울)'],
    background: '한적한 동네 쇼핑몰 앞, 오후 햇살, 손님 없음',
    mood: '고민 · 공감',
    colorPalette: 'amber, orange, warm beige',
    notes: '말풍선 영역 상단 40% 비워둘 것. 텍스트 없이 그림만.',
    imagePath: 'webtoon/ep1-p1.png',
  },
  {
    id: 'ep1-p2',
    episode: 1,
    panel: 2,
    title: '링크커넥트 등장',
    composition: '링크커넥트 로봇 마스코트 중앙, 반짝이 효과. 친근한 포즈, 한 손 들어 인사.',
    characters: ['링크커넥트 (밝은 표정, 안테나 반짝)'],
    background: '부드러운 인디고-보라 그라데이션, 별/스파클',
    mood: '희망 · 안내',
    colorPalette: 'indigo, violet, cyan sparkle',
    notes: '브랜드 마스코트 느낌. SF지만 친근하게.',
    imagePath: 'webtoon/ep1-p2.png',
  },
  {
    id: 'ep1-p3',
    episode: 1,
    panel: 3,
    title: '제휴 마케팅 3자 구조',
    composition: '좌: 김사장, 우: 민지(폰), 하단 중앙: 손님. 화살표로 소개→성과→보상 흐름.',
    characters: ['김사장', '홍보왕 민지', '손님'],
    background: '밝은 에메랄드 톤, 연결선/화살표 장식',
    mood: '설명 · 연결',
    colorPalette: 'emerald, teal, white',
    notes: '3자 관계가 한눈에 보이게. 화살표는 그림 요소로만.',
    imagePath: 'webtoon/ep1-p3.png',
  },
  {
    id: 'ep2-p1',
    episode: 2,
    panel: 1,
    title: 'CPA 블로그 홍보',
    composition: '민지가 노트북/폰으로 블로그 글 작성. 화면에 "무료 상담" 버튼 느낌(영문/아이콘만).',
    characters: ['홍보왕 민지 (집중, 살짝 미소)'],
    background: '방/카페, 노트북, 에메랄드 포인트',
    mood: '시작 · 기대',
    colorPalette: 'emerald, green, cream',
    notes: 'CPA는 행동(신청) 강조. 링크 아이콘 작게.',
    imagePath: 'webtoon/ep2-p1.png',
  },
  {
    id: 'ep2-p2',
    episode: 2,
    panel: 2,
    title: '손님 상담 신청',
    composition: '손님이 스마트폰으로 신청 폼 작성. 만족 표정. 옆에 작은 민지 실루엣.',
    characters: ['손님 (기쁨)', '홍보왕 민지 (작게)'],
    background: '하늘색, 모바일 UI 느낌 (텍스트 없이)',
    mood: '행동 완료',
    colorPalette: 'sky blue, white',
    notes: '체크마크/완료 느낌. CPA 핵심 컷.',
    imagePath: 'webtoon/ep2-p2.png',
  },
  {
    id: 'ep2-p3',
    episode: 2,
    panel: 3,
    title: 'CPA 수익 발생',
    composition: '민지가 폰 화면 보며 놀람/기쁨. 화면에서 동전/원화 아이콘 올라옴. +10,000 느낌.',
    characters: ['홍보왕 민지 (excited)'],
    background: '에메랄드 그라데이션, 돈/코인 아이콘',
    mood: '수익 · 성취',
    colorPalette: 'emerald, gold accent',
    notes: '숫자는 그리지 말고 코인/₩ 심볼만.',
    imagePath: 'webtoon/ep2-p3.png',
  },
  {
    id: 'ep3-p1',
    episode: 3,
    panel: 1,
    title: 'CPS 상품 리뷰',
    composition: '민지가 쇼핑백/상품 들고 리뷰 촬영 또는 포스팅. 쇼핑몰 상품 느낌.',
    characters: ['홍보왕 민지'],
    background: '시안/스카이, 쇼핑 태그 아이콘',
    mood: '홍보 · 리뷰',
    colorPalette: 'cyan, sky blue',
    notes: 'CPS는 구매 연결 강조.',
    imagePath: 'webtoon/ep3-p1.png',
  },
  {
    id: 'ep3-p2',
    episode: 3,
    panel: 2,
    title: '손님 구매',
    composition: '손님이 카드/폰 결제. 쇼핑백 들고 만족. 상품 박스 작게.',
    characters: ['손님 (excited)'],
    background: '보라-핑크 따뜻한 톤',
    mood: '구매 완료',
    colorPalette: 'violet, purple, warm',
    notes: '결제/쇼핑 카트 아이콘.',
    imagePath: 'webtoon/ep3-p2.png',
  },
  {
    id: 'ep3-p3',
    episode: 3,
    panel: 3,
    title: 'CPS 수수료',
    composition: '민지 + 수수료 % 개념 (파이/코인). 10% 느낌의 작은 차트 없이 아이콘만.',
    characters: ['홍보왕 민지', '링크커넥트 (작게)'],
    background: 'cyan-indigo 그라데이션',
    mood: '수수료 · 설명',
    colorPalette: 'cyan, indigo, gold',
    notes: 'CPS = 판매금액 비율.',
    imagePath: 'webtoon/ep3-p3.png',
  },
  {
    id: 'ep4-p1',
    episode: 4,
    panel: 1,
    title: 'CPA vs CPS 소개',
    composition: '링크커넥트가 두 갈래 길 가리킴. 좌우 분기 느낌.',
    characters: ['링크커넥트'],
    background: '슬레이트 그레이, Y자 갈림길',
    mood: '비교 시작',
    colorPalette: 'slate, white',
    notes: '다음 컷으로 이어지는 복선.',
    imagePath: 'webtoon/ep4-p1.png',
  },
  {
    id: 'ep4-p2',
    episode: 4,
    panel: 2,
    title: 'CPA vs CPS 대비',
    composition: '화면 좌우 분할. 좌: CPA(신청/체크), 우: CPS(쇼핑/카드). 중앙 VS.',
    characters: ['홍보왕 민지 (좌)', '손님 (우)'],
    background: '좌 emerald / 우 cyan 분할',
    mood: '한눈에 비교',
    colorPalette: 'emerald left, cyan right',
    notes: '가장 중요한 비교 컷. 대칭 구도.',
    imagePath: 'webtoon/ep4-p2.png',
  },
  {
    id: 'ep5-p1',
    episode: 5,
    panel: 1,
    title: '광고주 불안',
    composition: '김사장, 빈 광고 통계 차트(텍스트 없음), 지갑에서 돈 나감 표현.',
    characters: ['김사장 (worried)'],
    background: 'amber shop interior',
    mood: '불안 · 공감',
    colorPalette: 'amber, orange',
    notes: '광고비 낭비 공포.',
    imagePath: 'webtoon/ep5-p1.png',
  },
  {
    id: 'ep5-p2',
    episode: 5,
    panel: 2,
    title: '성과형 광고 안심',
    composition: '김사장 + 링크커넥트. 대시보드/그래프 상승. 밝은 표정.',
    characters: ['김사장 (happy)', '링크커넥트'],
    background: 'cyan-emerald, 성과 그래프',
    mood: '안심 · Win',
    colorPalette: 'cyan, emerald',
    notes: 'ROI/효율 느낌.',
    imagePath: 'webtoon/ep5-p2.png',
  },
  {
    id: 'ep6-p1',
    episode: 6,
    panel: 1,
    title: '파트너 수익 발견',
    composition: '민지가 블로그 통계/수익 화면 보고 깜짝. 반짝이.',
    characters: ['홍보왕 민지 (excited)'],
    background: 'emerald-teal, 블로그 UI',
    mood: '놀람 · 기쁨',
    colorPalette: 'emerald, teal',
    notes: '파트너 관점 클라이맥스.',
    imagePath: 'webtoon/ep6-p1.png',
  },
  {
    id: 'ep6-p2',
    episode: 6,
    panel: 2,
    title: 'Win-Win 마무리',
    composition: '4캐릭터 함께 (김사장, 민지, 손님, 링크커넥트). 하이파이브/밝은 미래.',
    characters: ['김사장', '홍보왕 민지', '손님', '링크커넥트'],
    background: 'indigo-violet celebration, confetti',
    mood: 'Happy Ending',
    colorPalette: 'indigo, emerald, gold confetti',
    notes: '시리즈 마지막 컷. 포스터 퀄리티.',
    imagePath: 'webtoon/ep6-p2.png',
  },
];

/** episode/panel 번호로 이미지 URL 조회 (Vite BASE_URL 포함) */
export function getPanelImageUrl(episode: number, panelIndex: number): string {
  const id = `ep${episode}-p${panelIndex + 1}`;
  const brief = illustrationBriefs.find((b) => b.id === id);
  if (!brief) return '';
  return `${import.meta.env.BASE_URL}${brief.imagePath}`;
}
