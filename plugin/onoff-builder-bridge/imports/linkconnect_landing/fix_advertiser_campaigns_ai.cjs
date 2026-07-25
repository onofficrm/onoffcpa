const fs = require('fs');
let content = fs.readFileSync('src/pages/advertiser/AdvertiserCampaigns.tsx', 'utf8');

if (!content.includes('Wand2')) {
  content = content.replace(
    /import \{[^\}]+\} from 'lucide-react';/,
    (match) => match.replace('}', ', Wand2 }')
  );
}

const aiBanner = `
      {/* AI Banner Generator */}
      <div className="mb-8 bg-gradient-to-r from-indigo-900 to-purple-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div className="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div className="relative z-10">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/20 border border-white/10 text-xs font-bold mb-3 backdrop-blur-sm">
            <Wand2 size={12} className="text-purple-300" /> AI 베타
          </div>
          <h3 className="text-xl font-bold mb-2">원클릭 AI 썸네일 & 배너 생성</h3>
          <p className="text-indigo-200 text-sm max-w-md">상품명과 타겟만 입력하면, AI가 전환율이 높은 광고 이미지와 홍보 문구를 자동으로 생성해 드립니다.</p>
        </div>
        <button className="relative z-10 shrink-0 bg-white text-indigo-900 hover:bg-indigo-50 px-6 py-3 rounded-xl font-bold text-sm shadow-[0_0_20px_rgba(255,255,255,0.3)] transition-all hover:scale-105 flex items-center gap-2">
          <Wand2 size={18} /> 지금 AI로 만들기
        </button>
      </div>

      {/* Summary Cards */}
`;

content = content.replace(
  '{/* Summary Cards */}',
  aiBanner
);

fs.writeFileSync('src/pages/advertiser/AdvertiserCampaigns.tsx', content);
