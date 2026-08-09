import { CircleHelp, X } from 'lucide-react';
import { useEffect, useId, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

type HelpTipButtonProps = {
  title: string;
  children: ReactNode;
  /** 버튼에 보이는 짧은 라벨. 비우면 아이콘만 */
  label?: string;
  className?: string;
};

/**
 * 초보 사용자용 클릭형 도움말. 작은 ? 버튼을 누르면 설명 팝업을 띄웁니다.
 */
export function HelpTipButton({ title, children, label = '설명', className = '' }: HelpTipButtonProps) {
  const [open, setOpen] = useState(false);
  const titleId = useId();

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open]);

  return (
    <>
      <button
        type="button"
        onClick={(e) => {
          e.stopPropagation();
          setOpen(true);
        }}
        className={`inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold text-slate-600 hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-800 transition-colors shrink-0 ${className}`}
        aria-haspopup="dialog"
        aria-expanded={open}
      >
        <CircleHelp size={13} className="text-cyan-600" aria-hidden />
        {label ? <span>{label}</span> : <span className="sr-only">{title} 설명</span>}
      </button>

      {open
        ? createPortal(
            <div
              className="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4 bg-slate-900/45 backdrop-blur-[2px]"
              onClick={() => setOpen(false)}
              role="presentation"
            >
              <div
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50">
                  <div className="min-w-0">
                    <div className="text-[11px] font-bold text-cyan-700 mb-0.5">도움말</div>
                    <h4 id={titleId} className="text-base font-bold text-slate-900 leading-snug">
                      {title}
                    </h4>
                  </div>
                  <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg shrink-0"
                    aria-label="닫기"
                  >
                    <X size={18} />
                  </button>
                </div>
                <div className="px-5 py-4 text-sm text-slate-600 leading-relaxed space-y-3">
                  {children}
                </div>
                <div className="px-5 py-3 border-t border-slate-100 bg-white">
                  <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold"
                  >
                    확인
                  </button>
                </div>
              </div>
            </div>,
            document.body,
          )
        : null}
    </>
  );
}

type HelpTipHeadingProps = {
  title: string;
  helpTitle?: string;
  children: ReactNode;
  className?: string;
  as?: 'h3' | 'div' | 'span';
};

/** 섹션 제목 + 설명 버튼을 한 줄에 배치 */
export function HelpTipHeading({
  title,
  helpTitle,
  children,
  className = 'text-sm font-bold text-slate-900',
  as = 'div',
}: HelpTipHeadingProps) {
  const Tag = as;
  return (
    <Tag className={`flex items-center gap-1.5 flex-wrap ${className}`}>
      <span>{title}</span>
      <HelpTipButton title={helpTitle || title}>{children}</HelpTipButton>
    </Tag>
  );
}
