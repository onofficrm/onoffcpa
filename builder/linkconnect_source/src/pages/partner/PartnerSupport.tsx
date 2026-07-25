import { useEffect, useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { MessageCircle, Clock, CheckCircle2, Bell, ChevronRight, HelpCircle } from 'lucide-react';
import { createPartnerInquiry, fetchPartnerInquiries, InquiryItem, InquirySummary } from '../../lib/api';

const faqs = [
  { id: 1, question: '승인율은 어디서 확인하나요?' },
  { id: 2, question: '정산 신청은 언제 가능한가요?' },
  { id: 3, question: '취소된 디비는 왜 생기나요?' },
  { id: 4, question: '광고주 코멘트는 어디서 확인하나요?' },
];

const emptySummary: InquirySummary = { total: 0, waiting: 0, processing: 0, closed: 0, today: 0 };

export function PartnerSupport() {
  const [summary, setSummary] = useState<InquirySummary>(emptySummary);
  const [history, setHistory] = useState<InquiryItem[]>([]);
  const [category, setCategory] = useState('');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchPartnerInquiries();
      setSummary(data.summary);
      setHistory(data.items);
    } catch (err) {
      setError(err instanceof Error ? err.message : '문의 내역을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const handleSubmit = async () => {
    if (!category || !subject.trim() || !body.trim()) {
      setError('문의 유형, 제목, 내용을 모두 입력해주세요.');
      return;
    }
    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const result = await createPartnerInquiry({ category, subject, body });
      setMessage(result.message);
      setCategory('');
      setSubject('');
      setBody('');
      setSummary(result.summary);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '문의 등록에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PartnerLayout activeMenu="support" title="문의하기">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">운영, 정산, 광고상품, 취소/무효 처리와 관련된 문의를 남겨주세요.</p>
      </div>

      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 문의" value={summary.total.toLocaleString()} suffix="건" icon={<MessageCircle className="text-slate-500" />} />
        <SummaryCard title="답변 대기" value={summary.waiting.toLocaleString()} suffix="건" icon={<Clock className="text-yellow-500" />} />
        <SummaryCard title="답변 완료" value={summary.closed.toLocaleString()} suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title="오늘 접수" value={summary.today.toLocaleString()} suffix="건" icon={<Bell className="text-blue-500" />} />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 space-y-8">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
            <h2 className="text-lg font-bold text-slate-900 mb-6">1:1 문의 작성</h2>
            <div className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">문의 유형 <span className="text-red-500">*</span></label>
                <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
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
                <input type="text" value={subject} onChange={(e) => setSubject(e.target.value)} className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" placeholder="문의 제목을 입력해주세요" />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">내용 <span className="text-red-500">*</span></label>
                <textarea rows={6} value={body} onChange={(e) => setBody(e.target.value)} className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none" placeholder="상세한 문의 내용을 입력해주세요." />
              </div>
              <div className="pt-4 flex justify-end">
                <button type="button" disabled={submitting} onClick={handleSubmit} className="px-8 py-3.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm disabled:opacity-60">
                  {submitting ? '등록 중...' : '문의 등록하기'}
                </button>
              </div>
            </div>
          </div>

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
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr><td colSpan={4} className="px-5 py-8 text-center text-slate-500">불러오는 중...</td></tr>
                  ) : history.length === 0 ? (
                    <tr><td colSpan={4} className="px-5 py-8 text-center text-slate-500">문의 내역이 없습니다.</td></tr>
                  ) : history.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-5 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                      <td className="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">{item.category}</td>
                      <td className="px-5 py-4 text-slate-900 font-medium min-w-[200px]">{item.title}</td>
                      <td className="px-5 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg sticky top-6">
            <div className="flex items-center gap-2 mb-6">
              <HelpCircle className="text-cyan-400" size={24} />
              <h2 className="text-lg font-bold">자주 묻는 질문</h2>
            </div>
            <div className="space-y-4">
              {faqs.map((faq) => (
                <button key={faq.id} type="button" className="w-full text-left bg-white/10 hover:bg-white/15 transition-colors p-4 rounded-xl flex items-start gap-3 group">
                  <span className="text-cyan-400 font-bold mt-0.5">Q.</span>
                  <span className="text-sm text-slate-200 group-hover:text-white leading-relaxed">{faq.question}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </PartnerLayout>
  );
}
