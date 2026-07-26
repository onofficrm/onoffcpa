/** OnOff CPA brand mark — power/ON symbol (not the LinkConnect chain). */
export function BrandMark({ className = 'w-7 h-7', title = '온오프CPA' }: { className?: string; title?: string }) {
  return (
    <img
      src="/img/brand/onoffcpa-mark.svg"
      alt=""
      title={title}
      width={28}
      height={28}
      className={`shrink-0 rounded-[22%] ${className}`}
      decoding="async"
    />
  );
}
