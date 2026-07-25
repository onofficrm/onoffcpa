import { BookOpen, ChevronDown } from 'lucide-react';
import { CharacterIllustration, SceneDecoration } from './webtoon/WebtoonCharacters';
import { getPanelImageUrl } from './webtoon/webtoonIllustrationBrief';
import {
  characterMeta,
  episodes,
  type CharacterId,
  type WebtoonPanel,
} from './webtoon/webtoonData';

function CharacterSprite({
  id,
  scale = 1,
  mood = 'neutral',
}: {
  id: CharacterId;
  scale?: number;
  mood?: WebtoonPanel['characters'][0]['mood'];
}) {
  const meta = characterMeta[id];
  return (
    <div className="flex flex-col items-center" style={{ transform: `scale(${scale})` }}>
      <div
        className={`rounded-2xl ${meta.bg} border-2 ${meta.border} p-1 shadow-[3px_3px_0_0_rgba(15,23,42,0.12)]`}
      >
        <CharacterIllustration id={id} mood={mood} />
      </div>
      <span className="mt-1.5 text-[10px] md:text-xs font-bold text-slate-700 bg-white/90 px-2.5 py-0.5 rounded-full border-2 border-slate-900/10 shadow-sm">
        {meta.label}
      </span>
    </div>
  );
}

function SpeechBubble({
  align,
  speaker,
  speakerColor,
  text,
  highlight,
}: WebtoonPanel['bubble']) {
  const alignClass =
    align === 'left'
      ? 'ml-2 mr-auto max-w-[88%]'
      : align === 'right'
        ? 'ml-auto mr-2 max-w-[88%]'
        : 'mx-auto max-w-[92%]';

  const tailClass =
    align === 'left'
      ? 'before:left-8'
      : align === 'right'
        ? 'before:right-8 before:left-auto'
        : 'before:left-1/2 before:-translate-x-1/2';

  return (
    <div
      className={`relative bg-white border-2 border-slate-900 rounded-2xl px-4 py-3 shadow-[4px_4px_0_0_rgba(15,23,42,1)] ${alignClass} before:content-[''] before:absolute before:-bottom-2 before:w-4 before:h-4 before:bg-white before:border-b-2 before:border-r-2 before:border-slate-900 before:rotate-45 ${tailClass}`}
    >
      <p className={`text-xs font-bold mb-1 ${speakerColor}`}>{speaker}</p>
      <p className="text-sm md:text-base text-slate-800 leading-relaxed font-medium">
        {highlight && text.includes(highlight) ? (
          <>
            {text.split(highlight)[0]}
            <strong className="text-emerald-600 font-bold">{highlight}</strong>
            {text.split(highlight)[1]}
          </>
        ) : (
          text
        )}
      </p>
    </div>
  );
}

function WebtoonPanelView({
  panel,
  index,
  episodeNum,
}: {
  panel: WebtoonPanel;
  index: number;
  episodeNum: number;
}) {
  const illustrationUrl = getPanelImageUrl(episodeNum, index);
  const hasIllustration = Boolean(illustrationUrl);

  if (hasIllustration) {
    return (
      <article className="relative border-b-4 border-slate-900/10 overflow-hidden bg-slate-900">
        <div className="relative aspect-[3/4] w-full">
          <img
            src={illustrationUrl}
            alt=""
            className="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-black/55 via-black/10 to-black/50" />

          <div className="absolute top-3 left-3 w-8 h-8 rounded-full bg-white/90 border-2 border-slate-900/20 flex items-center justify-center text-xs font-black text-slate-600 z-10">
            {index + 1}
          </div>

          <div className="absolute inset-0 z-10 flex flex-col justify-between p-4">
            <SpeechBubble {...panel.bubble} />
            {panel.caption && (
              <p className="text-center text-xs md:text-sm font-bold text-white/95 tracking-wide bg-black/40 backdrop-blur-sm rounded-lg py-2 px-3 border border-white/20">
                {panel.caption}
              </p>
            )}
          </div>
        </div>
      </article>
    );
  }

  return (
    <article
      className={`relative min-h-[300px] md:min-h-[340px] bg-gradient-to-b ${panel.scene} border-b-4 border-slate-900/10 overflow-hidden`}
    >
      <div className={`absolute inset-0 ${panel.sceneAccent}`} />
      {panel.sceneProp && <SceneDecoration type={panel.sceneProp} />}

      <div className="absolute top-3 left-3 w-8 h-8 rounded-full bg-white/80 border-2 border-slate-900/20 flex items-center justify-center text-xs font-black text-slate-500">
        {index + 1}
      </div>

      <div className="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-black/8 to-transparent pointer-events-none" />

      <div className="absolute inset-x-0 bottom-2 h-[120px] md:h-[130px]">
        {panel.characters.map((ch) => (
          <div
            key={`${ch.id}-${ch.x}-${ch.y}`}
            className="absolute -translate-x-1/2 -translate-y-1/2 z-10"
            style={{ left: `${ch.x}%`, top: `${ch.y}%` }}
          >
            <CharacterSprite id={ch.id} scale={ch.scale} mood={ch.mood} />
          </div>
        ))}
      </div>

      <div className="relative z-20 px-4 pt-5 pb-4 min-h-[300px] md:min-h-[340px] flex flex-col">
        <SpeechBubble {...panel.bubble} />
        {panel.caption && (
          <p className="mt-auto pt-3 text-center text-xs md:text-sm font-bold text-slate-600/90 tracking-wide bg-white/50 rounded-lg py-2 px-3 border border-white/60">
            {panel.caption}
          </p>
        )}
      </div>
    </article>
  );
}

function EpisodeBlock({ episode }: { episode: (typeof episodes)[0] }) {
  return (
    <section className="scroll-mt-24" id={`webtoon-ep${episode.num}`}>
      <header className="sticky top-[72px] z-20 bg-slate-900 text-white px-5 py-4 border-b-4 border-emerald-400">
        <div className="flex items-center gap-3">
          <span className="shrink-0 w-9 h-9 rounded-lg bg-emerald-500 text-slate-950 font-black text-sm flex items-center justify-center">
            {episode.num}화
          </span>
          <div>
            <h3 className="font-bold text-base md:text-lg leading-tight">{episode.title}</h3>
            <p className="text-emerald-300/90 text-xs md:text-sm">{episode.subtitle}</p>
          </div>
        </div>
      </header>

      <div>
        {episode.panels.map((panel, i) => (
          <WebtoonPanelView key={i} panel={panel} index={i} episodeNum={episode.num} />
        ))}
      </div>
    </section>
  );
}

function LegendAvatar({ id }: { id: CharacterId }) {
  const meta = characterMeta[id];
  return (
    <span
      className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border-2 ${meta.bg} ${meta.border} text-slate-700 shadow-sm`}
    >
      <span className="w-7 h-7 rounded-lg overflow-hidden bg-white/60 flex items-center justify-center">
        <CharacterIllustration id={id} className="w-7 h-7" />
      </span>
      {meta.label}
      <span className="text-slate-400 font-normal">({meta.role})</span>
    </span>
  );
}

export function AffiliateWebtoon() {
  return (
    <section className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-100" id="webtoon">
      <div className="max-w-lg mx-auto mb-10 text-center">
        <p className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 text-emerald-600 text-sm font-bold mb-5 shadow-sm">
          <BookOpen className="w-4 h-4" />
          5분 만화로 배우기
        </p>
        <h2 className="text-2xl md:text-4xl font-black text-slate-900 mb-3 leading-tight">
          사장님과 홍보왕의
          <br />
          <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-cyan-500">
            제휴마케팅 입문기
          </span>
        </h2>
        <p className="text-slate-500 text-sm md:text-base leading-relaxed">
          CPA·CPS가 뭔지, 광고주와 파트너에게 왜 좋은지
          <br className="hidden sm:block" />
          웹툰 6화로 쉽게 알아보세요.
        </p>

        <div className="flex flex-wrap justify-center gap-2 mt-6">
          {(Object.keys(characterMeta) as CharacterId[]).map((id) => (
            <LegendAvatar key={id} id={id} />
          ))}
        </div>
      </div>

      <nav className="max-w-lg mx-auto mb-6 flex flex-wrap justify-center gap-2">
        {episodes.map((ep) => (
          <a
            key={ep.num}
            href={`#webtoon-ep${ep.num}`}
            className="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:border-emerald-400 hover:text-emerald-600 transition-colors shadow-sm"
          >
            {ep.num}화
          </a>
        ))}
      </nav>

      <div className="max-w-lg mx-auto rounded-2xl overflow-hidden border-4 border-slate-900 shadow-[8px_8px_0_0_rgba(15,23,42,0.15)] bg-white">
        {episodes.map((ep) => (
          <EpisodeBlock key={ep.num} episode={ep} />
        ))}

        <div className="bg-gradient-to-br from-emerald-500 to-cyan-500 px-6 py-10 text-center text-white relative overflow-hidden">
          <div className="absolute inset-0 opacity-20">
            <SceneDecoration type="sparkle" />
          </div>
          <p className="relative text-2xl font-black mb-2">END</p>
          <p className="relative text-sm md:text-base font-medium opacity-95 leading-relaxed">
            이제 제휴 마케팅, CPA, CPS가 뭔지 알겠죠?
            <br />
            링크 하나로 시작해 보세요!
          </p>
        </div>
      </div>

      <p className="max-w-lg mx-auto mt-6 text-center text-slate-400 text-xs flex items-center justify-center gap-1">
        <ChevronDown className="w-4 h-4 animate-bounce" />
        아래에서 자세한 설명도 확인하세요
      </p>
    </section>
  );
}
