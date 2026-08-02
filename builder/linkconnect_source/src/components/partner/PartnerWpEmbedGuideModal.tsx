import { Copy, Download, X } from 'lucide-react';
import {
  buildLeadEmbedShortcode,
  buildLeadEmbedSnippet,
  leadEmbedPluginDownloadUrl,
} from '../../lib/partnerEmbed';

type Props = {
  open: boolean;
  onClose: () => void;
  /** 있으면 예시 스니펫에 실제 코드 표시 */
  lkCode?: string;
  onCopySnippet?: (snippet: string) => void;
};

export function PartnerWpEmbedGuideModal({ open, onClose, lkCode, onCopySnippet }: Props) {
  if (!open) return null;

  const sampleCode = (lkCode || 'YOUR_LK_CODE').trim();
  const snippet = buildLeadEmbedSnippet(sampleCode);
  const shortcode = buildLeadEmbedShortcode(sampleCode === 'YOUR_LK_CODE' ? '' : sampleCode);
  const pluginUrl = leadEmbedPluginDownloadUrl();

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="wp-embed-guide-title"
      >
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
          <div>
            <h3 id="wp-embed-guide-title" className="text-lg font-bold text-slate-900">
              워드프레스 상담폼 사용방법
            </h3>
            <p className="text-sm text-slate-500 mt-0.5">플러그인 설치 또는 HTML 코드로 홈페이지에 연결</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg"
            aria-label="닫기"
          >
            <X size={20} />
          </button>
        </div>

        <div className="p-6 space-y-5 overflow-y-auto">
          <section className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-3">
            <div className="text-sm font-bold text-emerald-900">추천: 워드프레스 플러그인</div>
            <ol className="list-decimal pl-5 space-y-1.5 text-sm text-emerald-950/90 leading-relaxed">
              <li>아래 zip을 받아 워드프레스 → 플러그인 → 새로 추가 → 업로드</li>
              <li>설정 → 온오프CPA 상담폼 에서 홍보코드 저장</li>
              <li>페이지에 블록 「온오프CPA 상담폼」 또는 숏코드 삽입</li>
            </ol>
            <div className="flex flex-col sm:flex-row gap-2">
              <a
                href={pluginUrl}
                className="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500"
                download
              >
                <Download size={16} />
                플러그인 zip 다운로드
              </a>
              {onCopySnippet ? (
                <button
                  type="button"
                  onClick={() => onCopySnippet(shortcode)}
                  className="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl border border-emerald-300 bg-white text-emerald-800 text-sm font-bold"
                >
                  <Copy size={16} />
                  숏코드 복사
                </button>
              ) : null}
            </div>
            <pre className="text-[11px] break-all whitespace-pre-wrap bg-white/80 text-emerald-950 rounded-xl p-3 font-mono border border-emerald-100">
              {shortcode}
            </pre>
          </section>

          <section className="space-y-3">
            <div className="text-sm font-bold text-slate-900">또는 HTML 코드 직접 삽입</div>
            <ol className="space-y-3">
              {[
                '파트너센터에서 홍보 링크를 준비합니다.',
                '설치 코드를 복사합니다.',
                '워드프레스 커스텀 HTML 블록에 붙여넣고 게시합니다.',
                '테스트 접수 후 파트너센터 실적을 확인합니다.',
              ].map((body, index) => (
                <li key={body} className="flex gap-3">
                  <span className="shrink-0 w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">
                    {index + 1}
                  </span>
                  <p className="text-sm text-slate-600 leading-relaxed pt-1">{body}</p>
                </li>
              ))}
            </ol>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-2">
              <div className="text-xs font-bold text-slate-500">설치 코드 예시</div>
              <pre className="text-[11px] break-all whitespace-pre-wrap bg-slate-900 text-slate-100 rounded-xl p-3 font-mono max-h-36 overflow-y-auto">
                {snippet}
              </pre>
              {onCopySnippet && lkCode ? (
                <button
                  type="button"
                  onClick={() => onCopySnippet(snippet)}
                  className="w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold"
                >
                  <Copy size={16} />
                  HTML 설치 코드 복사
                </button>
              ) : null}
            </div>
          </section>

          <ul className="text-xs text-slate-500 space-y-1.5 leading-relaxed list-disc pl-4">
            <li>페이지 URL에 <code className="text-slate-700">?lkCode=</code>가 있으면 그 값이 우선 적용됩니다.</li>
            <li>스크립트·플러그인은 링크커넥트 도메인의 폼 API를 호출합니다.</li>
            <li>테마·캐시 플러그인 때문에 안 보이면 캐시를 비운 뒤 다시 확인해 주세요.</li>
          </ul>
        </div>

        <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 shrink-0">
          <button
            type="button"
            onClick={onClose}
            className="w-full py-3 bg-slate-900 text-white font-bold rounded-xl text-sm"
          >
            확인
          </button>
        </div>
      </div>
    </div>
  );
}
