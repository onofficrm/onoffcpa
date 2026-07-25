const fs = require('fs');

let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

const startTag = '{/* Ranking Section */}';
const endTag = '{/* Advertiser Promo */}';
const startIndex = content.indexOf(startTag);
const endIndex = content.indexOf(endTag);

if (startIndex === -1 || endIndex === -1) {
  console.log("Could not find ranking section boundaries");
  process.exit(1);
}

const beforeRanking = content.substring(0, startIndex);
const afterRanking = content.substring(endIndex);

const rankingSection = `{/* Ranking Section */}
        <section id="ranking" className="bg-slate-900 rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-xl border border-slate-800">
          <div className="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
            <Trophy size={300} className="text-amber-400 rotate-12" />
          </div>

          <div className="relative z-10 mb-8 md:mb-10">
            <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-bold mb-3 border border-amber-500/30">
              <Trophy size={14} /> 랭킹 이벤트
            </div>
            <h2 className="text-2xl md:text-3xl font-bold text-white mb-2">이번 달 파트너 리워드 랭킹</h2>
            <p className="text-slate-400 text-sm md:text-base max-w-xl">
              승인 DB 기준 상위 파트너에게 추가 리워드가 지급됩니다.
            </p>
          </div>

          {/* Ranking Summary Cards */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 relative z-10">
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">현재 1위 승인 DB</div>
              <div className="text-2xl md:text-3xl font-black text-white">128<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-cyan-900/30 backdrop-blur-sm rounded-2xl p-5 border border-cyan-800 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-cyan-400 mb-1">내 현재 순위</div>
              <div className="text-2xl md:text-3xl font-black text-cyan-400">18<span className="text-sm font-bold text-cyan-600 ml-1">위</span></div>
            </div>
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">TOP 10까지 남은 DB</div>
              <div className="text-2xl md:text-3xl font-black text-white">7<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">내 예상 보너스</div>
              <div className="text-2xl md:text-3xl font-black text-white">0<span className="text-sm font-bold text-slate-500 ml-1">원</span></div>
              <div className="text-[10px] text-emerald-400 mt-1">TOP 10 진입 시 100,000원</div>
            </div>
          </div>

          <div className="grid grid-cols-1 xl:grid-cols-3 gap-8 relative z-10">
            <div className="xl:col-span-2 space-y-8">
              {/* Top 3 Podium Cards */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* 2nd Place */}
                <div className="bg-gradient-to-b from-slate-300/10 to-transparent p-1 rounded-2xl border border-slate-300/20 md:order-1 order-2">
                  <div className="bg-slate-900/80 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-12 h-12 bg-gradient-to-br from-slate-200 to-slate-400 rounded-full flex items-center justify-center text-slate-900 shadow-lg shadow-slate-300/20 mb-3 border-4 border-slate-900">
                      <span className="font-black text-lg">2</span>
                    </div>
                    <div className="text-lg font-bold text-white mb-1">PTN-54**</div>
                    <div className="text-sm text-slate-400 mb-4">승인 DB 96건</div>
                    <div className="mt-auto w-full bg-slate-300/10 border border-slate-300/20 rounded-lg p-3">
                      <div className="text-[10px] text-slate-400 font-bold mb-1">예상 리워드</div>
                      <div className="text-lg font-black text-white">500,000원</div>
                    </div>
                  </div>
                </div>

                {/* 1st Place */}
                <div className="bg-gradient-to-b from-amber-500/20 to-transparent p-1 rounded-2xl border border-amber-500/30 md:-mt-6 md:order-2 order-1 shadow-2xl shadow-amber-500/10">
                  <div className="bg-slate-900/90 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-16 h-16 bg-gradient-to-br from-amber-300 to-amber-500 rounded-full flex items-center justify-center text-slate-900 shadow-lg shadow-amber-500/30 mb-3 border-4 border-slate-900 relative">
                      <div className="absolute -top-3 text-amber-300"><Trophy size={20} fill="currentColor" /></div>
                      <span className="font-black text-2xl">1</span>
                    </div>
                    <div className="text-xl font-bold text-amber-400 mb-1">PTN-8291</div>
                    <div className="text-sm text-amber-500/80 font-bold mb-1">확정수익 3,840,000원</div>
                    <div className="text-sm text-slate-300 mb-4">승인 DB 128건</div>
                    <div className="mt-auto w-full bg-amber-500/10 border border-amber-500/30 rounded-lg p-3">
                      <div className="text-[10px] text-amber-500 font-bold mb-1">예상 리워드</div>
                      <div className="text-2xl font-black text-amber-400">1,000,000원</div>
                    </div>
                  </div>
                </div>

                {/* 3rd Place */}
                <div className="bg-gradient-to-b from-orange-600/20 to-transparent p-1 rounded-2xl border border-orange-600/30 md:order-3 order-3 md:mt-4">
                  <div className="bg-slate-900/80 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-700 rounded-full flex items-center justify-center text-white shadow-lg shadow-orange-600/20 mb-3 border-4 border-slate-900">
                      <span className="font-black text-lg">3</span>
                    </div>
                    <div className="text-lg font-bold text-white mb-1">PTN-30**</div>
                    <div className="text-sm text-slate-400 mb-4">승인 DB 74건</div>
                    <div className="mt-auto w-full bg-orange-600/10 border border-orange-600/20 rounded-lg p-3">
                      <div className="text-[10px] text-orange-400 font-bold mb-1">예상 리워드</div>
                      <div className="text-lg font-black text-orange-400">300,000원</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* My Rank Card */}
              <div className="bg-cyan-950/30 border border-cyan-900 rounded-2xl p-6 shadow-inner">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                  <div>
                    <h3 className="text-lg font-bold text-cyan-400 mb-4 flex items-center gap-2"><Target size={18}/> 내 현재 순위</h3>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-6">
                      <div>
                        <div className="text-xs text-slate-400 mb-1">현재 순위</div>
                        <div className="text-xl font-bold text-white">18위</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">승인 DB</div>
                        <div className="text-xl font-bold text-white">42건</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">10위까지 남은 DB</div>
                        <div className="text-xl font-bold text-rose-400">7건</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">현재 확정수익</div>
                        <div className="text-xl font-bold text-white">1,260,000<span className="text-sm text-slate-400 ml-0.5">원</span></div>
                      </div>
                    </div>
                  </div>
                  <div className="w-full md:w-auto shrink-0 flex flex-col gap-2">
                    <button className="w-full px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                      순위 올리기 좋은 캠페인
                    </button>
                    <button className="w-full px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-cyan-900 rounded-xl text-sm font-bold transition-colors">
                      이벤트 상품 홍보하기
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div className="xl:col-span-1 space-y-6">
              {/* TOP 10 List */}
              <div className="bg-slate-800/40 border border-slate-700 rounded-2xl overflow-hidden">
                <div className="p-4 border-b border-slate-700 bg-slate-800/60">
                  <h3 className="text-sm font-bold text-white">TOP 4 ~ 10 순위</h3>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm text-left text-slate-300">
                    <thead className="text-xs text-slate-400 bg-slate-800/40 uppercase">
                      <tr>
                        <th scope="col" className="px-4 py-3">순위</th>
                        <th scope="col" className="px-4 py-3">파트너</th>
                        <th scope="col" className="px-4 py-3 text-right">승인 DB</th>
                        <th scope="col" className="px-4 py-3 text-right">리워드</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-700/50">
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">4</td>
                        <td className="px-4 py-3">PTN-91**</td>
                        <td className="px-4 py-3 text-right font-medium">68건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">5</td>
                        <td className="px-4 py-3">PTN-12**</td>
                        <td className="px-4 py-3 text-right font-medium">61건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">6</td>
                        <td className="px-4 py-3">PTN-84**</td>
                        <td className="px-4 py-3 text-right font-medium">55건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">7</td>
                        <td className="px-4 py-3">PTN-33**</td>
                        <td className="px-4 py-3 text-right font-medium">52건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">8</td>
                        <td className="px-4 py-3">PTN-76**</td>
                        <td className="px-4 py-3 text-right font-medium">51건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">9</td>
                        <td className="px-4 py-3">PTN-21**</td>
                        <td className="px-4 py-3 text-right font-medium">49건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">10</td>
                        <td className="px-4 py-3">PTN-65**</td>
                        <td className="px-4 py-3 text-right font-medium">49건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Reward Structure */}
              <div className="bg-slate-800/40 border border-slate-700 rounded-2xl p-5">
                <h3 className="text-sm font-bold text-white mb-3">리워드 지급 기준</h3>
                <ul className="space-y-2 text-sm">
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-amber-400 font-bold">1위</span>
                    <span className="text-white font-bold">1,000,000원</span>
                  </li>
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-slate-300 font-bold">2위</span>
                    <span className="text-white font-bold">500,000원</span>
                  </li>
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-orange-400 font-bold">3위</span>
                    <span className="text-white font-bold">300,000원</span>
                  </li>
                  <li className="flex justify-between items-center pt-1">
                    <span className="text-cyan-400 font-bold">4~10위</span>
                    <span className="text-white font-bold">100,000원</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div className="mt-8 border-t border-slate-800 pt-5 relative z-10">
            <h4 className="text-xs font-bold text-slate-400 flex items-center gap-1.5 mb-2"><AlertCircle size={14} /> 랭킹 기준 안내</h4>
            <ul className="text-[11px] text-slate-500 space-y-1 pl-5 list-disc marker:text-slate-600">
              <li>승인 완료 DB 기준으로 순위가 산정됩니다.</li>
              <li>취소/무효 DB는 랭킹에서 제외됩니다.</li>
              <li>동일 건수일 경우 확정수익이 높은 파트너가 우선됩니다.</li>
            </ul>
          </div>
        </section>
        
        `;

fs.writeFileSync('src/pages/Events.tsx', beforeRanking + rankingSection + afterRanking);
