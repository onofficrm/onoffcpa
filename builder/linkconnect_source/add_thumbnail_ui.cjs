const fs = require('fs');
let content = fs.readFileSync('src/pages/admin/AdminCampaigns.tsx', 'utf8');

// Add Image and Upload to imports
content = content.replace(
  'Database\n} from \'lucide-react\';',
  'Database, Image, Upload\n} from \'lucide-react\';'
);

const thumbnailSection = `
                    <div className="col-span-2">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">썸네일 이미지</label>
                      <div className="flex items-start gap-4">
                        <div className="w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 relative group">
                          {selectedCampaign.thumbnail ? (
                            <img src={selectedCampaign.thumbnail} alt="Thumbnail" className="w-full h-full object-cover" />
                          ) : (
                            <Image className="w-8 h-8 text-slate-300" />
                          )}
                          {isEditMode && (
                            <div className="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                              <Upload className="w-5 h-5 text-white" />
                            </div>
                          )}
                        </div>
                        <div className="flex-1">
                          {isEditMode ? (
                            <div className="border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer h-24">
                              <Upload className="w-5 h-5 text-cyan-500 mb-1" />
                              <span className="text-sm font-medium text-slate-600">클릭하여 이미지 업로드</span>
                              <span className="text-xs text-slate-400 mt-0.5">권장 크기: 800 x 600 (JPG, PNG)</span>
                            </div>
                          ) : (
                            <div className="h-24 flex items-center text-sm text-slate-500 bg-slate-50 rounded-xl px-4 border border-slate-100">
                              등록된 썸네일 이미지가 없습니다.
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
`;

content = content.replace(
  '<div className="col-span-2">\n                      <label className="block text-xs font-medium text-slate-500 mb-1.5">광고상품명</label>',
  thumbnailSection + '\n                    <div className="col-span-2">\n                      <label className="block text-xs font-medium text-slate-500 mb-1.5">광고상품명</label>'
);

fs.writeFileSync('src/pages/admin/AdminCampaigns.tsx', content);
