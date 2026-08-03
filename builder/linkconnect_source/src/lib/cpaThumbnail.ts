/** CPA 상품 썸네일 — 메인·CPA 목록·관리자 업로드에 동일 4:3 비율 */
export const CPA_THUMBNAIL_ASPECT_CLASS = 'aspect-[4/3]';

/** 목록/홈 카드 미디어 — 카드 가로 전체 · 4:3 (이미지는 absolute inset-0 object-cover) */
export const CPA_THUMBNAIL_LIST_MEDIA_CLASS =
  'relative aspect-[4/3] w-full overflow-hidden';

/** 카드 썸네일 img — 컨테이너를 빈틈없이 채움 */
export const CPA_THUMBNAIL_LIST_IMG_CLASS =
  'absolute inset-0 w-full h-full object-cover';

export const CPA_THUMBNAIL_SPEC = {
  /** 권장 업로드 해상도 (4:3 가로형) */
  width: 1200,
  height: 900,
  ratioLabel: '4:3',
  sizeLabel: '1200 × 900px',
  formats: 'JPG · PNG · WEBP',
  maxMb: 2,
} as const;

export function cpaThumbnailHint(short = false): string {
  const { sizeLabel, ratioLabel, formats, maxMb } = CPA_THUMBNAIL_SPEC;
  if (short) {
    return `${sizeLabel} (${ratioLabel})`;
  }
  return `권장 ${sizeLabel} (${ratioLabel}). ${formats}, 최대 ${maxMb}MB. 메인·CPA 목록에 4:3 전체 폭으로 표시됩니다.`;
}
