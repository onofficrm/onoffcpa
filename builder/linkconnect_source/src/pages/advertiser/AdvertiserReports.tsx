import { useEffect, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer,
  ComposedChart, Line, Area,
} from 'recharts';
import { TrendingUp, TrendingDown, Filter, Lightbulb } from 'lucide-react';
import { fetchMerchantReports, fetchMerchantAiSummary, MerchantReportResponse } from '../../lib/api';
import { AiReportInsight } from '../../components/AiReportInsight';

const emptyReport: MerchantReportResponse = {
  summary: { total: 0, approved: 0, rejected: 0, avgRate: 0, totalSpend: 0, avgPrice: 0 },
  dbChart7d: [],
  spendChart7d: [],
  campaigns: [],
  partners: [],
  dbReady: false,
};

export function AdvertiserReports() {
  const [report, setReport] = useState<MerchantReportResponse>(emptyReport);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchMerchantReports();
      setReport(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : '리포트를 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const dbTrendData = report.dbChart7d.map((row) => ({
    date: row.date,
    접수: row.received,
    승인: row.approved,
    취소: row.rejected,
  }));

  const spendTrendData = report.spendChart7d.map((row) => ({
    date: row.date,
    가차감: row.holdSpend,
    확정차감: row.confSpend,
    환급: row.refund,
  }));

  const topCampaign = report.campaigns[0];
  const riskyPartner = report.partners.find((p) => p.note !== '-');

  return (
    <AdvertiserLayout activeMenu="reports" title="성과 리포트">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">광고상품별 디비 성과와 광고비 효율을 분석하세요.</p>
      </div>

      {error && (
        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
      )}

      <AiReportInsight title="AI 성과 브리핑" fetchSummary={fetchMerchantAiSummary} />

      <div className="flex flex-col lg:flex-col gap-6 lg:gap-8 mb-8">
        <div className="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center shadow-sm">
          <button type="button" onClick={load} className="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm ml-auto">
            <Filter size={16} /> {loading ? '조회 중...' : '새로고침'}
          </button>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <SummaryCard title="총 접수 DB" value={report.summary.total.toLocaleString()} suffix="건" />
          <SummaryCard title="승인 DB" value={report.summary.approved.toLocaleString()} suffix="건" color="emerald" highlight />
          <SummaryCard title="취소/무효 DB" value={report.summary.rejected.toLocaleString()} suffix="건" color="red" highlight />
          <SummaryCard title="평균 승인율" value={String(report.summary.avgRate)} suffix="%" color="cyan" highlight />
          <SummaryCard title="총 사용 광고비" value={report.summary.totalSpend.toLocaleString()} suffix="원" dark />
          <SummaryCard title="평균 DB 단가" value={report.summary.avgPrice.toLocaleString()} suffix="원" />
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <h3 className="text-lg font-bold text-slate-900 mb-6">기간별 디비 처리 현황 (7일)</h3>
            <div className="flex-1 min-h-[300px]">
              {dbTrendData.length === 0 ? (
                <div className="h-full flex items-center justify-center text-slate-500 text-sm">데이터 없음</div>
              ) : (
                <ResponsiveContainer width="100%" height="100%">
                  <ComposedChart data={dbTrendData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                    <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                    <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                    <Legend wrapperStyle={{ paddingTop: '20px', fontSize: '12px' }} />
                    <Bar dataKey="접수" fill="#94a3b8" radius={[4, 4, 0, 0]} barSize={20} />
                    <Bar dataKey="승인" fill="#0891b2" radius={[4, 4, 0, 0]} barSize={20} />
                    <Line type="monotone" dataKey="취소" stroke="#ef4444" strokeWidth={2} dot={{ r: 4 }} />
                  </ComposedChart>
                </ResponsiveContainer>
              )}
            </div>
          </div>

          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <h3 className="text-lg font-bold text-slate-900 mb-6">광고비 사용 추이 (7일)</h3>
            <div className="flex-1 min-h-[300px]">
              {spendTrendData.length === 0 ? (
                <div className="h-full flex items-center justify-center text-slate-500 text-sm">데이터 없음</div>
              ) : (
                <ResponsiveContainer width="100%" height="100%">
                  <ComposedChart data={spendTrendData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                    <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(val) => `${val / 10000}만`} />
                    <RechartsTooltip formatter={(val: number) => `${val.toLocaleString()}원`} contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                    <Legend wrapperStyle={{ paddingTop: '20px', fontSize: '12px' }} />
                    <Area type="monotone" dataKey="가차감" stroke="#3b82f6" fill="#3b82f6" fillOpacity={0.1} />
                    <Area type="monotone" dataKey="확정차감" stroke="#0f172a" fill="#0f172a" fillOpacity={0.1} />
                    <Line type="monotone" dataKey="환급" stroke="#10b981" strokeWidth={2} dot={{ r: 4 }} />
                  </ComposedChart>
                </ResponsiveContainer>
              )}
            </div>
          </div>
        </div>

        <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
          <div className="relative z-10 flex flex-col md:flex-row items-start gap-6">
            <div className="p-4 bg-slate-800 rounded-2xl shrink-0">
              <Lightbulb className="w-8 h-8 text-cyan-400" />
            </div>
            <div>
              <h3 className="text-xl font-bold mb-5">성과 인사이트</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4 text-sm text-slate-300">
                {topCampaign ? (
                  <p className="flex items-start gap-3">
                    <TrendingUp className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                    <span><strong className="text-white">{topCampaign.name}</strong> 상품의 승인율이 {topCampaign.approvalRate}%입니다.</span>
                  </p>
                ) : null}
                {riskyPartner ? (
                  <p className="flex items-start gap-3">
                    <TrendingDown className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                    <span><strong className="text-white">{riskyPartner.code}</strong> 파트너의 취소율이 평균 대비 높습니다.</span>
                  </p>
                ) : null}
                <p className="flex items-start gap-3">
                  <TrendingUp className="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" />
                  <span>총 사용 광고비 <strong className="text-white">{report.summary.totalSpend.toLocaleString()}원</strong> 기준으로 분석되었습니다.</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-8">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 bg-slate-50">
              <h3 className="font-bold text-slate-900">캠페인별 성과</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-4 font-medium">캠페인</th>
                    <th className="px-6 py-4 font-medium text-center">접수</th>
                    <th className="px-6 py-4 font-medium text-center">승인</th>
                    <th className="px-6 py-4 font-medium text-center">취소</th>
                    <th className="px-6 py-4 font-medium text-center">승인율</th>
                    <th className="px-6 py-4 font-medium text-right">사용 광고비</th>
                    <th className="px-6 py-4 font-medium text-center">상태</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {report.campaigns.length === 0 ? (
                    <tr><td colSpan={7} className="px-6 py-8 text-center text-slate-500">캠페인 데이터가 없습니다.</td></tr>
                  ) : report.campaigns.map((row) => (
                    <tr key={row.id} className="hover:bg-slate-50">
                      <td className="px-6 py-4 font-bold text-slate-900">{row.name}</td>
                      <td className="px-6 py-4 text-center">{row.total}</td>
                      <td className="px-6 py-4 text-center text-cyan-600 font-bold">{row.approved}</td>
                      <td className="px-6 py-4 text-center text-red-500">{row.canceled}</td>
                      <td className="px-6 py-4 text-center">{row.approvalRate}%</td>
                      <td className="px-6 py-4 text-right font-medium">{row.spend.toLocaleString()}원</td>
                      <td className="px-6 py-4 text-center"><StatusBadge status={row.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 bg-slate-50">
              <h3 className="font-bold text-slate-900">파트너별 성과 TOP</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-4 font-medium">파트너</th>
                    <th className="px-6 py-4 font-medium text-center">접수</th>
                    <th className="px-6 py-4 font-medium text-center">승인</th>
                    <th className="px-6 py-4 font-medium text-center">승인율</th>
                    <th className="px-6 py-4 font-medium text-right">사용 광고비</th>
                    <th className="px-6 py-4 font-medium">비고</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {report.partners.length === 0 ? (
                    <tr><td colSpan={6} className="px-6 py-8 text-center text-slate-500">파트너 데이터가 없습니다.</td></tr>
                  ) : report.partners.map((row) => (
                    <tr key={row.code} className="hover:bg-slate-50">
                      <td className="px-6 py-4 font-bold text-slate-900">{row.code}</td>
                      <td className="px-6 py-4 text-center">{row.total}</td>
                      <td className="px-6 py-4 text-center">{row.approved}</td>
                      <td className="px-6 py-4 text-center">{row.approvalRate}%</td>
                      <td className="px-6 py-4 text-right">{row.spend.toLocaleString()}원</td>
                      <td className="px-6 py-4 text-slate-500">{row.note}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AdvertiserLayout>
  );
}
