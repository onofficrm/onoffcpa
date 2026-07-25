import { ArrowRight, Clock, ShoppingBag, TrendingUp } from 'lucide-react';
import { cpsItems } from '../data';
import { cn } from '../lib/utils';

const filters = ['전체', '고수익률', '신규', '인기', '쇼핑몰', '건강식품', '뷰티', '교육상품', '디지털상품'];

export function CPSList() {
  return (
    <section id="cps" className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900 border-t border-slate-800 relative">
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-cyan-500/5 blur-[120px] rounded-full pointer-events-none"></div>
      
      <div className="max-w-7xl mx-auto relative z-10">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
            구매가 발생하면 수익이 쌓이는 <span className="text-cyan-400">CPS 상품</span>
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto">
            쇼핑몰, 건강식품, 뷰티, 교육상품 등 구매 또는 결제가 발생하면 판매금액 기준으로 수익이 지급됩니다.
          </p>
        </div>

        <div className="flex flex-wrap justify-center gap-2 mb-10">
          {filters.map((filter, i) => (
            <button 
              key={filter}
              className={cn(
                "px-4 py-2 rounded-lg text-sm font-medium transition-colors border shadow-sm",
                i === 0 
                  ? "bg-cyan-500 text-slate-900 border-cyan-500" 
                  : "bg-slate-800 border-slate-700 text-slate-300 hover:text-white hover:border-slate-500 hover:bg-slate-700"
              )}
            >
              {filter}
            </button>
          ))}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          {cpsItems.map((item) => (
            <div key={item.id} className="bg-slate-800/50 border border-slate-700 rounded-2xl overflow-hidden hover:border-cyan-500/50 hover:bg-slate-800 transition-all group flex flex-col shadow-md">
              {item.imageUrl && (
                <div className="aspect-square overflow-hidden relative group">
                  <img 
                    src={item.imageUrl} 
                    alt={item.brand} 
                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                  <div className="absolute top-3 left-3 right-3 flex justify-between items-start">
                    <span className="text-xs font-medium px-2.5 py-1 rounded-md bg-slate-900/80 backdrop-blur-sm text-slate-200 border border-slate-700 shadow-sm">
                      {item.category}
                    </span>
                    {item.badge && (
                      <span className={cn(
                        "text-xs font-bold px-2.5 py-1 rounded-md shadow-sm backdrop-blur-sm border",
                        item.badge === '고수익률' || item.badge === '수익률 상향' 
                          ? "bg-yellow-500/20 text-yellow-300 border-yellow-500/30" 
                          : item.badge === '인기' 
                          ? "bg-emerald-500/20 text-emerald-300 border-emerald-500/30" 
                          : "bg-slate-800/80 text-white border-slate-600"
                      )}>
                        {item.badge}
                      </span>
                    )}
                  </div>
                </div>
              )}
              <div className="p-5 flex-1 flex flex-col">
                <h3 className="text-lg font-bold text-white mb-2 line-clamp-1">{item.brand}</h3>
                <p className="text-sm text-slate-400 mb-6 line-clamp-2 min-h-[40px]">{item.description}</p>

                <div className="space-y-3 mb-6 bg-slate-900/50 p-3 rounded-xl border border-slate-700/50 mt-auto">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-400 flex items-center gap-1.5"><ShoppingBag className="w-4 h-4"/>수익 조건</span>
                    <span className="text-slate-200 font-medium">{item.condition}</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-400 flex items-center gap-1.5"><TrendingUp className="w-4 h-4"/>수익률</span>
                    <span className="text-cyan-400 font-bold text-base">{item.rewardRate}</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-400 flex items-center gap-1.5"><Clock className="w-4 h-4"/>쿠키 기간</span>
                    <span className="text-slate-300 font-medium">{item.cookieDays}일</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors border border-slate-600">
                    상세보기
                  </button>
                  <button className="w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-slate-900 text-sm font-bold rounded-lg transition-colors shadow-md shadow-cyan-500/20">
                    홍보하기
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="text-center">
          <button className="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-cyan-500/30 text-cyan-400 hover:bg-cyan-500/10 transition-colors font-medium bg-slate-800 shadow-sm">
            CPS 전체 상품 보기
            <ArrowRight className="w-4 h-4" />
          </button>
        </div>
      </div>
    </section>
  );
}
