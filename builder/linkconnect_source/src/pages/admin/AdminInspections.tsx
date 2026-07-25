import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import {
  ShieldAlert, Clock, CheckCircle2, XCircle, AlertTriangle, Percent,
  Search, Calendar, ChevronDown, Download, X, User, MessageSquare,
  Building2, Users, Check, X as XIcon, Bot,
} from 'lucide-react';
import {
  AdminInspection,
  AdminInspectionSummary,
  fetchAdminInspections,
  updateAdminInspection,
} from '../../lib/api';

const emptySummary: AdminInspectionSummary = {
  pending: 0,
  todayCancel: 0,
  confirmed: 0,
  restored: 0,
  appeals: 0,
  cancelRate: 0,
};

export function AdminInspections() {
  const [items, setItems] = useState<AdminInspection[]>([]);
  const [summary, setSummary] = useState<AdminInspectionSummary>(emptySummary);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [adminMemo, setAdminMemo] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [isAIFiltering, setIsAIFiltering] = useState(false);
  const [showAbuseOnly, setShowAbuseOnly] = useState(false);

  const selectedItem = useMemo(
    () => items.find((item) => item.cvId === selectedId) ?? null,
    [items, selectedId],
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchAdminInspections({ q: searchQuery, status: statusFilter });
      setItems(data.items);
      setSummary(data.summary);
    } catch (err) {
      setError(err instanceof Error ? err.message : '검수 목록을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  }, [searchQuery, statusFilter]);

  useEffect(() => {
    load();
  }, [load]);

  const handleReview = async (action: 'confirm' | 'restore') => {
    if (!selectedItem) return;
    setSaving(true);
    setError('');
    try {
      await updateAdminInspection({ action, cvId: selectedItem.cvId, memo: adminMemo });
      setAdminMemo('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '처리에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  const getReasonStyle = (reason: string) => {
    switch(reason) {
      case '연락불가': return 'bg-slate-100 text-slate-700 border-slate-200';
      case '중복디비': return 'bg-orange-50 text-orange-700 border-orange-200';
      case '장난접수': return 'bg-red-50 text-red-700 border-red-200';
      case '조건불일치': return 'bg-blue-50 text-blue-700 border-blue-200';
      case '지역불가': return 'bg-purple-50 text-purple-700 border-purple-200';
      case '허위정보': return 'bg-red-100 text-red-800 border-red-300';
      default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
  };

  return (
    <AdminLayout activeMenu="inspections" title="취소/무효 검수" description="광고주가 취소 또는 무효 처리한 디비를 검수하고 분쟁을 관리하세요.">
      
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="검수 대기" value={summary.pending.toLocaleString()} suffix="건" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="오늘 취소 요청" value={summary.todayCancel.toLocaleString()} suffix="건" icon={<ShieldAlert size={18} />} />
        <SummaryCard title="취소 승인" value={summary.confirmed.toLocaleString()} suffix="건" color="emerald" highlight icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="취소 반려" value={summary.restored.toLocaleString()} suffix="건" color="red" highlight icon={<XCircle size={18} />} />
        <SummaryCard title="이의신청" value={summary.appeals.toLocaleString()} suffix="건" color="orange" highlight icon={<AlertTriangle size={18} />} />
        <SummaryCard title="평균 취소율" value={String(summary.cancelRate)} suffix="%" dark icon={<Percent size={18} />} />
      </div>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <div className={`flex flex-col ${selectedItem ? 'lg:col-span-2' : 'lg:col-span-3 xl:col-span-4'}`}>
          {/* Filter Area */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex items-center gap-2">
                <div className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>오늘</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 광고상품</option>
                  <option>개인회생 무료상담 이벤트</option>
                  <option>직장인 신용대출 한도조회</option>
                </select>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 검수 상태</option>
                  <option>검수대기</option>
                  <option>이의신청중</option>
                  <option>취소승인</option>
                  <option>취소반려</option>
                </select>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>취소 사유 전체</option>
                  <option>연락불가</option>
                  <option>중복디비</option>
                  <option>장난접수</option>
                  <option>조건불일치</option>
                </select>
                
                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer ml-2">
                  <input type="checkbox" className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  이의신청 건만 보기
                </label>
                <button onClick={() => { setIsAIFiltering(true); setTimeout(() => { setIsAIFiltering(false); setShowAbuseOnly(!showAbuseOnly); }, 1500); }} disabled={isAIFiltering} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors ml-2 shadow-sm ${showAbuseOnly ? "bg-indigo-600 text-white hover:bg-indigo-700" : "bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700"} disabled:opacity-50`}>
                  <Bot size={16} /> AI 어뷰징 의심 조회
                </button>

              </div>
            </div>
            <div className="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-4">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    placeholder="고객명, 연락처, 파트너명, 디비코드 검색"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
              <div className="text-sm font-medium text-slate-600">검색결과 <strong className="text-cyan-600">{items.length}</strong>건</div>
              <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                <Download size={14} /> 엑셀 다운로드
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-white border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">요청일/디비코드</th>
                    <th className="px-4 py-3 font-medium">광고상품/광고주</th>
                    <th className="px-4 py-3 font-medium">파트너/고객명</th>
                    <th className="px-4 py-3 font-medium">취소 사유/코멘트</th>
                    <th className="px-4 py-3 font-medium text-center">검수 상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr><td colSpan={6} className="px-4 py-10 text-center text-slate-500">불러오는 중...</td></tr>
                  ) : items.filter((item) => !showAbuseOnly || item.reason.includes('허위')).map((item) => (
                    <tr
                      key={item.cvId}
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedId === item.cvId ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => setSelectedId(item.cvId)}
                    >
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-medium text-slate-900 mb-0.5">{item.date}</div>
                        <div className="text-xs text-slate-500">{item.id}</div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-bold text-slate-900 mb-0.5 truncate max-w-[200px]">{item.campaign}</div>
                        <div className="text-xs text-slate-500">{item.advertiser}</div>
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-medium text-slate-700 mb-0.5">{item.partner}</div>
                        <div className="text-xs font-bold text-slate-900">
                          {item.customer} <span className="font-normal text-slate-500 ml-1">({item.phone})</span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2 mb-1">
                          <span className={`text-[11px] px-2 py-0.5 rounded border ${getReasonStyle(item.reason)} font-medium`}>
                            {item.reason}
                          </span>
                          {item.objection && (
                            <span className="text-[11px] px-2 py-0.5 rounded border bg-orange-50 text-orange-700 border-orange-200 font-bold flex items-center gap-1">
                              <AlertTriangle size={10} /> 이의신청
                            </span>
                          )}
                        </div>
                        <div className="text-xs text-slate-600 truncate max-w-[250px]">{item.comment}</div>
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">상세검수</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar / Detail Panel */}
        {selectedItem && (
          <div className="lg:col-span-1 xl:col-span-2 flex flex-col h-[calc(100vh-180px)] sticky top-[100px]">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <div className="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center sticky top-0 z-20">
                <div className="flex items-center gap-3">
                  <h3 className="font-bold text-slate-900 flex items-center gap-2">
                    취소/무효 상세 검수
                  </h3>
                  <StatusBadge status={selectedItem.status} />
                </div>
                <button 
                  onClick={() => setSelectedId(null)}
                  className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-6 bg-white hide-scrollbar">
                
                {/* 1. 디비 원본 정보 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <FileText size={16} className="text-slate-500" /> 디비 원본 정보
                  </h4>
                  <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="block text-xs text-slate-500 mb-1">고객명 / 연락처</span>
                      <span className="font-bold text-slate-900">{selectedItem.customer} <span className="font-normal text-slate-600 ml-1">({selectedItem.phone})</span></span>
                    </div>
                    <div>
                      <span className="block text-xs text-slate-500 mb-1">접수일시</span>
                      <span className="font-medium text-slate-900">2026.07.06 09:00:15</span>
                    </div>
                    <div className="col-span-2">
                      <span className="block text-xs text-slate-500 mb-1">문의내용</span>
                      <p className="font-medium text-slate-900 bg-white p-3 rounded-lg border border-slate-200">
                        대출 한도 조회 부탁드립니다. 직장인이고 연봉 4천입니다.
                      </p>
                    </div>
                    <div className="col-span-2">
                      <span className="block text-xs text-slate-500 mb-1">유입경로</span>
                      <span className="font-medium text-blue-600 break-all text-xs">
                        https://blog.naver.com/test_blog/223412341234
                      </span>
                    </div>
                  </div>
                </section>

                {/* 2. 광고주 취소 정보 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <Building2 size={16} className="text-slate-500" /> 광고주 취소 요청 정보
                  </h4>
                  <div className="border border-slate-200 p-4 rounded-xl space-y-4">
                    <div className="flex justify-between items-start">
                      <div>
                        <span className="block text-xs text-slate-500 mb-1">취소 사유</span>
                        <span className={`inline-block px-2.5 py-1 rounded-md text-sm font-bold border ${getReasonStyle(selectedItem.reason)}`}>
                          {selectedItem.reason}
                        </span>
                      </div>
                      <div className="text-right text-xs">
                        <div className="text-slate-500 mb-0.5">요청일시</div>
                        <div className="font-medium text-slate-900">{selectedItem.date}</div>
                      </div>
                    </div>
                    <div>
                      <span className="block text-xs text-slate-500 mb-1">광고주 코멘트</span>
                      <div className="bg-slate-50 p-3 rounded-lg text-sm text-slate-800 font-medium">
                        {selectedItem.comment}
                      </div>
                    </div>
                  </div>
                </section>

                {/* 3. 파트너 이의신청 정보 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <Users size={16} className="text-slate-500" /> 파트너 이의신청 정보
                  </h4>
                  {selectedItem.objection ? (
                    <div className="border border-orange-200 bg-orange-50/30 p-4 rounded-xl space-y-3">
                      <div className="flex items-center gap-2 mb-2">
                        <AlertTriangle size={16} className="text-orange-600" />
                        <span className="font-bold text-orange-800 text-sm">이의신청 접수됨</span>
                      </div>
                      <div>
                        <span className="block text-xs text-orange-700/70 mb-1">파트너 의견</span>
                        <div className="bg-white p-3 rounded-lg text-sm text-slate-800 font-medium border border-orange-100">
                          {selectedItem.objectionComment}
                        </div>
                      </div>
                    </div>
                  ) : (
                    <div className="bg-slate-50 border border-slate-200 p-4 rounded-xl text-center text-sm text-slate-500">
                      접수된 이의신청이 없습니다.
                    </div>
                  )}
                </section>

                {/* 4. 관리자 판단 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100">
                    관리자 처리 (검수 의견)
                  </h4>
                  <div className="space-y-4">
                    <textarea
                      value={adminMemo}
                      onChange={(e) => setAdminMemo(e.target.value)}
                      className="w-full p-3 rounded-xl border border-slate-200 text-sm resize-none h-24 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                      placeholder="검수 결과 및 처리 사유를 메모하세요 (내부 관리용)"
                    />
                    
                    <div className="grid grid-cols-2 gap-3">
                      <button onClick={() => handleReview('confirm')} disabled={saving} className="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 border border-emerald-600 transition-colors shadow-sm disabled:opacity-50">
                        <CheckCircle2 size={24} />
                        <span className="font-bold text-sm">취소 승인</span>
                      </button>
                      <button onClick={() => handleReview('restore')} disabled={saving} className="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 transition-colors shadow-sm disabled:opacity-50">
                        <XIcon size={24} />
                        <span className="font-bold text-sm">취소 반려</span>
                      </button>
                    </div>
                  </div>
                </section>

              </div>
            </div>
          </div>
        )}
      </div>

      {/* Bottom Insights */}
      {!selectedItem && (
        <div>
          <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <AlertCircle size={20} className="text-orange-500" />
            취소율 주의 대상
          </h3>
          <div className="grid md:grid-cols-3 gap-6">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
              <h4 className="text-sm font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">취소율 높은 광고주 TOP 5</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">1. 스피드렌터카</span>
                  <span className="font-bold text-red-500">31.4%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">2. 에듀스터디</span>
                  <span className="font-bold text-orange-500">20.1%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">3. 희망법무법인</span>
                  <span className="font-bold text-slate-900">15.2%</span>
                </div>
              </div>
            </div>
            
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
              <h4 className="text-sm font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">취소율 높은 파트너 TOP 5</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">1. 어뷰징의심 (PT-9912)</span>
                  <span className="font-bold text-red-500">88.5%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">2. 정카페 (PT-7731)</span>
                  <span className="font-bold text-orange-500">33.3%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">3. 김마케터 (PT-8832)</span>
                  <span className="font-bold text-slate-900">17.2%</span>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
              <h4 className="text-sm font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">반복 취소 사유 TOP 5</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">1. 연락불가</span>
                  <span className="font-bold text-slate-900">42%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">2. 단순변심</span>
                  <span className="font-bold text-slate-900">28%</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="font-medium text-slate-700">3. 조건불일치</span>
                  <span className="font-bold text-slate-900">15%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

    </AdminLayout>
  );
}
