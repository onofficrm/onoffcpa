const fs = require('fs');

let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

// Update imports
content = content.replace(
  "CheckCircle2, Megaphone, Target, Briefcase, Zap, Star",
  "CheckCircle2, Megaphone, Target, Briefcase, Zap, Star, Sparkles, User, HelpCircle"
);

// Define the personalized section
const personalizedSection = `
        {/* Personalized Recommendations Section */}
        <section id="personalized-recommendations" className="bg-slate-900 rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-xl border border-slate-800">
          <div className="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
            <Sparkles size={200} className="text-cyan-400" />
          </div>

          <div className="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
              <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-cyan-500/20 text-cyan-400 text-xs font-bold mb-3 border border-cyan-500/30">
                <Sparkles size={14} /> 맞춤 추천
              </div>
              <h2 className="text-2xl md:text-3xl font-bold text-white mb-2">PTN-8291 파트너님에게 추천하는 이벤트</h2>
              <p className="text-slate-400 text-sm md:text-base max-w-xl">
                최근 유입 성과와 홍보 채널을 기준으로 참여하기 좋은 이벤트를 추천합니다.
              </p>
            </div>
            
            <div className="bg-slate-800/50 backdrop-blur-sm border border-slate-700 p-4 rounded-xl max-w-sm">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-cyan-500/20 rounded-full flex items-center justify-center shrink-0">
                  <User size={20} className="text-cyan-400" />
                </div>
                <div>
                  <p className="text-sm text-slate-300 leading-snug">
                    "최근 <span className="text-cyan-400 font-bold">블로그 채널</span>에서 접수 DB가 많이 발생하고 있어, 블로그 홍보가 가능한 <span className="text-emerald-400 font-bold">고승인율 캠페인</span>을 추천합니다."
                  </p>
                  <div className="flex flex-wrap gap-1.5 mt-3">
                    <span className="text-[10px] px-2 py-0.5 bg-slate-700 text-slate-300 rounded">#블로그추천</span>
                    <span className="text-[10px] px-2 py-0.5 bg-slate-700 text-slate-300 rounded">#고승인율</span>
                    <span className="text-[10px] px-2 py-0.5 bg-slate-700 text-slate-300 rounded">#단가상승</span>
                    <span className="text-[10px] px-2 py-0.5 bg-slate-700 text-slate-300 rounded">#마감임박</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-10">
            {/* Rec Card 1 */}
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 transition-colors shadow-lg shadow-black/20">
              <div className="flex justify-between items-start mb-3">
                <Badge type="단가 상승">단가 상승</Badge>
                <span className="text-xs font-bold text-rose-500 bg-rose-50 px-2 py-1 rounded">D-5</span>
              </div>
              <h3 className="text-lg font-bold text-slate-900 mb-1 group-hover:text-cyan-600 transition-colors">개인회생 상담 DB 단가 상승 이벤트</h3>
              <p className="text-xs text-slate-500 mb-4 pb-4 border-b border-slate-100 flex items-start gap-1.5">
                <CheckCircle2 size={14} className="text-emerald-500 shrink-0 mt-0.5" />
                <span className="leading-tight">최근 법률 카테고리 유입 성과가 좋습니다.</span>
              </p>
              <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100 mb-4 flex-1">
                <div className="text-xs text-slate-500 mb-2">적용 상품: <span className="font-bold text-slate-700">개인회생 상담 DB</span></div>
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-slate-400 line-through">30,000원</span>
                  <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">승인율 68%</span>
                </div>
                <div className="text-2xl font-black text-slate-900">40,000<span className="text-sm font-bold text-slate-500 ml-1">원</span></div>
              </div>
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-auto">
                <button className="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1 w-full sm:w-auto justify-center sm:justify-start">
                  <HelpCircle size={14} /> 왜 추천되었나요?
                </button>
                <button className="w-full sm:w-auto px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                  홍보 링크 만들기
                </button>
              </div>
            </div>

            {/* Rec Card 2 */}
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 transition-colors shadow-lg shadow-black/20">
              <div className="flex justify-between items-start mb-3">
                <Badge type="진행중">고승인율</Badge>
              </div>
              <h3 className="text-lg font-bold text-slate-900 mb-1 group-hover:text-cyan-600 transition-colors">자동차 렌트 상담 DB 안정형 캠페인</h3>
              <p className="text-xs text-slate-500 mb-4 pb-4 border-b border-slate-100 flex items-start gap-1.5">
                <CheckCircle2 size={14} className="text-emerald-500 shrink-0 mt-0.5" />
                <span className="leading-tight">승인율이 높아 초보 파트너에게 적합합니다.</span>
              </p>
              <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100 mb-4 flex-1">
                <div className="text-xs text-slate-500 mb-2">적용 상품: <span className="font-bold text-slate-700">장기렌트카 견적 신청</span></div>
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-slate-500">이벤트 단가</span>
                  <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">승인율 78%</span>
                </div>
                <div className="text-2xl font-black text-slate-900">25,000<span className="text-sm font-bold text-slate-500 ml-1">원</span></div>
              </div>
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-auto">
                <button className="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1 w-full sm:w-auto justify-center sm:justify-start">
                  <HelpCircle size={14} /> 왜 추천되었나요?
                </button>
                <button className="w-full sm:w-auto px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-bold transition-colors shadow-sm">
                  이벤트 보기
                </button>
              </div>
            </div>

            {/* Rec Card 3 */}
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 transition-colors shadow-lg shadow-black/20">
              <div className="flex justify-between items-start mb-3">
                <Badge type="마감임박">마감임박</Badge>
                <span className="text-xs font-bold text-rose-500 bg-rose-50 px-2 py-1 rounded">D-3</span>
              </div>
              <h3 className="text-lg font-bold text-slate-900 mb-1 group-hover:text-cyan-600 transition-colors">영어캠프 상담 DB 시즌 프로모션</h3>
              <p className="text-xs text-slate-500 mb-4 pb-4 border-b border-slate-100 flex items-start gap-1.5">
                <CheckCircle2 size={14} className="text-emerald-500 shrink-0 mt-0.5" />
                <span className="leading-tight">시즌 키워드 검색량이 증가 중입니다.</span>
              </p>
              <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100 mb-4 flex-1">
                <div className="text-xs text-slate-500 mb-2">적용 상품: <span className="font-bold text-slate-700">세부 영어캠프 상담 DB</span></div>
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-slate-500">이벤트 단가</span>
                </div>
                <div className="text-2xl font-black text-slate-900">35,000<span className="text-sm font-bold text-slate-500 ml-1">원</span></div>
              </div>
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-auto">
                <button className="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1 w-full sm:w-auto justify-center sm:justify-start">
                  <HelpCircle size={14} /> 왜 추천되었나요?
                </button>
                <button className="w-full sm:w-auto px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                  지금 참여하기
                </button>
              </div>
            </div>
          </div>
        </section>
`;

if (!content.includes('id="personalized-recommendations"')) {
  content = content.replace(
    '<div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 space-y-16">',
    '<div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 space-y-16">\n' + personalizedSection
  );
  fs.writeFileSync('src/pages/Events.tsx', content);
}
