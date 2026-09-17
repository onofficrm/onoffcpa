import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { Wallet, CreditCard, ArrowRight, History, Download, Filter, Calendar } from 'lucide-react';
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

type PeriodPreset = 'this_month' | 'last_month' | '7d' | '30d' | 'custom';

const PERIOD_PRESETS: Array<{ id: PeriodPreset; label: string }> = [
  { id: 'this_month', label: '이번 달' },
  { id: 'last_month', label: '지난 달' },
  { id: '7d', label: '최근 7일' },
  { id: '30d', label: '최근 30일' },
  { id: 'custom', label: '직접 선택' },
];

function pad2(n: number) {
  return String(n).padStart(2, '0');
}

function formatDate(d: Date) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

function rangeForPreset(preset: PeriodPreset): { from: string; to: string } {
  const today = new Date();
  const to = formatDate(today);

  if (preset === 'this_month') {
    return { from: `${today.getFullYear()}-${pad2(today.getMonth() + 1)}-01`, to };
  }
  if (preset === 'last_month') {
    const firstThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastPrev = new Date(firstThisMonth.getTime() - 86400000);
    const firstPrev = new Date(lastPrev.getFullYear(), lastPrev.getMonth(), 1);
    return { from: formatDate(firstPrev), to: formatDate(lastPrev) };
  }
  if (preset === '7d') {
    const from = new Date(today);
    from.setDate(from.getDate() - 6);
    return { from: formatDate(from), to };
  }
  if (preset === '30d') {
    const from = new Date(today);
    from.setDate(from.getDate() - 29);
    return { from: formatDate(from), to };
  }

  const monthStart = `${today.getFullYear()}-${pad2(today.getMonth() + 1)}-01`;
  return { from: monthStart, to };
}

export function AdvertiserBilling() {
  const initialRange = rangeForPreset('this_month');
  const [chargeAmount, setChargeAmount] = useState<number | ''>('');
  const [chargeMemo, setChargeMemo] = useState('');
  const [activeTab, setActiveTab] = useState('전체');
  const [history, setHistory] = useState<MerchantWalletTransaction[]>([]);
  const [balance, setBalance] = useState(0);
  const [summary, setSummary] = useState({ monthlyCharge: 0, monthlySpend: 0, monthlyAdminDeduct: 0, availableBalance: 0 });
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [periodPreset, setPeriodPreset] = useState<PeriodPreset>('this_month');
  const [dateFrom, setDateFrom] = useState(initialRange.from);
  const [dateTo, setDateTo] = useState(initialRange.to);

  const tabs = ['전체', '충전', '차감', '환급'];

  const loadWallet = useCallback(async (from: string, to: string) => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchMerchantWallet({ dateFrom: from, dateTo: to });
      setHistory(data.items);
      setBalance(data.balance);
      setSummary(data.summary);
      if (data.summary.dateFrom) setDateFrom(data.summary.dateFrom);
      if (data.summary.dateTo) setDateTo(data.summary.dateTo);
    } catch (err) {
      setError(err instanceof Error ? err.message : '광고비 내역을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadWallet(dateFrom, dateTo);
    // 초기 로드만 — 이후는 프리셋/조회 버튼에서 호출
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const filteredHistory = useMemo(
    () => (activeTab === '전체' ? history : history.filter((item) => item.type === activeTab)),
    [activeTab, history],
  );

  const periodLabel = useMemo(() => {
    if (periodPreset === 'this_month') return '이번 달';
    if (periodPreset === 'last_month') return '지난 달';
    if (periodPreset === '7d') return '최근 7일';
    if (periodPreset === '30d') return '최근 30일';
    return '선택 기간';
  }, [periodPreset]);

  const rangeLabel = `${dateFrom} ~ ${dateTo}`;

  const applyPreset = (preset: PeriodPreset) => {
    setPeriodPreset(preset);
    if (preset === 'custom') return;
    const range = rangeForPreset(preset);
    setDateFrom(range.from);
    setDateTo(range.to);
    void loadWallet(range.from, range.to);
  };

  const applyCustomRange = () => {
    if (!dateFrom || !dateTo) {
      setError('조회 기간을 선택해주세요.');
      return;
    }
    let from = dateFrom;
    let to = dateTo;
    if (from > to) {
      const tmp = from;
      from = to;
      to = tmp;
      setDateFrom(from);
      setDateTo(to);
    }
    setPeriodPreset('custom');
    void loadWallet(from, to);
  };

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
      await loadWallet(dateFrom, dateTo);
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

  const adminDeduct = summary.monthlyAdminDeduct ?? 0;
  // 순증감: 충전 − DB사용 − 관리자수동차감 (잔액 변동과 맞춤)
  const periodNet = summary.monthlyCharge - summary.monthlySpend - adminDeduct;

  return (
    <AdvertiserLayout activeMenu="billing" title="광고비 충전/내역">
      <div className="flex flex-col mb-6 -mt-2">
        <p className="text-slate-500">
          광고비 잔액과 충전, 차감, 환급 내역을 확인하세요. 사용액은 DB 승인 차감만 포함합니다.
        </p>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm mb-6">
        <div className="flex items-center gap-2 mb-4 text-slate-700 font-medium">
          <Filter size={18} className="text-cyan-500" />
          기간 필터
          <span className="text-xs font-normal text-slate-400 ml-2 flex items-center gap-1">
            <Calendar size={12} /> {rangeLabel}
          </span>
        </div>
        <div className="flex flex-col lg:flex-row lg:items-center gap-3 flex-wrap">
          <div className="flex rounded-xl border border-slate-200 overflow-hidden flex-wrap">
            {PERIOD_PRESETS.map((option) => (
              <button
                key={option.id}
                type="button"
                onClick={() => applyPreset(option.id)}
                className={`px-4 py-2 text-sm font-medium transition-colors ${
                  periodPreset === option.id ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'
                }`}
              >
                {option.label}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => {
                setPeriodPreset('custom');
                setDateFrom(e.target.value);
              }}
              className="px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white"
            />
            <span className="text-slate-400 text-sm">~</span>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => {
                setPeriodPreset('custom');
                setDateTo(e.target.value);
              }}
              className="px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white"
            />
            <button
              type="button"
              onClick={applyCustomRange}
              className="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-sm font-bold transition-colors"
            >
              조회
            </button>
          </div>
        </div>
      </div>

      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className={`grid grid-cols-2 md:grid-cols-3 ${adminDeduct > 0 ? 'lg:grid-cols-6' : 'lg:grid-cols-5'} gap-4 mb-8`}>
        <SummaryCard title="현재 광고비 잔액" value={balance.toLocaleString()} suffix="원" highlight />
        <SummaryCard title={`${periodLabel} 충전액`} value={summary.monthlyCharge.toLocaleString()} suffix="원" />
        <SummaryCard title={`${periodLabel} 사용액`} value={summary.monthlySpend.toLocaleString()} suffix="원" caption="DB 승인 차감" />
        {adminDeduct > 0 ? (
          <SummaryCard title="관리자 수동 차감" value={adminDeduct.toLocaleString()} suffix="원" color="purple" caption="사용액과 별도" />
        ) : null}
        <SummaryCard title={`${periodLabel} 순증감`} value={periodNet.toLocaleString()} suffix="원" dark />
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
            <div>
              <h2 className="text-lg font-bold text-white">{periodLabel} 광고비 현황</h2>
              <p className="text-xs text-slate-400 mt-0.5">{rangeLabel}</p>
            </div>
          </div>

          <div className="space-y-6 flex-1 relative z-10">
            <div>
              <div className="text-slate-400 text-sm mb-1">{periodLabel} 충전액</div>
              <div className="text-xl font-medium text-white">+{summary.monthlyCharge.toLocaleString()}원</div>
            </div>
            <div>
              <div className="text-slate-400 text-sm mb-1">{periodLabel} 사용액 (DB)</div>
              <div className="text-xl font-medium text-rose-400">-{summary.monthlySpend.toLocaleString()}원</div>
            </div>
            {adminDeduct > 0 ? (
              <div>
                <div className="text-slate-400 text-sm mb-1">관리자 수동 차감</div>
                <div className="text-xl font-medium text-violet-300">-{adminDeduct.toLocaleString()}원</div>
              </div>
            ) : null}
            <div>
              <div className="text-slate-400 text-sm mb-1">
                {periodLabel} 순증감 {adminDeduct > 0 ? '(충전 − 사용 − 수동차감)' : '(충전 − 사용)'}
              </div>
              <div className={`text-xl font-medium ${periodNet >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                {periodNet >= 0 ? '+' : ''}{periodNet.toLocaleString()}원
              </div>
            </div>
          </div>

          <div className="pt-6 mt-6 border-t border-slate-800 relative z-10">
            <div className="text-slate-400 text-sm mb-2">현재 잔액 (사용 가능)</div>
            <div className="text-3xl font-bold text-white tracking-tight">{balance.toLocaleString()}<span className="text-xl font-medium ml-1">원</span></div>
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
            <div>
              <h2 className="text-lg font-bold text-slate-900">광고비 내역</h2>
              <p className="text-xs text-slate-400 mt-0.5">{rangeLabel}</p>
            </div>
          </div>
          
          <div className="flex gap-2">
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
                    선택한 기간에 내역이 없습니다.
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
                  <span className="font-medium text-slate-900">{item.balance.toLocaleString()}원</span>
                </div>
                <div className="flex flex-col items-end">
                  <span className="text-xs text-slate-500 mb-0.5">금액</span>
                  <span className={`font-bold ${item.amount > 0 ? 'text-emerald-600' : item.amount < 0 ? 'text-red-600' : 'text-slate-500'}`}>
                    {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                  </span>
                </div>
              </div>
              <div className="flex justify-end">
                <StatusBadge status={statusLabel(item.status)} />
              </div>
            </div>
          ))}
          {!loading && filteredHistory.length === 0 && (
            <div className="p-8 text-center text-slate-500 text-sm">선택한 기간에 내역이 없습니다.</div>
          )}
        </div>
      </div>
    </AdvertiserLayout>
  );
}
