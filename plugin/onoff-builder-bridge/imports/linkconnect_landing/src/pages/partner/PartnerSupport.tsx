import { PartnerLayout } from '../../layouts/PartnerLayout';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { MessageCircle, Clock, CheckCircle2, Bell, Paperclip, ChevronRight, HelpCircle } from 'lucide-react';

const inquiryHistory = [
  { id: 1, date: '2026.10.07', type: '정산 문의', title: '9월 정산 내역 확인 요청드립니다.', status: '답변대기' },
  { id: 2, date: '2026.10.05', type: '광고상품 문의', title: '신규 캠페인 허용 채널 문의', status: '답변완료' },
  { id: 3, date: '2026.09.28', type: '취소/무효 문의', title: 'DB240928-015 취소 사유 재확인 요청', status: '답변완료' },
  { id: 4, date: '2026.09.15', type: '시스템 문의', title: '링크 생성 시 오류가 발생합니다.', status: '답변완료' },
];

const faqs = [
  { id: 1, question: '승인율은 어디서 확인하나요?' },
  { id: 2, question: '정산 신청은 언제 가능한가요?' },
  { id: 3, question: '취소된 디비는 왜 생기나요?' },
  { id: 4, question: '광고주 코멘트는 어디서 확인하나요?' },
];

export function PartnerSupport() {
  return (
    <PartnerLayout activeMenu="support" title="문의하기">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          운영, 정산, 광고상품, 취소/무효 처리와 관련된 문의를 남겨주세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 문의" value="12" suffix="건" icon={<MessageCircle className="text-slate-500" />} />
        <SummaryCard title="답변 대기" value="1" suffix="건" icon={<Clock className="text-yellow-500" />} />
        <SummaryCard title="답변 완료" value="11" suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title="최근 공지" value="3" suffix="건" icon={<Bell className="text-blue-500" />} />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        {/* Form Area */}
        <div className="lg:col-span-2 space-y-8">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
            <h2 className="text-lg font-bold text-slate-900 mb-6">1:1 문의 작성</h2>
            
            <div className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">문의 유형 <span className="text-red-500">*</span></label>
                <select className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 appearance-none">
                  <option value="">문의 유형을 선택해주세요</option>
                  <option>광고상품 문의</option>
                  <option>수익 문의</option>
                  <option>정산 문의</option>
                  <option>취소/무효 문의</option>
                  <option>시스템 문의</option>
                  <option>기타</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">제목 <span className="text-red-500">*</span></label>
                <input 
                  type="text" 
                  className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                  placeholder="문의 제목을 입력해주세요"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">내용 <span className="text-red-500">*</span></label>
                <textarea 
                  rows={6}
                  className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none"
                  placeholder="상세한 문의 내용을 입력해주세요. (개인정보가 포함되지 않도록 주의해주세요)"
                ></textarea>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">첨부파일</label>
                <div className="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:bg-slate-50 transition-colors cursor-pointer">
                  <Paperclip className="w-8 h-8 text-slate-400 mx-auto mb-3" />
                  <p className="text-sm font-medium text-slate-600 mb-1">클릭하여 파일을 선택하거나 드래그 앤 드롭 하세요.</p>
                  <p className="text-xs text-slate-400">JPG, PNG, PDF (최대 10MB)</p>
                </div>
              </div>
              
              <div className="pt-4 flex justify-end">
                <button className="px-8 py-3.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm">
                  문의 등록하기
                </button>
              </div>
            </div>
          </div>

          {/* History Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 bg-slate-50">
              <h2 className="font-bold text-slate-900">문의 내역</h2>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">등록일</th>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">문의 유형</th>
                    <th className="px-5 py-4 font-medium whitespace-nowrap">제목</th>
                    <th className="px-5 py-4 font-medium text-center whitespace-nowrap">상태</th>
                    <th className="px-5 py-4 font-medium text-center whitespace-nowrap">상세보기</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {inquiryHistory.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-5 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                      <td className="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">{item.type}</td>
                      <td className="px-5 py-4 text-slate-900 font-medium min-w-[200px]">{item.title}</td>
                      <td className="px-5 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-5 py-4 text-center">
                        <button className="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                          <ChevronRight size={18} />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Side: FAQ */}
        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg sticky top-6">
            <div className="flex items-center gap-2 mb-6">
              <HelpCircle className="text-cyan-400" size={24} />
              <h2 className="text-lg font-bold">자주 묻는 질문</h2>
            </div>
            
            <div className="space-y-4">
              {faqs.map((faq) => (
                <button key={faq.id} className="w-full text-left bg-white/10 hover:bg-white/15 transition-colors p-4 rounded-xl flex items-start gap-3 group">
                  <span className="text-cyan-400 font-bold mt-0.5">Q.</span>
                  <span className="text-sm text-slate-200 group-hover:text-white leading-relaxed">{faq.question}</span>
                </button>
              ))}
            </div>

            <div className="mt-8 pt-6 border-t border-white/10">
              <p className="text-sm text-slate-400 mb-2">고객센터 운영시간</p>
              <p className="text-slate-200 font-medium">평일 10:00 - 18:00</p>
              <p className="text-xs text-slate-500 mt-1">(점심시간 12:30 - 13:30 / 주말 및 공휴일 휴무)</p>
            </div>
          </div>
        </div>
      </div>

    </PartnerLayout>
  );
}




