const fs = require('fs');
let content = fs.readFileSync('src/pages/admin/AdminCampaigns.tsx', 'utf8');

// just replace multiple "{isEditMode ? (" with a single one if they are adjacent
content = content.replace(
  /\{isEditMode \? \(\s*\{isEditMode \? \(/g,
  "{isEditMode ? ("
);

fs.writeFileSync('src/pages/admin/AdminCampaigns.tsx', content);
