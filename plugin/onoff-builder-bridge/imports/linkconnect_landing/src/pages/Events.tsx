import React, { useState } from 'react';
import { 
  Gift, TrendingUp, Clock, Trophy, ArrowRight, Search, Filter, 
  ChevronRight, Calendar, AlertCircle, CheckCircle2, Megaphone, Target, Briefcase, Zap, Star, Sparkles, User, HelpCircle
} from 'lucide-react';
import { Link } from 'react-router-dom';

const BADGE_STYLES: Record<string, string> = {
  '진행중': 'bg-cyan-50 text-cyan-700 border-cyan-200',
  '마감임박': 'bg-orange-50 text-orange-700 border-orange-200',
  '종료': 'bg-slate-100 text-slate-600 border-slate-200',
  '예정': 'bg-blue-50 text-blue-700 border-blue-200',
  '보너스 지급': 'bg-purple-50 text-purple-700 border-purple-200',
  '단가 상승': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  '광고주 전용': 'bg-slate-800 text-slate-100 border-slate-900',
  '신규 캠페인': 'bg-blue-50 text-blue-700 border-blue-200',
  '파트너 보너스': 'bg-purple-50 text-purple-700 border-purple-200'
};

function Badge({ children, type }: { children: React.ReactNode, type: string }) {
  return (
    <span className={`inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border ${BADGE_STYLES[type] || 'bg-slate-100 text-slate-600 border-slate-200'}`}>
      {children}
    </span>
  );
}

export function Events() {
  const [activeTab, setActiveTab] = useState('전체');
  const [isMounted, setIsMounted] = useState(false);
  React.useEffect(() => { setTimeout(() => setIsMounted(true), 100); }, []);

  const tabs = ['전체', '파트너 이벤트', '광고주 프로모션', '단가 상승', '신규 캠페인', '마감 임박', '리워드 랭킹'];

  return (
    <div className="bg-slate-50 min-h-screen pb-20">
      
      {/* Hero Section */}
      <section className="bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 pt-32 pb-16 md:pt-36 md:pb-20 relative overflow-hidden">
        {/* Abstract Background Shapes */}
        <div className="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-cyan-300 text-sm font-bold mb-6">
            <Gift size={16} />
            <span>이벤트 / 프로모션</span>
          </div>
          <h1 className="text-3xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">
            성과가 좋은 캠페인에는 <span className="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-emerald-400">더 큰 리워드를</span>
          </h1>
          <p className="text-lg text-slate-300 mb-10 max-w-2xl mx-auto">
            파트너 전용 보너스, 단가 상승 이벤트, 신규 광고상품 프로모션을 한눈에 확인하고 바로 참여하세요.
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="#events-list" className="px-8 py-3.5 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-xl font-bold transition-colors shadow-lg shadow-cyan-500/20 flex items-center justify-center gap-2 w-full sm:w-auto">
              진행 중 이벤트 보기 <ArrowRight size={18} />
            </a>
            <a href="#promo-campaigns" className="px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 w-full sm:w-auto">
              추천 CPA 상품 보기
            </a>
          </div>
        </div>
      </section>

      {/* Summary Cards */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-2xl p-5 shadow-lg shadow-slate-200/50 border border-slate-200 flex flex-col items-center justify-center text-center">
            <div className="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-xl flex items-center justify-center mb-3">
              <Megaphone size={24} />
            </div>
            <div className="text-3xl font-bold text-slate-900">12<span className="text-sm font-medium text-slate-500 ml-1">개</span></div>
            <div className="text-sm font-medium text-slate-600 mt-1">진행 중 이벤트</div>
          </div>
          <div className="bg-white rounded-2xl p-5 shadow-lg shadow-slate-200/50 border border-slate-200 flex flex-col items-center justify-center text-center">
            <div className="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-3">
              <Gift size={24} />
            </div>
            <div className="text-3xl font-bold text-slate-900">5<span className="text-sm font-medium text-slate-500 ml-1">개</span></div>
            <div className="text-sm font-medium text-slate-600 mt-1">보너스 지급 캠페인</div>
          </div>
          <div className="bg-white rounded-2xl p-5 shadow-lg shadow-slate-200/50 border border-slate-200 flex flex-col items-center justify-center text-center">
            <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-3">
              <TrendingUp size={24} />
            </div>
            <div className="text-3xl font-bold text-slate-900">8<span className="text-sm font-medium text-slate-500 ml-1">개</span></div>
            <div className="text-sm font-medium text-slate-600 mt-1">단가 상승 상품</div>
          </div>
          <div className="bg-white rounded-2xl p-5 shadow-lg shadow-slate-200/50 border border-slate-200 flex flex-col items-center justify-center text-center">
            <div className="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center mb-3">
              <Clock size={24} />
            </div>
            <div className="text-3xl font-bold text-slate-900">3<span className="text-sm font-medium text-slate-500 ml-1">개</span></div>
            <div className="text-sm font-medium text-slate-600 mt-1">마감 임박 이벤트</div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 space-y-16">

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
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 shadow-lg shadow-black/20">
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
                <Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                  홍보 링크 만들기
                </Link>
              </div>
            </div>

            {/* Rec Card 2 */}
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 shadow-lg shadow-black/20">
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
                <Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-bold transition-colors shadow-sm">
                  이벤트 보기
                </Link>
              </div>
            </div>

            {/* Rec Card 3 */}
            <div className="bg-white rounded-2xl p-5 flex flex-col group border border-transparent hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 shadow-lg shadow-black/20">
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
                <Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                  지금 참여하기
                </Link>
              </div>
            </div>
          </div>
        </section>

        
        {/* Tabs & Search */}
        
        {/* My Event Progress Section */}
        <section id="event-progress" className="bg-white rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-sm border border-slate-200">
          <div className="relative z-10 mb-8 md:mb-10">
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">내 이벤트 참여 현황</h2>
            <p className="text-slate-500 text-sm md:text-base max-w-xl">
              현재 참여 중인 이벤트의 달성률과 예상 보너스를 확인하세요.
            </p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div className="bg-slate-50 rounded-2xl p-5 border border-slate-100 flex flex-col items-center justify-center text-center">
              <div className="text-sm font-medium text-slate-500 mb-1">참여 중 이벤트</div>
              <div className="text-2xl font-black text-slate-900">3<span className="text-sm font-bold text-slate-500 ml-1">개</span></div>
            </div>
            <div className="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 flex flex-col items-center justify-center text-center">
              <div className="text-sm font-medium text-emerald-600 mb-1">달성 완료 이벤트</div>
              <div className="text-2xl font-black text-emerald-700">1<span className="text-sm font-bold text-emerald-600 ml-1">개</span></div>
            </div>
            <div className="bg-slate-50 rounded-2xl p-5 border border-slate-100 flex flex-col items-center justify-center text-center">
              <div className="text-sm font-medium text-slate-500 mb-1">목표까지 남은 DB</div>
              <div className="text-2xl font-black text-slate-900">7<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-cyan-50 rounded-2xl p-5 border border-cyan-100 flex flex-col items-center justify-center text-center">
              <div className="text-sm font-medium text-cyan-600 mb-1">예상 추가 보너스</div>
              <div className="text-2xl font-black text-cyan-700">320,000<span className="text-sm font-bold text-cyan-600 ml-1">원</span></div>
            </div>
          </div>

          <div className="space-y-4 relative z-10">
            {/* Progress Card 1 */}
            <div className="bg-white rounded-2xl p-5 md:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6 group hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="진행중">참여중</Badge>
                  <span className="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded flex items-center gap-1"><Calendar size={12} /> ~ 2026.10.31</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">첫 승인 5건 달성 보너스</h3>
                <p className="text-sm text-slate-600 mb-1">승인 DB 5건 달성 시 100,000원 보너스</p>
                <div className="text-xs font-bold text-rose-500 flex items-center gap-1"><AlertCircle size={14} /> 목표까지 승인 DB 2건 남았습니다!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-cyan-600 text-sm">60%</span></div>
                  <div className="text-sm font-bold text-slate-800">3 / 5건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out" style={{ width: isMounted ? '60%' : '0%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                  <span className="text-xs font-bold text-slate-500">예상 보너스</span>
                  <span className="text-lg font-black text-cyan-600">100,000원</span>
                </div>
              </div>
              
              <div className="md:w-32 shrink-0 flex flex-col justify-end">
                <Link to="/events/detail" className="w-full flex items-center justify-center py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                  추천 상품 보기
                </Link>
              </div>
            </div>

            {/* Progress Card 2 */}
            <div className="bg-white rounded-2xl p-5 md:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6 group hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="진행중">참여중</Badge>
                  <span className="text-xs font-bold text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded">마감 D-2</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">개인회생 단가 상승 이벤트</h3>
                <p className="text-sm text-slate-600 mb-1">승인 DB 10건 이상 시 추가 단가 적용</p>
                <div className="text-xs font-bold text-orange-500 flex items-center gap-1"><Clock size={14} /> 2건만 더 달성하면 추가 수익 발생!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-cyan-600 text-sm">80%</span></div>
                  <div className="text-sm font-bold text-slate-800">8 / 10건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out" style={{ width: isMounted ? '80%' : '0%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                  <span className="text-xs font-bold text-slate-500">예상 추가 수익</span>
                  <span className="text-lg font-black text-emerald-600">80,000원</span>
                </div>
              </div>
              
              <div className="md:w-32 shrink-0 flex flex-col justify-end">
                <button className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                  목표 달성하기
                </button>
              </div>
            </div>

            {/* Progress Card 3 */}
            <div className="bg-emerald-50/50 rounded-2xl p-5 md:p-6 border border-emerald-200 shadow-sm flex flex-col md:flex-row md:items-center gap-6">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-3">
                  <Badge type="단가 상승">달성완료</Badge>
                  <span className="text-xs font-bold text-slate-500 bg-white px-2 py-1 rounded flex items-center gap-1"><Calendar size={12} /> 2026.10.05 달성</span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">신규 파트너 첫 승인 보너스</h3>
                <p className="text-sm text-slate-600 mb-1">첫 승인 DB 1건 발생 완료</p>
                <div className="text-xs font-bold text-emerald-600 flex items-center gap-1"><CheckCircle2 size={14} /> 목표를 성공적으로 달성했습니다!</div>
              </div>

              <div className="flex-1 max-w-sm w-full bg-white p-4 rounded-xl border border-emerald-100">
                <div className="flex justify-between items-end mb-2">
                  <div className="text-xs font-bold text-slate-500">진행률 <span className="text-emerald-600 text-sm">100%</span></div>
                  <div className="text-sm font-bold text-emerald-700">1 / 1건</div>
                </div>
                <div className="w-full bg-slate-200 rounded-full h-2.5 mb-4 overflow-hidden">
                  <div className="bg-gradient-to-r from-emerald-400 to-emerald-500 h-2.5 rounded-full" style={{ width: '100%' }}></div>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-100 mt-2">
                  <span className="text-xs font-bold text-slate-500">지급 예정 보너스</span>
                  <span className="text-lg font-black text-purple-600 flex items-center gap-1"><Gift size={16}/> 50,000원</span>
                </div>
              </div>
              
              <div className="md:w-32 shrink-0 flex flex-col justify-end">
                <Link to="/events/detail" className="w-full flex items-center justify-center py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm font-bold transition-colors shadow-sm">
                  리워드 내역 보기
                </Link>
              </div>
            </div>
          </div>

          <div className="mt-8 bg-slate-50 rounded-xl p-5 border border-slate-200">
            <h4 className="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2"><AlertCircle size={16} className="text-slate-400"/> 진행률 기준 안내</h4>
            <ul className="text-xs text-slate-600 space-y-1.5 pl-6 list-disc">
              <li>승인 완료된 DB만 이벤트 실적에 반영됩니다.</li>
              <li>취소/무효 처리된 DB는 진행률에서 제외됩니다.</li>
              <li>이벤트 기간 내 발생한 성과만 인정됩니다.</li>
              <li>보너스는 관리자 검수 후 정산에 반영됩니다.</li>
            </ul>
          </div>
        </section>

        <section id="events-list">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div className="overflow-x-auto pb-2 md:pb-0 hide-scrollbar flex-1">
              <div className="flex gap-2 min-w-max">
                {tabs.map(tab => (
                  <button 
                    key={tab}
                    onClick={() => setActiveTab(tab)}
                    className={`px-4 py-2.5 rounded-xl text-sm font-bold transition-colors whitespace-nowrap ${
                      activeTab === tab 
                        ? 'bg-slate-900 text-white shadow-sm' 
                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                    }`}
                  >
                    {tab}
                  </button>
                ))}
              </div>
            </div>
            
            <div className="flex gap-2 w-full md:w-auto">
              <div className="relative flex-1 md:w-64">
                <input 
                  type="text" 
                  placeholder="이벤트명, 광고상품명으로 검색하세요." 
                  className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                />
                <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              </div>
              <button className="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 flex items-center gap-2 transition-colors">
                <Filter size={16} />
                <span className="text-sm font-bold hidden sm:inline">필터</span>
              </button>
            </div>
          </div>

          {/* Event Cards Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            {/* Event Card 1 */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-cyan-200 transition-all overflow-hidden flex flex-col group cursor-pointer">
              <div className="p-6 flex-1">
                <div className="flex items-center gap-2 mb-4">
                  <Badge type="진행중">진행중</Badge>
                  <Badge type="단가 상승">단가 상승</Badge>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2 group-hover:text-cyan-700 transition-colors">개인회생 상담 DB 단가 상승 이벤트</h3>
                <p className="text-sm text-slate-600 line-clamp-2 mb-5 leading-relaxed">
                  이벤트 기간 동안 개인회생 상담 DB 승인 1건당 파트너 지급 단가가 30,000원에서 40,000원으로 상승합니다.
                </p>
                <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Calendar size={14} /> 기간</span>
                    <span className="font-bold text-slate-800">2026.10.01 ~ 10.31</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Briefcase size={14} /> 적용 상품</span>
                    <span className="font-bold text-slate-800">개인회생 상담 DB 외 3개</span>
                  </div>
                  <div className="pt-3 mt-3 border-t border-slate-200 flex items-center justify-between text-sm">
                    <span className="font-bold text-slate-800">보너스 혜택</span>
                    <span className="font-bold text-emerald-600 flex items-center gap-1"><TrendingUp size={14}/> 승인 1건당 +10,000원</span>
                  </div>
                </div>
              </div>
              <div className="p-4 border-t border-slate-100 bg-slate-50 grid grid-cols-2 gap-2">
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors">상세보기</button>
                <button className="py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-bold hover:bg-cyan-700 transition-colors shadow-sm">홍보 링크 만들기</button>
              </div>
            </div>

            {/* Event Card 2 */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-cyan-200 transition-all overflow-hidden flex flex-col group cursor-pointer relative">
              <div className="absolute -right-12 top-6 bg-rose-500 text-white text-[10px] font-bold py-1 px-12 rotate-45 z-10 shadow-sm">
                마감 3일전
              </div>
              <div className="p-6 flex-1">
                <div className="flex items-center gap-2 mb-4">
                  <Badge type="마감임박">마감임박</Badge>
                  <Badge type="파트너 보너스">파트너 보너스</Badge>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2 group-hover:text-cyan-700 transition-colors">신규 파트너 첫 승인 보너스</h3>
                <p className="text-sm text-slate-600 line-clamp-2 mb-5 leading-relaxed">
                  신규 파트너가 첫 승인 DB를 발생시키면 추가 보너스 50,000원을 지급합니다.
                </p>
                <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Calendar size={14} /> 기간</span>
                    <span className="font-bold text-slate-800">~ 2026.10.10 까지</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Target size={14} /> 참여 조건</span>
                    <span className="font-bold text-slate-800">첫 승인 DB 1건 이상</span>
                  </div>
                  <div className="pt-3 mt-3 border-t border-slate-200 flex items-center justify-between text-sm">
                    <span className="font-bold text-slate-800">예상 리워드</span>
                    <span className="font-bold text-purple-600 flex items-center gap-1"><Gift size={14}/> 50,000원 지급</span>
                  </div>
                </div>
              </div>
              <div className="p-4 border-t border-slate-100 bg-slate-50 grid grid-cols-2 gap-2">
                <button className="col-span-2 py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-bold hover:bg-cyan-700 transition-colors shadow-sm">참여하기</button>
              </div>
            </div>

            {/* Event Card 3 */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-cyan-200 transition-all overflow-hidden flex flex-col group cursor-pointer">
              <div className="p-6 flex-1">
                <div className="flex items-center gap-2 mb-4">
                  <Badge type="진행중">진행중</Badge>
                  <Badge type="신규 캠페인">신규 캠페인</Badge>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2 group-hover:text-cyan-700 transition-colors">영어캠프 상담 DB 집중 프로모션</h3>
                <p className="text-sm text-slate-600 line-clamp-2 mb-5 leading-relaxed">
                  겨울방학 시즌을 맞아 세부 영어캠프 상담 DB 캠페인의 집중 홍보 파트너를 모집합니다.
                </p>
                <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Calendar size={14} /> 기간</span>
                    <span className="font-bold text-slate-800">상시 진행</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Briefcase size={14} /> 적용 상품</span>
                    <span className="font-bold text-slate-800">세부 영어캠프 상담 DB</span>
                  </div>
                  <div className="pt-3 mt-3 border-t border-slate-200 flex items-center justify-between text-sm">
                    <span className="font-bold text-slate-800">파트너 단가</span>
                    <span className="font-bold text-cyan-600 flex items-center gap-1">35,000원</span>
                  </div>
                </div>
              </div>
              <div className="p-4 border-t border-slate-100 bg-slate-50 grid grid-cols-2 gap-2">
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors">상품 보기</button>
                <button className="py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-bold hover:bg-cyan-700 transition-colors shadow-sm">홍보 시작하기</button>
              </div>
            </div>
            
          </div>
        </section>

        {/* Promo Campaigns Section */}
        <section id="promo-campaigns">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-xl font-bold text-slate-900">프로모션 적용 CPA 상품</h2>
              <p className="text-sm text-slate-500 mt-1">현재 이벤트 혜택이 적용되는 광고상품을 확인하고 바로 홍보를 시작하세요.</p>
            </div>
            <Link to="/cpa-list" className="hidden sm:flex items-center gap-1 text-sm font-bold text-cyan-600 hover:text-cyan-700 transition-colors">
              전체보기 <ChevronRight size={16} />
            </Link>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            {/* Promo Campaign 1 */}
            <div className="bg-white rounded-2xl p-5 border-2 border-emerald-400 shadow-lg shadow-emerald-500/10 flex flex-col group relative">
              <div className="absolute top-0 right-6 -translate-y-1/2 bg-emerald-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1 shadow-sm">
                <Zap size={14} /> 단가 상승 중
              </div>
              <div className="flex justify-between items-start mb-4 mt-2">
                <div>
                  <div className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded mb-2 inline-block">10월 단가 상승 이벤트</div>
                  <h3 className="text-lg font-bold text-slate-900 group-hover:text-cyan-600 transition-colors">개인회생 상담 DB</h3>
                  <div className="text-sm text-slate-500 mt-1 flex items-center gap-2">
                    <span className="bg-slate-100 px-2 py-0.5 rounded text-xs">법률/세무</span>
                    <span className="flex items-center gap-1"><CheckCircle2 size={12} className="text-emerald-500"/> 승인율 68%</span>
                  </div>
                </div>
              </div>
              <div className="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-5">
                <div className="flex justify-between items-center">
                  <div className="text-sm text-slate-500 line-through">기존 30,000원</div>
                  <div className="text-xs font-bold text-emerald-600 bg-emerald-100 px-2 py-1 rounded-full">+10,000원 상승</div>
                </div>
                <div className="text-2xl font-black text-slate-900 mt-1 flex items-end justify-between">
                  <div>40,000<span className="text-base font-bold text-slate-500 ml-1">원</span></div>
                </div>
              </div>
              <button className="mt-auto w-full py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                홍보 링크 생성
              </button>
            </div>
            
            {/* Promo Campaign 2 */}
            <div className="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col group relative hover:border-cyan-200 transition-colors">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <div className="text-xs font-bold text-purple-600 bg-purple-50 px-2 py-1 rounded mb-2 inline-block">첫 승인 보너스 5만원</div>
                  <h3 className="text-lg font-bold text-slate-900 group-hover:text-cyan-600 transition-colors">장기렌트카 견적 신청</h3>
                  <div className="text-sm text-slate-500 mt-1 flex items-center gap-2">
                    <span className="bg-slate-100 px-2 py-0.5 rounded text-xs">자동차</span>
                    <span className="flex items-center gap-1"><CheckCircle2 size={12} className="text-emerald-500"/> 승인율 82%</span>
                  </div>
                </div>
              </div>
              <div className="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-5">
                <div className="flex justify-between items-center">
                  <div className="text-sm text-slate-500">기본 단가</div>
                  <div className="text-xs font-bold text-purple-600">+보너스 혜택 적용</div>
                </div>
                <div className="text-2xl font-black text-slate-900 mt-1 flex items-end justify-between">
                  <div>25,000<span className="text-base font-bold text-slate-500 ml-1">원</span></div>
                </div>
              </div>
              <button className="mt-auto w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                홍보 링크 생성
              </button>
            </div>
          </div>
        </section>

        {/* Ranking Section */}
        <section id="ranking" className="bg-slate-900 rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-xl border border-slate-800">
          <div className="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
            <Trophy size={300} className="text-amber-400 rotate-12" />
          </div>

          <div className="relative z-10 mb-8 md:mb-10">
            <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-bold mb-3 border border-amber-500/30">
              <Trophy size={14} /> 랭킹 이벤트
            </div>
            <h2 className="text-2xl md:text-3xl font-bold text-white mb-2">이번 달 파트너 리워드 랭킹</h2>
            <p className="text-slate-400 text-sm md:text-base max-w-xl">
              승인 DB 기준 상위 파트너에게 추가 리워드가 지급됩니다.
            </p>
          </div>

          {/* Ranking Summary Cards */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 relative z-10">
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">현재 1위 승인 DB</div>
              <div className="text-2xl md:text-3xl font-black text-white">128<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-cyan-900/30 backdrop-blur-sm rounded-2xl p-5 border border-cyan-800 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-cyan-400 mb-1">내 현재 순위</div>
              <div className="text-2xl md:text-3xl font-black text-cyan-400">18<span className="text-sm font-bold text-cyan-600 ml-1">위</span></div>
            </div>
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">TOP 10까지 남은 DB</div>
              <div className="text-2xl md:text-3xl font-black text-white">7<span className="text-sm font-bold text-slate-500 ml-1">건</span></div>
            </div>
            <div className="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-700 flex flex-col items-center justify-center text-center shadow-sm">
              <div className="text-sm font-medium text-slate-400 mb-1">내 예상 보너스</div>
              <div className="text-2xl md:text-3xl font-black text-white">0<span className="text-sm font-bold text-slate-500 ml-1">원</span></div>
              <div className="text-[10px] text-emerald-400 mt-1">TOP 10 진입 시 100,000원</div>
            </div>
          </div>

          <div className="grid grid-cols-1 xl:grid-cols-3 gap-8 relative z-10">
            <div className="xl:col-span-2 space-y-8">
              {/* Top 3 Podium Cards */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* 2nd Place */}
                <div className="bg-gradient-to-b from-slate-300/10 to-transparent p-1 rounded-2xl border border-slate-300/20 md:order-1 order-2">
                  <div className="bg-slate-900/80 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-12 h-12 bg-gradient-to-br from-slate-200 to-slate-400 rounded-full flex items-center justify-center text-slate-900 shadow-lg shadow-slate-300/20 mb-3 border-4 border-slate-900">
                      <span className="font-black text-lg">2</span>
                    </div>
                    <div className="text-lg font-bold text-white mb-1">PTN-54**</div>
                    <div className="text-sm text-slate-400 mb-4">승인 DB 96건</div>
                    <div className="mt-auto w-full bg-slate-300/10 border border-slate-300/20 rounded-lg p-3">
                      <div className="text-[10px] text-slate-400 font-bold mb-1">예상 리워드</div>
                      <div className="text-lg font-black text-white">500,000원</div>
                    </div>
                  </div>
                </div>

                {/* 1st Place */}
                <div className="bg-gradient-to-b from-amber-500/20 to-transparent p-1 rounded-2xl border border-amber-500/30 md:-mt-6 md:order-2 order-1 shadow-2xl shadow-amber-500/10">
                  <div className="bg-slate-900/90 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-16 h-16 bg-gradient-to-br from-amber-300 to-amber-500 rounded-full flex items-center justify-center text-slate-900 shadow-lg shadow-amber-500/30 mb-3 border-4 border-slate-900 relative">
                      <div className="absolute -top-3 text-amber-300"><Trophy size={20} fill="currentColor" /></div>
                      <span className="font-black text-2xl">1</span>
                    </div>
                    <div className="text-xl font-bold text-amber-400 mb-1">PTN-8291</div>
                    <div className="text-sm text-amber-500/80 font-bold mb-1">확정수익 3,840,000원</div>
                    <div className="text-sm text-slate-300 mb-4">승인 DB 128건</div>
                    <div className="mt-auto w-full bg-amber-500/10 border border-amber-500/30 rounded-lg p-3">
                      <div className="text-[10px] text-amber-500 font-bold mb-1">예상 리워드</div>
                      <div className="text-2xl font-black text-amber-400">1,000,000원</div>
                    </div>
                  </div>
                </div>

                {/* 3rd Place */}
                <div className="bg-gradient-to-b from-orange-600/20 to-transparent p-1 rounded-2xl border border-orange-600/30 md:order-3 order-3 md:mt-4">
                  <div className="bg-slate-900/80 backdrop-blur-sm p-5 rounded-xl h-full flex flex-col items-center text-center">
                    <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-700 rounded-full flex items-center justify-center text-white shadow-lg shadow-orange-600/20 mb-3 border-4 border-slate-900">
                      <span className="font-black text-lg">3</span>
                    </div>
                    <div className="text-lg font-bold text-white mb-1">PTN-30**</div>
                    <div className="text-sm text-slate-400 mb-4">승인 DB 74건</div>
                    <div className="mt-auto w-full bg-orange-600/10 border border-orange-600/20 rounded-lg p-3">
                      <div className="text-[10px] text-orange-400 font-bold mb-1">예상 리워드</div>
                      <div className="text-lg font-black text-orange-400">300,000원</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* My Rank Card */}
              <div className="bg-cyan-950/30 border border-cyan-900 rounded-2xl p-6 shadow-inner">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                  <div>
                    <h3 className="text-lg font-bold text-cyan-400 mb-4 flex items-center gap-2"><Target size={18}/> 내 현재 순위</h3>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-6">
                      <div>
                        <div className="text-xs text-slate-400 mb-1">현재 순위</div>
                        <div className="text-xl font-bold text-white">18위</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">승인 DB</div>
                        <div className="text-xl font-bold text-white">42건</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">10위까지 남은 DB</div>
                        <div className="text-xl font-bold text-rose-400">7건</div>
                      </div>
                      <div>
                        <div className="text-xs text-slate-400 mb-1">현재 확정수익</div>
                        <div className="text-xl font-bold text-white">1,260,000<span className="text-sm text-slate-400 ml-0.5">원</span></div>
                      </div>
                    </div>
                  </div>
                  <div className="w-full md:w-auto shrink-0 flex flex-col gap-2">
                    <button className="w-full px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                      순위 올리기 좋은 캠페인
                    </button>
                    <button className="w-full px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-cyan-900 rounded-xl text-sm font-bold transition-colors">
                      이벤트 상품 홍보하기
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div className="xl:col-span-1 space-y-6">
              {/* TOP 10 List */}
              <div className="bg-slate-800/40 border border-slate-700 rounded-2xl overflow-hidden">
                <div className="p-4 border-b border-slate-700 bg-slate-800/60">
                  <h3 className="text-sm font-bold text-white">TOP 4 ~ 10 순위</h3>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm text-left text-slate-300">
                    <thead className="text-xs text-slate-400 bg-slate-800/40 uppercase">
                      <tr>
                        <th scope="col" className="px-4 py-3">순위</th>
                        <th scope="col" className="px-4 py-3">파트너</th>
                        <th scope="col" className="px-4 py-3 text-right">승인 DB</th>
                        <th scope="col" className="px-4 py-3 text-right">리워드</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-700/50">
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">4</td>
                        <td className="px-4 py-3">PTN-91**</td>
                        <td className="px-4 py-3 text-right font-medium">68건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">5</td>
                        <td className="px-4 py-3">PTN-12**</td>
                        <td className="px-4 py-3 text-right font-medium">61건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">6</td>
                        <td className="px-4 py-3">PTN-84**</td>
                        <td className="px-4 py-3 text-right font-medium">55건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">7</td>
                        <td className="px-4 py-3">PTN-33**</td>
                        <td className="px-4 py-3 text-right font-medium">52건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">8</td>
                        <td className="px-4 py-3">PTN-76**</td>
                        <td className="px-4 py-3 text-right font-medium">51건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">9</td>
                        <td className="px-4 py-3">PTN-21**</td>
                        <td className="px-4 py-3 text-right font-medium">49건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                      <tr className="hover:bg-slate-700/30">
                        <td className="px-4 py-3 font-bold text-slate-300">10</td>
                        <td className="px-4 py-3">PTN-65**</td>
                        <td className="px-4 py-3 text-right font-medium">49건</td>
                        <td className="px-4 py-3 text-right text-cyan-400 font-bold">10만원</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Reward Structure */}
              <div className="bg-slate-800/40 border border-slate-700 rounded-2xl p-5">
                <h3 className="text-sm font-bold text-white mb-3">리워드 지급 기준</h3>
                <ul className="space-y-2 text-sm">
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-amber-400 font-bold">1위</span>
                    <span className="text-white font-bold">1,000,000원</span>
                  </li>
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-slate-300 font-bold">2위</span>
                    <span className="text-white font-bold">500,000원</span>
                  </li>
                  <li className="flex justify-between items-center pb-2 border-b border-slate-700/50">
                    <span className="text-orange-400 font-bold">3위</span>
                    <span className="text-white font-bold">300,000원</span>
                  </li>
                  <li className="flex justify-between items-center pt-1">
                    <span className="text-cyan-400 font-bold">4~10위</span>
                    <span className="text-white font-bold">100,000원</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div className="mt-8 border-t border-slate-800 pt-5 relative z-10">
            <h4 className="text-xs font-bold text-slate-400 flex items-center gap-1.5 mb-2"><AlertCircle size={14} /> 랭킹 기준 안내</h4>
            <ul className="text-[11px] text-slate-500 space-y-1 pl-5 list-disc marker:text-slate-600">
              <li>승인 완료 DB 기준으로 순위가 산정됩니다.</li>
              <li>취소/무효 DB는 랭킹에서 제외됩니다.</li>
              <li>동일 건수일 경우 확정수익이 높은 파트너가 우선됩니다.</li>
            </ul>
          </div>
        </section>
        
        {/* Advertiser Promo */}
        <section id="advertiser-promo" className="bg-gradient-to-b from-slate-900 to-slate-950 rounded-3xl p-6 md:p-10 relative overflow-hidden shadow-2xl border border-slate-800">
          {/* Background Decor */}
          <div className="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
            <Target size={250} className="text-cyan-400 rotate-12" />
          </div>

          <div className="relative z-10 mb-8 md:mb-12">
            <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-cyan-500/10 text-cyan-400 text-xs font-bold mb-4 border border-cyan-500/20">
              광고주 전용
            </div>
            <h2 className="text-2xl md:text-3xl font-black text-white mb-3 tracking-tight">내 CPA 상품도 프로모션에 노출하고 싶으신가요?</h2>
            <p className="text-slate-400 text-sm md:text-base max-w-xl">
              단가 상승, 추천 캠페인 노출, 파트너 모집 이벤트로 더 많은 디비를 확보해보세요.
            </p>
          </div>

          {/* Cards Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 relative z-10 mb-12">
            {/* Card 1 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-cyan-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center mb-4 border border-cyan-500/20 group-hover:scale-110 transition-transform">
                <TrendingUp size={20} className="text-cyan-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">단가 상승 프로모션</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                일정 기간 파트너 단가를 높여 더 많은 파트너 참여를 유도합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 빠르게 DB를 확보하고 싶은 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-cyan-400 font-bold block mb-0.5">혜택</span> 이벤트 페이지 + 추천 영역 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors">
                신청하기
              </button>
            </div>

            {/* Card 2 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-emerald-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-4 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                <Star size={20} className="text-emerald-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">신규 캠페인 추천 노출</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                새로 등록한 CPA 상품을 파트너에게 추천 캠페인으로 노출합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 신규 광고상품을 빠르게 알리고 싶은 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-emerald-400 font-bold block mb-0.5">혜택</span> 메인 추천 캠페인 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                상담 신청
              </button>
            </div>

            {/* Card 3 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-amber-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center mb-4 border border-amber-500/20 group-hover:scale-110 transition-transform">
                <Trophy size={20} className="text-amber-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">파트너 랭킹 이벤트</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                특정 상품 기준으로 파트너 랭킹 이벤트를 운영하여 홍보 경쟁을 유도합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 대량 디비가 필요한 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-amber-400 font-bold block mb-0.5">혜택</span> 랭킹 이벤트 페이지 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                이벤트 만들기 문의
              </button>
            </div>

            {/* Card 4 */}
            <div className="bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 hover:border-purple-500/30 rounded-2xl p-5 flex flex-col group transition-all duration-300">
              <div className="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center mb-4 border border-purple-500/20 group-hover:scale-110 transition-transform">
                <Gift size={20} className="text-purple-400" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">광고비 충전 보너스</h3>
              <p className="text-xs text-slate-400 leading-relaxed mb-4 flex-1">
                일정 금액 이상 광고비 충전 시 추가 노출 혜택을 제공합니다.
              </p>
              <div className="space-y-2 mb-5">
                <div className="text-[11px] text-slate-500">
                  <span className="text-slate-300 font-bold block mb-0.5">추천 대상</span> 장기 캠페인을 운영하는 광고주
                </div>
                <div className="text-[11px] text-slate-500">
                  <span className="text-purple-400 font-bold block mb-0.5">혜택</span> 추천 상품 우선 노출
                </div>
              </div>
              <button className="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 rounded-xl text-sm font-bold transition-colors">
                광고비 충전 문의
              </button>
            </div>
          </div>

          {/* Process Flow */}
          <div className="relative z-10 bg-slate-950/50 rounded-2xl p-6 md:p-8 border border-slate-800 mb-10">
            <h3 className="text-sm font-bold text-slate-300 mb-6 text-center">프로모션 신청 및 진행 과정</h3>
            <div className="flex flex-col md:flex-row justify-between items-center gap-4 relative">
              <div className="hidden md:block absolute top-1/2 left-0 w-full h-[1px] bg-slate-800 -translate-y-1/2"></div>
              
              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">1</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">광고상품 선택</span>
              </div>
              
              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">2</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">유형 선택</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">3</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">관리자 검토</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">4</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">이벤트 노출</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-cyan-900 group-hover:border-cyan-500 group-hover:text-cyan-400 transition-colors z-10">5</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">파트너 홍보 시작</span>
              </div>

              <div className="relative flex flex-col items-center gap-2 group w-full md:w-auto">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-emerald-900 group-hover:border-emerald-500 group-hover:text-emerald-400 transition-colors z-10">6</div>
                <span className="text-xs text-slate-400 font-medium whitespace-nowrap">디비 성과 확인</span>
              </div>
            </div>
          </div>

          {/* Bottom CTA Area */}
          <div className="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8 bg-cyan-900/20 rounded-2xl p-6 md:p-8 border border-cyan-500/20">
            <div className="flex-1">
              <h3 className="text-xl md:text-2xl font-bold text-white mb-4">광고상품 성과를 더 빠르게 키우고 싶다면<br className="hidden md:block"/> 프로모션을 신청하세요.</h3>
              
              <div className="flex flex-wrap gap-4 text-sm text-slate-300">
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">프로모션 적용 상품</span>
                  <span className="font-black text-white text-lg">38<span className="text-sm text-slate-400 font-normal ml-0.5">개</span></span>
                </div>
                <div className="w-[1px] h-10 bg-slate-700 hidden sm:block"></div>
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">참여 파트너</span>
                  <span className="font-black text-white text-lg">1,200<span className="text-sm text-slate-400 font-normal ml-0.5">명+</span></span>
                </div>
                <div className="w-[1px] h-10 bg-slate-700 hidden sm:block"></div>
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500">평균 DB 증가율</span>
                  <span className="font-black text-emerald-400 text-lg">32<span className="text-sm font-normal ml-0.5">%</span></span>
                </div>
              </div>
            </div>

            <div className="w-full lg:w-auto flex flex-col sm:flex-row gap-3">
              <Link to="/advertiser" className="flex items-center justify-center px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                광고주센터로 이동
              </Link>
              <button className="flex items-center justify-center px-6 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors shadow-lg shadow-cyan-900/50">
                프로모션 신청하기
              </button>
            </div>
          </div>
        </section>

        {/* Footer Info Area */}
        <section className="bg-slate-100 rounded-2xl p-6 md:p-8 flex items-start gap-4 border border-slate-200">
          <AlertCircle className="w-6 h-6 text-slate-400 shrink-0 mt-1" />
          <div>
            <h3 className="text-base font-bold text-slate-800 mb-3">이벤트 참여 전 확인해주세요.</h3>
            <ul className="text-sm text-slate-600 space-y-2 list-disc list-inside">
              <li>이벤트별 참여 조건을 반드시 확인해주세요.</li>
              <li>승인 완료된 디비만 리워드 지급 대상에 포함됩니다.</li>
              <li>취소/무효 처리된 디비는 이벤트 실적에서 제외됩니다.</li>
              <li>이벤트 기간 내 발생한 성과만 인정됩니다.</li>
              <li>리워드는 정산 정책에 따라 지급되며, 세금 처리가 적용될 수 있습니다.</li>
            </ul>
          </div>
        </section>

      </div>
    </div>
  );
}
