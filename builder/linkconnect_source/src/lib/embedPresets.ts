import type { PartnerEmbedOptions } from './partnerEmbed';

/** 상담 위젯 디자인 프리셋 */
export type EmbedPresetId = 'default' | 'simple' | 'card' | 'bold' | 'soft' | 'dark';

/** PC 전용 배치 모드 (템플릿과 독립) */
export type EmbedPcLayoutId = 'auto' | 'split' | 'wide' | 'hero';

export type EmbedPcLayoutMeta = {
  id: EmbedPcLayoutId;
  label: string;
  desc: string;
};

export const EMBED_PC_LAYOUTS: EmbedPcLayoutMeta[] = [
  { id: 'auto', label: '프리셋 기본', desc: '템플릿별 PC 배치 그대로' },
  { id: 'split', label: '스플릿', desc: '좌 신뢰 · 우 폼 2열' },
  { id: 'wide', label: '와이드폼', desc: '넓은 1열 + 필드 나란히' },
  { id: 'hero', label: '히어로', desc: '큰 카피 + 흰 폼 패널' },
];

export function normalizeEmbedPcLayout(value?: string | null): EmbedPcLayoutId {
  const v = String(value || '').trim().toLowerCase();
  if (v === 'split' || v === 'wide' || v === 'hero' || v === 'auto') return v;
  return 'auto';
}

/** 미리보기용: PC 배치 모드 → 레이아웃 스킨 (색은 preset 유지) */
export function resolvePcLayoutSkin(
  preset: EmbedPresetId,
  pcLayout?: string | null,
): EmbedPresetId {
  const layout = normalizeEmbedPcLayout(pcLayout);
  if (layout === 'split') return 'default';
  if (layout === 'wide') return 'simple';
  if (layout === 'hero') return 'soft';
  return preset;
}

export type EmbedPresetMeta = {
  id: EmbedPresetId;
  label: string;
  desc: string;
  /** 선택 시 기본 강조색 (사용자가 이미 바꾼 경우 덮지 않음) */
  accentHint?: string;
};

export const EMBED_PRESETS: EmbedPresetMeta[] = [
  { id: 'default', label: '기본형', desc: 'PC 2열 신뢰+폼 · 범용', accentHint: '#0d9488' },
  { id: 'simple', label: '심플형', desc: 'PC 와이드 1열 · 가벼운 톤', accentHint: '#0f766e' },
  { id: 'card', label: '카드형', desc: 'PC 중앙 카드 · 배지 스트립', accentHint: '#2563eb' },
  { id: 'bold', label: '강조형', desc: 'PC 풀폭 헤더+2열 · 강한 CTA', accentHint: '#dc2626' },
  { id: 'soft', label: '소프트형', desc: 'PC 히어로+흰 폼 패널', accentHint: '#7c3aed' },
  { id: 'dark', label: '다크형', desc: 'PC 대비 스플릿 · 다크 레일', accentHint: '#22d3ee' },
];

export function normalizeEmbedPreset(value?: string | null): EmbedPresetId {
  const v = String(value || '').trim().toLowerCase();
  if (v === 'simple' || v === 'card' || v === 'bold' || v === 'soft' || v === 'dark') {
    return v;
  }
  return 'default';
}

export function withEmbedPreset(
  options: PartnerEmbedOptions,
  preset: EmbedPresetId,
  opts?: { applyAccentHint?: boolean },
): PartnerEmbedOptions {
  const next: PartnerEmbedOptions = { ...options, preset };
  if (opts?.applyAccentHint) {
    const meta = EMBED_PRESETS.find((p) => p.id === preset);
    if (meta?.accentHint) next.accent = meta.accentHint;
  }
  return next;
}

/** 미리보기·CSS용 테마 토큰 */
export type EmbedThemeTokens = {
  accent: string;
  accentText: string;
  border: string;
  bg: string;
  text: string;
  muted: string;
  call: string;
  radius: string;
  shadow: string;
  padding: string;
  inputBg: string;
  headerBg?: string;
  headerText?: string;
};

export function embedThemeTokens(
  preset: EmbedPresetId,
  accent = '#0d9488',
): EmbedThemeTokens {
  const a = accent || '#0d9488';
  switch (preset) {
    case 'simple':
      return {
        accent: a,
        accentText: '#ffffff',
        border: '#e2e8f0',
        bg: '#ffffff',
        text: '#0f172a',
        muted: '#64748b',
        call: '#059669',
        radius: '10px',
        shadow: 'none',
        padding: '16px',
        inputBg: '#ffffff',
      };
    case 'card':
      return {
        accent: a,
        accentText: '#ffffff',
        border: '#e2e8f0',
        bg: '#ffffff',
        text: '#0f172a',
        muted: '#64748b',
        call: '#059669',
        radius: '22px',
        shadow: '0 18px 40px rgba(15,23,42,.12)',
        padding: '22px',
        inputBg: '#f8fafc',
      };
    case 'bold':
      return {
        accent: a,
        accentText: '#ffffff',
        border: 'transparent',
        bg: '#ffffff',
        text: '#0f172a',
        muted: '#64748b',
        call: '#059669',
        radius: '16px',
        shadow: '0 12px 28px rgba(15,23,42,.1)',
        padding: '0 20px 20px',
        inputBg: '#f8fafc',
        headerBg: a,
        headerText: '#ffffff',
      };
    case 'soft':
      return {
        accent: a,
        accentText: '#ffffff',
        border: `${a}33`,
        bg: `${a}14`,
        text: '#0f172a',
        muted: '#475569',
        call: a,
        radius: '18px',
        shadow: '0 8px 20px rgba(15,23,42,.05)',
        padding: '20px',
        inputBg: '#ffffff',
      };
    case 'dark':
      return {
        accent: a,
        accentText: '#0f172a',
        border: '#334155',
        bg: '#0f172a',
        text: '#f8fafc',
        muted: '#94a3b8',
        call: a,
        radius: '16px',
        shadow: '0 12px 32px rgba(0,0,0,.35)',
        padding: '20px',
        inputBg: '#1e293b',
      };
    default:
      return {
        accent: a,
        accentText: '#ffffff',
        border: '#e2e8f0',
        bg: '#ffffff',
        text: '#0f172a',
        muted: '#64748b',
        call: '#059669',
        radius: '16px',
        shadow: '0 8px 24px rgba(15,23,42,.06)',
        padding: '20px',
        inputBg: '#f8fafc',
      };
  }
}
