import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { 
  Settings, Save, RotateCcw, History, AlertCircle, Check, HelpCircle
} from 'lucide-react';

export function AdminSettings() {
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved'>('idle');

  const handleSave = () => {
    setSaveStatus('saving');
    setTimeout(() => {
      setSaveStatus('saved');
      setTimeout(() => setSaveStatus('idle'), 3000);
    }, 800);
  };

  return (
    <AdminLayout activeMenu="settings" title="환경설정" description="링크커넥트의 운영 정책과 시스템 기본값을 설정하세요.">
      
      <div className="max-w-4xl mx-auto space-y-6">
        
        {/* Section 1. 기본 운영 설정 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
            <Settings className="w-5 h-5 text-slate-500" />
            <h3 className="font-bold text-slate-900">기본 운영 설정</h3>
          </div>
          <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">서비스명</label>
              <input type="text" defaultValue="링크커넥트" className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">플랫폼 운영 상태</label>
              <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                <option>정상 운영</option>
                <option>점검 중 (접속 차단)</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">관리자 이메일</label>
              <input type="email" defaultValue="admin@linkconnect.com" className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">고객센터 연락처</label>
              <input type="text" defaultValue="1588-0000" className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">기본 통화</label>
              <select disabled className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed">
                <option>KRW (대한민국 원)</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">시간대 설정</label>
              <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                <option>Asia/Seoul (KST)</option>
              </select>
            </div>
          </div>
        </section>

        {/* Section 2. CPA 디비 정책 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <h3 className="font-bold text-slate-900">CPA 디비 정책</h3>
          </div>
          <div className="p-6 space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1.5">중복 디비 판단 기간</label>
                <div className="relative">
                  <input type="number" defaultValue="30" className="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
                  <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">일</span>
                </div>
                <p className="text-xs text-slate-500 mt-1.5 flex items-center gap-1"><AlertCircle size={12}/> 기간 내 동일 정보 인입 시 중복 처리됩니다.</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1.5">디비 접수 시 기본 상태</label>
                <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                  <option>신규접수</option>
                  <option>승인대기</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2.5">중복 기준 (다중 선택 가능)</label>
              <div className="flex flex-wrap gap-4">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" defaultChecked className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <span className="text-sm text-slate-700">같은 전화번호</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" defaultChecked className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <span className="text-sm text-slate-700">같은 광고상품</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" className="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <span className="text-sm text-slate-700">같은 광고주</span>
                </label>
              </div>
            </div>
            
            <div className="pt-4 border-t border-slate-100">
              <label className="block text-sm font-medium text-slate-700 mb-1.5">광고주 처리 제한일</label>
              <div className="relative max-w-[50%]">
                <input type="number" defaultValue="7" className="w-full pl-4 pr-16 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">일 이내</span>
              </div>
              <p className="text-xs text-slate-500 mt-1.5">제한일 경과 시 자동 승인완료 처리됩니다.</p>
            </div>
          </div>
        </section>

        {/* Section 3. 광고비 정책 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h3 className="font-bold text-slate-900">광고비 정책</h3>
          </div>
          <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">광고비 차감 방식</label>
              <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                <option>디비 접수 시 가차감</option>
                <option>승인완료 시 차감</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">광고비 부족 시 처리</label>
              <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                <option>디비 보류 (대기상태)</option>
                <option>캠페인 일시중지</option>
                <option>관리자 알림만 발송</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">최소 충전 금액</label>
              <div className="relative">
                <input type="number" defaultValue="500000" className="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">원</span>
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">충전 승인 방식</label>
              <select className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow">
                <option>자동 승인 (가상계좌 연동)</option>
                <option>관리자 수동 승인</option>
              </select>
            </div>
          </div>
        </section>

        {/* Section 4. 파트너 수익 정책 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h3 className="font-bold text-slate-900">파트너 수익 정책</h3>
          </div>
          <div className="p-6 space-y-5">
            
            <div className="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
              <div>
                <div className="text-sm font-bold text-slate-800">디비 접수 시 예상수익 반영 여부</div>
                <div className="text-xs text-slate-500 mt-0.5">접수 단계에서 파트너에게 예상 수익으로 보여줍니다.</div>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </label>
            </div>

            <div className="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
              <div>
                <div className="text-sm font-bold text-slate-800">승인완료 시 확정수익 반영</div>
                <div className="text-xs text-slate-500 mt-0.5">광고주가 승인하면 파트너의 확정 수익으로 전환됩니다.</div>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </label>
            </div>

            <div className="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
              <div>
                <div className="text-sm font-bold text-slate-800">취소 시 수익 제외</div>
                <div className="text-xs text-slate-500 mt-0.5">취소/무효 처리된 디비는 수익에서 제외합니다.</div>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </label>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1.5">최소 정산 가능 금액</label>
                <div className="relative">
                  <input type="number" defaultValue="50000" className="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
                  <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">원</span>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1.5">정산 신청 가능일</label>
                <input type="text" defaultValue="매월 1일 ~ 5일" className="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
              </div>
            </div>

          </div>
        </section>

        {/* Section 5. 취소/무효 정책 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h3 className="font-bold text-slate-900">취소/무효 정책</h3>
          </div>
          <div className="p-6 space-y-4">
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">광고주 즉시 취소 허용 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">관리자 검수 필수 여부 (추천)</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">취소 사유 필수 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>

            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">취소 코멘트 필수 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">파트너 이의신청 허용 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
          </div>
        </section>

        {/* Section 6. API 보안 설정 */}
        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-slate-100 bg-slate-50">
            <h3 className="font-bold text-slate-900">API 보안 설정</h3>
          </div>
          <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">API Key 사용 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">API Secret 사용 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">IP 제한 사용 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">API 로그 저장 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>

            <label className="flex items-center justify-between py-1 cursor-pointer">
              <span className="text-sm font-medium text-slate-700">개인정보 마스킹 저장 여부</span>
              <div className="relative inline-flex items-center">
                <input type="checkbox" defaultChecked className="sr-only peer" />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
              </div>
            </label>
            
            <div className="flex flex-col justify-center">
              <div className="flex items-center justify-between">
                <label className="text-sm font-medium text-slate-700">실패 API 재시도 횟수</label>
                <div className="relative w-24">
                  <input type="number" defaultValue="3" className="w-full px-3 py-1.5 bg-white border border-slate-300 rounded text-sm text-right focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-shadow" />
                </div>
              </div>
            </div>

          </div>
        </section>

        {/* Section 7. CPS 확장 준비 */}
        <section className="bg-slate-50 rounded-2xl border border-slate-200 shadow-sm overflow-hidden opacity-70 grayscale">
          <div className="p-5 border-b border-slate-200 bg-slate-100 flex items-center justify-between">
            <h3 className="font-bold text-slate-700 flex items-center gap-2">
              CPS 확장 준비
              <span className="px-2 py-0.5 bg-slate-200 text-slate-600 text-[10px] rounded-full">Coming Soon</span>
            </h3>
            <HelpCircle size={16} className="text-slate-400" />
          </div>
          <div className="p-6">
            <p className="text-sm text-slate-600 mb-6 font-medium bg-white p-3 rounded-xl border border-slate-200 inline-block">
              CPS 기능은 추후 확장 예정입니다. 현재는 CPA 기능만 사용합니다.
            </p>
            
            <div className="space-y-4 max-w-lg">
              <label className="flex items-center justify-between py-1 cursor-not-allowed">
                <span className="text-sm font-medium text-slate-500">CPS 기능 활성화</span>
                <div className="relative inline-flex items-center opacity-50">
                  <input type="checkbox" disabled className="sr-only peer" />
                  <div className="w-11 h-6 bg-slate-300 rounded-full peer"></div>
                </div>
              </label>
              <label className="flex items-center justify-between py-1 cursor-not-allowed">
                <span className="text-sm font-medium text-slate-500">주문 전환 사용</span>
                <div className="relative inline-flex items-center opacity-50">
                  <input type="checkbox" disabled className="sr-only peer" />
                  <div className="w-11 h-6 bg-slate-300 rounded-full peer"></div>
                </div>
              </label>
              <label className="flex items-center justify-between py-1 cursor-not-allowed">
                <span className="text-sm font-medium text-slate-500">구매확정 상태 사용</span>
                <div className="relative inline-flex items-center opacity-50">
                  <input type="checkbox" disabled className="sr-only peer" />
                  <div className="w-11 h-6 bg-slate-300 rounded-full peer"></div>
                </div>
              </label>
              <label className="flex items-center justify-between py-1 cursor-not-allowed">
                <span className="text-sm font-medium text-slate-500">환불 상태 사용</span>
                <div className="relative inline-flex items-center opacity-50">
                  <input type="checkbox" disabled className="sr-only peer" />
                  <div className="w-11 h-6 bg-slate-300 rounded-full peer"></div>
                </div>
              </label>
              <label className="flex items-center justify-between py-1 cursor-not-allowed">
                <span className="text-sm font-medium text-slate-500">수수료율 정산 사용</span>
                <div className="relative inline-flex items-center opacity-50">
                  <input type="checkbox" disabled className="sr-only peer" />
                  <div className="w-11 h-6 bg-slate-300 rounded-full peer"></div>
                </div>
              </label>
            </div>
          </div>
        </section>
        
        {/* Actions */}
        <div className="flex items-center justify-between pt-6 border-t border-slate-200 pb-12">
          <div className="flex items-center gap-3">
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
              <RotateCcw size={16} /> 기본값 복원
            </button>
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
              <History size={16} /> 변경내역 보기
            </button>
          </div>
          
          <button 
            onClick={handleSave}
            disabled={saveStatus !== 'idle'}
            className={`px-8 py-3 rounded-xl text-sm font-bold shadow-sm flex items-center justify-center gap-2 transition-all ${
              saveStatus === 'idle' ? 'bg-slate-900 text-white hover:bg-slate-800' :
              saveStatus === 'saving' ? 'bg-cyan-500 text-white cursor-wait' :
              'bg-emerald-500 text-white'
            }`}
          >
            {saveStatus === 'idle' && <><Save size={18} /> 설정 저장</>}
            {saveStatus === 'saving' && <><Save size={18} className="animate-pulse" /> 저장 중...</>}
            {saveStatus === 'saved' && <><Check size={18} /> 저장 완료</>}
          </button>
        </div>

      </div>
    </AdminLayout>
  );
}
