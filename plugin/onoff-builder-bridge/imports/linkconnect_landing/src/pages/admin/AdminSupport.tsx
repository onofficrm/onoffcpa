import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  MessageSquare, Clock, CheckCircle2, User, HelpCircle, 
  Search, Calendar, ChevronDown, Check, X, FileText, Settings2, ShieldQuestion,
  CornerDownRight, Paperclip, CheckCheck
} from 'lucide-react';

const supportData = [
  { id: 'Q-260706-05', date: '2026.07.06 14:30', type: '파트너', author: '김파트너 (P-10023)', category: '정산 문의', title: '정산 계좌를 변경하고 싶습니다.', campaign: '-', dbCode: '-', status: '답변대기', replyDate: '-', content: '안녕하세요. 이번 달부터 정산 받는 계좌를 국민은행에서 신한은행으로 변경하고 싶습니다. 마이페이지에서 수정하면 이번 정산부터 바로 적용되는지 궁금합니다.' },
  { id: 'Q-260706-04', date: '2026.07.06 13:15', type: '광고주', author: '희망법무법인 (A-20011)', category: '광고비 문의', title: '세금계산서 발행 시기 문의', campaign: '-', dbCode: '-', status: '처리중', replyDate: '-', content: '오늘 500만원 충전했는데 세금계산서는 언제 발행되나요? 빠른 발행 부탁드립니다.' },
  { id: 'Q-260706-02', date: '2026.07.06 11:20', type: '파트너', author: '리드젠 (P-10112)', category: '취소/무효 문의', title: '이 디비가 왜 취소되었는지 확인 부탁드립니다.', campaign: '개인회생 무료상담 이벤트', dbCode: 'DB-260705-089', status: '접수완료', replyDate: '-', content: '어제 인입된 DB인데 결번으로 취소되었습니다. 저희 쪽 녹취록에는 정상 통화가 되었는데 다시 확인 부탁드립니다.', hasAttachment: true },
  { id: 'Q-260705-12', date: '2026.07.05 16:45', type: '광고주', author: '(주)성공대부 (A-20045)', category: 'API 연동 문의', title: 'API 호출 시 오류가 발생합니다.', campaign: '-', dbCode: '-', status: '답변완료', replyDate: '2026.07.06 09:30', content: '저희 쪽 랜딩페이지에서 DB 연동을 테스트하고 있는데 계속 400 에러가 납니다. 첨부한 요청 로그 확인 부탁드립니다.', reply: '안녕하세요. 확인해본 결과 요청 Body에 필수 파라미터인 "phone" 항목이 누락되었습니다. 연동 가이드 문서를 참고하여 파라미터를 다시 확인해주시기 바랍니다.' },
  { id: 'Q-260705-08', date: '2026.07.05 14:10', type: '파트너', author: '박마케터 (P-10008)', category: '수익 문의', title: '어제 발생한 수익이 아직 안 들어왔어요.', campaign: '종합건강검진 할인 프로모션', dbCode: '-', status: '보류', replyDate: '-', content: '어제 분명히 DB 5건 접수되었는데 오늘 수익 내역에 하나도 안보입니다. 확인 좀 해주세요.' },
];

export function AdminSupport() {
  const [selectedInquiry, setSelectedInquiry] = useState<string | null>(null);
  
  const activeInquiry = supportData.find(q => q.id === selectedInquiry);

  return (
    <AdminLayout activeMenu="support" title="문의 관리" description="파트너와 광고주의 문의를 확인하고 답변을 관리하세요.">
      
      {/* 5 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="전체 문의" value="1,284" suffix="건" dark icon={<MessageSquare size={18} />} />
        <SummaryCard title="답변 대기" value="15" suffix="건" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="처리중" value="8" suffix="건" color="blue" highlight icon={<ShieldQuestion size={18} />} />
        <SummaryCard title="답변 완료" value="1,250" suffix="건" color="emerald" icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="오늘 접수" value="32" suffix="건" color="cyan" icon={<Calendar size={18} />} />
      </div>

      <div className="flex flex-col lg:flex-row gap-6 mb-8">
        
        {/* Main List */}
        <div className={`flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${selectedInquiry ? 'hidden lg:block lg:w-2/3' : 'w-full'}`}>
          <div className="p-6">
            
            {/* Filters */}
            <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="제목, 작성자 검색" 
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              
              <div className="flex flex-wrap items-center gap-2">
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 구분</option>
                  <option>파트너</option>
                  <option>광고주</option>
                </select>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 유형</option>
                  <option>광고상품 문의</option>
                  <option>디비 처리 문의</option>
                  <option>수익/정산 문의</option>
                  <option>광고비 문의</option>
                  <option>API 연동 문의</option>
                </select>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>상태 전체</option>
                  <option>접수완료</option>
                  <option>답변대기</option>
                  <option>처리중</option>
                  <option>답변완료</option>
                </select>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">접수일</th>
                    <th className="px-4 py-3 font-medium text-center">구분</th>
                    <th className="px-4 py-3 font-medium">작성자</th>
                    <th className="px-4 py-3 font-medium">유형</th>
                    <th className="px-4 py-3 font-medium">제목</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">답변일</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {supportData.map((item, i) => (
                    <tr 
                      key={i} 
                      className={`transition-colors cursor-pointer ${selectedInquiry === item.id ? 'bg-cyan-50' : 'hover:bg-slate-50'}`}
                      onClick={() => setSelectedInquiry(item.id)}
                    >
                      <td className="px-4 py-4 whitespace-nowrap text-slate-600 font-medium">
                        {item.date}
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${item.type === '파트너' ? 'bg-indigo-100 text-indigo-700' : 'bg-fuchsia-100 text-fuchsia-700'}`}>
                          {item.type}
                        </span>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="font-bold text-slate-900">{item.author.split(' ')[0]}</div>
                        <div className="text-xs text-slate-500">{item.author.split(' ')[1]}</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <span className="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded">
                          {item.category}
                        </span>
                      </td>
                      <td className="px-4 py-4">
                        <div className="font-medium text-slate-900 truncate max-w-[200px] flex items-center gap-1.5">
                          {item.title}
                          {item.hasAttachment && <Paperclip size={12} className="text-slate-400 shrink-0" />}
                        </div>
                        {item.campaign !== '-' && <div className="text-[10px] text-slate-500 mt-0.5 truncate max-w-[200px]">{item.campaign}</div>}
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap text-slate-500 text-xs font-medium">
                        {item.replyDate}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

          </div>
        </div>

        {/* Details Panel */}
        {activeInquiry && (
          <div className="lg:w-1/3 w-full flex flex-col gap-4">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-120px)] sticky top-6">
              
              <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 className="font-bold text-slate-900 flex items-center gap-2">
                  <HelpCircle size={18} className="text-slate-400" />
                  문의 상세 및 답변
                </h3>
                <button 
                  onClick={() => setSelectedInquiry(null)}
                  className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="p-5 overflow-y-auto flex-1 space-y-6">
                
                {/* 1. 문의 내용 */}
                <section>
                  <div className="flex items-center gap-2 mb-3">
                    <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${activeInquiry.type === '파트너' ? 'bg-indigo-100 text-indigo-700' : 'bg-fuchsia-100 text-fuchsia-700'}`}>
                      {activeInquiry.type}
                    </span>
                    <span className="text-sm font-bold text-slate-900">{activeInquiry.author}</span>
                  </div>
                  
                  <div className="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <div className="flex items-center gap-2 mb-2">
                      <span className="text-xs font-bold text-cyan-700 bg-cyan-100 px-2 py-0.5 rounded">{activeInquiry.category}</span>
                      <span className="text-xs text-slate-400 font-medium">{activeInquiry.date}</span>
                    </div>
                    <h4 className="font-bold text-slate-900 text-base mb-3">{activeInquiry.title}</h4>
                    <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                      {activeInquiry.content}
                    </p>
                    
                    {activeInquiry.hasAttachment && (
                      <div className="mt-4 pt-3 border-t border-slate-200">
                        <div className="flex items-center gap-2 p-2 bg-white rounded border border-slate-200 w-fit cursor-pointer hover:bg-slate-50 transition-colors">
                          <Paperclip size={14} className="text-slate-500" />
                          <span className="text-xs font-medium text-slate-700">screenshot_2026.png</span>
                        </div>
                      </div>
                    )}
                  </div>
                  
                  {activeInquiry.campaign !== '-' && (
                    <div className="mt-3 flex gap-4 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                      <div>
                        <span className="text-slate-500 mr-2">관련 상품:</span>
                        <span className="font-medium text-slate-800">{activeInquiry.campaign}</span>
                      </div>
                      {activeInquiry.dbCode !== '-' && (
                        <div>
                          <span className="text-slate-500 mr-2">관련 디비:</span>
                          <span className="font-medium text-slate-800">{activeInquiry.dbCode}</span>
                        </div>
                      )}
                    </div>
                  )}
                </section>

                {/* 2. 답변 영역 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <CornerDownRight size={16} className="text-cyan-600" /> 답변 등록
                  </h4>
                  
                  {activeInquiry.reply ? (
                    <div className="bg-cyan-50 rounded-xl p-4 border border-cyan-100">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-cyan-800">관리자 답변</span>
                        <span className="text-xs text-cyan-600 font-medium">{activeInquiry.replyDate}</span>
                      </div>
                      <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                        {activeInquiry.reply}
                      </p>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      <div>
                        <textarea 
                          placeholder="답변 내용을 작성해주세요. (고객에게 노출됩니다)"
                          className="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm h-32 resize-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                        ></textarea>
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-slate-500 mb-1.5">내부 메모 (관리자 전용)</label>
                        <input 
                          type="text"
                          placeholder="담당자 확인용 메모"
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-slate-300 focus:outline-none"
                        />
                      </div>
                      <div className="flex items-center gap-2">
                        <select className="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium focus:border-cyan-500 focus:outline-none">
                          <option>상태: 답변대기</option>
                          <option>상태: 처리중</option>
                          <option>상태: 보류</option>
                        </select>
                        <button className="flex-[2] py-2 bg-slate-900 text-white rounded-lg text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm flex items-center justify-center gap-1.5">
                          <CheckCheck size={16} /> 답변 등록 및 완료
                        </button>
                      </div>
                    </div>
                  )}
                </section>
                
              </div>
            </div>
          </div>
        )}

      </div>
      
      {/* FAQ Management Mini Card */}
      {!selectedInquiry && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
              <Settings2 className="text-slate-600" size={24} />
            </div>
            <div>
              <h3 className="font-bold text-slate-900">FAQ (자주 묻는 질문) 관리</h3>
              <p className="text-sm text-slate-500">고객센터에 노출될 카테고리와 질문을 설정하세요.</p>
            </div>
          </div>
          <button className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm whitespace-nowrap">
            FAQ 설정 바로가기
          </button>
        </div>
      )}

    </AdminLayout>
  );
}
