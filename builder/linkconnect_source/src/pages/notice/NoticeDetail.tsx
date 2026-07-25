import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, ChevronLeft, ChevronRight, Edit3, Trash2, Megaphone, Eye, Calendar, User } from 'lucide-react';
import { deleteNotice, fetchNoticeDetail, NoticeDetail } from '../../lib/api';

export function NoticeDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const wrId = Number(id ?? '0');

  const [item, setItem] = useState<NoticeDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!wrId) {
      setLoading(false);
      setError('잘못된 접근입니다.');
      return;
    }

    let cancelled = false;
    setLoading(true);
    setError('');

    fetchNoticeDetail(wrId)
      .then((data) => {
        if (!cancelled) setItem(data.item);
      })
      .catch((err) => {
        if (!cancelled) setError(err instanceof Error ? err.message : '공지사항을 불러오지 못했습니다.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [wrId]);

  const handleDelete = async () => {
    if (!item || !window.confirm('이 공지사항을 삭제하시겠습니까?')) return;
    setDeleting(true);
    try {
      await deleteNotice(item.id);
      navigate('/notice');
    } catch (err) {
      alert(err instanceof Error ? err.message : '삭제에 실패했습니다.');
    } finally {
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="bg-slate-50 min-h-screen pt-32 pb-16 flex items-center justify-center">
        <p className="text-slate-400">불러오는 중…</p>
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className="bg-slate-50 min-h-screen pt-32 pb-16">
        <div className="max-w-3xl mx-auto px-4 text-center">
          <p className="text-slate-600 mb-6">{error || '공지사항을 찾을 수 없습니다.'}</p>
          <Link to="/notice" className="text-cyan-600 font-bold hover:underline">목록으로</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-slate-50 min-h-screen pt-32 pb-16">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link to="/notice" className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-cyan-600 mb-6 transition-colors">
          <ArrowLeft size={16} /> 공지사항 목록
        </Link>

        <article className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <header className="p-6 md:p-8 border-b border-slate-100">
            <div className="flex flex-wrap items-center gap-2 mb-4">
              {item.isNotice && (
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold border border-amber-200">
                  <Megaphone size={12} /> 공지
                </span>
              )}
            </div>
            <h1 className="text-2xl md:text-3xl font-bold text-slate-900 leading-snug mb-5">{item.subject}</h1>
            <div className="flex flex-wrap items-center gap-4 text-sm text-slate-500">
              <span className="inline-flex items-center gap-1.5"><User size={15} className="text-slate-400" /> {item.author}</span>
              <span className="inline-flex items-center gap-1.5"><Calendar size={15} className="text-slate-400" /> {item.date}</span>
              <span className="inline-flex items-center gap-1.5"><Eye size={15} className="text-slate-400" /> {item.hit}</span>
            </div>
          </header>

          <div
            className="p-6 md:p-8 prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-cyan-600 prose-img:rounded-xl leading-relaxed text-slate-700"
            dangerouslySetInnerHTML={{ __html: item.contentHtml }}
          />

          <footer className="px-6 md:px-8 py-5 border-t border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-3">
            <div className="flex gap-2">
              {item.prevId > 0 && (
                <Link
                  to={`/notice/${item.prevId}`}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-white transition-colors"
                >
                  <ChevronLeft size={16} /> 이전글
                </Link>
              )}
              {item.nextId > 0 && (
                <Link
                  to={`/notice/${item.nextId}`}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-white transition-colors"
                >
                  다음글 <ChevronRight size={16} />
                </Link>
              )}
            </div>
            <div className="flex gap-2">
              {item.canEdit && (
                <Link
                  to={`/notice/${item.id}/edit`}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-sm font-bold transition-colors"
                >
                  <Edit3 size={15} /> 수정
                </Link>
              )}
              {item.canDelete && (
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={deleting}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-bold transition-colors disabled:opacity-60"
                >
                  <Trash2 size={15} /> {deleting ? '삭제 중…' : '삭제'}
                </button>
              )}
            </div>
          </footer>
        </article>
      </div>
    </div>
  );
}
