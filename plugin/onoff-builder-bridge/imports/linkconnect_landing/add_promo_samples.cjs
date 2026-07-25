const fs = require('fs');
let content = fs.readFileSync('src/pages/EventDetail.tsx', 'utf8');

// Add Copy icon and useState
if (!content.includes('import React, { useState }')) {
  content = content.replace("import React from 'react';", "import React, { useState } from 'react';");
}
if (!content.includes('Copy')) {
  content = content.replace("Link as LinkIcon\n}", "Link as LinkIcon,\n  Copy, FileText, MessageCircle, MessageSquare, Youtube\n}");
}

const promoSection = `
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
                className={\`px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-colors \${activeTab === tab ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}\`}
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
`;

content = content.replace("export function EventDetail() {", "export function EventDetail() {\n  const [activeTab, setActiveTab] = useState('블로그 제목');\n  const [copiedId, setCopiedId] = useState<string | number | null>(null);\n\n  const handleCopy = (text: string, id: string | number) => {\n    setCopiedId(id);\n    setTimeout(() => setCopiedId(null), 2000);\n  };\n");

content = content.replace(
  "{/* Content Section */}",
  promoSection + "\n\n        {/* Content Section */}"
);

fs.writeFileSync('src/pages/EventDetail.tsx', content);
