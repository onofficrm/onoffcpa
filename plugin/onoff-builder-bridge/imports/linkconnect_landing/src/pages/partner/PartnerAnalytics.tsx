import { MousePointerClick, Target, CheckCircle2, XCircle, Percent, Lightbulb, TrendingUp } from 'lucide-react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { PartnerLayout } from '../../layouts/PartnerLayout';

const mockChartData = [
  { date: '10.01', click: 400, db: 24, approval: 18 },
  { date: '10.02', click: 300, db: 18, approval: 12 },
  { date: '10.03', click: 550, db: 35, approval: 25 },
  { date: '10.04', click: 480, db: 28, approval: 20 },
  { date: '10.05', click: 600, db: 42, approval: 30 },
  { date: '10.06', click: 720, db: 55, approval: 41 },
  { date: '10.07', click: 850, db: 60, approval: 45 },
];

const mockChannelData = [
  { id: 1, channel: '네이버 블로그', subId: 'blog_01', click: 1248, received: 32, approved: 21, canceled: 4, convRate: '2.56%', appRate: '65.6%', estRev: 960000, confRev: 630000 },
  { id: 2, channel: '인스타그램', subId: 'insta_bio', click: 850, received: 12, approved: 8, canceled: 2, convRate: '1.41%', appRate: '66.6%', estRev: 300000, confRev: 200000 },
  { id: 3, channel: '유튜브', subId: 'yt_desc', click: 2100, received: 45, approved: 30, canceled: 8, convRate: '2.14%', appRate: '66.6%', estRev: 1440000, confRev: 960000 },
];

const mockCampaignData = [
  { id: 1, campaign: '개인회생 상담 DB', click: 3500, received: 85, approved: 55, appRate: '64.7%', confRev: 1650000 },
  { id: 2, campaign: '자동차 장기렌트 특가', click: 2100, received: 40, approved: 28, appRate: '70.0%', confRev: 700000 },
  { id: 3, campaign: '어린이 영어캠프', click: 1200, received: 15, approved: 8, appRate: '53.3%', confRev: 280000 },
];

const inflowData = [
  { name: '네이버 블로그', clickRate: '45%', db: 56, convRate: '2.8%', rev: '1,680,000', color: 'bg-emerald-500' },
  { name: '유튜브', clickRate: '25%', db: 35, convRate: '2.1%', rev: '1,120,000', color: 'bg-red-500' },
  { name: '네이버 카페', clickRate: '15%', db: 20, convRate: '2.5%', rev: '750,000', color: 'bg-emerald-400' },
  { name: '인스타그램 SNS', clickRate: '10%', db: 10, convRate: '1.8%', rev: '280,000', color: 'bg-purple-500' },
  { name: '기타', clickRate: '5%', db: 3, convRate: '1.2%', rev: '90,000', color: 'bg-slate-400' },
];

export function PartnerAnalytics() {
  return (
    <PartnerLayout activeMenu="analytics" title="유입 분석">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          유입경로, 채널, sub_id별 성과를 확인하고 효율 좋은 채널을 분석하세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="총 클릭 수" value="5,420" suffix="회" icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="총 접수 DB" value="124" suffix="건" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="총 승인 DB" value="85" suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title="총 취소 DB" value="15" suffix="건" icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="평균 전환율" value="2.2" suffix="%" icon={<Percent className="text-purple-500" />} />
        <SummaryCard title="평균 승인율" value="68.5" suffix="%" icon={<Percent className="text-emerald-500" />} highlight />
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center shadow-sm mb-8">
        <div className="flex items-center gap-2">
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-01" />
          <span className="text-slate-400">~</span>
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-07" />
        </div>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 광고상품</option>
          <option>개인회생 상담 DB</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 채널</option>
          <option>네이버 블로그</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 sub_id</option>
          <option>blog_01</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 유입경로</option>
          <option>자연검색</option>
        </select>
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        {/* Chart */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
          <h2 className="text-lg font-bold text-slate-900 mb-6">최근 7일 성과 추이</h2>
          <div className="h-72 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={mockChartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorClick" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.1}/>
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                  </linearGradient>
                  <linearGradient id="colorDb" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#06b6d4" stopOpacity={0.2}/>
                    <stop offset="95%" stopColor="#06b6d4" stopOpacity={0}/>
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
                <Area type="monotone" dataKey="db" name="접수 DB" stroke="#06b6d4" strokeWidth={2} fill="url(#colorDb)" />
                <Area type="monotone" dataKey="approval" name="승인 DB" stroke="#10b981" strokeWidth={2} fill="url(#colorApproval)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Insight Card */}
        <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg flex flex-col">
          <div className="flex items-center gap-2 mb-6">
            <Lightbulb className="text-yellow-400" size={24} />
            <h2 className="text-lg font-bold">성과 인사이트</h2>
          </div>
          <div className="space-y-4 flex-1">
            <div className="bg-white/10 rounded-xl p-4">
              <p className="text-sm text-slate-200">
                <strong className="text-emerald-400 font-bold">네이버 블로그</strong> 채널의 전환율이 가장 높습니다. (평균 2.8%)
              </p>
            </div>
            <div className="bg-white/10 rounded-xl p-4">
              <p className="text-sm text-slate-200">
                <strong className="text-red-400 font-bold">유튜브</strong> 채널은 클릭 수 대비 승인율이 다소 낮습니다. 타겟팅 점검이 필요할 수 있습니다.
              </p>
            </div>
            <div className="bg-white/10 rounded-xl p-4">
              <p className="text-sm text-slate-200">
                <strong className="text-cyan-400 font-bold font-mono">blog_01</strong> sub_id가 이번 주 가장 높은 수익을 기록했습니다.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Inflow cards */}
      <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
        <TrendingUp className="text-emerald-500" size={20} />
        유입경로 성과 분석
      </h2>
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        {inflowData.map((item, idx) => (
          <div key={idx} className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div className="flex items-center gap-2 mb-4">
              <div className={`w-3 h-3 rounded-full ${item.color}`}></div>
              <h3 className="font-bold text-slate-800">{item.name}</h3>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-slate-500">클릭 비중</span>
                <span className="font-medium text-slate-900">{item.clickRate}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">접수 DB</span>
                <span className="font-medium text-cyan-600">{item.db}건</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">전환율</span>
                <span className="font-medium text-purple-600">{item.convRate}</span>
              </div>
              <div className="pt-2 mt-2 border-t border-slate-100 flex justify-between items-center">
                <span className="text-xs text-slate-400">예상수익</span>
                <span className="font-bold text-emerald-600">{item.rev}원</span>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Tables */}
      <div className="grid lg:grid-cols-1 gap-8 mb-8">
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h2 className="font-bold text-slate-900">채널별 성과</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-5 py-4 font-medium whitespace-nowrap">채널명</th>
                  <th className="px-5 py-4 font-medium whitespace-nowrap">sub_id</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">클릭 수</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">접수 DB</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인 DB</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">취소 DB</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">전환율</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인율</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">예상수익</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">확정수익</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {mockChannelData.map((row) => (
                  <tr key={row.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-5 py-4 font-bold text-slate-900">{row.channel}</td>
                    <td className="px-5 py-4 font-medium text-slate-600">{row.subId}</td>
                    <td className="px-5 py-4 text-right font-medium text-slate-700">{row.click.toLocaleString()}</td>
                    <td className="px-5 py-4 text-right font-medium text-cyan-600">{row.received}</td>
                    <td className="px-5 py-4 text-right font-bold text-emerald-600">{row.approved}</td>
                    <td className="px-5 py-4 text-right font-medium text-red-500">{row.canceled}</td>
                    <td className="px-5 py-4 text-right font-medium text-purple-600">{row.convRate}</td>
                    <td className="px-5 py-4 text-right font-medium text-slate-700">{row.appRate}</td>
                    <td className="px-5 py-4 text-right text-slate-500">{row.estRev.toLocaleString()}원</td>
                    <td className="px-5 py-4 text-right font-bold text-slate-900">{row.confRev.toLocaleString()}원</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h2 className="font-bold text-slate-900">광고상품별 성과</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-5 py-4 font-medium whitespace-nowrap">광고상품</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">클릭 수</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">접수 DB</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인 DB</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인율</th>
                  <th className="px-5 py-4 font-medium text-right whitespace-nowrap">확정수익</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {mockCampaignData.map((row) => (
                  <tr key={row.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-5 py-4 font-bold text-slate-900">{row.campaign}</td>
                    <td className="px-5 py-4 text-right font-medium text-slate-700">{row.click.toLocaleString()}</td>
                    <td className="px-5 py-4 text-right font-medium text-cyan-600">{row.received}</td>
                    <td className="px-5 py-4 text-right font-bold text-emerald-600">{row.approved}</td>
                    <td className="px-5 py-4 text-right font-medium text-slate-700">{row.appRate}</td>
                    <td className="px-5 py-4 text-right font-bold text-slate-900">{row.confRev.toLocaleString()}원</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </PartnerLayout>
  );
}


