import { ArrowUpRight, BarChart2, CheckCircle, Clock, Database, DollarSign, Search } from "lucide-react";

export function PartnerPreview() {
  return (
    <section className="py-24 bg-slate-50 border-y border-slate-200">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        <div className="mb-12 md:flex md:items-end md:justify-between">
          <div className="max-w-2xl">
            <h2 className="text-3xl font-bold text-slate-900 mb-4 tracking-tight">
              파트너는 수익과 유입현황을 <br className="sm:hidden"/>쉽게 확인할 수 있습니다
            </h2>
            <p className="text-lg text-slate-500">
              투명한 정산 시스템과 강력한 유입 분석 툴로 수익 극대화를 지원합니다.
            </p>
          </div>
          <button className="mt-6 md:mt-0 inline-flex items-center gap-2 text-blue-600 font-semibold hover:text-blue-700 transition-colors">
            파트너센터 자세히 보기
            <ArrowUpRight className="h-4 w-4" />
          </button>
        </div>

        {/* Dashboard UI Wrapper */}
        <div className="rounded-2xl bg-white border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
          {/* Top Bar */}
          <div className="border-b border-slate-100 bg-slate-50/50 px-6 py-4 flex justify-between items-center">
            <div className="flex gap-4">
              <div className="h-3 w-3 rounded-full bg-rose-400"></div>
              <div className="h-3 w-3 rounded-full bg-amber-400"></div>
              <div className="h-3 w-3 rounded-full bg-emerald-400"></div>
            </div>
            <div className="text-sm font-medium text-slate-500">파트너 대시보드 미리보기</div>
            <div className="w-10"></div>
          </div>

          <div className="p-6 md:p-8 bg-slate-50/30">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              {/* Highlight Card 1 */}
              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex justify-between items-start mb-4">
                  <div className="p-2.5 bg-teal-50 text-teal-600 rounded-lg">
                    <DollarSign className="h-5 w-5" />
                  </div>
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                    이번 달 예정
                  </span>
                </div>
                <p className="text-sm font-medium text-slate-500 mb-1">정산 예정 금액</p>
                <h3 className="text-2xl font-bold text-slate-900">₩ 3,450,000</h3>
              </div>

              {/* Highlight Card 2 */}
              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex justify-between items-start mb-4">
                  <div className="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                    <Database className="h-5 w-5" />
                  </div>
                </div>
                <p className="text-sm font-medium text-slate-500 mb-1">이번 주 DB 발생</p>
                <div className="flex items-baseline gap-2">
                  <h3 className="text-2xl font-bold text-slate-900">342 건</h3>
                  <span className="text-sm text-blue-600 font-medium">+12%</span>
                </div>
              </div>

              {/* Highlight Card 3 */}
              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex justify-between items-start mb-4">
                  <div className="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
                    <CheckCircle className="h-5 w-5" />
                  </div>
                </div>
                <p className="text-sm font-medium text-slate-500 mb-1">평균 승인율</p>
                <div className="flex items-baseline gap-2">
                  <h3 className="text-2xl font-bold text-slate-900">89.4%</h3>
                  <span className="text-sm text-slate-400 font-medium">우수</span>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Left Column (2/3) */}
              <div className="lg:col-span-2 space-y-6">
                <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                  <h4 className="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <BarChart2 className="h-5 w-5 text-slate-400" />
                    캠페인별 성과 (Top 3)
                  </h4>
                  <div className="space-y-4">
                    <CampaignStatRow name="A법무법인 개인회생 DB" clicks="1,240" db="85" rate="6.8%" revenue="₩ 1,275,000" />
                    <CampaignStatRow name="B피부과 체험단 이벤트" clicks="890" db="64" rate="7.1%" revenue="₩ 960,000" />
                    <CampaignStatRow name="C교육원 자격증 상담" clicks="2,100" db="112" rate="5.3%" revenue="₩ 1,120,000" />
                  </div>
                </div>
              </div>

              {/* Right Column (1/3) */}
              <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h4 className="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                  <Search className="h-5 w-5 text-slate-400" />
                  주요 유입 키워드
                </h4>
                <div className="space-y-3">
                  <KeywordRow rank="1" keyword="개인회생 비용" percent="35%" />
                  <KeywordRow rank="2" keyword="여드름 흉터 치료" percent="24%" />
                  <KeywordRow rank="3" keyword="사회복지사 2급" percent="18%" />
                  <KeywordRow rank="4" keyword="국비지원 코딩" percent="12%" />
                  <KeywordRow rank="5" keyword="다이어트 보조제" percent="11%" />
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>
  );
}

function CampaignStatRow({ name, clicks, db, rate, revenue }: any) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
      <div className="mb-2 sm:mb-0">
        <p className="text-sm font-semibold text-slate-900">{name}</p>
        <div className="flex gap-3 text-xs text-slate-500 mt-1">
          <span className="flex items-center gap-1"><Clock className="h-3 w-3" />클릭 {clicks}</span>
          <span className="flex items-center gap-1"><Database className="h-3 w-3" />DB {db}</span>
        </div>
      </div>
      <div className="text-left sm:text-right">
        <p className="text-sm font-bold text-blue-600">{revenue}</p>
        <p className="text-xs text-slate-500 mt-1">전환율 {rate}</p>
      </div>
    </div>
  )
}

function KeywordRow({ rank, keyword, percent }: any) {
  return (
    <div className="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
      <div className="flex items-center gap-3">
        <span className={`flex h-5 w-5 items-center justify-center rounded text-xs font-bold ${rank === '1' ? 'bg-blue-100 text-blue-700' : rank === '2' ? 'bg-slate-100 text-slate-700' : rank === '3' ? 'bg-orange-50 text-orange-700' : 'bg-transparent text-slate-400'}`}>
          {rank}
        </span>
        <span className="text-sm font-medium text-slate-700">{keyword}</span>
      </div>
      <span className="text-xs font-semibold text-slate-500">{percent}</span>
    </div>
  )
}
