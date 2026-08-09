import { Copy, Download, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { HelpTipButton, HelpTipHeading } from '../HelpTipButton';
import { EMBED_HELP } from '../../lib/embedHelpTips';
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
  accent: '#0d9488',
  title: '무료 상담 신청',
  submitLabel: '상담 신청하기',
  buttonLabel: '무료 상담 신청',
  callLabel: '전화 상담',
  successMessage: '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.',
  successRedirectUrl: '',
  trackConversion: true,
  conversionEventName: 'lc_lead_submit',
  showRegion: true,
  showInquiry: true,
  privacyText: '개인정보 수집·이용에 동의합니다.',
  requireWidgetKey: false,
};

type Props = {
  open: boolean;
  onClose: () => void;
  /** 있으면 예시 스니펫에 실제 코드 표시 */
  lkCode?: string;
  onCopySnippet?: (snippet: string) => void;
};

const MODES: Array<{ id: LeadEmbedMode; label: string; desc: string }> = [
  { id: 'form', label: '폼형', desc: '페이지에 상담폼 + 전화 표시' },
  { id: 'button', label: '버튼형', desc: '버튼 클릭 시 모달 폼' },
  { id: 'phone', label: '전화형', desc: '안심번호 전화 버튼만' },
];

export function PartnerWpEmbedGuideModal({ open, onClose, lkCode, onCopySnippet }: Props) {
  const [domainsText, setDomainsText] = useState('');
  const [phoneHint, setPhoneHint] = useState('');
  const [saving, setSaving] = useState(false);
  const [saveMsg, setSaveMsg] = useState('');
  const [mode, setMode] = useState<LeadEmbedMode>('form');
  const [embedToday, setEmbedToday] = useState(0);
  const [embedTotal, setEmbedTotal] = useState(0);
  const [statsDays, setStatsDays] = useState(14);
  const [byDomain, setByDomain] = useState<Array<{ host: string; total: number; today: number }>>([]);
  const [widgetKey, setWidgetKey] = useState('');
  const [keyBusy, setKeyBusy] = useState(false);
  const [options, setOptions] = useState<PartnerEmbedOptions>(DEFAULT_OPTIONS);
  const [savingOptions, setSavingOptions] = useState(false);

  useEffect(() => {
    if (!open) return;
    setSaveMsg('');
    fetchPartnerEmbedSettings(lkCode || undefined)
      .then((data) => {
        setDomainsText((data.domains || []).join('\n'));
        setEmbedToday(data.embedToday ?? 0);
        setEmbedTotal(data.embedTotal ?? 0);
        setStatsDays(data.statsDays ?? 14);
        setByDomain(data.byDomain || []);
        setWidgetKey(data.widgetKey || '');
        setOptions({ ...DEFAULT_OPTIONS, ...(data.options || {}) });
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
  }, [open, lkCode]);

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
  const previewUrl = useMemo(
    () =>
      buildLeadEmbedPreviewUrl(sampleCode, {
        mode,
        widgetKey,
      }),
    [sampleCode, mode, widgetKey],
  );
  const pluginUrl = leadEmbedPluginDownloadUrl();

  if (!open) return null;

  const handleWidgetKey = async (modeAction: 'issue' | 'rotate') => {
    if (modeAction === 'rotate' && !window.confirm('재발급하면 기존 설치 코드는 바로 동작하지 않습니다. 계속할까요?')) {
      return;
    }
    setKeyBusy(true);
    setSaveMsg('');
    try {
      const res = modeAction === 'issue'
        ? await issuePartnerEmbedWidgetKey()
        : await rotatePartnerEmbedWidgetKey();
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
      const res = await savePartnerEmbedOptions(options);
      setOptions({ ...DEFAULT_OPTIONS, ...(res.options || {}) });
      if (res.widgetKey) setWidgetKey(res.widgetKey);
      setSaveMsg(res.message || '위젯 설정을 저장했습니다.');
    } catch (e) {
      setSaveMsg(e instanceof Error ? e.message : '위젯 설정 저장에 실패했습니다.');
    } finally {
      setSavingOptions(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="wp-embed-guide-title"
      >
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
          <div>
            <div className="flex items-center gap-1.5 flex-wrap">
              <h3 id="wp-embed-guide-title" className="text-lg font-bold text-slate-900">
                외부 홈페이지 상담 위젯
              </h3>
              <HelpTipButton title={EMBED_HELP.overview.title}>{EMBED_HELP.overview.body}</HelpTipButton>
            </div>
            <p className="text-sm text-slate-500 mt-0.5">HTML 코드 또는 워드프레스로 상담폼·전화를 연결</p>
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

        <div className="p-6 space-y-5 overflow-y-auto">
          <section className="rounded-2xl border border-cyan-200 bg-cyan-50/70 p-4 space-y-2">
            <div className="text-sm font-bold text-cyan-950">동작 방식</div>
            <p className="text-sm text-cyan-950/90 leading-relaxed">
              외부 도메인 홈페이지에 위젯을 넣으면 상담 DB가 플랫폼으로 접수됩니다.
              폼/버튼형은 iframe으로 삽입되어 설치 사이트 CSS와 충돌하지 않습니다.
              콜디비 안심번호가 배정된 경우 전화 버튼도 함께 노출됩니다.
            </p>
            <p className="text-xs text-cyan-900/80">{phoneHint}</p>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
            <div className="flex items-center justify-between gap-2">
              <div className="text-sm font-bold text-slate-900">위젯 실적</div>
              <div className="text-[11px] text-slate-400">최근 {statsDays}일 도메인 기준</div>
            </div>
            <div className="grid grid-cols-2 gap-2">
              <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                <div className="text-[11px] text-slate-500">오늘</div>
                <div className="text-lg font-bold text-cyan-700 tabular-nums">{embedToday}</div>
              </div>
              <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                <div className="text-[11px] text-slate-500">누적</div>
                <div className="text-lg font-bold text-slate-900 tabular-nums">{embedTotal}</div>
              </div>
            </div>
            {byDomain.length > 0 ? (
              <div className="space-y-1.5">
                {byDomain.slice(0, 5).map((row) => (
                  <div key={row.host} className="flex items-center justify-between gap-2 text-xs">
                    <span className="font-mono text-slate-600 truncate" title={row.host}>{row.host}</span>
                    <span className="tabular-nums text-slate-800 font-semibold shrink-0">{row.total}건</span>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-xs text-slate-400">아직 위젯 접수가 없습니다. 설치 후 도메인별 건수가 표시됩니다.</p>
            )}
          </section>

          <section className="space-y-3">
            <HelpTipHeading title="1) HTML 코드 삽입 (추천)" helpTitle={EMBED_HELP.modes.title}>
              {EMBED_HELP.modes.body}
            </HelpTipHeading>
            <div className="grid grid-cols-3 gap-2">
              {MODES.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => setMode(item.id)}
                  className={`rounded-xl border px-2 py-2 text-left transition-colors ${
                    mode === item.id
                      ? 'border-cyan-500 bg-cyan-50 text-cyan-900'
                      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <div className="text-xs font-bold">{item.label}</div>
                  <div className="text-[10px] mt-0.5 leading-snug opacity-80">{item.desc}</div>
                </button>
              ))}
            </div>
            <ol className="space-y-2 text-sm text-slate-600 leading-relaxed list-decimal pl-5">
              <li>원하는 형태(폼/버튼/전화)를 고른 뒤 설치 코드를 복사합니다.</li>
              <li>카페24·아임웹·워드프레스·자체 홈페이지 HTML에 붙여넣습니다.</li>
              <li>테스트 접수 후 파트너센터 실적에서 「외부위젯」으로 확인합니다.</li>
            </ol>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-2">
              <div className="text-xs font-bold text-slate-500">설치 코드 · {MODES.find((m) => m.id === mode)?.label}</div>
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
              {previewUrl ? (
                <div className="rounded-xl border border-dashed border-slate-200 bg-white overflow-hidden">
                  <div className="px-3 py-1.5 text-[10px] font-bold text-slate-500 border-b border-slate-100">
                    미리보기 · 테스트 접수도 실제 DB로 들어갑니다
                  </div>
                  <iframe
                    title="상담 위젯 미리보기"
                    src={previewUrl}
                    className="w-full bg-white"
                    style={{ height: mode === 'phone' ? 120 : 400, border: 0 }}
                  />
                </div>
              ) : null}
            </div>
          </section>

          <section className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-3">
            <HelpTipHeading title="2) 워드프레스 플러그인" helpTitle={EMBED_HELP.wordpress.title} className="text-sm font-bold text-emerald-900">
              {EMBED_HELP.wordpress.body}
            </HelpTipHeading>
            <ol className="list-decimal pl-5 space-y-1.5 text-sm text-emerald-950/90 leading-relaxed">
              <li>아래 zip을 받아 워드프레스 → 플러그인 → 새로 추가 → 업로드</li>
              <li>설정에서 홍보코드·위젯 키(발급 시)·위젯 형태 저장</li>
              <li>페이지에 숏코드 또는 블록 삽입</li>
            </ol>
            <div className="flex flex-col sm:flex-row gap-2">
              <a
                href={pluginUrl}
                className="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500"
                download
              >
                <Download size={16} />
                플러그인 zip 다운로드
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
          </section>

          <section className="space-y-3">
            <HelpTipHeading title="3) 위젯 키 (권장)" helpTitle={EMBED_HELP.widgetKey.title}>
              {EMBED_HELP.widgetKey.body}
            </HelpTipHeading>
            <p className="text-xs text-slate-500 leading-relaxed">
              발급하면 설치 코드에 키가 포함되어 무단 복제를 줄일 수 있습니다. 기본은 키 없이도 홍보코드로 동작하며, 「키 필수」를 켜면 키가 없는 설치는 거부됩니다.
            </p>
            <label className={`flex items-start gap-2 rounded-xl border border-violet-100 bg-violet-50/50 px-3 py-2.5 cursor-pointer ${savingOptions ? 'opacity-60' : ''}`}>
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
                      setOptions({ ...DEFAULT_OPTIONS, ...(res.options || {}) });
                      if (res.widgetKey) setWidgetKey(res.widgetKey);
                      setSaveMsg(res.message || (requireWidgetKey ? '위젯 키 필수를 켰습니다.' : '위젯 키 필수를 해제했습니다.'));
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
                <span className="block text-slate-500 mt-0.5">켜면 키가 자동 발급되며, 키 없는 설치·요청은 차단됩니다.</span>
              </span>
            </label>
            {widgetKey ? (
              <div className="rounded-xl border border-violet-200 bg-violet-50/70 p-3 space-y-2">
                <code className="block text-[11px] font-mono text-violet-900 break-all">{widgetKey}</code>
                <div className="flex gap-2">
                  <button
                    type="button"
                    disabled={keyBusy}
                    onClick={() => void handleWidgetKey('rotate')}
                    className="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 disabled:opacity-60"
                  >
                    키 재발급
                  </button>
                </div>
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
          </section>

          <section className="space-y-3">
            <HelpTipHeading title="4) 허용 도메인 (선택)" helpTitle={EMBED_HELP.domains.title}>
              {EMBED_HELP.domains.body}
            </HelpTipHeading>
            <p className="text-xs text-slate-500 leading-relaxed">
              비우면 모든 사이트에서 위젯이 동작합니다. 도메인을 등록하면 해당 사이트에서만 설정·접수가 가능합니다.
              예: example.com / www.myshop.kr.kr
            </p>
            <textarea
              value={domainsText}
              onChange={(e) => setDomainsText(e.target.value)}
              rows={4}
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
          </section>

          <section className="space-y-3">
            <HelpTipHeading title="5) 디자인 · 폼 필드 · 완료 후 이동" helpTitle={EMBED_HELP.design.title}>
              {EMBED_HELP.design.body}
            </HelpTipHeading>
            <p className="text-xs text-slate-500 leading-relaxed">
              강조색·문구·표시 필드를 맞추고, 접수 후 고마움 페이지로 보낼 수 있습니다.
              허용 도메인을 등록한 경우 리다이렉트도 같은 도메인만 가능합니다.
            </p>
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
                placeholder="무료 상담 신청"
              />
              <label className="text-xs font-bold text-slate-600">전화 라벨</label>
              <input
                type="text"
                value={options.callLabel || ''}
                onChange={(e) => setOptions((prev) => ({ ...prev, callLabel: e.target.value }))}
                className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                placeholder="전화 상담"
              />
              <label className="text-xs font-bold text-slate-600">완료 문구</label>
              <input
                type="text"
                value={options.successMessage || ''}
                onChange={(e) => setOptions((prev) => ({ ...prev, successMessage: e.target.value }))}
                className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
              />
              <label className="text-xs font-bold text-slate-600">완료 후 URL</label>
              <input
                type="url"
                value={options.successRedirectUrl || ''}
                onChange={(e) => setOptions((prev) => ({ ...prev, successRedirectUrl: e.target.value }))}
                className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                placeholder="https://example.com/thanks"
              />
              <label className="text-xs font-bold text-slate-600">폼 필드</label>
              <div className="flex flex-wrap gap-4 text-sm text-slate-700">
                <label className="inline-flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={options.showRegion !== false}
                    onChange={(e) => setOptions((prev) => ({ ...prev, showRegion: e.target.checked }))}
                    className="rounded border-slate-300 text-cyan-600"
                  />
                  지역
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
              <label className="text-xs font-bold text-slate-600">동의 문구</label>
              <input
                type="text"
                value={options.privacyText || ''}
                onChange={(e) => setOptions((prev) => ({ ...prev, privacyText: e.target.value }))}
                className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                placeholder="개인정보 수집·이용에 동의합니다."
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
                placeholder="lc_lead_submit"
              />
            </div>
            <p className="text-[11px] text-slate-400 leading-relaxed">
              접수 성공 시 설치 페이지에서{' '}
              <code className="text-slate-600">{`dataLayer.push({ event: 'lc_lead_submit', ... })`}</code>
              {' '}가 실행됩니다. GTM 트리거를 이 이벤트명으로 걸면 됩니다.
            </p>
            <button
              type="button"
              disabled={savingOptions}
              onClick={() => void handleSaveOptions()}
              className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold disabled:opacity-60"
            >
              {savingOptions ? '저장 중...' : '디자인·필드·완료 설정 저장'}
            </button>
            {saveMsg ? <p className="text-xs text-slate-600">{saveMsg}</p> : null}
          </section>

          <ul className="text-xs text-slate-500 space-y-1.5 leading-relaxed list-disc pl-4">
            <li>페이지 URL에 <code className="text-slate-700">?lkCode=</code>가 있으면 그 값이 우선 적용됩니다.</li>
            <li>광고주 직통번호는 넣지 마세요. 성과 측정이 가능한 안심번호만 위젯에 표시됩니다.</li>
            <li>접수 DB는 파트너센터·관리자 상담 목록에서 「외부위젯」으로 구분됩니다.</li>
            <li>페이지 URL의 UTM(<code className="text-slate-700">utm_source</code> 등)과 referrer가 DB에 함께 저장됩니다.</li>
            <li>동일 IP에서 짧은 시간 반복 제출은 자동 제한됩니다.</li>
            <li>위젯 키 발급 후에는 설치 코드의 <code className="text-slate-700">data-widget-key</code>가 필요합니다.</li>
          </ul>
        </div>

        <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 shrink-0">
          <button
            type="button"
            onClick={onClose}
            className="w-full py-3 bg-slate-900 text-white font-bold rounded-xl text-sm"
          >
            확인
          </button>
        </div>
      </div>
    </div>
  );
}
