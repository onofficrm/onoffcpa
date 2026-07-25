export type CharacterId = 'owner' | 'partner' | 'customer' | 'guide';

export type CharacterMood = 'neutral' | 'worried' | 'happy' | 'excited' | 'thinking';

export type BubbleAlign = 'left' | 'right' | 'center';

export type SceneProp = 'shop' | 'blog' | 'compare' | 'money' | 'sparkle';

export interface PanelCharacter {
  id: CharacterId;
  x: number;
  y: number;
  scale?: number;
  mood?: CharacterMood;
}

export interface WebtoonPanel {
  scene: string;
  sceneAccent: string;
  sceneProp?: SceneProp;
  characters: PanelCharacter[];
  bubble: {
    align: BubbleAlign;
    speaker: string;
    speakerColor: string;
    text: string;
    highlight?: string;
  };
  caption?: string;
}

export interface WebtoonEpisode {
  num: number;
  title: string;
  subtitle: string;
  panels: WebtoonPanel[];
}

export const characterMeta: Record<
  CharacterId,
  { label: string; bg: string; border: string; role: string; accent: string }
> = {
  owner: {
    label: '김사장',
    bg: 'bg-amber-100',
    border: 'border-amber-300',
    role: '광고주',
    accent: 'text-amber-700',
  },
  partner: {
    label: '홍보왕 민지',
    bg: 'bg-emerald-100',
    border: 'border-emerald-300',
    role: '파트너',
    accent: 'text-emerald-700',
  },
  customer: {
    label: '손님',
    bg: 'bg-sky-100',
    border: 'border-sky-300',
    role: '고객',
    accent: 'text-sky-700',
  },
  guide: {
    label: '링크커넥트',
    bg: 'bg-indigo-100',
    border: 'border-indigo-300',
    role: '안내자',
    accent: 'text-indigo-700',
  },
};

export const episodes: WebtoonEpisode[] = [
  {
    num: 1,
    title: '제휴마케팅이 뭐예요?',
    subtitle: '광고비는 나가는데, 손님은 안 오는 사장님',
    panels: [
      {
        scene: 'from-amber-50 to-orange-100',
        sceneAccent: 'bg-amber-200/40',
        sceneProp: 'shop',
        characters: [{ id: 'owner', x: 50, y: 68, scale: 1.15, mood: 'worried' }],
        bubble: {
          align: 'left',
          speaker: '김사장',
          speakerColor: 'text-amber-700',
          text: '광고비는 많이 썼는데… 손님이 늘지 않아요. 효과가 있는지도 모르겠고요',
        },
        caption: '동네 쇼핑몰 사장님, 요즘 고민이 많습니다.',
      },
      {
        scene: 'from-indigo-50 to-violet-100',
        sceneAccent: 'bg-indigo-200/30',
        sceneProp: 'sparkle',
        characters: [{ id: 'guide', x: 50, y: 66, scale: 1.2, mood: 'happy' }],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '걱정 마세요! 성과가 났을 때만 보상하는 광고 방식이 있어요. 바로 제휴 마케팅입니다',
          highlight: '성과가 났을 때만 보상',
        },
      },
      {
        scene: 'from-emerald-50 to-teal-100',
        sceneAccent: 'bg-emerald-200/30',
        characters: [
          { id: 'owner', x: 18, y: 72, mood: 'neutral' },
          { id: 'partner', x: 82, y: 72, mood: 'happy' },
          { id: 'customer', x: 50, y: 82, scale: 0.85, mood: 'neutral' },
        ],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '파트너가 고객을 소개하고, 실제 성과(구매·상담)가 생기면 광고주가 보상해요!',
        },
        caption: '소개 → 성과 → 보상. 이게 제휴 마케팅의 핵심!',
      },
    ],
  },
  {
    num: 2,
    title: 'CPA는 뭐예요?',
    subtitle: '신청·상담·가입하면 수익!',
    panels: [
      {
        scene: 'from-emerald-50 to-green-100',
        sceneAccent: 'bg-emerald-200/40',
        sceneProp: 'blog',
        characters: [{ id: 'partner', x: 38, y: 68, mood: 'thinking' }],
        bubble: {
          align: 'left',
          speaker: '홍보왕 민지',
          speakerColor: 'text-emerald-700',
          text: '블로그에 "무료 상담 신청" 링크를 올려볼까?',
        },
      },
      {
        scene: 'from-sky-50 to-blue-100',
        sceneAccent: 'bg-sky-200/30',
        characters: [
          { id: 'customer', x: 58, y: 66, mood: 'happy' },
          { id: 'partner', x: 18, y: 78, scale: 0.8, mood: 'neutral' },
        ],
        bubble: {
          align: 'right',
          speaker: '손님',
          speakerColor: 'text-sky-700',
          text: '오, 무료 상담이네? 신청해볼게요!',
        },
        caption: '고객이 링크를 눌러 상담 신청을 완료합니다.',
      },
      {
        scene: 'from-emerald-100 to-teal-100',
        sceneAccent: 'bg-emerald-300/30',
        sceneProp: 'money',
        characters: [{ id: 'partner', x: 50, y: 66, scale: 1.15, mood: 'excited' }],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '상담 신청 1건 = 10,000원! CPA는 고객의 "행동"이 완료되면 수익이 발생해요.',
          highlight: 'CPA = Cost Per Action (행동형)',
        },
      },
    ],
  },
  {
    num: 3,
    title: 'CPS는 뭐예요?',
    subtitle: '구매하면 수수료!',
    panels: [
      {
        scene: 'from-cyan-50 to-sky-100',
        sceneAccent: 'bg-cyan-200/40',
        sceneProp: 'blog',
        characters: [{ id: 'partner', x: 32, y: 68, mood: 'happy' }],
        bubble: {
          align: 'left',
          speaker: '홍보왕 민지',
          speakerColor: 'text-emerald-700',
          text: '이번엔 쇼핑몰 상품 리뷰 + 구매 링크를 올려볼게!',
        },
      },
      {
        scene: 'from-violet-50 to-purple-100',
        sceneAccent: 'bg-violet-200/30',
        characters: [{ id: 'customer', x: 58, y: 66, mood: 'excited' }],
        bubble: {
          align: 'right',
          speaker: '손님',
          speakerColor: 'text-sky-700',
          text: '좋아 보이네! 링크 타고 50,000원짜리 구매할게요',
        },
      },
      {
        scene: 'from-cyan-100 to-indigo-100',
        sceneAccent: 'bg-cyan-300/30',
        sceneProp: 'money',
        characters: [{ id: 'partner', x: 50, y: 66, scale: 1.15, mood: 'excited' }],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '50,000원 × 수수료 10% = 5,000원! CPS는 "구매"가 완료되면 수익이 발생해요.',
          highlight: 'CPS = Cost Per Sale (판매형)',
        },
      },
    ],
  },
  {
    num: 4,
    title: 'CPA vs CPS',
    subtitle: '한눈에 비교하기',
    panels: [
      {
        scene: 'from-slate-50 to-slate-100',
        sceneAccent: 'bg-slate-200/30',
        characters: [{ id: 'guide', x: 50, y: 70, scale: 1.1, mood: 'neutral' }],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '같은 제휴 마케팅이지만, 수익이 발생하는 순간이 달라요!',
        },
      },
      {
        scene: 'from-emerald-50 via-white to-cyan-50',
        sceneAccent: 'bg-gradient-to-r from-emerald-200/20 to-cyan-200/20',
        sceneProp: 'compare',
        characters: [
          { id: 'partner', x: 25, y: 72, mood: 'happy' },
          { id: 'customer', x: 75, y: 72, mood: 'neutral' },
        ],
        bubble: {
          align: 'center',
          speaker: '비교',
          speakerColor: 'text-slate-700',
          text: 'CPA → 상담·가입·문의·예약  |  CPS → 쇼핑몰 구매·결제 완료',
          highlight: '행동 vs 구매',
        },
        caption: 'CPA: 신청만 해도 OK  ·  CPS: 구매가 되어야 OK',
      },
    ],
  },
  {
    num: 5,
    title: '광고주는 왜 좋아요?',
    subtitle: '성과 없으면 비용도 없어요',
    panels: [
      {
        scene: 'from-amber-50 to-orange-100',
        sceneAccent: 'bg-amber-200/40',
        sceneProp: 'shop',
        characters: [{ id: 'owner', x: 50, y: 68, mood: 'worried' }],
        bubble: {
          align: 'left',
          speaker: '김사장',
          speakerColor: 'text-amber-700',
          text: '성과 없이 광고비만 나가는 게 제일 무서웠어요…',
        },
      },
      {
        scene: 'from-cyan-50 to-emerald-100',
        sceneAccent: 'bg-cyan-200/30',
        sceneProp: 'sparkle',
        characters: [
          { id: 'owner', x: 28, y: 72, mood: 'happy' },
          { id: 'guide', x: 72, y: 66, mood: 'happy' },
        ],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '제휴 마케팅은 실제 성과가 있을 때만 비용을 지불해요. 파트너들이 대신 홍보하고, 광고비 효율도 한눈에 확인!',
          highlight: '성과 중심 · 예산 낭비 ZERO',
        },
      },
    ],
  },
  {
    num: 6,
    title: '파트너는 왜 좋아요?',
    subtitle: '링크 하나로 수익화',
    panels: [
      {
        scene: 'from-emerald-50 to-teal-100',
        sceneAccent: 'bg-emerald-200/40',
        sceneProp: 'blog',
        characters: [{ id: 'partner', x: 50, y: 68, scale: 1.15, mood: 'excited' }],
        bubble: {
          align: 'left',
          speaker: '홍보왕 민지',
          speakerColor: 'text-emerald-700',
          text: '와… 내 블로그 글 하나로 수익이 쌓이네?',
        },
      },
      {
        scene: 'from-indigo-50 to-violet-100',
        sceneAccent: 'bg-indigo-200/30',
        sceneProp: 'sparkle',
        characters: [
          { id: 'partner', x: 32, y: 72, mood: 'happy' },
          { id: 'guide', x: 70, y: 66, mood: 'happy' },
        ],
        bubble: {
          align: 'center',
          speaker: '링크커넥트',
          speakerColor: 'text-indigo-700',
          text: '가입비 0원! 원하는 캠페인을 골라 링크 하나로 성과를 추적하고, 실시간으로 수익을 확인하세요',
          highlight: '자본금 없이 시작 · 실시간 수익 확인',
        },
        caption: '사장님도, 홍보왕도, 모두 Win-Win!',
      },
    ],
  },
];
