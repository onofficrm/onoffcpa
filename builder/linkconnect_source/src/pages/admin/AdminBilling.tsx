import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  CreditCard, Wallet, ArrowDownCircle, ArrowUpCircle, RefreshCcw, AlertCircle,
  Search, Calendar, ChevronDown, Download, CheckCircle2, 
  PlusCircle, MinusCircle, X, Check,
  AlertTriangle
} from 'lucide-react';
import { AdminPendingCharge, AdminMerchantBalance, AdminWalletSummary, AdminWalletTransaction, adjustAdminWallet, fetchAdminPendingCharges, fetchAdminWalletBalances, fetchAdminWalletHistory, fetchAdminWalletSummary, updateAdminCharge } from '../../lib/api';

const emptySummary: AdminWalletSummary = {
  totalBalance: 0,
  totalPending: 0,
  todayCharge: 0,
  todaySpend: 0,
  todayRefund: 0,
  lowBalance: 0,
};

export function AdminBilling() {
  const [activeTab, setActiveTab] = useState<'balance' | 'requests' | 'history'>('balance');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalType, setModalType] = useState<'충전' | '차감' | '환급' | '조정'>('충전');
  const [selectedMerchant, setSelectedMerchant] = useState<AdminMerchantBalance | null>(null);
  const [adjustAmount, setAdjustAmount] = useState('');
  const [adjustMemo, setAdjustMemo] = useState('');
  const [summary, setSummary] = useState<AdminWalletSummary>(emptySummary);
  const [balances, setBalances] = useState<AdminMerchantBalance[]>([]);
  const [history, setHistory] = useState<AdminWalletTransaction[]>([]);
  const [chargeRequests, setChargeRequests] = useState<AdminPendingCharge[]>([]);
  const [pendingCount, setPendingCount] = useState(0);
  const [processingId, setProcessingId] = useState<number | null>(null);
  const [searchQ, setSearchQ] = useState('');

  const loadSummary = useCallback(() => {
    fetchAdminWalletSummary()
      .then((data) => {
        setSummary(data.summary);
        setPendingCount(data.pending);
      })
      .catch(() => {});
  }, []);

  const loadBalances = useCallback((q = searchQ) => {
    fetchAdminWalletBalances(q)
      .then((data) => {
        setBalances(data.items);
        setSummary(data.summary);
      })
      .catch(() => {});
  }, [searchQ]);

  const loadHistory = useCallback((q = searchQ) => {
    fetchAdminWalletHistory({ q })
      .then((data) => {
        setHistory(data.items);
        setSummary(data.summary);
      })
      .catch(() => {});
  }, [searchQ]);

  const loadPendingCharges = useCallback(() => {
    fetchAdminPendingCharges()
      .then((data) => {
        setChargeRequests(data.items);
        setPendingCount(data.pending);
      })
      .catch(() => {});
  }, []);

  useEffect(() => {
    loadSummary();
  }, [loadSummary]);

  useEffect(() => {
    if (activeTab === 'balance') loadBalances();
    if (activeTab === 'requests') loadPendingCharges();
    if (activeTab === 'history') loadHistory();
  }, [activeTab, loadBalances, loadHistory, loadPendingCharges]);

  const handleApproveCharge = async (wtId: number) => {
    setProcessingId(wtId);
    try {
      await updateAdminCharge({ action: 'approve', wtId });
      loadPendingCharges();
    } catch {
      // ignore — UI keeps row
    } finally {
      setProcessingId(null);
    }
  };

  const handleRejectCharge = async (wtId: number) => {
    setProcessingId(wtId);
    try {
      await updateAdminCharge({ action: 'reject', wtId });
      loadPendingCharges();
    } catch {
      // ignore
    } finally {
      setProcessingId(null);
    }
  };

  const openModal = (type: '충전' | '차감' | '환급' | '조정', merchant?: AdminMerchantBalance) => {
    setModalType(type);
    setSelectedMerchant(merchant ?? null);
    setAdjustAmount('');
    setAdjustMemo('');
    setIsModalOpen(true);
  };

  const handleAdjust = async () => {
    if (!selectedMerchant || !adjustAmount) return;
    const typeMap = { '충전': 'charge', '차감': 'deduct', '환급': 'refund', '조정': 'adjust' } as const;
    try {
      await adjustAdminWallet({
        mtId: selectedMerchant.id,
        type: typeMap[modalType],
        amount: Number(adjustAmount),
        memo: adjustMemo,
      });
      setIsModalOpen(false);
      loadSummary();
      if (activeTab === 'balance') loadBalances();
      if (activeTab === 'history') loadHistory();
    } catch {
      // ignore
    }
  };

  const displayRequests = chargeRequests;
  const requestCount = pendingCount;
  const balanceRows = balances;
  const historyRows = history;

  return (
    <AdminLayout activeMenu="billing" title="광고비 관리" description="광고주별 광고비 잔액과 충전/차감/환급 내역을 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 광고비 잔액" value={summary.totalBalance.toLocaleString()} suffix="원" dark icon={<Wallet size={18} />} />
        <SummaryCard title="충전 대기" value={summary.totalPending.toLocaleString()} suffix="원" color="yellow" highlight icon={<MinusCircle size={18} />} />
        <SummaryCard title="오늘 충전액" value={summary.todayCharge.toLocaleString()} suffix="원" color="cyan" highlight icon={<ArrowUpCircle size={18} />} />
        <SummaryCard title="오늘 사용액" value={summary.todaySpend.toLocaleString()} suffix="원" icon={<ArrowDownCircle size={18} />} />
        <SummaryCard title="환급 처리액" value={summary.todayRefund.toLocaleString()} suffix="원" color="emerald" highlight icon={<RefreshCcw size={18} />} />
        <SummaryCard title="광고비 부족 광고주" value={String(summary.lowBalance)} suffix="곳" color="red" highlight icon={<AlertCircle size={18} />} />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        {/* Tabs */}
        <div className="flex border-b border-slate-200">
          <button 
            className={`flex-1 py-4 text-sm font-bold transition-colors border-b-2 ${activeTab === 'balance' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
            onClick={() => setActiveTab('balance')}
          >
            광고주 광고비 목록
          </button>
          <button 
            className={`flex-1 py-4 text-sm font-bold transition-colors border-b-2 ${activeTab === 'requests' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700'} flex items-center justify-center gap-2`}
            onClick={() => setActiveTab('requests')}
          >
            충전 신청 <span className="bg-cyan-100 text-cyan-700 py-0.5 px-2 rounded-full text-xs">{requestCount}건</span>
          </button>
          <button 
            className={`flex-1 py-4 text-sm font-bold transition-colors border-b-2 ${activeTab === 'history' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
            onClick={() => setActiveTab('history')}
          >
            전체 거래 내역
          </button>
        </div>

        {/* Tab Content */}
        <div className="p-6">
          
          {/* Filters */}
          <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
            <div className="flex-1 min-w-[200px]">
              <div className="relative">
                <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={searchQ}
                  onChange={(e) => setSearchQ(e.target.value)}
                  placeholder="광고주명 검색"
                  className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                />
              </div>
            </div>
            <button type="button" onClick={() => { if (activeTab === 'balance') loadBalances(searchQ); if (activeTab === 'history') loadHistory(searchQ); }} className="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold">조회</button>
            
            <div className="flex flex-wrap items-center gap-2">
              <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                <option>전체 상태</option>
                <option>정상</option>
                <option>광고비 부족</option>
                <option>사용중지</option>
              </select>
              {activeTab === 'history' && (
                <>
                  <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                    <option>전체 구분</option>
                    <option>충전</option>
                    <option>가차감</option>
                    <option>확정차감</option>
                    <option>환급</option>
                    <option>수동조정</option>
                  </select>
                  <div className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                    <Calendar size={16} className="text-slate-500" />
                    <span>기간 선택</span>
                    <ChevronDown size={14} className="text-slate-500" />
                  </div>
                </>
              )}
            </div>
          </div>

          {/* Tables */}
          <div className="overflow-x-auto">
            
            {activeTab === 'balance' && (
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">광고주명</th>
                    <th className="px-4 py-3 font-medium text-right">현재 잔액</th>
                    <th className="px-4 py-3 font-medium text-right text-yellow-600">가차감 금액</th>
                    <th className="px-4 py-3 font-medium text-right text-cyan-600">사용 가능 잔액</th>
                    <th className="px-4 py-3 font-medium text-right">총 충전/사용/환급</th>
                    <th className="px-4 py-3 font-medium text-center">최근 충전일</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">수동 관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {balanceRows.length === 0 ? (
                    <tr><td colSpan={8} className="px-4 py-8 text-center text-slate-500">광고주 데이터가 없습니다.</td></tr>
                  ) : balanceRows.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-4 font-bold text-slate-900 whitespace-nowrap">{item.name}</td>
                      <td className="px-4 py-4 text-right font-bold text-slate-900 whitespace-nowrap">{item.balance.toLocaleString()}원</td>
                      <td className="px-4 py-4 text-right font-medium text-yellow-600 whitespace-nowrap">{item.pending > 0 ? `-${item.pending.toLocaleString()}` : 0}원</td>
                      <td className={`px-4 py-4 text-right font-bold whitespace-nowrap ${item.available < 0 ? 'text-red-500' : 'text-cyan-600'}`}>{item.available.toLocaleString()}원</td>
                      <td className="px-4 py-4 text-right whitespace-nowrap text-xs text-slate-500 space-y-1">
                        <div>충전: <span className="font-medium text-slate-700">{item.totalCharged.toLocaleString()}</span></div>
                        <div>사용: <span className="font-medium text-slate-700">{item.totalUsed.toLocaleString()}</span></div>
                        <div>환급: <span className="font-medium text-slate-700">{item.totalRefund.toLocaleString()}</span></div>
                      </td>
                      <td className="px-4 py-4 text-center font-medium text-slate-600 whitespace-nowrap">{item.lastCharged}</td>
                      <td className="px-4 py-4 text-center whitespace-nowrap"><StatusBadge status={item.status} /></td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <div className="flex items-center justify-center gap-1">
                          <button type="button" onClick={() => openModal('충전', item)} className="px-2 py-1 bg-cyan-50 text-cyan-700 hover:bg-cyan-100 rounded text-xs font-bold">충전</button>
                          <button type="button" onClick={() => openModal('차감', item)} className="px-2 py-1 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded text-xs font-bold">차감</button>
                          <button type="button" onClick={() => openModal('환급', item)} className="px-2 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-xs font-bold">환급</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

            {activeTab === 'requests' && (
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">신청일</th>
                    <th className="px-4 py-3 font-medium">광고주</th>
                    <th className="px-4 py-3 font-medium text-right">신청 금액</th>
                    <th className="px-4 py-3 font-medium">입금자명</th>
                    <th className="px-4 py-3 font-medium text-center">세금계산서</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {displayRequests.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-4 font-medium text-slate-600 whitespace-nowrap">{item.date}</td>
                      <td className="px-4 py-4 font-bold text-slate-900 whitespace-nowrap">{item.merchant}</td>
                      <td className="px-4 py-4 text-right font-bold text-cyan-600 whitespace-nowrap text-base">
                        {item.amount.toLocaleString()}원
                      </td>
                      <td className="px-4 py-4 font-medium text-slate-700 whitespace-nowrap">{item.memo || '-'}</td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <span className="text-xs font-bold px-2 py-1 rounded bg-slate-100 text-slate-500">미요청</span>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        {item.id > 0 ? (
                          <div className="flex items-center justify-center gap-1">
                            <button
                              type="button"
                              disabled={processingId === item.id}
                              onClick={() => handleApproveCharge(item.id)}
                              className="px-3 py-1.5 bg-cyan-600 text-white hover:bg-cyan-700 disabled:opacity-60 rounded text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-1"
                            >
                              <CheckCircle2 size={14} /> {processingId === item.id ? '처리중' : '승인'}
                            </button>
                            <button
                              type="button"
                              disabled={processingId === item.id}
                              onClick={() => handleRejectCharge(item.id)}
                              className="px-2 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded text-xs font-bold transition-colors"
                            >
                              반려
                            </button>
                          </div>
                        ) : (
                          <button type="button" className="px-3 py-1.5 bg-cyan-600 text-white hover:bg-cyan-700 rounded text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-1 mx-auto">
                            <CheckCircle2 size={14} /> 충전 승인
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

            {activeTab === 'history' && (
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">일시</th>
                    <th className="px-4 py-3 font-medium">광고주</th>
                    <th className="px-4 py-3 font-medium text-center">구분</th>
                    <th className="px-4 py-3 font-medium">관련 디비 / 광고상품</th>
                    <th className="px-4 py-3 font-medium text-right">금액</th>
                    <th className="px-4 py-3 font-medium text-right">처리 후 잔액</th>
                    <th className="px-4 py-3 font-medium">처리자 / 메모</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {historyRows.length === 0 ? (
                    <tr><td colSpan={7} className="px-4 py-8 text-center text-slate-500">거래 내역이 없습니다.</td></tr>
                  ) : historyRows.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-4 font-medium text-slate-600 whitespace-nowrap">{item.date}</td>
                      <td className="px-4 py-4 font-bold text-slate-900 whitespace-nowrap">{item.merchant}</td>
                      <td className="px-4 py-4 text-center whitespace-nowrap"><StatusBadge status={item.type} /></td>
                      <td className="px-4 py-4">
                        <div className="text-xs text-slate-500 mb-0.5">{item.dbCode}</div>
                        <div className="font-medium text-slate-800 truncate max-w-[200px]">{item.campaign}</div>
                      </td>
                      <td className={`px-4 py-4 text-right font-bold whitespace-nowrap ${item.amount > 0 ? 'text-cyan-600' : 'text-slate-900'}`}>
                        {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                      </td>
                      <td className="px-4 py-4 text-right font-medium text-slate-700 whitespace-nowrap">{item.balance.toLocaleString()}원</td>
                      <td className="px-4 py-4 text-xs text-slate-500">{item.processor} / {item.memo}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

          </div>
        </div>
      </div>

      {/* Manual Processing Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
              <h3 className="font-bold text-slate-900">광고비 수동 {modalType}</h3>
              <button 
                onClick={() => setIsModalOpen(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg transition-colors"
              >
                <X size={18} />
              </button>
            </div>
            
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">광고주 선택</label>
                <select
                  value={selectedMerchant?.id ?? ''}
                  onChange={(e) => {
                    const id = Number(e.target.value);
                    setSelectedMerchant(balances.find((m) => m.id === id) ?? null);
                  }}
                  className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-medium focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                >
                  <option value="">광고주 선택</option>
                  {balances.map((m) => (
                    <option key={m.id} value={m.id}>{m.name} (잔액: {m.balance.toLocaleString()}원)</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">처리 유형</label>
                <div className="flex gap-2">
                  <button className={`flex-1 py-2 rounded-xl text-sm font-bold border transition-colors ${modalType === '충전' ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-white border-slate-200 text-slate-600'}`} onClick={() => setModalType('충전')}>수동 충전</button>
                  <button className={`flex-1 py-2 rounded-xl text-sm font-bold border transition-colors ${modalType === '차감' ? 'bg-slate-800 border-slate-900 text-slate-100' : 'bg-white border-slate-200 text-slate-600'}`} onClick={() => setModalType('차감')}>수동 차감</button>
                  <button className={`flex-1 py-2 rounded-xl text-sm font-bold border transition-colors ${modalType === '환급' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-slate-200 text-slate-600'}`} onClick={() => setModalType('환급')}>환급</button>
                  <button className={`flex-1 py-2 rounded-xl text-sm font-bold border transition-colors ${modalType === '조정' ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-white border-slate-200 text-slate-600'}`} onClick={() => setModalType('조정')}>기타 조정</button>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">금액</label>
                <div className="relative">
                  <input 
                    type="number" 
                    placeholder="0"
                    value={adjustAmount}
                    onChange={(e) => setAdjustAmount(e.target.value)}
                    className="w-full pl-3 pr-8 py-2.5 bg-white border border-slate-300 rounded-xl text-lg font-bold focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-medium">원</span>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">처리 사유 및 메모 (관리자용)</label>
                <textarea 
                  placeholder={`${modalType} 사유를 상세히 기록해주세요.`}
                  value={adjustMemo}
                  onChange={(e) => setAdjustMemo(e.target.value)}
                  className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm h-20 resize-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                ></textarea>
              </div>

              <div className="bg-amber-50 border border-amber-200 p-3 rounded-xl flex items-start gap-2 text-amber-800">
                <AlertTriangle size={16} className="mt-0.5 shrink-0" />
                <p className="text-xs font-medium">광고비 수동 변경은 모든 거래 내역에 영구적으로 기록되며 삭제할 수 없습니다. 정확한 금액을 입력해주세요.</p>
              </div>
            </div>

            <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
              <button 
                onClick={() => setIsModalOpen(false)}
                className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors shadow-sm"
              >
                취소
              </button>
              <button 
                type="button"
                onClick={handleAdjust}
                disabled={!selectedMerchant || !adjustAmount}
                className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Check size={16} /> 처리 완료
              </button>
            </div>
          </div>
        </div>
      )}

    </AdminLayout>
  );
}
