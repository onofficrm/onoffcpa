const fs = require('fs');
let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

content = content.replace(
  'className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full" style={{ width: isMounted ? \'60%\' : \'0%\' }} className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out"',
  'className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out" style={{ width: isMounted ? \'60%\' : \'0%\' }}'
);

content = content.replace(
  'className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full" style={{ width: isMounted ? \'80%\' : \'0%\' }} className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out"',
  'className="bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out" style={{ width: isMounted ? \'80%\' : \'0%\' }}'
);

fs.writeFileSync('src/pages/Events.tsx', content);
