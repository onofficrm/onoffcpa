import React, { useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Building2, Database, ShieldAlert, CreditCard, 
  ServerCrash, RefreshCw, AlertCircle
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer, BarChart, Bar, Legend, ComposedChart, Line, CartesianGrid } from 'recharts';
import { fetchAdminDashboard, fetchAdminAiSummary } from '../../lib/api';
import { AiReportInsight } from '../../components/AiReportInsight';

const fallbackChartData = [
  { date: '10.01', 접수: 210, 승인: 145, 취소: 32, 매출: 7250000 },
  { date: '10.02', 접수: 235, 승인: 160, 취소: 35, 매출: 8000000 },
  { date: '10.03', 접수: 198, 승인: 135, 취소: 28, 매출: 6750000 },
  { date: '10.04', 접수: 250, 승인: 172, 취소: 40, 매출: 8600000 },
  { date: '10.05', 접수: 220, 승인: 155, 취소: 36, 매출: 7750000 },
  { date: '10.06', 접수: 275, 승인: 190, 취소: 45, 매출: 9500000 },
  { date: '10.07', 접수: 248, 승인: 173, 취소: 42, 매출: 8650000 },
];

const campaignData = [
  { name: '개인회생 무료상담 지원센터', advertiser: '희망법무법인', total: 1250, approved: 850, canceled: 150, rate: '68.0%', revenue: 42500000, partnerProfit: 25500000, margin: 17000000, status: '정상' },
  { name: '직장인 신용대출 한도조회', advertiser: '(주)성공대부', total: 980, approved: 720, canceled: 110, rate: '73.4%', revenue: 21600000, partnerProfit: 14400000, margin: 7200000, status: '정상' },
  { name: '자동차 장기렌트 특가', advertiser: '스피드렌터카', total: 650, approved: 410, canceled: 120, rate: '63.0%', revenue: 16400000, partnerProfit: 8200000, margin: 8200000, status: '광고비부족' },
  { name: '어린이 화상영어 1주 체험', advertiser: '키즈잉글리시', total: 420, approved: 350, canceled: 30, rate: '83.3%', revenue: 10500000, partnerProfit: 6300000, margin: 4200000, status: '정상' },
];

const partnerTop5 = [
  { code: 'PT-8832', total: 450, approved: 320, rate: '71.1%', profit: 12500000 },
  { code: 'PT-1029', total: 380, approved: 290, rate: '76.3%', profit: 9800000 },
  { code: 'PT-5591', total: 410, approved: 260, rate: '63.4%', profit: 8500000 },
  { code: 'PT-2248', total: 290, approved: 210, rate: '72.4%', profit: 6200000 },
  { code: 'PT-7731', total: 250, approved: 180, rate: '72.0%', profit: 5400000 },
];

const advertiserTop5 = [
  { name: '희망법무법인', total: 850, approved: 620, spend: 31000000, balance: 14500000 },
  { name: '(주)성공대부', total: 720, approved: 510, spend: 15300000, balance: 2500000 },
  { name: '스피드렌터카', total: 580, approved: 390, spend: 15600000, balance: 120000 },
  { name: '라이프보험법인', total: 450, approved: 310, spend: 12400000, balance: 8500000 },
  { name: '에듀스터디', total: 380, approved: 280, spend: 8400000, balance: 4200000 },
];

const recentDb = [
  { date: '10.07 14:22', campaign: '개인회생 상담', partner: 'PT-8832', advertiser: '희망법무', customer: '이*성', status: '신규접수' },
  { date: '10.07 14:18', campaign: '신용대출 조회', partner: 'PT-1029', advertiser: '성공대부', customer: '박*민', status: '확인중' },
  { date: '10.07 14:15', campaign: '장기렌트 특가', partner: 'PT-5591', advertiser: '스피드렌터', customer: '최*훈', status: '취소요청' },
  { date: '10.07 14:10', campaign: '개인회생 상담', partner: 'PT-8832', advertiser: '희망법무', customer: '김*수', status: '승인완료' },
];

const recentCancels = [
  { date: '10.07 13:45', campaign: '장기렌트 특가', advertiser: '스피드렌터카', reason: '결번/통화불가', status: '검수대기' },
  { date: '10.07 13:20', campaign: '신용대출 조회', advertiser: '(주)성공대부', reason: '단순변심', status: '검수대기' },
  { date: '10.07 12:15', campaign: '화상영어 체험', advertiser: '키즈잉글리시', reason: '중복신청', status: '검수완료' },
  { date: '10.07 11:30', campaign: '개인회생 상담', advertiser: '희망법무법인', reason: '조건미달(나이)', status: '검수반려' },
];

const apiErrorsFallback = [
  { time: '14:25:33', name: '희망법무법인 CRM', code: 'ERR_TIMEOUT', msg: '응답시간 초과 (5000ms)' },
  { time: '13:10:12', name: '스피드렌터카 ERP', code: 'ERR_AUTH', msg: '유효하지 않은 API 토큰' },
];

export function AdminDashboard() {
  const [summary, setSummary] = useState({
    todayReceived: 248,
    todayApproved: 173,
    todayRejected: 42,
    todayRate: 69.7,
    todayRevenue: 8650000,
    pendingDb: 18,
    pendingCharge: 2,
    pendingPartners: 1,
    pendingMerchants: 1,
  });
  const [chartData, setChartData] = useState(fallbackChartData);
  const [recentItems, setRecentItems] = useState(recentDb);
  const [campaignRows, setCampaignRows] = useState(campaignData);
  const [partnerRows, setPartnerRows] = useState(partnerTop5);
  const [advertiserRows, setAdvertiserRows] = useState(advertiserTop5);
  const [cancelRows, setCancelRows] = useState(recentCancels);
  const [lowBalanceMerchants, setLowBalanceMerchants] = useState(3);
  const [pendingInspections, setPendingInspections] = useState(0);
  const [pendingSettlements, setPendingSettlements] = useState(0);
  const [apiErrorRows, setApiErrorRows] = useState(apiErrorsFallback);

  useEffect(() => {
    fetchAdminDashboard()
      .then((data) => {
        setSummary(data.summary);
        setLowBalanceMerchants(data.merchants.lowBalance);
        setPendingInspections(data.summary.pendingInspections ?? 0);
        setPendingSettlements(data.summary.pendingSettlements ?? 0);
        if (data.chart7d.length) {
          setChartData(data.chart7d.map((row) => ({
            date: row.date,
            접수: row.received,
            승인: row.approved,
            취소: row.rejected,
            매출: row.revenue,
          })));
        }
        if (data.recent.length) {
          setRecentItems(data.recent.map((row) => ({
            date: row.date,
            campaign: row.campaign,
            partner: row.partner,
            advertiser: row.advertiser,
            customer: row.customer,
            status: row.status,
          })));
        }
        if (data.campaignTop?.length) {
          setCampaignRows(data.campaignTop.map((row) => ({
            name: row.name,
            advertiser: row.advertiser,
            total: row.total,
            approved: row.approved,
            canceled: row.canceled,
            rate: row.rate,
            revenue: row.revenue,
            partnerProfit: 0,
            margin: 0,
            status: row.status,
          })));
        }
        if (data.partnerTop5?.length) {
          setPartnerRows(data.partnerTop5.map((row) => ({
            code: row.code,
            total: row.total,
            approved: row.approved,
            rate: row.rate,
            profit: row.profit,
          })));
        }
        if (data.advertiserTop5?.length) {
          setAdvertiserRows(data.advertiserTop5.map((row) => ({
            name: row.name,
            total: row.total,
            approved: row.approved,
            spend: row.spend,
            balance: row.balance,
          })));
        }
        if (data.recentCancels?.length) {
          setCancelRows(data.recentCancels.map((row) => ({
            date: row.date,
            campaign: row.campaign,
            advertiser: row.advertiser,
            reason: row.reason,
            status: row.status,
          })));
        }
        if (data.apiErrors?.length) {
          setApiErrorRows(data.apiErrors.map((row) => ({
            time: row.time,
            name: row.name,
            code: row.code,
            msg: row.msg,
          })));
        } else {
          setApiErrorRows([]);
        }
      })
      .catch(() => {
        // 샘플 UI fallback
      });
  }, []);

  return (
    <AdminLayout activeMenu="dashboard" title="관리자 통합 대시보드" description="링크커넥트 CPA 운영 현황과 주요 이슈를 한눈에 확인하세요.">
      <AiReportInsight title="AI 운영 브리핑" fetchSummary={fetchAdminAiSummary} />

      {/* 8 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <SummaryCard title="오늘 접수 DB" value={String(summary.todayReceived)} suffix="건" />
        <SummaryCard title="오늘 승인 DB" value={String(summary.todayApproved)} suffix="건" color="emerald" highlight />
        <SummaryCard title="오늘 취소/무효 DB" value={String(summary.todayRejected)} suffix="건" color="red" highlight />
        <SummaryCard title="오늘 승인율" value={String(summary.todayRate)} suffix="%" />
        
        <SummaryCard title="오늘 매출" value={summary.todayRevenue.toLocaleString()} suffix="원" color="cyan" highlight />
        <SummaryCard title="승인 대기 DB" value={String(summary.pendingDb)} suffix="건" />
        <SummaryCard title="충전 대기" value={String(summary.pendingCharge)} suffix="건" color="blue" highlight />
        <SummaryCard title="승인 대기 파트너" value={String(summary.pendingPartners)} suffix="명" dark />
      </div>

      <div className="grid lg:grid-cols-3 gap-6 mb-6">
        {/* Chart */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-bold text-slate-900">최근 7일 전체 성과</h2>
            <select className="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg px-3 py-1.5 outline-none focus:border-cyan-500">
              <option>최근 7일</option>
              <option>최근 30일</option>
            </select>
          </div>
          <div className="h-[280px]">
            <ResponsiveContainer width="100%" height="100%">
              <ComposedChart data={chartData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                <YAxis yAxisId="left" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                <YAxis yAxisId="right" orientation="right" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(value) => `${value / 10000}만`} />
                <Tooltip 
                  contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                  formatter={(value: any, name: string) => {
                    if (name === '매출') return [`${value.toLocaleString()}원`, '매출액'];
                    return [`${value}건`, name];
                  }}
                />
                <Legend iconType="circle" wrapperStyle={{ fontSize: '12px', paddingTop: '10px' }} />
                <Bar yAxisId="left" dataKey="접수" name="접수 DB" fill="#94a3b8" barSize={16} radius={[4, 4, 0, 0]} />
                <Bar yAxisId="left" dataKey="승인" name="승인 DB" fill="#10b981" barSize={16} radius={[4, 4, 0, 0]} />
                <Bar yAxisId="left" dataKey="취소" name="취소 DB" fill="#f43f5e" barSize={16} radius={[4, 4, 0, 0]} />
                <Line yAxisId="right" type="monotone" dataKey="매출" name="매출" stroke="#06b6d4" strokeWidth={3} dot={{ r: 4, strokeWidth: 2 }} activeDot={{ r: 6 }} />
              </ComposedChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Alerts */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-bold text-slate-900">오늘 처리 필요 알림</h2>
          </div>
          <div className="flex flex-col gap-3 flex-1 overflow-y-auto pr-2">
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-orange-50 border border-orange-100 group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                  <ShieldAlert size={18} />
                </div>
                <span className="text-orange-900 font-medium text-sm">취소/무효 검수 대기 <strong className="text-orange-700 ml-1">{pendingInspections}건</strong></span>
              </div>
              <button className="text-xs font-bold text-orange-700 hover:text-orange-800 bg-white px-3 py-1.5 rounded-lg border border-orange-200 transition-colors shadow-sm">바로가기</button>
            </div>
            
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 border border-emerald-100 group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                  <CreditCard size={18} />
                </div>
                <span className="text-emerald-900 font-medium text-sm">충전 승인 대기 <strong className="text-emerald-700 ml-1">{summary.pendingCharge}건</strong></span>
              </div>
              <button className="text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-white px-3 py-1.5 rounded-lg border border-emerald-200 transition-colors shadow-sm">바로가기</button>
            </div>
            
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-red-50 border border-red-100 group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                  <Building2 size={18} />
                </div>
                <span className="text-red-900 font-medium text-sm">광고비 부족 광고주 <strong className="text-red-700 ml-1">{lowBalanceMerchants}곳</strong></span>
              </div>
              <button className="text-xs font-bold text-red-700 hover:text-red-800 bg-white px-3 py-1.5 rounded-lg border border-red-200 transition-colors shadow-sm">바로가기</button>
            </div>
            
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-rose-50 border border-rose-100 group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-rose-100 flex items-center justify-center text-rose-600">
                  <ServerCrash size={18} />
                </div>
                <span className="text-rose-900 font-medium text-sm">API 오류 <strong className="text-rose-700 ml-1">{apiErrorRows.length}건</strong></span>
              </div>
              <button className="text-xs font-bold text-rose-700 hover:text-rose-800 bg-white px-3 py-1.5 rounded-lg border border-rose-200 transition-colors shadow-sm">바로가기</button>
            </div>

            <div className="flex items-center justify-between p-3.5 rounded-xl bg-yellow-50 border border-yellow-100 group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                  <AlertCircle size={18} />
                </div>
                <span className="text-yellow-900 font-medium text-sm">승인 대기 파트너/광고주 <strong className="text-yellow-700 ml-1">{summary.pendingPartners + summary.pendingMerchants}곳</strong></span>
              </div>
              <button className="text-xs font-bold text-yellow-700 hover:text-yellow-800 bg-white px-3 py-1.5 rounded-lg border border-yellow-200 transition-colors shadow-sm">바로가기</button>
            </div>
          </div>
        </div>
      </div>

      {/* Campaign Performance Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6 flex flex-col">
        <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
          <h2 className="text-lg font-bold text-slate-900">광고상품별 실적</h2>
          <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-3 font-medium">광고상품명</th>
                <th className="px-6 py-3 font-medium">광고주</th>
                <th className="px-6 py-3 font-medium text-right">접수 DB</th>
                <th className="px-6 py-3 font-medium text-right">승인 DB</th>
                <th className="px-6 py-3 font-medium text-right">취소 DB</th>
                <th className="px-6 py-3 font-medium text-right">승인율</th>
                <th className="px-6 py-3 font-medium text-right">매출</th>
                <th className="px-6 py-3 font-medium text-right">파트너 수익</th>
                <th className="px-6 py-3 font-medium text-right">관리자 마진</th>
                <th className="px-6 py-3 font-medium text-center">상태</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {campaignRows.map((item, i) => (
                <tr key={i} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-900 font-bold whitespace-nowrap">{item.name}</td>
                  <td className="px-6 py-4 text-slate-600 whitespace-nowrap">{item.advertiser}</td>
                  <td className="px-6 py-4 text-slate-900 text-right font-medium whitespace-nowrap">{item.total.toLocaleString()}</td>
                  <td className="px-6 py-4 text-emerald-600 text-right font-bold whitespace-nowrap">{item.approved.toLocaleString()}</td>
                  <td className="px-6 py-4 text-red-600 text-right font-medium whitespace-nowrap">{item.canceled.toLocaleString()}</td>
                  <td className="px-6 py-4 text-slate-900 text-right font-bold whitespace-nowrap">{item.rate}</td>
                  <td className="px-6 py-4 text-slate-900 text-right font-bold whitespace-nowrap">{item.revenue.toLocaleString()}원</td>
                  <td className="px-6 py-4 text-slate-600 text-right font-medium whitespace-nowrap">{item.partnerProfit.toLocaleString()}원</td>
                  <td className="px-6 py-4 text-cyan-600 text-right font-bold whitespace-nowrap">{item.margin.toLocaleString()}원</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={item.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Top 5 Lists */}
      <div className="grid lg:grid-cols-2 gap-6 mb-6">
        {/* Partner Top 5 */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
          <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 className="text-lg font-bold text-slate-900">파트너 TOP 5 (당월)</h2>
            <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3 font-medium">파트너 코드</th>
                  <th className="px-6 py-3 font-medium text-right">접수 DB</th>
                  <th className="px-6 py-3 font-medium text-right">승인 DB</th>
                  <th className="px-6 py-3 font-medium text-right">승인율</th>
                  <th className="px-6 py-3 font-medium text-right">확정수익</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {partnerRows.map((item, i) => (
                  <tr key={i} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-4 text-slate-900 font-bold whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <span className={`w-5 h-5 rounded flex items-center justify-center text-[10px] font-bold ${i < 3 ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-500'}`}>{i + 1}</span>
                        {item.code}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-slate-600 text-right font-medium whitespace-nowrap">{item.total.toLocaleString()}</td>
                    <td className="px-6 py-4 text-emerald-600 text-right font-bold whitespace-nowrap">{item.approved.toLocaleString()}</td>
                    <td className="px-6 py-4 text-slate-900 text-right font-medium whitespace-nowrap">{item.rate}</td>
                    <td className="px-6 py-4 text-slate-900 text-right font-bold whitespace-nowrap">{item.profit.toLocaleString()}원</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Advertiser Top 5 */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
          <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 className="text-lg font-bold text-slate-900">광고주 TOP 5 (당월)</h2>
            <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3 font-medium">광고주명</th>
                  <th className="px-6 py-3 font-medium text-right">접수 DB</th>
                  <th className="px-6 py-3 font-medium text-right">승인 DB</th>
                  <th className="px-6 py-3 font-medium text-right">사용 광고비</th>
                  <th className="px-6 py-3 font-medium text-right">광고비 잔액</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {advertiserRows.map((item, i) => (
                  <tr key={i} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-4 text-slate-900 font-bold whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <span className={`w-5 h-5 rounded flex items-center justify-center text-[10px] font-bold ${i < 3 ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-500'}`}>{i + 1}</span>
                        {item.name}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-slate-600 text-right font-medium whitespace-nowrap">{item.total.toLocaleString()}</td>
                    <td className="px-6 py-4 text-emerald-600 text-right font-bold whitespace-nowrap">{item.approved.toLocaleString()}</td>
                    <td className="px-6 py-4 text-slate-900 text-right font-medium whitespace-nowrap">{item.spend.toLocaleString()}원</td>
                    <td className="px-6 py-4 text-slate-600 text-right font-bold whitespace-nowrap">{item.balance.toLocaleString()}원</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Bottom section */}
      <div className="grid lg:grid-cols-2 gap-6 mb-6">
        {/* Recent DB */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
          <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 className="text-lg font-bold text-slate-900">최근 접수 디비</h2>
            <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3 font-medium">접수일</th>
                  <th className="px-6 py-3 font-medium">광고상품</th>
                  <th className="px-6 py-3 font-medium">광고주</th>
                  <th className="px-6 py-3 font-medium">고객명</th>
                  <th className="px-6 py-3 font-medium text-center">상태</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {recentItems.map((item, i) => (
                  <tr key={i} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-3 text-slate-500 whitespace-nowrap">{item.date}</td>
                    <td className="px-6 py-3 text-slate-900 font-bold whitespace-nowrap">{item.campaign}</td>
                    <td className="px-6 py-3 text-slate-600 whitespace-nowrap">{item.advertiser}</td>
                    <td className="px-6 py-3 text-slate-900 whitespace-nowrap">{item.customer}</td>
                    <td className="px-6 py-3 text-center whitespace-nowrap">
                      <StatusBadge status={item.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Recent Cancel Requests */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
          <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 className="text-lg font-bold text-slate-900">최근 취소/무효 요청</h2>
            <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3 font-medium">요청일</th>
                  <th className="px-6 py-3 font-medium">광고상품</th>
                  <th className="px-6 py-3 font-medium">취소 사유</th>
                  <th className="px-6 py-3 font-medium text-center">처리상태</th>
                  <th className="px-6 py-3 font-medium text-right">관리</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {cancelRows.map((item, i) => (
                  <tr key={i} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-3 text-slate-500 whitespace-nowrap">{item.date}</td>
                    <td className="px-6 py-3 text-slate-900 font-bold whitespace-nowrap">{item.campaign}</td>
                    <td className="px-6 py-3 text-slate-600 whitespace-nowrap">{item.reason}</td>
                    <td className="px-6 py-3 text-center whitespace-nowrap">
                      <StatusBadge status={item.status} />
                    </td>
                    <td className="px-6 py-3 text-right whitespace-nowrap">
                      <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">검수하기</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* API Logs */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
          <div className="flex items-center gap-2">
            <h2 className="text-lg font-bold text-slate-900">API 오류 로그</h2>
            <span className="bg-rose-100 text-rose-700 text-xs font-bold px-2 py-0.5 rounded-full">최근 24시간</span>
          </div>
          <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">더보기</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-3 font-medium">발생시간</th>
                <th className="px-6 py-3 font-medium">연동명</th>
                <th className="px-6 py-3 font-medium">오류코드</th>
                <th className="px-6 py-3 font-medium">메시지</th>
                <th className="px-6 py-3 font-medium text-right">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {apiErrorRows.length === 0 ? (
                <tr><td colSpan={5} className="px-6 py-8 text-center text-slate-500">최근 24시간 API 오류가 없습니다.</td></tr>
              ) : apiErrorRows.map((item, i) => (
                <tr key={i} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.time}</td>
                  <td className="px-6 py-4 text-slate-900 font-bold whitespace-nowrap">{item.name}</td>
                  <td className="px-6 py-4 text-slate-600 font-mono text-xs whitespace-nowrap">{item.code}</td>
                  <td className="px-6 py-4 text-rose-600 whitespace-nowrap">{item.msg}</td>
                  <td className="px-6 py-4 text-right whitespace-nowrap">
                    <button className="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded text-xs font-bold transition-colors flex items-center justify-end gap-1 ml-auto shadow-sm">
                      <RefreshCw size={12} /> 재시도
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

    </AdminLayout>
  );
}
