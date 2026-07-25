import { ArrowRight, MessageSquare } from "lucide-react";

export function CtaSection() {
  return (
    <section className="py-24 bg-slate-50 relative overflow-hidden">
      {/* Decorative blobs */}
      <div className="absolute top-0 right-0 -mr-20 -mt-20 h-64 w-64 rounded-full bg-teal-100/50 blur-3xl"></div>
      <div className="absolute bottom-0 left-0 -ml-20 -mb-20 h-64 w-64 rounded-full bg-blue-100/50 blur-3xl"></div>

      <div className="relative z-10 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
        <h2 className="text-4xl font-bold text-slate-900 mb-6 tracking-tight">
          성과형 마케팅, <br className="sm:hidden" />
          이제 <span className="text-blue-700">링크커넥트</span>에서 시작하세요
        </h2>
        <p className="text-lg text-slate-500 mb-10 max-w-2xl mx-auto">
          복잡한 절차 없이 간편하게 가입하고, 최적화된 플랫폼에서 성과를 확인하세요.
          링크커넥트가 여러분의 비즈니스 성장을 지원합니다.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
          <button className="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-8 py-4 text-lg font-semibold text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-blue-800 hover:-translate-y-0.5">
            파트너 가입하기
            <ArrowRight className="h-5 w-5" />
          </button>
          <button className="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-8 py-4 text-lg font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:-translate-y-0.5">
            <MessageSquare className="h-5 w-5 text-slate-400" />
            광고주 문의하기
          </button>
        </div>
      </div>
    </section>
  );
}
