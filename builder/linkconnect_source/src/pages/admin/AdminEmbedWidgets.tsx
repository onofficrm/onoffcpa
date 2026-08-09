import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard } from '../../components/admin/AdminShared';
import {
  AdminEmbedPartnerDetail,
  AdminEmbedPartnerItem,
  AdminEmbedPartnerOptions,
  fetchAdminEmbedPartnerDetail,
  fetchAdminEmbedPartners,
  issueAdminEmbedWidgetKey,
  rotateAdminEmbedWidgetKey,
  saveAdminEmbedDomains,
  saveAdminEmbedOptions,
} from '../../lib/api';
import { buildLeadEmbedPreviewUrl } from '../../lib/partnerEmbed';
import { HelpTipButton, HelpTipHeading } from '../../components/HelpTipButton';
import { EMBED_HELP } from '../../lib/embedHelpTips';
import { Code2, Copy, Globe2, KeyRound, Search, X } from 'lucide-react';

type ScopeFilter = '' | 'active' | 'locked';
type SnippetMode = 'form' | 'button' | 'phone';

const DEFAULT_OPTIONS: AdminEmbedPartnerOptions = {
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

export function AdminEmbedWidgets() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [items, setItems] = useState<AdminEmbedPartnerItem[]>([]);
  const [summary, setSummary] = useState({
    partners: 0,
    domainLocked: 0,
    embedTotal: 0,
    embedToday: 0,
    activeLinks: 0,
  });
  const [q, setQ] = useState('');
  const [scope, setScope] = useState<ScopeFilter>('');
  const [loading, setLoading] = useState(true);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<AdminEmbedPartnerDetail | null>(null);
  const [domainsText, setDomainsText] = useState('');
  const [options, setOptions] = useState<AdminEmbedPartnerOptions>(DEFAULT_OPTIONS);
  const [saving, setSaving] = useState(false);
  const [savingOptions, setSavingOptions] = useState(false);
  const [message, setMessage] = useState('');
  const [snippetMode, setSnippetMode] = useState<SnippetMode>('form');
  const [copied, setCopied] = useState('');
  const [keyBusy, setKeyBusy] = useState(false);

  const load = useCallback(() => {
    setLoading(true);
    fetchAdminEmbedPartners({ q, scope })
      .then((data) => {
        setItems(data.items || []);
        setSummary({
          partners: data.summary?.partners ?? 0,
          domainLocked: data.summary?.domainLocked ?? 0,
          embedTotal: data.summary?.embedTotal ?? 0,
          embedToday: data.summary?.embedToday ?? 0,
          activeLinks: data.summary?.activeLinks ?? 0,
        });
      })
      .catch(() => {
        setItems([]);
      })
      .finally(() => setLoading(false));
  }, [q, scope]);

  useEffect(() => {
    load();
  }, [load]);

  const openDetail = useCallback(async (ptId: number, syncUrl = true) => {
    setSelectedId(ptId);
    setMessage('');
    if (syncUrl) {
      const next = new URLSearchParams(searchParams);
      next.set('ptId', String(ptId));
      setSearchParams(next, { replace: true });
    }
    try {
      const data = await fetchAdminEmbedPartnerDetail(ptId);
      setDetail(data);
      setDomainsText((data.domains || []).join('\n'));
      setOptions({ ...DEFAULT_OPTIONS, ...(data.options || {}) });
    } catch (e) {
      setDetail(null);
      setMessage(e instanceof Error ? e.message : '상세 정보를 불러오지 못했습니다.');
    }
  }, [searchParams, setSearchParams]);

  useEffect(() => {
    const raw = searchParams.get('ptId') || searchParams.get('pt_id') || '';
    const ptId = Number(raw);
    if (!Number.isFinite(ptId) || ptId <= 0 || selectedId === ptId) {
      return;
    }
    void openDetail(ptId, false);
  }, [searchParams, selectedId, openDetail]);

  const closeDetail = () => {
    setSelectedId(null);
    setDetail(null);
    setOptions(DEFAULT_OPTIONS);
    setMessage('');
    const next = new URLSearchParams(searchParams);
    next.delete('ptId');
    next.delete('pt_id');
    setSearchParams(next, { replace: true });
  };

  const handleSave = async () => {
    if (!selectedId) return;
    setSaving(true);
    setMessage('');
    try {
      const domains = domainsText
        .split(/[\n,]+/)
        .map((v) => v.trim())
        .filter(Boolean);
      const res = await saveAdminEmbedDomains({ ptId: selectedId, domains });
      setDomainsText((res.domains || []).join('\n'));
      if (res.partner) {
        setDetail(res.partner);
        setOptions({ ...DEFAULT_OPTIONS, ...(res.partner.options || {}) });
      }
      setMessage(res.message || '저장되었습니다.');
      load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '저장에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  const handleSaveOptions = async () => {
    if (!selectedId) return;
    setSavingOptions(true);
    setMessage('');
    try {
      const res = await saveAdminEmbedOptions({ ptId: selectedId, options });
      setOptions({ ...DEFAULT_OPTIONS, ...(res.options || {}) });
      if (res.partner) setDetail(res.partner);
      setMessage(res.message || '위젯 설정을 저장했습니다.');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '위젯 설정 저장에 실패했습니다.');
    } finally {
      setSavingOptions(false);
    }
  };

  const copyText = async (text: string, key: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(key);
      window.setTimeout(() => setCopied(''), 1500);
    } catch {
      setMessage('클립보드 복사에 실패했습니다.');
    }
  };

  const handleWidgetKey = async (mode: 'issue' | 'rotate') => {
    if (!selectedId) return;
    if (mode === 'rotate' && !window.confirm('위젯 키를 재발급하면 기존 설치 코드는 즉시 동작하지 않습니다. 계속할까요?')) {
      return;
    }
    setKeyBusy(true);
    setMessage('');
    try {
      const res = mode === 'issue'
        ? await issueAdminEmbedWidgetKey(selectedId)
        : await rotateAdminEmbedWidgetKey(selectedId);
      if (res.partner) setDetail(res.partner);
      setMessage(res.message || (mode === 'issue' ? '위젯 키를 발급했습니다.' : '위젯 키를 재발급했습니다.'));
      load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '위젯 키 처리에 실패했습니다.');
    } finally {
      setKeyBusy(false);
    }
  };

  return (
    <AdminLayout
      activeMenu="embed"
      title="외부 상담 위젯"
      description="파트너별 허용 도메인·설치 코드를 관리하고 외부위젯 접수 현황을 확인합니다."
    >
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <SummaryCard title="활성 파트너" value={String(summary.partners)} suffix="명" />
        <SummaryCard title="도메인 제한" value={String(summary.domainLocked)} suffix="명" color="amber" />
        <SummaryCard title="오늘 위젯 DB" value={String(summary.embedToday)} suffix="건" color="cyan" highlight />
        <SummaryCard title="누적 위젯 DB" value={String(summary.embedTotal)} suffix="건" color="emerald" />
        <SummaryCard title="활성 홍보링크" value={String(summary.activeLinks)} suffix="개" />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-2 font-bold text-slate-800">
            <Globe2 size={18} className="text-cyan-500" />
            파트너 위젯 설정
            <HelpTipButton title={EMBED_HELP.overview.title}>{EMBED_HELP.overview.body}</HelpTipButton>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {([
              { id: '' as ScopeFilter, label: '전체' },
              { id: 'active' as ScopeFilter, label: '이용중' },
              { id: 'locked' as ScopeFilter, label: '도메인제한' },
            ]).map((item) => (
              <button
                key={item.id || 'all'}
                type="button"
                onClick={() => setScope(item.id)}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold border ${
                  scope === item.id
                    ? 'bg-cyan-600 text-white border-cyan-600'
                    : 'bg-white text-slate-600 border-slate-200'
                }`}
              >
                {item.label}
              </button>
            ))}
            <div className="relative">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="파트너 코드/이름"
                className="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm w-44"
              />
            </div>
            <Link
              to="/admin/conversions?source=embed"
              className="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50"
            >
              외부위젯 DB 보기
            </Link>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[920px]">
            <thead className="bg-slate-50 text-slate-500 text-left text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-3">파트너</th>
                <th className="px-4 py-3">허용 도메인</th>
                <th className="px-4 py-3 text-right">오늘</th>
                <th className="px-4 py-3 text-right">누적</th>
                <th className="px-4 py-3 text-right">링크</th>
                <th className="px-4 py-3 text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-4 py-12 text-center text-slate-400">불러오는 중...</td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-12 text-center text-slate-400">표시할 파트너가 없습니다.</td>
                </tr>
              ) : (
                items.map((row) => (
                  <tr key={row.ptId} className="hover:bg-slate-50/80">
                    <td className="px-4 py-3">
                      <div className="font-bold text-slate-900">{row.name || '-'}</div>
                      <div className="text-xs font-mono text-slate-500 mt-0.5">{row.code}</div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="space-y-1">
                        {row.domainLock ? (
                          <div className="flex flex-wrap gap-1">
                            {row.domains.slice(0, 3).map((d) => (
                              <span key={d} className="px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10px] font-bold">
                                {d}
                              </span>
                            ))}
                            {row.domains.length > 3 ? (
                              <span className="text-[10px] text-slate-400">+{row.domains.length - 3}</span>
                            ) : null}
                          </div>
                        ) : (
                          <span className="text-xs text-slate-400">전체 허용</span>
                        )}
                        {row.hasWidgetKey ? (
                          <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 text-[10px] font-bold">
                            <KeyRound size={10} /> 키 발급됨
                          </span>
                        ) : null}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-right tabular-nums font-semibold text-cyan-700">{row.embedToday}</td>
                    <td className="px-4 py-3 text-right tabular-nums text-slate-700">{row.embedTotal}</td>
                    <td className="px-4 py-3 text-right tabular-nums text-slate-600">{row.activeLinks}</td>
                    <td className="px-4 py-3 text-center">
                      <button
                        type="button"
                        onClick={() => void openDetail(row.ptId)}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-bold"
                      >
                        <Code2 size={13} />
                        설정
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {selectedId && detail ? (
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-sm" onClick={closeDetail}>
          <div
            className="w-full max-w-xl h-full bg-white shadow-2xl flex flex-col overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-slate-900">{detail.name}</h3>
                <p className="text-xs font-mono text-slate-500 mt-0.5">{detail.code}</p>
              </div>
              <button type="button" onClick={closeDetail} className="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                <X size={18} />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto p-5 space-y-5">
              <div className="grid grid-cols-3 gap-3">
                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <div className="text-xs text-slate-500">오늘 위젯 DB</div>
                  <div className="text-xl font-bold text-cyan-700 mt-1">{detail.embedToday}</div>
                </div>
                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <div className="text-xs text-slate-500">누적 위젯 DB</div>
                  <div className="text-xl font-bold text-slate-900 mt-1">{detail.embedTotal}</div>
                </div>
                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <div className="text-xs text-slate-500">승인</div>
                  <div className="text-xl font-bold text-emerald-700 mt-1">{detail.embedApproved ?? 0}</div>
                </div>
              </div>

              <section className="space-y-3">
                <div className="text-sm font-bold text-slate-900">
                  최근 {detail.statsDays ?? 14}일 추이
                </div>
                {(detail.daily || []).some((d) => d.count > 0) ? (
                  <div className="flex items-end gap-1 h-24 rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                    {(detail.daily || []).map((d) => {
                      const max = Math.max(1, ...(detail.daily || []).map((x) => x.count));
                      const h = Math.round((d.count / max) * 100);
                      return (
                        <div key={d.date} className="flex-1 flex flex-col items-center justify-end gap-1 min-w-0 h-full">
                          <div
                            className="w-full max-w-[14px] rounded-t bg-cyan-500/80"
                            style={{ height: `${Math.max(d.count > 0 ? 8 : 2, h)}%` }}
                            title={`${d.label}: ${d.count}건`}
                          />
                          <div className="text-[9px] text-slate-400 truncate w-full text-center">{d.label.slice(3)}</div>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-xs text-slate-400">최근 기간 위젯 접수가 없습니다.</p>
                )}
              </section>

              <section className="space-y-2">
                <div className="text-sm font-bold text-slate-900">
                  도메인별 실적 (최근 {detail.statsDays ?? 14}일)
                </div>
                {(detail.byDomain || []).length === 0 ? (
                  <p className="text-xs text-slate-400">도메인별 데이터가 없습니다. 위젯 접수 후 표시됩니다.</p>
                ) : (
                  <div className="rounded-xl border border-slate-200 overflow-hidden">
                    <table className="w-full text-xs">
                      <thead className="bg-slate-50 text-slate-500">
                        <tr>
                          <th className="px-3 py-2 text-left font-medium">도메인</th>
                          <th className="px-3 py-2 text-right font-medium">오늘</th>
                          <th className="px-3 py-2 text-right font-medium">기간</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {(detail.byDomain || []).map((row) => (
                          <tr key={row.host}>
                            <td className="px-3 py-2 font-mono text-slate-700 truncate max-w-[220px]" title={row.host}>
                              {row.host}
                            </td>
                            <td className="px-3 py-2 text-right tabular-nums text-cyan-700">{row.today}</td>
                            <td className="px-3 py-2 text-right tabular-nums text-slate-800 font-semibold">{row.total}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </section>

              <section className="space-y-2">
                <div className="text-sm font-bold text-slate-900 flex items-center gap-1.5 flex-wrap">
                  <KeyRound size={15} className="text-violet-600" />
                  위젯 키
                  <HelpTipButton title={EMBED_HELP.widgetKey.title}>{EMBED_HELP.widgetKey.body}</HelpTipButton>
                </div>
                <p className="text-xs text-slate-500 leading-relaxed">
                  발급 후 설치 코드에 <code className="text-slate-700">data-widget-key</code>가 포함됩니다.
                  기본은 키 없이도 홍보코드로 동작하며, 아래 「키 필수」를 켜면 키가 없는 설치는 거부됩니다. 재발급 시 기존 코드는 즉시 무효입니다.
                </p>
                <label className={`flex items-start gap-2 rounded-xl border border-violet-100 bg-violet-50/40 px-3 py-2.5 cursor-pointer ${savingOptions ? 'opacity-60' : ''}`}>
                  <input
                    type="checkbox"
                    disabled={savingOptions}
                    checked={!!options.requireWidgetKey}
                    onChange={(e) => {
                      const requireWidgetKey = e.target.checked;
                      setOptions((prev) => ({ ...prev, requireWidgetKey }));
                      void (async () => {
                        if (!selectedId) return;
                        setSavingOptions(true);
                        setMessage('');
                        try {
                          const res = await saveAdminEmbedOptions({
                            ptId: selectedId,
                            options: { ...options, requireWidgetKey },
                          });
                          setOptions({ ...DEFAULT_OPTIONS, ...(res.options || {}) });
                          if (res.partner) setDetail(res.partner);
                          setMessage(res.message || (requireWidgetKey ? '위젯 키 필수를 켰습니다.' : '위젯 키 필수를 해제했습니다.'));
                          load();
                        } catch (err) {
                          setOptions((prev) => ({ ...prev, requireWidgetKey: !requireWidgetKey }));
                          setMessage(err instanceof Error ? err.message : '위젯 키 필수 설정에 실패했습니다.');
                        } finally {
                          setSavingOptions(false);
                        }
                      })();
                    }}
                    className="mt-0.5 rounded border-slate-300"
                  />
                  <span className="text-xs text-slate-700 leading-relaxed">
                    <span className="font-bold text-violet-900">위젯 키 필수</span>
                    <span className="block text-slate-500 mt-0.5">켜면 키가 자동 발급되며, 키 없는 설치·요청은 즉시 차단됩니다.</span>
                  </span>
                </label>
                {detail.widgetKey ? (
                  <div className="rounded-xl border border-violet-200 bg-violet-50/60 p-3 space-y-2">
                    <code className="block text-xs font-mono text-violet-900 break-all">{detail.widgetKey}</code>
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => void copyText(detail.widgetKey || '', 'widget-key')}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-violet-200 bg-white text-xs font-bold text-violet-800"
                      >
                        <Copy size={13} />
                        {copied === 'widget-key' ? '복사됨' : '키 복사'}
                      </button>
                      <button
                        type="button"
                        disabled={keyBusy}
                        onClick={() => void handleWidgetKey('rotate')}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 disabled:opacity-60"
                      >
                        재발급
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

              <section className="space-y-2">
                <HelpTipHeading title="허용 도메인" helpTitle={EMBED_HELP.domains.title}>
                  {EMBED_HELP.domains.body}
                </HelpTipHeading>
                <p className="text-xs text-slate-500 leading-relaxed">
                  비우면 모든 사이트에서 위젯이 동작합니다. 등록 시 해당 도메인에서만 설정·접수가 가능합니다.
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
                  onClick={() => void handleSave()}
                  className="w-full py-2.5 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-sm font-bold disabled:opacity-60"
                >
                  {saving ? '저장 중...' : '허용 도메인 저장'}
                </button>
              </section>

              <section className="space-y-2">
                <HelpTipHeading title="디자인 · 폼 필드 · 완료 후 이동" helpTitle={EMBED_HELP.design.title}>
                  {EMBED_HELP.design.body}
                </HelpTipHeading>
                <p className="text-xs text-slate-500 leading-relaxed">
                  강조색·문구·표시 필드와 접수 완료 후 이동 URL입니다. 허용 도메인 등록 시 리다이렉트도 같은 도메인만 가능합니다.
                </p>
                <div className="space-y-2">
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
                      className="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono"
                      placeholder="#0d9488"
                    />
                  </div>
                  <input
                    type="text"
                    value={options.title || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, title: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="폼 제목"
                  />
                  <input
                    type="text"
                    value={options.submitLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, submitLabel: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="제출 버튼 문구"
                  />
                  <input
                    type="text"
                    value={options.buttonLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, buttonLabel: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="버튼형 트리거 문구"
                  />
                  <input
                    type="text"
                    value={options.callLabel || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, callLabel: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="전화 상담 버튼 문구"
                  />
                  <input
                    type="text"
                    value={options.successMessage || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, successMessage: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="완료 메시지"
                  />
                  <input
                    type="url"
                    value={options.successRedirectUrl || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, successRedirectUrl: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                    placeholder="https://example.com/thanks"
                  />
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
                      문의 내용 필드
                    </label>
                  </div>
                  <input
                    type="text"
                    value={options.privacyText || ''}
                    onChange={(e) => setOptions((prev) => ({ ...prev, privacyText: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm"
                    placeholder="개인정보 수집·이용에 동의합니다."
                  />
                  <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input
                      type="checkbox"
                      checked={options.trackConversion !== false}
                      onChange={(e) => setOptions((prev) => ({ ...prev, trackConversion: e.target.checked }))}
                      className="rounded border-slate-300 text-cyan-600"
                    />
                    GTM dataLayer 전환 이벤트
                  </label>
                  <input
                    type="text"
                    value={options.conversionEventName || 'lc_lead_submit'}
                    onChange={(e) => setOptions((prev) => ({ ...prev, conversionEventName: e.target.value }))}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono"
                    placeholder="lc_lead_submit"
                  />
                </div>
                <button
                  type="button"
                  disabled={savingOptions}
                  onClick={() => void handleSaveOptions()}
                  className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold disabled:opacity-60"
                >
                  {savingOptions ? '저장 중...' : '디자인·필드·완료 설정 저장'}
                </button>
                {message ? <p className="text-xs text-slate-600">{message}</p> : null}
              </section>

              <section className="space-y-3">
                <div className="flex items-center justify-between gap-2">
                  <div className="text-sm font-bold text-slate-900">설치 코드 발급</div>
                  <div className="flex gap-1">
                    {([
                      { id: 'form' as SnippetMode, label: '폼' },
                      { id: 'button' as SnippetMode, label: '버튼' },
                      { id: 'phone' as SnippetMode, label: '전화' },
                    ]).map((m) => (
                      <button
                        key={m.id}
                        type="button"
                        onClick={() => setSnippetMode(m.id)}
                        className={`px-2.5 py-1 rounded-lg text-[11px] font-bold border ${
                          snippetMode === m.id
                            ? 'bg-slate-900 text-white border-slate-900'
                            : 'bg-white text-slate-600 border-slate-200'
                        }`}
                      >
                        {m.label}
                      </button>
                    ))}
                  </div>
                </div>

                {detail.links.length === 0 ? (
                  <p className="text-sm text-slate-500">활성 홍보 링크가 없습니다. 파트너센터에서 링크를 먼저 생성하세요.</p>
                ) : (
                  detail.links.map((link) => {
                    const snippet = link.snippets[snippetMode];
                    const key = `${link.code}-${snippetMode}`;
                    const previewUrl = buildLeadEmbedPreviewUrl(link.code, {
                      mode: snippetMode,
                      widgetKey: detail.widgetKey,
                    });
                    return (
                      <div key={link.id} className="rounded-2xl border border-slate-200 p-3 space-y-2">
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <div className="font-bold text-slate-900 text-sm">{link.campaign || '캠페인'}</div>
                            <div className="text-[11px] font-mono text-slate-500 mt-0.5">
                              {link.code}
                              {link.channel ? ` · ${link.channel}` : ''}
                              {link.subId ? ` · ${link.subId}` : ''}
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={() => void copyText(snippet, key)}
                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50"
                          >
                            <Copy size={13} />
                            {copied === key ? '복사됨' : '복사'}
                          </button>
                        </div>
                        <pre className="text-[10px] break-all whitespace-pre-wrap bg-slate-900 text-slate-100 rounded-xl p-3 font-mono max-h-28 overflow-y-auto">
                          {snippet}
                        </pre>
                        {previewUrl ? (
                          <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 overflow-hidden">
                            <div className="px-3 py-1.5 text-[10px] font-bold text-slate-500 border-b border-slate-100">
                              미리보기 · 테스트 접수도 실제 DB로 들어갑니다
                            </div>
                            <iframe
                              title={`위젯 미리보기 ${link.code}`}
                              src={previewUrl}
                              className="w-full bg-white"
                              style={{ height: snippetMode === 'phone' ? 120 : 420, border: 0 }}
                            />
                          </div>
                        ) : null}
                      </div>
                    );
                  })
                )}
              </section>
            </div>
          </div>
        </div>
      ) : null}
    </AdminLayout>
  );
}
