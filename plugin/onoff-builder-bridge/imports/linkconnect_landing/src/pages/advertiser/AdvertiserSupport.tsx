import React, { useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { HelpCircle, MessageSquare, ChevronDown, Paperclip, Send, AlertCircle, FileText, ChevronRight } from 'lucide-react';

const inquiries = [
  { id: 'IQ-241007-02', date: '2026.10.07 15:30', type: '광고비 충전 문의', title: '무통장 입금 확인 부탁드립니다.', campaign: '-', status: '접수완료', repliedAt: '-' },
  { id: 'IQ-241006-05', date: '2026.10.06 11:20', type: '취소/무효 문의', title: '연락처 결번 디비 환급 요청', campaign: '개인회생 상담 DB', status: '답변완료', repliedAt: '2026.10.06 14:10' },
  { id: 'IQ-241005-01', date: '2026.10.05 09:15', type: '광고상품 문의', title: '신규 상품 단가 조정 가능한가요?', campaign: '-', status: '답변완료', repliedAt: '2026.10.05 11:30' },
  { id: 'IQ-241002-03', date: '2026.10.02 16:40', type: 'API 연동 문의', title: '웹훅 연동 시 필수 파라미터 문의', campaign: '-', status: '답변완료', repliedAt: '2026.10.04 10:00' },
  { id: 'IQ-240928-01', date: '2026.09.28 10:00', type: '시스템 오류', title: '대시보드 통계 수치 오류', campaign: '-', status: '답변완료', repliedAt: '2026.09.28 11:20' },
];

const faqs = [
  { 
    q: '광고비는 언제 차감되나요?', 
    a: '사용자가 광고를 통해 디비를 남기면 우선 "가차감" 처리되며, 광고주가 해당 디비를 "승인" 처리할 경우 가차감이 "확정 차감"으로 변경됩니다. 만약 무효/취소 처리되면 가차감된 금액은 즉시 환급됩니다.' 
  },
  { 
    q: '취소/무효 처리 기준은 무엇인가요?', 
    a: '결번, 본인아님, 중복접수, 장난/테스트 등 명백한 허위 디비의 경우 취소/무효 처리가 가능합니다. 단순 변심이나 연락 부재(3회 미만)는 취소 사유에 해당하지 않습니다.' 
  },
  { 
    q: '승인된 디비는 수정할 수 있나요?', 
    a: '한 번 승인(확정) 처리된 디비는 시스템 상 직접 수정이나 취소가 불가능합니다. 부득이하게 변경이 필요한 경우 디비 번호와 사유를 기재하여 1:1 문의를 남겨주세요.' 
  },
  { 
    q: '광고비 충전은 얼마나 걸리나요?', 
    a: '무통장 입금의 경우 평일 영업시간 기준 입금 후 30분 이내에 충전 처리됩니다. 주말이나 공휴일 입금 건은 다음 영업일 오전에 일괄 처리됩니다.' 
  },
  { 
    q: 'API 연동은 어떻게 신청하나요?', 
    a: 'API 연동은 별도의 연동 키 발급이 필요합니다. "API 연동 문의" 유형으로 사용하시는 CRM/시스템 종류와 함께 문의를 남겨주시면 담당자가 연동 가이드와 키를 안내해 드립니다.' 
  },
];

export function AdvertiserSupport() {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  return (
    <AdvertiserLayout activeMenu="support" title="문의하기">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          광고상품 운영, 디비 처리, 광고비 충전과 관련된 문의를 남겨주세요.
        </p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 문의" value="12" suffix="건" />
        <SummaryCard title="답변 대기" value="3" suffix="건" highlight color="cyan" />
        <SummaryCard title="답변 완료" value="9" suffix="건" />
        <SummaryCard title="최근 공지" value="2" suffix="건" dark />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* Inquiry Form */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2.5 bg-cyan-100 text-cyan-600 rounded-xl">
              <MessageSquare size={24} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">1:1 문의 작성</h2>
              <p className="text-sm text-slate-500">궁금한 점이나 요청사항을 남겨주시면 빠르게 답변해 드립니다.</p>
            </div>
          </div>

          <form className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">문의 유형 *</label>
                <select className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow">
                  <option value="">유형을 선택하세요</option>
                  <option>광고상품 문의</option>
                  <option>디비 처리 문의</option>
                  <option>취소/무효 문의</option>
                  <option>광고비 충전 문의</option>
                  <option>광고비 차감 문의</option>
                  <option>API 연동 문의</option>
                  <option>시스템 오류</option>
                  <option>기타</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">관련 광고상품 (선택)</label>
                <select className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow">
                  <option value="">상품을 선택하세요</option>
                  <option>개인회생 상담 DB</option>
                  <option>어린이 영어캠프</option>
                  <option>소상공인 대출 상담</option>
                  <option>자동차 렌트 상담</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">제목 *</label>
              <input 
                type="text" 
                placeholder="문의 제목을 입력하세요" 
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">문의 내용 *</label>
              <textarea 
                rows={6}
                placeholder="상세한 문의 내용을 입력해주세요. 디비 처리 관련 문의일 경우 관련 디비 번호를 함께 남겨주시면 빠른 처리가 가능합니다." 
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow resize-none"
              ></textarea>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">관련 디비 번호 (선택)</label>
                <input 
                  type="text" 
                  placeholder="예: DB241007-001" 
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow font-mono"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">첨부파일 (선택)</label>
                <div className="relative">
                  <input type="file" className="hidden" id="file-upload" />
                  <label htmlFor="file-upload" className="flex items-center justify-center w-full px-4 py-3 bg-slate-50 border border-slate-200 border-dashed rounded-xl text-sm text-slate-500 cursor-pointer hover:bg-slate-100 hover:border-slate-300 transition-colors">
                    <Paperclip size={16} className="mr-2" />
                    <span>파일을 선택하거나 드래그하세요</span>
                  </label>
                </div>
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end">
              <button type="button" className="px-8 py-3.5 bg-cyan-600 text-white hover:bg-cyan-700 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 shadow-sm">
                문의 등록하기 <Send size={18} />
              </button>
            </div>
          </form>
        </div>

        {/* FAQ */}
        <div className="bg-slate-50 rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 h-fit">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2.5 bg-white shadow-sm border border-slate-200 text-slate-700 rounded-xl">
              <HelpCircle size={24} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">자주 묻는 질문</h2>
            </div>
          </div>

          <div className="space-y-3">
            {faqs.map((faq, index) => (
              <div 
                key={index} 
                className={`bg-white rounded-xl border transition-colors ${openFaq === index ? 'border-cyan-200 shadow-sm' : 'border-slate-200 hover:border-slate-300'}`}
              >
                <button 
                  onClick={() => setOpenFaq(openFaq === index ? null : index)}
                  className="w-full flex items-center justify-between p-4 text-left"
                >
                  <span className={`text-sm font-bold pr-4 ${openFaq === index ? 'text-cyan-700' : 'text-slate-700'}`}>{faq.q}</span>
                  <ChevronDown size={16} className={`shrink-0 transition-transform ${openFaq === index ? 'rotate-180 text-cyan-500' : 'text-slate-400'}`} />
                </button>
                {openFaq === index && (
                  <div className="px-4 pb-4 pt-1">
                    <p className="text-sm text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100">
                      {faq.a}
                    </p>
                  </div>
                )}
              </div>
            ))}
          </div>

          <div className="mt-8 p-4 bg-blue-50 border border-blue-100 rounded-xl flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
            <div>
              <p className="text-sm text-blue-800 font-medium mb-1">운영시간 안내</p>
              <p className="text-xs text-blue-600 leading-relaxed">
                평일 10:00 - 18:00 (점심시간 12:30 - 13:30)<br/>
                주말 및 공휴일 휴무<br/>
                ※ 운영시간 외 접수된 문의는 다음 영업일에 순차적으로 답변해 드립니다.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Inquiry History */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div className="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
          <div className="p-2 bg-slate-100 rounded-lg text-slate-700">
            <FileText size={20} />
          </div>
          <h2 className="text-lg font-bold text-slate-900">나의 문의 내역</h2>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-4 font-medium whitespace-nowrap">등록일</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">문의 유형</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap min-w-[300px]">제목</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">관련 상품</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap text-center">상태</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">답변일</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap text-center">상세보기</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {inquiries.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                  <td className="px-6 py-4 text-slate-600 font-medium whitespace-nowrap">{item.type}</td>
                  <td className="px-6 py-4 font-bold text-slate-900 truncate max-w-[300px]">
                    <span className="cursor-pointer hover:text-cyan-600 transition-colors">{item.title}</span>
                  </td>
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.campaign}</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={item.status} />
                  </td>
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.repliedAt}</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <button className="p-2 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors">
                      <ChevronRight size={18} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AdvertiserLayout>
  );
}
