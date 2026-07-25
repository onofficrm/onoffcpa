import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Megaphone, Search, ChevronLeft, ChevronRight, Pin, Plus, Eye } from 'lucide-react';
import { fetchNoticeList, NoticeListItem } from '../../lib/api';
import { getLcAuth } from '../../lib/auth';
import { g5LoginUrl } from '../../lib/urls';

export function NoticeList() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Math.max(1, Number(searchParams.get('page') ?? '1') || 1);
  const qParam = searchParams.get('q') ?? '';

  const [searchQuery, setSearchQuery] = useState(qParam);
  const [items, setItems] = useState<NoticeListItem[]>([]);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const [canWrite, setCanWrite] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [boardReady, setBoardReady] = useState(true);

  useEffect(() => {
    setSearchQuery(qParam);
  }, [qParam]);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      setLoading(true);
      setError('');
      try {
        const data = await fetchNoticeList({ page, q: qParam });
        if (cancelled) return;
        setItems(data.items);
        setTotal(data.total);
        setTotalPages(data.totalPages);
        setCanWrite(data.permissions.canWrite);
        setBoardReady(data.boardReady);
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : '공지사항을 불러오지 못했습니다.');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => {
      cancelled = true;
    };
  }, [page, qParam]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const next = new URLSearchParams();
    if (searchQuery.trim()) next.set('q', searchQuery.trim());
    next.set('page', '1');
    setSearchParams(next);
  };

  const goPage = (nextPage: number) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', String(nextPage));
    setSearchParams(next);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const auth = getLcAuth();

  return (
    <div className="bg-slate-50 min-h-screen pt-32 pb-16">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-10">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-50 text-cyan-700 text-xs font-bold mb-4 border border-cyan-100">
            <Megaphone size={14} /> 회사소개
          </div>
          <h1 className="text-3xl md:text-4xl font-bold text-slate-900 mb-3">공지사항</h1>
          <p className="text-slate-600 text-lg max-w-2xl">
            링크커넥트의 새로운 소식, 업데이트, 운영 안내를 확인하세요.
          </p>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-4 md:p-5 border-b border-slate-100 flex flex-col md:flex-row gap-3 md:items-center md:justify-between bg-slate-50/80">
            <form onSubmit={handleSearch} className="relative flex-1 max-w-md">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="search"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="제목·내용 검색"
                className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-400"
              />
            </form>
            <div className="flex items-center gap-3">
              <span className="text-sm text-slate-500">총 <strong className="text-slate-800">{total}</strong>건</span>
              {canWrite ? (
                <Link
                  to="/notice/write"
                  className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition-colors"
                >
                  <Plus size={16} /> 글쓰기
                </Link>
              ) : !auth.loggedIn ? (
                <a
                  href={g5LoginUrl('/notice')}
                  className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-white text-sm font-bold transition-colors"
                >
                  로그인
                </a>
              ) : null}
            </div>
          </div>

          {!boardReady && (
            <div className="p-10 text-center text-slate-500">
              공지 게시판이 준비되지 않았습니다. 관리자에게 문의해주세요.
            </div>
          )}

          {boardReady && loading && (
            <div className="p-16 text-center text-slate-400">불러오는 중…</div>
          )}

          {boardReady && !loading && error && (
            <div className="p-10 text-center text-rose-600">{error}</div>
          )}

          {boardReady && !loading && !error && items.length === 0 && (
            <div className="p-16 text-center">
              <Megaphone size={40} className="mx-auto text-slate-300 mb-4" />
              <p className="text-slate-500 font-medium">등록된 공지사항이 없습니다.</p>
            </div>
          )}

          {boardReady && !loading && !error && items.length > 0 && (
            <>
              <div className="hidden md:grid grid-cols-[1fr_120px_100px_80px] gap-4 px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide border-b border-slate-100 bg-white">
                <span>제목</span>
                <span className="text-center">작성자</span>
                <span className="text-center">날짜</span>
                <span className="text-center">조회</span>
              </div>

              <ul className="divide-y divide-slate-100">
                {items.map((item) => (
                  <li key={item.id}>
                    <Link
                      to={`/notice/${item.id}`}
                      className={`block px-4 md:px-5 py-4 md:py-4 hover:bg-cyan-50/40 transition-colors group ${item.isNotice ? 'bg-amber-50/30' : ''}`}
                    >
                      <div className="md:grid md:grid-cols-[1fr_120px_100px_80px] md:gap-4 md:items-center">
                        <div className="flex items-start gap-2 min-w-0 mb-2 md:mb-0">
                          {item.isNotice && (
                            <span className="inline-flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 text-[11px] font-bold border border-amber-200">
                              <Pin size={11} /> 공지
                            </span>
                          )}
                          <span className="font-semibold text-slate-900 group-hover:text-cyan-700 transition-colors line-clamp-2 md:truncate">
                            {item.subject}
                          </span>
                        </div>
                        <span className="text-sm text-slate-500 md:text-center">{item.author}</span>
                        <span className="text-sm text-slate-400 md:text-center">{item.date}</span>
                        <span className="hidden md:flex items-center justify-center gap-1 text-sm text-slate-400">
                          <Eye size={14} /> {item.hit}
                        </span>
                      </div>
                    </Link>
                  </li>
                ))}
              </ul>
            </>
          )}

          {boardReady && totalPages > 1 && (
            <div className="flex items-center justify-center gap-2 p-5 border-t border-slate-100 bg-slate-50/50">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => goPage(page - 1)}
                className="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <ChevronLeft size={18} />
              </button>
              {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                let num = i + 1;
                if (totalPages > 5) {
                  const start = Math.max(1, Math.min(page - 2, totalPages - 4));
                  num = start + i;
                }
                return (
                  <button
                    key={num}
                    type="button"
                    onClick={() => goPage(num)}
                    className={`min-w-[2.25rem] h-9 rounded-lg text-sm font-bold transition-colors ${
                      num === page
                        ? 'bg-slate-900 text-white'
                        : 'border border-slate-200 text-slate-600 hover:bg-white'
                    }`}
                  >
                    {num}
                  </button>
                );
              })}
              <button
                type="button"
                disabled={page >= totalPages}
                onClick={() => goPage(page + 1)}
                className="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <ChevronRight size={18} />
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
