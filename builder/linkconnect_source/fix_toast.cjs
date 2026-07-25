const fs = require('fs');
let content = fs.readFileSync('src/pages/EventDetail.tsx', 'utf8');

const toastCode = `
      {/* Toast Notification */}
      <div 
        className={\`fixed bottom-24 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 \${
          copiedId !== null ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'
        }\`}
      >
        <div className="bg-slate-900 text-white px-6 py-3 rounded-full shadow-lg font-medium text-sm flex items-center gap-2">
          <CheckCircle2 size={16} className="text-emerald-400" />
          홍보 문구가 복사되었습니다.
        </div>
      </div>
      
      {/* Mobile Sticky CTA */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
        <button className="w-full bg-cyan-600 hover:bg-cyan-500 text-white py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-cyan-900/20 transition-colors">
          내 홍보 링크 만들기
        </button>
      </div>
`;

content = content.replace(
  '{/* Hero Section */}',
  toastCode + '\n      {/* Hero Section */}'
);

content = content.replace(
  'const handleCopy = (text: string, id: string | number) => {',
  `const handleCopy = (text: string, id: string | number) => {
    navigator.clipboard.writeText(text).catch(() => {});`
);

fs.writeFileSync('src/pages/EventDetail.tsx', content);
