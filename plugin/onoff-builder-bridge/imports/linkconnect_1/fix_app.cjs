const fs = require('fs');

let app = fs.readFileSync('src/App.tsx', 'utf8');
if (!app.includes('import { Events }')) {
  app = app.replace("import { Home } from './pages/Home';", "import { Home } from './pages/Home';\nimport { Events } from './pages/Events';");
  app = app.replace("<Route path=\"cpa-list\" element={<CpaList />} />", "<Route path=\"cpa-list\" element={<CpaList />} />\n          <Route path=\"events\" element={<Events />} />");
  fs.writeFileSync('src/App.tsx', app);
}

let header = fs.readFileSync('src/components/Header.tsx', 'utf8');
header = header.replace(
  '<a href="/#events" className="text-base font-medium text-slate-300 hover:text-white transition-colors">이벤트/프로모션</a>',
  '<Link to="/events" className="text-base font-medium text-slate-300 hover:text-white transition-colors">이벤트/프로모션</Link>'
);
header = header.replace(
  '<a href="/#events" onClick={() => setIsMobileMenuOpen(false)} className="block px-3 py-3 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg">이벤트/프로모션</a>',
  '<Link to="/events" onClick={() => setIsMobileMenuOpen(false)} className="block px-3 py-3 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg">이벤트/프로모션</Link>'
);

fs.writeFileSync('src/components/Header.tsx', header);

