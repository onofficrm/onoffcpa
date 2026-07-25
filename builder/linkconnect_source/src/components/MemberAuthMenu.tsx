import { User, UserCog } from 'lucide-react';
import { getMemberDisplayName, isLcLoggedIn } from '../lib/auth';
import {
  currentSpaReturnPath,
  currentSpaReturnUrl,
  g5LoginUrl,
  g5LogoutUrl,
  g5MemberEditUrl,
  g5RegisterUrl,
} from '../lib/urls';

type MemberAuthMenuProps = {
  variant?: 'header-dark' | 'footer' | 'sidebar' | 'card' | 'compact';
  loginReturnUrl?: string;
  logoutReturnPath?: string;
  onNavigate?: () => void;
};

export function MemberAuthMenu({
  variant = 'header-dark',
  loginReturnUrl,
  logoutReturnPath,
  onNavigate,
}: MemberAuthMenuProps) {
  const loggedIn = isLcLoggedIn();
  const displayName = getMemberDisplayName();
  const loginUrl = g5LoginUrl(loginReturnUrl ?? currentSpaReturnUrl());
  const logoutUrl = g5LogoutUrl(logoutReturnPath ?? currentSpaReturnPath());
  const editUrl = g5MemberEditUrl();
  const registerUrl = g5RegisterUrl();

  const handleClick = () => {
    onNavigate?.();
  };

  if (variant === 'card') {
    if (loggedIn) {
      return (
        <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
          <span className="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white/10 border border-white/15 rounded-xl text-sm">
            <User className="w-4 h-4 text-emerald-400" />
            <span className="font-medium">{displayName}</span>
          </span>
          <a
            href={editUrl}
            className="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white/10 hover:bg-white/15 border border-white/15 font-medium rounded-xl transition-colors"
          >
            <UserCog className="w-4 h-4" />
            정보수정
          </a>
          <a
            href={logoutUrl}
            className="inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-800 hover:bg-slate-700 font-medium rounded-xl transition-colors"
          >
            로그아웃
          </a>
        </div>
      );
    }

    return (
      <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <a
          href={loginUrl}
          className="inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl transition-colors"
        >
          로그인
        </a>
        <a
          href={registerUrl}
          className="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white/10 hover:bg-white/15 border border-white/15 font-medium rounded-xl transition-colors"
        >
          회원가입
        </a>
      </div>
    );
  }

  if (variant === 'footer') {
    if (loggedIn) {
      return (
        <>
          <li>
            <span className="text-slate-300">{displayName}</span>
          </li>
          <li>
            <a href={editUrl} className="hover:text-cyan-400 transition-colors">
              정보수정
            </a>
          </li>
          <li>
            <a href={logoutUrl} className="hover:text-cyan-400 transition-colors">
              로그아웃
            </a>
          </li>
        </>
      );
    }

    return (
      <>
        <li>
          <a href={loginUrl} className="hover:text-cyan-400 transition-colors">
            로그인
          </a>
        </li>
        <li>
          <a href={registerUrl} className="hover:text-cyan-400 transition-colors">
            회원가입
          </a>
        </li>
      </>
    );
  }

  if (variant === 'sidebar') {
    return (
      <div className="border-t border-inherit pt-4 mt-4 space-y-1">
        {loggedIn && (
          <>
            <div className="px-4 py-2 text-xs text-slate-500 truncate">{displayName}</div>
            <a
              href={editUrl}
              className="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-colors font-medium"
            >
              <UserCog size={20} />
              <span>회원정보 수정</span>
            </a>
          </>
        )}
        {loggedIn ? (
          <a
            href={logoutUrl}
            className="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-colors font-medium"
          >
            <span>로그아웃</span>
          </a>
        ) : (
          <>
            <a
              href={loginUrl}
              className="flex items-center gap-3 px-4 py-3 rounded-xl text-emerald-400 hover:bg-white/5 transition-colors font-medium"
            >
              <span>로그인</span>
            </a>
            <a
              href={registerUrl}
              className="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-white/5 transition-colors font-medium"
            >
              <span>회원가입</span>
            </a>
          </>
        )}
      </div>
    );
  }

  if (variant === 'compact') {
    if (loggedIn) {
      return (
        <div className="flex items-center gap-2">
          <span className="text-xs font-medium text-slate-500 max-w-[120px] truncate hidden sm:inline">{displayName}</span>
          <a
            href={editUrl}
            title="회원정보 수정"
            className="w-10 h-10 rounded-full flex items-center justify-center border shadow-sm transition-colors bg-white border-slate-200 text-slate-500 hover:text-slate-900"
          >
            <User size={20} />
          </a>
        </div>
      );
    }
    return null;
  }

  // header-dark (default)
  if (loggedIn) {
    return (
      <>
        <span className="hidden lg:inline text-sm font-medium text-slate-400 max-w-[140px] truncate">
          {displayName}
        </span>
        <a
          href={editUrl}
          onClick={handleClick}
          className="text-base font-medium text-slate-300 hover:text-white transition-colors px-3 py-2"
        >
          정보수정
        </a>
        <a
          href={logoutUrl}
          onClick={handleClick}
          className="text-base font-medium text-slate-300 hover:text-white transition-colors px-4 py-2"
        >
          로그아웃
        </a>
      </>
    );
  }

  return (
    <>
      <a
        href={loginUrl}
        onClick={handleClick}
        className="text-sm font-medium text-slate-300 hover:text-white transition-colors px-3 py-2 rounded-lg hover:bg-white/5"
      >
        로그인
      </a>
      <a
        href={registerUrl}
        onClick={handleClick}
        className="text-sm font-medium text-white bg-white/10 hover:bg-white/15 border border-white/15 transition-colors px-4 py-2 rounded-lg"
      >
        회원가입
      </a>
    </>
  );
}

export function MemberAuthMenuMobile({
  loginReturnUrl,
  logoutReturnPath,
  onNavigate,
}: Omit<MemberAuthMenuProps, 'variant'>) {
  const loggedIn = isLcLoggedIn();
  const displayName = getMemberDisplayName();
  const loginUrl = g5LoginUrl(loginReturnUrl ?? currentSpaReturnUrl());
  const logoutUrl = g5LogoutUrl(logoutReturnPath ?? currentSpaReturnPath());
  const editUrl = g5MemberEditUrl();
  const registerUrl = g5RegisterUrl();

  if (loggedIn) {
    return (
      <>
        <p className="text-center text-sm text-slate-400 px-3">{displayName}</p>
        <a
          href={editUrl}
          onClick={onNavigate}
          className="w-full text-center text-base font-medium text-slate-300 border border-slate-600 hover:bg-white/5 transition-colors px-4 py-3 rounded-xl"
        >
          정보수정
        </a>
        <a
          href={logoutUrl}
          onClick={onNavigate}
          className="w-full text-center text-base font-medium text-slate-300 border border-slate-600 hover:bg-white/5 transition-colors px-4 py-3 rounded-xl"
        >
          로그아웃
        </a>
      </>
    );
  }

  return (
    <>
      <a
        href={loginUrl}
        onClick={onNavigate}
        className="w-full text-center text-base font-medium text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/10 transition-colors px-4 py-3 rounded-xl"
      >
        로그인
      </a>
      <a
        href={registerUrl}
        onClick={onNavigate}
        className="w-full text-center text-base font-medium text-slate-300 border border-slate-600 hover:bg-white/5 transition-colors px-4 py-3 rounded-xl"
      >
        회원가입
      </a>
    </>
  );
}
