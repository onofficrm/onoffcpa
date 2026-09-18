import { useEffect, useId, useRef, useState, type RefObject } from 'react';
import { Bold, ExternalLink, FileDown, Heading2, Italic, List, Maximize2, Pencil, Printer, Save, X } from 'lucide-react';
import './contractDocument.css';

export type ContractDocumentViewerProps = {
  html: string;
  editHtml?: string;
  title?: string;
  contractCode?: string;
  signedAt?: string;
  signatureUrl?: string;
  documentPreviewUrl?: string;
  documentPdfUrl?: string;
  maxHeight?: string;
  showToolbar?: boolean;
  className?: string;
  editable?: boolean;
  saving?: boolean;
  onSaveHtml?: (html: string) => Promise<void> | void;
};

function DocumentPaper({
  html,
  contractCode,
  signedAt,
  signatureUrl,
  printId,
  editing,
  editorRef,
}: {
  html: string;
  contractCode?: string;
  signedAt?: string;
  signatureUrl?: string;
  printId?: string;
  editing?: boolean;
  editorRef?: RefObject<HTMLDivElement | null>;
}) {
  if (!editing && !html.trim()) {
    return (
      <div className="contract-document-viewer__paper text-sm text-slate-500 text-center py-16">
        표시할 계약서 내용이 없습니다.
      </div>
    );
  }

  return (
    <div
      id={printId}
      className={`contract-document-viewer__paper contract-document-viewer__print-target${editing ? ' contract-document-viewer__paper--editing' : ''}`}
    >
      {editing ? (
        <div
          ref={editorRef}
          className="lc-contract-document"
          contentEditable
          suppressContentEditableWarning
          role="textbox"
          aria-multiline="true"
          aria-label="계약서 본문"
        />
      ) : (
        <div className="lc-contract-document" dangerouslySetInnerHTML={{ __html: html }} />
      )}
      {(contractCode || signedAt || signatureUrl) ? (
        <footer className="contract-document-viewer__sign-footer">
          {contractCode ? <p><strong>계약번호</strong> {contractCode}</p> : null}
          {signedAt ? <p><strong>체결일시</strong> {signedAt}</p> : null}
          {signatureUrl ? (
            <div>
              <p className="font-semibold text-slate-700 mb-2">전자서명</p>
              <img
                src={signatureUrl}
                alt="계약 서명"
                className="contract-document-viewer__signature-img"
                onError={(event) => {
                  const img = event.currentTarget;
                  img.style.display = 'none';
                  const fallback = img.nextElementSibling;
                  if (fallback instanceof HTMLElement) {
                    fallback.hidden = false;
                  }
                }}
              />
              <p className="text-xs text-slate-500 mt-1" hidden>
                서명 이미지를 불러오지 못했습니다.
              </p>
            </div>
          ) : null}
        </footer>
      ) : null}
    </div>
  );
}

export function ContractDocumentViewer({
  html,
  editHtml,
  title = 'CPA 광고 제휴 계약서',
  contractCode,
  signedAt,
  signatureUrl,
  documentPreviewUrl,
  documentPdfUrl,
  maxHeight = '72vh',
  showToolbar = true,
  className = '',
  editable = false,
  saving = false,
  onSaveHtml,
}: ContractDocumentViewerProps) {
  const [fullscreen, setFullscreen] = useState(false);
  const [editing, setEditing] = useState(false);
  const editorRef = useRef<HTMLDivElement>(null);
  const printId = useId().replace(/:/g, '');
  const sourceHtml = (editHtml && editHtml.trim()) ? editHtml : html;

  useEffect(() => {
    setEditing(false);
  }, [html, editHtml]);

  useEffect(() => {
    if (!editing || !editorRef.current) {
      return;
    }
    editorRef.current.innerHTML = sourceHtml;
    editorRef.current.focus();
  }, [editing, sourceHtml]);

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

  const applyFormat = (command: string, value?: string) => {
    editorRef.current?.focus();
    document.execCommand(command, false, value);
  };

  const handleSave = async () => {
    const next = editorRef.current?.innerHTML.trim() ?? '';
    if (!next || next === '<br>') {
      window.alert('계약서 내용이 비어 있습니다.');
      return;
    }
    try {
      await onSaveHtml?.(next);
      setEditing(false);
    } catch {
      // 부모에서 오류 메시지를 표시한다.
    }
  };

  const editActions = editable ? (
    editing ? (
      <>
        <button type="button" className="contract-document-viewer__btn" disabled={saving} onClick={() => applyFormat('bold')}>
          <Bold size={15} />
          굵게
        </button>
        <button type="button" className="contract-document-viewer__btn" disabled={saving} onClick={() => applyFormat('italic')}>
          <Italic size={15} />
          기울임
        </button>
        <button type="button" className="contract-document-viewer__btn" disabled={saving} onClick={() => applyFormat('insertUnorderedList')}>
          <List size={15} />
          목록
        </button>
        <button type="button" className="contract-document-viewer__btn" disabled={saving} onClick={() => applyFormat('formatBlock', 'h3')}>
          <Heading2 size={15} />
          제목
        </button>
        <button type="button" className="contract-document-viewer__btn" disabled={saving} onClick={() => setEditing(false)}>
          <X size={15} />
          취소
        </button>
        <button type="button" className="contract-document-viewer__btn contract-document-viewer__btn--save" disabled={saving} onClick={() => void handleSave()}>
          <Save size={15} />
          {saving ? '저장 중...' : '저장'}
        </button>
      </>
    ) : (
      <button type="button" className="contract-document-viewer__btn contract-document-viewer__btn--primary" onClick={() => setEditing(true)}>
        <Pencil size={15} />
        수정
      </button>
    )
  ) : null;

  const toolbar = showToolbar ? (
    <div className="contract-document-viewer__toolbar">
      <div>
        <p className="contract-document-viewer__title">{title}</p>
        {editing ? (
          <p className="contract-document-viewer__hint">본문을 직접 고친 뒤 저장하세요. 특약은 별도로 유지됩니다.</p>
        ) : null}
      </div>
      <div className="contract-document-viewer__actions">
        {editActions}
        {!editing ? (
          <button type="button" className="contract-document-viewer__btn contract-document-viewer__btn--primary" onClick={() => setFullscreen(true)}>
            <Maximize2 size={15} />
            전체 화면
          </button>
        ) : null}
        <button type="button" className="contract-document-viewer__btn" onClick={handlePrint}>
          <Printer size={15} />
          인쇄
        </button>
        {!editing && documentPreviewUrl ? (
          <a href={documentPreviewUrl} target="_blank" rel="noopener noreferrer" className="contract-document-viewer__btn">
            <ExternalLink size={15} />
            새 창
          </a>
        ) : null}
        {!editing && documentPdfUrl ? (
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
        editing={editing}
        editorRef={editorRef}
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
