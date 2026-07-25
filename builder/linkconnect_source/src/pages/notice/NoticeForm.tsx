import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Save, Megaphone } from 'lucide-react';
import { fetchNoticeDetail, fetchNoticeList, saveNotice } from '../../lib/api';
import { getLcAuth } from '../../lib/auth';
import { g5LoginUrl } from '../../lib/urls';

export function NoticeForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const wrId = id ? Number(id) : 0;
  const isEdit = wrId > 0;

  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');
  const [isNotice, setIsNotice] = useState(true);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [forbidden, setForbidden] = useState(false);

  const auth = getLcAuth();

  useEffect(() => {
    if (!auth.loggedIn) return;

    if (!isEdit) {
      let cancelled = false;
      fetchNoticeList({ page: 1, perPage: 1 })
        .then((data) => {
          if (!cancelled && !data.permissions.canWrite) setForbidden(true);
        })
        .catch(() => {});
      return () => {
        cancelled = true;
      };
    }

    let cancelled = false;
    fetchNoticeDetail(wrId)
      .then((data) => {
        if (cancelled) return;
        if (!data.item.canEdit) {
          setForbidden(true);
          return;
        }
        setSubject(data.item.subject);
        setContent(data.item.contentPlain || data.item.contentHtml.replace(/<[^>]+>/g, ''));
        setIsNotice(data.item.isNotice);
      })
      .catch((err) => {
        if (!cancelled) setError(err instanceof Error ? err.message : '불러오지 못했습니다.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [auth.loggedIn, isEdit, wrId]);

  if (!auth.loggedIn) {
    return (
      <div className="bg-slate-50 min-h-screen pt-32 pb-16">
        <div className="max-w-xl mx-auto px-4 text-center">
          <p className="text-slate-600 mb-6">글쓰기는 로그인 후 이용할 수 있습니다.</p>
          <a href={g5LoginUrl(isEdit ? `/notice/${wrId}/edit` : '/notice/write')} className="inline-flex px-6 py-3 rounded-xl bg-slate-900 text-white font-bold">
            로그인
          </a>
        </div>
      </div>
    );
  }

  if (forbidden) {
    return (
      <div className="bg-slate-50 min-h-screen pt-32 pb-16">
        <div className="max-w-xl mx-auto px-4 text-center">
          <p className="text-slate-600 mb-6">공지사항 작성·수정 권한이 없습니다.</p>
          <Link to="/notice" className="text-cyan-600 font-bold">목록으로</Link>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="bg-slate-50 min-h-screen pt-32 pb-16 flex items-center justify-center">
        <p className="text-slate-400">불러오는 중…</p>
      </div>
    );
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!subject.trim() || !content.trim()) {
      setError('제목과 내용을 입력해주세요.');
      return;
    }

    setSubmitting(true);
    setError('');
    try {
      const result = await saveNotice({
        action: isEdit ? 'update' : 'save',
        id: isEdit ? wrId : undefined,
        subject: subject.trim(),
        content: content.trim(),
        isNotice,
      });
      navigate(`/notice/${result.item.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : '저장에 실패했습니다.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="bg-slate-50 min-h-screen pt-32 pb-16">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link to={isEdit ? `/notice/${wrId}` : '/notice'} className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-cyan-600 mb-6 transition-colors">
          <ArrowLeft size={16} /> {isEdit ? '글 보기' : '목록으로'}
        </Link>

        <div className="mb-8">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-50 text-cyan-700 text-xs font-bold mb-3 border border-cyan-100">
            <Megaphone size={14} /> {isEdit ? '공지 수정' : '공지 작성'}
          </div>
          <h1 className="text-2xl md:text-3xl font-bold text-slate-900">{isEdit ? '공지사항 수정' : '공지사항 작성'}</h1>
        </div>

        <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-6 md:p-8 space-y-6">
            <div>
              <label htmlFor="notice-subject" className="block text-sm font-bold text-slate-700 mb-2">제목</label>
              <input
                id="notice-subject"
                type="text"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                maxLength={255}
                placeholder="공지 제목을 입력하세요"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-400"
              />
            </div>

            <div>
              <label htmlFor="notice-content" className="block text-sm font-bold text-slate-700 mb-2">내용</label>
              <textarea
                id="notice-content"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                rows={14}
                placeholder="공지 내용을 입력하세요"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-400 resize-y min-h-[280px]"
              />
            </div>

            <label className="flex items-center gap-3 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={isNotice}
                onChange={(e) => setIsNotice(e.target.checked)}
                className="w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
              />
              <span className="text-sm font-medium text-slate-700">상단 공지로 고정</span>
            </label>

            {error && (
              <p className="text-sm text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-4 py-3">{error}</p>
            )}
          </div>

          <div className="px-6 md:px-8 py-5 border-t border-slate-100 bg-slate-50/60 flex flex-wrap gap-3 justify-end">
            <Link
              to={isEdit ? `/notice/${wrId}` : '/notice'}
              className="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-white transition-colors"
            >
              취소
            </Link>
            <button
              type="submit"
              disabled={submitting}
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition-colors disabled:opacity-60"
            >
              <Save size={16} /> {submitting ? '저장 중…' : isEdit ? '수정하기' : '등록하기'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
