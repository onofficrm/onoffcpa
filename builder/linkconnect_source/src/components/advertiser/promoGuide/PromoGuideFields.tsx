import { GripVertical, Loader2, Plus, Trash2, Upload, Wand2, X } from 'lucide-react';
import { type DragEvent, type KeyboardEvent, type ReactNode, useState } from 'react';
import {
  formatPromoAssetSize,
  PromoAssetSizeGuide,
  PromoAssetSizePreset,
  PROMO_ASSET_SIZE_PRESETS,
  suggestTitleForPreset,
} from './PromoAssetSizeGuide';

export function SectionCard({
  title,
  description,
  hint,
  count,
  max,
  error,
  children,
}: {
  title: string;
  description?: string;
  hint?: string;
  count?: number;
  max?: number;
  error?: string;
  children: ReactNode;
}) {
  return (
    <section className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6 space-y-4">
      <div>
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-base font-bold text-slate-900">{title}</h2>
          {typeof count === 'number' && typeof max === 'number' ? (
            <span className="text-xs font-semibold text-slate-500">
              {count} / {max}
            </span>
          ) : null}
        </div>
        {description ? <p className="text-sm text-slate-500 mt-1">{description}</p> : null}
        {hint ? <p className="text-xs text-slate-400 mt-2">{hint}</p> : null}
      </div>
      {children}
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
    </section>
  );
}

export function StringListInput({
  items,
  max,
  placeholder,
  disabled,
  onChange,
}: {
  items: string[];
  max: number;
  placeholder: string;
  disabled?: boolean;
  onChange: (items: string[]) => void;
}) {
  const filled = items.filter((v) => v.trim()).length;

  const update = (index: number, value: string) => {
    const next = [...items];
    next[index] = value;
    onChange(next);
  };

  const add = () => {
    if (filled >= max) return;
    onChange([...items, '']);
  };

  const remove = (index: number) => {
    const next = items.filter((_, i) => i !== index);
    onChange(next.length === 0 ? [''] : next);
  };

  return (
    <div className="space-y-2">
      {items.map((value, index) => (
        <div key={index} className="flex gap-2">
          <input
            type="text"
            value={value}
            disabled={disabled}
            placeholder={placeholder}
            onChange={(e) => update(index, e.target.value)}
            className="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-slate-50"
          />
          {items.length > 1 ? (
            <button
              type="button"
              disabled={disabled}
              onClick={() => remove(index)}
              className="p-2.5 rounded-xl border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 disabled:opacity-50"
              aria-label="삭제"
            >
              <Trash2 size={16} />
            </button>
          ) : null}
        </div>
      ))}
      <button
        type="button"
        disabled={disabled || filled >= max}
        onClick={add}
        className="inline-flex items-center gap-1.5 text-sm font-semibold text-cyan-700 hover:text-cyan-800 disabled:opacity-50"
      >
        <Plus size={16} /> 항목 추가
      </button>
    </div>
  );
}

export function TagInput({
  tags,
  max,
  placeholder,
  disabled,
  onChange,
}: {
  tags: string[];
  max: number;
  placeholder: string;
  disabled?: boolean;
  onChange: (tags: string[]) => void;
}) {
  const [input, setInput] = useState('');

  const addTags = (raw: string) => {
    const parts = raw
      .split(/[,，]/)
      .map((p) => p.trim())
      .filter(Boolean);
    if (parts.length === 0) return;

    const seen = new Set(tags.map((t) => t.toLowerCase()));
    const next = [...tags];
    parts.forEach((part) => {
      const key = part.toLowerCase();
      if (seen.has(key) || next.length >= max) return;
      seen.add(key);
      next.push(part);
    });
    onChange(next);
    setInput('');
  };

  const onKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTags(input);
    } else if (e.key === 'Backspace' && input === '' && tags.length > 0) {
      onChange(tags.slice(0, -1));
    }
  };

  const remove = (index: number) => {
    onChange(tags.filter((_, i) => i !== index));
  };

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap gap-2 min-h-[2rem]">
        {tags.map((tag, index) => (
          <span
            key={`${tag}-${index}`}
            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 border border-cyan-100 text-sm"
          >
            {tag}
            {!disabled ? (
              <button type="button" onClick={() => remove(index)} className="text-cyan-600 hover:text-cyan-900" aria-label="삭제">
                <X size={14} />
              </button>
            ) : null}
          </span>
        ))}
      </div>
      <input
        type="text"
        value={input}
        disabled={disabled || tags.length >= max}
        placeholder={tags.length >= max ? `최대 ${max}개까지 등록할 수 있습니다.` : placeholder}
        onChange={(e) => setInput(e.target.value)}
        onKeyDown={onKeyDown}
        onBlur={() => {
          if (input.trim()) addTags(input);
        }}
        className="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-slate-50"
      />
      <p className="text-xs text-slate-400">엔터 또는 쉼표로 키워드를 추가할 수 있습니다.</p>
    </div>
  );
}

export type PromoGuideImageItem = {
  id: number;
  imageTitle: string;
  downloadUrl: string;
  originalFilename: string;
};

const DEFAULT_SIZE_ID =
  PROMO_ASSET_SIZE_PRESETS.find((p) => p.freeFormat)?.id ??
  PROMO_ASSET_SIZE_PRESETS.find((p) => p.group === 'popular')?.id ??
  PROMO_ASSET_SIZE_PRESETS[0]?.id ??
  null;

export function ImageUploader({
  images,
  max,
  maxBytes,
  disabled,
  uploading,
  aiGenerating,
  onUpload,
  onAiGenerate,
  onDelete,
  onSort,
  onTitleChange,
  onTitleBlur,
}: {
  images: PromoGuideImageItem[];
  max: number;
  maxBytes: number;
  disabled?: boolean;
  uploading?: boolean;
  aiGenerating?: boolean;
  onUpload: (files: FileList | File[], imageTitle?: string) => void;
  onAiGenerate?: (preset: PromoAssetSizePreset, imageTitle: string) => void;
  onDelete: (id: number) => void;
  onSort: (ids: number[]) => void;
  onTitleChange: (id: number, title: string) => void;
  onTitleBlur: (id: number, title: string) => void;
}) {
  const [dragOver, setDragOver] = useState(false);
  const [dragIndex, setDragIndex] = useState<number | null>(null);
  const [selectedSizeId, setSelectedSizeId] = useState<string | null>(DEFAULT_SIZE_ID);

  const selectedPreset = PROMO_ASSET_SIZE_PRESETS.find((p) => p.id === selectedSizeId) ?? null;
  const uploadTitle = selectedPreset
    ? selectedPreset.freeFormat
      ? suggestTitleForPreset(selectedPreset)
      : `${suggestTitleForPreset(selectedPreset)} (${formatPromoAssetSize(selectedPreset)})`
    : '';
  const busy = Boolean(uploading || aiGenerating);

  const handleSizeSelect = (preset: PromoAssetSizePreset) => {
    setSelectedSizeId(preset.id);
  };

  const submitFiles = (files: FileList | File[]) => {
    if (disabled || busy) return;
    if (!selectedPreset) return;
    onUpload(files, uploadTitle);
  };

  const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    if (e.dataTransfer.files?.length) {
      submitFiles(e.dataTransfer.files);
    }
  };

  const onImageDragStart = (index: number) => setDragIndex(index);
  const onImageDrop = (index: number) => {
    if (dragIndex === null || dragIndex === index) return;
    const next = [...images];
    const [moved] = next.splice(dragIndex, 1);
    next.splice(index, 0, moved);
    onSort(next.map((img) => img.id));
    setDragIndex(null);
  };

  const maxLabel = maxBytes >= 1024 * 1024
    ? `${(maxBytes / (1024 * 1024)).toFixed(1)}MB`
    : `${Math.round(maxBytes / 1024)}KB`;

  return (
    <div className="space-y-5">
      <div className="rounded-xl border border-cyan-100 bg-cyan-50/60 px-4 py-3 text-sm text-cyan-900">
        <p className="font-bold">사이즈별 업로드</p>
        <p className="text-xs text-cyan-800/90 mt-1 leading-relaxed">
          기본은 <strong>자유형식</strong>입니다. 권장 사이즈가 있으면 아래에서 고른 뒤 업로드하세요.
        </p>
      </div>

      <PromoAssetSizeGuide
        selectedId={selectedSizeId}
        onSelect={disabled ? undefined : handleSizeSelect}
      />

      <div
        onDragOver={(e) => {
          e.preventDefault();
          if (!disabled && !busy && selectedPreset) setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={handleDrop}
        className={`rounded-2xl border-2 border-dashed p-6 text-center transition-colors ${
          !selectedPreset
            ? 'border-slate-200 bg-slate-50 opacity-70'
            : dragOver
              ? 'border-cyan-400 bg-cyan-50'
              : selectedPreset.freeFormat
                ? 'border-slate-300 bg-gradient-to-b from-slate-50 to-white'
                : 'border-cyan-300 bg-gradient-to-b from-cyan-50/80 to-white'
        } ${disabled ? 'opacity-60' : ''}`}
      >
        {selectedPreset ? (
          <>
            <div
              className={`inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-3 ${
                selectedPreset.freeFormat ? 'bg-slate-200 text-slate-700' : 'bg-cyan-100 text-cyan-700'
              }`}
            >
              <Upload size={22} />
            </div>
            <p className="text-base font-bold text-slate-900 mb-1">
              {selectedPreset.title} 업로드
            </p>
            <p
              className={`text-sm font-semibold tabular-nums mb-2 ${
                selectedPreset.freeFormat ? 'text-slate-600' : 'text-cyan-700'
              }`}
            >
              {formatPromoAssetSize(selectedPreset)}
            </p>
            <p className="text-xs text-slate-500 mb-1 max-w-md mx-auto leading-relaxed">{selectedPreset.hint}</p>
            <p className="text-xs text-slate-400 mb-4">JPG, PNG, WEBP · 파일당 최대 {maxLabel} · 최대 {max}개</p>
            <div className="flex flex-col sm:flex-row items-center justify-center gap-2">
              <label
                className={`inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold shadow-sm ${
                  selectedPreset.freeFormat
                    ? 'bg-slate-900 text-white hover:bg-slate-800'
                    : 'bg-cyan-600 text-white hover:bg-cyan-500'
                } ${
                  disabled || busy || images.length >= max ? 'pointer-events-none opacity-50' : 'cursor-pointer'
                }`}
              >
                <Upload size={16} />
                {uploading
                  ? '업로드 중...'
                  : selectedPreset.freeFormat
                    ? '자유형식으로 파일 선택'
                    : '이 사이즈로 파일 선택'}
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                  multiple
                  className="hidden"
                  disabled={disabled || busy || images.length >= max || !selectedPreset}
                  onChange={(e) => {
                    if (e.target.files?.length) submitFiles(e.target.files);
                    e.target.value = '';
                  }}
                />
              </label>
              {onAiGenerate ? (
                <button
                  type="button"
                  disabled={disabled || busy || images.length >= max}
                  onClick={() => onAiGenerate(selectedPreset, uploadTitle)}
                  className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold shadow-sm bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 disabled:opacity-50"
                >
                  {aiGenerating ? <Loader2 size={16} className="animate-spin" /> : <Wand2 size={16} />}
                  {aiGenerating ? 'AI 생성 중...' : 'AI로 이 규격 만들기'}
                </button>
              ) : null}
            </div>
          </>
        ) : (
          <p className="text-sm text-slate-500 py-4">먼저 위에서 업로드할 사이즈를 선택해 주세요.</p>
        )}
      </div>

      {images.length > 0 ? (
        <div>
          <h3 className="text-sm font-bold text-slate-900 mb-3">등록된 이미지 ({images.length})</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {images.map((img, index) => (
              <div
                key={img.id}
                draggable={!disabled}
                onDragStart={() => onImageDragStart(index)}
                onDragOver={(e) => e.preventDefault()}
                onDrop={() => onImageDrop(index)}
                className="rounded-xl border border-slate-200 overflow-hidden bg-white"
              >
                <div className="relative aspect-video bg-slate-100">
                  <img src={img.downloadUrl} alt={img.imageTitle || img.originalFilename} className="w-full h-full object-contain" />
                  {!disabled ? (
                    <div className="absolute top-2 left-2 p-1.5 rounded-lg bg-white/90 text-slate-500 cursor-grab">
                      <GripVertical size={16} />
                    </div>
                  ) : null}
                  {!disabled ? (
                    <button
                      type="button"
                      onClick={() => onDelete(img.id)}
                      className="absolute top-2 right-2 p-1.5 rounded-lg bg-white/90 text-red-500 hover:bg-red-50"
                      aria-label="삭제"
                    >
                      <Trash2 size={16} />
                    </button>
                  ) : null}
                </div>
                <div className="p-3">
                  <input
                    type="text"
                    value={img.imageTitle}
                    disabled={disabled}
                    placeholder="예: 네이버 블로그 상단 배너 (758 × 140 px)"
                    onChange={(e) => onTitleChange(img.id, e.target.value)}
                    onBlur={(e) => onTitleBlur(img.id, e.target.value)}
                    className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-slate-50"
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
}
