import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { Calendar, Database, Download, FileText, Link2, MonitorPlay, PhoneIncoming, User, X } from 'lucide-react';
import { AdminConversion, downloadAdminConversionsCsv, fetchAdminConversions } from '../../lib/api';
import { HelpTipButton } from '../../components/HelpTipButton';
import { EMBED_HELP } from '../../lib/embedHelpTips';
import { ConversionInflowCell, ConversionInflowDetails } from '../../components/ConversionInflowPath';

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
  const [summary, setSummary] = useState({ todayReceived: 0, approved: 0, rejected: 0, pending: 0, callUncreated: 0 });
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState(false);
  const [sourceFilter, setSourceFilter] = useState<SourceFilter>(() => parseSourceFilter(searchParams.get('source')));
  const [selectedDb, setSelectedDb] = useState<AdminConversion | null>(null);
  const [isDetailOpen, setIsDetailOpen] = useState(false);

  useEffect(() => {
    setSourceFilter(parseSourceFilter(searchParams.get('source')));
  }, [searchParams]);

  const closeDetail = useCallback(() => {
    setIsDetailOpen(false);
    setSelectedDb(null);
  }, []);

  useEffect(() => {
    if (!isDetailOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') closeDetail();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [isDetailOpen, closeDetail]);

  const openDetail = (row: AdminConversion) => {
    setSelectedDb(row);
    setIsDetailOpen(true);
  };

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

  const formatCallDuration = (sec?: number) => {
    const total = Math.max(0, Number(sec ?? 0));
    return `${Math.floor(total / 60)}분 ${total % 60}초`;
  };

  return (
    <AdminLayout activeMenu="db" title="전체 디비 관리" description="전체 접수·승인·취소 디비와 수익 분배를 조회합니다.">
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <SummaryCard title="오늘 접수" value={String(summary.todayReceived)} suffix="건" />
        <SummaryCard title="승인 완료" value={String(summary.approved)} suffix="건" color="emerald" highlight />
        <SummaryCard title="취소/무효" value={String(summary.rejected)} suffix="건" color="red" />
        <SummaryCard title="검수 대기" value={String(summary.pending)} suffix="건" color="amber" />
        <SummaryCard title="콜디비 미생성" value={String(summary.callUncreated ?? 0)} suffix="건" color="violet" highlight={(summary.callUncreated ?? 0) > 0} />
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
        <div className="px-6 py-3 border-b border-slate-100 bg-violet-50/60 text-sm text-violet-900 flex items-start gap-2">
          <PhoneIncoming size={16} className="mt-0.5 text-violet-600" />
          <span>
            콜디비는 생성된 전환 DB와 아직 전환으로 생성되지 않은 통화로그까지 함께 표시됩니다.
            <strong className="ml-1">CALL-</strong>로 시작하는 항목은 통화 원본 로그입니다.
          </span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[1180px]">
            <thead className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-3 text-left">DB ID</th>
                <th className="px-4 py-3 text-left">접수일</th>
                <th className="px-4 py-3 text-left">고객</th>
                <th className="px-4 py-3 text-left">연락처</th>
                <th className="px-4 py-3 text-left">파트너</th>
                <th className="px-4 py-3 text-left">유입경로</th>
                <th className="px-4 py-3 text-left">광고주</th>
                <th className="px-4 py-3 text-left">상품</th>
                <th className="px-4 py-3 text-left">상태</th>
                <th className="px-4 py-3 text-right">단가</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={10} className="px-4 py-10 text-center text-slate-500">불러오는 중...</td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={10} className="px-4 py-10 text-center text-slate-500">등록된 디비가 없습니다.</td>
                </tr>
              ) : (
                rows.map((row) => (
                    <tr
                    key={row.id}
                    className={`border-t border-slate-100 hover:bg-slate-50/80 ${
                      selectedDb?.id === row.id ? 'bg-cyan-50/60' : ''
                    }`}
                  >
                      <td className="px-4 py-3 font-mono text-xs text-slate-700">
                      <span className={row.isCallLogOnly ? 'text-violet-700 font-bold' : ''}>{row.id}</span>
                    </td>
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{row.date}</td>
                      <td className="px-4 py-3">
                      <button
                        type="button"
                        onClick={() => openDetail(row)}
                        className="font-medium text-cyan-700 hover:text-cyan-900 hover:underline text-left"
                        title={row.isCallLogOnly ? '콜디비 통화 정보 보기' : '랜딩 입력 정보 보기'}
                      >
                        {row.customer || '-'}
                        {row.isCallLogOnly ? (
                          <span className="ml-2 inline-flex px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 text-[10px] font-bold align-middle">
                            통화로그
                          </span>
                        ) : null}
                      </button>
                    </td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-800 whitespace-nowrap">{row.phone || '-'}</td>
                      <td className="px-4 py-3 font-mono text-xs">{row.partner}</td>
                      <td className="px-4 py-3">
                        <ConversionInflowCell data={row} showAbuse />
                      </td>
                      <td className="px-4 py-3 text-slate-700">{row.advertiser}</td>
                      <td className="px-4 py-3 text-slate-700">{row.campaign}</td>
                      <td className="px-4 py-3"><StatusBadge status={row.status} /></td>
                      <td className="px-4 py-3 text-right tabular-nums text-cyan-600 font-semibold">
                        {row.price > 0 ? `${row.price.toLocaleString()}원` : '-'}
                      </td>
                    </tr>
                  ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {isDetailOpen && selectedDb ? (
        <div
          className="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-sm animate-in fade-in"
          onClick={closeDetail}
        >
          <div
            className="w-full md:w-[560px] h-full bg-slate-50 flex flex-col shadow-2xl animate-in slide-in-from-right overflow-hidden"
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-label="고객 디비 상세"
          >
            <div className="flex justify-between items-center px-6 py-4 border-b border-slate-200 bg-white shrink-0">
              <h2 className="text-lg font-bold text-slate-900">고객 디비 상세</h2>
              <button
                type="button"
                onClick={closeDetail}
                className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                <X size={20} />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto p-6 space-y-6">
              <div className="flex flex-col gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <div className="flex items-center gap-3">
                  <StatusBadge status={selectedDb.status} />
                  <span className="text-xs font-mono text-slate-400">{selectedDb.id}</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900">{selectedDb.campaign || '-'}</h3>
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                  <span className="inline-flex items-center gap-1.5">
                    <Calendar size={14} />
                    {selectedDb.date} 접수
                  </span>
                  <span>단가 {selectedDb.price > 0 ? `${selectedDb.price.toLocaleString()}원` : '-'}</span>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  {selectedDb.isCallLogOnly ? <PhoneIncoming size={16} className="text-violet-500" /> : <User size={16} className="text-slate-400" />}
                  {selectedDb.isCallLogOnly ? '콜디비 통화 정보' : '랜딩 입력 정보'}
                </div>
                <div className="p-5 grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                  <div>
                    <div className="text-slate-400 mb-1">{selectedDb.isCallLogOnly ? '구분' : '고객명'}</div>
                    <div className="font-medium text-slate-900">{selectedDb.customer || '-'}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">{selectedDb.isCallLogOnly ? '발신번호' : '연락처'}</div>
                    <div className="font-medium font-mono text-slate-900">{selectedDb.phone || '-'}</div>
                  </div>
                  {selectedDb.isCallLogOnly ? (
                    <>
                      <div>
                        <div className="text-slate-400 mb-1">가상번호</div>
                        <div className="font-medium font-mono text-slate-900">{selectedDb.virtualNumber || '-'}</div>
                      </div>
                      <div>
                        <div className="text-slate-400 mb-1">통화결과</div>
                        <div className="font-medium text-slate-900">{selectedDb.callResultLabel || selectedDb.callResult || '-'}</div>
                      </div>
                      <div>
                        <div className="text-slate-400 mb-1">통화시간</div>
                        <div className="font-medium text-slate-900">{formatCallDuration(selectedDb.callDuration)}</div>
                      </div>
                      <div>
                        <div className="text-slate-400 mb-1">전환상태</div>
                        <div className="font-medium text-violet-700">아직 전환 DB 미생성</div>
                      </div>
                    </>
                  ) : (
                    <>
                      <div>
                        <div className="text-slate-400 mb-1">이메일</div>
                        <div className="font-medium text-slate-900 break-all">{selectedDb.email || '-'}</div>
                      </div>
                      <div>
                        <div className="text-slate-400 mb-1">지역</div>
                        <div className="font-medium text-slate-900">{selectedDb.region || '-'}</div>
                      </div>
                    </>
                  )}
                  <div className="sm:col-span-2">
                    <div className="text-slate-400 mb-1">문의내용</div>
                    <div className="bg-slate-50 p-3 rounded-lg text-slate-700 leading-relaxed whitespace-pre-wrap">
                      {selectedDb.inquiry || '-'}
                    </div>
                  </div>
                  {selectedDb.attachmentName ? (
                    <div className="sm:col-span-2">
                      <div className="text-slate-400 mb-1 flex items-center gap-1.5">
                        <FileText size={14} /> 첨부파일
                      </div>
                      <div className="bg-slate-50 p-3 rounded-lg border border-slate-200 text-sm text-slate-800 break-all">
                        {selectedDb.attachmentName}
                        {selectedDb.attachmentStored === false ? (
                          <p className="mt-2 text-xs text-amber-700">파일명만 확인 가능합니다.</p>
                        ) : null}
                      </div>
                    </div>
                  ) : null}
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <Link2 size={16} className="text-slate-400" /> 유입 정보
                </div>
                <div className="p-5">
                  <ConversionInflowDetails data={selectedDb} showPartner showAbuse />
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <MonitorPlay size={16} className="text-slate-400" /> 캠페인 / 파트너
                </div>
                <div className="p-5 grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                  <div>
                    <div className="text-slate-400 mb-1">광고상품</div>
                    <div className="font-medium text-slate-900">{selectedDb.campaign || '-'}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">광고주</div>
                    <div className="font-medium text-slate-900">{selectedDb.advertiser || '-'}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">파트너</div>
                    <div className="font-medium font-mono text-slate-900">{selectedDb.partner || '-'}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">채널 / 출처</div>
                    <div className="font-medium text-slate-900">
                      {[selectedDb.channel, selectedDb.source].filter(Boolean).join(' · ') || '-'}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}

    </AdminLayout>
  );
}
