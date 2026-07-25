import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import {
  Banknote, Calendar, Search, ChevronDown, CheckCircle2,
  XCircle, Clock, Check, X, FileText, AlertTriangle, User,
} from 'lucide-react';
import {
  AdminSettlement,
  AdminSettlementSummary,
  fetchAdminSettlements,
  fetchSettlementRisk,
  updateAdminSettlement,
} from '../../lib/api';

const emptySummary: AdminSettlementSummary = {
  pending: 0,
  pendingAmount: 0,
  todayApproved: 0,
  monthPaid: 0,
  hold: 0,
  rejected: 0,
};

export function AdminSettlements() {
  const [items, setItems] = useState<AdminSettlement[]>([]);
  const [summary, setSummary] = useState<AdminSettlementSummary>(emptySummary);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [approvedAmount, setApprovedAmount] = useState<number | ''>('');
  const [adminMemo, setAdminMemo] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [riskData, setRiskData] = useState<{ score: number; level: string; blocked: boolean; risks: Array<{ level: string; code: string; message: string }> } | null>(null);
  const [riskLoading, setRiskLoading] = useState(false);

  const selected = useMemo(
    () => items.find((item) => item.id === selectedId) ?? null,
    [items, selectedId],
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchAdminSettlements({ q: searchQuery, status: statusFilter });
      setItems(data.items);
      setSummary(data.summary);
    } catch (err) {
      setError(err instanceof Error ? err.message : '정산 목록을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  }, [searchQuery, statusFilter]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (selected) {
      setApprovedAmount(selected.approvedAmount || selected.requestAmount);
      setRiskLoading(true);
      fetchSettlementRisk(selected.id)
        .then((data) => setRiskData(data.risk))
        .catch(() => setRiskData(null))
        .finally(() => setRiskLoading(false));
    } else {
      setRiskData(null);
    }
  }, [selected]);

  const handleAction = async (action: 'review' | 'approve' | 'pay' | 'hold' | 'reject') => {
    if (!selected) return;
    setSaving(true);
    setError('');
    try {
      await updateAdminSettlement({
        action,
        stId: selected.id,
        approvedAmount: approvedAmount ? Number(approvedAmount) : undefined,
        memo: adminMemo,
      });
      setAdminMemo('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '처리에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminLayout activeMenu="settlements" title="정산 관리" description="파트너 수익 정산 신청을 검토하고 지급 상태를 관리하세요.">
      
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="정산 신청 대기" value={summary.pending.toLocaleString()} suffix="건" dark icon={<Banknote size={18} />} />
        <SummaryCard title="정산 대기 금액" value={summary.pendingAmount.toLocaleString()} suffix="원" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="오늘 승인 금액" value={summary.todayApproved.toLocaleString()} suffix="원" color="cyan" highlight icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="이번 달 지급완료" value={summary.monthPaid.toLocaleString()} suffix="원" color="emerald" highlight icon={<Check size={18} />} />
        <SummaryCard title="보류 건수" value={summary.hold.toLocaleString()} suffix="건" color="orange" icon={<AlertTriangle size={18} />} />
        <SummaryCard title="반려 건수" value={summary.rejected.toLocaleString()} suffix="건" color="red" icon={<XCircle size={18} />} />
      </div>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="flex flex-col lg:flex-row gap-6">
        
        {/* Main List */}
        <div className={`flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${selected ? 'hidden lg:block lg:w-2/3' : 'w-full'}`}>
          <div className="p-6">
            
            {/* Filters */}
            <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    placeholder="파트너명 또는 코드 검색"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              
              <div className="flex flex-wrap items-center gap-2">
                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option value="">전체 상태</option>
                  <option value="pending">신청완료</option>
                  <option value="reviewing">승인대기</option>
                  <option value="approved">승인완료</option>
                  <option value="paid">지급완료</option>
                  <option value="hold">보류</option>
                  <option value="rejected">반려</option>
                </select>
                <div className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>신청일 기준</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">신청일 / 정산코드</th>
                    <th className="px-4 py-3 font-medium">파트너 정보</th>
                    <th className="px-4 py-3 font-medium text-right">신청금액</th>
                    <th className="px-4 py-3 font-medium">계좌정보</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr><td colSpan={5} className="px-4 py-10 text-center text-slate-500">불러오는 중...</td></tr>
                  ) : items.length === 0 ? (
                    <tr><td colSpan={5} className="px-4 py-10 text-center text-slate-500">정산 신청 내역이 없습니다.</td></tr>
                  ) : items.map((item) => (
                    <tr
                      key={item.id}
                      className={`transition-colors cursor-pointer ${selectedId === item.id ? 'bg-cyan-50' : 'hover:bg-slate-50'}`}
                      onClick={() => setSelectedId(item.id)}
                    >
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-slate-600 mb-0.5">{item.date}</div>
                        <div className="text-xs text-slate-400">{item.id}</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{item.partnerName}</div>
                        <div className="text-xs text-slate-500">{item.partnerCode}</div>
                      </td>
                      <td className="px-4 py-4 text-right whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{item.requestAmount.toLocaleString()}원</div>
                        {item.status === '승인완료' || item.status === '지급완료' ? (
                           <div className="text-xs text-cyan-600 font-medium">승인: {item.approvedAmount.toLocaleString()}원</div>
                        ) : null}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-slate-700 mb-0.5">{item.bank} {item.account}</div>
                        <div className="text-xs text-slate-500">예금주: {item.accountHolder}</div>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

          </div>
        </div>

        {selected && (
          <div className="lg:w-1/3 w-full flex flex-col gap-4">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-120px)] sticky top-6">
              <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 className="font-bold text-slate-900 flex items-center gap-2"><FileText size={18} className="text-slate-400" />정산 상세 내역</h3>
                <button onClick={() => setSelectedId(null)} className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg transition-colors"><X size={18} /></button>
              </div>
              <div className="p-5 overflow-y-auto flex-1 space-y-6">
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2"><User size={16} className="text-cyan-600" />파트너 정보</h4>
                  <div className="bg-slate-50 rounded-xl p-4 text-sm space-y-3">
                    <div className="flex justify-between"><span className="text-slate-500">이름/상호명</span><span className="font-bold text-slate-900">{selected.partnerName} ({selected.partnerCode})</span></div>
                    <div className="flex justify-between"><span className="text-slate-500">계좌 정보</span><span className="font-bold text-slate-800">{selected.bank} {selected.account} ({selected.accountHolder})</span></div>
                  </div>
                </section>
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2"><Banknote size={16} className="text-cyan-600" />정산 금액</h4>
                  <div className="bg-slate-50 rounded-xl p-4 text-sm space-y-3">
                    <div className="flex justify-between"><span className="text-slate-500">신청 금액</span><span className="font-bold">{selected.requestAmount.toLocaleString()}원</span></div>
                    <div className="flex justify-between"><span className="text-slate-500">상태</span><StatusBadge status={selected.status} /></div>
                  </div>
                </section>
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2"><AlertTriangle size={16} className="text-amber-600" />정산 리스크 체크</h4>
                  {riskLoading ? (
                    <div className="text-sm text-slate-500">리스크 점검 중...</div>
                  ) : riskData ? (
                    <div className={`rounded-xl p-4 text-sm space-y-2 border ${riskData.blocked ? 'bg-red-50 border-red-200' : riskData.level === 'high' ? 'bg-orange-50 border-orange-200' : 'bg-emerald-50 border-emerald-200'}`}>
                      <div className="flex justify-between items-center">
                        <span className="font-bold text-slate-900">리스크 점수</span>
                        <span className={`font-bold ${riskData.blocked ? 'text-red-600' : 'text-slate-900'}`}>{riskData.score}점 ({riskData.level})</span>
                      </div>
                      {riskData.risks.length > 0 ? (
                        <ul className="space-y-1 mt-2">
                          {riskData.risks.map((r) => (
                            <li key={r.code} className={`text-xs ${r.level === 'high' ? 'text-red-700' : r.level === 'medium' ? 'text-orange-700' : 'text-slate-600'}`}>
                              • {r.message}
                            </li>
                          ))}
                        </ul>
                      ) : (
                        <p className="text-xs text-emerald-700">특이 리스크 없음</p>
                      )}
                      {riskData.blocked && <p className="text-xs font-bold text-red-700 mt-2">차단 조건 충족 — 승인/지급 전 확인 필요</p>}
                    </div>
                  ) : (
                    <div className="text-sm text-slate-500">리스크 정보 없음</div>
                  )}
                </section>
                <section className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                  <h4 className="text-sm font-bold text-slate-900 mb-3">관리자 처리</h4>
                  <div className="space-y-4">
                    <input type="number" value={approvedAmount} onChange={(e) => setApprovedAmount(Number(e.target.value))} className="w-full pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm font-bold focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" />
                    <textarea value={adminMemo} onChange={(e) => setAdminMemo(e.target.value)} placeholder="승인/보류/반려 사유 입력" className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm h-16 resize-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" />
                    <div className="grid grid-cols-2 gap-2 pt-2">
                      <button onClick={() => handleAction('hold')} disabled={saving} className="py-2 bg-slate-800 text-white rounded-lg text-sm font-bold disabled:opacity-50">승인 보류</button>
                      <button onClick={() => handleAction('reject')} disabled={saving} className="py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold disabled:opacity-50">반려 처리</button>
                      <button onClick={() => handleAction('approve')} disabled={saving || riskData?.blocked} className="py-2 bg-cyan-600 text-white rounded-lg text-sm font-bold col-span-2 disabled:opacity-50">승인 완료</button>
                      <button onClick={() => handleAction('pay')} disabled={saving || riskData?.blocked} className="py-2 bg-emerald-600 text-white rounded-lg text-sm font-bold col-span-2 disabled:opacity-50">최종 지급완료 처리</button>
                    </div>
                  </div>
                </section>
              </div>
            </div>
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
              <AlertTriangle size={18} className="text-amber-600 mt-0.5 shrink-0" />
              <div className="text-xs text-amber-800 space-y-1">
                <p className="font-bold mb-1">정산 처리 주의사항</p>
                <p>승인완료된 수익만 정산 대상입니다. 지급완료 후에는 수정할 수 없습니다.</p>
              </div>
            </div>
          </div>
        )}

      </div>
    </AdminLayout>
  );
}
