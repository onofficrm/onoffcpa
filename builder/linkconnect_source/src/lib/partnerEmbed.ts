import { getLcAuth } from './auth';

/** 파트너 홍보코드로 워드프레스 등에 붙일 상담폼 설치 스니펫 */
export function leadEmbedOrigin(): string {
  if (typeof window === 'undefined') {
    return '';
  }
  const site = (getLcAuth().siteUrl || '').replace(/\/$/, '');
  if (site) {
    try {
      return new URL(site).origin;
    } catch {
      return site;
    }
  }
  return window.location.origin;
}

export function buildLeadEmbedSnippet(lkCode: string, origin = leadEmbedOrigin()): string {
  const code = lkCode.trim();
  const safe = code.replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 32) || 'form';
  const id = `lc-lead-${safe}`;
  const base = origin.replace(/\/$/, '');
  const scriptSrc = `${base}/plugin/linkconnect/assets/js/lead-embed.js`;

  return [
    '<!-- 온오프CPA 상담신청 폼 -->',
    `<div id="${id}"></div>`,
    `<script src="${scriptSrc}" data-lk-code="${code}" data-target="#${id}" async></script>`,
  ].join('\n');
}

export function leadEmbedPluginDownloadUrl(origin = leadEmbedOrigin()): string {
  const base = origin.replace(/\/$/, '');
  return `${base}/plugin/linkconnect/assets/wordpress/linkconnect-lead.zip`;
}

/** WP 플러그인 숏코드 (설정에 lkCode가 있으면 인자 생략 가능) */
export function buildLeadEmbedShortcode(lkCode?: string): string {
  const code = (lkCode || '').trim();
  if (!code) {
    return '[linkconnect_lead]';
  }
  return `[linkconnect_lead lk_code="${code}"]`;
}
