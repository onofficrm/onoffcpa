const fs = require('fs');

// PARTNER LAYOUT
let partner = fs.readFileSync('src/layouts/PartnerLayout.tsx', 'utf8');
partner = partner.replace(
  '<div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">\n      <SuperAdminWidget />',
  '<div className="min-h-screen bg-slate-50 flex flex-col">\n      <SuperAdminWidget />\n      <div className="flex flex-col md:flex-row flex-1">'
);
partner = partner.replace(/    <\/div>\n  \);\n}/, '      </div>\n    </div>\n  );\n}');
fs.writeFileSync('src/layouts/PartnerLayout.tsx', partner);

// ADVERTISER LAYOUT
let adv = fs.readFileSync('src/layouts/AdvertiserLayout.tsx', 'utf8');
adv = adv.replace(
  '<div className="flex flex-col md:flex-row flex-1 min-h-[calc(100vh-64px)]">\n      <SuperAdminWidget />',
  '<div className="min-h-screen bg-slate-50 flex flex-col">\n      <SuperAdminWidget />\n      <div className="flex flex-col md:flex-row flex-1">'
);
adv = adv.replace(/    <\/div>\n  \);\n}/, '      </div>\n    </div>\n  );\n}');
fs.writeFileSync('src/layouts/AdvertiserLayout.tsx', adv);
