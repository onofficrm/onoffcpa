import { Calendar, ChevronRight, Gift } from 'lucide-react';
import { Link } from 'react-router-dom';
import { events } from '../data';

export function EventBoard() {
  return (
    <section id="events" className="py-24 bg-white border-b border-slate-100">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-end justify-between mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2 flex items-center gap-2">
              <Gift className="w-6 h-6 text-amber-500" />
              수익을 더 높이는 이벤트와 프로모션
            </h2>
          </div>
          <Link to="/events" className="text-sm font-medium text-slate-500 hover:text-slate-900 flex items-center transition-colors">
            더보기 <ChevronRight className="w-4 h-4 ml-1" />
          </Link>
        </div>

        <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
          {events.map((event, i) => (
            <div 
              key={event.id} 
              className={`group flex items-center justify-between p-5 hover:bg-slate-50 transition-colors ${
                i !== events.length - 1 ? 'border-b border-slate-100' : ''
              }`}
            >
              <div className="flex items-center gap-4">
                <span className="text-sm font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-md">
                  진행중
                </span>
                <span className="font-medium text-slate-700 group-hover:text-emerald-600 transition-colors">
                  {event.title}
                </span>
              </div>
              <div className="hidden sm:flex items-center gap-2 text-sm text-slate-500">
                <Calendar className="w-4 h-4" />
                {event.date}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
