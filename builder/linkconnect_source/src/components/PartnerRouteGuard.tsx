import { useEffect, useState } from 'react';
import { Link, Outlet } from 'react-router-dom';
import { applyPartner } from '../lib/api';
import { canAccessPartnerCenter, getLcAuth } from '../lib/auth';
import { currentSpaReturnUrl, g5LoginUrl } from '../lib/urls';

export function PartnerRouteGuard() {
  const auth = getLcAuth();
  const [applying, setApplying] = useState(false);
  const [applyError, setApplyError] = useState('');
  const [redirecting, setRedirecting] = useState(!auth.loggedIn);

  useEffect(() => {
    if (!auth.loggedIn) {
      setRedirecting(true);
      window.location.replace(g5LoginUrl(currentSpaReturnUrl('/partner')));
    }
  }, [auth.loggedIn]);

  if (!auth.loggedIn) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
        <p className="text-slate-600">{redirecting ? '로그인 페이지로 이동 중...' : '로그인이 필요합니다.'}</p>
      </div>
    );
  }

  if (canAccessPartnerCenter()) {
    return <Outlet />;
  }

  const handleApply = async () => {
    setApplying(true);
    setApplyError('');
    try {
      await applyPartner();
      window.location.reload();
    } catch (error) {
      setApplyError(error instanceof Error ? error.message : '신청에 실패했습니다.');
    } finally {
      setApplying(false);
    }
  };

  const isPending = auth.isPartner && auth.partnerStatus === 'pending';
  const isSuspended = auth.isPartner && auth.partnerStatus === 'suspended';

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
      <div className="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        {isPending ? (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 승인 대기 중</h1>
            <p className="text-slate-600 mb-4">
              신청이 접수되었습니다. 관리자 승인 후 파트너센터를 이용할 수 있습니다.
            </p>
            {auth.partnerCode && (
              <p className="text-sm text-slate-500">
                파트너 코드: <span className="font-mono font-semibold text-slate-800">{auth.partnerCode}</span>
              </p>
            )}
          </>
        ) : isSuspended ? (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 계정 정지</h1>
            <p className="text-slate-600">계정이 정지되었습니다. 고객센터로 문의해 주세요.</p>
          </>
        ) : (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 등록이 필요합니다</h1>
            <p className="text-slate-600 mb-6">
              파트너센터는 승인된 파트너 회원만 이용할 수 있습니다. 아래 버튼으로 신청해 주세요.
            </p>
            {applyError && (
              <p className="text-sm text-red-600 mb-4">{applyError}</p>
            )}
            <button
              type="button"
              onClick={handleApply}
              disabled={applying}
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold rounded-xl transition-colors"
            >
              {applying ? '신청 중...' : '파트너 신청하기'}
            </button>
          </>
        )}

        <div className="mt-6 flex gap-3">
          <Link to="/" className="text-sm text-slate-500 hover:text-emerald-600">홈으로</Link>
          <Link to="/select-center" className="text-sm text-slate-500 hover:text-emerald-600">센터 선택</Link>
        </div>
      </div>
    </div>
  );
}
