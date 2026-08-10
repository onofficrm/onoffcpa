import type { PartnerEmbedOptions } from './partnerEmbed';

/** 상담 위젯 디자인 프리셋 */
export type EmbedPresetId = 'default' | 'simple' | 'card' | 'bold' | 'soft' | 'dark';

export type EmbedPresetMeta = {
  id: EmbedPresetId;
  label: string;
  desc: string;
  /** 선택 시 기본 강조색 (사용자가 이미 바꾼 경우 덮지 않음) */
  accentHint?: string;
};

export const EMBED_PRESETS: EmbedPresetMeta[] = [
  { id: 'default', label: '기본형', desc: '카드 + 그림자, 범용', accentHint: '#0d9488' },
  { id: 'simple', label: '심플형', desc: '테두리만, 가벼운 느낌', accentHint: '#0f766e' },
  { id: 'card', label: '카드형', desc: '둥근 모서리·깊은 그림자', accentHint: '#2563eb' },
  { id: 'bold', label: '강조형', desc: '상단 컬러바 + 큰 CTA', accentHint: '#dc2626' },
  { id: 'soft', label: '소프트형', desc: '연한 배경·부드러운 톤', accentHint: '#7c3aed' },
  { id: 'dark', label: '다크형', desc: '어두운 배경·밝은 글자', accentHint: '#22d3ee' },
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
