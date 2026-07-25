import { Link } from 'react-router-dom';
import { handleSectionLink } from '../lib/navigation';

export function FinalCTA() {
  return (
    <section className="py-32 px-4 sm:px-6 lg:px-8 text-center relative overflow-hidden bg-emerald-600">
      <div className="absolute inset-0 bg-gradient-to-b from-transparent to-emerald-800/40 pointer-events-none"></div>
      <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-emerald-400/30 blur-[100px] rounded-full pointer-events-none"></div>

      <div className="relative z-10 max-w-3xl mx-auto">
        <h2 className="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">
          광고주는 성과를 만들고, <br />
          파트너는 수익을 만듭니다.
        </h2>
        <p className="text-lg text-emerald-100 mb-10">
          지금 바로 링크커넥트에서 완벽한 제휴마케팅을 시작하세요.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link to="/partner" className="w-full sm:w-auto px-8 py-4 bg-white hover:bg-slate-50 text-emerald-700 font-bold rounded-xl transition-colors shadow-xl">
            파트너로 시작하기
          </Link>
          <Link
            to="/"
            onClick={() => handleSectionLink('lc-inquiry')}
            className="w-full sm:w-auto px-8 py-4 bg-emerald-700 hover:bg-emerald-800 text-white font-medium rounded-xl border border-emerald-500 transition-colors shadow-sm"
          >
            광고주로 문의하기
          </Link>
        </div>
      </div>
    </section>
  );
}
