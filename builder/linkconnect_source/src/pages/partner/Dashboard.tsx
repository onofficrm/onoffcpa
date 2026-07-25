import React, { Fragment, useEffect, useState } from "react";
import { 
  ChevronRight, 
  MousePointerClick, 
  Target, 
  XCircle 
} from 'lucide-react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Link } from 'react-router-dom';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { fetchPartnerDashboard, PartnerConversion, PartnerDashboardResponse } from '../../lib/api';

const fallbackChartData = [
  { date: '—', click: 0, db: 0, approval: 0 },
];

export function PartnerDashboard() {
  const [data, setData] = useState<PartnerDashboardResponse | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPartnerDashboard()
      .then(setData)
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, []);

  const summary = data?.summary;
  const chartData = data?.chart7d?.length ? data.chart7d : fallbackChartData;
  const channels = data?.channels ?? [];
  const recent = data?.recent ?? [];
  const balance = data?.balanceFormatted ?? '0';

  return (
    <PartnerLayout activeMenu="dashboard" title="파트너센터">
      {loading && <p className="text-slate-500 mb-4">대시보드를 불러오는 중...</p>}

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="오늘 클릭 수" value={String(summary?.todayClicks ?? 0)} icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="오늘 접수 DB" value={String(summary?.todayReceived ?? 0)} icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="승인완료 DB" value={String(summary?.approved ?? 0)} icon={<Target className="text-emerald-500" />} />
        <SummaryCard title="취소/무효 DB" value={String(summary?.rejected ?? 0)} icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="오늘 예상수입" value={(summary?.todayEstRevenue ?? 0).toLocaleString()} suffix="원" highlight />
        <SummaryCard title="확정수익" value={(summary?.confRevenue ?? 0).toLocaleString()} suffix="원" highlight dark />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6">최근 7일 성과 추이</h2>
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorClick" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.1}/>
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                  </linearGradient>
                  <linearGradient id="colorApproval" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.2}/>
                    <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                <Tooltip contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                <Area type="monotone" dataKey="click" name="클릭 수" stroke="#3b82f6" strokeWidth={2} fill="url(#colorClick)" />
                <Area type="monotone" dataKey="approval" name="승인 DB" stroke="#10b981" strokeWidth={2} fill="url(#colorApproval)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
          <h2 className="text-lg font-bold text-slate-900 mb-6">유입경로 분석</h2>
          <div className="flex-1 flex flex-col justify-center gap-6">
            <div className="space-y-4">
              {channels.length > 0 ? channels.map((item) => (
                <div key={item.channel}>
                  <InflowItem label={item.channel} percentage={item.percentage} color="bg-emerald-500" />
                </div>
              )) : (
                <p className="text-sm text-slate-500">아직 유입 데이터가 없습니다.</p>
              )}
            </div>
          </div>
          <Link to="/partner/report" className="mt-6 text-sm text-slate-500 hover:text-slate-900 flex items-center justify-center gap-1 w-full py-2 bg-slate-50 rounded-lg transition-colors">
            상세 리포트 보기 <ChevronRight size={16} />
          </Link>
        </div>
      </div>

      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm overflow-hidden flex flex-col">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-bold text-slate-900">최근 접수 디비</h2>
            <Link to="/partner/db-status" className="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">전체보기</Link>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3 font-medium">접수일</th>
                  <th className="px-4 py-3 font-medium">상품명</th>
                  <th className="px-4 py-3 font-medium">고객명</th>
                  <th className="px-4 py-3 font-medium">연락처</th>
                  <th className="px-4 py-3 font-medium text-center">상태</th>
                  <th className="px-4 py-3 font-medium text-right">예상수익</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {recent.length > 0 ? recent.map((row) => (
                  <Fragment key={row.id}>
                    <TableRow row={row} />
                  </Fragment>
                )) : (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-slate-500">접수된 디비가 없습니다.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div className="bg-slate-900 rounded-2xl p-6 shadow-lg text-white flex flex-col justify-between">
          <div>
            <h2 className="text-lg font-bold mb-6 flex items-center gap-2">
              <Target className="text-emerald-400" />
              정산 가능 금액
            </h2>
            <div className="space-y-4 mb-8">
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400">확정수익</span>
                <span className="font-semibold">{(summary?.confRevenue ?? 0).toLocaleString()} 원</span>
              </div>
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400">검수 대기</span>
                <span className="font-semibold text-yellow-400">{(summary?.pending ?? 0).toLocaleString()} 건</span>
              </div>
              <div className="flex justify-between items-center py-4">
                <span className="text-lg font-medium text-slate-300">정산 가능 잔액</span>
                <span className="text-3xl font-bold text-emerald-400">{balance} <span className="text-lg font-normal text-slate-400">원</span></span>
              </div>
            </div>
          </div>
          <Link to="/partner/settlement" className="w-full py-4 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl transition-colors text-center block">
            정산 신청하기
          </Link>
        </div>
      </div>
    </PartnerLayout>
  );
}

function InflowItem({ label, percentage, color }: { label: string, percentage: number, color: string }) {
  return (
    <div>
      <div className="flex justify-between text-sm mb-1.5">
        <span className="font-medium text-slate-700">{label}</span>
        <span className="text-slate-500">{percentage}%</span>
      </div>
      <div className="w-full bg-slate-100 rounded-full h-2">
        <div className={`${color} h-2 rounded-full`} style={{ width: `${percentage}%` }}></div>
      </div>
    </div>
  );
}

function TableRow({ row }: { row: PartnerConversion }) {
  const isStrike = row.statusCode === 'rejected';
  const revenue = row.estRevenue;

  return (
    <tr className="hover:bg-slate-50 transition-colors">
      <td className="px-4 py-3 text-slate-500">{row.date}</td>
      <td className={`px-4 py-3 font-medium ${isStrike ? 'text-slate-400 line-through' : 'text-slate-900'}`}>{row.campaign}</td>
      <td className="px-4 py-3 text-slate-600">{row.name}</td>
      <td className="px-4 py-3 text-slate-600 font-mono text-xs">{row.phone}</td>
      <td className="px-4 py-3 text-center">
        <StatusBadge status={row.status} />
      </td>
      <td className={`px-4 py-3 text-right font-medium ${revenue === 0 ? 'text-slate-400' : 'text-emerald-600'}`}>
        {revenue > 0 ? '+' : ''} {revenue.toLocaleString()}
      </td>
    </tr>
  );
}
