const fs = require('fs');

let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

// Replace "이벤트 보기" or "지금 참여하기" or "추천 상품 보기" inside the progress section with Link
content = content.replace(
  /<button className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">\s*추천 상품 보기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full flex items-center justify-center py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">\n                  추천 상품 보기\n                </Link>'
);

content = content.replace(
  /<button className="w-full sm:w-auto px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-bold transition-colors shadow-sm">\s*이벤트 보기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-bold transition-colors shadow-sm">\n                  이벤트 보기\n                </Link>'
);

content = content.replace(
  /<button className="w-full sm:w-auto px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">\s*지금 참여하기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">\n                  지금 참여하기\n                </Link>'
);

content = content.replace(
  /<button className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">\s*남은 조건 달성하러 가기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full flex items-center justify-center py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">\n                  남은 조건 달성하러 가기\n                </Link>'
);

content = content.replace(
  /<button className="w-full py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm font-bold transition-colors shadow-sm">\s*리워드 내역 보기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full flex items-center justify-center py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm font-bold transition-colors shadow-sm">\n                  리워드 내역 보기\n                </Link>'
);

content = content.replace(
  /<button className="w-full sm:w-auto px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">\s*홍보 링크 만들기\s*<\/button>/g,
  '<Link to="/events/detail" className="w-full sm:w-auto flex items-center justify-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-bold transition-colors shadow-sm">\n                  홍보 링크 만들기\n                </Link>'
);

fs.writeFileSync('src/pages/Events.tsx', content);

