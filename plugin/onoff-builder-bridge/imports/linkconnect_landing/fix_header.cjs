const fs = require('fs');
let content = fs.readFileSync('src/components/Header.tsx', 'utf8');

if (!content.includes('SuperAdminHeaderButton')) {
  content = content.replace(
    "import { Link } from 'react-router-dom';",
    "import { Link } from 'react-router-dom';\nimport { SuperAdminHeaderButton } from './SuperAdminWidget';"
  );
  
  content = content.replace(
    '<div className="hidden md:flex items-center gap-4">',
    '<div className="hidden md:flex items-center gap-4">\n            <SuperAdminHeaderButton />'
  );
  
  fs.writeFileSync('src/components/Header.tsx', content);
}
