import { useEffect, useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { CreditCard, CheckCircle2, Clock, Building2, User, FileText, Calculator } from 'lucide-react';
import {
  fetchPartnerSettlements,
  PartnerSettlementItem,
  PartnerSettlementSummary,
  requestPartnerSettlement,
} from '../../lib/api';

const emptySummary: PartnerSettlementSummary = {
  balance: 0,
  pendingAmount: 0,
  availableAmount: 0,
  paidTotal: 0,
  monthConfirmed: 0,
  minAmount: 50000,
  bankName: '',
  bankAccount: '',
  bankHolder: '',
};

export function PartnerSettlement() {
  const [summary, setSummary] = useState<PartnerSettlementSummary>(emptySummary);
  const [history, setHistory] = useState<PartnerSettlementItem[]>([]);
  const [amount, setAmount] = useState<number | ''>('');
  const [memo, setMemo] = useState('');
  const [bankName, setBankName] = useState('');
  const [bankAccount, setBankAccount] = useState('');
  const [bankHolder, setBankHolder] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchPartnerSettlements();
      setSummary(data.summary);
      setHistory(data.items);
      setBankName(data.summary.bankName);
      setBankAccount(data.summary.bankAccount);
      setBankHolder(data.summary.bankHolder);
    } catch (err) {
      setError(err instanceof Error ? err.message : '정산 정보를 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const handleSubmit = async () => {
    if (!amount || Number(amount) < summary.minAmount) {
      setError(`최소 ${summary.minAmount.toLocaleString()}원 이상 신청 가능합니다.`);
      return;
    }

    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const result = await requestPartnerSettlement({
        amount: Number(amount),
        memo,
        bankName,
        bankAccount,
        bankHolder,
      });
      setMessage(result.message);
      setAmount('');
      setMemo('');
      setSummary(result.summary);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '정산 신청에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PartnerLayout activeMenu="settlement" title="정산 신청">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">확정수익을 기준으로 정산 가능 금액을 확인하고 정산을 신청하세요.</p>
      </div>

      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="이번 달 확정수익" value={summary.monthConfirmed.toLocaleString()} suffix="원" icon={<Calculator className="text-slate-500" />} />
        <SummaryCard title="정산 완료 금액" value={summary.paidTotal.toLocaleString()} suffix="원" icon={<CheckCircle2 className="text-blue-500" />} />
        <SummaryCard title="정산 대기 금액" value={summary.pendingAmount.toLocaleString()} suffix="원" icon={<Clock className="text-yellow-500" />} />
        <SummaryCard title="정산 가능 금액" value={summary.availableAmount.toLocaleString()} suffix="원" icon={<CreditCard className="text-emerald-600" />} highlight />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-10">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
          <h2 className="text-lg font-bold text-slate-900 mb-6">정산 신청 정보</h2>
          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">정산 신청 금액 <span className="text-red-500">*</span></label>
              <div className="relative">
                <input
                  type="number"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value ? Number(e.target.value) : '')}
                  className="w-full pl-4 pr-12 py-3 bg-white border border-slate-200 rounded-xl text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">원</span>
              </div>
              <div className="mt-2 flex items-center justify-between">
                <p className="text-xs text-slate-500">최소 정산 가능 금액: {summary.minAmount.toLocaleString()}원</p>
                <button type="button" onClick={() => setAmount(summary.availableAmount)} className="text-xs font-medium text-emerald-600 hover:text-emerald-700">전액 입력</button>
              </div>
            </div>

            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">은행명 <span className="text-red-500">*</span></label>
                <div className="relative">
                  <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                  <input value={bankName} onChange={(e) => setBankName(e.target.value)} className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">예금주 <span className="text-red-500">*</span></label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                  <input value={bankHolder} onChange={(e) => setBankHolder(e.target.value)} className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">계좌번호 <span className="text-red-500">*</span></label>
              <div className="relative">
                <CreditCard className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input value={bankAccount} onChange={(e) => setBankAccount(e.target.value)} className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">메모 <span className="text-slate-400 font-normal">(선택)</span></label>
              <div className="relative">
                <FileText className="absolute left-3 top-4 w-5 h-5 text-slate-400" />
                <textarea rows={3} value={memo} onChange={(e) => setMemo(e.target.value)} className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none" placeholder="관리자에게 남길 메모가 있다면 입력해주세요." />
              </div>
            </div>

            <button onClick={handleSubmit} disabled={submitting || loading} className="w-full py-4 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm text-lg disabled:opacity-50">
              {submitting ? '신청 중...' : '정산 신청하기'}
            </button>
          </div>
        </div>

        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg sticky top-6">
            <h2 className="text-lg font-bold mb-8 flex items-center gap-2"><CreditCard className="text-emerald-400" />내 정산 요약</h2>
            <div className="space-y-5">
              <div className="flex justify-between items-center py-3 border-b border-white/10"><span className="text-slate-400">현재 잔액</span><span className="font-bold">{summary.balance.toLocaleString()}원</span></div>
              <div className="flex justify-between items-center py-3 border-b border-white/10"><span className="text-slate-400">정산 대기</span><span className="font-bold text-yellow-300">{summary.pendingAmount.toLocaleString()}원</span></div>
              <div className="flex justify-between items-center py-3"><span className="text-emerald-300 font-medium">정산 가능</span><span className="text-2xl font-bold text-emerald-400">{summary.availableAmount.toLocaleString()}원</span></div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-slate-100"><h2 className="text-lg font-bold text-slate-900">정산 신청 내역</h2></div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4">신청일 / 코드</th>
                <th className="px-5 py-4 text-right">신청금액</th>
                <th className="px-5 py-4 text-right">승인금액</th>
                <th className="px-5 py-4 text-center">상태</th>
                <th className="px-5 py-4">지급일</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr><td colSpan={5} className="px-5 py-10 text-center text-slate-500">불러오는 중...</td></tr>
              ) : history.length === 0 ? (
                <tr><td colSpan={5} className="px-5 py-10 text-center text-slate-500">정산 내역이 없습니다.</td></tr>
              ) : history.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50">
                  <td className="px-5 py-4"><div className="font-medium">{item.date}</div><div className="text-xs text-slate-400">{item.code}</div></td>
                  <td className="px-5 py-4 text-right font-bold">{item.reqAmount.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-right">{item.appAmount.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-center"><StatusBadge status={item.status} /></td>
                  <td className="px-5 py-4 text-slate-500">{item.payDate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </PartnerLayout>
  );
}
