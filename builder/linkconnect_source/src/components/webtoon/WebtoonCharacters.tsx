import type { CharacterId, CharacterMood } from './webtoonData';

interface CharacterIllustrationProps {
  id: CharacterId;
  mood?: CharacterMood;
  className?: string;
}

function OwnerCharacter({ mood = 'neutral' }: { mood?: CharacterMood }) {
  const mouth =
    mood === 'worried' ? (
      <path d="M44 58 Q50 54 56 58" stroke="#92400e" strokeWidth="2" fill="none" strokeLinecap="round" />
    ) : mood === 'happy' ? (
      <path d="M42 57 Q50 64 58 57" stroke="#92400e" strokeWidth="2" fill="none" strokeLinecap="round" />
    ) : (
      <line x1="44" y1="58" x2="56" y2="58" stroke="#92400e" strokeWidth="2" strokeLinecap="round" />
    );

  const brow =
    mood === 'worried' ? (
      <>
        <line x1="38" y1="42" x2="46" y2="44" stroke="#78350f" strokeWidth="2" strokeLinecap="round" />
        <line x1="62" y1="44" x2="54" y2="42" stroke="#78350f" strokeWidth="2" strokeLinecap="round" />
      </>
    ) : null;

  return (
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <ellipse cx="50" cy="92" rx="28" ry="6" fill="#000" opacity="0.08" />
      <rect x="28" y="68" width="44" height="24" rx="8" fill="#1e3a5f" />
      <path d="M34 68 L50 58 L66 68" fill="#2563eb" />
      <rect x="46" y="74" width="8" height="14" fill="#fff" opacity="0.9" />
      <circle cx="50" cy="38" r="22" fill="#fcd9b6" />
      <path d="M28 36 C28 18 72 18 72 36 C72 44 68 48 50 48 C32 48 28 44 28 36Z" fill="#57534e" />
      <circle cx="42" cy="38" r="2.5" fill="#1c1917" />
      <circle cx="58" cy="38" r="2.5" fill="#1c1917" />
      {brow}
      {mouth}
      {mood === 'worried' && (
        <text x="68" y="28" fontSize="14">💦</text>
      )}
      <rect x="22" y="62" width="10" height="16" rx="3" fill="#fcd9b6" />
      <rect x="68" y="62" width="10" height="16" rx="3" fill="#fcd9b6" />
    </svg>
  );
}

function PartnerCharacter({ mood = 'neutral' }: { mood?: CharacterMood }) {
  const mouth =
    mood === 'excited' || mood === 'happy' ? (
      <path d="M42 56 Q50 63 58 56" stroke="#065f46" strokeWidth="2" fill="none" strokeLinecap="round" />
    ) : mood === 'thinking' ? (
      <circle cx="54" cy="57" r="2" fill="#065f46" />
    ) : (
      <path d="M44 57 Q50 60 56 57" stroke="#065f46" strokeWidth="2" fill="none" strokeLinecap="round" />
    );

  return (
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <ellipse cx="50" cy="92" rx="26" ry="6" fill="#000" opacity="0.08" />
      <path d="M30 72 C30 68 70 68 70 72 L72 88 L28 88 Z" fill="#10b981" />
      <rect x="38" y="78" width="24" height="4" rx="2" fill="#059669" />
      <circle cx="50" cy="36" r="21" fill="#fde68a" />
      <path d="M29 34 C30 16 70 16 71 34 C71 50 66 54 50 54 C34 54 29 50 29 34Z" fill="#292524" />
      <path d="M29 30 C35 42 40 44 50 44 C60 44 65 42 71 30" fill="#292524" />
      <circle cx="42" cy="38" r="2.5" fill="#1c1917" />
      <circle cx="58" cy="38" r="2.5" fill="#1c1917" />
      <ellipse cx="40" cy="44" rx="3" ry="2" fill="#fca5a5" opacity="0.5" />
      <ellipse cx="60" cy="44" rx="3" ry="2" fill="#fca5a5" opacity="0.5" />
      {mouth}
      {/* phone */}
      <rect x="62" y="58" width="14" height="22" rx="3" fill="#1e293b" stroke="#334155" strokeWidth="1.5" />
      <rect x="64" y="61" width="10" height="14" rx="1" fill="#22d3ee" />
      <circle cx="69" cy="78" r="1.5" fill="#64748b" />
      {mood === 'excited' && (
        <>
          <text x="12" y="24" fontSize="12">✨</text>
          <text x="74" y="20" fontSize="12">✨</text>
        </>
      )}
    </svg>
  );
}

function CustomerCharacter({ mood = 'neutral' }: { mood?: CharacterMood }) {
  const mouth =
    mood === 'happy' || mood === 'excited' ? (
      <path d="M42 57 Q50 64 58 57" stroke="#0369a1" strokeWidth="2" fill="none" strokeLinecap="round" />
    ) : (
      <path d="M44 58 Q50 61 56 58" stroke="#0369a1" strokeWidth="2" fill="none" strokeLinecap="round" />
    );

  return (
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <ellipse cx="50" cy="92" rx="24" ry="6" fill="#000" opacity="0.08" />
      <path d="M32 70 C32 66 68 66 68 70 L70 88 L30 88 Z" fill="#38bdf8" />
      <circle cx="50" cy="36" r="20" fill="#fde68a" />
      <path d="M30 34 C32 18 68 18 70 34 C70 48 65 52 50 52 C35 52 30 48 30 34Z" fill="#57534e" />
      <circle cx="43" cy="38" r="2.5" fill="#1c1917" />
      <circle cx="57" cy="38" r="2.5" fill="#1c1917" />
      {mouth}
      {/* shopping bag */}
      <rect x="18" y="62" width="16" height="18" rx="3" fill="#f97316" />
      <path d="M22 62 C22 56 30 56 30 62" stroke="#ea580c" strokeWidth="2" fill="none" />
      {mood === 'excited' && (
        <rect x="58" y="58" width="20" height="14" rx="2" fill="#6366f1" opacity="0.9" />
      )}
    </svg>
  );
}

function GuideCharacter({ mood = 'neutral' }: { mood?: CharacterMood }) {
  return (
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <ellipse cx="50" cy="92" rx="30" ry="6" fill="#000" opacity="0.08" />
      {/* body */}
      <rect x="25" y="48" width="50" height="36" rx="14" fill="#4f46e5" />
      <rect x="30" y="54" width="40" height="22" rx="8" fill="#6366f1" />
      {/* link icon on chest */}
      <path d="M38 65 L44 59 M56 59 L62 65 M44 59 C42 57 46 55 48 57 M56 59 C58 57 54 55 52 57" stroke="#a5f3fc" strokeWidth="2.5" strokeLinecap="round" />
      {/* head */}
      <circle cx="50" cy="32" r="22" fill="#818cf8" />
      <circle cx="50" cy="32" r="18" fill="#a5b4fc" />
      {/* face screen */}
      <rect x="36" y="24" width="28" height="16" rx="6" fill="#1e1b4b" />
      <circle cx="43" cy="32" r="3" fill="#34d399" />
      <circle cx="57" cy="32" r="3" fill="#34d399" />
      {mood === 'happy' ? (
        <path d="M42 38 Q50 44 58 38" stroke="#34d399" strokeWidth="2" fill="none" />
      ) : (
        <line x1="42" y1="40" x2="58" y2="40" stroke="#34d399" strokeWidth="2" strokeLinecap="round" />
      )}
      {/* antenna */}
      <line x1="50" y1="10" x2="50" y2="16" stroke="#6366f1" strokeWidth="3" strokeLinecap="round" />
      <circle cx="50" cy="8" r="4" fill="#22d3ee" />
      {(mood === 'happy' || mood === 'excited') && (
        <>
          <text x="8" y="20" fontSize="11">✨</text>
          <text x="78" y="18" fontSize="11">✨</text>
        </>
      )}
      {/* arms */}
      <rect x="14" y="56" width="12" height="8" rx="4" fill="#6366f1" />
      <rect x="74" y="56" width="12" height="8" rx="4" fill="#6366f1" />
    </svg>
  );
}

export function CharacterIllustration({ id, mood = 'neutral', className = '' }: CharacterIllustrationProps) {
  const chars: Record<CharacterId, JSX.Element> = {
    owner: <OwnerCharacter mood={mood} />,
    partner: <PartnerCharacter mood={mood} />,
    customer: <CustomerCharacter mood={mood} />,
    guide: <GuideCharacter mood={mood} />,
  };

  return (
    <div className={`w-20 h-20 md:w-24 md:h-24 ${className}`}>
      {chars[id]}
    </div>
  );
}

/** 장면별 배경 소품 SVG */
export function SceneDecoration({ type }: { type: 'shop' | 'blog' | 'compare' | 'money' | 'sparkle' }) {
  if (type === 'shop') {
    return (
      <svg className="absolute bottom-4 left-4 w-24 h-16 opacity-30" viewBox="0 0 96 64" aria-hidden="true">
        <rect x="8" y="24" width="80" height="36" rx="4" fill="#f59e0b" />
        <path d="M8 24 L48 8 L88 24" fill="#d97706" />
        <rect x="36" y="40" width="24" height="20" rx="2" fill="#fff" />
      </svg>
    );
  }
  if (type === 'blog') {
    return (
      <svg className="absolute top-16 right-4 w-20 h-20 opacity-25" viewBox="0 0 80 80" aria-hidden="true">
        <rect x="10" y="10" width="60" height="60" rx="6" fill="#fff" stroke="#10b981" strokeWidth="2" />
        <line x1="20" y1="26" x2="60" y2="26" stroke="#10b981" strokeWidth="3" />
        <line x1="20" y1="38" x2="55" y2="38" stroke="#cbd5e1" strokeWidth="2" />
        <line x1="20" y1="48" x2="50" y2="48" stroke="#cbd5e1" strokeWidth="2" />
      </svg>
    );
  }
  if (type === 'compare') {
    return (
      <>
        <div className="absolute inset-y-0 left-0 w-1/2 bg-emerald-400/10 border-r-2 border-dashed border-emerald-400/40" />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0 bg-white border-2 border-slate-900 rounded-full px-3 py-1 text-xs font-black text-slate-700 shadow-md">
          VS
        </div>
        <div className="absolute top-20 left-4 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-md">CPA</div>
        <div className="absolute top-20 right-4 bg-cyan-500 text-white text-[10px] font-bold px-2 py-1 rounded-md">CPS</div>
      </>
    );
  }
  if (type === 'money') {
    return (
      <svg className="absolute top-14 right-6 w-16 h-16 opacity-40" viewBox="0 0 64 64" aria-hidden="true">
        <circle cx="32" cy="32" r="28" fill="#fbbf24" stroke="#f59e0b" strokeWidth="2" />
        <text x="32" y="38" textAnchor="middle" fontSize="18" fontWeight="bold" fill="#92400e">₩</text>
      </svg>
    );
  }
  return (
    <div className="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
      {[...Array(6)].map((_, i) => (
        <span
          key={i}
          className="absolute text-lg opacity-20"
          style={{ left: `${15 + i * 14}%`, top: `${20 + (i % 3) * 25}%` }}
        >
          ✨
        </span>
      ))}
    </div>
  );
}
