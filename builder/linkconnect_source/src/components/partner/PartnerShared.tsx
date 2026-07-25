import React from 'react';

export function SummaryCard({ title, value, suffix = '', icon, highlight = false, dark = false }: any) {
  if (dark) {
    return (
      <div className="bg-slate-900 p-5 md:p-6 rounded-2xl shadow-lg flex flex-col justify-between h-full">
        <div className="flex items-center justify-between mb-4">
          <div className="text-sm font-medium text-slate-400">{title}</div>
          {icon && <div className="p-2 rounded-xl bg-slate-800 shrink-0">{icon}</div>}
        </div>
        <div className="text-2xl md:text-3xl font-bold text-white mt-auto">{value} <span className="text-sm font-normal text-slate-400">{suffix}</span></div>
      </div>
    );
  }
  
  return (
    <div className={`bg-white p-5 md:p-6 rounded-2xl shadow-sm border ${highlight ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'} flex flex-col justify-between h-full`}>
      <div className="flex items-center justify-between mb-4">
        <div className="text-sm font-medium text-slate-500">{title}</div>
        {icon && <div className={`p-2 rounded-xl shrink-0 ${highlight ? 'bg-emerald-100' : 'bg-slate-50'}`}>{icon}</div>}
      </div>
      <div className={`text-2xl md:text-3xl font-bold ${highlight ? 'text-emerald-600' : 'text-slate-900'} mt-auto`}>{value} <span className="text-sm md:text-base font-normal text-slate-400">{suffix}</span></div>
    </div>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    '접수완료': 'bg-slate-100 text-slate-600 border-slate-200',
    '검수중': 'bg-blue-50 text-blue-600 border-blue-200',
    '승인완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '취소/무효': 'bg-red-50 text-red-600 border-red-200',
    '확정완료': 'bg-purple-50 text-purple-600 border-purple-200',
    '정산완료': 'bg-slate-900 text-white border-slate-900',
    '보류': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '중복의심': 'bg-orange-50 text-orange-600 border-orange-200',
    
    // For other terms
    '지급완료': 'bg-slate-900 text-white border-slate-900',
    '승인대기': 'bg-blue-50 text-blue-600 border-blue-200',
    '신청완료': 'bg-slate-100 text-slate-600 border-slate-200',
    '반려': 'bg-red-50 text-red-600 border-red-200',
    '답변대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '답변완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
  };

  const defaultStyle = 'bg-slate-100 text-slate-600 border-slate-200';
  const currentStyle = styles[status] || defaultStyle;

  return (
    <span className={`inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md border ${currentStyle} whitespace-nowrap`}>
      {status}
    </span>
  );
}
