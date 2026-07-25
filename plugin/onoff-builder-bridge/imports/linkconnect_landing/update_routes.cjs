const fs = require('fs');
let app = fs.readFileSync('src/App.tsx', 'utf8');

if (!app.includes('EventDetail')) {
  app = app.replace("import { Events } from './pages/Events';", "import { Events } from './pages/Events';\nimport { EventDetail } from './pages/EventDetail';");
  app = app.replace('<Route path="events" element={<Events />} />', '<Route path="events" element={<Events />} />\n          <Route path="events/detail" element={<EventDetail />} />');
  fs.writeFileSync('src/App.tsx', app);
}
