const fs = require('fs');
let content = fs.readFileSync('src/pages/advertiser/AdvertiserDb.tsx', 'utf8');

if (!content.includes('Bot')) {
  content = content.replace(
    /import \{[^\}]+\} from 'lucide-react';/,
    (match) => match.replace('}', ', Bot }')
  );
}

const aiButton = `
        <div className="flex items-center gap-2">
          <input type="checkbox" id="needsAction" className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
          <label htmlFor="needsAction" className="text-sm font-medium text-slate-700 cursor-pointer">처리 필요만 보기</label>
        </div>
        <button className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-bold transition-colors shadow-sm">
          <Bot size={16} /> AI 어뷰징 의심 필터
        </button>
`;

content = content.replace(
  /<div className="flex items-center gap-2">\s*<input type="checkbox" id="needsAction"[\s\S]*?처리 필요만 보기<\/label>\s*<\/div>/,
  aiButton
);

fs.writeFileSync('src/pages/advertiser/AdvertiserDb.tsx', content);
