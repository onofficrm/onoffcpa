const fs = require('fs');
let content = fs.readFileSync('src/pages/Events.tsx', 'utf8');

// Use effect for triggering progress bar animations
if (!content.includes('const [isMounted, setIsMounted] = useState(false)')) {
  content = content.replace(
    "const [activeTab, setActiveTab] = useState('전체');",
    "const [activeTab, setActiveTab] = useState('전체');\n  const [isMounted, setIsMounted] = useState(false);\n  React.useEffect(() => { setTimeout(() => setIsMounted(true), 100); }, []);"
  );
  
  // Apply to the first progress bar
  content = content.replace(
    "style={{ width: '60%' }}",
    "style={{ width: isMounted ? '60%' : '0%' }} className=\"bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out\""
  );
  
  // Apply to the second progress bar
  content = content.replace(
    "style={{ width: '80%' }}",
    "style={{ width: isMounted ? '80%' : '0%' }} className=\"bg-gradient-to-r from-cyan-400 to-cyan-500 h-2.5 rounded-full transition-all duration-1000 ease-out\""
  );
  
  // Hover effects on cards
  content = content.replace(
    /hover:border-cyan-300 transition-colors/g,
    "hover:border-cyan-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
  );

  fs.writeFileSync('src/pages/Events.tsx', content);
  console.log('Animation added');
} else {
  console.log('Already added');
}
