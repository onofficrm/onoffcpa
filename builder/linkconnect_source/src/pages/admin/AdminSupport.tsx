import { useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { MessageSquare, Clock, CheckCircle2, HelpCircle, Search, CheckCheck, X, CornerDownRight } from 'lucide-react';
import { fetchAdminInquiries, fetchAdminInquiryDetail, InquiryItem, InquirySummary, updateAdminInquiry } from '../../lib/api';

const emptySummary: InquirySummary = { total: 0, waiting: 0, processing: 0, closed: 0, today: 0 };

export function AdminSupport() {
  const [summary, setSummary] = useState<InquirySummary>(emptySummary);
  const [items, setItems] = useState<InquiryItem[]>([]);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<InquiryItem | null>(null);
  const [reply, setReply] = useState('');
  const [adminMemo, setAdminMemo] = useState('');
  const [status, setStatus] = useState('processing');
  const [centerFilter, setCenterFilter] = useState('');
  const [q, setQ] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchAdminInquiries({
        center: centerFilter === 'partner' ? 'partner' : centerFilter === 'merchant' ? 'merchant' : '',
        q,
      });
      setSummary(data.summary);
      setItems(data.items);
    } catch (err) {
      setError(err instanceof Error ? err.message : '문의 목록을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const openDetail = async (item: InquiryItem) => {
    setSelectedId(item.iqId);
    setReply('');
    setAdminMemo('');
    setStatus('processing');
    try {
      const data = await fetchAdminInquiryDetail(item.iqId);
      setDetail(data.item);
      setReply(data.item.reply || '');
      setAdminMemo(data.item.adminMemo || '');
    } catch {
      setDetail(item);
    }
  };

  const handleReply = async () => {
    if (!selectedId) return;
    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const result = await updateAdminInquiry({
        iqId: selectedId,
        action: reply.trim() ? 'reply' : 'status',
        reply,
        status,
        adminMemo,
      });
      setMessage(result.message);
      setDetail(result.item);
      setSummary(result.summary);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '답변 등록에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AdminLayout activeMenu="support" title="문의 관리" description="파트너와 광고주의 문의를 확인하고 답변을 관리하세요.">
      {(error || message) && (
        <div className={`mb-6 rounded-xl border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}>
          {error || message}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="전체 문의" value={summary.total.toLocaleString()} suffix="건" dark icon={<MessageSquare size={18} />} />
        <SummaryCard title="답변 대기" value={summary.waiting.toLocaleString()} suffix="건" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="처리중" value={summary.processing.toLocaleString()} suffix="건" color="blue" highlight icon={<HelpCircle size={18} />} />
        <SummaryCard title="답변 완료" value={summary.closed.toLocaleString()} suffix="건" color="emerald" icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="오늘 접수" value={summary.today.toLocaleString()} suffix="건" color="cyan" icon={<Clock size={18} />} />
      </div>

      <div className="flex flex-col lg:flex-row gap-6 mb-8">
        <div className={`flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${selectedId ? 'hidden lg:block lg:w-2/3' : 'w-full'}`}>
          <div className="p-6">
            <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
              <div className="flex-1 min-w-[200px] relative">
                <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="제목, 작성자 검색" className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500" />
              </div>
              <select value={centerFilter} onChange={(e) => setCenterFilter(e.target.value)} className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                <option value="">전체 구분</option>
                <option value="partner">파트너</option>
                <option value="merchant">광고주</option>
              </select>
              <button type="button" onClick={load} className="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold">조회</button>
            </div>

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
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">불러오는 중...</td></tr>
                  ) : items.length === 0 ? (
                    <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">문의가 없습니다.</td></tr>
                  ) : items.map((item) => (
                    <tr key={item.id} className={`cursor-pointer transition-colors ${selectedId === item.iqId ? 'bg-cyan-50' : 'hover:bg-slate-50'}`} onClick={() => openDetail(item)}>
                      <td className="px-4 py-4 whitespace-nowrap text-slate-600">{item.date}</td>
                      <td className="px-4 py-4 text-center whitespace-nowrap"><span className="text-[10px] font-bold px-2 py-1 rounded-full bg-slate-100">{item.center}</span></td>
                      <td className="px-4 py-4 whitespace-nowrap font-bold text-slate-900">{item.author}</td>
                      <td className="px-4 py-4 whitespace-nowrap"><span className="text-xs bg-slate-100 px-2 py-1 rounded">{item.category}</span></td>
                      <td className="px-4 py-4 font-medium text-slate-900 truncate max-w-[200px]">{item.title}</td>
                      <td className="px-4 py-4 text-center whitespace-nowrap"><StatusBadge status={item.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {detail && selectedId && (
          <div className="lg:w-1/3 w-full">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden sticky top-6">
              <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 className="font-bold text-slate-900 flex items-center gap-2"><HelpCircle size={18} /> 문의 상세 및 답변</h3>
                <button type="button" onClick={() => { setSelectedId(null); setDetail(null); }} className="p-1.5 text-slate-400 hover:text-slate-600"><X size={18} /></button>
              </div>
              <div className="p-5 space-y-6 max-h-[calc(100vh-120px)] overflow-y-auto">
                <section>
                  <div className="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <div className="text-xs text-cyan-700 bg-cyan-100 inline-block px-2 py-0.5 rounded mb-2">{detail.category}</div>
                    <h4 className="font-bold text-slate-900 mb-2">{detail.title}</h4>
                    <p className="text-sm text-slate-700 whitespace-pre-line">{detail.content || '-'}</p>
                  </div>
                </section>
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2"><CornerDownRight size={16} className="text-cyan-600" /> 답변 등록</h4>
                  {detail.statusCode === 'closed' && detail.reply ? (
                    <div className="bg-cyan-50 rounded-xl p-4 border border-cyan-100 text-sm text-slate-700 whitespace-pre-line">{detail.reply}</div>
                  ) : (
                    <div className="space-y-4">
                      <textarea value={reply} onChange={(e) => setReply(e.target.value)} placeholder="답변 내용을 작성해주세요." className="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm h-32 resize-none" />
                      <input value={adminMemo} onChange={(e) => setAdminMemo(e.target.value)} placeholder="내부 메모 (관리자 전용)" className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                      <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="waiting">답변대기</option>
                        <option value="processing">처리중</option>
                        <option value="hold">보류</option>
                        <option value="closed">답변완료</option>
                      </select>
                      <button type="button" disabled={submitting} onClick={handleReply} className="w-full py-2 bg-slate-900 text-white rounded-lg text-sm font-bold flex items-center justify-center gap-1.5 disabled:opacity-60">
                        <CheckCheck size={16} /> {submitting ? '처리 중...' : '답변 등록 및 완료'}
                      </button>
                    </div>
                  )}
                </section>
              </div>
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
