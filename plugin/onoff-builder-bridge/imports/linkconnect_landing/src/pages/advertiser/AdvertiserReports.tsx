import React, { useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer, 
  ComposedChart, Line, Area
} from 'recharts';
import { TrendingUp, TrendingDown, Filter, Download, Lightbulb, Calendar, Search, ArrowRight, BarChart2, Users } from 'lucide-react';

const dbTrendData = [
  { date: '10.01', 접수: 45, 승인: 32, 취소: 8 },
  { date: '10.02', 접수: 52, 승인: 38, 취소: 10 },
  { date: '10.03', 접수: 48, 승인: 35, 취소: 9 },
  { date: '10.04', 접수: 61, 승인: 45, 취소: 12 },
  { date: '10.05', 접수: 59, 승인: 41, 취소: 11 },
  { date: '10.06', 접수: 75, 승인: 55, 취소: 15 },
  { date: '10.07', 접수: 88, 승인: 66, 취소: 11 },
];

const spendTrendData = [
  { date: '10.01', 가차감: 2250000, 확정차감: 1600000, 환급: 400000 },
  { date: '10.02', 가차감: 2600000, 확정차감: 1900000, 환급: 500000 },
  { date: '10.03', 가차감: 2400000, 확정차감: 1750000, 환급: 450000 },
  { date: '10.04', 가차감: 3050000, 확정차감: 2250000, 환급: 600000 },
  { date: '10.05', 가차감: 2950000, 확정차감: 2050000, 환급: 550000 },
  { date: '10.06', 가차감: 3750000, 확정차감: 2750000, 환급: 750000 },
  { date: '10.07', 가차감: 4400000, 확정차감: 3300000, 환급: 550000 },
];

const campaignPerformance = [
  { id: 1, name: '개인회생 상담 DB', total: 215, approved: 165, canceled: 32, approvalRate: 76.7, cancelRate: 14.9, spend: 8250000, avgPrice: 50000, status: '진행중' },
  { id: 2, name: '어린이 영어캠프', total: 132, approved: 95, canceled: 28, approvalRate: 72.0, cancelRate: 21.2, spend: 3325000, avgPrice: 35000, status: '진행중' },
  { id: 3, name: '소상공인 대출 상담', total: 54, approved: 35, canceled: 12, approvalRate: 64.8, cancelRate: 22.2, spend: 875000, avgPrice: 25000, status: '일시중지' },
  { id: 4, name: '자동차 렌트 상담', total: 27, approved: 17, canceled: 4, approvalRate: 63.0, cancelRate: 14.8, spend: 765000, avgPrice: 45000, status: '진행중' },
];

const partnerPerformance = [
  { id: 'PTN-8291', total: 145, approved: 112, canceled: 18, approvalRate: 77.2, spend: 5450000, note: '우수 파트너' },
  { id: 'PTN-1022', total: 88, approved: 65, canceled: 15, approvalRate: 73.9, spend: 2850000, note: '-' },
  { id: 'PTN-5044', total: 64, approved: 41, canceled: 18, approvalRate: 64.1, spend: 1450000, note: '취소율 모니터링' },
  { id: 'PTN-3011', total: 52, approved: 38, canceled: 8, approvalRate: 73.1, spend: 1750000, note: '-' },
  { id: 'PTN-9982', total: 45, approved: 28, canceled: 14, approvalRate: 62.2, spend: 1150000, note: '-' },
];

export function AdvertiserReports() {
  return (
    <AdvertiserLayout activeMenu="reports" title="성과 리포트">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          광고상품별 디비 성과와 광고비 효율을 분석하세요.
        </p>
      </div>

      <div className="flex flex-col lg:flex-col gap-6 lg:gap-8 mb-8">
        {/* 1. Top Filters */}
        <div className="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center shadow-sm order-none">
          <div className="flex items-center gap-2">
            <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500" defaultValue="2026-10-01" />
            <span className="text-slate-400">~</span>
            <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500" defaultValue="2026-10-07" />
          </div>
          <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[140px] flex-1 md:flex-none">
            <option>전체 캠페인</option>
            <option>개인회생 상담 DB</option>
            <option>어린이 영어캠프</option>
          </select>
          <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[140px] flex-1 md:flex-none">
            <option>전체 파트너</option>
            <option>PTN-8291</option>
            <option>PTN-1022</option>
          </select>
          <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[120px] flex-1 md:flex-none">
            <option>상태 전체</option>
            <option>진행중</option>
            <option>일시중지</option>
            <option>종료</option>
          </select>
          <button className="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm ml-auto md:w-auto w-full">
            <Filter size={16} /> 조회
          </button>
        </div>

        {/* In Mobile: Graphs -> Summary -> Insights -> Tables */}
        {/* In Desktop: Summary -> Graphs -> Insights -> Tables */}
        
        {/* 2. Summary Cards */}
        <div className="order-2 lg:order-1 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <SummaryCard title="총 접수 DB" value="428" suffix="건" />
          <SummaryCard title="승인 DB" value="312" suffix="건" color="emerald" highlight />
          <SummaryCard title="취소/무효 DB" value="76" suffix="건" color="red" highlight />
          <SummaryCard title="평균 승인율" value="72.9" suffix="%" color="cyan" highlight />
          <SummaryCard title="총 사용 광고비" value="15,600,000" suffix="원" dark />
          <SummaryCard title="평균 DB 단가" value="50,000" suffix="원" />
        </div>

        {/* 3. Charts */}
        <div className="order-1 lg:order-2 grid grid-cols-1 xl:grid-cols-2 gap-6">
          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <h3 className="text-lg font-bold text-slate-900 mb-6">기간별 디비 처리 현황</h3>
            <div className="flex-1 min-h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <ComposedChart data={dbTrendData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                  <Legend wrapperStyle={{ paddingTop: '20px', fontSize: '12px' }} />
                  <Bar dataKey="접수" name="접수 DB" fill="#94a3b8" radius={[4, 4, 0, 0]} barSize={20} />
                  <Bar dataKey="승인" name="승인 DB" fill="#0891b2" radius={[4, 4, 0, 0]} barSize={20} />
                  <Line type="monotone" dataKey="취소" name="취소 DB" stroke="#ef4444" strokeWidth={2} dot={{ r: 4 }} />
                </ComposedChart>
              </ResponsiveContainer>
            </div>
          </div>

          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <h3 className="text-lg font-bold text-slate-900 mb-6">광고비 사용 추이</h3>
            <div className="flex-1 min-h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <ComposedChart data={spendTrendData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(val) => `${val / 10000}만`} />
                  <RechartsTooltip formatter={(val: number) => `${val.toLocaleString()}원`} contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                  <Legend wrapperStyle={{ paddingTop: '20px', fontSize: '12px' }} />
                  <Area type="monotone" dataKey="가차감" name="가차감 광고비" stroke="#3b82f6" fill="#3b82f6" fillOpacity={0.1} />
                  <Area type="monotone" dataKey="확정차감" name="확정 사용 광고비" stroke="#0f172a" fill="#0f172a" fillOpacity={0.1} />
                  <Line type="monotone" dataKey="환급" name="환급 광고비" stroke="#10b981" strokeWidth={2} dot={{ r: 4 }} />
                </ComposedChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* 4. Insights */}
        <div className="order-3">
          <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
            <div className="absolute top-0 right-0 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
            <div className="relative z-10 flex flex-col md:flex-row items-start gap-6">
              <div className="p-4 bg-slate-800 rounded-2xl shrink-0">
                <Lightbulb className="w-8 h-8 text-cyan-400" />
              </div>
              <div>
                <h3 className="text-xl font-bold mb-5">성과 인사이트</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4">
                  <div className="flex items-start gap-3">
                    <TrendingUp className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                    <p className="text-sm text-slate-300 leading-relaxed">
                      <strong className="text-white">개인회생 상담 DB</strong> 상품의 승인율이 76.7%로 가장 높습니다.
                    </p>
                  </div>
                  <div className="flex items-start gap-3">
                    <TrendingDown className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                    <p className="text-sm text-slate-300 leading-relaxed">
                      <strong className="text-white">PTN-5044</strong> 파트너의 취소율(28.1%)이 평균 대비 높습니다. 유입 품질 확인이 필요합니다.
                    </p>
                  </div>
                  <div className="flex items-start gap-3">
                    <TrendingUp className="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" />
                    <p className="text-sm text-slate-300 leading-relaxed">
                      이번 주 광고비 사용량이 지난주 대비 <strong className="text-white">18% 증가</strong>하며 안정적인 효율을 보이고 있습니다.
                    </p>
                  </div>
                  <div className="flex items-start gap-3">
                    <Lightbulb className="w-5 h-5 text-yellow-400 shrink-0 mt-0.5" />
                    <p className="text-sm text-slate-300 leading-relaxed">
                      승인율이 저조한 상품은 랜딩페이지나 배너의 <strong className="text-white">홍보 문구 점검</strong>을 권장합니다.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* 5. Tables */}
        <div className="order-4 space-y-8">
          {/* Campaign Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50">
              <div className="p-2 bg-white rounded-lg text-slate-700 shadow-sm border border-slate-200">
                <BarChart2 size={18} />
              </div>
              <h3 className="font-bold text-slate-900">광고상품별 성과</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">광고상품</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">접수 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">취소 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인율</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">취소율</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">사용 광고비</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">평균 단가</th>
                    <th className="px-5 py-4 font-medium text-center whitespace-nowrap">상태</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {campaignPerformance.map(item => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-5 py-4 font-bold text-slate-900 whitespace-nowrap">{item.name}</td>
                      <td className="px-5 py-4 text-right text-slate-600 font-mono whitespace-nowrap">{item.total.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-emerald-600 font-bold font-mono whitespace-nowrap">{item.approved.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-red-600 font-bold font-mono whitespace-nowrap">{item.canceled.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-cyan-600 font-bold font-mono whitespace-nowrap">{item.approvalRate}%</td>
                      <td className="px-5 py-4 text-right text-red-600 font-bold font-mono whitespace-nowrap">{item.cancelRate}%</td>
                      <td className="px-5 py-4 text-right font-bold text-slate-900 font-mono whitespace-nowrap">{item.spend.toLocaleString()}원</td>
                      <td className="px-5 py-4 text-right text-slate-600 font-mono whitespace-nowrap">{item.avgPrice.toLocaleString()}원</td>
                      <td className="px-5 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Partner Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50">
              <div className="p-2 bg-white rounded-lg text-slate-700 shadow-sm border border-slate-200">
                <Users size={18} />
              </div>
              <h3 className="font-bold text-slate-900">파트너별 성과</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">파트너 코드</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">유입 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">취소 DB</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인율</th>
                    <th className="px-5 py-4 font-medium text-right whitespace-nowrap">사용 광고비</th>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">비고</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {partnerPerformance.map(item => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-5 py-4 font-bold text-cyan-700 font-mono whitespace-nowrap">{item.id}</td>
                      <td className="px-5 py-4 text-right text-slate-600 font-mono whitespace-nowrap">{item.total.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-emerald-600 font-bold font-mono whitespace-nowrap">{item.approved.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-red-600 font-bold font-mono whitespace-nowrap">{item.canceled.toLocaleString()}</td>
                      <td className="px-5 py-4 text-right text-cyan-600 font-bold font-mono whitespace-nowrap">{item.approvalRate}%</td>
                      <td className="px-5 py-4 text-right font-bold text-slate-900 font-mono whitespace-nowrap">{item.spend.toLocaleString()}원</td>
                      <td className="px-5 py-4 text-slate-500 whitespace-nowrap">{item.note}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* 6. Bottom Actions */}
        <div className="order-5 flex flex-col sm:flex-row items-center justify-center gap-4 py-4">
          <button className="w-full sm:w-auto px-8 py-3.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-slate-900 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 shadow-sm">
            <Download size={18} /> 리포트 엑셀 다운로드
          </button>
          <button className="w-full sm:w-auto px-8 py-3.5 bg-slate-900 text-white hover:bg-slate-800 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 shadow-sm">
            <Calendar size={18} /> 기간별 리포트 보기 <ArrowRight size={18} />
          </button>
        </div>
      </div>
    </AdvertiserLayout>
  );
}
