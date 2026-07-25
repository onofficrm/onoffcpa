import { ArrowUpRight } from 'lucide-react';
import heroDashboardImage from '../assets/images/hero_dashboard_mockup_1782987484126.jpg';

export function Hero() {
  return (
    <section className="pt-32 pb-20 px-4 sm:px-6 lg:px-8 bg-slate-950">
      <div className="max-w-7xl mx-auto">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Left: Copy */}
          <div className="space-y-8">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-sm font-medium">
              <span className="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
              클릭을 수익으로, DB를 성과로 연결하는 제휴마케팅 플랫폼
            </div>
            
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight">
              트래픽이 있다면, <br />
              지금 바로 <span className="text-emerald-400">수익으로 연결</span>하세요
            </h1>
            
            <p className="text-lg text-slate-400 leading-relaxed max-w-xl">
              CPA DB 캠페인과 CPS 구매 캠페인을 한곳에서 확인하고, 
              실시간 성과와 정산 내역까지 링크커넥트에서 관리하세요.
            </p>
            
            <div className="flex flex-wrap items-center gap-4">
              <button className="px-8 py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl flex items-center gap-2 transition-colors">
                인기 CPA 상품 보기
                <ArrowUpRight className="w-5 h-5" />
              </button>
              <button className="px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-medium rounded-xl border border-white/10 transition-colors">
                CPS 상품 둘러보기
              </button>
              <button className="px-8 py-4 bg-transparent hover:bg-cyan-500/10 text-cyan-400 font-medium rounded-xl transition-colors">
                광고주 입점 문의
              </button>
            </div>
          </div>

          {/* Right: Dashboard Image */}
          <div className="relative">
            <div className="absolute -inset-1 bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 blur-2xl rounded-3xl"></div>
            <div className="relative rounded-2xl overflow-hidden shadow-2xl border border-white/10">
              <img 
                src={heroDashboardImage} 
                alt="LinkConnect Dashboard Preview" 
                className="w-full h-auto object-cover"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
