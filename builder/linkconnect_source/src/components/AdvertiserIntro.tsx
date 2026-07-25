import { ArrowUpRight, CreditCard, Users } from 'lucide-react';

export function AdvertiserIntro() {
  return (
    <section id="lc-inquiry" className="py-24 bg-slate-50 border-b border-slate-200 overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-center flex-col-reverse lg:flex-row">
          
          <div className="relative order-2 lg:order-1">
            <div className="absolute inset-0 bg-cyan-100 blur-[100px] rounded-full pointer-events-none"></div>
            <div className="relative bg-white border border-slate-200 rounded-2xl p-6 shadow-xl">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-10 h-10 rounded-lg bg-cyan-50 flex items-center justify-center border border-cyan-100">
                  <CreditCard className="w-5 h-5 text-cyan-600" />
                </div>
                <div>
                  <div className="text-sm text-slate-500">잔여 광고비 (캐시)</div>
                  <div className="text-xl font-bold text-slate-900">12,500,000 원</div>
                </div>
              </div>

              <div className="space-y-3">
                <div className="text-sm font-medium text-slate-500 mb-2">실시간 DB 인입 현황</div>
                {[
                  { name: '개인회생 상담 (홍길동)', time: '방금 전', status: '대기', source: '네이버 블로그' },
                  { name: '보험 비교 (김철수)', time: '5분 전', status: '승인', source: '유튜브' },
                  { name: '다이어트 (이영희)', time: '12분 전', status: '승인', source: '인스타그램' },
                  { name: '신차 렌트 (박민수)', time: '28분 전', status: '반려', source: '페이스북' },
                ].map((db, i) => (
                  <div key={i} className="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <div>
                      <div className="text-sm font-medium text-slate-900 mb-1">{db.name}</div>
                      <div className="text-xs text-slate-500 flex items-center gap-2">
                        <Users className="w-3 h-3" /> {db.source}
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-xs text-slate-400 mb-1">{db.time}</div>
                      <span className={`text-xs font-bold px-2 py-0.5 rounded border ${
                        db.status === '승인' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 
                        db.status === '대기' ? 'text-amber-600 bg-amber-50 border-amber-100' : 
                        'text-red-600 bg-red-50 border-red-100'
                      }`}>
                        {db.status}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div className="space-y-8 order-1 lg:order-2">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                광고주는 DB와 광고비를 <br />
                <span className="text-cyan-600">투명하게 관리</span>합니다
              </h2>
              <p className="text-slate-600 text-lg">
                허수 DB는 필터링하고 진성 DB만 정산하세요. <br/>
                광고 성과와 단가를 실시간으로 모니터링할 수 있습니다.
              </p>
            </div>
            
            <ul className="space-y-4">
              {['실시간 DB 확인 및 다운로드', '간편한 광고비 충전/차감 내역', '캠페인별 단가 및 ROI 분석', '매체별/파트너별 유입 품질 확인'].map((item, i) => (
                <li key={i} className="flex items-center gap-3 text-slate-700 font-medium">
                  <div className="w-6 h-6 rounded-full bg-cyan-50 flex items-center justify-center border border-cyan-100">
                    <ArrowUpRight className="w-4 h-4 text-cyan-600" />
                  </div>
                  {item}
                </li>
              ))}
            </ul>
          </div>

        </div>
      </div>
    </section>
  );
}
