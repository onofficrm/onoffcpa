import { getLcAuth } from './auth';
import { partnerApiGet, partnerApiPost } from './api';

export type LeadEmbedMode = 'form' | 'button' | 'phone';

/** 파트너 홍보코드로 외부 홈페이지에 붙일 상담폼 설치 스니펫 */
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

export function buildLeadEmbedSnippet(
  lkCode: string,
  originOrOptions:
    | string
    | { origin?: string; mode?: LeadEmbedMode; widgetKey?: string } = leadEmbedOrigin(),
): string {
  const opts =
    typeof originOrOptions === 'string'
      ? { origin: originOrOptions, mode: 'form' as LeadEmbedMode, widgetKey: '' }
      : {
          origin: originOrOptions.origin || leadEmbedOrigin(),
          mode: originOrOptions.mode || 'form',
          widgetKey: originOrOptions.widgetKey || '',
        };
  const code = lkCode.trim();
  const safe = code.replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 32) || 'form';
  const mode = opts.mode || 'form';
  const id = `lc-lead-${safe}${mode !== 'form' ? `-${mode}` : ''}`;
  const base = opts.origin.replace(/\/$/, '');
  const scriptSrc = `${base}/plugin/linkconnect/assets/js/lead-embed.js`;
  const modeAttr = mode !== 'form' ? ` data-mode="${mode}"` : '';
  const widgetKey = (opts.widgetKey || '').trim();
  const widgetAttr = widgetKey ? ` data-widget-key="${widgetKey}"` : '';

  const comment =
    mode === 'button'
      ? '상담신청 버튼 위젯 (클릭 시 모달)'
      : mode === 'phone'
        ? '전화 상담 위젯 (안심번호)'
        : '상담신청 위젯 (폼 + 전화)';

  return [
    `<!-- ${comment} -->`,
    `<div id="${id}"></div>`,
    `<script src="${scriptSrc}" data-lk-code="${code}"${widgetAttr} data-target="#${id}" data-channel="embed"${modeAttr} async></script>`,
  ].join('\n');
}

export function leadEmbedPluginDownloadUrl(origin = leadEmbedOrigin()): string {
  const base = origin.replace(/\/$/, '');
  return `${base}/plugin/linkconnect/assets/wordpress/linkconnect-lead.zip`;
}

/** 관리자/파트너 센터 미리보기용 iframe URL (플랫폼 도메인은 허용 도메인과 무관하게 동작) */
export function buildLeadEmbedPreviewUrl(
  lkCode: string,
  options?: { origin?: string; mode?: LeadEmbedMode; widgetKey?: string },
): string {
  const code = (lkCode || '').trim();
  if (!code || code === 'YOUR_LK_CODE') return '';
  const base = (options?.origin || leadEmbedOrigin()).replace(/\/$/, '');
  const mode = options?.mode === 'button' ? 'form' : options?.mode || 'form';
  const widgetKey = (options?.widgetKey || '').trim();
  const pageUrl =
    typeof window !== 'undefined' ? window.location.href : `${base}/`;
  const params = new URLSearchParams({
    lkCode: code,
    mode,
    channel: 'embed',
    page_url: pageUrl,
  });
  if (widgetKey) params.set('widgetKey', widgetKey);
  return `${base}/plugin/linkconnect/api/embed_frame.php?${params.toString()}`;
}

/** WP 플러그인 숏코드 (설정에 lkCode가 있으면 인자 생략 가능) */
export function buildLeadEmbedShortcode(
  lkCode?: string,
  options?: { widgetKey?: string; mode?: LeadEmbedMode },
): string {
  const code = (lkCode || '').trim();
  const widgetKey = (options?.widgetKey || '').trim();
  const mode = options?.mode || 'form';
  if (!code && !widgetKey && mode === 'form') {
    return '[linkconnect_lead]';
  }
  const parts = ['[linkconnect_lead'];
  if (code) parts.push(`lk_code="${code}"`);
  if (widgetKey) parts.push(`widget_key="${widgetKey}"`);
  if (mode !== 'form') parts.push(`mode="${mode}"`);
  return `${parts.join(' ')}]`;
}

export type PartnerEmbedStatsDomainRow = {
  host: string;
  total: number;
  today: number;
};

export type PartnerEmbedStatsDailyRow = {
  date: string;
  label: string;
  count: number;
};

export type PartnerEmbedOptions = {
  /** default | simple | card | bold | soft | dark */
  preset?: string;
  /** PC 배치: auto(프리셋 기본) | split | wide | hero */
  pcLayout?: string;
  accent?: string;
  title?: string;
  submitLabel?: string;
  buttonLabel?: string;
  callLabel?: string;
  successMessage?: string;
  successRedirectUrl?: string;
  trackConversion?: boolean;
  conversionEventName?: string;
  showRegion?: boolean;
  showInquiry?: boolean;
  privacyText?: string;
  requireWidgetKey?: boolean;
  /** 이름·연락처만 우선, 추가항목은 접기 */
  minimalForm?: boolean;
  /** 신뢰 배지 영역 표시 */
  showTrustBadges?: boolean;
  badgeFree?: boolean;
  badgeCallback?: boolean;
  badgePrivacy?: boolean;
  /** 제목 아래 혜택 한 줄 */
  benefitText?: string;
  /** 제출 버튼 아래 보조 문구 */
  ctaHint?: string;
  /** 시급성 문구 */
  showLiveCount?: boolean;
  liveCountText?: string;
  /** 모바일에서 제출 버튼 sticky */
  stickyMobileCta?: boolean;
  /** 완료 화면에 전화 CTA */
  successShowCall?: boolean;
  /** 상담폼·버튼형에 콜디비 안심번호 표시 */
  showFormCall?: boolean;
  /** 완료 후 다음 안내 */
  successNextStep?: string;
};

export type PartnerEmbedSettings = {
  domains: string[];
  domainLock: boolean;
  scriptUrl?: string;
  brandName?: string;
  snippet?: string;
  widgetKey?: string;
  hasWidgetKey?: boolean;
  /** 파트너가 디자인·전환 옵션을 저장한 적 있는지 */
  hasCustomOptions?: boolean;
  options?: PartnerEmbedOptions;
  embedTotal?: number;
  embedToday?: number;
  embedApproved?: number;
  statsDays?: number;
  byDomain?: PartnerEmbedStatsDomainRow[];
  daily?: PartnerEmbedStatsDailyRow[];
  config?: {
    hasPartnerPhone?: boolean;
    partnerPhoneDisplay?: string;
    campaignTitle?: string;
    campaignId?: number;
  };
};

export function fetchPartnerEmbedSettings(lkCode?: string) {
  const query: Record<string, string> = {};
  if (lkCode) query.lkCode = lkCode;
  return partnerApiGet<PartnerEmbedSettings>('embed.php', query);
}

export function savePartnerEmbedDomains(domains: string[]) {
  return partnerApiPost<{ message: string; domains: string[]; domainLock: boolean }>('embed.php', {
    domains,
  });
}

export function savePartnerEmbedOptions(options: PartnerEmbedOptions) {
  return partnerApiPost<{
    message: string;
    options: PartnerEmbedOptions;
    widgetKey?: string;
    hasWidgetKey?: boolean;
  }>('embed.php', {
    action: 'save_options',
    options,
  });
}

export function issuePartnerEmbedWidgetKey() {
  return partnerApiPost<{ message: string; widgetKey: string; hasWidgetKey: boolean }>('embed.php', {
    action: 'issue_widget_key',
  });
}

export function rotatePartnerEmbedWidgetKey() {
  return partnerApiPost<{ message: string; widgetKey: string; hasWidgetKey: boolean }>('embed.php', {
    action: 'rotate_widget_key',
  });
}

export function formatEmbedSourceLabel(source?: string, channel?: string): string {
  const s = (source || '').toLowerCase();
  const c = (channel || '').toLowerCase();
  if (s === 'embed' || c === 'embed' || c === 'wordpress' || c === 'widget' || c === 'external') {
    return '외부위젯';
  }
  if (s === 'call') return '콜디비';
  if (c === 'seo') return 'SEO';
  return channel || '-';
}
