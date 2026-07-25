import React from 'react';

export function SummaryCard({ title, value, suffix = '', highlight = false, dark = false, color = 'slate', icon }: any) {
  if (dark) {
    return (
      <div className="bg-slate-900 p-5 rounded-2xl shadow-sm flex flex-col justify-between h-full border border-slate-800">
        <div className="text-sm font-medium text-slate-400 mb-2 flex items-center justify-between">
          <span>{title}</span>
          {icon && <span className="text-slate-500">{icon}</span>}
        </div>
        <div className="text-2xl font-bold text-white mt-auto">{value} <span className="text-sm font-normal text-slate-400">{suffix}</span></div>
      </div>
    );
  }

  const borderColors: Record<string, string> = {
    slate: 'border-slate-200 bg-white',
    cyan: 'border-cyan-200 bg-cyan-50/50',
    blue: 'border-blue-200 bg-blue-50/50',
    emerald: 'border-emerald-200 bg-emerald-50/50',
    rose: 'border-rose-200 bg-rose-50/50',
    red: 'border-red-200 bg-red-50/50',
    yellow: 'border-yellow-200 bg-yellow-50/50',
  };

  const textColors: Record<string, string> = {
    slate: 'text-slate-900',
    cyan: 'text-cyan-700',
    blue: 'text-blue-700',
    emerald: 'text-emerald-700',
    rose: 'text-rose-700',
    red: 'text-red-700',
    yellow: 'text-yellow-700',
  };

  return (
    <div className={`p-5 rounded-2xl shadow-sm border ${highlight ? borderColors[color] : 'bg-white border-slate-200'} flex flex-col justify-between h-full`}>
      <div className="text-sm font-medium text-slate-500 mb-2 flex items-center justify-between">
        <span>{title}</span>
        {icon && <span className="text-slate-400">{icon}</span>}
      </div>
      <div className={`text-2xl font-bold mt-auto ${highlight ? textColors[color] : 'text-slate-900'}`}>{value} <span className="text-sm font-medium text-slate-400 ml-0.5">{suffix}</span></div>
    </div>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    '신규접수': 'bg-slate-100 text-slate-600 border-slate-200',
    '확인중': 'bg-blue-50 text-blue-600 border-blue-200',
    '승인완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '취소요청': 'bg-orange-50 text-orange-600 border-orange-200',
    '취소/무효': 'bg-red-50 text-red-600 border-red-200',
    '보류': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '충전완료': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '차감완료': 'bg-slate-800 text-slate-100 border-slate-900',
    '환급완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '진행중': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '일시중지': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '종료': 'bg-slate-100 text-slate-600 border-slate-200',
    '접수완료': 'bg-slate-100 text-slate-600 border-slate-200',
    '처리중': 'bg-blue-50 text-blue-600 border-blue-200',
    '답변대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '답변완료': 'bg-cyan-50 text-cyan-600 border-cyan-200',
  };

  const defaultStyle = 'bg-slate-100 text-slate-600 border-slate-200';
  const currentStyle = styles[status] || defaultStyle;

  return (
    <span className={`inline-flex items-center px-2 py-1 text-xs font-bold rounded border ${currentStyle} whitespace-nowrap`}>
      {status}
    </span>
  );
}
