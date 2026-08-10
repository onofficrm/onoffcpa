import { Copy, Download, Monitor, Smartphone, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { HelpTipButton, HelpTipHeading } from '../HelpTipButton';
import { EmbedDesignGallery } from './EmbedDesignGallery';
import { EmbedWidgetLivePreview, type EmbedPreviewStage } from './EmbedWidgetLivePreview';
import { EMBED_HELP } from '../../lib/embedHelpTips';
import {
  normalizeEmbedPreset,
  withEmbedPreset,
  type EmbedPresetId,
} from '../../lib/embedPresets';
import {
  applyEmbedCtaPreset,
  EMBED_CTA_PRESETS,
  type EmbedCtaPresetId,
} from '../../lib/embedConversion';
import {
  buildLeadEmbedPreviewUrl,
  buildLeadEmbedShortcode,
  buildLeadEmbedSnippet,
  fetchPartnerEmbedSettings,
  issuePartnerEmbedWidgetKey,
  LeadEmbedMode,
  leadEmbedPluginDownloadUrl,
  PartnerEmbedOptions,
  rotatePartnerEmbedWidgetKey,
  savePartnerEmbedDomains,
  savePartnerEmbedOptions,
} from '../../lib/partnerEmbed';

const DEFAULT_OPTIONS: PartnerEmbedOptions = {
  preset: 'default',
  accent: '#0d9488',
  title: '무료 상담 신청',
  submitLabel: '지금 무료 상담 받기',
  buttonLabel: '지금 무료 상담 받기',
  callLabel: '전화 상담',
  successMessage: '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.',
  successRedirectUrl: '',
  trackConversion: true,
  conversionEventName: 'lc_lead_submit',
  showRegion: true,
  showInquiry: true,
  privacyText: '개인정보 수집·이용에 동의합니다.',
  requireWidgetKey: false,
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

export type EmbedGuideTabId = 'preset' | 'convert' | 'copy' | 'fields' | 'install';

type Props = {
  open: boolean;
  onClose: () => void;
  lkCode?: string;
  /** 모달 열릴 때 시작할 탭 */
  initialTab?: EmbedGuideTabId;
  onCopySnippet?: (snippet: string) => void;
  /** 옵션 저장 후 목록 상태 갱신 */
  onSaved?: () => void;
};

type TabId = EmbedGuideTabId;

const MODES: Array<{ id: LeadEmbedMode; label: string; desc: string }> = [
  { id: 'form', label: '폼형', desc: '페이지에 상담폼 + 전화' },
  { id: 'button', label: '버튼형', desc: '클릭 시 모달 폼' },
  { id: 'phone', label: '전화형', desc: '안심번호 버튼만' },
];

const TABS: Array<{ id: TabId; label: string }> = [
  { id: 'preset', label: '템플릿' },
  { id: 'convert', label: '전환' },
  { id: 'copy', label: '문구·색상' },
  { id: 'fields', label: '필드·완료' },
  { id: 'install', label: '설치' },
];

export function PartnerWpEmbedGuideModal({
  open,
  onClose,
  lkCode,
  initialTab = 'preset',
  onCopySnippet,
  onSaved,
}: Props) {
  const [domainsText, setDomainsText] = useState('');
  const [phoneHint, setPhoneHint] = useState('');
  const [brandName, setBrandName] = useState('상담');
  const [saving, setSaving] = useState(false);
  const [saveMsg, setSaveMsg] = useState('');
  const [mode, setMode] = useState<LeadEmbedMode>('form');
  const [tab, setTab] = useState<TabId>('preset');
  const [device, setDevice] = useState<'pc' | 'mobile'>('pc');
  const [previewStage, setPreviewStage] = useState<EmbedPreviewStage>('form');
  const [embedToday, setEmbedToday] = useState(0);
  const [embedTotal, setEmbedTotal] = useState(0);
  const [statsDays, setStatsDays] = useState(14);
  const [byDomain, setByDomain] = useState<Array<{ host: string; total: number; today: number }>>([]);
  const [widgetKey, setWidgetKey] = useState('');
  const [keyBusy, setKeyBusy] = useState(false);
  const [options, setOptions] = useState<PartnerEmbedOptions>(DEFAULT_OPTIONS);
  const [savingOptions, setSavingOptions] = useState(false);
  const [previewTick, setPreviewTick] = useState(0);

  useEffect(() => {
    if (!open) return;
    setSaveMsg('');
    setTab(initialTab);
    setPreviewStage('form');
    fetchPartnerEmbedSettings(lkCode || undefined)
      .then((data) => {
        setDomainsText((data.domains || []).join('\n'));
        setEmbedToday(data.embedToday ?? 0);
        setEmbedTotal(data.embedTotal ?? 0);
        setStatsDays(data.statsDays ?? 14);
        setByDomain(data.byDomain || []);
        setWidgetKey(data.widgetKey || '');
        setBrandName(data.brandName || '상담');
        setOptions({
          ...DEFAULT_OPTIONS,
          ...(data.options || {}),
          preset: normalizeEmbedPreset(data.options?.preset),
        });
        const phone = data.config?.partnerPhoneDisplay || '';
        setPhoneHint(
          data.config?.hasPartnerPhone && phone
            ? `위젯에 안심번호 ${phone} 이 함께 표시됩니다.`
            : '현재 배정된 안심번호가 없으면 상담폼만 표시됩니다. (전화형은 번호 배정 필요)',
        );
      })
      .catch(() => {
        setDomainsText('');
        setEmbedToday(0);
        setEmbedTotal(0);
        setByDomain([]);
        setWidgetKey('');
        setOptions(DEFAULT_OPTIONS);
        setPhoneHint('허용 도메인을 등록하면 등록된 사이트에서만 위젯이 동작합니다.');
      });
  }, [open, lkCode, initialTab]);

  const sampleCode = (lkCode || 'YOUR_LK_CODE').trim();
  const snippet = useMemo(
    () => buildLeadEmbedSnippet(sampleCode, { mode, widgetKey }),
    [sampleCode, mode, widgetKey],
  );
  const shortcode = useMemo(
    () =>
      buildLeadEmbedShortcode(sampleCode === 'YOUR_LK_CODE' ? '' : sampleCode, {
        widgetKey,
        mode,
      }),
    [sampleCode, widgetKey, mode],
  );
  const previewUrl = useMemo(() => {
    const base = buildLeadEmbedPreviewUrl(sampleCode, { mode, widgetKey });
    if (!base) return '';
    return `${base}${base.includes('?') ? '&' : '?'}_=${previewTick}`;
  }, [sampleCode, mode, widgetKey, previewTick]);
  const pluginUrl = leadEmbedPluginDownloadUrl();
  const presetId = normalizeEmbedPreset(options.preset);

  if (!open) return null;

  const handleWidgetKey = async (modeAction: 'issue' | 'rotate') => {
    if (modeAction === 'rotate' && !window.confirm('재발급하면 기존 설치 코드는 바로 동작하지 않습니다. 계속할까요?')) {
      return;
    }
    setKeyBusy(true);
    setSaveMsg('');
    try {
      const res =
        modeAction === 'issue' ? await issuePartnerEmbedWidgetKey() : await rotatePartnerEmbedWidgetKey();
      setWidgetKey(res.widgetKey || '');
      setSaveMsg(res.message || (modeAction === 'issue' ? '위젯 키를 발급했습니다.' : '위젯 키를 재발급했습니다.'));
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '위젯 키 처리에 실패했습니다.');
    } finally {
      setKeyBusy(false);
    }
  };

  const handleSaveDomains = async () => {
    setSaving(true);
    setSaveMsg('');
    try {
      const domains = domainsText
        .split(/[\n,]+/)
        .map((v) => v.trim())
        .filter(Boolean);
      const res = await savePartnerEmbedDomains(domains);
      setDomainsText((res.domains || []).join('\n'));
      setSaveMsg(res.message || '저장되었습니다.');
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '저장에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  const handleSaveOptions = async () => {
    setSavingOptions(true);
    setSaveMsg('');
    try {
      const res = await savePartnerEmbedOptions({
        ...options,
        preset: normalizeEmbedPreset(options.preset),
      });
      setOptions({
        ...DEFAULT_OPTIONS,
        ...(res.options || {}),
        preset: normalizeEmbedPreset(res.options?.preset),
      });
      if (res.widgetKey) setWidgetKey(res.widgetKey);
      setPreviewTick((n) => n + 1);
      setSaveMsg(res.message || '위젯 설정을 저장했습니다. 설치 코드에 반영됩니다.');
      onSaved?.();
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '위젯 설정 저장에 실패했습니다.');
    } finally {
      setSavingOptions(false);
    }
  };

  const selectPreset = (id: EmbedPresetId) => {
    setOptions((prev) => withEmbedPreset(prev, id, { applyAccentHint: true }));
  };

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-sm"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-3xl shadow-xl w-full max-w-6xl overflow-hidden max-h-[94vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="wp-embed-guide-title"
      >
        <div className="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
          <div>
            <div className="flex items-center gap-1.5 flex-wrap">
              <h3 id="wp-embed-guide-title" className="text-lg font-bold text-slate-900">
                외부 홈페이지 상담 위젯
              </h3>
              <HelpTipButton title={EMBED_HELP.overview.title}>{EMBED_HELP.overview.body}</HelpTipButton>
            </div>
            <p className="text-sm text-slate-500 mt-0.5">디자인 프리셋 · 실시간 미리보기 · HTML 설치</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg"
            aria-label="닫기"
          >
            <X size={20} />
          </button>
        </div>

        <div className="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(320px,400px)]">
          <div className="overflow-y-auto p-5 sm:p-6 space-y-5 border-b lg:border-b-0 lg:border-r border-slate-100">
            <section className="rounded-2xl border border-cyan-200 bg-cyan-50/70 p-3.5 space-y-2">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="text-sm font-bold text-cyan-950">위젯 형태</div>
                <div className="text-[11px] text-cyan-900/70">
                  오늘 {embedToday} · 누적 {embedTotal} · 최근 {statsDays}일
                </div>
              </div>
              <div className="grid grid-cols-3 gap-2">
                {MODES.map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => {
                      setMode(item.id);
                      if (item.id === 'phone') setPreviewStage('form');
                    }}
                    className={`rounded-xl border px-2 py-2 text-left transition-colors ${
                      mode === item.id
                        ? 'border-cyan-500 bg-white text-cyan-900 shadow-sm'
                        : 'border-cyan-100 bg-cyan-50/40 text-slate-600 hover:bg-white'
                    }`}
                  >
                    <div className="text-xs font-bold">{item.label}</div>
                    <div className="text-[10px] mt-0.5 leading-snug opacity-80">{item.desc}</div>
                  </button>
                ))}
              </div>
              <p className="text-xs text-cyan-900/80">{phoneHint}</p>
            </section>

            <div className="flex gap-1 p-1 rounded-xl bg-slate-100">
              {TABS.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => setTab(item.id)}
                  className={`flex-1 rounded-lg px-2 py-2 text-xs sm:text-sm font-bold transition-colors ${
                    tab === item.id ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'
                  }`}
                >
                  {item.label}
                </button>
              ))}
            </div>

            {tab === 'preset' ? (
              <section className="space-y-3">
                <HelpTipHeading title="디자인 템플릿" helpTitle={EMBED_HELP.design.title}>
                  {EMBED_HELP.design.body}
                </HelpTipHeading>
                <p className="text-xs text-slate-500 leading-relaxed">
                  디자인별 미리보기를 비교해 고르세요. 선택하면 우측 큰 미리보기·강조색이 바로 바뀌며, 문구·색상 탭에서 세부 수정할 수 있습니다.
                </p>
                <EmbedDesignGallery
                  selectedId={presetId}
                  options={options}
                  brandName={brandName}
                  phoneHint={phoneHint}
                  onSelect={selectPreset}
                />
              </section>
            ) : null}

            {tab === 'convert' ? (
              <section className="space-y-4">
                <div>
                  <div className="text-sm font-bold text-slate-900">전환율 최적화</div>
                  <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                    업종 CTA · 신뢰 배지 · 미니멀 폼 · 모바일 sticky 제출을 켜면 미리보기에 바로 반영됩니다.
                  </p>
                </div>
                <div className="space-y-2">
                  <div className="text-xs font-bold text-slate-600">업종별 CTA 프리셋</div>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {EMBED_CTA_PRESETS.map((cta) => (
                      <button
                        key={cta.id}
                        type="button"
                        onClick={() => setOptions((prev) => applyEmbedCtaPreset(prev, cta.id as EmbedCtaPresetId))}
                        className="rounded-xl border border-slate-200 bg-white hover:border-cyan-400 px-2.5 py-2 text-left"
                      >
                        <div className="text-xs font-bold text-slate-900">{cta.label}</div>
                        <div className="text-[10px] text-slate-500 mt-0.5">{cta.desc}</div>
                      </button>
                    ))}
                  </div>
                </div>
                <div className="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                  {[
                    { key: 'minimalForm', label: '미니멀 폼', desc: '이름·연락처만 먼저, 추가항목 접기' },
                    { key: 'showTrustBadges', label: '신뢰 배지', desc: '무료 / 3분콜백 / 비밀보장' },
                    { key: 'showLiveCount', label: '시급성 문구', desc: '상담 활발 안내' },
                    { key: 'stickyMobileCta', label: '모바일 sticky CTA', desc: '제출 버튼을 하단 고정' },
                    { key: 'successShowCall', label: '완료 화면 전화 CTA', desc: '접수 후 전화 버튼 노출' },
                  ].map((item) => (
                    <label key={item.key} className="flex items-start gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        className="mt-0.5 rounded border-slate-300 text-cyan-600"
                        checked={options[item.key as keyof PartnerEmbedOptions] !== false}
                        onChange={(e) =>
                          setOptions((prev) => ({ ...prev, [item.key]: e.target.checked }))
                        }
                      />
                      <span className="text-xs text-slate-700 leading-relaxed">
                        <span className="font-bold">{item.label}</span>
                        <span className="block text-slate-500">{item.desc}</span>
                      </span>
                    </label>
                  ))}
                </div>
                {options.showTrustBadges !== false ? (
                  <div className="flex flex-wrap gap-3 text-xs text-slate-700">
                    {[
                      { key: 'badgeFree', label: '상담비 없음' },
                      { key: 'badgeCallback', label: '3분 내 연락' },
                      { key: 'badgePrivacy', label: '비밀보장' },
                    ].map((b) => (
                      <label key={b.key} className="inline-flex items-center gap-1.5">
                        <input
                          type="checkbox"
                          className="rounded border-slate-300 text-cyan-600"
                          checked={options[b.key as keyof PartnerEmbedOptions] !== false}
                          onChange={(e) => setOptions((prev) => ({ ...prev, [b.key]: e.target.checked }))}
                        />
                        {b.label}
                      </label>
                    ))}
                  </div>
                ) : null}
                <div className="grid grid-cols-[auto_1fr] gap-3 items-center">
                  <label className="text-xs font-bold text-slate-600">혜택 한 줄</label>
                  <input
                    type="text"
                    value={options.benefitText || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, benefitText: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm"
                    placeholder="상담비 없음 · 3분 내 연락"
                  />
                  <label className="text-xs font-bold text-slate-600">버튼 보조문구</label>
                  <input
                    type="text"
                    value={options.ctaHint || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, ctaHint: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm"
                    placeholder="영업전화 없음 · 개인정보 안전"
                  />
                  <label className="text-xs font-bold text-slate-600">시급성 문구</label>
                  <input
                    type="text"
                    value={options.liveCountText || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, liveCountText: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm"
                    placeholder="지금 상담 신청이 활발합니다"
                  />
                </div>
              </section>
            ) : null}

            {tab === 'copy' ? (
              <section className="space-y-3">
                <div className="text-sm font-bold text-slate-900">문구 · 색상</div>
                <div className="grid grid-cols-[auto_1fr] gap-3 items-center">
                  <label className="text-xs font-bold text-slate-600">강조색</label>
                  <div className="flex items-center gap-2">
                    <input
                      type="color"
                      value={options.accent || '#0d9488'}
                      onChange={(e) => setOptions((prev) => ({ ...prev, accent: e.target.value }))}
                      className="h-9 w-12 rounded-lg border border-slate-200 bg-white p-1"
                    />
                    <input
                      type="text"
                      value={options.accent || ''}
                      onChange={(e) => setOptions((prev) => ({ ...prev, accent: e.target.value }))}
                      className="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                      placeholder="#0d9488"
                    />
                  </div>
                  <label className="text-xs font-bold text-slate-600">제목</label>
                  <input
                    type="text"
                    value={options.title || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, title: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                  <label className="text-xs font-bold text-slate-600">제출 버튼</label>
                  <input
                    type="text"
                    value={options.submitLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, submitLabel: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                  <label className="text-xs font-bold text-slate-600">버튼형 라벨</label>
                  <input
                    type="text"
                    value={options.buttonLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, buttonLabel: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                  <label className="text-xs font-bold text-slate-600">전화 라벨</label>
                  <input
                    type="text"
                    value={options.callLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, callLabel: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                  <label className="text-xs font-bold text-slate-600">동의 문구</label>
                  <input
                    type="text"
                    value={options.privacyText || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, privacyText: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                </div>
              </section>
            ) : null}

            {tab === 'fields' ? (
              <section className="space-y-3">
                <div className="flex items-center justify-between gap-2 flex-wrap">
                  <div className="text-sm font-bold text-slate-900">필드 · 완료 후 동작</div>
                  <button
                    type="button"
                    onClick={() => setPreviewStage('success')}
                    className="text-[11px] font-bold text-cyan-700 hover:text-cyan-800 underline underline-offset-2"
                  >
                    완료 화면 미리보기 →
                  </button>
                </div>
                <div className="flex flex-wrap gap-4 text-sm text-slate-700">
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={options.showRegion !== false}
                      onChange={(e) => setOptions((prev) => ({ ...prev, showRegion: e.target.checked }))}
                      className="rounded border-slate-300 text-cyan-600"
                    />
                    지역 필드
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={options.showInquiry !== false}
                      onChange={(e) => setOptions((prev) => ({ ...prev, showInquiry: e.target.checked }))}
                      className="rounded border-slate-300 text-cyan-600"
                    />
                    문의 내용
                  </label>
                  <span className="text-xs text-slate-400 self-center">이름·연락처는 항상 표시</span>
                </div>
                <div className="grid grid-cols-[auto_1fr] gap-3 items-center">
                  <label className="text-xs font-bold text-slate-600">완료 문구</label>
                  <input
                    type="text"
                    value={options.successMessage || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, successMessage: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                  />
                  <label className="text-xs font-bold text-slate-600">다음 안내</label>
                  <input
                    type="text"
                    value={options.successNextStep || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, successNextStep: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="담당자가 확인 후 곧 연락드립니다."
                  />
                  <label className="text-xs font-bold text-slate-600">완료 후 URL</label>
                  <input
                    type="url"
                    value={options.successRedirectUrl || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, successRedirectUrl: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                    placeholder="https://example.com/thanks"
                  />
                  <label className="text-xs font-bold text-slate-600">전환 추적</label>
                  <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input
                      type="checkbox"
                      checked={options.trackConversion !== false}
                      onChange={(e) => setOptions((prev) => ({ ...prev, trackConversion: e.target.checked }))}
                      className="rounded border-slate-300 text-cyan-600"
                    />
                    GTM dataLayer 이벤트 전송
                  </label>
                  <label className="text-xs font-bold text-slate-600">이벤트명</label>
                  <input
                    type="text"
                    value={options.conversionEventName || 'lc_lead_submit'}
                    onChange={(e) => setOptions((prev) => ({ ...prev, conversionEventName: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                  />
                </div>
              </section>
            ) : null}

            {tab === 'install' ? (
              <section className="space-y-4">
                <div className="space-y-2">
                  <HelpTipHeading title="HTML 설치 코드" helpTitle={EMBED_HELP.modes.title}>
                    {EMBED_HELP.modes.body}
                  </HelpTipHeading>
                  <pre className="text-[11px] break-all whitespace-pre-wrap bg-slate-900 text-slate-100 rounded-xl p-3 font-mono max-h-36 overflow-y-auto">
                    {snippet}
                  </pre>
                  {onCopySnippet && lkCode ? (
                    <button
                      type="button"
                      onClick={() => onCopySnippet(snippet)}
                      className="w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold"
                    >
                      <Copy size={16} />
                      HTML 설치 코드 복사
                    </button>
                  ) : null}
                </div>

                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-3">
                  <HelpTipHeading
                    title="워드프레스"
                    helpTitle={EMBED_HELP.wordpress.title}
                    className="text-sm font-bold text-emerald-900"
                  >
                    {EMBED_HELP.wordpress.body}
                  </HelpTipHeading>
                  <div className="flex flex-col sm:flex-row gap-2">
                    <a
                      href={pluginUrl}
                      className="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500"
                      download
                    >
                      <Download size={16} />
                      플러그인 zip
                    </a>
                    {onCopySnippet ? (
                      <button
                        type="button"
                        onClick={() => onCopySnippet(shortcode)}
                        className="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl border border-emerald-300 bg-white text-emerald-800 text-sm font-bold"
                      >
                        <Copy size={16} />
                        숏코드 복사
                      </button>
                    ) : null}
                  </div>
                  <pre className="text-[11px] break-all whitespace-pre-wrap bg-white/80 text-emerald-950 rounded-xl p-3 font-mono border border-emerald-100">
                    {shortcode}
                  </pre>
                </div>

                <div className="space-y-2">
                  <HelpTipHeading title="위젯 키" helpTitle={EMBED_HELP.widgetKey.title}>
                    {EMBED_HELP.widgetKey.body}
                  </HelpTipHeading>
                  <label
                    className={`flex items-start gap-2 rounded-xl border border-violet-100 bg-violet-50/50 px-3 py-2.5 cursor-pointer ${savingOptions ? 'opacity-60' : ''}`}
                  >
                    <input
                      type="checkbox"
                      disabled={savingOptions}
                      checked={!!options.requireWidgetKey}
                      onChange={(e) => {
                        const requireWidgetKey = e.target.checked;
                        setOptions((prev) => ({ ...prev, requireWidgetKey }));
                        void (async () => {
                          setSavingOptions(true);
                          setSaveMsg('');
                          try {
                            const res = await savePartnerEmbedOptions({ ...options, requireWidgetKey });
                            setOptions({
                              ...DEFAULT_OPTIONS,
                              ...(res.options || {}),
                              preset: normalizeEmbedPreset(res.options?.preset),
                            });
                            if (res.widgetKey) setWidgetKey(res.widgetKey);
                            setSaveMsg(
                              res.message ||
                                (requireWidgetKey ? '위젯 키 필수를 켰습니다.' : '위젯 키 필수를 해제했습니다.'),
                            );
                          } catch (err) {
                            setOptions((prev) => ({ ...prev, requireWidgetKey: !requireWidgetKey }));
                            setSaveMsg(err instanceof Error ? err.message : '위젯 키 필수 설정에 실패했습니다.');
                          } finally {
                            setSavingOptions(false);
                          }
                        })();
                      }}
                      className="mt-0.5 rounded border-slate-300"
                    />
                    <span className="text-xs text-slate-700 leading-relaxed">
                      <span className="font-bold text-violet-900">위젯 키 필수</span>
                      <span className="block text-slate-500 mt-0.5">켜면 키 없는 설치는 차단됩니다.</span>
                    </span>
                  </label>
                  {widgetKey ? (
                    <div className="rounded-xl border border-violet-200 bg-violet-50/70 p-3 space-y-2">
                      <code className="block text-[11px] font-mono text-violet-900 break-all">{widgetKey}</code>
                      <button
                        type="button"
                        disabled={keyBusy}
                        onClick={() => void handleWidgetKey('rotate')}
                        className="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 disabled:opacity-60"
                      >
                        키 재발급
                      </button>
                    </div>
                  ) : (
                    <button
                      type="button"
                      disabled={keyBusy}
                      onClick={() => void handleWidgetKey('issue')}
                      className="w-full py-2.5 rounded-xl bg-violet-700 hover:bg-violet-600 text-white text-sm font-bold disabled:opacity-60"
                    >
                      {keyBusy ? '처리 중...' : '위젯 키 발급'}
                    </button>
                  )}
                </div>

                <div className="space-y-2">
                  <HelpTipHeading title="허용 도메인" helpTitle={EMBED_HELP.domains.title}>
                    {EMBED_HELP.domains.body}
                  </HelpTipHeading>
                  <textarea
                    value={domainsText}
                    onChange={(e) => setDomainsText(e.target.value)}
                    rows={3}
                    placeholder={'example.com\nmyshop.kr.kr'}
                    className="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                  />
                  <button
                    type="button"
                    disabled={saving}
                    onClick={() => void handleSaveDomains()}
                    className="w-full py-2.5 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-sm font-bold disabled:opacity-60"
                  >
                    {saving ? '저장 중...' : '허용 도메인 저장'}
                  </button>
                  {byDomain.length > 0 ? (
                    <div className="space-y-1.5 pt-1">
                      {byDomain.slice(0, 5).map((row) => (
                        <div key={row.host} className="flex items-center justify-between gap-2 text-xs">
                          <span className="font-mono text-slate-600 truncate" title={row.host}>
                            {row.host}
                          </span>
                          <span className="tabular-nums text-slate-800 font-semibold shrink-0">{row.total}건</span>
                        </div>
                      ))}
                    </div>
                  ) : null}
                </div>

                {previewUrl ? (
                  <div className="rounded-xl border border-dashed border-slate-200 bg-white overflow-hidden">
                    <div className="px-3 py-1.5 text-[10px] font-bold text-slate-500 border-b border-slate-100">
                      서버 미리보기 · 저장 후 실제 위젯 (테스트 접수도 DB 반영)
                    </div>
                    <iframe
                      title="상담 위젯 서버 미리보기"
                      src={previewUrl}
                      className="w-full bg-white"
                      style={{ height: mode === 'phone' ? 120 : 420, border: 0 }}
                    />
                  </div>
                ) : null}
              </section>
            ) : null}

            {(tab === 'preset' || tab === 'convert' || tab === 'copy' || tab === 'fields') && (
              <button
                type="button"
                disabled={savingOptions}
                onClick={() => void handleSaveOptions()}
                className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold disabled:opacity-60"
              >
                {savingOptions ? '저장 중...' : '디자인·문구 설정 저장'}
              </button>
            )}
            {saveMsg ? <p className="text-xs text-slate-600">{saveMsg}</p> : null}
          </div>

          <aside className="bg-slate-50 p-4 sm:p-5 overflow-y-auto flex flex-col gap-3">
            <div className="flex items-center justify-between gap-2 flex-wrap">
              <div className="text-sm font-bold text-slate-900">실시간 미리보기</div>
              <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
                <button
                  type="button"
                  onClick={() => setDevice('pc')}
                  className={`inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-[11px] font-bold ${
                    device === 'pc' ? 'bg-slate-900 text-white' : 'text-slate-500'
                  }`}
                >
                  <Monitor size={13} /> PC
                </button>
                <button
                  type="button"
                  onClick={() => setDevice('mobile')}
                  className={`inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-[11px] font-bold ${
                    device === 'mobile' ? 'bg-slate-900 text-white' : 'text-slate-500'
                  }`}
                >
                  <Smartphone size={13} /> 모바일
                </button>
              </div>
            </div>
            <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 self-start">
              <button
                type="button"
                onClick={() => setPreviewStage('form')}
                className={`px-3 py-1.5 rounded-md text-[11px] font-bold ${
                  previewStage === 'form' ? 'bg-cyan-700 text-white' : 'text-slate-500'
                }`}
              >
                입력 폼
              </button>
              <button
                type="button"
                onClick={() => setPreviewStage('success')}
                disabled={mode === 'phone'}
                className={`px-3 py-1.5 rounded-md text-[11px] font-bold disabled:opacity-40 ${
                  previewStage === 'success' ? 'bg-cyan-700 text-white' : 'text-slate-500'
                }`}
              >
                완료 화면
              </button>
            </div>
            <p className="text-[11px] text-slate-500 leading-relaxed">
              {previewStage === 'success'
                ? '접수 성공 후 고객에게 보이는 안내입니다. 완료 문구·이동 URL이 여기에 반영됩니다.'
                : '설정 변경이 즉시 반영됩니다. 실제 사이트 적용 전에는 「디자인·문구 설정 저장」을 눌러 주세요.'}
            </p>
            <EmbedWidgetLivePreview
              mode={mode}
              options={options}
              device={device}
              stage={previewStage}
              phoneHint={phoneHint}
              brandName={brandName}
            />
          </aside>
        </div>

        <div className="px-5 sm:px-6 py-3.5 bg-white border-t border-slate-100 shrink-0 flex flex-col sm:flex-row gap-2">
          {(tab === 'preset' || tab === 'convert' || tab === 'copy' || tab === 'fields') && (
            <button
              type="button"
              disabled={savingOptions}
              onClick={() => void handleSaveOptions()}
              className="flex-1 py-3 bg-cyan-700 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-60"
            >
              {savingOptions ? '저장 중...' : '설정 저장'}
            </button>
          )}
          {onCopySnippet && lkCode ? (
            <button
              type="button"
              onClick={() => onCopySnippet(snippet)}
              className="flex-1 py-3 bg-slate-900 text-white font-bold rounded-xl text-sm inline-flex items-center justify-center gap-1.5"
            >
              <Copy size={16} />
              HTML 복사
            </button>
          ) : null}
          <button
            type="button"
            onClick={onClose}
            className="sm:w-28 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm"
          >
            닫기
          </button>
        </div>
      </div>
    </div>
  );
}
