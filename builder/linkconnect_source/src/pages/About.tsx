import {
  ArrowUpRight,
  BadgeDollarSign,
  BarChart3,
  Handshake,
  Link2,
  ShieldCheck,
  Sparkles,
  TrendingUp,
  Users,
  Zap,
} from 'lucide-react';
import { Link } from 'react-router-dom';

const asset = (path: string) => `${import.meta.env.BASE_URL}${path}`;

const images = {
  hero: asset('about/about_hero.png'),
  ecosystem: asset('about/about_ecosystem.png'),
  dashboard: asset('hero_dashboard_mockup.png'),
  partner: asset('webtoon/ep6-p1.png'),
  tracking: asset('webtoon/ep4-p2.png'),
  settlement: asset('webtoon/ep5-p2.png'),
};

const coreValues = [
  {
    icon: <Sparkles className="w-6 h-6 text-emerald-500" />,
    image: images.partner,
    imageAlt: '파트너 수익 창출',
    title: '누구나 쉽고 빠르게, 더 많은 수익 창출',
    desc: '복잡한 과정 없이 캠페인을 선택하고 홍보할 수 있는 환경을 제공합니다. 검증된 노하우로 마케터의 노력 이상의 수익을 빠르게 가져갈 수 있도록 지원합니다.',
  },
  {
    icon: <ShieldCheck className="w-6 h-6 text-cyan-500" />,
    image: images.dashboard,
    imageAlt: '실시간 성과 대시보드',
    title: '투명하고 정교한 실시간 트래킹 & 어뷰징 관리',
    desc: '링크 클릭부터 CPA/CPS 전환까지 모든 데이터를 실시간 대시보드로 공개합니다. 24시간 모니터링으로 허위 실적을 차단해 깨끗한 생태계를 만듭니다.',
  },
  {
    icon: <BadgeDollarSign className="w-6 h-6 text-amber-500" />,
    image: images.settlement,
    imageAlt: '광고주와 파트너 상생',
    title: '지연 없는 정산과 신뢰 기반의 상생 파트너십',
    desc: '마케터의 수익이 지연 없이 보상받도록 신속한 정산을 약속합니다. 광고주에게는 ROI, 마케터에게는 업계 최고 수준의 커미션을 보장합니다.',
  },
];

const highlights = [
  { value: '300억+', label: '연간 견인 매출', sub: '자체 운영 노하우', icon: <TrendingUp className="w-5 h-5" /> },
  { value: 'CPA/CPS', label: '성과형 네트워크', sub: 'CPS · CPA 전문', icon: <Link2 className="w-5 h-5" /> },
  { value: '실시간', label: '성과 추적', sub: '투명한 데이터', icon: <Zap className="w-5 h-5" /> },
  { value: 'Win-Win', label: '상생 파트너십', sub: '광고주 · 파트너', icon: <Handshake className="w-5 h-5" /> },
];

const introPoints = [
  {
    title: '성장하는 제휴 마케팅 시장',
    text: '해외는 수십 조원 규모, 국내는 무궁무진한 잠재력. 링크커넥트는 연간 300억 원 이상의 견인 매출 노하우로 새로운 기준을 제시합니다.',
  },
  {
    title: '광고주와 파트너를 잇는 네트워크',
    text: '비즈니스 성장을 원하는 광고주와, 영향력을 수익으로 전환하고 싶은 파트너(마케터·인플루언서·블로거)를 효율적으로 연결합니다.',
  },
  {
    title: '모두가 이기는 Win-Win',
    text: 'CPS/CPA 전문 네트워크로 광고주와 마케터 모두 확실한 성과와 수익을 만들 수 있도록 함께합니다.',
  },
];

export function About() {
  return (
    <main>
      {/* Hero */}
      <section className="pt-28 pb-16 md:pt-32 md:pb-24 px-4 sm:px-6 lg:px-8 bg-slate-950 relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-900/20 via-slate-950 to-slate-950" />
        <div className="absolute top-1/4 -right-32 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl" />
        <div className="absolute bottom-0 -left-32 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl" />

        <div className="max-w-7xl mx-auto relative">
          <div className="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
            <div className="text-center lg:text-left">
              <p className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-cyan-400 text-sm font-medium mb-6">
                <Link2 className="w-4 h-4" />
                About LinkConnect
              </p>
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6">
                가치를 연결하고,
                <br />
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
                  성과로 증명
                </span>
                하다.
              </h1>
              <p className="text-lg text-slate-400 leading-relaxed max-w-xl mx-auto lg:mx-0">
                대한민국 성과 중심 제휴 마케팅 플랫폼, 링크커넥트(LinkConnect)입니다.
              </p>
            </div>

            <div className="relative">
              <div className="absolute -inset-3 bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 rounded-2xl blur-xl" />
              <img
                src={images.hero}
                alt="링크커넥트 제휴 마케팅 플랫폼"
                className="relative w-full rounded-2xl border border-white/10 shadow-2xl object-cover aspect-video"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Intro — 이미지 + 핵심 포인트 */}
      <section className="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
          <div className="relative order-2 lg:order-1">
            <img
              src={images.ecosystem}
              alt="제휴 마케팅 생태계 — 광고주, 파트너, 고객 연결"
              className="w-full rounded-2xl border border-slate-100 shadow-lg object-cover aspect-video"
            />
            <div className="absolute -bottom-4 -right-4 hidden md:block bg-emerald-500 text-white text-sm font-bold px-4 py-2 rounded-xl shadow-lg">
              CPS · CPA 전문
            </div>
          </div>

          <div className="space-y-6 order-1 lg:order-2">
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 leading-snug">
              연결을 수익으로 바꾸는
              <br />
              <span className="text-emerald-600">성과형 제휴 마케팅</span>
            </h2>
            <div className="space-y-5">
              {introPoints.map((point, i) => (
                <article key={point.title} className="flex gap-4">
                  <div className="shrink-0 w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 font-bold text-sm">
                    {i + 1}
                  </div>
                  <div>
                    <h3 className="font-bold text-slate-900 mb-1">{point.title}</h3>
                    <p className="text-slate-600 text-sm md:text-base leading-relaxed">{point.text}</p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Stats strip */}
      <section className="py-14 px-4 sm:px-6 lg:px-8 bg-slate-50 border-y border-slate-100">
        <div className="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
          {highlights.map((item) => (
            <div key={item.label} className="text-center bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
              <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mx-auto mb-3">
                {item.icon}
              </div>
              <div className="text-2xl md:text-3xl font-bold text-slate-900 mb-1">{item.value}</div>
              <div className="text-sm font-semibold text-emerald-600">{item.label}</div>
              <div className="text-xs text-slate-500 mt-0.5">{item.sub}</div>
            </div>
          ))}
        </div>
      </section>

      {/* 3 core values — 이미지 카드 */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              링크커넥트가 약속하는 <span className="text-emerald-500">3가지 핵심 가치</span>
            </h2>
            <p className="text-slate-500 max-w-xl mx-auto">
              연결부터 정산까지, 성과 중심 제휴 마케팅의 새로운 기준
            </p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {coreValues.map((item, i) => (
              <article
                key={item.title}
                className="group bg-white border border-slate-100 rounded-2xl overflow-hidden hover:border-emerald-200 hover:shadow-xl transition-all"
              >
                <div className="relative aspect-[16/10] overflow-hidden bg-slate-100">
                  <img
                    src={item.image}
                    alt={item.imageAlt}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
                  <div className="absolute top-3 left-3 w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center shadow-md">
                    {i + 1}
                  </div>
                </div>
                <div className="p-6">
                  <div className="w-11 h-11 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-4">
                    {item.icon}
                  </div>
                  <h3 className="text-lg font-bold text-slate-900 mb-3 leading-snug">{item.title}</h3>
                  <p className="text-slate-600 text-sm leading-relaxed">{item.desc}</p>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* Visual banner — 트래킹 강조 */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-slate-950">
        <div className="max-w-7xl mx-auto grid md:grid-cols-2 gap-10 items-center">
          <div>
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-bold mb-4">
              <BarChart3 className="w-4 h-4" />
              REAL-TIME TRACKING
            </div>
            <h2 className="text-2xl md:text-3xl font-bold text-white mb-4 leading-snug">
              클릭부터 전환까지,
              <br />
              <span className="text-cyan-400">한눈에 보는 성과</span>
            </h2>
            <p className="text-slate-400 leading-relaxed mb-6">
              CPA DB 캠페인과 CPS 구매 캠페인의 클릭·전환·수익 데이터를 실시간 대시보드에서 확인하세요.
              광고주와 파트너 모두 투명한 데이터로 안심하고 운영할 수 있습니다.
            </p>
            <Link
              to="/affiliate"
              className="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 font-semibold text-sm transition-colors"
            >
              제휴 마케팅 웹툰으로 쉽게 이해하기
              <ArrowUpRight className="w-4 h-4" />
            </Link>
          </div>
          <div className="relative">
            <div className="absolute -inset-2 bg-cyan-500/10 rounded-2xl blur-lg" />
            <img
              src={images.tracking}
              alt="CPA vs CPS 성과 비교"
              className="relative w-full rounded-2xl border border-white/10 shadow-2xl object-cover aspect-[4/3]"
              loading="lazy"
            />
          </div>
        </div>
      </section>

      {/* Closing CTA */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-slate-900 to-slate-950 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-950/30 via-slate-950 to-cyan-950/20" />
        <div className="max-w-4xl mx-auto relative text-center">
          <div className="flex justify-center gap-3 mb-6">
            <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
              <Users className="w-6 h-6 text-emerald-400" />
            </div>
            <div className="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center">
              <TrendingUp className="w-6 h-6 text-cyan-400" />
            </div>
          </div>
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6 leading-tight">
            연결이 곧 수익이 되는 곳,
            <br />
            <span className="text-emerald-400">링크커넥트</span>
          </h2>
          <p className="text-slate-400 text-lg leading-relaxed max-w-2xl mx-auto mb-10">
            연간 300억 매출 견인의 저력, 투명한 데이터와 강력한 네트워크로
            여러분의 비즈니스를 다음 단계로 도약시켜 드립니다.
          </p>
          <div className="flex flex-wrap items-center justify-center gap-4">
            <Link
              to="/partner"
              className="px-8 py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl flex items-center gap-2 transition-colors"
            >
              파트너로 시작하기
              <ArrowUpRight className="w-5 h-5" />
            </Link>
            <Link
              to="/advertiser"
              className="px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-medium rounded-xl border border-white/10 transition-colors"
            >
              광고주 입점 문의
            </Link>
            <Link
              to="/cpa-list"
              className="px-8 py-4 text-cyan-400 hover:text-cyan-300 font-medium transition-colors"
            >
              CPA 캠페인 보기
            </Link>
            <Link
              to="/affiliate"
              className="px-8 py-4 text-slate-400 hover:text-white font-medium transition-colors"
            >
              제휴 마케팅이란?
            </Link>
          </div>
        </div>
      </section>
    </main>
  );
}
