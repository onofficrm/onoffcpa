import { useEffect, useRef, useState, type ReactNode } from 'react';

export type EmbedPreviewDevice = 'pc' | 'mobile';

export const EMBED_DEVICE_VIEWPORTS = {
  mobile: { width: 390, height: 844, label: '390×844' },
  pc: { width: 1280, height: 800, label: '1280×800' },
} as const;

type Props = {
  device: EmbedPreviewDevice;
  children: ReactNode;
  /** 주소창·페이지 헤더에 표시할 상품명 */
  productLabel?: string;
  pageHost?: string;
};

/**
 * PC 브라우저 / 모바일 실기기 크롬 프레임.
 * 내부는 논리 픽셀(390 / 1280)로 레이아웃하고, 패널 폭에 맞춰 축소합니다.
 */
export function EmbedDevicePreviewFrame({
  device,
  children,
  productLabel,
  pageHost = 'example.com',
}: Props) {
  const viewport = EMBED_DEVICE_VIEWPORTS[device];
  const wrapRef = useRef<HTMLDivElement>(null);
  const [scale, setScale] = useState(0.28);

  useEffect(() => {
    const el = wrapRef.current;
    if (!el) return;
    const update = () => {
      const avail = Math.max(120, el.clientWidth - 4);
      const next = Math.min(1, avail / viewport.width);
      setScale(Math.max(0.2, next));
    };
    update();
    const ro = new ResizeObserver(update);
    ro.observe(el);
    return () => ro.disconnect();
  }, [viewport.width]);

  const pageTitle = productLabel ? `${productLabel} · 홈페이지` : '내 홈페이지';
  const urlPath = productLabel ? `/consult` : '/';

  return (
    <div className="w-full space-y-2">
      <div className="flex items-center justify-between gap-2 text-[10px] text-slate-500">
        <span className="font-bold tabular-nums">
          {device === 'mobile' ? '모바일' : 'PC'} · {viewport.label}
        </span>
        <span className="tabular-nums opacity-80">{Math.round(scale * 100)}% 축소</span>
      </div>
      <div ref={wrapRef} className="w-full flex justify-center overflow-hidden">
        <div
          style={{
            width: viewport.width * scale,
            height: viewport.height * scale,
            position: 'relative',
          }}
        >
          <div
            className="origin-top-left absolute top-0 left-0"
            style={{
              width: viewport.width,
              height: viewport.height,
              transform: `scale(${scale})`,
            }}
          >
            {device === 'mobile' ? (
              <MobileChrome pageTitle={pageTitle}>{children}</MobileChrome>
            ) : (
              <DesktopChrome pageHost={pageHost} urlPath={urlPath} pageTitle={pageTitle}>
                {children}
              </DesktopChrome>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function DesktopChrome({
  children,
  pageHost,
  urlPath,
  pageTitle,
}: {
  children: ReactNode;
  pageHost: string;
  urlPath: string;
  pageTitle: string;
}) {
  return (
    <div
      className="h-full w-full flex flex-col overflow-hidden rounded-xl border border-slate-300 bg-slate-200 shadow-lg"
      style={{ boxShadow: '0 18px 40px rgba(15,23,42,.18)' }}
    >
      <div className="shrink-0 border-b border-slate-300 bg-slate-100">
        <div className="flex items-center gap-2 px-3 pt-2.5 pb-1.5">
          <span className="inline-flex gap-1.5">
            <span className="h-2.5 w-2.5 rounded-full bg-[#ff5f57]" />
            <span className="h-2.5 w-2.5 rounded-full bg-[#febc2e]" />
            <span className="h-2.5 w-2.5 rounded-full bg-[#28c840]" />
          </span>
          <div className="ml-2 flex-1 truncate rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] text-slate-500">
            https://{pageHost}
            {urlPath}
          </div>
        </div>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto bg-[linear-gradient(180deg,#f8fafc_0%,#e2e8f0_100%)]">
        <div className="border-b border-slate-200/80 bg-white/90 px-8 py-4">
          <div className="text-[13px] font-extrabold text-slate-800">{pageTitle}</div>
          <div className="mt-1 text-[11px] text-slate-500">외부 사이트에 위젯이 삽입된 모습입니다</div>
        </div>
        <div className="mx-auto w-full max-w-[480px] px-6 py-8">{children}</div>
      </div>
    </div>
  );
}

function MobileChrome({ children, pageTitle }: { children: ReactNode; pageTitle: string }) {
  return (
    <div
      className="relative h-full w-full overflow-hidden rounded-[2.35rem] border-[3px] border-slate-800 bg-slate-900 shadow-xl"
      style={{ boxShadow: '0 22px 48px rgba(15,23,42,.28)' }}
    >
      <div className="absolute left-1/2 top-2 z-20 h-[22px] w-[118px] -translate-x-1/2 rounded-b-2xl bg-slate-950" />
      <div className="absolute inset-[10px] overflow-hidden rounded-[1.85rem] bg-white">
        <div className="flex h-11 shrink-0 items-end justify-between border-b border-slate-100 bg-slate-50 px-4 pb-1.5">
          <span className="text-[10px] font-bold tabular-nums text-slate-700">9:41</span>
          <span className="max-w-[55%] truncate text-center text-[10px] font-bold text-slate-600">
            {pageTitle}
          </span>
          <span className="text-[9px] font-bold text-slate-500">5G</span>
        </div>
        <div className="h-[calc(100%-2.75rem)] overflow-y-auto overscroll-contain bg-[linear-gradient(180deg,#f8fafc_0%,#eef2f7_100%)] p-3">
          {children}
        </div>
      </div>
    </div>
  );
}
