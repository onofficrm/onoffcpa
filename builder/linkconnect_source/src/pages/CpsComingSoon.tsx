import { ArrowLeft, Clock, ShoppingBag } from 'lucide-react';
import { Link } from 'react-router-dom';

export function CpsComingSoon() {
  return (
    <main className="pt-32 pb-24 px-4 sm:px-6 lg:px-8 min-h-[70vh] flex items-center">
      <div className="max-w-2xl mx-auto text-center w-full">
        <div className="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 mb-8">
          <ShoppingBag className="w-10 h-10" />
        </div>

        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-300 text-sm font-medium mb-6">
          <Clock className="w-4 h-4" />
          서비스 준비중
        </div>

        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4 tracking-tight">
          CPS 상품은 곧 오픈됩니다
        </h1>
        <p className="text-lg text-slate-400 leading-relaxed mb-10">
          구매·결제 기반 수익형 CPS 캠페인은 현재 준비 중입니다.
          <br className="hidden sm:inline" />
          지금은 CPA DB 캠페인을 이용해 주세요.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
          <Link
            to="/cpa-list"
            className="w-full sm:w-auto px-8 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl transition-colors"
          >
            CPA 상품 보기
          </Link>
          <Link
            to="/"
            className="w-full sm:w-auto px-8 py-3.5 bg-white/5 hover:bg-white/10 text-white font-medium rounded-xl border border-white/10 transition-colors inline-flex items-center justify-center gap-2"
          >
            <ArrowLeft className="w-4 h-4" />
            홈으로
          </Link>
        </div>
      </div>
    </main>
  );
}
