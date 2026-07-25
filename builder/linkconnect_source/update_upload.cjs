const fs = require('fs');
let content = fs.readFileSync('src/pages/admin/AdminCampaigns.tsx', 'utf8');

const imageHandler = `
  const fileInputRef = React.useRef<HTMLInputElement>(null);
  
  const handleThumbnailClick = () => {
    if (isEditMode) {
      fileInputRef.current?.click();
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const url = URL.createObjectURL(file);
      setSelectedCampaign({ ...selectedCampaign, thumbnail: url });
    }
  };
`;

content = content.replace(
  'const handleCreateNew = () => {',
  imageHandler + '\n  const handleCreateNew = () => {'
);

content = content.replace(
  '<div className="w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 relative group">',
  '<div className="w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 relative group" onClick={handleThumbnailClick}>\n                          <input type="file" ref={fileInputRef} className="hidden" accept="image/*" onChange={handleFileChange} />'
);

content = content.replace(
  '<div className="border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer h-24">',
  '<div className="border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer h-24" onClick={handleThumbnailClick}>'
);

fs.writeFileSync('src/pages/admin/AdminCampaigns.tsx', content);
