const fs = require('fs');

// 1. AdvertiserCampaigns.tsx
let advCamp = fs.readFileSync('src/pages/advertiser/AdvertiserCampaigns.tsx', 'utf8');
advCamp = advCamp.replace(
  "import { Target, CheckCircle2, PlayCircle, PauseCircle, ChevronRight, BarChart3, Edit3, Pause , Wand2 } from 'lucide-react';",
  "import { Target, CheckCircle2, PlayCircle, PauseCircle, ChevronRight, BarChart3, Edit3, Pause , Wand2, Loader2 } from 'lucide-react';\nimport { useState } from 'react';"
);
advCamp = advCamp.replace(
  "export function AdvertiserCampaigns() {",
  `export function AdvertiserCampaigns() {
  const [isGenerating, setIsGenerating] = useState(false);
  const handleAIGenerate = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setIsGenerating(false);
      alert('AI 배너 및 홍보 문구가 성공적으로 생성되었습니다.');
    }, 2000);
  };`
);
advCamp = advCamp.replace(
  '<button className="relative z-10 shrink-0 bg-white text-indigo-900 hover:bg-indigo-50 px-6 py-3 rounded-xl font-bold text-sm shadow-[0_0_20px_rgba(255,255,255,0.3)] transition-all hover:scale-105 flex items-center gap-2">',
  '<button onClick={handleAIGenerate} disabled={isGenerating} className="relative z-10 shrink-0 bg-white text-indigo-900 hover:bg-indigo-50 px-6 py-3 rounded-xl font-bold text-sm shadow-[0_0_20px_rgba(255,255,255,0.3)] transition-all hover:scale-105 flex items-center gap-2 disabled:opacity-50 disabled:hover:scale-100">'
);
advCamp = advCamp.replace(
  '<Wand2 size={18} /> 지금 AI로 만들기',
  '{isGenerating ? <Loader2 size={18} className="animate-spin" /> : <Wand2 size={18} />} {isGenerating ? "생성 중..." : "지금 AI로 만들기"}'
);
fs.writeFileSync('src/pages/advertiser/AdvertiserCampaigns.tsx', advCamp);

// 2. AdminInspections.tsx
let adminInsp = fs.readFileSync('src/pages/admin/AdminInspections.tsx', 'utf8');
if (!adminInsp.includes('Bot')) {
  adminInsp = adminInsp.replace("AlertCircle\n}", "AlertCircle, Bot, Loader2\n}");
} else if (!adminInsp.includes('Loader2')) {
  adminInsp = adminInsp.replace("Bot", "Bot, Loader2");
}

adminInsp = adminInsp.replace(
  "const [selectedItem, setSelectedItem] = useState<any>(null);",
  "const [selectedItem, setSelectedItem] = useState<any>(null);\n  const [isAIFiltering, setIsAIFiltering] = useState(false);\n  const [showAbuseOnly, setShowAbuseOnly] = useState(false);"
);

adminInsp = adminInsp.replace(
  '<button className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-bold transition-colors ml-2 shadow-sm">',
  '<button onClick={() => { setIsAIFiltering(true); setTimeout(() => { setIsAIFiltering(false); setShowAbuseOnly(!showAbuseOnly); }, 1500); }} disabled={isAIFiltering} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors ml-2 shadow-sm ${showAbuseOnly ? "bg-indigo-600 text-white hover:bg-indigo-700" : "bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700"} disabled:opacity-50`}>'
);
adminInsp = adminInsp.replace(
  '<Bot size={16} /> AI 어뷰징 의심 조회',
  '{isAIFiltering ? <Loader2 size={16} className="animate-spin" /> : <Bot size={16} />} {isAIFiltering ? "AI 분석 중..." : (showAbuseOnly ? "AI 필터 해제" : "AI 어뷰징 의심 조회")}'
);

adminInsp = adminInsp.replace(
  '{inspectionData.map((item, i) => (',
  '{inspectionData.filter(item => !showAbuseOnly || item.reason === "허위정보").map((item, i) => ('
);

fs.writeFileSync('src/pages/admin/AdminInspections.tsx', adminInsp);

// 3. AdvertiserDb.tsx
let advDb = fs.readFileSync('src/pages/advertiser/AdvertiserDb.tsx', 'utf8');
if (!advDb.includes('Bot')) {
  advDb = advDb.replace("import { Search, Filter, Download, ChevronRight, ChevronLeft, ChevronDown, CheckCircle2, XCircle, AlertCircle, Clock, Check, X, Search as SearchIcon } from 'lucide-react';", "import { Search, Filter, Download, ChevronRight, ChevronLeft, ChevronDown, CheckCircle2, XCircle, AlertCircle, Clock, Check, X, Search as SearchIcon, Bot, Loader2 } from 'lucide-react';");
} else if (!advDb.includes('Loader2')) {
  advDb = advDb.replace("Bot }", "Bot, Loader2 }");
}

advDb = advDb.replace(
  "const [cancelComment, setCancelComment] = useState(\"\");",
  "const [cancelComment, setCancelComment] = useState(\"\");\n  const [isAIFiltering, setIsAIFiltering] = useState(false);\n  const [showAbuseOnly, setShowAbuseOnly] = useState(false);"
);

advDb = advDb.replace(
  '<button className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-bold transition-colors shadow-sm">',
  '<button onClick={() => { setIsAIFiltering(true); setTimeout(() => { setIsAIFiltering(false); setShowAbuseOnly(!showAbuseOnly); }, 1500); }} disabled={isAIFiltering} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors shadow-sm ${showAbuseOnly ? "bg-indigo-600 text-white hover:bg-indigo-700" : "bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700"} disabled:opacity-50`}>'
);

advDb = advDb.replace(
  '<Bot size={16} /> AI 어뷰징 의심 필터',
  '{isAIFiltering ? <Loader2 size={16} className="animate-spin" /> : <Bot size={16} />} {isAIFiltering ? "AI 분석 중..." : (showAbuseOnly ? "AI 필터 해제" : "AI 어뷰징 의심 필터")}'
);

advDb = advDb.replace(
  '{dbData.map((db) => (',
  '{dbData.filter(db => !showAbuseOnly || db.status === "취소/무효" || db.status === "취소요청").map((db) => ('
);
fs.writeFileSync('src/pages/advertiser/AdvertiserDb.tsx', advDb);

console.log("Done");
