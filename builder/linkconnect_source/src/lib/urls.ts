import { getLcAuth } from './auth';

/** GNUBoard LinkConnect 플러그인 경로 (운영·관리 화면) */
export const LC_PLUGIN_BASE = '/plugin/linkconnect';

/** GNUBoard 게시판 기본 경로 (PHP 주입 전 폴백) */
export const G5_BBS_BASE = '/bbs';

export function getG5BbsBase(): string {
  const fromAuth = getLcAuth().bbsUrl;
  if (fromAuth) {
    return fromAuth.replace(/\/$/, '');
  }
  return G5_BBS_BASE;
}

export function lcPluginUrl(path = '') {
  const normalized = path.replace(/^\//, '');
  return normalized ? `${LC_PLUGIN_BASE}/${normalized}` : LC_PLUGIN_BASE;
}

export function g5BbsUrl(path: string) {
  const base = getG5BbsBase();
  const normalized = path.replace(/^\//, '');
  return normalized ? `${base}/${normalized}` : base;
}

function siteBasePath(): string {
  if (typeof window === 'undefined') {
    return '';
  }

  const base = document.querySelector('base')?.getAttribute('href') ?? '/';
  try {
    const url = new URL(base, window.location.origin);
    const path = url.pathname.replace(/\/$/, '');
    return path === '/' ? '' : path;
  } catch {
    return '';
  }
}

/** BrowserRouter SPA 절대 URL (예: https://site.com/partner) */
export function spaReturnUrl(path = '/') {
  if (typeof window === 'undefined') {
    return '/';
  }

  const normalized = path.startsWith('/') ? path : `/${path}`;
  const base = siteBasePath();
  return `${window.location.origin}${base}${normalized === '/' ? '/' : normalized}`;
}

/** 현재 SPA 경로의 절대 URL (GNUBoard 로그인 복귀용) */
export function currentSpaReturnUrl(fallback = '/') {
  if (typeof window === 'undefined') {
    return spaReturnUrl(fallback);
  }

  const { pathname, search, hash } = window.location;
  const base = siteBasePath();
  let path = pathname;

  if (base && path.startsWith(base)) {
    path = path.slice(base.length) || '/';
  }

  if (path === '/' || path === '/index.php') {
    return spaReturnUrl(fallback);
  }

  return `${window.location.origin}${base}${path}${search}${hash}`;
}

/** 현재 SPA 상대 경로 (GNUBoard 로그아웃 복귀용 — 도메인 불가) */
export function currentSpaReturnPath(fallback = '/') {
  if (typeof window === 'undefined') {
    return fallback.startsWith('/') ? fallback : `/${fallback}`;
  }

  try {
    const full = currentSpaReturnUrl(fallback);
    const parsed = new URL(full);
    return `${parsed.pathname}${parsed.search}${parsed.hash}`;
  } catch {
    const normalized = fallback.startsWith('/') ? fallback : `/${fallback}`;
    const base = siteBasePath();
    return `${base}${normalized}`;
  }
}

/** GNUBoard 로그인 — url 파라미터로 로그인 후 복귀 */
export function g5LoginUrl(returnUrl?: string) {
  const url = returnUrl || currentSpaReturnUrl();
  return `${g5BbsUrl('login.php')}?url=${encodeURIComponent(url)}`;
}

export function g5RegisterUrl() {
  return g5BbsUrl('register.php');
}

/** GNUBoard 회원정보 수정 (비밀번호 확인 후 register_form.php) */
export function g5MemberEditUrl() {
  return `${g5BbsUrl('member_confirm.php')}?url=${encodeURIComponent('register_form.php')}`;
}

/** GNUBoard 로그아웃 — 상대 경로 url로 SPA 복귀 */
export function g5LogoutUrl(returnPath?: string) {
  const path = returnPath || currentSpaReturnPath();
  return `${g5BbsUrl('logout.php')}?url=${encodeURIComponent(path)}`;
}
