import { Maximize2, X } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

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
  /** 클릭/버튼으로 크게 보기 (기본 true) */
  expandable?: boolean;
};

/**
 * PC 브라우저 / 모바일 실기기 크롬 프레임.
 * 내부는 논리 픽셀(390 / 1280)로 레이아웃하고, 패널 폭에 맞춰 축소합니다.
 * 클릭 또는 「크게 보기」로 거의 실사이즈 확대 오버레이를 엽니다.
 */
export function EmbedDevicePreviewFrame({
  device,
  children,
  productLabel,
  pageHost = 'example.com',
  expandable = true,
}: Props) {
  const viewport = EMBED_DEVICE_VIEWPORTS[device];
  const wrapRef = useRef<HTMLDivElement>(null);
  const [scale, setScale] = useState(0.28);
  const [expanded, setExpanded] = useState(false);

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

  useEffect(() => {
    if (!expanded) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setExpanded(false);
    };
    window.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      window.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [expanded]);

  const pageTitle = productLabel ? `${productLabel} · 홈페이지` : '내 홈페이지';
  const urlPath = productLabel ? `/consult` : '/';

  const chrome = (
    device === 'mobile' ? (
      <MobileChrome pageTitle={pageTitle}>{children}</MobileChrome>
    ) : (
      <DesktopChrome pageHost={pageHost} urlPath={urlPath} pageTitle={pageTitle}>
        {children}
      </DesktopChrome>
    )
  );

  return (
    <div className="w-full space-y-2">
      <div className="flex items-center justify-between gap-2 text-[10px] text-slate-500">
        <span className="font-bold tabular-nums">
          {device === 'mobile' ? '모바일' : 'PC'} · {viewport.label}
        </span>
        <div className="flex items-center gap-2">
          <span className="tabular-nums opacity-80">{Math.round(scale * 100)}% 축소</span>
          {expandable ? (
            <button
              type="button"
              onClick={() => setExpanded(true)}
              className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50"
            >
              <Maximize2 size={11} />
              크게 보기
            </button>
          ) : null}
        </div>
      </div>

      <div
        ref={wrapRef}
        className={`w-full flex justify-center overflow-hidden ${
          expandable ? 'group relative cursor-zoom-in' : ''
        }`}
        role={expandable ? 'button' : undefined}
        tabIndex={expandable ? 0 : undefined}
        onClick={expandable ? () => setExpanded(true) : undefined}
        onKeyDown={
          expandable
            ? (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  setExpanded(true);
                }
              }
            : undefined
        }
        aria-label={expandable ? '미리보기 크게 보기' : undefined}
      >
        <div
          style={{
            width: viewport.width * scale,
            height: viewport.height * scale,
            position: 'relative',
          }}
        >
          <div
            className="origin-top-left absolute top-0 left-0 pointer-events-none"
            style={{
              width: viewport.width,
              height: viewport.height,
              transform: `scale(${scale})`,
            }}
          >
            {chrome}
          </div>
        </div>
        {expandable ? (
          <div className="pointer-events-none absolute inset-0 flex items-end justify-center pb-3 opacity-0 transition-opacity group-hover:opacity-100">
            <span className="rounded-full bg-slate-900/80 px-3 py-1.5 text-[10px] font-bold text-white shadow-lg">
              클릭하여 크게 보기
            </span>
          </div>
        ) : null}
      </div>

      {expanded && expandable
        ? createPortal(
            <ExpandedPreviewOverlay
              device={device}
              viewport={viewport}
              pageTitle={pageTitle}
              pageHost={pageHost}
              urlPath={urlPath}
              onClose={() => setExpanded(false)}
            >
              {children}
            </ExpandedPreviewOverlay>,
            document.body,
          )
        : null}
    </div>
  );
}

function ExpandedPreviewOverlay({
  device,
  viewport,
  pageTitle,
  pageHost,
  urlPath,
  onClose,
  children,
}: {
  device: EmbedPreviewDevice;
  viewport: (typeof EMBED_DEVICE_VIEWPORTS)[EmbedPreviewDevice];
  pageTitle: string;
  pageHost: string;
  urlPath: string;
  onClose: () => void;
  children: ReactNode;
}) {
  const stageRef = useRef<HTMLDivElement>(null);
  const [scale, setScale] = useState(0.7);

  useEffect(() => {
    const el = stageRef.current;
    if (!el) return;
    const update = () => {
      const pad = 24;
      const availW = Math.max(200, el.clientWidth - pad);
      const availH = Math.max(200, el.clientHeight - pad);
      const next = Math.min(1, availW / viewport.width, availH / viewport.height);
      setScale(Math.max(0.35, next));
    };
    update();
    const ro = new ResizeObserver(update);
    ro.observe(el);
    return () => ro.disconnect();
  }, [viewport.width, viewport.height]);

  return (
    <div
      className="fixed inset-0 z-[80] flex flex-col bg-slate-950/70 backdrop-blur-sm p-3 sm:p-5"
      role="dialog"
      aria-modal="true"
      aria-labelledby="embed-preview-expand-title"
      onClick={onClose}
    >
      <div
        className="mx-auto flex h-full w-full max-w-[1400px] flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700 px-4 py-3">
          <div className="min-w-0">
            <div id="embed-preview-expand-title" className="text-sm font-bold text-white">
              미리보기 크게 보기
            </div>
            <p className="mt-0.5 truncate text-[11px] text-slate-400">
              {device === 'mobile' ? '모바일' : 'PC'} · {viewport.label} · {Math.round(scale * 100)}%
              {scale < 1 ? ' 맞춤' : ' 실사이즈'}
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-slate-100 hover:bg-slate-700"
            aria-label="닫기"
          >
            <X size={16} />
            닫기
          </button>
        </div>
        <div ref={stageRef} className="min-h-0 flex-1 overflow-auto bg-slate-800/80 p-4 sm:p-6">
          <div className="flex min-h-full items-center justify-center">
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
        <p className="shrink-0 border-t border-slate-700 px-4 py-2 text-center text-[11px] text-slate-400">
          Esc 또는 바깥 영역 클릭으로 닫습니다
        </p>
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
        <div className="mx-auto w-full max-w-[800px] px-8 py-8">{children}</div>
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
