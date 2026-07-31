/** 플러그인 이미지 서빙 URL에 리사이즈/WebP 파라미터를 붙인다. */

export type OptimizedImageOpts = {
  /** CSS 표시 너비에 맞춘 요청 폭 (서버가 허용 그리드로 스냅) */
  w?: number;
  h?: number;
  fit?: 'cover' | 'contain';
  /** 기본 webp — 화질 유지·용량 절감 */
  fmt?: 'webp' | 'jpeg' | 'png' | 'original';
  /** 원본 강제 (다운로드용) */
  download?: boolean;
};

const OPTIMIZABLE_PATH_MARKERS = [
  'campaign-thumbnail.php',
  'campaign-guide-asset.php',
  'ad-apply-asset.php',
];

function isOptimizableUrl(url: string): boolean {
  if (!url || url.startsWith('data:') || url.startsWith('blob:')) {
    return false;
  }
  // 외부 CDN/로고는 그대로
  try {
    const parsed = new URL(url, typeof window !== 'undefined' ? window.location.origin : 'https://local');
    return OPTIMIZABLE_PATH_MARKERS.some((marker) => parsed.pathname.includes(marker));
  } catch {
    return OPTIMIZABLE_PATH_MARKERS.some((marker) => url.includes(marker));
  }
}

/**
 * 목록·카드용 썸네일 URL (WebP + 표시 크기).
 * 원본 다운로드가 필요하면 `{ download: true }` 또는 `fmt: 'original'`.
 */
export function optimizedImageUrl(
  url: string | undefined | null,
  opts: OptimizedImageOpts = {},
): string {
  const raw = (url || '').trim();
  if (!raw) {
    return '';
  }
  if (!isOptimizableUrl(raw)) {
    return raw;
  }

  try {
    const parsed = new URL(raw, typeof window !== 'undefined' ? window.location.origin : 'https://local');
    if (opts.download || opts.fmt === 'original') {
      parsed.searchParams.set('download', '1');
      return parsed.pathname + parsed.search + parsed.hash;
    }

    if (opts.w && opts.w > 0) {
      parsed.searchParams.set('w', String(Math.round(opts.w)));
    }
    if (opts.h && opts.h > 0) {
      parsed.searchParams.set('h', String(Math.round(opts.h)));
    }
    if (opts.fit) {
      parsed.searchParams.set('fit', opts.fit);
    } else if (opts.w || opts.h) {
      parsed.searchParams.set('fit', 'cover');
    }

    const fmt = opts.fmt ?? 'webp';
    if (fmt !== 'original') {
      parsed.searchParams.set('fmt', fmt);
    }

    // 상대 URL 유지 (다른 오리진으로 바뀌지 않게)
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      return parsed.toString();
    }
    return parsed.pathname + parsed.search + parsed.hash;
  } catch {
    return raw;
  }
}

/** CPA 목록/메인 카드 (~표시 폭 400~600 → 640 그리드) */
export function cpaCardImageUrl(url?: string | null): string {
  return optimizedImageUrl(url, { w: 640, h: 480, fit: 'cover', fmt: 'webp' });
}

/** 파트너 검색 소형 아이콘 */
export function cpaTinyImageUrl(url?: string | null): string {
  return optimizedImageUrl(url, { w: 160, h: 160, fit: 'cover', fmt: 'webp' });
}

/** 상세 히어로 */
export function cpaHeroImageUrl(url?: string | null): string {
  return optimizedImageUrl(url, { w: 960, h: 720, fit: 'cover', fmt: 'webp' });
}

/** 홍보 가이드 미리보기 */
export function promoPreviewImageUrl(url?: string | null): string {
  return optimizedImageUrl(url, { w: 800, fit: 'contain', fmt: 'webp' });
}
