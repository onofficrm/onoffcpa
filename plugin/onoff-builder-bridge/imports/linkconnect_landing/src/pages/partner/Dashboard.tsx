import React from "react";
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

const mockChartData = [
  { date: '10.01', click: 400, db: 24, approval: 18 },
  { date: '10.02', click: 300, db: 18, approval: 12 },
  { date: '10.03', click: 550, db: 35, approval: 25 },
  { date: '10.04', click: 480, db: 28, approval: 20 },
  { date: '10.05', click: 600, db: 42, approval: 30 },
  { date: '10.06', click: 720, db: 55, approval: 41 },
  { date: '10.07', click: 850, db: 60, approval: 45 },
];

export function PartnerDashboard() {
  return (
    <PartnerLayout activeMenu="dashboard" title="파트너센터">
      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="오늘 클릭 수" value="1,248" icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="오늘 접수 DB" value="32" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="승인완료 DB" value="21" icon={<Target className="text-emerald-500" />} />
        <SummaryCard title="취소/무효 DB" value="4" icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="오늘 예상수입" value="960,000" suffix="원" highlight />
        <SummaryCard title="이번 달 확정수입" value="8,430,000" suffix="원" highlight dark />
      </div>

      {/* Middle Area: Chart & Tables */}
      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        
        {/* Chart */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6">최근 7일 성과 추이</h2>
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={mockChartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
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
                <Tooltip 
                  contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                />
                <Area type="monotone" dataKey="click" name="클릭 수" stroke="#3b82f6" strokeWidth={2} fill="url(#colorClick)" />
                <Area type="monotone" dataKey="approval" name="승인 DB" stroke="#10b981" strokeWidth={2} fill="url(#colorApproval)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Inflow Analysis */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
          <h2 className="text-lg font-bold text-slate-900 mb-6">유입경로 분석</h2>
          <div className="flex-1 flex flex-col justify-center gap-6">
            <div className="space-y-4">
              <InflowItem label="네이버 블로그" percentage={45} color="bg-emerald-500" />
              <InflowItem label="네이버 카페" percentage={25} color="bg-emerald-400" />
              <InflowItem label="유튜브" percentage={15} color="bg-red-500" />
              <InflowItem label="인스타그램 SNS" percentage={10} color="bg-purple-500" />
              <InflowItem label="기타" percentage={5} color="bg-slate-300" />
            </div>
          </div>
          <button className="mt-6 text-sm text-slate-500 hover:text-slate-900 flex items-center justify-center gap-1 w-full py-2 bg-slate-50 rounded-lg transition-colors">
            상세 리포트 보기 <ChevronRight size={16} />
          </button>
        </div>
      </div>

      {/* Bottom Area: Recent DB */}
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
                <TableRow date="10.07 14:22" product="개인회생 상담 DB" name="김*성" phone="010-42**-12**" status="검수중"  revenue="30,000" />
                <TableRow date="10.07 13:15" product="어린이 영어캠프" name="이*희" phone="010-88**-56**" status="승인완료"  revenue="35,000" />
                <TableRow date="10.07 11:40" product="자동차 렌트 상담" name="박*민" phone="010-21**-99**" status="접수완료"  revenue="25,000" />
                <TableRow date="10.07 10:05" product="개인회생 상담 DB" name="최*훈" phone="010-55**-33**" status="취소/무효"  revenue="0" />
                <TableRow date="10.06 18:30" product="소상공인 대출 상담" name="정*수" phone="010-77**-11**" status="정산완료"  revenue="32,000" isStrike />
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
                <span className="text-slate-400">이번 달 확정수입</span>
                <span className="font-semibold">8,430,000 원</span>
              </div>
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400">정산 대기</span>
                <span className="font-semibold text-yellow-400">1,200,000 원</span>
              </div>
              <div className="flex justify-between items-center py-4">
                <span className="text-lg font-medium text-slate-300">정산 가능</span>
                <span className="text-3xl font-bold text-emerald-400">7,230,000 <span className="text-lg font-normal text-slate-400">원</span></span>
              </div>
            </div>
          </div>
          
          <button className="w-full py-4 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl transition-colors">
            정산 신청하기
          </button>
        </div>

      </div>
    </PartnerLayout>
  );
}

function NavItem({ icon, label, active = false }: { icon: React.ReactNode, label: string, active?: boolean }) {
  return (
    <a href="#" className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-colors ${active ? 'bg-emerald-500/10 text-emerald-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-white/5'}`}>
      {icon}
      <span>{label}</span>
    </a>
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

function TableRow({ date, product, name, phone, status, statusColor, revenue, isStrike = false }: any) {
  const colors: Record<string, string> = {
    slate: 'bg-slate-100 text-slate-600 border-slate-200',
    blue: 'bg-blue-50 text-blue-600 border-blue-200',
    emerald: 'bg-emerald-50 text-emerald-600 border-emerald-200',
    red: 'bg-red-50 text-red-600 border-red-200',
  };

  return (
    <tr className="hover:bg-slate-50 transition-colors">
      <td className="px-4 py-3 text-slate-500">{date}</td>
      <td className={`px-4 py-3 font-medium ${isStrike ? 'text-slate-400 line-through' : 'text-slate-900'}`}>{product}</td>
      <td className="px-4 py-3 text-slate-600">{name}</td>
      <td className="px-4 py-3 text-slate-600 font-mono text-xs">{phone}</td>
      <td className="px-4 py-3 text-center">
        <span className={`px-2.5 py-1 text-xs font-medium rounded-md border ${colors[statusColor]}`}>
          {status}
        </span>
      </td>
      <td className={`px-4 py-3 text-right font-medium ${revenue === '0' ? 'text-slate-400' : 'text-emerald-600'}`}>
        {revenue !== '0' && '+'} {revenue}
      </td>
    </tr>
  );
}
