const fs = require('fs');

let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

const startTag = '{/* Advertiser Promo */}';
const endTag = '{/* Footer Info Area */}';
const startIndex = content.indexOf(startTag);
const endIndex = content.indexOf(endTag);

if (startIndex === -1 || endIndex === -1) {
  console.log("Could not find boundaries");
  process.exit(1);
}

const beforePromo = content.substring(0, startIndex);
const afterPromo = content.substring(endIndex);

const newPromoSection = `{/* Advertiser Promo */}
        <section id="advertiser-promo" className="bg-gradient-to-b from-slate-900 to-slate-950 rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-2xl border border-slate-800">
          {/* Background Decor */}
          <div className="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
            <Target size={250} className="text-cyan-400 rotate-12" />
          </div>

          <div className="relative z-10 mb-8 md:mb-12">
            <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-cyan-500/10 text-cyan-400 text-xs font-bold mb-4 border border-cyan-500/20">
              광고주 전용
            </div>
            <h2 className="text-2xl md:text-3xl font-black text-white mb-3 tracking-tight">내 CPA 상품도 프로모션에 노출하고 싶으신가요?</h2>
            <p className="text-slate-400 text-sm md:text-base max-w-xl">
              단가 상승, 추천 캠페인 노출, 파트너 모집 이벤트로 더 많은 디비를 확보해보세요.
            </p>
          </div>

          {/* Cards Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 relative z-10 mb-12">
            {/* Card 1 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-cyan-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center mb-4 border border-cyan-500/20 group-hover:scale-110 transition-transform">
                <TrendingUp size={20} className="text-cyan-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">단가 상승 프로모션</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                일정 기간 파트너 단가를 높여 더 많은 파트너 참여를 유도합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 빠르게 DB를 확보하고 싶은 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-cyan-400 font-bold block mb-0.5">혜택</span> 이벤트 페이지 + 추천 영역 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors">
                신청하기
              </button>
            </div>

            {/* Card 2 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-emerald-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-4 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                <Star size={20} className="text-emerald-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">신규 캠페인 추천 노출</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                새로 등록한 CPA 상품을 파트너에게 추천 캠페인으로 노출합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 신규 광고상품을 빠르게 알리고 싶은 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-emerald-400 font-bold block mb-0.5">혜택</span> 메인 추천 캠페인 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                상담 신청
              </button>
            </div>

            {/* Card 3 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-amber-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center mb-4 border border-amber-500/20 group-hover:scale-110 transition-transform">
                <Trophy size={20} className="text-amber-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">파트너 랭킹 이벤트</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                특정 상품 기준으로 파트너 랭킹 이벤트를 운영하여 홍보 경쟁을 유도합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 대량 디비가 필요한 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-amber-400 font-bold block mb-0.5">혜택</span> 랭킹 이벤트 페이지 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                이벤트 만들기 문의
              </button>
            </div>

            {/* Card 4 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-purple-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center mb-4 border border-purple-500/20 group-hover:scale-110 transition-transform">
                <Gift size={20} className="text-purple-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">광고비 충전 보너스</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                일정 금액 이상 광고비 충전 시 추가 노출 혜택을 제공합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 장기 캠페인을 운영하는 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-purple-400 font-bold block mb-0.5">혜택</span> 추천 상품 우선 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                광고비 충전 문의
              </button>
            </div>
          </div>

          {/* Process Flow */}
          <div className="relative z-10 bg-slate-950/50 rounded-2xl p-6 md:p-8 border border-slate-800 mb-10">
            <h3 className="text-sm font-bold text-slate-300 mb-6 text-center">프로모션 신청 및 진행 과정</h3>
            <div className="flex flex-col md:flex-row justify-between items-center gap-4 relative">
              <div className="hidden md:block absolute top-1/2 left-0 w-full h-[1px] bg-slate-800 -translate-y-1/2"></div>
              
              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">1</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">광고상품 선택</span>
              </div>
              
              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">2</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">유형 선택</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">3</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">관리자 검토</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">4</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">이벤트 노출</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">5</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">파트너 홍보 시작</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-emerald-900 group-hover:border-emerald-500 group-hover:text-emerald-400 transition-colors z-10">6</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">디비 성과 확인</span>
              </div>
            </div>
          </div>

          {/* Bottom CTA Area */}
          <div className="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8 bg-cyan-900/20 rounded-2xl p-6 md:p-8 border border-cyan-500/20">
            <div className="flex-1">
              <h3 className="text-xl md:text-2xl font-bold text-white mb-4">광고상품 성과를 더 빠르게 키우고 싶다면<br className="hidden md:block"/> 프로모션을 신청하세요.</h3>
              
              <div className="flex flex-wrap gap-4 text-sm text-slate-300">
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">프로모션 적용 상품</span>
                  <span className="font-black text-white text-lg">38<span className="text-sm text-slate-400 font-normal ml-0.5">개</span></span>
                </div>
                <div className="w-[1px] h-10 bg-slate-700 hidden sm:block"></div>
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">참여 파트너</span>
                  <span className="font-black text-white text-lg">1,200<span className="text-sm text-slate-400 font-normal ml-0.5">명+</span></span>
                </div>
                <div className="w-[1px] h-10 bg-slate-700 hidden sm:block"></div>
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">평균 DB 증가율</span>
                  <span className="font-black text-emerald-400 text-lg">32<span className="text-sm font-normal ml-0.5">%</span></span>
                </div>
              </div>
            </div>

            <div className="w-full lg:w-auto flex flex-col sm:flex-row gap-3">
              <Link to="/advertiser" className="flex items-center justify-center px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                광고주센터로 이동
              </Link>
              <button className="flex items-center justify-center px-6 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors shadow-lg shadow-cyan-900/50">
                프로모션 신청하기
              </button>
            </div>
          </div>
        </section>

        `;

fs.writeFileSync('src/pages/Events.tsx', beforePromo + newPromoSection + afterPromo);
