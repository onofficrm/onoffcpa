import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { Database, Download } from 'lucide-react';
import { AdminConversion, downloadAdminConversionsCsv, fetchAdminConversions } from '../../lib/api';
import { formatEmbedSourceLabel } from '../../lib/partnerEmbed';
import { HelpTipButton } from '../../components/HelpTipButton';
import { EMBED_HELP } from '../../lib/embedHelpTips';

function isExternalWidget(source?: string, channel?: string) {
  const s = (source || '').toLowerCase();
  const c = (channel || '').toLowerCase();
  return s === 'embed' || ['embed', 'wordpress', 'widget', 'external'].includes(c);
}

type SourceFilter = '' | 'embed' | 'call' | 'form';

function parseSourceFilter(raw: string | null): SourceFilter {
  const v = (raw || '').toLowerCase();
  if (v === 'embed' || v === 'external') return 'embed';
  if (v === 'call') return 'call';
  if (v === 'form') return 'form';
  return '';
}

export function AdminConversions() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<AdminConversion[]>([]);
  const [summary, setSummary] = useState({ todayReceived: 0, approved: 0, rejected: 0, pending: 0 });
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState(false);
  const [sourceFilter, setSourceFilter] = useState<SourceFilter>(() => parseSourceFilter(searchParams.get('source')));

  useEffect(() => {
    setSourceFilter(parseSourceFilter(searchParams.get('source')));
  }, [searchParams]);

  const load = useCallback(() => {
    setLoading(true);
    fetchAdminConversions({ source: sourceFilter })
      .then((data) => {
        setRows(data.items);
        setSummary(data.summary);
      })
      .catch(() => {
        setRows([]);
      })
      .finally(() => setLoading(false));
  }, [sourceFilter]);

  useEffect(() => {
    load();
  }, [load]);

  const handleDownload = async () => {
    setDownloading(true);
    try {
      await downloadAdminConversionsCsv({ source: sourceFilter });
    } catch (error) {
      alert(error instanceof Error ? error.message : '다운로드에 실패했습니다.');
    } finally {
      setDownloading(false);
    }
  };

  return (
    <AdminLayout activeMenu="db" title="전체 디비 관리" description="전체 접수·승인·취소 디비와 수익 분배를 조회합니다.">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <SummaryCard title="오늘 접수" value={String(summary.todayReceived)} suffix="건" />
        <SummaryCard title="승인 완료" value={String(summary.approved)} suffix="건" color="emerald" highlight />
        <SummaryCard title="취소/무효" value={String(summary.rejected)} suffix="건" color="red" />
        <SummaryCard title="검수 대기" value={String(summary.pending)} suffix="건" color="amber" />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-200">
          <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Database size={20} className="text-cyan-500" />
            전체 디비 목록
          </h2>
          <div className="flex flex-wrap items-center gap-2">
            {([
              { id: '' as SourceFilter, label: '전체' },
              { id: 'embed' as SourceFilter, label: '외부위젯' },
              { id: 'call' as SourceFilter, label: '콜디비' },
              { id: 'form' as SourceFilter, label: '폼/링크' },
            ]).map((item) => (
              <button
                key={item.id || 'all'}
                type="button"
                onClick={() => {
                  setSourceFilter(item.id);
                  if (item.id) setSearchParams({ source: item.id });
                  else setSearchParams({});
                }}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors ${
                  sourceFilter === item.id
                    ? 'bg-cyan-600 text-white border-cyan-600'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                }`}
              >
                {item.label}
              </button>
            ))}
            <HelpTipButton title={EMBED_HELP.sourceFilter.title}>{EMBED_HELP.sourceFilter.body}</HelpTipButton>
          </div>
          <button
            type="button"
            disabled={downloading}
            onClick={() => void handleDownload()}
            className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50"
          >
            <Download size={16} />
            {downloading ? '다운로드 중...' : 'CSV 다운로드'}
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[1040px]">
            <thead className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-3 text-left">DB ID</th>
                <th className="px-4 py-3 text-left">접수일</th>
                <th className="px-4 py-3 text-left">고객</th>
                <th className="px-4 py-3 text-left">파트너</th>
                <th className="px-4 py-3 text-left">출처</th>
                <th className="px-4 py-3 text-left">광고주</th>
                <th className="px-4 py-3 text-left">상품</th>
                <th className="px-4 py-3 text-left">상태</th>
                <th className="px-4 py-3 text-right">단가</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={9} className="px-4 py-10 text-center text-slate-500">불러오는 중...</td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-4 py-10 text-center text-slate-500">등록된 디비가 없습니다.</td>
                </tr>
              ) : (
                rows.map((row) => {
                  const sourceLabel = formatEmbedSourceLabel(row.source, row.channel);
                  const embed = isExternalWidget(row.source, row.channel);
                  return (
                    <tr key={row.id} className="border-t border-slate-100 hover:bg-slate-50/80">
                      <td className="px-4 py-3 font-mono text-xs text-slate-700">{row.id}</td>
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{row.date}</td>
                      <td className="px-4 py-3 font-medium text-slate-900">{row.customer}</td>
                      <td className="px-4 py-3 font-mono text-xs">{row.partner}</td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="flex flex-col gap-0.5">
                          <span className="text-slate-700 text-xs">{sourceLabel}</span>
                          {embed ? (
                            <span className="inline-flex w-fit px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10px] font-bold">외부위젯</span>
                          ) : null}
                          {embed && (row.pageHost || row.pageUrl) ? (
                            <a
                              href={row.pageUrl || undefined}
                              target="_blank"
                              rel="noreferrer"
                              className="text-[10px] text-cyan-700/80 hover:underline max-w-[140px] truncate"
                              title={row.pageUrl || row.pageHost}
                            >
                              {row.pageHost || row.pageUrl}
                            </a>
                          ) : null}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-slate-700">{row.advertiser}</td>
                      <td className="px-4 py-3 text-slate-700">{row.campaign}</td>
                      <td className="px-4 py-3"><StatusBadge status={row.status} /></td>
                      <td className="px-4 py-3 text-right tabular-nums text-cyan-600 font-semibold">
                        {row.price > 0 ? `${row.price.toLocaleString()}원` : '-'}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AdminLayout>
  );
}
