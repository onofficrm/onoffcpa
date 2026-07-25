import { ArrowUpRight, BarChart, CheckSquare, CreditCard, PieChart, Users } from "lucide-react";

export function AdvertiserPreview() {
  return (
    <section className="py-24 bg-white">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        <div className="mb-12 md:flex md:items-end md:justify-between">
          <div className="max-w-2xl">
            <h2 className="text-3xl font-bold text-slate-900 mb-4 tracking-tight">
              광고주는 광고비와 DB 성과를 <br className="sm:hidden"/>투명하게 확인할 수 있습니다
            </h2>
            <p className="text-lg text-slate-500">
              실시간 DB 확인부터 캠페인별 ROI 분석까지, 완벽한 광고 관리 환경을 제공합니다.
            </p>
          </div>
          <button className="mt-6 md:mt-0 inline-flex items-center gap-2 text-teal-600 font-semibold hover:text-teal-700 transition-colors">
            광고주센터 자세히 보기
            <ArrowUpRight className="h-4 w-4" />
          </button>
        </div>

        {/* Dashboard UI Wrapper (Dark Theme for contrast) */}
        <div className="rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden">
          {/* Top Bar */}
          <div className="border-b border-slate-800 bg-slate-900/50 px-6 py-4 flex justify-between items-center">
            <div className="flex gap-4">
              <div className="h-3 w-3 rounded-full bg-slate-700"></div>
              <div className="h-3 w-3 rounded-full bg-slate-700"></div>
              <div className="h-3 w-3 rounded-full bg-slate-700"></div>
            </div>
            <div className="text-sm font-medium text-slate-400">광고주 대시보드 미리보기</div>
            <div className="w-10"></div>
          </div>

          <div className="p-6 md:p-8">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
              <AdvertiserStatCard label="광고비 잔액" value="₩ 12,450,000" icon={<CreditCard />} trend="충전필요(7일내)" isAlert />
              <AdvertiserStatCard label="오늘 수집된 DB" value="84 건" icon={<Users />} trend="+15% (전일대비)" />
              <AdvertiserStatCard label="대기중인 DB" value="12 건" icon={<CheckSquare />} trend="빠른 승인 요망" isWarning />
              <AdvertiserStatCard label="평균 CPA 단가" value="₩ 15,000" icon={<BarChart />} trend="-2% (최근 7일)" isGood />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Left: DB List Preview */}
              <div className="bg-slate-800/50 rounded-xl border border-slate-700 p-6">
                <div className="flex justify-between items-center mb-6">
                  <h4 className="text-base font-bold text-white">실시간 상담 DB</h4>
                  <button className="text-xs font-medium text-teal-400 hover:text-teal-300">전체보기</button>
                </div>
                
                <div className="overflow-hidden rounded-lg border border-slate-700">
                  <table className="w-full text-left text-sm text-slate-300">
                    <thead className="bg-slate-800/80 text-xs uppercase text-slate-400">
                      <tr>
                        <th className="px-4 py-3 font-medium">유입시간</th>
                        <th className="px-4 py-3 font-medium">이름</th>
                        <th className="px-4 py-3 font-medium">연락처</th>
                        <th className="px-4 py-3 font-medium text-center">상태</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-700/50 bg-slate-800/30">
                      <DbRow time="10:42" name="김*철" phone="010-1234-****" status="대기" />
                      <DbRow time="10:15" name="이*영" phone="010-9876-****" status="승인" />
                      <DbRow time="09:50" name="박*민" phone="010-4567-****" status="승인" />
                      <DbRow time="09:22" name="최*훈" phone="010-5555-****" status="보류" />
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Right: Partner Analytics */}
              <div className="bg-slate-800/50 rounded-xl border border-slate-700 p-6">
                <div className="flex justify-between items-center mb-6">
                  <h4 className="text-base font-bold text-white flex items-center gap-2">
                    <PieChart className="h-5 w-5 text-slate-400" />
                    파트너별 유입 성과
                  </h4>
                </div>
                <div className="space-y-5">
                  <PartnerProgress name="파트너 A (influencer_x)" value={45} color="bg-teal-400" count="38 DB" />
                  <PartnerProgress name="파트너 B (blog_master)" value={25} color="bg-blue-400" count="21 DB" />
                  <PartnerProgress name="파트너 C (sns_king)" value={15} color="bg-indigo-400" count="12 DB" />
                  <PartnerProgress name="기타 파트너 (12명)" value={15} color="bg-slate-500" count="13 DB" />
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>
  );
}

function AdvertiserStatCard({ label, value, icon, trend, isAlert, isWarning, isGood }: any) {
  return (
    <div className="bg-slate-800/50 rounded-xl border border-slate-700 p-5 relative overflow-hidden">
      {isAlert && <div className="absolute top-0 right-0 w-16 h-16 bg-rose-500/10 rounded-bl-full"></div>}
      <div className="flex justify-between items-start mb-4">
        <div className="text-slate-400">
          <p className="text-sm font-medium mb-1">{label}</p>
          <h3 className="text-2xl font-bold text-white">{value}</h3>
        </div>
        <div className={`p-2 rounded-lg ${isAlert ? 'bg-rose-500/20 text-rose-400' : isWarning ? 'bg-amber-500/20 text-amber-400' : isGood ? 'bg-teal-500/20 text-teal-400' : 'bg-slate-700 text-slate-300'}`}>
          {icon}
        </div>
      </div>
      <p className={`text-xs font-medium ${isAlert ? 'text-rose-400' : isWarning ? 'text-amber-400' : isGood ? 'text-teal-400' : 'text-slate-500'}`}>
        {trend}
      </p>
    </div>
  )
}

function DbRow({ time, name, phone, status }: any) {
  const isPending = status === '대기';
  const isApproved = status === '승인';
  
  return (
    <tr className="hover:bg-slate-700/30 transition-colors">
      <td className="px-4 py-3 text-slate-400">{time}</td>
      <td className="px-4 py-3 font-medium text-slate-200">{name}</td>
      <td className="px-4 py-3 font-mono text-slate-400 text-xs">{phone}</td>
      <td className="px-4 py-3 text-center">
        <span className={`inline-flex px-2 py-0.5 rounded text-[11px] font-bold ${
          isPending ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
          isApproved ? 'bg-teal-500/20 text-teal-400 border border-teal-500/30' :
          'bg-slate-600/50 text-slate-400 border border-slate-600'
        }`}>
          {status}
        </span>
      </td>
    </tr>
  )
}

function PartnerProgress({ name, value, color, count }: any) {
  return (
    <div>
      <div className="flex justify-between items-end mb-2">
        <span className="text-sm font-medium text-slate-300">{name}</span>
        <span className="text-xs font-bold text-white">{count} ({value}%)</span>
      </div>
      <div className="w-full bg-slate-700/50 rounded-full h-2">
        <div className={`h-2 rounded-full ${color}`} style={{ width: `${value}%` }}></div>
      </div>
    </div>
  )
}
