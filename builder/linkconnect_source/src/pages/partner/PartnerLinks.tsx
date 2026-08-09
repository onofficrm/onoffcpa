import { Copy, ExternalLink, Link as LinkIcon, Plus, MousePointerClick, Target, CheckCircle2, DollarSign, Info, X, Code2, CircleHelp, Download } from 'lucide-react';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { PartnerWpEmbedGuideModal } from '../../components/partner/PartnerWpEmbedGuideModal';
import { useEffect, useMemo, useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { createPartnerLink, fetchPartnerCampaigns, fetchPartnerLinks, PartnerLink } from '../../lib/api';
import { buildLeadEmbedSnippet, leadEmbedPluginDownloadUrl } from '../../lib/partnerEmbed';

export function PartnerLinks() {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [links, setLinks] = useState<PartnerLink[]>([]);
  const [loading, setLoading] = useState(true);
  const [campaigns, setCampaigns] = useState<Array<{ id: number; title: string }>>([]);
  const [campaignId, setCampaignId] = useState(0);
  const [channel, setChannel] = useState('');
  const [subId, setSubId] = useState('');
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [copiedId, setCopiedId] = useState<number | null>(null);
  const [wpGuideOpen, setWpGuideOpen] = useState(false);
  const [wpGuideLkCode, setWpGuideLkCode] = useState('');

  const notify = (msg: string) => {
    setMessage(msg);
    window.setTimeout(() => setMessage(''), 2500);
  };

  const loadLinks = () => {
    setLoading(true);
    fetchPartnerLinks()
      .then((data) => setLinks(data.items))
      .catch(() => setLinks([]))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadLinks();
  }, []);

  useEffect(() => {
    if (!isModalOpen) return;
    fetchPartnerCampaigns()
      .then((data) => {
        const items = data.items.map((c) => ({ id: c.id, title: c.title }));
        setCampaigns(items);
        if (items.length && campaignId === 0) {
          setCampaignId(items[0].id);
        }
      })
      .catch(() => setCampaigns([]));
  }, [isModalOpen, campaignId]);

  const totals = useMemo(() => ({
    count: links.length,
    clicks: links.reduce((s, l) => s + l.clicks, 0),
    received: links.reduce((s, l) => s + l.received, 0),
    approved: links.reduce((s, l) => s + l.approved, 0),
    confRevenue: links.reduce((s, l) => s + l.confRevenue, 0),
  }), [links]);

  const handleCreate = async () => {
    if (!campaignId) return;
    setCreating(true);
    setError('');
    try {
      await createPartnerLink({ campaignId, channel, subId });
      setIsModalOpen(false);
      setChannel('');
      setSubId('');
      loadLinks();
      notify('홍보 링크가 생성되었습니다.');
    } catch (err) {
      setError(err instanceof Error ? err.message : '링크 생성에 실패했습니다.');
    } finally {
      setCreating(false);
    }
  };

  const copyUrl = async (url: string, id = 0, successMsg = '링크가 복사되었습니다.') => {
    try {
      await navigator.clipboard.writeText(url);
      setCopiedId(id);
      notify(successMsg);
      window.setTimeout(() => setCopiedId(null), 2000);
    } catch {
      notify('복사에 실패했습니다.');
    }
  };

  return (
    <PartnerLayout activeMenu="links" title="내 홍보 링크">
      <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 -mt-2">
        <p className="text-slate-500">생성한 홍보 링크를 관리하고, 채널별 성과를 확인하세요.</p>
        <div className="flex flex-wrap gap-2">
          <a
            href={leadEmbedPluginDownloadUrl()}
            download
            className="px-4 py-2.5 bg-white border border-emerald-200 hover:bg-emerald-50 text-emerald-800 font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm text-sm"
          >
            <Download size={18} /> WP 플러그인
          </a>
          <button
            type="button"
            onClick={() => {
              setWpGuideLkCode(links[0]?.code || '');
              setWpGuideOpen(true);
            }}
            className="px-4 py-2.5 bg-white border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 text-slate-700 font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm text-sm"
          >
            <CircleHelp size={18} className="text-emerald-600" /> 사용방법 안내
          </button>
          <button
            onClick={() => setIsModalOpen(true)}
            className="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm"
          >
            <Plus size={18} /> 새 홍보 링크 만들기
          </button>
        </div>
      </div>

      {message ? (
        <div className="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2">{message}</div>
      ) : null}

      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="전체 홍보 링크 수" value={String(totals.count)} suffix="개" icon={<LinkIcon className="text-slate-500" />} />
        <SummaryCard title="총 클릭 수" value={totals.clicks.toLocaleString()} suffix="회" icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="접수 DB" value={String(totals.received)} suffix="건" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="승인완료 DB" value={String(totals.approved)} suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} />
        <SummaryCard title="확정수익" value={totals.confRevenue.toLocaleString()} suffix="원" highlight icon={<DollarSign className="text-emerald-600" />} />
      </div>

      <div className="grid lg:grid-cols-4 gap-8">
        <div className="lg:col-span-3 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            {loading ? (
              <p className="p-8 text-slate-500">링크 목록을 불러오는 중...</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-left">
                  <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                      <th className="px-4 py-4 font-medium whitespace-nowrap">광고상품 / 채널명</th>
                      <th className="px-4 py-4 font-medium whitespace-nowrap">sub_id</th>
                      <th className="px-4 py-4 font-medium whitespace-nowrap">홍보 링크</th>
                      <th className="px-4 py-4 font-medium text-right whitespace-nowrap">클릭 수</th>
                      <th className="px-4 py-4 font-medium text-right whitespace-nowrap">접수/승인/취소</th>
                      <th className="px-4 py-4 font-medium text-right whitespace-nowrap">예상/확정수익</th>
                      <th className="px-4 py-4 font-medium text-center whitespace-nowrap">상태</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {links.length > 0 ? links.map((link) => (
                      <tr key={link.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-4 py-4">
                          <div className="flex flex-col min-w-[140px]">
                            <span className="font-bold text-slate-900 line-clamp-1">{link.campaign}</span>
                            <span className="text-xs text-slate-500 mt-1">{link.channel || '-'}</span>
                          </div>
                        </td>
                        <td className="px-4 py-4 font-medium text-slate-600">{link.subId || '-'}</td>
                        <td className="px-4 py-4">
                          <div className="flex flex-wrap items-center gap-1.5 min-w-[220px] max-w-[360px]">
                            <button
                              type="button"
                              onClick={() => copyUrl(link.url, link.id)}
                              className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:bg-slate-50 max-w-full"
                              title={`${link.url} (클릭하여 복사)`}
                            >
                              <LinkIcon size={14} className="shrink-0 text-slate-400" />
                              <span className="truncate">{link.url}</span>
                            </button>
                            <button
                              type="button"
                              onClick={() => copyUrl(link.url, link.id)}
                              className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold"
                              title="링크 복사"
                            >
                              {copiedId === link.id ? <CheckCircle2 size={14} /> : <Copy size={14} />}
                              복사
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                void copyUrl(
                                  buildLeadEmbedSnippet(link.code),
                                  link.id,
                                  '외부 홈페이지 설치 코드가 복사되었습니다.',
                                );
                              }}
                              className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-bold"
                              title="외부 홈페이지 상담 위젯 설치 코드 복사"
                            >
                              <Code2 size={14} />
                              HTML 위젯
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setWpGuideLkCode(link.code);
                                setWpGuideOpen(true);
                              }}
                              className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:bg-slate-50"
                              title="외부 홈페이지 상담 위젯 사용방법"
                            >
                              <CircleHelp size={14} />
                              안내
                            </button>
                            <a href={link.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:bg-slate-50" title="새 창으로 열기">
                              <ExternalLink size={14} />
                            </a>
                          </div>
                        </td>
                        <td className="px-4 py-4 text-right font-bold text-slate-700">{link.clicks.toLocaleString()}</td>
                        <td className="px-4 py-4 text-right">
                          <div className="flex items-center justify-end gap-2 text-xs">
                            <span className="font-medium text-slate-600">{link.received}</span>
                            <span className="text-slate-300">/</span>
                            <span className="font-bold text-emerald-600">{link.approved}</span>
                            <span className="text-slate-300">/</span>
                            <span className="font-medium text-red-500">{link.canceled}</span>
                          </div>
                        </td>
                        <td className="px-4 py-4 text-right">
                          <div className="flex flex-col items-end">
                            <span className="text-xs text-slate-400 mb-0.5">{link.estRevenue.toLocaleString()}</span>
                            <span className="font-bold text-slate-900">{link.confRevenue.toLocaleString()}원</span>
                          </div>
                        </td>
                        <td className="px-4 py-4 text-center">
                          <StatusBadge status={link.status} />
                        </td>
                      </tr>
                    )) : (
                      <tr>
                        <td colSpan={7} className="px-4 py-12 text-center text-slate-500">생성된 홍보 링크가 없습니다.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg sticky top-6">
            <div className="flex items-center gap-2 mb-4">
              <Info className="text-cyan-400" size={20} />
              <h3 className="font-bold text-lg">채널별 성과 팁</h3>
            </div>
            <div className="space-y-4 text-sm text-slate-300">
              <p><strong className="text-emerald-400 font-semibold">sub_id</strong>를 구분해서 생성하면 채널별 성과를 쉽게 비교할 수 있습니다.</p>
              <p className="text-slate-400 text-xs leading-relaxed">
                <strong className="text-emerald-400 font-semibold">HTML 위젯</strong> 코드로 외부 홈페이지에 상담폼·전화를 넣고,
                접수는 플랫폼 실적으로 확인할 수 있습니다.
              </p>
              <button
                type="button"
                onClick={() => {
                  setWpGuideLkCode(links[0]?.code || '');
                  setWpGuideOpen(true);
                }}
                className="w-full mt-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs font-bold"
              >
                <CircleHelp size={14} className="text-cyan-400" />
                사용방법 안내 보기
              </button>
            </div>
          </div>
        </div>
      </div>

      <PartnerWpEmbedGuideModal
        open={wpGuideOpen}
        onClose={() => setWpGuideOpen(false)}
        lkCode={wpGuideLkCode || undefined}
        onCopySnippet={
          wpGuideLkCode
            ? (snippet) => {
                void copyUrl(snippet, 0, '외부 홈페이지 설치 코드가 복사되었습니다.');
              }
            : undefined
        }
      />

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <h3 className="text-lg font-bold text-slate-900">새 홍보 링크 만들기</h3>
              <button type="button" onClick={() => setIsModalOpen(false)} className="text-slate-400 hover:text-slate-600"><X size={20} /></button>
            </div>
            <div className="p-6 space-y-5">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">광고상품 선택</label>
                <select value={campaignId} onChange={(e) => setCampaignId(Number(e.target.value))} className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                  {campaigns.map((c) => (
                    <option key={c.id} value={c.id}>{c.title}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">채널명</label>
                <input value={channel} onChange={(e) => setChannel(e.target.value)} type="text" placeholder="예) 네이버 블로그" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">sub_id <span className="text-slate-400 font-normal">(선택)</span></label>
                <input value={subId} onChange={(e) => setSubId(e.target.value)} type="text" placeholder="blog_01" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm" />
              </div>
              {error && <p className="text-sm text-red-600">{error}</p>}
            </div>
            <div className="px-6 py-5 bg-slate-50 border-t border-slate-100 flex gap-3">
              <button type="button" onClick={() => setIsModalOpen(false)} className="flex-1 py-3 bg-white border border-slate-200 rounded-xl">취소</button>
              <button type="button" disabled={creating} onClick={handleCreate} className="flex-1 py-3 bg-emerald-500 text-white font-bold rounded-xl disabled:opacity-60">
                {creating ? '생성 중...' : '링크 생성하기'}
              </button>
            </div>
          </div>
        </div>
      )}
    </PartnerLayout>
  );
}
