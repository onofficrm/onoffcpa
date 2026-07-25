import { useEffect, useState } from 'react';
import { Link, Outlet } from 'react-router-dom';
import { canAccessAdmin, getLcAuth } from '../lib/auth';
import { currentSpaReturnUrl, g5LoginUrl } from '../lib/urls';

export function AdminRouteGuard() {
  const auth = getLcAuth();
  const [redirecting, setRedirecting] = useState(!auth.loggedIn);

  useEffect(() => {
    if (!auth.loggedIn) {
      setRedirecting(true);
      window.location.replace(g5LoginUrl(currentSpaReturnUrl('/admin')));
    }
  }, [auth.loggedIn]);

  if (!auth.loggedIn) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
        <p className="text-slate-600">{redirecting ? '로그인 페이지로 이동 중...' : '로그인이 필요합니다.'}</p>
      </div>
    );
  }

  if (canAccessAdmin()) {
    return <Outlet />;
  }

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
      <div className="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <h1 className="text-2xl font-bold text-slate-900 mb-3">관리자 권한이 필요합니다</h1>
        <p className="text-slate-600 mb-4">
          관리자센터는 슈퍼관리자 또는 링크커넥트 관리자(level 9 이상)만 이용할 수 있습니다.
        </p>
        <div className="mt-6 flex gap-3">
          <Link to="/" className="text-sm text-slate-500 hover:text-cyan-600">홈으로</Link>
          <Link to="/select-center" className="text-sm text-slate-500 hover:text-cyan-600">센터 선택</Link>
        </div>
      </div>
    </div>
  );
}
