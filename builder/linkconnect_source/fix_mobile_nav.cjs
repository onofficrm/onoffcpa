const fs = require('fs');

let header = fs.readFileSync('src/components/Header.tsx', 'utf8');
if (!header.includes('이벤트/프로모션</Link>          <Link to="/partner" onClick')) {
  header = header.replace(
    '<a href="/#cps" onClick={() => setIsMobileMenuOpen(false)} className="block px-3 py-3 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg">CPS</a>',
    '<a href="/#cps" onClick={() => setIsMobileMenuOpen(false)} className="block px-3 py-3 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg">CPS</a>\n          <Link to="/events" onClick={() => setIsMobileMenuOpen(false)} className="block px-3 py-3 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg">이벤트/프로모션</Link>'
  );
  fs.writeFileSync('src/components/Header.tsx', header);
}
