import { useMemo, useState } from 'react';

export type PromoAssetSizePreset = {
  id: string;
  title: string;
  width: number;
  height: number;
  platform: 'naver' | 'web' | 'google';
  group: 'popular' | 'naver' | 'web';
  hint: string;
  /** 규격 제한 없이 업로드 */
  freeFormat?: boolean;
};

/** 네이버·구글·웹사이트 중심 권장 사이즈 (SNS보다 웹/블로그/카페 우선) */
export const PROMO_ASSET_SIZE_PRESETS: PromoAssetSizePreset[] = [
  {
    id: 'naver-blog-banner',
    title: '네이버 블로그 상단 배너',
    width: 758,
    height: 140,
    platform: 'naver',
    group: 'popular',
    hint: '블로그 스킨 상단·사이드 배너에 많이 쓰입니다.',
  },
  {
    id: 'naver-cafe-banner',
    title: '네이버 카페 배너',
    width: 790,
    height: 160,
    platform: 'naver',
    group: 'popular',
    hint: '카페 메인·게시판 상단 배너에 적합합니다.',
  },
  {
    id: 'web-og-thumbnail',
    title: '웹사이트 공유 썸네일',
    width: 1200,
    height: 630,
    platform: 'web',
    group: 'popular',
    hint: '링크 공유·오픈그래프(OG)용. 네이버·구글 검색 미리보기에도 유리합니다.',
  },
  {
    id: 'cpa-product-thumb',
    title: '상품 리스트 썸네일',
    width: 1200,
    height: 750,
    platform: 'web',
    group: 'popular',
    hint: '온오프CPA CPA 목록·파트너 카드에 표시되는 권장 비율(16:10)입니다.',
  },
  {
    id: 'free-format',
    title: '자유형식',
    width: 0,
    height: 0,
    platform: 'web',
    group: 'popular',
    freeFormat: true,
    hint: '규격에 상관없이 로고, 상세컷, 기타 홍보 이미지를 올릴 수 있습니다.',
  },
  {
    id: 'naver-blog-body',
    title: '네이버 블로그 본문 이미지',
    width: 860,
    height: 484,
    platform: 'naver',
    group: 'naver',
    hint: '블로그 본문 폭에 맞춘 가로형 이미지입니다.',
  },
  {
    id: 'naver-blog-cover',
    title: '네이버 블로그 대표 이미지',
    width: 800,
    height: 800,
    platform: 'naver',
    group: 'naver',
    hint: '글 목록·검색 노출용 정사각 대표 이미지입니다.',
  },
  {
    id: 'naver-cafe-post',
    title: '네이버 카페 게시글 이미지',
    width: 800,
    height: 450,
    platform: 'naver',
    group: 'naver',
    hint: '카페 게시글·공지에 넣는 본문 이미지입니다.',
  },
  {
    id: 'web-hero-banner',
    title: '웹사이트 히어로 배너',
    width: 1920,
    height: 600,
    platform: 'web',
    group: 'web',
    hint: '랜딩·소개 페이지 상단 와이드 배너입니다.',
  },
  {
    id: 'google-display-mrec',
    title: '구글 디스플레이 (중간형)',
    width: 300,
    height: 250,
    platform: 'google',
    group: 'web',
    hint: 'Google Ads 디스플레이에서 가장 흔한 Medium Rectangle 규격입니다.',
  },
  {
    id: 'google-leaderboard',
    title: '구글 디스플레이 (가로형)',
    width: 728,
    height: 90,
    platform: 'google',
    group: 'web',
    hint: '사이트 상단·하단에 쓰는 Leaderboard 배너입니다.',
  },
  {
    id: 'google-skyscraper',
    title: '구글 디스플레이 (세로형)',
    width: 160,
    height: 600,
    platform: 'google',
    group: 'web',
    hint: '사이드바용 Wide Skyscraper 배너입니다.',
  },
];

export const FREE_FORMAT_PRESET =
  PROMO_ASSET_SIZE_PRESETS.find((p) => p.freeFormat) ??
  ({
    id: 'free-format',
    title: '자유형식',
    width: 0,
    height: 0,
    platform: 'web' as const,
    group: 'popular' as const,
    freeFormat: true,
    hint: '규격에 상관없이 이미지를 올릴 수 있습니다.',
  } satisfies PromoAssetSizePreset);

const GROUP_TABS = [
  { id: 'popular' as const, label: '인기' },
  { id: 'naver' as const, label: '네이버' },
  { id: 'web' as const, label: '웹·구글' },
];

function SizePreview({
  width,
  height,
  active,
  freeFormat,
}: {
  width: number;
  height: number;
  active?: boolean;
  freeFormat?: boolean;
}) {
  if (freeFormat) {
    return (
      <div className="h-16 flex items-center justify-center">
        <div
          className={`w-14 h-10 rounded-md border border-dashed flex items-center justify-center text-[10px] font-bold tracking-tight ${
            active
              ? 'bg-cyan-100 border-cyan-400 text-cyan-700'
              : 'bg-white border-slate-300 text-slate-400'
          }`}
          aria-hidden
        >
          FREE
        </div>
      </div>
    );
  }

  const maxW = 88;
  const maxH = 56;
  const scale = Math.min(maxW / width, maxH / height);
  const w = Math.max(10, Math.round(width * scale));
  const h = Math.max(8, Math.round(height * scale));

  return (
    <div className="h-16 flex items-center justify-center">
      <div
        className={`rounded-md border shadow-sm transition-colors ${
          active
            ? 'bg-cyan-100 border-cyan-400'
            : 'bg-white border-slate-200'
        }`}
        style={{ width: w, height: h }}
        aria-hidden
      />
    </div>
  );
}

export function formatPromoAssetSize(preset: PromoAssetSizePreset): string;
export function formatPromoAssetSize(width: number, height: number): string;
export function formatPromoAssetSize(a: PromoAssetSizePreset | number, b?: number) {
  if (typeof a === 'object') {
    if (a.freeFormat) return '자유 규격';
    return `${a.width.toLocaleString()} × ${a.height.toLocaleString()} px`;
  }
  if (!a || !b) return '자유 규격';
  return `${a.toLocaleString()} × ${b.toLocaleString()} px`;
}

export function suggestTitleForPreset(preset: PromoAssetSizePreset) {
  return preset.freeFormat ? '자유형식 이미지' : preset.title;
}

type PromoAssetSizeGuideProps = {
  selectedId?: string | null;
  onSelect?: (preset: PromoAssetSizePreset) => void;
};

export function PromoAssetSizeGuide({ selectedId = null, onSelect }: PromoAssetSizeGuideProps) {
  const [tab, setTab] = useState<'popular' | 'naver' | 'web'>('popular');

  const items = useMemo(
    () =>
      tab === 'popular'
        ? PROMO_ASSET_SIZE_PRESETS.filter((p) => p.group === 'popular' && !p.freeFormat)
        : PROMO_ASSET_SIZE_PRESETS.filter((p) => p.group === tab && !p.freeFormat),
    [tab],
  );

  const selected = PROMO_ASSET_SIZE_PRESETS.find((p) => p.id === selectedId) ?? null;
  const freeActive = selectedId === FREE_FORMAT_PRESET.id;

  return (
    <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 md:p-5 space-y-4">
      <div>
        <h3 className="text-sm font-bold text-slate-900">1. 업로드할 사이즈 선택</h3>
        <p className="text-xs text-slate-500 mt-1 leading-relaxed">
          규격이 정해지지 않았다면 바로 <strong className="text-slate-700">자유형식</strong>을 고르세요.
          블로그·카페·웹 권장 사이즈도 아래에서 선택할 수 있습니다.
        </p>
      </div>

      <button
        type="button"
        onClick={() => onSelect?.(FREE_FORMAT_PRESET)}
        disabled={!onSelect}
        className={`w-full text-left rounded-xl border-2 border-dashed p-4 transition-all ${
          freeActive
            ? 'border-slate-900 bg-white ring-2 ring-slate-300 shadow-sm'
            : 'border-slate-300 bg-white hover:border-slate-500 hover:shadow-sm'
        } ${!onSelect ? 'cursor-default' : ''}`}
      >
        <div className="flex items-start gap-3">
          <div
            className={`shrink-0 w-14 h-10 rounded-md border border-dashed flex items-center justify-center text-[10px] font-bold ${
              freeActive
                ? 'bg-slate-900 border-slate-900 text-white'
                : 'bg-slate-50 border-slate-300 text-slate-400'
            }`}
            aria-hidden
          >
            FREE
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-2">
              <span className="text-sm font-bold text-slate-900">자유형식</span>
              <span className="text-[11px] font-semibold text-slate-500">자유 규격 · 픽셀 제한 없음</span>
            </div>
            <p className="text-xs text-slate-500 mt-1 leading-relaxed">
              로고, 상세컷, 기타 홍보 이미지를 원하는 크기로 올립니다.
            </p>
          </div>
          {freeActive ? (
            <span className="shrink-0 text-[11px] font-bold text-slate-900">선택됨</span>
          ) : null}
        </div>
      </button>

      <div className="flex flex-wrap items-center gap-1.5">
        <span className="text-[11px] font-semibold text-slate-400 mr-1">권장 사이즈</span>
        {GROUP_TABS.map((g) => (
          <button
            key={g.id}
            type="button"
            onClick={() => setTab(g.id)}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
              tab === g.id
                ? 'bg-slate-900 text-white'
                : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300'
            }`}
          >
            {g.label}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        {items.map((preset) => {
          const active = selectedId === preset.id;
          return (
            <button
              key={preset.id}
              type="button"
              onClick={() => onSelect?.(preset)}
              disabled={!onSelect}
              className={`text-left rounded-xl border p-3 transition-all ${
                active
                  ? 'border-cyan-400 bg-white ring-2 ring-cyan-200 shadow-sm'
                  : 'border-slate-200/80 bg-white hover:border-cyan-300 hover:shadow-sm'
              } ${!onSelect ? 'cursor-default' : ''}`}
            >
              <div className={`rounded-lg mb-2 ${active ? 'bg-cyan-50' : 'bg-slate-100'}`}>
                <SizePreview
                  width={preset.width || 120}
                  height={preset.height || 80}
                  active={active}
                  freeFormat={preset.freeFormat}
                />
              </div>
              <div className="text-xs font-bold text-slate-900 leading-snug">{preset.title}</div>
              <div className="text-[11px] text-slate-500 mt-1 tabular-nums">
                {formatPromoAssetSize(preset)}
              </div>
            </button>
          );
        })}
      </div>

      {selected ? (
        <div className="rounded-xl border border-cyan-100 bg-cyan-50/70 px-3.5 py-3 text-xs text-cyan-900 space-y-1">
          <p className="font-bold">
            {selected.title}
            {selected.freeFormat ? ' · 자유 규격' : ` · ${formatPromoAssetSize(selected)}`}
          </p>
          <p className="text-cyan-800/90 leading-relaxed">{selected.hint}</p>
          <p className="text-cyan-700/80">
            {selected.freeFormat
              ? 'JPG · PNG · WEBP 권장. 비율·픽셀 제한 없이 업로드할 수 있습니다.'
              : 'JPG · PNG · WEBP 권장. 가능하면 위 픽셀에 맞춰 제작한 뒤 업로드해 주세요.'}
          </p>
        </div>
      ) : (
        <p className="text-[11px] text-slate-400">사이즈 카드를 선택하면 상세 안내가 표시됩니다.</p>
      )}
    </div>
  );
}
