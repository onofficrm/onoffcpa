import { FormEvent, useEffect, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { HelpCircle, MessageSquare, ChevronDown, Send } from 'lucide-react';
import { createMerchantInquiry, fetchMerchantInquiries, InquiryItem, InquirySummary } from '../../lib/api';

const faqs = [
  { q: '광고비는 언제 차감되나요?', a: '디비 접수 시 가차감되며, 승인 시 확정 차감됩니다. 취소/무효 시 환급됩니다.' },
  { q: '취소/무효 처리 기준은 무엇인가요?', a: '결번, 중복접수, 장난/테스트 등 명백한 허위 디비의 경우 처리 가능합니다.' },
  { q: 'API 연동은 어떻게 신청하나요?', a: '"API 연동 문의" 유형으로 문의를 남겨주시면 연동 가이드를 안내해 드립니다.' },
];

const emptySummary: InquirySummary = { total: 0, waiting: 0, processing: 0, closed: 0, today: 0 };

export function AdvertiserSupport() {
  const [summary, setSummary] = useState<InquirySummary>(emptySummary);
  const [items, setItems] = useState<InquiryItem[]>([]);
  const [category, setCategory] = useState('');
  const [campaign, setCampaign] = useState('');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [openFaq, setOpenFaq] = useState<number | null>(0);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchMerchantInquiries();
      setSummary(data.summary);
      setItems(data.items);
    } catch (err) {
      setError(err instanceof Error ? err.message : '문의 내역을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!category || !subject.trim() || !body.trim()) {
      setError('문의 유형, 제목, 내용을 모두 입력해주세요.');
      return;
    }
    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const result = await createMerchantInquiry({ category, subject, body, campaign });
      setMessage(result.message);
      setCategory('');
      setCampaign('');
      setSubject('');
      setBody('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '문의 등록에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AdvertiserLayout activeMenu="support" title="문의하기">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">광고상품 운영, 디비 처리, 광고비 충전과 관련된 문의를 남겨주세요.</p>
      </div>

      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 문의" value={summary.total.toLocaleString()} suffix="건" />
        <SummaryCard title="답변 대기" value={summary.waiting.toLocaleString()} suffix="건" highlight color="cyan" />
        <SummaryCard title="답변 완료" value={summary.closed.toLocaleString()} suffix="건" />
        <SummaryCard title="오늘 접수" value={summary.today.toLocaleString()} suffix="건" dark />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
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

          <form className="space-y-6" onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">문의 유형 *</label>
                <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500">
                  <option value="">유형을 선택하세요</option>
                  <option>광고상품 문의</option>
                  <option>디비 처리 문의</option>
                  <option>취소/무효 문의</option>
                  <option>광고비 충전 문의</option>
                  <option>API 연동 문의</option>
                  <option>시스템 오류</option>
                  <option>기타</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">관련 광고상품 (선택)</label>
                <input type="text" value={campaign} onChange={(e) => setCampaign(e.target.value)} className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500" placeholder="캠페인명 입력" />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">제목 *</label>
              <input type="text" value={subject} onChange={(e) => setSubject(e.target.value)} placeholder="문의 제목을 입력하세요" className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">문의 내용 *</label>
              <textarea rows={6} value={body} onChange={(e) => setBody(e.target.value)} placeholder="상세한 문의 내용을 입력해주세요." className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 resize-none" />
            </div>
            <div className="flex justify-end">
              <button type="submit" disabled={submitting} className="px-8 py-3.5 bg-cyan-600 text-white font-bold rounded-xl hover:bg-cyan-500 transition-colors shadow-sm flex items-center gap-2 disabled:opacity-60">
                <Send size={18} /> {submitting ? '등록 중...' : '문의 등록하기'}
              </button>
            </div>
          </form>
        </div>

        <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex items-center gap-2 mb-6">
            <HelpCircle className="text-cyan-400" size={24} />
            <h2 className="text-lg font-bold">자주 묻는 질문</h2>
          </div>
          <div className="space-y-3">
            {faqs.map((faq, i) => (
              <div key={faq.q} className="border border-white/10 rounded-xl overflow-hidden">
                <button type="button" onClick={() => setOpenFaq(openFaq === i ? null : i)} className="w-full px-4 py-3 text-left flex items-center justify-between gap-2 hover:bg-white/5">
                  <span className="text-sm font-medium">{faq.q}</span>
                  <ChevronDown size={16} className={`shrink-0 transition-transform ${openFaq === i ? 'rotate-180' : ''}`} />
                </button>
                {openFaq === i && <div className="px-4 pb-4 text-sm text-slate-300 leading-relaxed">{faq.a}</div>}
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-slate-100 bg-slate-50">
          <h2 className="text-lg font-bold text-slate-900">나의 문의 내역</h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-4 font-medium whitespace-nowrap">접수일</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">문의 유형</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">제목</th>
                <th className="px-6 py-4 font-medium text-center whitespace-nowrap">상태</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">답변일</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr><td colSpan={5} className="px-6 py-8 text-center text-slate-500">불러오는 중...</td></tr>
              ) : items.length === 0 ? (
                <tr><td colSpan={5} className="px-6 py-8 text-center text-slate-500">문의 내역이 없습니다.</td></tr>
              ) : items.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                  <td className="px-6 py-4 whitespace-nowrap">{item.category}</td>
                  <td className="px-6 py-4 font-medium text-slate-900">{item.title}</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap"><StatusBadge status={item.status} /></td>
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.replyDate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AdvertiserLayout>
  );
}
