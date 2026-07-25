import React, { useState } from 'react';
import { 
  Gift, Trophy, ArrowRight, Search, Filter, 
  ChevronRight, Calendar, AlertCircle, CheckCircle2, Megaphone, Target, Briefcase, Zap, Star, Sparkles, User, HelpCircle, Clock, Link as LinkIcon,
  Copy, FileText, MessageCircle, MessageSquare, Youtube,
  ShieldAlert, ListChecks, XCircle, Ban, AlertTriangle
} from 'lucide-react';
import { Link } from 'react-router-dom';

function Badge({ children, type }: { children: React.ReactNode, type: string }) {
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
  return (
    <span className={`px-2.5 py-1 rounded-full text-xs font-bold border ${BADGE_STYLES[type] || BADGE_STYLES['진행중']}`}>
      {children}
    </span>
  );
}

export function EventDetail() {
  const [activeTab, setActiveTab] = useState('블로그 제목');
  const [copiedId, setCopiedId] = useState<string | number | null>(null);
  const [isAgreed, setIsAgreed] = useState(false);

  const handleCopy = (text: string, id: string | number) => {
    navigator.clipboard.writeText(text).catch(() => {});
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  return (
    <div className="min-h-screen bg-slate-50">
      
      {/* Toast Notification */}
      <div 
        className={`fixed bottom-24 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 ${
          copiedId !== null ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'
        }`}
      >
        <div className="bg-slate-900 text-white px-6 py-3 rounded-full shadow-lg font-medium text-sm flex items-center gap-2">
          <CheckCircle2 size={16} className="text-emerald-400" />
          홍보 문구가 복사되었습니다.
        </div>
      </div>
      
      {/* Mobile Sticky CTA */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
        <button className="w-full bg-cyan-600 hover:bg-cyan-500 text-white py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-cyan-900/20 transition-colors">
          내 홍보 링크 만들기
        </button>
      </div>

      {/* Hero Section */}
      <section className="bg-slate-900 pt-32 pb-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1557682250-33bd709cbe85?auto=format&fit=crop&q=80')] opacity-5 bg-cover bg-center"></div>
        <div className="max-w-4xl mx-auto relative z-10">
          <div className="mb-4">
            <Link to="/events" className="text-cyan-400 text-sm font-medium hover:text-cyan-300 flex items-center gap-1">
              <ChevronRight className="rotate-180" size={16} /> 목록으로 돌아가기
            </Link>
          </div>
          <div className="flex items-center gap-2 mb-4">
            <Badge type="진행중">참여중</Badge>
            <span className="text-sm font-bold text-slate-300 bg-white/10 px-2.5 py-1 rounded-full flex items-center gap-1 border border-white/10"><Calendar size={14} /> 2026.10.01 ~ 2026.10.31</span>
          </div>
          <h1 className="text-3xl md:text-5xl font-black text-white mb-6 leading-tight">
            첫 승인 5건 달성 보너스
          </h1>
          <p className="text-lg md:text-xl text-slate-300 max-w-2xl mb-8">
            신규 및 기존 파트너 모두 참여 가능! 이벤트 기간 내 승인 DB 5건 달성 시 100,000원 추가 보너스를 지급합니다.
          </p>
          <div className="flex flex-col sm:flex-row gap-3">
            <button className="px-6 py-3.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-base font-bold transition-colors shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2">
              <LinkIcon size={18} /> 홍보 링크 만들기
            </button>
            <button className="px-6 py-3.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-base font-bold transition-colors flex items-center justify-center gap-2">
              이벤트 문의하기
            </button>
          </div>
        </div>
      </section>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-[-30px] relative z-20 pb-24 space-y-8">
        
        {/* Progress Card Inserted Naturally */}
        <div className="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-xl shadow-slate-200/50 flex flex-col md:flex-row md:items-center gap-8">
          <div className="flex-1">
            <h3 className="text-xl font-bold text-slate-900 mb-2">나의 참여 현황</h3>
            <p className="text-sm text-slate-600 mb-4">현재까지 승인된 DB 개수와 예상 보너스입니다.</p>
            <div className="text-sm font-bold text-cyan-600 flex items-center gap-1.5 p-3 bg-cyan-50 rounded-xl border border-cyan-100"><CheckCircle2 size={16} /> 목표까지 승인 DB 2건 남았습니다!</div>
          </div>

          <div className="flex-1 w-full">
            <div className="flex justify-between items-end mb-3">
              <div className="text-sm font-bold text-slate-500">진행률 <span className="text-cyan-600 text-lg ml-1">60%</span></div>
              <div className="text-base font-bold text-slate-800">3 / 5건</div>
            </div>
            <div className="w-full bg-slate-100 rounded-full h-3 mb-5 overflow-hidden border border-slate-200/50">
              <div className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-3 rounded-full" style={{ width: '60%' }}></div>
            </div>
            <div className="flex justify-between items-center pt-4 border-t border-slate-100 mt-2">
              <span className="text-sm font-bold text-slate-500">예상 보너스 금액</span>
              <span className="text-2xl font-black text-cyan-600">100,000원</span>
            </div>
          </div>
        </div>

        {/* Info Box */}
        <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200 shadow-sm">
          <h4 className="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-3"><AlertCircle size={16} className="text-slate-400"/> 진행률 기준 안내</h4>
          <ul className="text-sm text-slate-600 space-y-2 pl-6 list-disc marker:text-slate-400">
            <li>승인 완료된 DB만 이벤트 실적에 반영됩니다.</li>
            <li>취소/무효 처리된 DB는 진행률에서 제외됩니다.</li>
            <li>이벤트 기간 내 발생한 성과만 인정됩니다.</li>
            <li>보너스는 관리자 검수 후 정산에 반영됩니다.</li>
          </ul>
        </div>
        
        
        {/* Promo Samples Section */}
        <div className="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm mt-8">
          <div className="mb-6">
            <h2 className="text-2xl font-bold text-slate-900 mb-2 flex items-center gap-2"><FileText className="text-cyan-600"/> 바로 사용할 수 있는 홍보 문구 샘플</h2>
            <p className="text-slate-500 text-sm">
              블로그, 카페, SNS에 활용할 수 있는 예시 문구를 확인하고 내 홍보 링크와 함께 사용해보세요.
            </p>
          </div>

          {/* Tabs */}
          <div className="flex overflow-x-auto gap-2 pb-2 mb-6 scrollbar-hide">
            {['블로그 제목', '블로그 본문', '카페 글', 'SNS 문구', '유튜브 설명란', '문자/카카오 안내'].map((tab) => (
              <button 
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-colors ${activeTab === tab ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
              >
                {tab}
              </button>
            ))}
          </div>

          {/* Tab Content */}
          <div className="space-y-4">
            {activeTab === '블로그 제목' && (
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                <h4 className="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">블로그 제목 샘플</h4>
                
                <div className="space-y-3">
                  {['개인회생 상담 전 꼭 확인해야 할 5가지', '개인회생 신청 조건, 어렵게 생각하지 마세요', '채무조정이 필요할 때 무료 상담으로 확인하는 방법'].map((sample, idx) => (
                    <div key={idx} className="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 group hover:border-cyan-300 transition-colors">
                      <div className="flex-1 text-slate-700 font-medium text-sm">"{sample}"</div>
                      <div className="flex gap-2 shrink-0 w-full sm:w-auto">
                         <button className="flex-1 sm:flex-none flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-xs font-bold transition-colors" onClick={() => handleCopy(sample, idx)}>
                          {copiedId === idx ? <CheckCircle2 size={14} className="text-emerald-500"/> : <Copy size={14} />} {copiedId === idx ? '복사 완료' : '제목 복사'}
                        </button>
                        <button className="flex-1 sm:flex-none flex items-center justify-center gap-1.5 px-3 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-xs font-bold transition-colors">
                          내 홍보 링크 넣기
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === '블로그 본문' && (
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                <div className="bg-white p-5 rounded-xl border border-slate-200">
                  <p className="text-slate-700 text-sm leading-relaxed mb-6 whitespace-pre-wrap">
개인회생을 고민하고 있다면 먼저 본인의 조건이 가능한지 확인하는 것이 중요합니다. 
복잡한 서류 준비나 자격 요건 때문에 막막하시다면, 
전문가의 무료 상담을 통해 현재 상황에 맞는 방법을 확인해보세요.

아래 링크를 통해 간단하게 상담을 신청하고 가능 여부를 알아보실 수 있습니다.

<span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold">👉 [내 홍보 링크]</span>
                  </p>
                  
                  <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-slate-100">
                    <button className="flex items-center justify-center gap-1.5 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm font-bold transition-colors" onClick={() => handleCopy('본문', 'body')}>
                      {copiedId === 'body' ? <CheckCircle2 size={16} className="text-emerald-500"/> : <Copy size={16} />} {copiedId === 'body' ? '복사 완료' : '본문 복사'}
                    </button>
                    <button className="flex items-center justify-center gap-1.5 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                      홍보 링크 생성
                    </button>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'SNS 문구' && (
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                 <div className="bg-white p-5 rounded-xl border border-slate-200">
                  <p className="text-slate-700 text-sm leading-relaxed mb-6 whitespace-pre-wrap">
채무 문제로 고민 중이라면 개인회생 가능 여부부터 확인해보세요. 
간단한 상담 신청으로 현재 상황에 맞는 친절한 안내를 받을 수 있습니다.

바로 확인하기 👇
<span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold">[내 홍보 링크]</span>

#개인회생 #무료상담 #채무조정 #신용회복
                  </p>
                  
                  <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-slate-100">
                    <button className="flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors" onClick={() => handleCopy('sns', 'sns')}>
                       {copiedId === 'sns' ? <CheckCircle2 size={16} className="text-emerald-400"/> : <Copy size={16} />} {copiedId === 'sns' ? '복사 완료' : 'SNS 문구 복사'}
                    </button>
                  </div>
                </div>
              </div>
            )}
            
            {activeTab === '카페 글' && (
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                 <div className="bg-white p-5 rounded-xl border border-slate-200">
                  <p className="text-slate-700 text-sm leading-relaxed mb-6 whitespace-pre-wrap">
개인회생이나 채무조정은 조건에 따라 가능 여부가 달라질 수 있습니다. 
혼자서 고민하기보다는 먼저 상담을 통해 자격 여부를 확인하는 것이 좋습니다.

아래 남겨드리는 곳에서 무료로 간편하게 알아보실 수 있으니 참고해보세요.

➡️ <span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold">[내 홍보 링크]</span>
                  </p>
                  
                  <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-slate-100">
                    <button className="flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors" onClick={() => handleCopy('cafe', 'cafe')}>
                       {copiedId === 'cafe' ? <CheckCircle2 size={16} className="text-emerald-400"/> : <Copy size={16} />} {copiedId === 'cafe' ? '복사 완료' : '카페 글 복사'}
                    </button>
                  </div>
                </div>
              </div>
            )}

            {activeTab === '유튜브 설명란' && (
               <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                 <div className="bg-white p-5 rounded-xl border border-slate-200">
                  <p className="text-slate-700 text-sm leading-relaxed mb-6 whitespace-pre-wrap">
▶ 개인회생 상담 신청하기: <span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold">[내 홍보 링크]</span>
현재 상황에 맞는 1:1 맞춤 상담을 받아보세요.

▶ 문의/제휴: example@email.com
                  </p>
                  
                  <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-slate-100">
                    <button className="flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors" onClick={() => handleCopy('youtube', 'youtube')}>
                       {copiedId === 'youtube' ? <CheckCircle2 size={16} className="text-emerald-400"/> : <Copy size={16} />} {copiedId === 'youtube' ? '복사 완료' : '유튜브 설명란 복사'}
                    </button>
                  </div>
                </div>
              </div>
            )}
            
            {activeTab === '문자/카카오 안내' && (
               <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                 <div className="bg-white p-5 rounded-xl border border-slate-200 max-w-sm mx-auto">
                  <div className="bg-yellow-300 p-3 rounded-t-xl text-center text-sm font-bold text-yellow-900">
                    카카오톡 메시지 예시
                  </div>
                  <div className="bg-slate-100 p-4 rounded-b-xl h-48 flex items-center justify-center">
                    <div className="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-slate-700 max-w-[80%] whitespace-pre-wrap">
안녕하세요!
요청하신 상담 안내드립니다.

아래 링크를 통해 접수해주시면
빠르게 안내 도와드리겠습니다. 😊

👉 <span className="bg-yellow-100 text-yellow-800 px-1 rounded font-bold">[링크]</span>
                    </div>
                  </div>
                  
                  <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 mt-4">
                    <button className="w-full flex items-center justify-center gap-1.5 px-4 py-2 bg-[#FEE500] hover:bg-[#FADA0A] text-black rounded-xl text-sm font-bold transition-colors" onClick={() => handleCopy('kakao', 'kakao')}>
                       {copiedId === 'kakao' ? <CheckCircle2 size={16} className="text-emerald-600"/> : <MessageCircle size={16} />} {copiedId === 'kakao' ? '복사 완료' : '메시지 복사'}
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
          
          {/* Warning Info Box */}
          <div className="mt-8 bg-slate-50 rounded-xl p-5 border border-slate-200">
            <h4 className="text-sm font-bold text-slate-800 flex items-center gap-1.5 mb-2"><AlertCircle size={16} className="text-rose-500"/> 홍보 문구 사용 시 주의사항</h4>
            <ul className="text-xs text-slate-600 space-y-1.5 pl-6 list-disc marker:text-slate-400">
              <li>과장된 표현은 사용하지 마세요.</li>
              <li>승인 보장, 결과 보장 문구는 사용할 수 없습니다.</li>
              <li>실제 제공되는 상담 내용과 다른 표현은 피해주세요.</li>
              <li>이벤트별 금지 문구를 반드시 확인해주세요.</li>
            </ul>
          </div>
        </div>


        {/* Content Section */}
        <div className="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm prose prose-slate max-w-none prose-headings:font-bold prose-a:text-cyan-600">
          <h2 className="text-2xl font-bold mb-4">이벤트 상세 내용</h2>
          <p>
            링크커넥트 파트너님들의 적극적인 홍보를 지원하기 위해 10월 한 달 동안 <strong>첫 승인 5건 달성 보너스</strong> 이벤트를 진행합니다.
            기존에 활동하시던 파트너님은 물론 신규 가입하신 파트너님도 모두 참여하실 수 있습니다.
          </p>
          <h3 className="text-xl font-bold mt-8 mb-4">참여 방법</h3>
          <ol className="list-decimal pl-5 space-y-2">
            <li>상단의 <strong>홍보 링크 만들기</strong> 버튼을 클릭하여 원하는 캠페인의 링크를 생성합니다.</li>
            <li>생성된 링크를 블로그, 카페, SNS 등에 홍보합니다.</li>
            <li>이벤트 기간 내에 <strong>승인 DB 5건</strong>을 달성하면 자동으로 이벤트에 참여됩니다.</li>
          </ol>
          <h3 className="text-xl font-bold mt-8 mb-4">주의사항</h3>
          <ul className="list-disc pl-5 space-y-2">
            <li>본 이벤트는 당사 사정에 의해 조기 종료될 수 있습니다.</li>
            <li>어뷰징이나 부정 행위가 적발될 경우 보너스 지급이 취소될 수 있습니다.</li>
            <li>동일 IP 중복 접수 등 유효하지 않은 DB는 승인 건수에서 제외됩니다.</li>
          </ul>
        </div>

        {/* Guidelines Section */}
        <div className="bg-white rounded-3xl border border-rose-100 shadow-sm overflow-hidden mt-8">
          <div className="bg-rose-50/50 p-6 md:p-8 border-b border-rose-100">
            <h2 className="text-2xl font-bold text-rose-900 mb-2 flex items-center gap-2">
              <ShieldAlert className="text-rose-600" /> 이벤트 참여 전 반드시 확인하세요
            </h2>
            <p className="text-rose-700 text-sm">
              아래 금지사항을 위반하여 발생한 디비는 취소/무효 처리될 수 있습니다.
            </p>
          </div>

          <div className="p-6 md:p-8">
            <div className="bg-rose-50 rounded-xl p-4 mb-8 flex items-start gap-3 border border-rose-100">
              <AlertTriangle className="text-rose-500 shrink-0 mt-0.5" size={20} />
              <p className="text-sm font-bold text-rose-800">
                허위·과장 홍보로 발생한 디비는 이벤트 실적과 정산 대상에서 제외됩니다.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
              {/* Card 1 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <XCircle size={18} className="text-rose-500" /> 허위광고 금지
                </h3>
                <p className="text-sm text-slate-600 mb-4">
                  실제 제공되지 않는 혜택이나 조건을 보장하는 문구는 사용할 수 없습니다.
                </p>
                <div className="bg-slate-50 p-3 rounded-lg">
                  <div className="text-xs font-bold text-slate-500 mb-2">예시 금지 문구</div>
                  <div className="flex flex-wrap gap-2">
                    <span className="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-md shadow-sm">"무조건 승인"</span>
                    <span className="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-md shadow-sm">"100% 해결"</span>
                    <span className="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-md shadow-sm">"누구나 가능"</span>
                  </div>
                </div>
              </div>

              {/* Card 2 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <XCircle size={18} className="text-rose-500" /> 결과 보장 문구 금지
                </h3>
                <p className="text-sm text-slate-600 mb-4">
                  상담 결과, 승인 여부, 법적 결과 등을 확정적으로 표현하면 안 됩니다.
                </p>
                <div className="bg-slate-50 p-3 rounded-lg">
                  <div className="text-xs font-bold text-slate-500 mb-2">예시 금지 문구</div>
                  <div className="flex flex-wrap gap-2">
                    <span className="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-md shadow-sm">"무조건 개인회생 가능"</span>
                    <span className="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-md shadow-sm">"반드시 승인됩니다"</span>
                  </div>
                </div>
              </div>

              {/* Card 3 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <Ban size={18} className="text-rose-500" /> 브랜드 사칭 금지
                </h3>
                <p className="text-sm text-slate-600">
                  광고주, 공공기관, 금융기관, 법률기관을 사칭하는 표현을 사용할 수 없습니다.
                </p>
              </div>

              {/* Card 4 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <Ban size={18} className="text-rose-500" /> 스팸 홍보 금지
                </h3>
                <p className="text-sm text-slate-600">
                  무단 문자, 대량 쪽지, 댓글 도배, 자동화 스팸 홍보는 금지됩니다.
                </p>
              </div>

              {/* Card 5 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <Ban size={18} className="text-rose-500" /> 중복·허위 DB 유도 금지
                </h3>
                <p className="text-sm text-slate-600">
                  동일 고객 반복 접수, 허위 정보 입력 유도, 보상 목적의 가짜 신청은 금지됩니다.
                </p>
              </div>

              {/* Card 6 */}
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <h3 className="text-base font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <Ban size={18} className="text-rose-500" /> 금지 채널 사용 금지
                </h3>
                <p className="text-sm text-slate-600">
                  각 광고상품에서 제한한 채널을 통한 홍보는 무효 처리될 수 있습니다.
                </p>
              </div>
            </div>

            {/* Invalid DB Info */}
            <div className="bg-slate-50 rounded-2xl border border-slate-200 p-6 mb-8">
              <h3 className="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                <ListChecks size={18} className="text-slate-500" /> 무효 처리될 수 있는 DB 예시
              </h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 연락 불가
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 중복 신청
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 장난 신청
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 허위 정보
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 조건 불일치
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 금지 채널 유입
                </div>
                <div className="flex items-center gap-2 text-sm text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm md:col-span-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span> 브랜드 사칭 홍보로 발생한 신청
                </div>
              </div>
            </div>

            {/* Check and CTA */}
            <div className="bg-slate-900 rounded-2xl p-6 md:p-8 flex flex-col items-center text-center mt-8">
              <label className="flex items-center gap-3 cursor-pointer mb-6 group">
                <div className="relative flex items-center justify-center">
                  <input 
                    type="checkbox" 
                    className="peer sr-only"
                    checked={isAgreed}
                    onChange={(e) => setIsAgreed(e.target.checked)}
                  />
                  <div className="w-6 h-6 rounded-md border-2 border-slate-600 bg-slate-800 peer-checked:bg-cyan-500 peer-checked:border-cyan-500 transition-all flex items-center justify-center">
                    <CheckCircle2 size={16} className={`text-white transition-opacity ${isAgreed ? 'opacity-100' : 'opacity-0'}`} />
                  </div>
                </div>
                <span className="text-white font-bold select-none group-hover:text-cyan-400 transition-colors">
                  위 홍보 금지사항과 무효 처리 기준을 확인했습니다.
                </span>
              </label>

              <div className="flex flex-col sm:flex-row gap-3 w-full max-w-md">
                <button className={`flex-1 py-3.5 rounded-xl text-sm font-bold transition-all ${isAgreed ? 'bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg shadow-cyan-900/50' : 'bg-slate-800 text-slate-500 cursor-not-allowed'}`}>
                  확인 후 이벤트 참여하기
                </button>
                <button className={`flex-1 py-3.5 rounded-xl text-sm font-bold transition-all border ${isAgreed ? 'bg-transparent hover:bg-slate-800 text-white border-slate-600' : 'bg-transparent text-slate-600 border-slate-800 cursor-not-allowed'}`}>
                  홍보 링크 만들기
                </button>
              </div>

              <p className="text-slate-500 text-xs mt-6">
                금지사항 위반이 반복될 경우 이벤트 참여 제한 또는 파트너 계정 검수가 진행될 수 있습니다.
              </p>
            </div>
          </div>
        </div>


      </div>
    </div>
  );
}
