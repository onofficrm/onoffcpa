const fs = require('fs');
let content = fs.readFileSync('src/pages/admin/AdminCampaigns.tsx', 'utf8');

if (!content.includes('Wand2')) {
  content = content.replace(
    /import \{[^\}]+\} from 'lucide-react';/,
    (match) => match.replace('}', ', Wand2, Loader2 }')
  );
}

const aiLogic = `
  const [isGenerating, setIsGenerating] = useState(false);
  const handleAIGenerate = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setSelectedCampaign({ ...selectedCampaign, thumbnail: 'https://images.unsplash.com/photo-1557682250-33bd709cbe85?auto=format&fit=crop&q=80' });
      setIsGenerating(false);
    }, 2000);
  };
`;

content = content.replace(
  'const handleThumbnailClick = () => {',
  aiLogic + '\n  const handleThumbnailClick = () => {'
);

const aiButton = `
                          {isEditMode ? (
                            <div className="flex flex-col gap-2 h-full justify-center">
                              <div className="border-2 border-dashed border-slate-200 rounded-xl p-2 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer flex-1" onClick={handleThumbnailClick}>
                                <Upload className="w-5 h-5 text-cyan-500 mb-1" />
                                <span className="text-xs font-medium text-slate-600">업로드</span>
                              </div>
                              <button onClick={handleAIGenerate} disabled={isGenerating} className="flex-1 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-xl flex items-center justify-center gap-1.5 text-xs font-bold transition-colors shadow-sm disabled:opacity-50">
                                {isGenerating ? <Loader2 size={14} className="animate-spin" /> : <Wand2 size={14} />} AI 생성
                              </button>
                            </div>
                          ) : (
`;

content = content.replace(
  /<div className="border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer h-24" onClick=\{handleThumbnailClick\}>[\s\S]*?<\/div>\s*\) : \(/,
  aiButton
);

fs.writeFileSync('src/pages/admin/AdminCampaigns.tsx', content);
