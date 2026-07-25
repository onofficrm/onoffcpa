import { Link } from 'react-router-dom';
import { Eye, X, ShieldCheck } from 'lucide-react';
import { exitImpersonate } from '../lib/api';
import { getLcAuth } from '../lib/auth';

export function ImpersonateBanner() {
  const auth = getLcAuth();

  if (!auth.isImpersonating || !auth.impersonateLabel) {
    return null;
  }

  const centerLabel = auth.impersonateType === 'merchant' ? '광고주' : '파트너';

  const handleExit = async () => {
    try {
      const result = await exitImpersonate();
      window.location.href = result.redirect || '/admin';
    } catch {
      window.location.href = '/admin';
    }
  };

  return (
    <div className="bg-amber-500 border-b border-amber-600 text-amber-950 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 relative z-50">
      <div className="flex items-center gap-3">
        <div className="bg-amber-600/20 p-2 rounded-lg">
          <Eye className="w-5 h-5 text-amber-950" />
        </div>
        <div>
          <p className="text-sm font-bold flex items-center gap-2">
            <ShieldCheck size={14} />
            {centerLabel} 계정으로 보는 중
          </p>
          <p className="text-xs mt-0.5 font-medium">{auth.impersonateLabel}</p>
        </div>
      </div>
      <div className="flex items-center gap-2 w-full sm:w-auto">
        <Link
          to="/admin"
          className="flex-1 sm:flex-none text-center bg-white/80 hover:bg-white text-amber-950 text-xs font-bold px-4 py-2 rounded-lg transition-colors"
        >
          관리자센터
        </Link>
        <button
          type="button"
          onClick={handleExit}
          className="flex-1 sm:flex-none text-center bg-amber-950 hover:bg-amber-900 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-1.5"
        >
          <X size={14} />
          보기 종료
        </button>
      </div>
    </div>
  );
}
