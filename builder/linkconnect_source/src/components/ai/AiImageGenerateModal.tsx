import { useEffect, useState } from 'react';
import { Loader2, Wand2, X } from 'lucide-react';

export type AiImageGenerateOptions = {
  mood: string;
  includeText: boolean;
  overlayText: string;
  extra: string;
};

export const EMPTY_AI_IMAGE_OPTIONS: AiImageGenerateOptions = {
  mood: '신뢰감 있는',
  includeText: false,
  overlayText: '',
  extra: '',
};

const MOOD_PRESETS = ['신뢰감 있는', '밝은·희망적인', '전문적인', '차분한', '활기찬', '고급스러운'] as const;

type Props = {
  open: boolean;
  title?: string;
  subtitle?: string;
  busy?: boolean;
  initial?: Partial<AiImageGenerateOptions>;
  defaultOverlayText?: string;
  onClose: () => void;
  onConfirm: (options: AiImageGenerateOptions) => void;
};

export function AiImageGenerateModal({
  open,
  title = 'AI 이미지 생성',
  subtitle,
  busy = false,
  initial,
  defaultOverlayText = '',
  onClose,
  onConfirm,
}: Props) {
  const [mood, setMood] = useState(EMPTY_AI_IMAGE_OPTIONS.mood);
  const [includeText, setIncludeText] = useState(false);
  const [overlayText, setOverlayText] = useState('');
  const [extra, setExtra] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    if (!open) return;
    setMood(initial?.mood?.trim() || EMPTY_AI_IMAGE_OPTIONS.mood);
    setIncludeText(Boolean(initial?.includeText));
    setOverlayText(initial?.overlayText?.trim() || defaultOverlayText || '');
    setExtra(initial?.extra?.trim() || '');
    setError('');
  }, [open, initial, defaultOverlayText]);

  if (!open) return null;

  const submit = () => {
    const nextMood = mood.trim();
    if (!nextMood) {
      setError('분위기를 선택하거나 입력해 주세요.');
      return;
    }
    if (includeText && overlayText.trim() === '') {
      setError('이미지에 넣을 텍스트를 입력해 주세요.');
      return;
    }
    setError('');
    onConfirm({
      mood: nextMood,
      includeText,
      overlayText: includeText ? overlayText.trim() : '',
      extra: extra.trim(),
    });
  };

  return (
    <div
      className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/50 backdrop-blur-sm"
      onClick={() => {
        if (!busy) onClose();
      }}
    >
      <div
        className="bg-white rounded-t-3xl sm:rounded-3xl shadow-xl w-full max-w-lg overflow-hidden max-h-[92vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="ai-image-generate-title"
      >
        <div className="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3 shrink-0">
          <div>
            <h3 id="ai-image-generate-title" className="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Wand2 size={18} className="text-indigo-600" />
              {title}
            </h3>
            {subtitle ? <p className="text-xs text-slate-500 mt-1">{subtitle}</p> : null}
          </div>
          <button
            type="button"
            disabled={busy}
            onClick={onClose}
            className="text-slate-400 hover:text-slate-600 disabled:opacity-50"
            aria-label="닫기"
          >
            <X size={20} />
          </button>
        </div>

        <div className="p-5 sm:p-6 space-y-5 overflow-y-auto">
          <div>
            <label className="block text-sm font-bold text-slate-800 mb-2">분위기</label>
            <div className="flex flex-wrap gap-2 mb-2">
              {MOOD_PRESETS.map((preset) => {
                const active = mood === preset;
                return (
                  <button
                    key={preset}
                    type="button"
                    disabled={busy}
                    onClick={() => setMood(preset)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors ${
                      active
                        ? 'bg-indigo-50 border-indigo-300 text-indigo-800'
                        : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                    } disabled:opacity-50`}
                  >
                    {preset}
                  </button>
                );
              })}
            </div>
            <input
              type="text"
              value={mood}
              disabled={busy}
              onChange={(e) => setMood(e.target.value)}
              placeholder="예: 신뢰감 있는, 밝은 상담실 느낌"
              className="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50"
            />
          </div>

          <div>
            <label className="block text-sm font-bold text-slate-800 mb-2">텍스트 넣기</label>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                disabled={busy}
                onClick={() => setIncludeText(false)}
                className={`rounded-xl border px-3 py-3 text-sm font-semibold transition-colors ${
                  !includeText
                    ? 'bg-slate-900 text-white border-slate-900'
                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                } disabled:opacity-50`}
              >
                넣지 않음
              </button>
              <button
                type="button"
                disabled={busy}
                onClick={() => setIncludeText(true)}
                className={`rounded-xl border px-3 py-3 text-sm font-semibold transition-colors ${
                  includeText
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                } disabled:opacity-50`}
              >
                텍스트 포함
              </button>
            </div>
            <p className="text-xs text-slate-400 mt-2 leading-relaxed">
              ChatGPT 이미지(GPT Image)는 한글을 비교적 잘 그립니다. 문구를 넣을 때는 짧고 명확하게 입력하세요.
            </p>
          </div>

          {includeText ? (
            <div>
              <label className="block text-sm font-bold text-slate-800 mb-2">넣을 텍스트</label>
              <textarea
                value={overlayText}
                disabled={busy}
                rows={3}
                onChange={(e) => setOverlayText(e.target.value)}
                placeholder={'예:\n개인회생 무료상담\n전문 법률 지원'}
                className="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50 resize-y min-h-[5rem]"
              />
              <p className="text-xs text-slate-400 mt-1.5">줄바꿈으로 여러 줄을 넣을 수 있습니다. 짧게 쓸수록 정확도가 높아집니다.</p>
            </div>
          ) : null}

          <div>
            <label className="block text-sm font-bold text-slate-800 mb-2">
              추가 지시사항 <span className="font-medium text-slate-400">(선택)</span>
            </label>
            <input
              type="text"
              value={extra}
              disabled={busy}
              onChange={(e) => setExtra(e.target.value)}
              placeholder="예: 왼쪽 여백 넓게, 아이콘 중심으로"
              className="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50"
            />
          </div>

          {error ? <p className="text-sm text-red-600">{error}</p> : null}
        </div>

        <div className="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3 shrink-0">
          <button
            type="button"
            disabled={busy}
            onClick={onClose}
            className="flex-1 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 disabled:opacity-50"
          >
            취소
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={submit}
            className="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm disabled:opacity-60 inline-flex items-center justify-center gap-2"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : <Wand2 size={16} />}
            {busy ? '생성 중...' : '생성하기'}
          </button>
        </div>
      </div>
    </div>
  );
}
