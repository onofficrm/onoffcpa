import { BarChart3, CheckCircle2, Clock, TrendingUp, Wallet } from 'lucide-react';
import { useEffect, useState } from 'react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { fetchPartnerReport, PartnerReportResponse } from '../../lib/api';

const emptyData: PartnerReportResponse = {
  summary: { estRevenue: 0, confRevenue: 0, availableAmount: 0, rejectedAmount: 0 },
  breakdown: [],
  monthly: [],
  campaigns: [],
  dbReady: false,
};

export function PartnerReport() {
  const [data, setData] = useState<PartnerReportResponse>(emptyData);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchPartnerReport()
      .then(setData)
      .catch((err) => setError(err instanceof Error ? err.message : '리포트를 불러오지 못했습니다.'))
      .finally(() => setLoading(false));
  }, []);

  const growth = data.monthly.length >= 2
    ? ((data.monthly[data.monthly.length - 1].value - data.monthly[data.monthly.length - 2].value) / Math.max(data.monthly[data.monthly.length - 2].value, 1)) * 100
    : 0;

  return (
    <PartnerLayout activeMenu="report" title="수익 리포트">
      <p className="text-slate-500 mb-8 -mt-2">예상·확정 수익과 정산 가능 금액을 확인하세요.</p>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="예상 수익" value={data.summary.estRevenue.toLocaleString()} suffix="원" icon={<Clock className="text-amber-500" />} />
        <SummaryCard title="확정 수익" value={data.summary.confRevenue.toLocaleString()} suffix="원" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title="정산 가능" value={data.summary.availableAmount.toLocaleString()} suffix="원" icon={<Wallet className="text-cyan-500" />} highlight />
        <SummaryCard title="이번 달 성장" value={growth.toFixed(1)} suffix="%" icon={<TrendingUp className="text-purple-500" />} />
      </div>

      <div className="grid lg:grid-cols-2 gap-8 mb-8">
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"><BarChart3 size={20} className="text-emerald-500" />월별 수익 추이</h2>
          {loading ? (
            <div className="h-40 flex items-center justify-center text-slate-500">불러오는 중...</div>
          ) : (
            <div className="flex items-end gap-3 h-40 mb-4">
              {data.monthly.map((item) => (
                <div key={item.month} className="flex-1 flex flex-col items-center gap-2">
                  <div className="w-full rounded-t-lg bg-emerald-500/80" style={{ height: `${Math.max(item.pct, 4)}%` }} />
                  <span className="text-xs text-slate-500">{item.month}</span>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6">수익 구성</h2>
          <div className="space-y-4">
            {data.breakdown.map((item) => (
              <div key={item.label}>
                <div className="flex justify-between text-sm mb-1"><span className="text-slate-600">{item.label}</span><span className="font-bold text-slate-900">{item.value.toLocaleString()}원</span></div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-slate-100"><h2 className="text-lg font-bold text-slate-900">캠페인 TOP 5</h2></div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4">캠페인</th>
                <th className="px-5 py-4 text-right">접수 DB</th>
                <th className="px-5 py-4 text-right">승인율</th>
                <th className="px-5 py-4 text-right">확정수익</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.campaigns.map((item) => (
                <tr key={item.campaign} className="hover:bg-slate-50">
                  <td className="px-5 py-4 font-medium">{item.campaign}</td>
                  <td className="px-5 py-4 text-right">{item.received}건</td>
                  <td className="px-5 py-4 text-right">{item.appRate}</td>
                  <td className="px-5 py-4 text-right font-bold text-emerald-600">{item.confRev.toLocaleString()}원</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </PartnerLayout>
  );
}
