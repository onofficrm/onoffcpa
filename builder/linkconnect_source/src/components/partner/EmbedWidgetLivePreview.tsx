import type { CSSProperties } from 'react';
import type { LeadEmbedMode, PartnerEmbedOptions } from '../../lib/partnerEmbed';
import {
  embedThemeTokens,
  normalizeEmbedPreset,
  type EmbedPresetId,
} from '../../lib/embedPresets';

type Device = 'pc' | 'mobile';

type Props = {
  mode: LeadEmbedMode;
  options: PartnerEmbedOptions;
  device: Device;
  phoneHint?: string;
  brandName?: string;
};

export function EmbedWidgetLivePreview({
  mode,
  options,
  device,
  phoneHint,
  brandName = '상담',
}: Props) {
  const preset = normalizeEmbedPreset(options.preset) as EmbedPresetId;
  const theme = embedThemeTokens(preset, options.accent || '#0d9488');
  const showRegion = options.showRegion !== false;
  const showInquiry = options.showInquiry !== false;
  const title = options.title || '무료 상담 신청';
  const submitLabel = options.submitLabel || '상담 신청하기';
  const buttonLabel = options.buttonLabel || '무료 상담 신청';
  const callLabel = options.callLabel || '전화 상담';
  const privacyText = options.privacyText || '개인정보 수집·이용에 동의합니다.';
  const hasPhone = Boolean(phoneHint && /[0-9]/.test(phoneHint));

  const shellWidth = device === 'mobile' ? 320 : 420;

  if (mode === 'phone') {
    return (
      <div className="flex justify-center">
        <div
          className="rounded-2xl border border-slate-200 bg-slate-100 p-4"
          style={{ width: shellWidth }}
        >
          <a
            href="#preview-call"
            onClick={(e) => e.preventDefault()}
            className="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-extrabold no-underline"
            style={{
              background: 'rgba(5,150,105,.1)',
              border: '1px solid rgba(5,150,105,.25)',
              color: theme.call,
            }}
          >
            {callLabel} <span className="tabular-nums">010-0000-0000</span>
          </a>
          {!hasPhone ? (
            <p className="mt-2 text-[11px] text-amber-700 leading-relaxed">
              안심번호가 배정되면 실제 번호가 표시됩니다.
            </p>
          ) : null}
        </div>
      </div>
    );
  }

  if (mode === 'button') {
    return (
      <div className="flex justify-center">
        <div
          className="rounded-2xl border border-slate-200 bg-slate-100 p-6 flex flex-col items-center gap-3"
          style={{ width: shellWidth }}
        >
          <button
            type="button"
            className="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-extrabold text-white shadow-md"
            style={{ background: theme.accent }}
          >
            {buttonLabel}
          </button>
          <p className="text-[11px] text-slate-500 text-center">클릭 시 모달 상담폼이 열립니다</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex justify-center">
      <div
        className="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#f1f5f9_0%,#e2e8f0_100%)] p-4"
        style={{ width: shellWidth }}
      >
        <div
          style={{
            background: theme.bg,
            color: theme.text,
            border: `1px solid ${theme.border}`,
            borderRadius: theme.radius,
            boxShadow: theme.shadow,
            padding: theme.padding,
            overflow: 'hidden',
            fontFamily:
              '-apple-system,BlinkMacSystemFont,"Apple SD Gothic Neo","Noto Sans KR",sans-serif',
          }}
        >
          {preset === 'bold' && theme.headerBg ? (
            <div
              style={{
                background: theme.headerBg,
                color: theme.headerText,
                margin: '-0px -20px 16px',
                padding: '18px 20px 14px',
              }}
            >
              <div style={{ fontSize: '1.125rem', fontWeight: 800, letterSpacing: '-0.02em' }}>
                {title}
              </div>
              <div style={{ marginTop: 4, fontSize: '0.8rem', opacity: 0.9 }}>
                빠른 상담을 남겨 주세요.
              </div>
            </div>
          ) : (
            <>
              <div style={{ margin: '0 0 4px', fontSize: '1.125rem', fontWeight: 800 }}>
                {title}
              </div>
              <div style={{ margin: '0 0 14px', fontSize: '0.875rem', color: theme.muted }}>
                빠른 상담을 남겨 주세요.
              </div>
            </>
          )}

          {hasPhone ? (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 8,
                margin: '0 0 14px',
                padding: '11px 12px',
                borderRadius: 12,
                background: 'rgba(5,150,105,.08)',
                border: '1px solid rgba(5,150,105,.22)',
                color: theme.call,
                fontWeight: 800,
                fontSize: '0.9rem',
              }}
            >
              {callLabel} <span className="tabular-nums">010-0000-0000</span>
            </div>
          ) : null}

          <Field label="이름" required theme={theme} placeholder="홍길동" />
          <Field label="연락처" required theme={theme} placeholder="010-1234-5678" />
          {showRegion ? <Field label="지역" theme={theme} placeholder="서울 / 경기 등" /> : null}
          {showInquiry ? (
            <Field label="문의 내용" theme={theme} placeholder="상담 내용을 적어 주세요." textarea />
          ) : null}

          <label
            style={{
              display: 'flex',
              gap: 8,
              alignItems: 'flex-start',
              margin: '4px 0 14px',
              fontSize: '0.8rem',
              color: theme.muted,
              lineHeight: 1.4,
            }}
          >
            <input type="checkbox" readOnly checked style={{ marginTop: 2 }} />
            <span>{privacyText}</span>
          </label>

          <button
            type="button"
            style={{
              display: 'flex',
              width: '100%',
              alignItems: 'center',
              justifyContent: 'center',
              border: 0,
              borderRadius: 12,
              padding: '13px 16px',
              fontSize: '0.95rem',
              fontWeight: 800,
              background: theme.accent,
              color: theme.accentText,
              cursor: 'default',
            }}
          >
            {submitLabel}
          </button>
          <p
            style={{
              margin: '12px 0 0',
              fontSize: '0.7rem',
              color: theme.muted,
              textAlign: 'center',
            }}
          >
            {brandName} 상담 위젯 · 미리보기
          </p>
        </div>
      </div>
    </div>
  );
}

function Field({
  label,
  required,
  theme,
  placeholder,
  textarea,
}: {
  label: string;
  required?: boolean;
  theme: ReturnType<typeof embedThemeTokens>;
  placeholder: string;
  textarea?: boolean;
}) {
  const inputStyle: CSSProperties = {
    width: '100%',
    border: `1px solid ${theme.border === 'transparent' ? '#e2e8f0' : theme.border}`,
    borderRadius: 12,
    padding: '11px 12px',
    fontSize: '0.9rem',
    color: theme.text,
    background: theme.inputBg,
    outline: 'none',
  };
  return (
    <div style={{ margin: '0 0 12px' }}>
      <div style={{ margin: '0 0 6px', fontSize: '0.75rem', fontWeight: 700, color: theme.muted }}>
        {label}
        {required ? <span style={{ color: '#e11d48', marginLeft: 2 }}>*</span> : null}
      </div>
      {textarea ? (
        <div style={{ ...inputStyle, minHeight: 72, color: theme.muted }}>{placeholder}</div>
      ) : (
        <div style={{ ...inputStyle, color: theme.muted }}>{placeholder}</div>
      )}
    </div>
  );
}
