import { useEffect, useId, useState } from 'react';
import { ExternalLink, FileDown, Maximize2, Printer, X } from 'lucide-react';
import './contractDocument.css';

export type ContractDocumentViewerProps = {
  html: string;
  title?: string;
  contractCode?: string;
  signedAt?: string;
  signatureUrl?: string;
  documentPreviewUrl?: string;
  documentPdfUrl?: string;
  maxHeight?: string;
  showToolbar?: boolean;
  className?: string;
};

function DocumentPaper({
  html,
  contractCode,
  signedAt,
  signatureUrl,
  printId,
}: {
  html: string;
  contractCode?: string;
  signedAt?: string;
  signatureUrl?: string;
  printId?: string;
}) {
  if (!html.trim()) {
    return (
      <div className="contract-document-viewer__paper text-sm text-slate-500 text-center py-16">
        표시할 계약서 내용이 없습니다.
      </div>
    );
  }

  return (
    <div id={printId} className="contract-document-viewer__paper contract-document-viewer__print-target">
      <div className="lc-contract-document" dangerouslySetInnerHTML={{ __html: html }} />
      {(contractCode || signedAt || signatureUrl) ? (
        <footer className="contract-document-viewer__sign-footer">
          {contractCode ? <p><strong>계약번호</strong> {contractCode}</p> : null}
          {signedAt ? <p><strong>체결일시</strong> {signedAt}</p> : null}
          {signatureUrl ? (
            <div>
              <p className="font-semibold text-slate-700 mb-2">전자서명</p>
              <img src={signatureUrl} alt="계약 서명" />
            </div>
          ) : null}
        </footer>
      ) : null}
    </div>
  );
}

export function ContractDocumentViewer({
  html,
  title = 'CPA 광고 제휴 계약서',
  contractCode,
  signedAt,
  signatureUrl,
  documentPreviewUrl,
  documentPdfUrl,
  maxHeight = '72vh',
  showToolbar = true,
  className = '',
}: ContractDocumentViewerProps) {
  const [fullscreen, setFullscreen] = useState(false);
  const printId = useId().replace(/:/g, '');

  useEffect(() => {
    if (!fullscreen) {
      return;
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setFullscreen(false);
      }
    };
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKeyDown);
    return () => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', onKeyDown);
    };
  }, [fullscreen]);

  const handlePrint = () => {
    window.print();
  };

  const toolbar = showToolbar ? (
    <div className="contract-document-viewer__toolbar">
      <p className="contract-document-viewer__title">{title}</p>
      <div className="contract-document-viewer__actions">
        <button type="button" className="contract-document-viewer__btn contract-document-viewer__btn--primary" onClick={() => setFullscreen(true)}>
          <Maximize2 size={15} />
          전체 화면
        </button>
        <button type="button" className="contract-document-viewer__btn" onClick={handlePrint}>
          <Printer size={15} />
          인쇄
        </button>
        {documentPreviewUrl ? (
          <a href={documentPreviewUrl} target="_blank" rel="noopener noreferrer" className="contract-document-viewer__btn">
            <ExternalLink size={15} />
            새 창
          </a>
        ) : null}
        {documentPdfUrl ? (
          <a href={documentPdfUrl} target="_blank" rel="noopener noreferrer" className="contract-document-viewer__btn">
            <FileDown size={15} />
            PDF
          </a>
        ) : null}
      </div>
    </div>
  ) : null;

  const canvas = (
    <div className="contract-document-viewer__canvas" style={{ maxHeight: fullscreen ? undefined : maxHeight }}>
      <DocumentPaper
        html={html}
        contractCode={contractCode}
        signedAt={signedAt}
        signatureUrl={signatureUrl}
        printId={printId}
      />
    </div>
  );

  return (
    <>
      {!fullscreen ? (
        <div className={`contract-document-viewer ${className}`.trim()}>
          {toolbar}
          {canvas}
        </div>
      ) : null}

      {fullscreen ? (
        <div className="contract-document-viewer contract-document-viewer--fullscreen" role="dialog" aria-modal="true" aria-label={title}>
          <div className="contract-document-viewer__toolbar bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-lg">
            <p className="contract-document-viewer__title">{title}</p>
            <div className="contract-document-viewer__actions">
              <button type="button" className="contract-document-viewer__btn" onClick={handlePrint}>
                <Printer size={15} />
                인쇄
              </button>
              {documentPdfUrl ? (
                <a href={documentPdfUrl} target="_blank" rel="noopener noreferrer" className="contract-document-viewer__btn">
                  <FileDown size={15} />
                  PDF
                </a>
              ) : null}
              <button type="button" className="contract-document-viewer__btn" onClick={() => setFullscreen(false)}>
                <X size={15} />
                닫기
              </button>
            </div>
          </div>
          {canvas}
        </div>
      ) : null}
    </>
  );
}
