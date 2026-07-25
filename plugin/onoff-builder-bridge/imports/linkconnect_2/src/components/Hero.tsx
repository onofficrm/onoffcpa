import { ArrowRight, BarChart3, CheckCircle2, Coins, MousePointerClick, TrendingUp, Users } from "lucide-react";

export function Hero() {
  return (
    <section className="relative overflow-hidden bg-slate-50 pt-20 pb-28">
      {/* Background decoration */}
      <div className="absolute inset-0 z-0">
        <div className="absolute -top-24 -right-24 h-[500px] w-[500px] rounded-full bg-sky-100/50 blur-3xl" />
        <div className="absolute top-48 -left-24 h-[400px] w-[400px] rounded-full bg-teal-50/50 blur-3xl" />
      </div>

      <div className="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
          
          {/* Left Content */}
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-medium text-blue-900 shadow-sm border border-slate-200 mb-6">
              <span className="flex h-2 w-2 rounded-full bg-teal-400"></span>
              신뢰할 수 있는 제휴마케팅 네트워크
            </div>
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight text-slate-900 leading-[1.15] mb-6">
              광고주와 파트너를 연결하는 <br />
              <span className="text-blue-900">성과형 마케팅 플랫폼</span>
            </h1>
            <p className="text-lg text-slate-600 mb-8 leading-relaxed max-w-lg">
              CPA DB 성과부터 CPS 매출 성과까지, 링크커넥트에서 캠페인 등록·성과 추적·정산 관리를 한 번에 확인하세요.
            </p>
            
            <div className="flex flex-col sm:flex-row gap-4">
              <button className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-6 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-blue-800 hover:shadow-md">
                파트너로 시작하기
                <ArrowRight className="h-5 w-5" />
              </button>
              <button className="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3.5 text-base font-semibold text-slate-700 shadow-sm border border-slate-200 transition-all hover:bg-slate-50 hover:text-slate-900">
                광고주 상담하기
              </button>
            </div>
          </div>

          {/* Right Dashboard Preview */}
          <div className="relative">
            {/* Main Dashboard Card */}
            <div className="rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-slate-200/50 p-6 overflow-hidden">
              <div className="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                <div className="flex items-center gap-3">
                  <div className="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center">
                    <UserAvatarIcon />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-slate-900">안녕하세요, 파트너님</p>
                    <p className="text-xs text-slate-500">오늘의 실시간 성과입니다.</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-xs text-slate-500 mb-1">예상 수익금</p>
                  <p className="text-xl font-bold text-slate-900">₩ 1,245,000</p>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <StatCard 
                  icon={<Users className="h-4 w-4 text-blue-600" />} 
                  label="오늘 발생 DB" 
                  value="142 건" 
                  trend="+12%" 
                  bg="bg-blue-50"
                  iconBg="bg-blue-100"
                />
                <StatCard 
                  icon={<CheckCircle2 className="h-4 w-4 text-teal-600" />} 
                  label="승인 DB" 
                  value="128 건" 
                  trend="+5%" 
                  bg="bg-teal-50"
                  iconBg="bg-teal-100"
                />
                <StatCard 
                  icon={<MousePointerClick className="h-4 w-4 text-indigo-600" />} 
                  label="유입 클릭수" 
                  value="3,402" 
                  trend="+24%" 
                  bg="bg-indigo-50"
                  iconBg="bg-indigo-100"
                />
                <StatCard 
                  icon={<TrendingUp className="h-4 w-4 text-amber-600" />} 
                  label="전환율" 
                  value="4.17%" 
                  trend="+0.8%" 
                  bg="bg-amber-50"
                  iconBg="bg-amber-100"
                />
              </div>

              {/* Chart Mockup */}
              <div className="mt-6 pt-6 border-t border-slate-100">
                <div className="flex items-center justify-between mb-4">
                  <p className="text-sm font-semibold text-slate-900">주간 성과 추이</p>
                  <div className="flex items-center gap-3 text-xs">
                    <span className="flex items-center gap-1"><div className="w-2 h-2 rounded-full bg-blue-600"></div>클릭</span>
                    <span className="flex items-center gap-1"><div className="w-2 h-2 rounded-full bg-teal-400"></div>DB</span>
                  </div>
                </div>
                <div className="h-32 w-full flex items-end gap-2 justify-between">
                  {/* CSS Mock Chart bars */}
                  {[40, 70, 45, 90, 65, 85, 100].map((h, i) => (
                    <div key={i} className="w-1/7 flex gap-1 items-end justify-center h-full group relative cursor-pointer">
                      <div className="w-3 rounded-t-sm bg-blue-600/20 group-hover:bg-blue-600 transition-colors" style={{ height: `${h}%` }}></div>
                      <div className="w-3 rounded-t-sm bg-teal-400/40 group-hover:bg-teal-400 transition-colors" style={{ height: `${h * 0.4}%` }}></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Floating Element: Ad Balance */}
            <div className="absolute -bottom-6 -left-8 rounded-xl bg-white p-4 shadow-xl shadow-slate-200/60 border border-slate-100 flex items-center gap-4 hidden sm:flex">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-900 text-white">
                <Coins className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-slate-500 font-medium mb-0.5">광고비 잔액</p>
                <p className="text-base font-bold text-slate-900">₩ 8,500,000</p>
              </div>
            </div>
            
            {/* Floating Element: Notification */}
            <div className="absolute -top-4 -right-4 rounded-xl bg-slate-900 p-3 shadow-lg shadow-blue-900/20 flex items-center gap-3 text-white hidden sm:flex">
              <div className="h-2 w-2 rounded-full bg-teal-400 animate-pulse"></div>
              <p className="text-xs font-medium pr-2">새로운 고단가 CPA 캠페인 등록됨</p>
            </div>
            
          </div>
        </div>
      </div>
    </section>
  );
}

function StatCard({ icon, label, value, trend, bg, iconBg }: any) {
  return (
    <div className={`rounded-xl ${bg} p-4 border border-white/50`}>
      <div className="flex items-center justify-between mb-3">
        <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${iconBg}`}>
          {icon}
        </div>
        <span className="text-xs font-semibold text-emerald-600 bg-white/60 px-2 py-0.5 rounded border border-white">{trend}</span>
      </div>
      <div>
        <p className="text-xs font-medium text-slate-500 mb-1">{label}</p>
        <p className="text-lg font-bold text-slate-900">{value}</p>
      </div>
    </div>
  )
}

function UserAvatarIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-slate-400">
      <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
      <circle cx="12" cy="7" r="4"></circle>
    </svg>
  )
}
