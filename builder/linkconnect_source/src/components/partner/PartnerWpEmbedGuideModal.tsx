import { Copy, Download, Monitor, Phone, Smartphone, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { HelpTipButton, HelpTipHeading } from '../HelpTipButton';
import { EmbedDesignGallery } from './EmbedDesignGallery';
import { EmbedDevicePreviewFrame } from './EmbedDevicePreviewFrame';
import { EmbedWidgetLivePreview, type EmbedPreviewStage } from './EmbedWidgetLivePreview';
import { EMBED_HELP } from '../../lib/embedHelpTips';
import {
  EMBED_PC_LAYOUTS,
  normalizeEmbedPreset,
  normalizeEmbedPcLayout,
  withEmbedPreset,
  type EmbedPcLayoutId,
  type EmbedPresetId,
} from '../../lib/embedPresets';
import {
  applyEmbedCtaPreset,
  applyEmbedIndustryPackage,
  EMBED_CRO_EVENTS,
  EMBED_CTA_PRESETS,
  EMBED_INDUSTRY_PACKAGES,
  type EmbedCtaPresetId,
  type EmbedIndustryPackageId,
} from '../../lib/embedConversion';
import {
  claimPartnerCallNumber,
  fetchPartnerAvailableCallNumbers,
  formatCallPhone,
  type PartnerAvailableCallNumber,
} from '../../lib/api';
import {
  buildLeadEmbedPreviewUrl,
  buildLeadEmbedShortcode,
  buildLeadEmbedSnippet,
  clearPartnerEmbedCampaignOptions,
  fetchPartnerEmbedSettings,
  issuePartnerEmbedWidgetKey,
  LeadEmbedMode,
  leadEmbedPluginDownloadUrl,
  PartnerEmbedAb,
  PartnerEmbedOptions,
  rotatePartnerEmbedWidgetKey,
  savePartnerEmbedDomains,
  savePartnerEmbedOptions,
} from '../../lib/partnerEmbed';

const DEFAULT_AB: PartnerEmbedAb = {
  enabled: false,
  split: 50,
  b: {
    title: '지금 바로 상담 신청',
    submitLabel: '1분 만에 상담 받기',
    benefitText: '상담비 없음 · 빠른 연락',
    ctaHint: '부담 없이 남겨 주세요',
  },
};

const DEFAULT_OPTIONS: PartnerEmbedOptions = {
  preset: 'default',
  pcLayout: 'auto',
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
  showFormCall: true,
  successNextStep: '담당자가 확인 후 곧 연락드립니다.',
};

export type EmbedGuideTabId = 'preset' | 'convert' | 'copy' | 'fields' | 'install';

export type EmbedGuideProductContext = {
  campaignTitle?: string;
  channel?: string;
  linkName?: string;
};

type Props = {
  open: boolean;
  onClose: () => void;
  lkCode?: string;
  /** 모달 열릴 때 시작할 탭 */
  initialTab?: EmbedGuideTabId;
  /** 홍보링크 행에서 열 때 상품·채널 맥락 */
  productContext?: EmbedGuideProductContext | null;
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
  productContext = null,
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
  const [callCampaignId, setCallCampaignId] = useState(0);
  const [callCampaignTitle, setCallCampaignTitle] = useState('');
  const [callPhoneDisplay, setCallPhoneDisplay] = useState('');
  const [hasCallPhone, setHasCallPhone] = useState(false);
  const [availableNumbers, setAvailableNumbers] = useState<PartnerAvailableCallNumber[]>([]);
  const [claimCnId, setClaimCnId] = useState('');
  const [claimBusy, setClaimBusy] = useState(false);
  const [optionScope, setOptionScope] = useState<'partner' | 'campaign'>('partner');
  const [hasCampaignOverride, setHasCampaignOverride] = useState(false);
  const [partnerOptions, setPartnerOptions] = useState<PartnerEmbedOptions>(DEFAULT_OPTIONS);
  const [campaignOptionsState, setCampaignOptionsState] = useState<PartnerEmbedOptions | null>(null);
  const [ab, setAb] = useState<PartnerEmbedAb>(DEFAULT_AB);
  const [previewAbVariant, setPreviewAbVariant] = useState<'A' | 'B'>('A');

  const loadEmbedSettings = (code?: string) => {
    fetchPartnerEmbedSettings(code || undefined)
      .then((data) => {
        setDomainsText((data.domains || []).join('\n'));
        setEmbedToday(data.embedToday ?? 0);
        setEmbedTotal(data.embedTotal ?? 0);
        setStatsDays(data.statsDays ?? 14);
        setByDomain(data.byDomain || []);
        setWidgetKey(data.widgetKey || '');
        setBrandName(data.brandName || '상담');
        const partnerOpts: PartnerEmbedOptions = {
          ...DEFAULT_OPTIONS,
          ...(data.options || {}),
          preset: normalizeEmbedPreset(data.options?.preset),
          pcLayout: normalizeEmbedPcLayout(data.options?.pcLayout),
        };
        setPartnerOptions(partnerOpts);
        const hasOverride = Boolean(data.hasCampaignOverride && data.campaignOptions);
        setHasCampaignOverride(hasOverride);
        const resolvedSrc = hasOverride
          ? data.resolvedOptions || data.campaignOptions || partnerOpts
          : partnerOpts;
        const normalizedResolved = {
          ...DEFAULT_OPTIONS,
          ...resolvedSrc,
          preset: normalizeEmbedPreset(resolvedSrc?.preset),
          pcLayout: normalizeEmbedPcLayout(resolvedSrc?.pcLayout),
        };
        setCampaignOptionsState(hasOverride ? normalizedResolved : null);
        setOptions(normalizedResolved);
        setOptionScope(hasOverride ? 'campaign' : 'partner');
        setAb({
          ...DEFAULT_AB,
          ...(data.ab || {}),
          b: { ...DEFAULT_AB.b, ...(data.ab?.b || {}) },
          split: data.ab?.split ?? 50,
          enabled: Boolean(data.ab?.enabled),
        });
        setPreviewAbVariant('A');
        const phone = (data.config?.partnerPhoneDisplay || '').trim();
        const hasPhone = Boolean(data.config?.hasPartnerPhone && phone);
        const cpId = Number(data.config?.campaignId || data.campaignId || 0);
        const cpTitle = (data.config?.campaignTitle || '').trim();
        setHasCallPhone(hasPhone);
        setCallPhoneDisplay(phone);
        setCallCampaignId(cpId);
        setCallCampaignTitle(cpTitle);
        setPhoneHint(
          hasPhone
            ? `위젯에 콜디비 안심번호 ${phone} 이 함께 표시됩니다.`
            : code
              ? '이 상품에 배정된 콜디비 안심번호가 없습니다. 아래에서 번호를 적용하세요.'
              : '홍보링크를 선택한 뒤 콜디비 안심번호를 적용할 수 있습니다.',
        );
        if (!hasPhone && cpId > 0) {
          fetchPartnerAvailableCallNumbers()
            .then((res) => {
              setAvailableNumbers(res.items || []);
              if (res.items?.length && !claimCnId) {
                setClaimCnId(String(res.items[0].cnId));
              }
            })
            .catch(() => setAvailableNumbers([]));
        } else {
          setAvailableNumbers([]);
        }
      })
      .catch(() => {
        setDomainsText('');
        setEmbedToday(0);
        setEmbedTotal(0);
        setByDomain([]);
        setWidgetKey('');
        setOptions(DEFAULT_OPTIONS);
        setPartnerOptions(DEFAULT_OPTIONS);
        setCampaignOptionsState(null);
        setHasCampaignOverride(false);
        setOptionScope('partner');
        setAb(DEFAULT_AB);
        setHasCallPhone(false);
        setCallPhoneDisplay('');
        setCallCampaignId(0);
        setCallCampaignTitle('');
        setAvailableNumbers([]);
        setPhoneHint('허용 도메인을 등록하면 등록된 사이트에서만 위젯이 동작합니다.');
      });
  };

  useEffect(() => {
    if (!open) return;
    setSaveMsg('');
    setTab(initialTab);
    setDevice(productContext?.campaignTitle ? 'mobile' : 'pc');
    setPreviewStage('form');
    loadEmbedSettings(lkCode || undefined);
  }, [open, lkCode, initialTab, productContext?.campaignTitle]);

  const handleClaimCallNumber = async () => {
    if (!callCampaignId || !claimCnId) return;
    setClaimBusy(true);
    setSaveMsg('');
    try {
      const res = await claimPartnerCallNumber({
        cpId: callCampaignId,
        cnId: Number(claimCnId),
        memo: '상담 위젯용 콜디비',
      });
      setSaveMsg(res.message || '콜디비 안심번호를 적용했습니다.');
      loadEmbedSettings(lkCode || undefined);
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '콜디비 번호 적용에 실패했습니다.');
    } finally {
      setClaimBusy(false);
    }
  };
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
      const payload = {
        ...options,
        preset: normalizeEmbedPreset(options.preset),
        pcLayout: normalizeEmbedPcLayout(options.pcLayout),
      };
      const saveCampaign = optionScope === 'campaign' && callCampaignId > 0;
      const res = await savePartnerEmbedOptions(payload, {
        ...(saveCampaign ? { campaignId: callCampaignId } : {}),
        ab,
      });
      const partnerOpts = {
        ...DEFAULT_OPTIONS,
        ...(res.options || (saveCampaign ? partnerOptions : payload)),
        preset: normalizeEmbedPreset(
          res.options?.preset || (saveCampaign ? partnerOptions.preset : payload.preset),
        ),
        pcLayout: normalizeEmbedPcLayout(
          res.options?.pcLayout || (saveCampaign ? partnerOptions.pcLayout : payload.pcLayout),
        ),
      };
      setPartnerOptions(partnerOpts);
      if (saveCampaign) {
        const resolved = {
          ...DEFAULT_OPTIONS,
          ...(res.resolvedOptions || res.campaignOptions || payload),
          preset: normalizeEmbedPreset(
            (res.resolvedOptions || res.campaignOptions || payload).preset,
          ),
          pcLayout: normalizeEmbedPcLayout(
            (res.resolvedOptions || res.campaignOptions || payload).pcLayout,
          ),
        };
        setOptions(resolved);
        setCampaignOptionsState(resolved);
        setHasCampaignOverride(true);
        setOptionScope('campaign');
      } else {
        setOptions(partnerOpts);
      }
      if (res.ab) {
        setAb({
          ...DEFAULT_AB,
          ...res.ab,
          b: { ...DEFAULT_AB.b, ...(res.ab.b || {}) },
        });
      }
      if (res.widgetKey) setWidgetKey(res.widgetKey);
      setPreviewTick((n) => n + 1);
      setSaveMsg(
        res.message ||
          (saveCampaign
            ? '이 상품 전용 설정을 저장했습니다.'
            : '위젯 설정을 저장했습니다. 설치 코드에 반영됩니다.'),
      );
      onSaved?.();
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '위젯 설정 저장에 실패했습니다.');
    } finally {
      setSavingOptions(false);
    }
  };

  const handleClearCampaignOptions = async () => {
    if (!callCampaignId) return;
    if (!window.confirm('이 상품 전용 설정을 삭제하고 공통 설정을 사용할까요?')) return;
    setSavingOptions(true);
    setSaveMsg('');
    try {
      const res = await clearPartnerEmbedCampaignOptions(callCampaignId);
      const partnerOpts = {
        ...DEFAULT_OPTIONS,
        ...(res.options || partnerOptions),
        preset: normalizeEmbedPreset(res.options?.preset || partnerOptions.preset),
        pcLayout: normalizeEmbedPcLayout(res.options?.pcLayout || partnerOptions.pcLayout),
      };
      setPartnerOptions(partnerOpts);
      setCampaignOptionsState(null);
      setOptions(partnerOpts);
      setHasCampaignOverride(false);
      setOptionScope('partner');
      setSaveMsg(res.message || '상품 전용 설정을 삭제했습니다.');
      setPreviewTick((n) => n + 1);
      onSaved?.();
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '상품 전용 설정 삭제에 실패했습니다.');
    } finally {
      setSavingOptions(false);
    }
  };

  const handleCopyHtml = () => {
    if (!onCopySnippet || !lkCode) return;
    const needsPhone = mode === 'phone' || options.showFormCall !== false;
    if (needsPhone && !hasCallPhone) {
      if (mode === 'phone') {
        setSaveMsg('전화형 위젯은 콜디비 안심번호 배정이 필요합니다. 위에서 번호를 적용하세요.');
        return;
      }
      const ok = window.confirm(
        '콜디비 안심번호가 배정되지 않았습니다. 폼에 전화 버튼이 비거나 숨겨질 수 있습니다. 그래도 HTML을 복사할까요?',
      );
      if (!ok) {
        setSaveMsg('번호를 적용한 뒤 다시 복사해 주세요.');
        return;
      }
    }
    onCopySnippet(snippet);
  };

  const selectPreset = (id: EmbedPresetId) => {
    setOptions((prev) => withEmbedPreset(prev, id, { applyAccentHint: true }));
  };

  const previewOptions = useMemo(() => {
    if (!ab.enabled || previewAbVariant !== 'B') return options;
    return { ...options, ...(ab.b || {}) };
  }, [options, ab, previewAbVariant]);

  const productTitle = (productContext?.campaignTitle || '').trim();
  const productChannel = (productContext?.channel || '').trim();
  const productLinkName = (productContext?.linkName || '').trim();
  const productLabel = productTitle || undefined;
  const contextBits = [productChannel, productLinkName].filter(Boolean);

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
            {productTitle ? (
              <p className="text-sm text-cyan-800 mt-0.5 font-semibold">
                {productTitle}용으로 보는 중
                {contextBits.length ? (
                  <span className="font-normal text-slate-500"> · {contextBits.join(' · ')}</span>
                ) : null}
              </p>
            ) : (
              <p className="text-sm text-slate-500 mt-0.5">디자인 프리셋 · 실시간 미리보기 · HTML 설치</p>
            )}
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

        <div className="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(340px,440px)]">
          <div className="overflow-y-auto p-5 sm:p-6 space-y-5 border-b lg:border-b-0 lg:border-r border-slate-100">
            {productTitle ? (
              <div className="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-[11px] leading-relaxed text-amber-950">
                <span className="font-bold">「{productTitle}」</span> 링크 기준으로 미리보기를 열었습니다.
                위젯 디자인·전환 설정은 파트너 계정에 공통 저장됩니다.
              </div>
            ) : null}
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

            <section className="rounded-2xl border border-violet-200 bg-violet-50/60 p-3.5 space-y-2.5">
              <div className="flex items-center gap-1.5">
                <Phone size={14} className="text-violet-700" />
                <div className="text-sm font-bold text-violet-950">콜디비 안심번호</div>
              </div>
              {!lkCode ? (
                <p className="text-[11px] text-violet-900/80 leading-relaxed">
                  홍보링크 행에서 「HTML 위젯」을 열면 해당 상품의 콜디비 번호를 폼에 적용할 수 있습니다.
                </p>
              ) : hasCallPhone ? (
                <div className="space-y-2">
                  <div className="rounded-xl border border-violet-200 bg-white px-3 py-2.5">
                    <div className="text-[10px] font-bold text-violet-700">적용된 안심번호</div>
                    <div className="mt-0.5 text-base font-extrabold tabular-nums text-slate-900">
                      {callPhoneDisplay}
                    </div>
                    {callCampaignTitle ? (
                      <div className="mt-1 text-[10px] text-slate-500">{callCampaignTitle}</div>
                    ) : null}
                  </div>
                  <label className="flex items-start gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      className="mt-0.5 rounded border-slate-300 text-violet-600"
                      checked={options.showFormCall !== false}
                      onChange={(e) => setOptions((prev) => ({ ...prev, showFormCall: e.target.checked }))}
                    />
                    <span className="text-xs text-violet-950 leading-relaxed">
                      <span className="font-bold">상담폼·버튼형에 콜디비 번호 표시</span>
                      <span className="block text-violet-800/70">끄면 폼만 보이고 전화 버튼은 숨깁니다. 전화형은 항상 번호를 사용합니다.</span>
                    </span>
                  </label>
                </div>
              ) : (
                <div className="space-y-2">
                  <p className="text-[11px] text-violet-900/85 leading-relaxed">
                    {callCampaignTitle ? (
                      <>
                        <span className="font-bold">「{callCampaignTitle}」</span> 에 배정된 안심번호가 없습니다.
                        아래에서 번호를 적용하면 상담폼에 전화 상담 버튼이 표시됩니다.
                      </>
                    ) : (
                      '이 링크 상품에 배정된 콜디비 안심번호가 없습니다.'
                    )}
                  </p>
                  {callCampaignId > 0 && availableNumbers.length > 0 ? (
                    <div className="flex flex-col sm:flex-row gap-2">
                      <select
                        value={claimCnId}
                        onChange={(e) => setClaimCnId(e.target.value)}
                        className="flex-1 rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-bold text-slate-800"
                      >
                        {availableNumbers.map((n) => (
                          <option key={n.cnId} value={n.cnId}>
                            {formatCallPhone(n.number) || n.number}
                            {n.memo ? ` · ${n.memo}` : ''}
                          </option>
                        ))}
                      </select>
                      <button
                        type="button"
                        disabled={claimBusy || !claimCnId}
                        onClick={() => void handleClaimCallNumber()}
                        className="shrink-0 rounded-xl bg-violet-700 hover:bg-violet-600 px-3 py-2 text-xs font-bold text-white disabled:opacity-60"
                      >
                        {claimBusy ? '적용 중…' : '폼에 번호 적용'}
                      </button>
                    </div>
                  ) : (
                    <div className="flex flex-wrap gap-2">
                      <Link
                        to="/partner/call"
                        className="inline-flex items-center rounded-xl border border-violet-300 bg-white px-3 py-2 text-xs font-bold text-violet-900 hover:bg-violet-50"
                      >
                        콜디비에서 번호 신청
                      </Link>
                    </div>
                  )}
                </div>
              )}
            </section>

            {callCampaignId > 0 ? (
              <div className="rounded-2xl border border-cyan-200 bg-cyan-50/70 px-3.5 py-3 space-y-2">
                <div className="flex items-start justify-between gap-2 flex-wrap">
                  <div>
                    <div className="text-xs font-bold text-cyan-950">설정 적용 범위</div>
                    <p className="text-[11px] text-cyan-900/80 mt-0.5 leading-relaxed">
                      {callCampaignTitle || '이 상품'}에만 다른 문구·디자인을 쓰거나, 모든 링크 공통 설정을 편집할 수 있습니다.
                    </p>
                  </div>
                  {hasCampaignOverride ? (
                    <span className="text-[10px] font-bold text-cyan-800 bg-white border border-cyan-200 px-2 py-0.5 rounded-md">
                      상품 전용 설정 사용 중
                    </span>
                  ) : null}
                </div>
                <div className="inline-flex rounded-lg border border-cyan-200 bg-white p-0.5">
                  <button
                    type="button"
                    onClick={() => {
                      if (optionScope === 'campaign') {
                        setCampaignOptionsState(options);
                      }
                      setOptionScope('partner');
                      setOptions(partnerOptions);
                    }}
                    className={`px-3 py-1.5 rounded-md text-[11px] font-bold ${
                      optionScope === 'partner' ? 'bg-slate-900 text-white' : 'text-slate-500'
                    }`}
                  >
                    공통 설정
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      if (optionScope === 'partner') {
                        setPartnerOptions(options);
                      }
                      setOptionScope('campaign');
                      setOptions(campaignOptionsState || partnerOptions);
                    }}
                    className={`px-3 py-1.5 rounded-md text-[11px] font-bold ${
                      optionScope === 'campaign' ? 'bg-slate-900 text-white' : 'text-slate-500'
                    }`}
                  >
                    이 상품만
                  </button>
                </div>
                {optionScope === 'campaign' && hasCampaignOverride ? (
                  <button
                    type="button"
                    disabled={savingOptions}
                    onClick={() => void handleClearCampaignOptions()}
                    className="text-[11px] font-bold text-rose-700 hover:text-rose-800 underline underline-offset-2"
                  >
                    상품 전용 설정 삭제
                  </button>
                ) : null}
              </div>
            ) : null}

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
                <div className="space-y-2">
                  <div className="text-sm font-bold text-slate-900">업종 원클릭 패키지</div>
                  <p className="text-xs text-slate-500 leading-relaxed">
                    템플릿 · PC 배치 · CTA · 전환 옵션을 한 번에 맞춥니다. 이후 세부 수정 가능합니다.
                  </p>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {EMBED_INDUSTRY_PACKAGES.map((pkg) => (
                      <button
                        key={pkg.id}
                        type="button"
                        onClick={() =>
                          setOptions((prev) =>
                            applyEmbedIndustryPackage(prev, pkg.id as EmbedIndustryPackageId),
                          )
                        }
                        className="rounded-xl border border-slate-200 bg-white hover:border-cyan-400 px-2.5 py-2 text-left"
                      >
                        <div className="text-xs font-bold text-slate-900">{pkg.label}</div>
                        <div className="text-[10px] text-slate-500 mt-0.5 leading-snug">{pkg.desc}</div>
                      </button>
                    ))}
                  </div>
                </div>
                <HelpTipHeading title="디자인 템플릿" helpTitle={EMBED_HELP.design.title}>
                  {EMBED_HELP.design.body}
                </HelpTipHeading>
                <p className="text-xs text-slate-500 leading-relaxed">
                  디자인별 미리보기를 비교해 고르세요. 선택하면 우측 큰 미리보기·강조색이 바로 바뀌며, 문구·색상 탭에서 세부 수정할 수 있습니다.
                </p>
                <EmbedDesignGallery
                  selectedId={presetId}
                  options={previewOptions}
                  brandName={brandName}
                  phoneHint={
                  hasCallPhone && callPhoneDisplay
                    ? `안심번호 ${callPhoneDisplay}`
                    : previewOptions.showFormCall === false
                      ? ''
                      : phoneHint
                }
                  onSelect={selectPreset}
                />
                <div className="space-y-2 pt-1">
                  <div className="text-sm font-bold text-slate-900">PC 레이아웃</div>
                  <p className="text-xs text-slate-500 leading-relaxed">
                    디자인 템플릿과 별개로 PC에서 위젯 배치만 고릅니다. 모바일은 항상 1열입니다.
                  </p>
                  <div className="grid grid-cols-2 gap-2">
                    {EMBED_PC_LAYOUTS.map((layout) => {
                      const active = normalizeEmbedPcLayout(options.pcLayout) === layout.id;
                      return (
                        <button
                          key={layout.id}
                          type="button"
                          onClick={() =>
                            setOptions((prev) => ({
                              ...prev,
                              pcLayout: layout.id as EmbedPcLayoutId,
                            }))
                          }
                          className={`rounded-xl border px-3 py-2.5 text-left transition-all ${
                            active
                              ? 'border-cyan-500 bg-cyan-50 ring-2 ring-cyan-200'
                              : 'border-slate-200 bg-white hover:border-slate-300'
                          }`}
                        >
                          <div className="text-xs font-bold text-slate-900">{layout.label}</div>
                          <div className="text-[10px] text-slate-500 mt-0.5 leading-snug">{layout.desc}</div>
                        </button>
                      );
                    })}
                  </div>
                </div>
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
                    { key: 'showFormCall', label: '폼에 콜디비 번호', desc: '상담폼·버튼형에 안심번호 전화 버튼' },
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

                <div className="rounded-2xl border border-amber-200 bg-amber-50/60 p-3 space-y-3">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <div className="text-xs font-bold text-amber-950">위젯 A/B 테스트</div>
                      <p className="text-[11px] text-amber-900/80 mt-0.5 leading-relaxed">
                        방문자를 A·B로 나누고, 유입분석에서 행동을 비교합니다. B안은 문구·디자인만 다르게 할 수 있습니다.
                      </p>
                    </div>
                    <label className="inline-flex items-center gap-1.5 text-[11px] font-bold text-amber-950 shrink-0">
                      <input
                        type="checkbox"
                        className="rounded border-amber-300 text-amber-600"
                        checked={Boolean(ab.enabled)}
                        onChange={(e) => setAb((prev) => ({ ...prev, enabled: e.target.checked }))}
                      />
                      사용
                    </label>
                  </div>
                  {ab.enabled ? (
                    <>
                      <label className="flex items-center gap-2 text-[11px] text-slate-700">
                        <span className="font-bold shrink-0">B안 비율</span>
                        <input
                          type="range"
                          min={10}
                          max={90}
                          step={5}
                          value={ab.split ?? 50}
                          onChange={(e) =>
                            setAb((prev) => ({ ...prev, split: Number(e.target.value) }))
                          }
                          className="flex-1"
                        />
                        <span className="tabular-nums font-bold w-10 text-right">{ab.split ?? 50}%</span>
                      </label>
                      <div className="grid grid-cols-[auto_1fr] gap-2 items-center">
                        <label className="text-[11px] font-bold text-slate-600">B 제목</label>
                        <input
                          type="text"
                          value={ab.b?.title || ''}
                          onChange={(e) =>
                            setAb((prev) => ({ ...prev, b: { ...prev.b, title: e.target.value } }))
                          }
                          className="px-2.5 py-1.5 rounded-lg border border-amber-200 bg-white text-xs"
                        />
                        <label className="text-[11px] font-bold text-slate-600">B 제출</label>
                        <input
                          type="text"
                          value={ab.b?.submitLabel || ''}
                          onChange={(e) =>
                            setAb((prev) => ({
                              ...prev,
                              b: { ...prev.b, submitLabel: e.target.value, buttonLabel: e.target.value },
                            }))
                          }
                          className="px-2.5 py-1.5 rounded-lg border border-amber-200 bg-white text-xs"
                        />
                        <label className="text-[11px] font-bold text-slate-600">B 혜택</label>
                        <input
                          type="text"
                          value={ab.b?.benefitText || ''}
                          onChange={(e) =>
                            setAb((prev) => ({
                              ...prev,
                              b: { ...prev.b, benefitText: e.target.value },
                            }))
                          }
                          className="px-2.5 py-1.5 rounded-lg border border-amber-200 bg-white text-xs"
                        />
                      </div>
                      <p className="text-[10px] text-amber-900/70 leading-relaxed">
                        미리보기에서 A/B를 전환해 확인할 수 있습니다. 저장 시 A/B 설정도 함께 반영됩니다.
                      </p>
                    </>
                  ) : null}
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
                  <label className="text-xs font-bold text-slate-600">제출 이벤트명</label>
                  <input
                    type="text"
                    value={options.conversionEventName || 'lc_lead_submit'}
                    onChange={(e) => setOptions((prev) => ({ ...prev, conversionEventName: e.target.value }))}
                    className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                  />
                </div>
                <div className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[11px] text-slate-600 leading-relaxed space-y-1">
                  <div className="font-bold text-slate-800">자동 전송되는 CRO 이벤트</div>
                  <p>
                    {EMBED_CRO_EVENTS.map((ev, i) => (
                      <span key={ev.id}>
                        {i > 0 ? ' · ' : ''}
                        <code className="text-cyan-800">{ev.id}</code>
                      </span>
                    ))}
                  </p>
                  <p className="text-slate-500">
                    dataLayer / gtag / <code className="text-slate-700">lc-embed-event</code> 로 전달됩니다. GTM에서 트리거로 걸어 퍼널을 측정하세요.
                  </p>
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
            <div className="flex flex-wrap gap-2">
              <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
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
              {ab.enabled ? (
                <div className="inline-flex rounded-lg border border-amber-200 bg-amber-50 p-0.5">
                  <button
                    type="button"
                    onClick={() => setPreviewAbVariant('A')}
                    className={`px-3 py-1.5 rounded-md text-[11px] font-bold ${
                      previewAbVariant === 'A' ? 'bg-amber-700 text-white' : 'text-amber-800'
                    }`}
                  >
                    A안
                  </button>
                  <button
                    type="button"
                    onClick={() => setPreviewAbVariant('B')}
                    className={`px-3 py-1.5 rounded-md text-[11px] font-bold ${
                      previewAbVariant === 'B' ? 'bg-amber-700 text-white' : 'text-amber-800'
                    }`}
                  >
                    B안
                  </button>
                </div>
              ) : null}
            </div>
            <p className="text-[11px] text-slate-500 leading-relaxed">
              {previewStage === 'success'
                ? '접수 성공 후 고객에게 보이는 안내입니다. 완료 문구·이동 URL이 여기에 반영됩니다.'
                : 'PC·모바일 실사이즈 프레임입니다. 미리보기를 클릭하거나 「크게 보기」로 확대해 확인할 수 있습니다.'}
            </p>
            <EmbedDevicePreviewFrame device={device} productLabel={productLabel} pageHost="example.com">
              <EmbedWidgetLivePreview
                mode={mode}
                options={previewOptions}
                device={device}
                stage={previewStage}
                phoneHint={
                  hasCallPhone && callPhoneDisplay
                    ? `안심번호 ${callPhoneDisplay}`
                    : previewOptions.showFormCall === false
                      ? ''
                      : phoneHint
                }
                brandName={brandName}
                inDeviceFrame
                productLabel={productLabel}
              />
            </EmbedDevicePreviewFrame>
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
              onClick={handleCopyHtml}
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
