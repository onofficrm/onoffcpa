const fs = require('fs');

// PARTNER LAYOUT
let partnerContent = fs.readFileSync('src/layouts/PartnerLayout.tsx', 'utf8');
if (!partnerContent.includes('SuperAdminWidget')) {
  partnerContent = partnerContent.replace("import { Link } from 'react-router-dom';", "import { Link } from 'react-router-dom';\nimport { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';");
  
  partnerContent = partnerContent.replace(
    'className="min-h-screen bg-slate-50 flex flex-col md:flex-row">',
    'className="min-h-screen bg-slate-50 flex flex-col md:flex-row">\n      <SuperAdminWidget />'
  );
  
  partnerContent = partnerContent.replace(
    '<div className="flex items-center gap-4">',
    '<div className="flex items-center gap-4">\n            <SuperAdminHeaderButton />'
  );
  
  fs.writeFileSync('src/layouts/PartnerLayout.tsx', partnerContent);
}

// ADVERTISER LAYOUT
let advContent = fs.readFileSync('src/layouts/AdvertiserLayout.tsx', 'utf8');
if (!advContent.includes('SuperAdminWidget')) {
  advContent = advContent.replace("import { Link } from 'react-router-dom';", "import { Link } from 'react-router-dom';\nimport { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';");
  
  advContent = advContent.replace(
    'className="flex flex-col md:flex-row flex-1 min-h-[calc(100vh-64px)]">',
    'className="flex flex-col md:flex-row flex-1 min-h-[calc(100vh-64px)]">\n      <SuperAdminWidget />'
  );
  
  advContent = advContent.replace(
    '<div className="flex items-center gap-4">',
    '<div className="flex items-center gap-4">\n            <SuperAdminHeaderButton />'
  );
  
  fs.writeFileSync('src/layouts/AdvertiserLayout.tsx', advContent);
}
