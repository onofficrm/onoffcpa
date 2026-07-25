import { categories } from '../data';

export function CategoryLinks() {
  return (
    <section className="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-slate-100">
      <div className="flex flex-wrap justify-center gap-3">
        {categories.map((category) => (
          <button 
            key={category}
            className="px-5 py-2.5 rounded-full bg-white border border-slate-200 text-slate-600 hover:text-cyan-700 hover:border-cyan-200 hover:bg-cyan-50 transition-all text-sm font-medium shadow-sm"
          >
            {category}
          </button>
        ))}
      </div>
    </section>
  );
}
