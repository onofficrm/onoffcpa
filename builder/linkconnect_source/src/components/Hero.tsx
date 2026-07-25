import { ArrowUpRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { handleSectionLink } from '../lib/navigation';

const heroDashboardImage = `${import.meta.env.BASE_URL}hero_dashboard_mockup.png`;

export function Hero() {
  return (
    <section className="relative min-h-[100svh] flex items-end overflow-hidden bg-slate-950">
      <img
        src={heroDashboardImage}
        alt=""
        aria-hidden="true"
        className="absolute inset-0 h-full w-full object-cover object-center scale-105 animate-[hero-zoom_18s_ease-out_forwards]"
      />
      <div className="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/85 to-slate-950/35" />
      <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent" />

      <div className="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-16 md:pb-24">
        <div className="max-w-2xl space-y-7 animate-[hero-rise_0.9s_ease-out_both]">
          <p className="text-sm font-semibold tracking-[0.18em] uppercase text-emerald-400/90">
            온오프CPA
          </p>

          <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-[1.12] tracking-tight">
            트래픽이 있다면,
            <br />
            지금 바로 <span className="text-emerald-400">수익으로 연결</span>하세요
          </h1>

          <p className="text-lg text-slate-300 leading-relaxed max-w-xl">
            CPA DB 캠페인을 한곳에서 확인하고, 실시간 성과와 정산까지 관리하세요.
          </p>

          <div className="flex flex-wrap items-center gap-3 pt-1">
            <Link
              to="/cpa-list"
              className="px-8 py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl inline-flex items-center gap-2 transition-colors duration-300"
            >
              인기 CPA 상품 보기
              <ArrowUpRight className="w-5 h-5" />
            </Link>
            <Link
              to="/"
              onClick={() => handleSectionLink('lc-inquiry')}
              className="px-8 py-4 text-cyan-300 hover:text-cyan-200 font-medium rounded-xl border border-white/15 hover:border-cyan-400/40 transition-colors duration-300"
            >
              광고주 입점 문의
            </Link>
          </div>
        </div>
      </div>

      <style>{`
        @keyframes hero-zoom {
          from { transform: scale(1.08); }
          to { transform: scale(1); }
        }
        @keyframes hero-rise {
          from { opacity: 0; transform: translateY(18px); }
          to { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </section>
  );
}
