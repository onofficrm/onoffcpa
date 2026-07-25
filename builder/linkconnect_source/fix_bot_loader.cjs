const fs = require('fs');

let adminInsp = fs.readFileSync('src/pages/admin/AdminInspections.tsx', 'utf8');
adminInsp = adminInsp.replace(/<Bot, Loader2 size=\{16\} \/>/g, "<Bot size={16} />");
fs.writeFileSync('src/pages/admin/AdminInspections.tsx', adminInsp);

let advDb = fs.readFileSync('src/pages/advertiser/AdvertiserDb.tsx', 'utf8');
advDb = advDb.replace(/<Bot, Loader2 size=\{16\} \/>/g, "<Bot size={16} />");
fs.writeFileSync('src/pages/advertiser/AdvertiserDb.tsx', advDb);

