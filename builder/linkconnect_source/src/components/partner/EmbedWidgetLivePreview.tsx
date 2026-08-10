import type { CSSProperties, ReactNode } from 'react';
import type { LeadEmbedMode, PartnerEmbedOptions } from '../../lib/partnerEmbed';
import {
  embedThemeTokens,
  normalizeEmbedPreset,
  resolvePcLayoutSkin,
  type EmbedPresetId,
} from '../../lib/embedPresets';

type Device = 'pc' | 'mobile';
export type EmbedPreviewStage = 'form' | 'success';

type Props = {
  mode: LeadEmbedMode;
  options: PartnerEmbedOptions;
  device: Device;
  stage?: EmbedPreviewStage;
  phoneHint?: string;
  brandName?: string;
  /** 디바이스 프레임 안이면 고정 shell 폭 없이 100% 사용 */
  inDeviceFrame?: boolean;
  /** 미리보기 하단 브랜드/상품 표기 */
  productLabel?: string;
};

export function EmbedWidgetLivePreview({
  mode,
  options,
  device,
  stage = 'form',
  phoneHint,
  brandName = '상담',
  inDeviceFrame = false,
  productLabel,
}: Props) {
  const preset = normalizeEmbedPreset(options.preset) as EmbedPresetId;
  const theme = embedThemeTokens(preset, options.accent || '#0d9488');
  const layoutPreset =
    device === 'pc' ? resolvePcLayoutSkin(preset, options.pcLayout) : preset;
  const showRegion = options.showRegion !== false;
  const showInquiry = options.showInquiry !== false;
  const minimalForm = options.minimalForm !== false;
  const title = options.title || '무료 상담 신청';
  const submitLabel = options.submitLabel || '지금 무료 상담 받기';
  const buttonLabel = options.buttonLabel || '지금 무료 상담 받기';
  const callLabel = options.callLabel || '전화 상담';
  const privacyText = options.privacyText || '개인정보 수집·이용에 동의합니다.';
  const successMessage =
    options.successMessage || '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.';
  const redirectUrl = (options.successRedirectUrl || '').trim();
  const benefitText = (options.benefitText || '').trim();
  const ctaHint = (options.ctaHint || '').trim();
  const liveCountText = (options.liveCountText || '지금 상담 신청이 활발합니다').trim();
  const successNextStep = (options.successNextStep || '담당자가 확인 후 곧 연락드립니다.').trim();
  const hasPhone = Boolean(phoneHint && /[0-9]/.test(phoneHint));
  const stickyMobile = options.stickyMobileCta !== false && device === 'mobile';
  const shellWidth = inDeviceFrame ? undefined : device === 'mobile' ? 320 : 420;
  const footerLabel = productLabel || brandName;

  const badges: string[] = [];
  if (options.showTrustBadges !== false) {
    if (options.badgeFree !== false) badges.push('상담비 없음');
    if (options.badgeCallback !== false) badges.push('3분 내 연락');
    if (options.badgePrivacy !== false) badges.push('비밀보장');
  }

  const wrapShell = (inner: ReactNode, compactPad = true) => {
    if (inDeviceFrame) return <div className="w-full">{inner}</div>;
    return (
      <div className="flex justify-center">
        <div
          className={
            compactPad
              ? 'rounded-2xl border border-slate-200 bg-slate-100 p-4'
              : 'rounded-2xl border border-slate-200 bg-slate-100 p-6 flex flex-col items-center gap-3'
          }
          style={{ width: shellWidth }}
        >
          {inner}
        </div>
      </div>
    );
  };

  if (mode === 'phone') {
    return wrapShell(
      stage === 'success' ? (
        <p className="text-[11px] text-slate-500 text-center leading-relaxed py-2">
          전화형은 완료 화면이 없습니다. 클릭 시 바로 통화가 연결됩니다.
        </p>
      ) : (
        <>
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
        </>
      ),
    );
  }

  if (mode === 'button' && stage === 'form') {
    return wrapShell(
      <>
        <button
          type="button"
          className="inline-flex w-full items-center justify-center rounded-xl px-5 py-3 text-sm font-extrabold text-white shadow-md"
          style={{ background: theme.accent }}
        >
          {buttonLabel}
        </button>
        {ctaHint ? <p className="mt-2 text-center text-[11px] text-slate-500">{ctaHint}</p> : null}
        <p className="mt-2 text-center text-[11px] text-slate-500">클릭 시 모달 상담폼이 열립니다</p>
      </>,
      false,
    );
  }

  const card = (
    <WidgetCard
      theme={theme}
      preset={preset}
      layoutPreset={layoutPreset}
      stage={stage}
      title={title}
      successMessage={successMessage}
      successNextStep={successNextStep}
      redirectUrl={redirectUrl}
      showCall={options.successShowCall !== false && hasPhone}
      callLabel={callLabel}
      brandName={footerLabel}
      benefitText={benefitText}
      liveCountText={liveCountText}
      badges={badges}
      hasPhone={hasPhone}
      showRegion={showRegion}
      showInquiry={showInquiry}
      minimalForm={minimalForm}
      privacyText={privacyText}
      submitLabel={submitLabel}
      ctaHint={ctaHint}
      stickyMobile={stickyMobile}
      showLiveCount={options.showLiveCount !== false}
      showModalHint={mode === 'button' && stage === 'success'}
      wideLayout={device === 'pc'}
    />
  );

  if (inDeviceFrame) {
    return (
      <div className="w-full">
        {mode === 'button' && stage === 'success' ? (
          <p className="mb-2 text-center text-[10px] font-bold text-slate-500">모달 접수 완료 화면</p>
        ) : null}
        {card}
      </div>
    );
  }

  return (
    <div className="flex justify-center">
      <div
        className="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#f1f5f9_0%,#e2e8f0_100%)] p-4"
        style={{ width: shellWidth }}
      >
        {mode === 'button' && stage === 'success' ? (
          <p className="mb-2 text-center text-[10px] font-bold text-slate-500">모달 접수 완료 화면</p>
        ) : null}
        {card}
      </div>
    </div>
  );
}

function WidgetCard({
  theme,
  preset,
  layoutPreset,
  stage,
  title,
  successMessage,
  successNextStep,
  redirectUrl,
  showCall,
  callLabel,
  brandName,
  benefitText,
  liveCountText,
  badges,
  hasPhone,
  showRegion,
  showInquiry,
  minimalForm,
  privacyText,
  submitLabel,
  ctaHint,
  stickyMobile,
  showLiveCount,
  wideLayout = false,
}: {
  theme: ReturnType<typeof embedThemeTokens>;
  preset: EmbedPresetId;
  layoutPreset: EmbedPresetId;
  stage: EmbedPreviewStage;
  title: string;
  successMessage: string;
  successNextStep: string;
  redirectUrl: string;
  showCall: boolean;
  callLabel: string;
  brandName: string;
  benefitText: string;
  liveCountText: string;
  badges: string[];
  hasPhone: boolean;
  showRegion: boolean;
  showInquiry: boolean;
  minimalForm: boolean;
  privacyText: string;
  submitLabel: string;
  ctaHint: string;
  stickyMobile: boolean;
  showLiveCount: boolean;
  showModalHint?: boolean;
  wideLayout?: boolean;
}) {
  const stickyForm = stickyMobile && stage === 'form' && !wideLayout;
  const borderTone = theme.border === 'transparent' ? '#e2e8f0' : theme.border;
  const pc = wideLayout ? layoutPreset : null;
  const stackedPc = pc === 'simple' || pc === 'card';
  const showIntroTitle = !(preset === 'bold' && theme.headerBg);

  const introStyle = ((): CSSProperties | undefined => {
    if (!wideLayout) return undefined;
    if (pc === 'simple') {
      return {
        display: 'flex',
        flexDirection: 'row',
        flexWrap: 'wrap',
        alignItems: 'center',
        gap: '8px 10px',
        padding: '0 0 14px',
        borderBottom: `1px solid ${borderTone}`,
        minWidth: 0,
      };
    }
    if (pc === 'card') {
      return {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        textAlign: 'center',
        padding: 0,
        minWidth: 0,
      };
    }
    if (pc === 'soft') {
      return {
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        padding: '8px 12px 8px 4px',
        minWidth: 0,
      };
    }
    if (pc === 'dark') {
      return {
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        padding: '28px 24px',
        borderRadius: '12px 0 0 12px',
        background: '#020617',
        minWidth: 0,
      };
    }
    return {
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      padding: '4px 20px 4px 0',
      borderRight: `1px solid ${borderTone}`,
      minWidth: 0,
    };
  })();

  const splitStyle = ((): CSSProperties | undefined => {
    if (!wideLayout) {
      return stickyForm ? { display: 'flex', flexDirection: 'column', minHeight: 480 } : undefined;
    }
    if (stackedPc) {
      return { display: 'flex', flexDirection: 'column', gap: pc === 'card' ? 16 : 18, alignItems: 'stretch' };
    }
    if (pc === 'soft') {
      return {
        display: 'grid',
        gridTemplateColumns: 'minmax(0,1.05fr) minmax(0,1fr)',
        gap: 24,
        alignItems: 'stretch',
      };
    }
    if (pc === 'dark') {
      return {
        display: 'grid',
        gridTemplateColumns: 'minmax(0,1fr) minmax(0,1.2fr)',
        gap: 0,
        alignItems: 'stretch',
      };
    }
    return {
      display: 'grid',
      gridTemplateColumns: 'minmax(0,1fr) minmax(0,1.15fr)',
      gap: pc === 'bold' ? 32 : 28,
      alignItems: 'stretch',
    };
  })();

  const mainStyle = ((): CSSProperties => {
    const base: CSSProperties = {
      minWidth: 0,
      flex: stickyForm ? 1 : undefined,
      display: stickyForm ? 'flex' : undefined,
      flexDirection: stickyForm ? 'column' : undefined,
    };
    if (pc === 'soft') {
      return {
        ...base,
        background: '#fff',
        borderRadius: 16,
        padding: 22,
        boxShadow: '0 10px 28px rgba(15,23,42,.08)',
        border: '1px solid rgba(15,23,42,.06)',
      };
    }
    if (pc === 'dark') {
      return {
        ...base,
        background: '#1e293b',
        borderRadius: '0 12px 12px 0',
        padding: 24,
        border: '1px solid #334155',
        borderLeft: 0,
      };
    }
    return base;
  })();

  const rootPad =
    pc === 'dark' ? 8 : pc === 'bold' ? undefined : pc === 'card' ? 28 : pc === 'soft' ? 28 : wideLayout ? 24 : theme.padding;
  const rootMax =
    pc === 'simple' ? 720 : pc === 'card' ? 760 : pc === 'bold' || pc === 'dark' ? 840 : pc === 'soft' ? 820 : wideLayout ? 800 : undefined;

  const introBlock = (
    <div style={introStyle}>
      {showIntroTitle ? (
        <>
          <div
            style={{
              margin: pc === 'simple' ? '0 12px 0 0' : pc === 'card' ? '0 0 6px' : '0 0 4px',
              fontSize: pc === 'soft' ? '1.55rem' : pc === 'dark' || pc === 'card' ? '1.4rem' : wideLayout ? '1.35rem' : '1.125rem',
              fontWeight: 800,
              lineHeight: pc === 'soft' ? 1.3 : undefined,
              letterSpacing: pc === 'soft' ? '-0.03em' : undefined,
              textAlign: pc === 'card' ? 'center' : undefined,
              color: pc === 'dark' ? '#f8fafc' : undefined,
            }}
          >
            {title}
          </div>
          <div
            style={{
              margin: pc === 'simple' ? 0 : wideLayout ? '0 0 18px' : '0 0 10px',
              fontSize: wideLayout ? (pc === 'soft' ? '0.95rem' : '0.9rem') : '0.875rem',
              color: theme.muted,
              flex: pc === 'simple' ? '1 1 100%' : undefined,
              textAlign: pc === 'card' ? 'center' : undefined,
            }}
          >
            빠른 상담을 남겨 주세요.
          </div>
        </>
      ) : null}

      {benefitText ? (
        <div
          style={{
            margin: pc === 'simple' ? '0 0 0 auto' : '0 0 10px',
            fontSize: pc === 'soft' ? '0.95rem' : '0.82rem',
            fontWeight: 700,
            color: theme.accent,
          }}
        >
          {benefitText}
        </div>
      ) : null}

      {showLiveCount ? (
        <div
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: 6,
            margin: pc === 'simple' ? 0 : pc === 'card' ? '0 auto 12px' : '0 0 12px',
            padding: '6px 10px',
            borderRadius: 999,
            background: 'rgba(239,68,68,.08)',
            border: '1px solid rgba(239,68,68,.2)',
            color: '#b91c1c',
            fontSize: '0.72rem',
            fontWeight: 800,
          }}
        >
          <span
            style={{
              width: 7,
              height: 7,
              borderRadius: 999,
              background: '#ef4444',
              boxShadow: '0 0 0 3px rgba(239,68,68,.2)',
            }}
          />
          {liveCountText}
        </div>
      ) : null}

      {badges.length > 0 ? (
        <div
          style={{
            display: 'flex',
            flexWrap: 'wrap',
            gap: 6,
            margin: pc === 'simple' ? 0 : pc === 'card' ? '0 0 14px' : wideLayout ? '0 0 16px' : '0 0 12px',
            justifyContent: pc === 'card' ? 'center' : undefined,
            width: pc === 'card' ? '100%' : undefined,
            padding: pc === 'card' ? '10px 12px' : undefined,
            borderRadius: pc === 'card' ? 14 : undefined,
            background: pc === 'card' ? 'rgba(15,23,42,.03)' : undefined,
            border: pc === 'card' ? `1px solid ${borderTone}` : undefined,
          }}
        >
          {badges.map((b) => (
            <span
              key={b}
              style={{
                padding: '5px 9px',
                borderRadius: 999,
                background: 'rgba(15,23,42,.04)',
                border: `1px solid ${borderTone}`,
                fontSize: '0.7rem',
                fontWeight: 800,
                color: theme.muted,
              }}
            >
              {b}
            </span>
          ))}
        </div>
      ) : null}

      {hasPhone ? (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: pc === 'card' ? 'center' : wideLayout && pc !== 'simple' ? 'flex-start' : 'center',
            gap: 8,
            margin: pc === 'simple' ? 0 : pc === 'card' ? '0 auto' : wideLayout ? 0 : '0 0 14px',
            padding: pc === 'simple' ? '8px 12px' : '11px 12px',
            borderRadius: 12,
            background: pc === 'dark' ? 'rgba(34,211,238,.1)' : 'rgba(5,150,105,.08)',
            border: pc === 'dark' ? '1px solid rgba(34,211,238,.35)' : '1px solid rgba(5,150,105,.22)',
            color: theme.call,
            fontWeight: 800,
            fontSize: pc === 'simple' ? '0.85rem' : '0.9rem',
            width: pc === 'card' ? '100%' : undefined,
            maxWidth: pc === 'card' ? 420 : undefined,
          }}
        >
          {callLabel} <span className="tabular-nums">010-0000-0000</span>
        </div>
      ) : null}
    </div>
  );

  const formBlock = (
    <div style={mainStyle}>
      <div
        style={
          wideLayout
            ? { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0 12px' }
            : undefined
        }
      >
        <Field label="이름" required theme={theme} placeholder="홍길동" />
        <Field label="연락처" required theme={theme} placeholder="010-1234-5678" />
      </div>

      {(showRegion || showInquiry) && minimalForm ? (
        <div
          style={{
            margin: '0 0 12px',
            border: `1px solid ${borderTone}`,
            borderRadius: 12,
            padding: '8px 10px',
            background: theme.inputBg,
            fontSize: '0.8rem',
            fontWeight: 800,
            color: theme.muted,
          }}
        >
          추가 정보 (선택) ▾
        </div>
      ) : (
        <>
          {showRegion ? <Field label="지역" theme={theme} placeholder="서울 / 경기 등" /> : null}
          {showInquiry ? (
            <Field label="문의 내용" theme={theme} placeholder="상담 내용을 적어 주세요." textarea />
          ) : null}
        </>
      )}

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

      <div
        style={
          stickyForm
            ? {
                position: 'sticky',
                bottom: 0,
                marginTop: 'auto',
                paddingTop: 8,
                paddingBottom: 2,
                background: `linear-gradient(180deg,transparent,${theme.bg} 28%)`,
              }
            : undefined
        }
      >
        <button
          type="button"
          style={{
            display: 'flex',
            width: '100%',
            alignItems: 'center',
            justifyContent: 'center',
            border: 0,
            borderRadius: 12,
            padding: pc === 'bold' ? '16px 18px' : '13px 16px',
            fontSize: pc === 'bold' ? '1.05rem' : '0.95rem',
            fontWeight: 800,
            background: theme.accent,
            color: theme.accentText,
            cursor: 'default',
          }}
        >
          {submitLabel}
        </button>
        {ctaHint ? (
          <p style={{ margin: '8px 0 0', fontSize: '0.72rem', color: theme.muted, textAlign: 'center' }}>
            {ctaHint}
          </p>
        ) : null}
        {stickyForm ? (
          <p style={{ margin: '6px 0 0', fontSize: '0.65rem', color: theme.muted, textAlign: 'center' }}>
            모바일 sticky CTA · 스크롤해도 제출 버튼 고정
          </p>
        ) : null}
        <p style={{ margin: '12px 0 0', fontSize: '0.7rem', color: theme.muted, textAlign: 'center' }}>
          {brandName} 상담 위젯 · 미리보기
        </p>
      </div>
    </div>
  );

  return (
    <div
      style={{
        background: theme.bg,
        color: theme.text,
        border: `1px solid ${theme.border}`,
        borderRadius: theme.radius,
        boxShadow: theme.shadow,
        padding: pc === 'bold' ? '0 28px 28px' : rootPad,
        overflow: 'hidden',
        fontFamily:
          '-apple-system,BlinkMacSystemFont,"Apple SD Gothic Neo","Noto Sans KR",sans-serif',
        minHeight: stickyForm ? 520 : undefined,
        maxWidth: rootMax,
        width: '100%',
      }}
    >
      {stage === 'success' ? (
        <SuccessPanel
          theme={theme}
          preset={preset}
          title={title}
          successMessage={successMessage}
          successNextStep={successNextStep}
          redirectUrl={redirectUrl}
          showCall={showCall}
          callLabel={callLabel}
          brandName={brandName}
        />
      ) : (
        <>
          {preset === 'bold' && theme.headerBg ? (
            <div
              style={{
                background: theme.headerBg,
                color: theme.headerText,
                margin: wideLayout ? '0 -28px 24px' : '0 -20px 16px',
                padding: wideLayout ? '28px 32px 24px' : '18px 20px 14px',
              }}
            >
              <div style={{ fontSize: wideLayout ? '1.55rem' : '1.125rem', fontWeight: 800 }}>{title}</div>
              <div style={{ marginTop: wideLayout ? 8 : 4, fontSize: wideLayout ? '0.95rem' : '0.8rem', opacity: 0.9 }}>
                빠른 상담을 남겨 주세요.
              </div>
            </div>
          ) : null}
          <div style={splitStyle}>
            {introBlock}
            {formBlock}
          </div>
        </>
      )}
    </div>
  );
}

function SuccessPanel({
  theme,
  preset,
  title,
  successMessage,
  successNextStep,
  redirectUrl,
  showCall,
  callLabel,
  brandName,
}: {
  theme: ReturnType<typeof embedThemeTokens>;
  preset: EmbedPresetId;
  title: string;
  successMessage: string;
  successNextStep: string;
  redirectUrl: string;
  showCall: boolean;
  callLabel: string;
  brandName: string;
}) {
  const okColor = preset === 'dark' ? '#34d399' : '#047857';
  return (
    <div style={{ textAlign: 'center', padding: '8px 4px 4px' }}>
      {preset === 'bold' && theme.headerBg ? (
        <div
          style={{
            background: theme.headerBg,
            color: theme.headerText,
            margin: '0 -20px 18px',
            padding: '16px 20px',
          }}
        >
          <div style={{ fontSize: '1rem', fontWeight: 800 }}>{title}</div>
        </div>
      ) : (
        <div style={{ margin: '0 0 14px', fontSize: '0.95rem', fontWeight: 800, color: theme.muted }}>{title}</div>
      )}
      <div
        style={{
          width: 56,
          height: 56,
          margin: '0 auto 14px',
          borderRadius: 999,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: preset === 'dark' ? 'rgba(52,211,153,.15)' : 'rgba(16,185,129,.12)',
          color: okColor,
          fontSize: 28,
          fontWeight: 800,
        }}
      >
        ✓
      </div>
      <div style={{ fontSize: '1.05rem', fontWeight: 800, color: theme.text, marginBottom: 8 }}>접수 완료</div>
      <p style={{ margin: '0 0 10px', fontSize: '0.9rem', lineHeight: 1.55, color: okColor, fontWeight: 700 }}>
        {successMessage}
      </p>
      {successNextStep ? (
        <p style={{ margin: '0 0 14px', fontSize: '0.8rem', lineHeight: 1.45, color: theme.muted }}>
          {successNextStep}
        </p>
      ) : null}
      {showCall ? (
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
      {redirectUrl ? (
        <div
          style={{
            margin: '0 0 14px',
            padding: '10px 12px',
            borderRadius: 12,
            background: preset === 'dark' ? '#1e293b' : '#f8fafc',
            border: `1px solid ${theme.border === 'transparent' ? '#e2e8f0' : theme.border}`,
            textAlign: 'left',
          }}
        >
          <div style={{ fontSize: '0.7rem', fontWeight: 700, color: theme.muted, marginBottom: 4 }}>완료 후 이동</div>
          <div style={{ fontSize: '0.75rem', color: theme.accent, wordBreak: 'break-all' }}>{redirectUrl}</div>
        </div>
      ) : null}
      <p style={{ margin: 0, fontSize: '0.7rem', color: theme.muted }}>{brandName} 상담 위젯 · 완료 미리보기</p>
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
