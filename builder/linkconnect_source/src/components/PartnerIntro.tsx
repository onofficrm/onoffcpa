import { Bar, BarChart, ResponsiveContainer, XAxis } from 'recharts';
import { ArrowRight, ArrowUpRight, BarChart3, Database, Wallet } from 'lucide-react';
import { Link } from 'react-router-dom';

const dbData = [
  { name: '11/01', db: 45 },
  { name: '11/02', db: 52 },
  { name: '11/03', db: 38 },
  { name: '11/04', db: 65 },
  { name: '11/05', db: 48 },
  { name: '11/06', db: 72 },
  { name: '11/07', db: 85 },
];

export function PartnerIntro() {
  return (
    <section id="partner" className="py-24 bg-slate-900 border-b border-slate-800 overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          <div className="space-y-8">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
                내 수익, DB, 유입분석을 <br />
                <span className="text-emerald-400">한눈에</span>
              </h2>
              <p className="text-slate-400 text-lg">
                복잡한 데이터도 직관적인 대시보드에서. <br/>
                전환율을 높이는 인사이트를 파트너센터에서 제공합니다.
              </p>
            </div>
            
            <ul className="space-y-4">
              {['수익금 정산 및 출금 신청', '실시간 DB 승인/반려 현황', '유입 URL 및 키워드 분석', '캠페인별 성과 리포트'].map((item, i) => (
                <li key={i} className="flex items-center gap-3 text-slate-300 font-medium">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                    <ArrowUpRight className="w-4 h-4 text-emerald-400" />
                  </div>
                  {item}
                </li>
              ))}
            </ul>
            <Link to="/partner" className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl transition-colors">
              파트너센터 바로가기 <ArrowRight className="w-5 h-5" />
            </Link>
          </div>

          <div className="relative">
            <div className="absolute inset-0 bg-emerald-500/10 blur-[100px] rounded-full pointer-events-none"></div>
            <div className="relative bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-2xl">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center border border-emerald-500/20">
                  <Wallet className="w-5 h-5 text-emerald-400" />
                </div>
                <div>
                  <div className="text-sm text-slate-400">출금 가능 수익금</div>
                  <div className="text-xl font-bold text-white">4,250,000 원</div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-6">
                <div className="p-4 bg-slate-900/50 rounded-xl border border-slate-700/50">
                  <div className="text-sm text-slate-400 mb-2 flex items-center gap-2">
                    <Database className="w-4 h-4 text-cyan-400" /> 누적 DB
                  </div>
                  <div className="text-2xl font-bold text-white">405<span className="text-sm font-normal text-slate-500"> 건</span></div>
                </div>
                <div className="p-4 bg-slate-900/50 rounded-xl border border-slate-700/50">
                  <div className="text-sm text-slate-400 mb-2 flex items-center gap-2">
                    <BarChart3 className="w-4 h-4 text-yellow-400" /> 승인율
                  </div>
                  <div className="text-2xl font-bold text-white">82.5<span className="text-sm font-normal text-slate-500"> %</span></div>
                </div>
              </div>

              <div className="h-48 mt-8">
                <div className="text-sm font-medium text-slate-400 mb-4">최근 7일 DB 현황</div>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={dbData}>
                    <XAxis dataKey="name" stroke="#64748b" fontSize={12} tickLine={false} axisLine={false} />
                    <Bar dataKey="db" fill="#34d399" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
