import React from "react";
import { 
  AlertCircle,
  BarChart3, 
  Bell, 
  Building2, 
  ChevronRight, 
  CreditCard, 
  FileText, 
  LayoutDashboard, 
  MessageSquare, 
  Search, 
  Target, 
  User, 
  Wallet
} from 'lucide-react';

import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';

import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const mockChartData = [
  { date: '10.01', db: 24, approval: 18, cancel: 6 },
  { date: '10.02', db: 18, approval: 12, cancel: 6 },
  { date: '10.03', db: 35, approval: 25, cancel: 10 },
  { date: '10.04', db: 28, approval: 20, cancel: 8 },
  { date: '10.05', db: 42, approval: 30, cancel: 12 },
  { date: '10.06', db: 55, approval: 41, cancel: 14 },
  { date: '10.07', db: 60, approval: 45, cancel: 15 },
];

export function AdvertiserDashboard() {
  return (
    <AdvertiserLayout activeMenu="dashboard" title="대시보드">

        {/* Summary Cards */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
          <SummaryCard title="광고비 잔액" value="2,350,000" suffix="원" highlight color="cyan" />
          <SummaryCard title="가차감 광고비" value="450,000" suffix="원" />
          <SummaryCard title="사용 가능 잔액" value="1,900,000" suffix="원" dark />
          <SummaryCard title="오늘 접수 DB" value="17" suffix="건" />
          <SummaryCard title="승인대기 DB" value="9" suffix="건" color="blue" />
          <SummaryCard title="오늘 사용 광고비" value="300,000" suffix="원" />
        </div>

        {/* Middle Area: Chart & Tables */}
        <div className="grid lg:grid-cols-3 gap-8 mb-8">
          
          {/* Chart */}
          <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-900 mb-6">최근 7일 디비 처리 현황</h2>
            <div className="h-72">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={mockChartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="colorDb" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#94a3b8" stopOpacity={0.2}/>
                      <stop offset="95%" stopColor="#94a3b8" stopOpacity={0}/>
                    </linearGradient>
                    <linearGradient id="colorApproval" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#06b6d4" stopOpacity={0.2}/>
                      <stop offset="95%" stopColor="#06b6d4" stopOpacity={0}/>
                    </linearGradient>
                    <linearGradient id="colorCancel" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#ef4444" stopOpacity={0.1}/>
                      <stop offset="95%" stopColor="#ef4444" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <Tooltip 
                    contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                  />
                  <Area type="monotone" dataKey="db" name="접수 DB" stroke="#94a3b8" strokeWidth={2} fill="url(#colorDb)" />
                  <Area type="monotone" dataKey="approval" name="승인 DB" stroke="#06b6d4" strokeWidth={2} fill="url(#colorApproval)" />
                  <Area type="monotone" dataKey="cancel" name="취소 DB" stroke="#ef4444" strokeWidth={2} fill="url(#colorCancel)" />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Ad Spend Analysis */}
          <div className="bg-slate-900 rounded-2xl p-6 shadow-lg text-white flex flex-col justify-between">
            <div>
              <h2 className="text-lg font-bold mb-6 flex items-center gap-2">
                <Wallet className="text-cyan-400" />
                이번 달 광고비 현황
              </h2>
              
              <div className="space-y-4 mb-8">
                <div className="flex justify-between items-center py-3 border-b border-white/10">
                  <span className="text-slate-400">이번 달 충전액</span>
                  <span className="font-semibold text-white">5,000,000 원</span>
                </div>
                <div className="flex justify-between items-center py-3 border-b border-white/10">
                  <span className="text-slate-400">이번 달 사용액</span>
                  <span className="font-semibold text-rose-400">-2,650,000 원</span>
                </div>
                <div className="flex justify-between items-center py-4">
                  <span className="text-lg font-medium text-slate-300">남은 잔액</span>
                  <span className="text-3xl font-bold text-cyan-400">2,350,000 <span className="text-lg font-normal text-slate-400">원</span></span>
                </div>
              </div>
            </div>
            
            <button className="w-full py-4 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl transition-colors shadow-sm">
              광고비 충전하기
            </button>
          </div>
        </div>

        {/* Bottom Area: Recent DB & Notification */}
        <div className="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 rounded-lg text-blue-600">
              <AlertCircle size={20} />
            </div>
            <p className="text-blue-900 font-medium">현재 승인 또는 취소 처리가 필요한 디비가 <strong>9건</strong> 있습니다.</p>
          </div>
          <button className="text-sm font-bold text-blue-700 hover:text-blue-800 flex items-center gap-1">
            바로 처리하기 <ChevronRight size={16} />
          </button>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm overflow-hidden flex flex-col">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-bold text-slate-900">최근 접수 디비</h2>
            <button className="text-sm font-medium text-cyan-600 hover:text-cyan-700">디비 관리 가기</button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3 font-medium">접수일</th>
                  <th className="px-4 py-3 font-medium">고객명</th>
                  <th className="px-4 py-3 font-medium">연락처</th>
                  <th className="px-4 py-3 font-medium">상품명</th>
                  <th className="px-4 py-3 font-medium text-center">상태</th>
                  <th className="px-4 py-3 font-medium text-right">처리</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                <TableRow date="10.07 14:22" name="김민성" phone="010-4233-1255" product="개인회생 상담 DB" status="검수중"  needsAction />
                <TableRow date="10.07 13:15" name="이소희" phone="010-8812-5644" product="어린이 영어캠프" status="승인완료"  />
                <TableRow date="10.07 11:40" name="박재민" phone="010-2199-9922" product="개인회생 상담 DB" status="접수완료"  needsAction />
                <TableRow date="10.07 10:05" name="최지훈" phone="010-5511-3377" product="자동차 렌트 상담" status="취소/무효"  />
                <TableRow date="10.06 18:30" name="정민수" phone="010-7788-1122" product="소상공인 대출 상담" status="승인완료"  />
              </tbody>
            </table>
          </div>
        </div>

      </AdvertiserLayout>
  );
}

function NavItem({ icon, label, active = false, badge }: { icon: React.ReactNode, label: string, active?: boolean, badge?: number }) {
  return (
    <a href="#" className={`flex items-center justify-between px-4 py-3 rounded-xl transition-colors ${active ? 'bg-cyan-500/10 text-cyan-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-white/5'}`}>
      <div className="flex items-center gap-3">
        {icon}
        <span>{label}</span>
      </div>
      {badge && (
        <span className="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{badge}</span>
      )}
    </a>
  );
}


function TableRow({ date, name, phone, product, status, needsAction = false }: any) {
  return (
    <tr className="hover:bg-slate-50 transition-colors">
      <td className="px-4 py-4 text-slate-500 whitespace-nowrap">{date}</td>
      <td className="px-4 py-4 text-slate-900 font-medium whitespace-nowrap">{name}</td>
      <td className="px-4 py-4 text-slate-600 font-mono text-xs whitespace-nowrap">{phone}</td>
      <td className="px-4 py-4 text-slate-600 whitespace-nowrap">{product}</td>
      <td className="px-4 py-4 text-center whitespace-nowrap">
        <StatusBadge status={status} />
      </td>
      <td className="px-4 py-4 text-right whitespace-nowrap">
        {needsAction ? (
          <div className="flex gap-2 justify-end">
            <button className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded text-xs font-bold transition-colors shadow-sm">승인</button>
            <button className="px-3 py-1.5 bg-red-600 text-white hover:bg-red-700 rounded text-xs font-bold transition-colors shadow-sm">취소</button>
          </div>
        ) : (
          <button className="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded text-xs font-medium transition-colors">상세보기</button>
        )}
      </td>
    </tr>
  );
}