import { useEffect, useState } from 'react';
import { MousePointerClick, Target, CheckCircle2, XCircle, Percent, Lightbulb, TrendingUp } from 'lucide-react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { fetchPartnerAnalytics, PartnerAnalyticsResponse } from '../../lib/api';

const emptyData: PartnerAnalyticsResponse = {
  summary: { totalClicks: 0, totalDb: 0, approvedDb: 0, rejectedDb: 0, avgConvRate: 0, avgApprovalRate: 0 },
  chart7d: [],
  channels: [],
  campaigns: [],
  dbReady: false,
};

export function PartnerAnalytics() {
  const [data, setData] = useState<PartnerAnalyticsResponse>(emptyData);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchPartnerAnalytics()
      .then(setData)
      .catch((err) => setError(err instanceof Error ? err.message : '분석 데이터를 불러오지 못했습니다.'))
      .finally(() => setLoading(false));
  }, []);

  return (
    <PartnerLayout activeMenu="analytics" title="유입 분석">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">유입경로, 채널, sub_id별 성과를 확인하고 효율 좋은 채널을 분석하세요.</p>
      </div>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="총 클릭 수" value={data.summary.totalClicks.toLocaleString()} suffix="회" icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="총 접수 DB" value={data.summary.totalDb.toLocaleString()} suffix="건" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="총 승인 DB" value={data.summary.approvedDb.toLocaleString()} suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title="총 취소 DB" value={data.summary.rejectedDb.toLocaleString()} suffix="건" icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="평균 전환율" value={String(data.summary.avgConvRate)} suffix="%" icon={<Percent className="text-purple-500" />} />
        <SummaryCard title="평균 승인율" value={String(data.summary.avgApprovalRate)} suffix="%" icon={<Percent className="text-emerald-500" />} highlight />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
          <h2 className="text-lg font-bold text-slate-900 mb-6">최근 7일 성과 추이</h2>
          <div className="h-72 w-full">
            {loading ? (
              <div className="h-full flex items-center justify-center text-slate-500">불러오는 중...</div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={data.chart7d} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="colorClick" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#3b82f6" stopOpacity={0.1}/><stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/></linearGradient>
                    <linearGradient id="colorDb" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#06b6d4" stopOpacity={0.2}/><stop offset="95%" stopColor="#06b6d4" stopOpacity={0}/></linearGradient>
                    <linearGradient id="colorApproval" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#10b981" stopOpacity={0.2}/><stop offset="95%" stopColor="#10b981" stopOpacity={0}/></linearGradient>
                  </defs>
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <Tooltip />
                  <Area type="monotone" dataKey="click" stroke="#3b82f6" fill="url(#colorClick)" strokeWidth={2} name="클릭" />
                  <Area type="monotone" dataKey="db" stroke="#06b6d4" fill="url(#colorDb)" strokeWidth={2} name="DB" />
                  <Area type="monotone" dataKey="approval" stroke="#10b981" fill="url(#colorApproval)" strokeWidth={2} name="승인" />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"><Lightbulb size={20} className="text-yellow-500" />채널 TOP</h2>
          <div className="space-y-4">
            {data.channels.length === 0 ? (
              <p className="text-sm text-slate-500">채널 데이터가 없습니다.</p>
            ) : data.channels.map((item) => (
              <div key={item.channel}>
                <div className="flex justify-between text-sm mb-1"><span className="font-medium text-slate-700">{item.channel}</span><span className="text-slate-500">{item.percentage}%</span></div>
                <div className="h-2 bg-slate-100 rounded-full overflow-hidden"><div className="h-full bg-emerald-500 rounded-full" style={{ width: `${item.percentage}%` }} /></div>
                <div className="text-xs text-slate-400 mt-1">클릭 {item.clicks} · DB {item.dbs} · 승인 {item.approved}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-slate-100 flex items-center gap-2"><TrendingUp size={20} className="text-emerald-500" /><h2 className="text-lg font-bold text-slate-900">캠페인별 성과</h2></div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4">캠페인</th>
                <th className="px-5 py-4 text-right">클릭</th>
                <th className="px-5 py-4 text-right">접수</th>
                <th className="px-5 py-4 text-right">승인</th>
                <th className="px-5 py-4 text-right">승인율</th>
                <th className="px-5 py-4 text-right">확정수익</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.campaigns.map((item) => (
                <tr key={item.campaign} className="hover:bg-slate-50">
                  <td className="px-5 py-4 font-medium text-slate-900">{item.campaign}</td>
                  <td className="px-5 py-4 text-right">{item.clicks.toLocaleString()}</td>
                  <td className="px-5 py-4 text-right">{item.received.toLocaleString()}</td>
                  <td className="px-5 py-4 text-right">{item.approved.toLocaleString()}</td>
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
