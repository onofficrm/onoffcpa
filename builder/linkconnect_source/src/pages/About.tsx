import {
  ArrowUpRight,
  BadgeDollarSign,
  BarChart3,
  Handshake,
  Headphones,
  Link2,
  PhoneCall,
  Route,
  ShieldCheck,
  Sparkles,
  TrendingUp,
  Users,
} from 'lucide-react';
import { useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';

const asset = (path: string) => `${import.meta.env.BASE_URL}${path}`;

const images = {
  hero: asset('about/about_hero.webp'),
  ecosystem: asset('about/about_ecosystem.webp'),
  dashboard: asset('hero_dashboard_mockup.webp'),
  partner: asset('webtoon/ep6-p1.webp'),
  tracking: asset('webtoon/ep4-p2.webp'),
  settlement: asset('webtoon/ep5-p2.webp'),
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
    desc: '링크 클릭부터 CPA 전환·콜디비까지 모든 데이터를 실시간 대시보드로 공개합니다. 모니터링으로 허위 실적을 차단해 깨끗한 생태계를 만듭니다.',
  },
  {
    icon: <BadgeDollarSign className="w-6 h-6 text-amber-500" />,
    image: images.settlement,
    imageAlt: '광고주와 파트너 상생',
    title: '지연 없는 정산과 신뢰 기반의 상생 파트너십',
    desc: '마케터의 수익이 지연 없이 보상받도록 신속한 정산을 약속합니다. 광고주에게는 ROI, 마케터에게는 합리적인 커미션을 보장합니다.',
  },
];

const highlights = [
  { value: 'CPA', label: '성과형 네트워크', sub: 'CPA · 콜디비', icon: <Link2 className="w-5 h-5" /> },
  { value: '실시간', label: '성과 추적', sub: '투명한 데이터', icon: <BarChart3 className="w-5 h-5" /> },
  { value: '0503', label: '콜디비 연동', sub: '가상번호 추적', icon: <PhoneCall className="w-5 h-5" /> },
  { value: 'Win-Win', label: '상생 파트너십', sub: '광고주 · 파트너', icon: <Handshake className="w-5 h-5" /> },
];

const storyParagraphs = [
  '온오프CPA는 CPA 기반 제휴마케팅을 중심으로, 광고주와 파트너가 함께 성장할 수 있는 성과형 플랫폼입니다.',
  '우리는 상담·문의·전환 DB를 투명하게 추적하고, 파트너에게는 빠른 정산과 명확한 실적 데이터를, 광고주에게는 실질적인 성과 중심 유입을 제공합니다.',
  '단순히 광고주와 마케터를 중개하는 데 그치지 않습니다. 캠페인 운영, 어뷰징 관리, 콜디비(전화 문의) 추적까지 이어지는 운영 경험을 바탕으로 현장에서 필요한 기능을 직접 설계합니다.',
  '광고주는 불필요한 광고비 낭비를 줄이면서 검증 가능한 상담·전환 성과를 원합니다. 파트너는 자신의 홍보 성과를 실시간으로 확인하고, 노력에 맞는 보상을 받기를 원합니다.',
  '온오프CPA는 그 간극을 메우기 위해 만들어졌습니다. 클릭·신청폼·전화 문의까지 성과로 연결하고, 광고주와 파트너가 같은 데이터를 보며 신뢰를 쌓을 수 있는 환경을 지향합니다.',
  '연결이 곧 수익이 되는 구조. 성과로 증명하는 제휴마케팅 플랫폼, 온오프CPA입니다.',
];

export function About() {
  const location = useLocation();

  useEffect(() => {
    if (location.hash === '#call-db') {
      window.requestAnimationFrame(() => {
        document.getElementById('call-db')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
      return;
    }
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
  }, [location.hash, location.pathname]);

  return (
    <main>
      <section className="pt-28 pb-16 md:pt-32 md:pb-24 px-4 sm:px-6 lg:px-8 bg-slate-950 relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-900/20 via-slate-950 to-slate-950" />
        <div className="absolute top-1/4 -right-32 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl" />
        <div className="absolute bottom-0 -left-32 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl" />

        <div className="max-w-7xl mx-auto relative">
          <div className="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
            <div className="text-center lg:text-left">
              <p className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-cyan-400 text-sm font-medium mb-6">
                <Link2 className="w-4 h-4" />
                About OnOff CPA
              </p>
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6">
                연결이 곧 수익이 되는
                <br />
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
                  성과형 CPA 플랫폼
                </span>
              </h1>
              <p className="text-lg text-slate-300 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-3">
                안녕하세요. 온오프CPA입니다.
              </p>
              <p className="text-base text-slate-400 leading-relaxed max-w-xl mx-auto lg:mx-0">
                CPA 상담·전환 DB와 콜디비를 중심으로, 광고주와 파트너가 함께 성장하는 제휴마케팅 플랫폼을 만듭니다.
              </p>
            </div>

            <div className="relative">
              <div className="absolute -inset-3 bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 rounded-2xl blur-xl" />
              <img
                src={images.hero}
                alt="온오프CPA 제휴 마케팅 플랫폼"
                className="relative w-full rounded-2xl border border-white/10 shadow-2xl object-cover aspect-video"
              />
            </div>
          </div>
        </div>
      </section>

      <section className="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
          <div className="relative order-2 lg:order-1">
            <img
              src={images.ecosystem}
              alt="제휴 마케팅 생태계 — 광고주, 파트너, 고객 연결"
              className="w-full rounded-2xl border border-slate-100 shadow-lg object-cover aspect-video"
            />
            <div className="absolute -bottom-4 -right-4 hidden md:block bg-emerald-500 text-white text-sm font-bold px-4 py-2 rounded-xl shadow-lg">
              CPA · 콜디비
            </div>
          </div>

          <div className="space-y-5 order-1 lg:order-2">
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 leading-snug">
              성과로 증명하는
              <br />
              <span className="text-emerald-600">온오프CPA의 연결</span>
            </h2>
            <div className="space-y-4 text-slate-600 text-sm md:text-base leading-relaxed">
              {storyParagraphs.map((paragraph) => (
                <p key={paragraph.slice(0, 24)}>{paragraph}</p>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section id="call-db" className="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-violet-50/60 to-white border-y border-slate-100 scroll-mt-24">
        <div className="max-w-7xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-100 border border-violet-200 text-violet-700 text-xs font-bold mb-4">
                <Headphones className="w-4 h-4" />
                CALL DB
              </div>
              <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4 leading-tight">
                전화 문의까지 잡는
                <br />
                <span className="text-violet-600">콜디비(Call DB)</span>
              </h2>
              <p className="text-slate-600 leading-relaxed mb-6">
                CPA는 폼 신청이 핵심이지만, 실제 고객은 전화로 문의하는 경우가 많습니다.
                온오프CPA는 파트너에게 <b>전용 가상번호</b>를 배정해 전화 유입도 성과로 추적합니다.
              </p>
              <ul className="space-y-4 mb-8">
                <li className="flex gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center shrink-0">
                    <PhoneCall className="w-5 h-5 text-violet-600" />
                  </div>
                  <div>
                    <p className="font-bold text-slate-900">파트너별 고유 번호 배정</p>
                    <p className="text-sm text-slate-600 mt-0.5">
                      파트너마다 단독 가상번호를 제공해 홍보 채널별 전화 유입을 구분합니다.
                    </p>
                  </div>
                </li>
                <li className="flex gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center shrink-0">
                    <Route className="w-5 h-5 text-violet-600" />
                  </div>
                  <div>
                    <p className="font-bold text-slate-900">번호 기준 자동 매칭</p>
                    <p className="text-sm text-slate-600 mt-0.5">
                      고객 전화가 들어오면 가상번호로 파트너·광고주 캠페인을 식별하고 콜 DB로 기록합니다.
                    </p>
                  </div>
                </li>
                <li className="flex gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center shrink-0">
                    <TrendingUp className="w-5 h-5 text-violet-600" />
                  </div>
                  <div>
                    <p className="font-bold text-slate-900">CPA 수익 기회 확대</p>
                    <p className="text-sm text-slate-600 mt-0.5">
                      신청폼 없이 전화만 한 고객도 유효 전환으로 연결해 CPA 캠페인 수익 기회를 넓힙니다.
                    </p>
                  </div>
                </li>
              </ul>
              <div className="flex flex-wrap gap-3">
                <Link
                  to="/partner/call"
                  className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm transition-colors"
                >
                  파트너 콜디비 바로가기
                  <ArrowUpRight className="w-4 h-4" />
                </Link>
                <Link
                  to="/cpa-list"
                  className="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-violet-200 text-violet-700 hover:bg-violet-50 font-bold text-sm bg-white transition-colors"
                >
                  CPA 캠페인 보기
                </Link>
              </div>
            </div>

            <div className="relative">
              <div className="absolute -inset-3 bg-violet-500/10 rounded-3xl blur-xl" aria-hidden />
              <div className="relative rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-700 to-indigo-800 p-8 md:p-10 text-white shadow-2xl">
                <p className="text-violet-200 text-sm font-semibold mb-2">콜디비 운영 흐름</p>
                <ol className="space-y-4 text-sm">
                  <li className="flex gap-3 items-start">
                    <span className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">1</span>
                    <span>파트너가 CPA 캠페인 홍보 + 전용 가상번호 노출</span>
                  </li>
                  <li className="flex gap-3 items-start">
                    <span className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">2</span>
                    <span>고객이 전화 문의 → 가상번호로 파트너·캠페인 식별</span>
                  </li>
                  <li className="flex gap-3 items-start">
                    <span className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">3</span>
                    <span>광고주 착신 연결 + 통화 로그·콜 DB 적재</span>
                  </li>
                  <li className="flex gap-3 items-start">
                    <span className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">4</span>
                    <span>유효 전환 확정 시 CPA 수익 정산</span>
                  </li>
                </ol>
                <div className="mt-8 pt-6 border-t border-white/15">
                  <p className="text-violet-200 text-xs mb-1">전화 유입도 성과로 연결</p>
                  <p className="text-xl md:text-2xl font-bold tracking-tight">폼 + 콜 = 더 많은 CPA 기회</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

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

      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              온오프CPA가 약속하는 <span className="text-emerald-500">3가지 핵심 가치</span>
            </h2>
            <p className="text-slate-500 max-w-xl mx-auto">
              연결부터 정산까지, 성과 중심 제휴 마케팅의 기준
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
              CPA DB 캠페인과 파트너 전용 가상번호 기반 콜디비까지.
              클릭·신청·전화 문의의 전환을 실시간 대시보드에서 확인하세요.
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
              alt="CPA 성과 트래킹"
              className="relative w-full rounded-2xl border border-white/10 shadow-2xl object-cover aspect-[4/3]"
              loading="lazy"
            />
          </div>
        </div>
      </section>

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
            <span className="text-emerald-400">온오프CPA</span>
          </h2>
          <p className="text-slate-400 text-lg leading-relaxed max-w-2xl mx-auto mb-10">
            광고주와 파트너가 같은 성과 데이터를 보고 성장하는 선순환.
            투명한 추적과 합리적인 정산으로 더 큰 기회를 만듭니다.
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
