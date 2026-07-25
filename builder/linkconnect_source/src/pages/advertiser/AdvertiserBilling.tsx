import React, { useEffect, useMemo, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { Wallet, CreditCard, ArrowRight, History, Download, Filter, AlertTriangle } from 'lucide-react';
import { fetchMerchantWallet, MerchantWalletTransaction, requestMerchantCharge } from '../../lib/api';

const TypeBadge = ({ type }: { type: string }) => {
  const styles: Record<string, string> = {
    '충전': 'text-cyan-700 bg-cyan-50 border-cyan-200',
    '가차감': 'text-rose-600 bg-rose-50 border-rose-200',
    '확정차감': 'text-slate-100 bg-slate-800 border-slate-900',
    '환급': 'text-emerald-700 bg-emerald-50 border-emerald-200',
    '조정': 'text-purple-700 bg-purple-50 border-purple-200',
  };
  const style = styles[type] || 'text-slate-600 bg-slate-50 border-slate-200';
  
  return (
    <span className={`px-2 py-1 text-xs font-bold rounded-lg border ${style} whitespace-nowrap`}>
      {type}
    </span>
  );
};

export function AdvertiserBilling() {
  const [chargeAmount, setChargeAmount] = useState<number | ''>('');
  const [chargeMemo, setChargeMemo] = useState('');
  const [activeTab, setActiveTab] = useState('전체');
  const [history, setHistory] = useState<MerchantWalletTransaction[]>([]);
  const [balance, setBalance] = useState(0);
  const [summary, setSummary] = useState({ monthlyCharge: 0, monthlySpend: 0, availableBalance: 0 });
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const tabs = ['전체', '충전', '차감', '환급'];

  const loadWallet = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchMerchantWallet();
      setHistory(data.items);
      setBalance(data.balance);
      setSummary(data.summary);
    } catch (err) {
      setError(err instanceof Error ? err.message : '광고비 내역을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadWallet();
  }, []);

  const filteredHistory = useMemo(
    () => (activeTab === '전체' ? history : history.filter((item) => item.type === activeTab)),
    [activeTab, history],
  );

  const handleQuickAmount = (amount: number) => {
    setChargeAmount(amount);
  };

  const handleChargeSubmit = async () => {
    if (!chargeAmount || chargeAmount <= 0) {
      setError('충전 금액을 입력해주세요.');
      return;
    }

    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const result = await requestMerchantCharge({
        amount: Number(chargeAmount),
        memo: chargeMemo,
      });
      setMessage(result.message);
      setChargeAmount('');
      setChargeMemo('');
      await loadWallet();
    } catch (err) {
      setError(err instanceof Error ? err.message : '충전 신청에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  const statusLabel = (status: string) => {
    if (status === 'completed') return '처리완료';
    if (status === 'pending') return '승인대기';
    if (status === 'rejected') return '반려';
    return status;
  };

  return (
    <AdvertiserLayout activeMenu="billing" title="광고비 충전/내역">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          광고비 잔액과 충전, 차감, 환급 내역을 확인하세요.
        </p>
      </div>

      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="현재 광고비 잔액" value={balance.toLocaleString()} suffix="원" highlight />
        <SummaryCard title="이번 달 충전액" value={summary.monthlyCharge.toLocaleString()} suffix="원" />
        <SummaryCard title="이번 달 사용액" value={summary.monthlySpend.toLocaleString()} suffix="원" />
        <SummaryCard title="사용 가능 잔액" value={summary.availableBalance.toLocaleString()} suffix="원" dark />
        <SummaryCard title="거래 건수" value={history.length.toLocaleString()} suffix="건" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* Charge Request Form */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2.5 bg-cyan-100 text-cyan-600 rounded-xl">
              <CreditCard size={24} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">광고비 충전 신청</h2>
              <p className="text-sm text-slate-500">무통장 입금으로 광고비를 충전할 수 있습니다.</p>
            </div>
          </div>

          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">충전 신청 금액 *</label>
              <div className="relative">
                <input 
                  type="number" 
                  value={chargeAmount}
                  onChange={(e) => setChargeAmount(Number(e.target.value))}
                  placeholder="금액을 입력하세요" 
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-bold outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow text-right pr-12 text-lg" 
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">원</span>
              </div>
              <div className="grid grid-cols-3 md:grid-cols-6 gap-2 mt-3">
                {[100000, 300000, 500000, 1000000, 3000000, 5000000].map(amount => (
                  <button 
                    key={amount}
                    onClick={() => handleQuickAmount(amount)}
                    className="py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-medium rounded-lg transition-colors"
                  >
                    +{(amount/10000).toLocaleString()}만
                  </button>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">입금자명 *</label>
                <input 
                  type="text" 
                  placeholder="입금하시는 분 성함/법인명" 
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow" 
                />
              </div>
              <div className="flex flex-col justify-center pt-2">
                <label className="flex items-center gap-3 cursor-pointer">
                  <input type="checkbox" className="w-5 h-5 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <div>
                    <span className="block text-sm font-bold text-slate-900">세금계산서 발행 요청</span>
                    <span className="block text-xs text-slate-500">사업자등록증 정보로 발행됩니다.</span>
                  </div>
                </label>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">메모 (선택)</label>
              <input
                type="text"
                value={chargeMemo}
                onChange={(e) => setChargeMemo(e.target.value)}
                placeholder="전달하실 내용이 있다면 입력해주세요."
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
              />
            </div>

            <div className="pt-4 border-t border-slate-100">
              <button
                onClick={handleChargeSubmit}
                disabled={submitting}
                className="w-full py-4 bg-cyan-600 text-white hover:bg-cyan-700 rounded-xl font-bold text-lg transition-colors shadow-sm shadow-cyan-600/20 flex items-center justify-center gap-2 disabled:opacity-50"
              >
                {submitting ? '신청 중...' : '광고비 충전 신청하기'} <ArrowRight size={20} />
              </button>
            </div>
          </div>
        </div>

        {/* Current Status Card */}
        <div className="bg-slate-900 rounded-2xl shadow-lg p-6 md:p-8 text-white flex flex-col relative overflow-hidden">
          <div className="absolute top-0 right-0 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
          
          <div className="flex items-center gap-3 mb-8 relative z-10">
            <div className="p-2 bg-slate-800 rounded-xl">
              <Wallet size={24} className="text-cyan-400" />
            </div>
            <h2 className="text-lg font-bold text-white">이번 달 광고비 현황</h2>
          </div>

          <div className="space-y-6 flex-1 relative z-10">
            <div>
              <div className="text-slate-400 text-sm mb-1">이번 달 충전액</div>
              <div className="text-xl font-medium text-white">+{summary.monthlyCharge.toLocaleString()}원</div>
            </div>
            <div>
              <div className="text-slate-400 text-sm mb-1">이번 달 사용액</div>
              <div className="text-xl font-medium text-white">-{summary.monthlySpend.toLocaleString()}원</div>
            </div>
            <div>
              <div className="text-slate-400 text-sm mb-1 flex items-center gap-1">
                현재 잔액
              </div>
              <div className="text-xl font-medium text-cyan-400">{balance.toLocaleString()}원</div>
            </div>
          </div>

          <div className="pt-6 mt-6 border-t border-slate-800 relative z-10">
            <div className="text-slate-400 text-sm mb-2">남은 잔액 (사용 가능)</div>
            <div className="text-3xl font-bold text-white tracking-tight">{summary.availableBalance.toLocaleString()}<span className="text-xl font-medium ml-1">원</span></div>
          </div>

          <button onClick={() => window.scrollTo({top: 0, behavior: 'smooth'})} className="w-full mt-8 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2 border border-white/10 relative z-10">
            광고비 충전하기
          </button>
        </div>
      </div>

      {/* History Table Section */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-slate-100 rounded-lg text-slate-700">
              <History size={20} />
            </div>
            <h2 className="text-lg font-bold text-slate-900">광고비 내역</h2>
          </div>
          
          <div className="flex gap-2">
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
              <Filter size={16} /> 필터
            </button>
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
              <Download size={16} /> 엑셀 다운로드
            </button>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex overflow-x-auto border-b border-slate-100 hide-scrollbar px-6">
          {tabs.map(tab => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`px-5 py-3.5 text-sm font-bold whitespace-nowrap border-b-2 transition-colors ${
                activeTab === tab 
                  ? 'border-cyan-600 text-cyan-600' 
                  : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
              }`}
            >
              {tab}
            </button>
          ))}
        </div>

        {/* Desktop Table */}
        <div className="hidden lg:block overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-4 font-medium whitespace-nowrap">일시</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap text-center">구분</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">내역</th>
                <th className="px-6 py-4 font-medium text-right whitespace-nowrap">금액</th>
                <th className="px-6 py-4 font-medium text-right whitespace-nowrap">처리 후 잔액</th>
                <th className="px-6 py-4 font-medium text-center whitespace-nowrap">상태</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-slate-500">불러오는 중...</td>
                </tr>
              ) : filteredHistory.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <TypeBadge type={item.type} />
                  </td>
                  <td className="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">{item.memo || '-'}</td>
                  <td className={`px-6 py-4 text-right font-bold whitespace-nowrap ${item.amount > 0 ? 'text-emerald-600' : item.amount < 0 ? 'text-red-600' : 'text-slate-500'}`}>
                    {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                  </td>
                  <td className="px-6 py-4 text-right font-medium text-slate-900 whitespace-nowrap">
                    {item.balance.toLocaleString()}원
                  </td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={statusLabel(item.status)} />
                  </td>
                </tr>
              ))}
              {!loading && filteredHistory.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-slate-500">
                    내역이 없습니다.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Mobile List */}
        <div className="lg:hidden divide-y divide-slate-100">
          {loading ? (
            <div className="p-8 text-center text-slate-500 text-sm">불러오는 중...</div>
          ) : filteredHistory.map((item) => (
            <div key={item.id} className="p-5 flex flex-col gap-3">
              <div className="flex justify-between items-center">
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <span>{item.date}</span>
                </div>
                <TypeBadge type={item.type} />
              </div>

              <div>
                <h3 className="font-bold text-slate-900 mb-1">{item.memo || '거래 내역'}</h3>
              </div>

              <div className="bg-slate-50 p-3 rounded-xl flex justify-between items-center mt-1">
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500 mb-0.5">잔액</span>
                  <span className="text-sm font-medium text-slate-700">{item.balance.toLocaleString()}원</span>
                </div>
                <div className="flex flex-col items-end">
                  <span className="text-xs text-slate-500 mb-0.5">변동금액</span>
                  <span className={`font-bold ${item.amount > 0 ? 'text-emerald-600' : item.amount < 0 ? 'text-red-600' : 'text-slate-500'}`}>
                    {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                  </span>
                </div>
              </div>
              <div className="mt-1">
                <StatusBadge status={statusLabel(item.status)} />
              </div>
            </div>
          ))}
          {filteredHistory.length === 0 && (
            <div className="p-8 text-center text-slate-500 text-sm">
              내역이 없습니다.
            </div>
          )}
        </div>
      </div>

      {/* Info Box */}
      <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <div className="flex items-start gap-4">
          <AlertTriangle className="w-6 h-6 text-cyan-400 shrink-0 mt-0.5" />
          <div>
            <h3 className="text-lg font-bold mb-4">광고비 운영 안내</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>디비가 접수되면 광고비가 먼저 <strong>가차감</strong>됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>승인 완료 시 광고비가 <strong>확정 차감</strong> 처리됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                <span>취소/무효 처리된 디비는 가차감되었던 광고비가 다시 <strong>환급</strong>됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                <span>광고비 잔액이 부족해지면 진행 중인 캠페인이 <strong>일시중지</strong>될 수 있으니 잔액을 여유있게 유지해주세요.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </AdvertiserLayout>
  );
}
