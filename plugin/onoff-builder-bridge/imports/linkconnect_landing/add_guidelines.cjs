const fs = require('fs');
let content = fs.readFileSync('src/pages/EventDetail.tsx', 'utf8');

// Add icons
if (!content.includes('ShieldAlert')) {
  content = content.replace("Youtube\n}", "Youtube,\n  ShieldAlert, ListChecks, XCircle, Ban, AlertTriangle\n}");
}

// Checkbox state
if (!content.includes('const [isAgreed, setIsAgreed]')) {
  content = content.replace("const [copiedId, setCopiedId] = useState<string | number | null>(null);", "const [copiedId, setCopiedId] = useState<string | number | null>(null);\n  const [isAgreed, setIsAgreed] = useState(false);");
}

const guidelinesSection = `
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
                    <CheckCircle2 size={16} className={\`text-white transition-opacity \${isAgreed ? 'opacity-100' : 'opacity-0'}\`} />
                  </div>
                </div>
                <span className="text-white font-bold select-none group-hover:text-cyan-400 transition-colors">
                  위 홍보 금지사항과 무효 처리 기준을 확인했습니다.
                </span>
              </label>

              <div className="flex flex-col sm:flex-row gap-3 w-full max-w-md">
                <button className={\`flex-1 py-3.5 rounded-xl text-sm font-bold transition-all \${isAgreed ? 'bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg shadow-cyan-900/50' : 'bg-slate-800 text-slate-500 cursor-not-allowed'}\`}>
                  확인 후 이벤트 참여하기
                </button>
                <button className={\`flex-1 py-3.5 rounded-xl text-sm font-bold transition-all border \${isAgreed ? 'bg-transparent hover:bg-slate-800 text-white border-slate-600' : 'bg-transparent text-slate-600 border-slate-800 cursor-not-allowed'}\`}>
                  홍보 링크 만들기
                </button>
              </div>

              <p className="text-slate-500 text-xs mt-6">
                금지사항 위반이 반복될 경우 이벤트 참여 제한 또는 파트너 계정 검수가 진행될 수 있습니다.
              </p>
            </div>
          </div>
        </div>
`;

content = content.replace(
  "{/* Content Section */}",
  "{/* Content Section */}"
).replace(
  /<\/div>\n      <\/div>\n    <\/div>\n  \);\n}/,
  guidelinesSection + "\n      </div>\n    </div>\n  );\n}"
);

fs.writeFileSync('src/pages/EventDetail.tsx', content);
