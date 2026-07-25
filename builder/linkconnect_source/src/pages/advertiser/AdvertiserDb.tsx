import React, { useCallback, useEffect, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { fetchMerchantConversions, MerchantConversion, reportMerchantChannel, updateMerchantConversion } from '../../lib/api';
import { Database, CheckCircle2, Clock, XCircle, Search, Filter, Download, AlertCircle, ChevronRight, MessageSquare, Check, X, FileText, AlertTriangle, User, Link2, MonitorPlay, LogIn, Calendar, Hash, ArrowRight, Bot, Loader2 } from 'lucide-react';

const fallbackDbData: MerchantConversion[] = [];

export function AdvertiserDb() {
  const [rows, setRows] = useState<MerchantConversion[]>(fallbackDbData);
  const [summary, setSummary] = useState({ pending: 9, needsAction: 9, todaySpend: 300000 });
  const [loading, setLoading] = useState(true);
  const [actionError, setActionError] = useState('');
  const [approveComment, setApproveComment] = useState('');
  const [qualityScore, setQualityScore] = useState(4);
  const [qualityTags, setQualityTags] = useState<string[]>([]);
  const [partnerVisible, setPartnerVisible] = useState(true);
  const [processing, setProcessing] = useState(false);

  const [selectedDb, setSelectedDb] = useState<MerchantConversion | null>(null);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [isApproveOpen, setIsApproveOpen] = useState(false);
  const [isRejectOpen, setIsRejectOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState('');
  const [cancelComment, setCancelComment] = useState('');
  const [isAIFiltering, setIsAIFiltering] = useState(false);
  const [showAbuseOnly, setShowAbuseOnly] = useState(false);
  const [isReportOpen, setIsReportOpen] = useState(false);
  const [reportReason, setReportReason] = useState('');
  const [reportChannel, setReportChannel] = useState('');

  const closeDetail = () => {
    setIsDetailOpen(false);
    setSelectedDb(null);
  };

  const loadRows = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchMerchantConversions();
      setRows(data.items);
      setSummary(data.summary);
    } catch {
      setRows([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadRows();
  }, [loadRows]);

  const handleApproveConfirm = async () => {
    if (!selectedDb?.cvId) {
      return;
    }
    setProcessing(true);
    setActionError('');
    try {
      await updateMerchantConversion({
        action: 'approve',
        cvId: selectedDb.cvId,
        comment: approveComment,
        qualityScore,
        qualityTags,
        partnerVisible: true,
      });
      setIsApproveOpen(false);
      setApproveComment('');
      setQualityScore(4);
      setQualityTags([]);
      closeDetail();
      await loadRows();
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '승인 처리에 실패했습니다.');
    } finally {
      setProcessing(false);
    }
  };

  const handleReportChannel = async () => {
    if (!selectedDb?.cvId || !reportReason.trim()) {
      return;
    }
    setProcessing(true);
    setActionError('');
    try {
      const result = await reportMerchantChannel({
        cvId: selectedDb.cvId,
        channel: reportChannel || selectedDb.channel,
        reason: reportReason.trim(),
      });
      alert(result.message);
      setIsReportOpen(false);
      setReportReason('');
      setReportChannel('');
      closeDetail();
      await loadRows();
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '금지 채널 신고에 실패했습니다.');
    } finally {
      setProcessing(false);
    }
  };

  const handleRejectConfirm = async () => {
    if (!selectedDb?.cvId || !cancelReason) {
      return;
    }
    setProcessing(true);
    setActionError('');
    try {
      await updateMerchantConversion({
        action: 'reject',
        cvId: selectedDb.cvId,
        reason: cancelReason,
        comment: cancelComment,
        partnerVisible,
      });
      setIsRejectOpen(false);
      setCancelReason('');
      setCancelComment('');
      setPartnerVisible(true);
      closeDetail();
      await loadRows();
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '취소 처리에 실패했습니다.');
    } finally {
      setProcessing(false);
    }
  };

  const handleOpenDetail = (db: MerchantConversion) => {
    setSelectedDb(db);
    setIsDetailOpen(true);
  };

  const handleApproveClick = (db: MerchantConversion) => {
    setSelectedDb(db);
    setIsApproveOpen(true);
  };

  const handleRejectClick = (db: MerchantConversion) => {
    setSelectedDb(db);
    setIsRejectOpen(true);
  };

  return (
    <AdvertiserLayout activeMenu="db" title="디비 확인" pendingBadge={summary.needsAction}>
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          접수된 디비를 확인하고 승인 또는 취소/무효 처리를 진행하세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="신규접수" value={String(summary.pending)} suffix="건" />
        <SummaryCard title="오늘 처리 필요" value={String(summary.needsAction)} suffix="건" color="cyan" highlight />
        <SummaryCard title="오늘 사용 광고비" value={summary.todaySpend.toLocaleString()} suffix="원" dark />
      </div>

      {/* Alert Box */}
      <div className="mb-8 bg-blue-50 border border-blue-200 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-blue-100 rounded-lg text-blue-600">
            <AlertCircle size={20} />
          </div>
          <p className="text-blue-900 font-medium">현재 승인 또는 취소 처리가 필요한 디비가 <strong>{summary.needsAction}건</strong> 있습니다.</p>
        </div>
        <button className="text-sm font-bold bg-white text-blue-700 border border-blue-200 hover:bg-blue-100 hover:text-blue-800 px-4 py-2 rounded-lg flex items-center justify-center gap-1 transition-colors">
          바로 처리하기 <ChevronRight size={16} />
        </button>
      </div>

      {actionError && (
        <div className="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">{actionError}</div>
      )}

      {loading && (
        <p className="text-slate-500 mb-4">디비 목록을 불러오는 중...</p>
      )}

      {/* Filter Bar */}
      <div className="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center mb-6 shadow-sm">
        <div className="flex items-center gap-2">
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500" defaultValue="2026-10-01" />
          <span className="text-slate-400">~</span>
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500" defaultValue="2026-10-07" />
        </div>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 캠페인</option>
          <option>개인회생 상담 DB</option>
          <option>어린이 영어캠프</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[120px] flex-1 md:flex-none">
          <option>상태 전체</option>
          <option>신규접수</option>
          <option>확인중</option>
          <option>승인완료</option>
          <option>취소요청</option>
          <option>취소/무효</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[140px] flex-1 md:flex-none">
          <option>취소 사유 전체</option>
          <option>연락불가</option>
          <option>중복 DB</option>
          <option>조건 불일치</option>
        </select>
        
        <div className="flex items-center gap-2">
          <input type="checkbox" id="needsAction" className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
          <label htmlFor="needsAction" className="text-sm font-medium text-slate-700 cursor-pointer">처리 필요만 보기</label>
        </div>
        <button onClick={() => { setIsAIFiltering(true); setTimeout(() => { setIsAIFiltering(false); setShowAbuseOnly(!showAbuseOnly); }, 1500); }} disabled={isAIFiltering} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors shadow-sm ${showAbuseOnly ? "bg-indigo-600 text-white hover:bg-indigo-700" : "bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700"} disabled:opacity-50`}>
          {isAIFiltering ? <Loader2 size={16} className="animate-spin" /> : <Bot size={16} />} {isAIFiltering ? "AI 분석 중..." : (showAbuseOnly ? "AI 필터 해제" : "AI 어뷰징 의심 필터")}
        </button>

        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
          <input type="text" placeholder="고객명, 연락처 검색" className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500" />
        </div>
        <button className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm ml-auto">
          <Filter size={16} /> 조회
        </button>
      </div>

      {/* DB List */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <div className="text-sm text-slate-600 font-medium">총 <span className="text-cyan-600 font-bold">{rows.length}</span>건의 디비가 조회되었습니다.</div>
          <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
            <Download size={14} /> 엑셀 다운로드
          </button>
        </div>
        
        {/* Desktop Table */}
        <div className="hidden lg:block overflow-x-auto">
          <table className="w-full text-sm text-left cursor-default">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-4 py-4 font-medium whitespace-nowrap">접수일</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">광고상품</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">고객명</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">연락처</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">지역</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">문의내용</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">유입 파트너</th>
                <th className="px-4 py-4 font-medium text-center whitespace-nowrap">상태</th>
                <th className="px-4 py-4 font-medium text-right whitespace-nowrap">광고비 차감액</th>
                <th className="px-4 py-4 font-medium text-center whitespace-nowrap">코멘트</th>
                <th className="px-4 py-4 font-medium text-center whitespace-nowrap">처리 버튼</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {rows.filter(db => !showAbuseOnly || db.status === "취소/무효" || db.status === "취소요청").map((db) => (
                <tr key={db.id} className="hover:bg-slate-50 transition-colors" onClick={() => handleOpenDetail(db)}>
                  <td className="px-4 py-4 text-slate-500 whitespace-nowrap">{db.date}</td>
                  <td className="px-4 py-4 font-medium text-slate-900 whitespace-nowrap">{db.campaign}</td>
                  <td className="px-4 py-4 text-slate-900 font-bold whitespace-nowrap">{db.name}</td>
                  <td className="px-4 py-4 text-slate-600 font-mono text-xs whitespace-nowrap">{db.phone}</td>
                  <td className="px-4 py-4 text-slate-600 whitespace-nowrap">{db.region}</td>
                  <td className="px-4 py-4 text-slate-600 max-w-[150px] truncate" title={db.inquiry}>{db.inquiry}</td>
                  <td className="px-4 py-4 text-slate-500 font-mono text-xs whitespace-nowrap">{db.partner}</td>
                  <td className="px-4 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={db.status} />
                  </td>
                  <td className={`px-4 py-4 text-right font-medium whitespace-nowrap ${db.price > 0 ? 'text-slate-900' : 'text-slate-400'}`}>
                    {db.price.toLocaleString()}원
                  </td>
                  <td className="px-4 py-4 text-center whitespace-nowrap">
                    <button className={`p-1.5 rounded-lg transition-colors ${db.comment ? 'text-cyan-600 bg-cyan-50 hover:bg-cyan-100' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'}`} title={db.comment || '코멘트 작성'} onClick={(e) => { e.stopPropagation(); handleOpenDetail(db); }}>
                      <MessageSquare size={16} />
                    </button>
                  </td>
                  <td className="px-4 py-4 text-center whitespace-nowrap">
                    {db.needsAction ? (
                      <div className="flex gap-2 justify-center">
                        <button onClick={(e) => { e.stopPropagation(); handleApproveClick(db); }} className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm border-0 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                          <Check size={14} /> 승인
                        </button>
                        <button onClick={(e) => { e.stopPropagation(); handleRejectClick(db); }} className="px-3 py-1.5 bg-red-600 text-white hover:bg-red-700 shadow-sm border-0 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                          <X size={14} /> 취소
                        </button>
                      </div>
                    ) : (
                      <div className="flex gap-2 justify-center">
                        <button onClick={(e) => { e.stopPropagation(); handleOpenDetail(db); }} className="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 shadow-sm border-0 rounded-lg text-xs font-medium transition-colors flex items-center gap-1">
                          <FileText size={14} /> 상세
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Mobile Cards */}
        <div className="lg:hidden divide-y divide-slate-100">
          {rows.map((db) => (
            <div key={db.id} className="p-5 flex flex-col gap-4 cursor-pointer" onClick={() => handleOpenDetail(db)}>
              <div className="flex justify-between items-start">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="text-xs text-slate-500">{db.date}</span>
                    <span className="text-xs text-slate-400 font-mono">{db.partner}</span>
                  </div>
                  <h3 className="font-bold text-slate-900">{db.name} <span className="font-normal text-slate-500 text-sm ml-1">{db.campaign}</span></h3>
                  <p className="text-sm text-slate-600 font-mono mt-1">{db.phone}</p>
                </div>
                <StatusBadge status={db.status} />
              </div>
              
              <div className="bg-slate-50 rounded-lg p-3 text-sm text-slate-600">
                <div className="flex justify-between mb-1">
                  <span className="text-slate-400">지역</span>
                  <span className="font-medium text-slate-700">{db.region}</span>
                </div>
                <div className="flex justify-between mb-1">
                  <span className="text-slate-400">문의내용</span>
                  <span className="font-medium text-slate-700 truncate max-w-[200px]">{db.inquiry}</span>
                </div>
                <div className="flex justify-between mb-1">
                  <span className="text-slate-400">코멘트</span>
                  <span className="font-medium text-slate-700 truncate max-w-[200px]">{db.comment || '-'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-400">차감액</span>
                  <span className="font-bold text-slate-900">{db.price.toLocaleString()}원</span>
                </div>
              </div>

              {db.needsAction ? (
                <div className="flex gap-2 pt-2">
                  <button onClick={(e) => { e.stopPropagation(); handleApproveClick(db); }} className="flex-1 py-2.5 bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm border-0 rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-1">
                    <Check size={16} /> 승인
                  </button>
                  <button onClick={(e) => { e.stopPropagation(); handleRejectClick(db); }} className="flex-1 py-2.5 bg-red-600 text-white hover:bg-red-700 shadow-sm border-0 rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-1">
                    <X size={16} /> 취소
                  </button>
                </div>
              ) : (
                <div className="flex gap-2 pt-2">
                  <button onClick={(e) => { e.stopPropagation(); handleOpenDetail(db); }} className="flex-1 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 shadow-sm border-0 rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-1">
                    <FileText size={16} /> 상세 보기
                  </button>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Info Box */}
      <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <div className="flex items-start gap-4">
          <AlertTriangle className="w-6 h-6 text-cyan-400 shrink-0 mt-0.5" />
          <div>
            <h3 className="text-lg font-bold mb-4">디비 처리 기준</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>상담 가능 고객은 <strong>승인 처리</strong>해주세요. 승인 처리된 디비는 광고비가 확정 차감됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>연락불가, 중복, 조건불일치 디비는 <strong>취소/무효 처리</strong>할 수 있습니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                <span>취소/무효 처리 시 반드시 <strong>사유와 코멘트</strong>를 정확히 입력해야 합니다. 무분별한 취소는 경고 대상이 될 수 있습니다.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Detail Panel */}
      {isDetailOpen && selectedDb && (
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-sm animate-in fade-in" onClick={closeDetail}>
          <div className="w-full md:w-[600px] h-full bg-slate-50 flex flex-col shadow-2xl animate-in slide-in-from-right overflow-hidden" onClick={(e) => e.stopPropagation()}>
            {/* Header */}
            <div className="flex justify-between items-center px-6 py-4 border-b border-slate-200 bg-white shrink-0">
              <h2 className="text-lg font-bold text-slate-900">디비 상세정보</h2>
              <button onClick={closeDetail} className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                <X size={20} />
              </button>
            </div>
            
            {/* Content scrollable */}
            <div className="flex-1 overflow-y-auto p-6 space-y-6">
              
              {/* Status Header */}
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <div>
                  <div className="flex items-center gap-3 mb-2">
                    <StatusBadge status={selectedDb.status} />
                    <span className="text-xs font-mono text-slate-400">{selectedDb.id}</span>
                  </div>
                  <h3 className="text-lg font-bold text-slate-900">{selectedDb.campaign}</h3>
                  <div className="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <Calendar size={14} />
                    <span>{selectedDb.date} 접수</span>
                  </div>
                </div>
                <div className="md:text-right">
                  <div className="text-sm text-slate-500 mb-1">광고비 차감액</div>
                  <div className={`text-2xl font-bold ${selectedDb.price > 0 ? 'text-slate-900' : 'text-slate-400'}`}>
                    {selectedDb.price.toLocaleString()}원
                  </div>
                </div>
              </div>

              {/* Customer Info Card */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <User size={16} className="text-slate-400" /> 고객 정보
                </div>
                <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                  <div>
                    <div className="text-slate-400 mb-1">고객명</div>
                    <div className="font-medium text-slate-900">{selectedDb.name}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">연락처</div>
                    <div className="font-medium font-mono text-slate-900">{selectedDb.phone}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">이메일</div>
                    <div className="font-medium text-slate-900">{selectedDb.email || '-'}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">지역</div>
                    <div className="font-medium text-slate-900">{selectedDb.region}</div>
                  </div>
                  <div className="md:col-span-2">
                    <div className="text-slate-400 mb-1">문의내용</div>
                    <div className="bg-slate-50 p-3 rounded-lg text-slate-700 leading-relaxed">
                      {selectedDb.inquiry}
                    </div>
                  </div>
                </div>
              </div>

              {/* Inbound Info Card */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <Link2 size={16} className="text-slate-400" /> 유입 정보
                </div>
                <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                  <div>
                    <div className="text-slate-400 mb-1">유입 파트너 코드</div>
                    <div className="font-medium font-mono text-cyan-600 bg-cyan-50 px-2 py-0.5 rounded inline-block">{selectedDb.partner}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1">홍보 채널</div>
                    <div className="font-medium text-slate-900">{selectedDb.channel}</div>
                  </div>
                  <div className="md:col-span-2">
                    <div className="text-slate-400 mb-1">랜딩 URL</div>
                    <a href={selectedDb.landingUrl} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline break-all">
                      {selectedDb.landingUrl}
                    </a>
                  </div>
                  <div className="md:col-span-2">
                    <div className="text-slate-400 mb-1">유입경로 (Referer)</div>
                    <div className="text-slate-600 text-xs break-all truncate" title={selectedDb.referer}>
                      {selectedDb.referer}
                    </div>
                  </div>
                  <div className="md:col-span-2 bg-slate-50 p-3 rounded-lg grid grid-cols-3 gap-3">
                    <div>
                      <div className="text-slate-400 text-xs mb-0.5">UTM Source</div>
                      <div className="font-medium font-mono text-slate-700 text-xs">{selectedDb.utmSource || '-'}</div>
                    </div>
                    <div>
                      <div className="text-slate-400 text-xs mb-0.5">UTM Medium</div>
                      <div className="font-medium font-mono text-slate-700 text-xs">{selectedDb.utmMedium || '-'}</div>
                    </div>
                    <div>
                      <div className="text-slate-400 text-xs mb-0.5">UTM Campaign</div>
                      <div className="font-medium font-mono text-slate-700 text-xs">{selectedDb.utmCampaign || '-'}</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Campaign Info Card */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <MonitorPlay size={16} className="text-slate-400" /> 광고상품 정보
                </div>
                <div className="p-5 space-y-4 text-sm">
                  <div className="flex justify-between items-center">
                    <div className="text-slate-400">광고상품명</div>
                    <div className="font-bold text-slate-900">{selectedDb.campaign}</div>
                  </div>
                  <div className="flex justify-between items-center">
                    <div className="text-slate-400">광고 유형</div>
                    <div className="font-medium text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-xs">CPA</div>
                  </div>
                  <div className="flex justify-between items-center">
                    <div className="text-slate-400">디비당 차감 단가</div>
                    <div className="font-bold text-slate-900">{selectedDb.price.toLocaleString()}원</div>
                  </div>
                  <div className="pt-3 border-t border-slate-100">
                    <div className="text-slate-400 mb-1 text-xs">승인 기준</div>
                    <div className="font-medium text-cyan-700 bg-cyan-50 p-2 rounded text-xs">{selectedDb.approvalCriteria}</div>
                  </div>
                  <div>
                    <div className="text-slate-400 mb-1 text-xs">취소 기준</div>
                    <div className="font-medium text-red-700 bg-red-50 p-2 rounded text-xs">{selectedDb.cancelCriteria}</div>
                  </div>
                </div>
              </div>

              {/* History Timeline */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <Clock size={16} className="text-slate-400" /> 처리 이력
                </div>
                <div className="p-5">
                  <div className="relative border-l border-slate-200 ml-3 space-y-6">
                    {selectedDb.history.map((h: any, i: number) => (
                      <div key={i} className="relative pl-6">
                        <div className="absolute w-3 h-3 bg-white border-2 border-slate-300 rounded-full -left-[6.5px] top-1"></div>
                        <div className="text-xs text-slate-400 font-mono mb-0.5">{h.time}</div>
                        <div className="text-sm font-medium text-slate-900">{h.text}</div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Comments Area */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-2 text-slate-800 font-bold text-sm">
                  <MessageSquare size={16} className="text-slate-400" /> 코멘트
                </div>
                <div className="p-5 space-y-4 text-sm">
                  <div>
                    <div className="flex justify-between items-center mb-1">
                      <span className="text-slate-500 font-medium text-xs">광고주 코멘트</span>
                      {selectedDb.partnerPublic && <span className="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-bold">파트너 공개됨</span>}
                    </div>
                    <div className="bg-slate-50 border border-slate-100 p-3 rounded-lg text-slate-700">
                      {selectedDb.comment || <span className="text-slate-400 italic">등록된 코멘트가 없습니다.</span>}
                    </div>
                  </div>
                  <div>
                    <div className="text-slate-500 font-medium text-xs mb-1">관리자 코멘트</div>
                    <div className="bg-slate-50 border border-slate-100 p-3 rounded-lg text-slate-700">
                      {selectedDb.adminComment || <span className="text-slate-400 italic">등록된 코멘트가 없습니다.</span>}
                    </div>
                  </div>
                </div>
              </div>
              
              {/* Bottom padding for fixed footer */}
              <div className="h-10"></div>
            </div>

            {/* Footer Buttons */}
            <div className="px-6 py-4 border-t border-slate-200 bg-white shrink-0 flex flex-wrap gap-2 md:gap-3">
              <button type="button" onClick={() => { setReportChannel(selectedDb.channel || ''); setIsReportOpen(true); }} className="flex-1 md:flex-none px-4 py-2 border border-orange-200 bg-orange-50 text-orange-700 rounded-xl text-sm font-bold hover:bg-orange-100 transition-colors inline-flex items-center gap-1.5">
                <AlertTriangle size={14} /> 금지 채널 신고
              </button>
              <button className="flex-1 md:flex-none px-4 py-2 border border-slate-200 bg-white text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">
                확인중으로 변경
              </button>
              <button className="flex-1 md:flex-none px-4 py-2 border border-slate-200 bg-white text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">
                코멘트 작성
              </button>
              <div className="w-full md:w-auto md:ml-auto flex gap-2 md:gap-3">
                <button onClick={() => { setIsRejectOpen(true); }} className="flex-1 md:flex-none px-6 py-2 bg-red-600 text-white hover:bg-red-700 shadow-sm border-0 rounded-xl text-sm font-bold transition-colors">
                  취소/무효 처리
                </button>
                <button onClick={() => { setIsApproveOpen(true); }} className="flex-1 md:flex-none px-6 py-2 bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm border-0 rounded-xl text-sm font-bold transition-colors shadow-sm shadow-emerald-600/20">
                  승인하기
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Approve Modal */}
      {isApproveOpen && selectedDb && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in" onClick={() => setIsApproveOpen(false)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200" onClick={e => e.stopPropagation()}>
            <div className="p-6 md:p-8">
              <div className="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 mx-auto">
                <CheckCircle2 size={24} />
              </div>
              <h3 className="text-xl font-bold text-center text-slate-900 mb-2">이 디비를 승인하시겠습니까?</h3>
              <p className="text-sm text-center text-slate-500 mb-6">
                승인 처리 시 해당 디비는 유효 디비로 확정되며, 광고비가 확정 차감됩니다.
              </p>
              
              <div className="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3 mb-6 text-sm">
                <div className="flex justify-between">
                  <span className="text-slate-500">고객명</span>
                  <span className="font-bold text-slate-900">{selectedDb.name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">광고상품명</span>
                  <span className="font-medium text-slate-700">{selectedDb.campaign}</span>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-200">
                  <span className="text-slate-500">광고비 차감액</span>
                  <span className="font-bold text-lg text-slate-900">{selectedDb.price.toLocaleString()}원</span>
                </div>
              </div>

              <div className="mb-6 text-xs text-center text-blue-600 bg-blue-50 py-2 rounded-lg font-medium">
                파트너 수익에 즉시 반영됩니다.
              </div>

              <div className="mb-6">
                <label className="block text-sm font-medium text-slate-700 mb-2">리드 품질 평가</label>
                <div className="flex gap-2 mb-3">
                  {[1, 2, 3, 4, 5].map((score) => (
                    <button
                      key={score}
                      type="button"
                      onClick={() => setQualityScore(score)}
                      className={`w-9 h-9 rounded-full text-sm font-bold border ${qualityScore === score ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-300'}`}
                    >
                      {score}
                    </button>
                  ))}
                </div>
                <div className="flex flex-wrap gap-2">
                  {['상담예약', '구매의사', '지역적합', '연락원활', '중복의심'].map((tag) => {
                    const active = qualityTags.includes(tag);
                    return (
                      <button
                        key={tag}
                        type="button"
                        onClick={() => setQualityTags((prev) => (active ? prev.filter((t) => t !== tag) : [...prev, tag]))}
                        className={`px-2.5 py-1 rounded-lg text-xs font-bold border ${active ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : 'bg-slate-50 text-slate-600 border-slate-200'}`}
                      >
                        {tag}
                      </button>
                    );
                  })}
                </div>
                <p className="text-xs text-slate-500 mt-2">3점 이하 품질은 파트너에게 피드백 알림이 발송됩니다.</p>
              </div>

              <div className="mb-2">
                <label className="block text-sm font-medium text-slate-700 mb-2">승인 코멘트 (선택)</label>
                <input type="text" placeholder="예: 상담 예약 완료했습니다." value={approveComment} onChange={(e) => setApproveComment(e.target.value)} className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow" />
              </div>
            </div>
            
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
              <button onClick={() => setIsApproveOpen(false)} className="flex-1 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">
                취소
              </button>
              <button onClick={handleApproveConfirm} disabled={processing || !selectedDb?.cvId} className="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 transition-colors shadow-sm shadow-emerald-600/20 disabled:opacity-50">
                {processing ? '처리 중...' : '승인하기'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Reject Modal */}
      {isRejectOpen && selectedDb && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in" onClick={() => setIsRejectOpen(false)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200" onClick={e => e.stopPropagation()}>
            <div className="p-6 md:p-8">
              <div className="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-5 mx-auto">
                <XCircle size={24} />
              </div>
              <h3 className="text-xl font-bold text-center text-slate-900 mb-2">취소/무효 사유를 선택해주세요.</h3>
              <p className="text-sm text-center text-slate-500 mb-6">
                취소/무효 처리 시 파트너 수익에서 제외되며, 사유와 코멘트가 기록됩니다.
              </p>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">취소 사유 *</label>
                  <select 
                    value={cancelReason}
                    onChange={(e) => setCancelReason(e.target.value)}
                    className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-shadow appearance-none"
                  >
                    <option value="" disabled>사유를 선택해주세요</option>
                    <option value="연락불가">연락불가 (3회 이상 부재 등)</option>
                    <option value="중복디비">중복디비</option>
                    <option value="장난접수">장난/허위 정보 접수</option>
                    <option value="조건불일치">조건불일치 (나이/지역 등)</option>
                    <option value="지역불가">서비스 불가 지역</option>
                    <option value="이미상담">이미 상담받은 고객</option>
                    <option value="기타">기타</option>
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">상세 코멘트</label>
                  <textarea 
                    value={cancelComment}
                    onChange={(e) => setCancelComment(e.target.value)}
                    placeholder="상세한 취소 사유를 남겨주세요." 
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-shadow min-h-[100px] resize-none"
                  />
                </div>

                <div className="flex items-start gap-2 pt-2">
                  <input type="checkbox" id="partnerPublic" checked={partnerVisible} onChange={(e) => setPartnerVisible(e.target.checked)} className="mt-1 w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <label htmlFor="partnerPublic" className="text-sm text-slate-700 cursor-pointer">
                    <span className="font-medium block mb-0.5">파트너에게 사유 공개</span>
                    <span className="text-slate-500 text-xs block">체크 시 파트너도 취소 사유와 코멘트를 확인할 수 있습니다.</span>
                  </label>
                </div>
              </div>
            </div>
            
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
              <button onClick={() => setIsRejectOpen(false)} className="flex-1 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">
                닫기
              </button>
              <button 
                onClick={handleRejectConfirm}
                disabled={!cancelReason || processing || !selectedDb?.cvId}
                className="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-red-600/20"
              >
                {processing ? '처리 중...' : '무효 처리하기'}
              </button>
            </div>
          </div>
        </div>
      )}

      {isReportOpen && selectedDb && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in" onClick={() => setIsReportOpen(false)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200" onClick={(e) => e.stopPropagation()}>
            <div className="p-6 md:p-8">
              <div className="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-5 mx-auto">
                <AlertTriangle size={24} />
              </div>
              <h3 className="text-xl font-bold text-center text-slate-900 mb-2">금지 채널 유입 신고</h3>
              <p className="text-sm text-center text-slate-500 mb-6">
                금지된 채널(예: 성인, 도박, 스팸 등)을 통한 유입으로 의심될 경우 신고해 주세요. 관리자가 검토 후 파트너 제재 워크플로우를 생성합니다.
              </p>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">유입 채널</label>
                  <input
                    type="text"
                    value={reportChannel}
                    onChange={(e) => setReportChannel(e.target.value)}
                    placeholder={selectedDb.channel || '채널명 입력'}
                    className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-orange-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">신고 사유 *</label>
                  <textarea
                    value={reportReason}
                    onChange={(e) => setReportReason(e.target.value)}
                    placeholder="금지 채널 유입 근거를 상세히 입력해 주세요."
                    className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-orange-500 min-h-[100px] resize-none"
                  />
                </div>
              </div>
            </div>
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
              <button type="button" onClick={() => setIsReportOpen(false)} className="flex-1 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">
                닫기
              </button>
              <button
                type="button"
                onClick={handleReportChannel}
                disabled={!reportReason.trim() || processing || !selectedDb?.cvId}
                className="flex-1 px-4 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-colors disabled:opacity-50"
              >
                {processing ? '신고 중...' : '신고 접수'}
              </button>
            </div>
          </div>
        </div>
      )}

    </AdvertiserLayout>
  );
}
