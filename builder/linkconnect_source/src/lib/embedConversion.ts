import type { PartnerEmbedOptions } from './partnerEmbed';

/** 업종별 CTA·혜택 문구 프리셋 */
export type EmbedCtaPresetId = 'general' | 'legal' | 'clean' | 'interior' | 'clinic';

export type EmbedCtaPreset = {
  id: EmbedCtaPresetId;
  label: string;
  desc: string;
  submitLabel: string;
  buttonLabel: string;
  benefitText: string;
  ctaHint: string;
  successNextStep: string;
};

export const EMBED_CTA_PRESETS: EmbedCtaPreset[] = [
  {
    id: 'general',
    label: '범용',
    desc: '대부분 업종',
    submitLabel: '지금 무료 상담 받기',
    buttonLabel: '지금 무료 상담 받기',
    benefitText: '상담비 없음 · 3분 내 연락',
    ctaHint: '영업전화 없음 · 개인정보 안전',
    successNextStep: '담당자가 확인 후 곧 연락드립니다.',
  },
  {
    id: 'legal',
    label: '법률·회생',
    desc: '비밀·신뢰 강조',
    submitLabel: '비밀보장 상담 신청',
    buttonLabel: '비밀보장 상담 받기',
    benefitText: '상담 내용 비밀보장 · 부담 없는 안내',
    ctaHint: '상담비 없음 · 개인정보 철저 보호',
    successNextStep: '전문 상담원이 확인 후 안전하게 연락드립니다.',
  },
  {
    id: 'clean',
    label: '청소·유품',
    desc: '견적·추가금',
    submitLabel: '무료 견적 상담 받기',
    buttonLabel: '무료 견적 받기',
    benefitText: '견적 무료 · 추가금 없는 안내',
    ctaHint: '상담비 없음 · 당일 일정 가능 문의',
    successNextStep: '현장 상황을 확인한 뒤 견적과 일정을 안내드립니다.',
  },
  {
    id: 'interior',
    label: '인테리어',
    desc: '빠른 견적',
    submitLabel: '빠른 견적 상담 받기',
    buttonLabel: '빠른 견적 받기',
    benefitText: '사진 보내면 빠른 견적 · 상담 무료',
    ctaHint: '견적 상담 무료 · 부담 없는 안내',
    successNextStep: '접수 내용을 확인한 뒤 견적 안내를 드립니다.',
  },
  {
    id: 'clinic',
    label: '병원·뷰티',
    desc: '예약·시간대',
    submitLabel: '원하는 시간 상담 신청',
    buttonLabel: '상담 예약하기',
    benefitText: '원하는 시간대 안내 · 상담 예약',
    ctaHint: '예약 상담 · 개인정보 안전',
    successNextStep: '가능한 시간대를 확인한 뒤 연락드립니다.',
  },
];

export function applyEmbedCtaPreset(
  options: PartnerEmbedOptions,
  id: EmbedCtaPresetId,
): PartnerEmbedOptions {
  const preset = EMBED_CTA_PRESETS.find((p) => p.id === id);
  if (!preset) return options;
  return {
    ...options,
    submitLabel: preset.submitLabel,
    buttonLabel: preset.buttonLabel,
    benefitText: preset.benefitText,
    ctaHint: preset.ctaHint,
    successNextStep: preset.successNextStep,
  };
}

/** dataLayer / gtag 로 전송되는 CRO 마이크로 이벤트명 */
export const EMBED_CRO_EVENTS = [
  { id: 'badge_click', label: '신뢰 배지 클릭' },
  { id: 'extra_fields_open', label: '추가 정보 펼침' },
  { id: 'sticky_submit', label: '모바일 sticky 제출' },
  { id: 'success_call_tap', label: '완료 화면 전화 탭' },
] as const;

/** 전환 최적화 기본값 (신규·미설정 시) */
export const EMBED_CRO_DEFAULTS: Partial<PartnerEmbedOptions> = {
  minimalForm: true,
  showTrustBadges: true,
  badgeFree: true,
  badgeCallback: true,
  badgePrivacy: true,
  benefitText: '상담비 없음 · 3분 내 연락',
  ctaHint: '영업전화 없음 · 개인정보 안전',
  showLiveCount: true,
  liveCountText: '지금 상담 신청이 활발합니다',
  stickyMobileCta: true,
  successShowCall: true,
  successNextStep: '담당자가 확인 후 곧 연락드립니다.',
};

const CRO_FLAG_KEYS = [
  'minimalForm',
  'showTrustBadges',
  'showLiveCount',
  'stickyMobileCta',
  'successShowCall',
] as const;

/** 목록·배지용 위젯/전환 상태 요약 */
export function summarizeEmbedWidgetStatus(options?: PartnerEmbedOptions | null) {
  const opts = options || {};
  const croOnCount = CRO_FLAG_KEYS.filter((key) => opts[key] !== false).length;
  const croOn = croOnCount >= 3;
  const croLabels: string[] = [];
  if (opts.minimalForm !== false) croLabels.push('미니멀폼');
  if (opts.showTrustBadges !== false) croLabels.push('신뢰배지');
  if (opts.stickyMobileCta !== false) croLabels.push('모바일CTA');
  if (opts.successShowCall !== false) croLabels.push('완료전화');
  return {
    croOn,
    croOnCount,
    croLabels,
    croSummary: croOn ? `전환 최적화 ON (${croLabels.slice(0, 2).join('·')})` : '전환 최적화 OFF',
  };
}
