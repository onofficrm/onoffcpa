const fs = require('fs');

let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

const progressSection = `
        {/* My Event Progress Section */}
        <section id="event-progress" className="bg-white rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-sm border border-slate-200">
          <div className="relative z-10 mb-8 md:mb-10">
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">내 이벤트 참여 현황</h2>
            <p className="text-slate-500 text-sm md:text-base max-w-xl">
              현재 참여 중인 이벤트의 달성률과 예상 보너스를 확인하세요.
            </p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 md:mb-10">
            <div className="bg-slate-50 rounded-2xl p-5 border border-slate-100 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-500 mb-1">참여 중 이벤트</div>
              <div className="text-3xl font-black text-slate-900">3<span className="text-sm font-bold text-slate-500 ml-1">개</span></div>
            </div>
            <div className="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-emerald-600 mb-1">달성 완료 이벤트</div>
              <div className="text-3xl font-black text-emerald-700">1<span className="text-sm font-bold text-emerald-600 ml-1">개</span></div>
            </div>
            <div className="bg-slate-50 rounded-2xl p-5 border border-slate-100 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-500 mb-1">목표까지 남은 DB</div>
              <div className="text-3xl font-black text-slate-900">7<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-cyan-50 rounded-2xl p-5 border border-cyan-100 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-cyan-600 mb-1">예상 추가 보너스</div>
              <div className="text-3xl font-black text-cyan-700">320,000<span className="text-sm font-bold text-cyan-600 ml-1">원</span></div>
            </div>
          </div>

          <div className="space-y-4 relative z-10">
            {/* Progress Card 1 */}
            <div className="bg-white rounded-2xl p-5 md:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6 group hover:border-cyan-300 transition-colors">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="진행중">참여중</Badge>
                  <span className="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded flex items-center gap-1"><Calendar size={12} /> ~ 2026.10.31</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">첫 승인 5건 달성 보너스</h3>
                <p className="text-sm text-slate-600 mb-1">승인 DB 5건 달성 시 100,000원 보너스</p>
                <div className="text-xs font-bold text-cyan-600 flex items-center gap-1"><CheckCircle2 size={14} /> 목표까지 승인 DB 2건 남았습니다!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-cyan-600 text-sm ml-1">60%</span></div>
                  <div className="text-sm font-bold text-slate-800">3 / 5건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full" style={{ width: '60%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                  <span className="text-xs font-bold text-slate-500">예상 보너스</span>
                  <span className="text-lg font-black text-cyan-600">100,000원</span>
                </div>
              </div>
              
              <div className="md:w-36 shrink-0 flex flex-col justify-end mt-4 md:mt-0">
                <button className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                  추천 상품 보기
                </button>
              </div>
            </div>

            {/* Progress Card 2 */}
            <div className="bg-white rounded-2xl p-5 md:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6 group hover:border-cyan-300 transition-colors">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="진행중">참여중</Badge>
                  <span className="text-xs font-bold text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded flex items-center gap-1"><Clock size={12} /> 마감 D-2</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">개인회생 단가 상승 이벤트</h3>
                <p className="text-sm text-slate-600 mb-1">승인 DB 10건 이상 시 추가 단가 적용</p>
                <div className="text-xs font-bold text-emerald-600 flex items-center gap-1"><CheckCircle2 size={14} /> 2건만 더 달성하면 추가 수익 발생!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-cyan-600 text-sm ml-1">80%</span></div>
                  <div className="text-sm font-bold text-slate-800">8 / 10건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full" style={{ width: '80%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                  <span className="text-xs font-bold text-slate-500">예상 추가 수익</span>
                  <span className="text-lg font-black text-emerald-600">80,000원</span>
                </div>
              </div>
              
              <div className="md:w-36 shrink-0 flex flex-col justify-end mt-4 md:mt-0">
                <button className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                  남은 조건 달성하러 가기
                </button>
              </div>
            </div>

            {/* Progress Card 3 */}
            <div className="bg-emerald-50/50 rounded-2xl p-5 md:p-6 border border-emerald-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="단가 상승">달성완료</Badge>
                  <span className="text-xs font-bold text-slate-500 bg-white px-2 py-1 rounded border border-slate-100 flex items-center gap-1"><Calendar size={12} /> 2026.10.05 달성</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">신규 파트너 첫 승인 보너스</h3>
                <p className="text-sm text-slate-600 mb-1">첫 승인 DB 1건 발생 완료</p>
                <div className="text-xs font-bold text-emerald-600 flex items-center gap-1"><CheckCircle2 size={14} /> 목표를 성공적으로 달성했습니다!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-white p-4 rounded-xl border border-emerald-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-emerald-600 text-sm ml-1">100%</span></div>
                  <div className="text-sm font-bold text-emerald-700">1 / 1건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-emerald-400 to-emerald-500 h-2.5 rounded-full" style={{ width: '100%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-100 mt-2">
                  <span className="text-xs font-bold text-slate-500">지급 예정 보너스</span>
                  <span className="text-lg font-black text-purple-600 flex items-center gap-1"><Gift size={16}/> 50,000원</span>
                </div>
              </div>
              
              <div className="md:w-36 shrink-0 flex flex-col justify-end mt-4 md:mt-0">
                <button className="w-full py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm font-bold transition-colors shadow-sm">
                  리워드 내역 보기
                </button>
              </div>
            </div>
          </div>

          <div className="mt-8 bg-slate-50 rounded-xl p-5 border border-slate-200 shadow-sm">
            <h4 className="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2"><AlertCircle size={16} className="text-slate-400"/> 진행률 기준 안내</h4>
            <ul className="text-xs text-slate-600 space-y-1.5 pl-6 list-disc marker:text-slate-400">
              <li>승인 완료된 DB만 이벤트 실적에 반영됩니다.</li>
              <li>취소/무효 처리된 DB는 진행률에서 제외됩니다.</li>
              <li>이벤트 기간 내 발생한 성과만 인정됩니다.</li>
              <li>보너스는 관리자 검수 후 정산에 반영됩니다.</li>
            </ul>
          </div>
        </section>
`;

if (!content.includes('id="event-progress"')) {
  content = content.replace(
    '<section id="events-list">',
    progressSection + '\n        <section id="events-list">'
  );
  fs.writeFileSync('src/pages/Events.tsx', content);
}
