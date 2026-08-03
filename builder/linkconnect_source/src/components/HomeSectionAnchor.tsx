/**
 * 홈 메뉴 스크롤 앵커.
 * 섹션 바깥(패딩 위)이 아니라 제목 블록 바로 위에 두어
 * 고정 헤더(h-20) 아래에 제목이 오도록 맞춤.
 */
export function HomeSectionAnchor({ id }: { id: string }) {
  return (
    <div
      id={id}
      className="lc-home-anchor h-0 w-0 overflow-hidden scroll-mt-20"
      tabIndex={-1}
      aria-hidden="true"
    />
  );
}
