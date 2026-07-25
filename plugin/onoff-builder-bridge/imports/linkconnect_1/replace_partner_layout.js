const fs = require('fs');
let content = fs.readFileSync('src/layouts/PartnerLayout.tsx', 'utf8');

if (!content.includes('SuperAdminWidget')) {
  // Add imports
  content = content.replace("import { Link } from 'react-router-dom';", "import { Link } from 'react-router-dom';\nimport { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';");
  
  // Add SuperAdminWidget at the top of the main container
  content = content.replace(
    'className="min-h-screen bg-slate-50 flex flex-col md:flex-row">',
    'className="min-h-screen bg-slate-50 flex flex-col md:flex-row">\n      <SuperAdminWidget />'
  );
  
  // Add SuperAdminHeaderButton inside the header's right action area
  content = content.replace(
    '<div className="flex items-center gap-4">',
    '<div className="flex items-center gap-4">\n            <SuperAdminHeaderButton />'
  );
  
  fs.writeFileSync('src/layouts/PartnerLayout.tsx', content);
}
