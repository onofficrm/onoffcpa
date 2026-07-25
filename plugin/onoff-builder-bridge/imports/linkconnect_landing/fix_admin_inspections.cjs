const fs = require('fs');
let content = fs.readFileSync('src/pages/admin/AdminInspections.tsx', 'utf8');

// Add Bot to lucide-react imports if it's not there
if (!content.includes('Bot')) {
  content = content.replace(
    /import \{[^\}]+\} from 'lucide-react';/,
    (match) => match.replace('}', ', Bot }')
  );
}

const aiButton = `
                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer ml-2">
                  <input type="checkbox" className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  이의신청 건만 보기
                </label>
                <button className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-bold transition-colors ml-2 shadow-sm">
                  <Bot size={16} /> AI 어뷰징 의심 조회
                </button>
`;

content = content.replace(
  /<label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer ml-2">[\s\S]*?이의신청 건만 보기[\s\S]*?<\/label>/,
  aiButton
);

fs.writeFileSync('src/pages/admin/AdminInspections.tsx', content);
