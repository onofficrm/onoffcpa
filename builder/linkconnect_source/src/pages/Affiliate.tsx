import { ArrowUpRight, MousePointerClick, Link2, PenLine, Wallet, ShoppingCart, MessageSquare, Users, Building2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { g5RegisterUrl } from '../lib/urls';
import { AffiliateWebtoon } from '../components/AffiliateWebtoon';

const steps = [
  {
    num: '01',
    icon: <MousePointerClick className="w-6 h-6 text-emerald-400" />,
    title: '아이템 고르기',
    desc: '링크커넥트에 등록된 다양한 브랜드의 캠페인 중에서 내가 홍보하고 싶은 아이템을 선택합니다.',
  },
  {
    num: '02',
    icon: <Link2 className="w-6 h-6 text-cyan-400" />,
    title: '전용 링크 받기',
    desc: '버튼을 눌러 나만의 고유한 홍보 링크를 발급받습니다. 이 링크에는 누가 홍보했는지 기록되는 기술이 적용되어 있어, 나의 실적을 정확하게 인정받을 수 있습니다.',
  },
  {
    num: '03',
    icon: <PenLine className="w-6 h-6 text-amber-400" />,
    title: '콘텐츠 올리기',
    desc: '내가 활동하는 매체(블로그, SNS 등)에 제품을 소개하는 글을 작성하고, 발급받은 전용 링크를 함께 남깁니다.',
  },
  {
    num: '04',
    icon: <Wallet className="w-6 h-6 text-indigo-400" />,
    title: '수익금 정산받기',
    desc: '방문자가 내 링크를 눌러 상품을 구매하거나 문의를 남기면, 약속된 수수료가 내 계정에 실시간으로 쌓입니다.',
  },
];

const revenueTypes = [
  {
    type: '판매형 (CPS)',
    concept: 'Cost Per Sale',
    criteria: "내 링크를 통해 들어온 사람이 '물건을 결제'했을 때",
    examples: '쇼핑몰 제품 판매, 온라인 강의 결제',
    icon: <ShoppingCart className="w-5 h-5 text-cyan-500" />,
    accent: 'border-cyan-200 bg-cyan-50/50',
  },
  {
    type: '행동형 (CPA)',
    concept: 'Cost Per Action',
    criteria: "내 링크를 통해 들어온 사람이 '문의/상담 신청'을 했을 때",
    examples: '신규 앱 설치, 서비스 문의, 보험 및 금융 상담',
    icon: <MessageSquare className="w-5 h-5 text-emerald-500" />,
    accent: 'border-emerald-200 bg-emerald-50/50',
  },
];

export function Affiliate() {
  return (
    <main>
      {/* Hero */}
      <section className="pt-32 pb-20 px-4 sm:px-6 lg:px-8 bg-slate-950 relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-cyan-900/20 via-slate-950 to-slate-950" />
        <div className="max-w-4xl mx-auto relative text-center">
          <p className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-emerald-400 text-sm font-medium mb-8">
            <Link2 className="w-4 h-4" />
            What is Affiliate?
          </p>
          <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6">
            제휴 마케팅이란?
          </h1>
          <p className="text-xl md:text-2xl text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400 font-semibold mb-6">
            링크 하나로 시작하는 새로운 수익 모델
          </p>
          <p className="text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto">
            블로그, 카페, 소셜 미디어에 홍보 링크를 올리고, 유의미한 행동(구매·문의)이 발생하면
            소개 수수료(커미션)가 지급되는 비즈니스입니다.
          </p>
        </div>
      </section>

      {/* Intro */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-3xl mx-auto text-center">
          <p className="text-slate-600 leading-relaxed text-base md:text-lg">
            물건을 직접 사입하거나 배송, 고객 응대를 할 필요가 전혀 없습니다.
            오직 <strong className="text-slate-900 font-semibold">좋은 제품과 서비스를 사람들에게 소개</strong>하는 것만으로,
            자본금 없이 안정적인 수익을 만들어낼 수 있습니다.
          </p>
          <a
            href="#webtoon"
            className="inline-flex items-center gap-2 mt-6 px-5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-xl border border-emerald-200 text-sm transition-colors"
          >
            📖 웹툰으로 쉽게 이해하기
          </a>
        </div>
      </section>

      {/* Webtoon — 사장님과 홍보왕의 제휴마케팅 입문기 */}
      <AffiliateWebtoon />

      {/* 4 Steps */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-slate-50">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              수익이 만들어지는 <span className="text-emerald-500">4단계 과정</span>
            </h2>
            <p className="text-slate-500">복잡한 절차 없이, 단 4단계만 거치면 누구나 수익 창출을 시작할 수 있습니다.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {steps.map((step) => (
              <article
                key={step.num}
                className="bg-white border border-slate-100 rounded-2xl p-7 hover:border-emerald-200 hover:shadow-md transition-all flex gap-5"
              >
                <div className="shrink-0">
                  <div className="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-2">
                    {step.icon}
                  </div>
                  <div className="text-xs font-bold text-emerald-600 tracking-wider">STEP {step.num}</div>
                </div>
                <div>
                  <h3 className="text-lg font-bold text-slate-900 mb-2">{step.title}</h3>
                  <p className="text-slate-600 text-sm leading-relaxed">{step.desc}</p>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* CPS vs CPA */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-white">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
              초보자도 쉽게 이해하는 <span className="text-cyan-500">두 가지 수익 방식</span>
            </h2>
            <p className="text-slate-500">
              CPS와 CPA는 캠페인 목적에 따라 수익 발생 기준이 다릅니다.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            {revenueTypes.map((row) => (
              <article key={row.type} className={`border rounded-2xl p-7 ${row.accent}`}>
                <div className="flex items-center gap-3 mb-4">
                  {row.icon}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900">{row.type}</h3>
                    <p className="text-xs text-slate-500 font-mono">{row.concept}</p>
                  </div>
                </div>
                <dl className="space-y-3 text-sm">
                  <div>
                    <dt className="text-slate-500 font-medium mb-0.5">수익 발생 기준</dt>
                    <dd className="text-slate-800 font-semibold">{row.criteria}</dd>
                  </div>
                  <div>
                    <dt className="text-slate-500 font-medium mb-0.5">주요 예시</dt>
                    <dd className="text-slate-700">{row.examples}</dd>
                  </div>
                </dl>
              </article>
            ))}
          </div>

          <div className="flex flex-wrap justify-center gap-4">
            <Link to="/cps" className="px-6 py-3 bg-cyan-500 hover:bg-cyan-400 text-white font-bold rounded-xl text-sm transition-colors">
              CPS 캠페인 보기
            </Link>
            <Link to="/cpa-list" className="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-sm transition-colors">
              CPA 캠페인 보기
            </Link>
          </div>
        </div>
      </section>

      {/* Marketer vs Advertiser */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-slate-50">
        <div className="max-w-5xl mx-auto">
          <h2 className="text-3xl font-bold text-slate-900 text-center mb-12">
            마케터와 광고주 모두가 성장하는 시스템
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <article className="bg-white border border-slate-100 rounded-2xl p-8">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                  <Users className="w-5 h-5 text-emerald-600" />
                </div>
                <h3 className="text-xl font-bold text-slate-900">마케터 (홍보하는 사람)</h3>
              </div>
              <ul className="space-y-3 text-slate-600 text-sm leading-relaxed">
                <li className="flex gap-2"><span className="text-emerald-500 font-bold shrink-0">·</span>가입비나 초기 비용이 전혀 들지 않습니다.</li>
                <li className="flex gap-2"><span className="text-emerald-500 font-bold shrink-0">·</span>남는 시간을 활용해 언제 어디서든 부수익을 만들 수 있습니다.</li>
                <li className="flex gap-2"><span className="text-emerald-500 font-bold shrink-0">·</span>연 300억 원의 매출을 이끌어낸 링크커넥트의 검증된 캠페인으로 초보자도 수익을 빠르게 늘려갈 수 있습니다.</li>
              </ul>
            </article>
            <article className="bg-white border border-slate-100 rounded-2xl p-8">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-11 h-11 rounded-xl bg-cyan-50 border border-cyan-100 flex items-center justify-center">
                  <Building2 className="w-5 h-5 text-cyan-600" />
                </div>
                <h3 className="text-xl font-bold text-slate-900">광고주 (브랜드)</h3>
              </div>
              <ul className="space-y-3 text-slate-600 text-sm leading-relaxed">
                <li className="flex gap-2"><span className="text-cyan-500 font-bold shrink-0">·</span>단순히 광고를 노출하는 데 비용을 쓰지 않습니다.</li>
                <li className="flex gap-2"><span className="text-cyan-500 font-bold shrink-0">·</span>실제 제품이 팔리거나 신규 고객이 문의를 남겼을 때만 비용을 지급합니다.</li>
                <li className="flex gap-2"><span className="text-cyan-500 font-bold shrink-0">·</span>예산 낭비 없이 가장 안전하고 효율적으로 매출을 올릴 수 있습니다.</li>
              </ul>
            </article>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-24 px-4 sm:px-6 lg:px-8 bg-slate-950 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-950/30 via-slate-950 to-cyan-950/20" />
        <div className="max-w-3xl mx-auto relative text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-4 leading-tight">
            자본금 없이 시작하는 가장 안전한 비즈니스
          </h2>
          <p className="text-slate-400 text-lg mb-10">
            당신의 글과 콘텐츠가 수익이 되는 경험을 링크커넥트에서 시작해 보세요.
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link
              to="/partner"
              className="w-full sm:w-auto px-8 py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl flex items-center justify-center gap-2 transition-colors"
            >
              파트너로 가입하고 수익 내기
              <ArrowUpRight className="w-5 h-5" />
            </Link>
            <Link
              to="/advertiser"
              className="w-full sm:w-auto px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-medium rounded-xl border border-white/10 transition-colors"
            >
              광고주로 가입하고 매출 올리기
            </Link>
          </div>
          <p className="mt-6 text-sm text-slate-500">
            아직 회원이 아니신가요?{' '}
            <a href={g5RegisterUrl()} className="text-cyan-400 hover:text-cyan-300 underline underline-offset-2">
              회원가입
            </a>
          </p>
        </div>
      </section>
    </main>
  );
}
