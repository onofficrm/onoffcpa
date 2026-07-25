import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { ShieldAlert, ShieldCheck, ArrowRight, X } from 'lucide-react';

export function SuperAdminWidget() {
  // Prototype simulation: Assume mb_level === 10
  const isSuperAdmin = true; 
  const [showBanner, setShowBanner] = useState(true);

  if (!isSuperAdmin) return null;

  return (
    <>
      {/* 1. Dashboard Top Banner (PC & Mobile) */}
      {showBanner && (
        <div className="bg-slate-900 border-b border-slate-800 text-slate-300 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 relative z-50">
          <div className="flex items-center gap-3">
            <div className="bg-cyan-500/20 p-2 rounded-lg">
              <ShieldCheck className="w-5 h-5 text-cyan-400" />
            </div>
            <div>
              <p className="text-sm font-bold text-white">최고관리자 모드로 접속 중입니다.</p>
              <p className="text-xs text-slate-400 mt-0.5">전체 파트너, 광고주, 디비, 정산, API를 관리할 수 있습니다.</p>
            </div>
          </div>
          <div className="flex items-center gap-4 w-full sm:w-auto">
            <Link to="/admin" className="flex-1 sm:flex-none text-center bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-1.5 shadow-sm">
              관리자센터 바로가기 <ArrowRight size={14} />
            </Link>
            <button onClick={() => setShowBanner(false)} className="text-slate-500 hover:text-white p-1">
              <X size={16} />
            </button>
          </div>
        </div>
      )}

      {/* 2. Floating Button (Mobile Only) */}
      <div className="fixed bottom-6 right-6 md:hidden z-50">
        <Link to="/admin" className="flex items-center justify-center w-14 h-14 bg-slate-900 text-cyan-400 rounded-full shadow-lg border border-slate-700 hover:bg-slate-800 transition-colors relative group">
          <ShieldAlert size={24} />
          <span className="absolute -top-10 bg-slate-900 text-white text-xs font-bold py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap border border-slate-700 shadow-sm pointer-events-none">
            관리자센터
          </span>
          <span className="absolute top-0 right-0 w-3 h-3 bg-cyan-500 border-2 border-slate-900 rounded-full"></span>
        </Link>
      </div>
    </>
  );
}

export function SuperAdminHeaderButton() {
  const isSuperAdmin = true;
  
  if (!isSuperAdmin) return null;

  return (
    <Link to="/admin" className="hidden md:flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-cyan-400 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm relative group">
      <ShieldCheck size={14} />
      관리자센터
      {/* Tooltip */}
      <div className="absolute top-full mt-2 right-0 w-48 bg-slate-900 border border-slate-800 text-slate-300 text-xs p-2.5 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
        최고관리자 (mb_level=10) 전용 메뉴입니다.
      </div>
    </Link>
  );
}
