import { EmbedWidgetLivePreview } from './EmbedWidgetLivePreview';
import {
  EMBED_PRESETS,
  withEmbedPreset,
  type EmbedPresetId,
} from '../../lib/embedPresets';
import type { PartnerEmbedOptions } from '../../lib/partnerEmbed';

type Props = {
  selectedId: EmbedPresetId;
  options: PartnerEmbedOptions;
  brandName?: string;
  phoneHint?: string;
  onSelect: (id: EmbedPresetId) => void;
};

/** 상담폼 디자인 프리셋별 축소 미리보기 갤러리 */
export function EmbedDesignGallery({
  selectedId,
  options,
  brandName = '상담',
  phoneHint,
  onSelect,
}: Props) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
      {EMBED_PRESETS.map((preset) => {
        const previewOptions = withEmbedPreset(options, preset.id, { applyAccentHint: true });
        const active = selectedId === preset.id;
        return (
          <button
            key={preset.id}
            type="button"
            onClick={() => onSelect(preset.id)}
            className={`rounded-2xl border text-left transition-all overflow-hidden ${
              active
                ? 'border-cyan-500 bg-cyan-50 ring-2 ring-cyan-200'
                : 'border-slate-200 bg-white hover:border-slate-300'
            }`}
          >
            <div className="px-3 pt-3 pb-1.5 flex items-start justify-between gap-2">
              <div>
                <div className="text-xs font-bold text-slate-900">{preset.label}</div>
                <div className="text-[10px] text-slate-500 mt-0.5 leading-snug">{preset.desc}</div>
              </div>
              {active ? (
                <span className="shrink-0 text-[10px] font-bold text-cyan-800 bg-cyan-100 px-1.5 py-0.5 rounded-md">
                  선택
                </span>
              ) : null}
            </div>
            <div className="mx-3 mb-3 h-[128px] overflow-hidden rounded-xl border border-slate-200/80 bg-slate-100 pointer-events-none">
              <div
                className="origin-top-left"
                style={{
                  transform: 'scale(0.42)',
                  width: 320,
                }}
              >
                <EmbedWidgetLivePreview
                  mode="form"
                  options={previewOptions}
                  device="mobile"
                  stage="form"
                  brandName={brandName}
                  phoneHint={phoneHint || '안심번호 010-0000-0000'}
                />
              </div>
            </div>
          </button>
        );
      })}
    </div>
  );
}
