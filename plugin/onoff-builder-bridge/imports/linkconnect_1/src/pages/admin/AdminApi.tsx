import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Code, ShieldAlert, ArrowDownToLine, Clock,
  Key, RefreshCcw, PowerOff, FileText, CheckCircle2,
  Database, Eye, Search, Calendar, ChevronDown,
  X, Shield, Check, AlertTriangle, AlertCircle
} from 'lucide-react';

const apiLogsData = [
  { id: 'LOG-001', time: '2026.07.06 15:42:10', client: '디비쉐어', direction: '수신', endpoint: '/api/v1/db/receive', extId: 'dbshare_88291', intId: 'DB-260706-102', statusCode: 200, status: '성공', error: '-', requestBody: '{\n  "name": "김*",\n  "phone": "010-1234-****",\n  "campaign_code": "CP-10023",\n  "question": "대출 한도 문의"\n}', responseBody: '{\n  "success": true,\n  "db_code": "DB-260706-102"\n}' },
  { id: 'LOG-002', time: '2026.07.06 15:41:05', client: '랜딩페이지A', direction: '수신', endpoint: '/api/v1/db/receive', extId: 'ld_9921', intId: '-', statusCode: 409, status: '중복', error: 'Duplicate entry for phone number', requestBody: '{\n  "name": "이*",\n  "phone": "010-5555-****",\n  "campaign_code": "CP-10045"\n}', responseBody: '{\n  "success": false,\n  "error": "Duplicate entry",\n  "code": 409\n}' },
  { id: 'LOG-003', time: '2026.07.06 15:30:22', client: '디비쉐어', direction: '상태동기화', endpoint: '/api/v1/db/status', extId: 'dbshare_88200', intId: 'DB-260706-088', statusCode: 200, status: '성공', error: '-', requestBody: '{\n  "db_code": "DB-260706-088",\n  "status": "승인완료"\n}', responseBody: '{\n  "success": true\n}' },
  { id: 'LOG-004', time: '2026.07.06 15:15:00', client: '랜딩페이지B', direction: '수신', endpoint: '/api/v1/db/receive', extId: 'ld_8833', intId: '-', statusCode: 401, status: '인증오류', error: 'Invalid API Key', requestBody: '{\n  "name": "박*",\n  "phone": "010-2222-****",\n  "campaign_code": "CP-10088"\n}', responseBody: '{\n  "success": false,\n  "error": "Invalid API Key"\n}' },
  { id: 'LOG-005', time: '2026.07.06 14:50:11', client: '디비쉐어', direction: '수신', endpoint: '/api/v1/db/receive', extId: 'dbshare_88190', intId: '-', statusCode: 400, status: '검증오류', error: 'Missing required field: phone', requestBody: '{\n  "name": "최*",\n  "campaign_code": "CP-10023"\n}', responseBody: '{\n  "success": false,\n  "error": "Missing required field: phone"\n}' },
  { id: 'LOG-006', time: '2026.07.06 14:45:00', client: '랜딩페이지A', direction: '수신', endpoint: '/api/v1/db/receive', extId: 'ld_9920', intId: '-', statusCode: 500, status: '실패', error: 'Internal Server Error', requestBody: '{\n  "name": "정*",\n  "phone": "010-3333-****",\n  "campaign_code": "CP-10045"\n}', responseBody: '{\n  "success": false,\n  "error": "Internal Server Error"\n}' },
];

export function AdminApi() {
  const [selectedLog, setSelectedLog] = useState<string | null>(null);

  const activeLog = apiLogsData.find(log => log.id === selectedLog);

  return (
    <AdminLayout activeMenu="api" title="API 연동 관리" description="외부 디비 수집 솔루션과의 API 연동 상태와 로그를 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="오늘 API 수신" value="248" suffix="건" dark icon={<ArrowDownToLine size={18} />} />
        <SummaryCard title="성공" value="240" suffix="건" color="emerald" highlight icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="실패" value="8" suffix="건" color="red" highlight icon={<ShieldAlert size={18} />} />
        <SummaryCard title="중복 차단" value="12" suffix="건" color="orange" icon={<AlertCircle size={18} />} />
        <SummaryCard title="디비쉐어 수신" value="186" suffix="건" color="blue" icon={<Database size={18} />} />
        <SummaryCard title="마지막 수신 시간" value="15:42" suffix="" color="slate" icon={<Clock size={18} />} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        {/* API Client Card */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
              <Code className="text-slate-600" size={20} />
            </div>
            <div>
              <h3 className="font-bold text-slate-900">API 클라이언트 (랜딩페이지)</h3>
              <p className="text-sm text-slate-500">자체 랜딩페이지 연동용 API Key 관리</p>
            </div>
          </div>

          <div className="space-y-4 flex-1">
            <div className="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
              <div>
                <div className="text-xs font-medium text-slate-500 mb-1">API Key</div>
                <div className="font-mono font-bold text-slate-800 tracking-tight">sk_live_a1b2c3d4e5f6g7h8i9j0</div>
              </div>
              <StatusBadge status="정상" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="p-4 bg-white border border-slate-200 rounded-xl">
                <div className="text-xs font-medium text-slate-500 mb-1">허용 IP</div>
                <div className="text-sm font-bold text-slate-900">192.168.1.1, 10.0.0.*</div>
              </div>
              <div className="p-4 bg-white border border-slate-200 rounded-xl">
                <div className="text-xs font-medium text-slate-500 mb-1">최근 호출일</div>
                <div className="text-sm font-bold text-slate-900">2026.07.06 15:41</div>
              </div>
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-2 pt-6 border-t border-slate-100">
            <button className="flex-1 py-2 bg-slate-900 text-white rounded-lg text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm flex items-center justify-center gap-1.5">
              <Key size={16} /> API Key 발급
            </button>
            <button className="flex-1 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
              <RefreshCcw size={16} /> Secret 재발급
            </button>
            <button className="px-3 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors flex items-center justify-center gap-1.5" title="비활성화">
              <PowerOff size={16} />
            </button>
            <button className="px-3 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5" title="로그보기">
              <FileText size={16} />
            </button>
          </div>
        </div>

        {/* DB Share Integration Card */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <Database className="text-blue-600" size={20} />
            </div>
            <div className="flex-1">
              <h3 className="font-bold text-slate-900">디비쉐어 연동 상태</h3>
              <p className="text-sm text-slate-500">DBShare 솔루션과의 실시간 동기화</p>
            </div>
            <StatusBadge status="정상" />
          </div>

          <div className="grid grid-cols-2 gap-4 flex-1">
            <div className="p-4 bg-slate-50 rounded-xl">
              <div className="text-xs font-medium text-slate-500 mb-1">마지막 수신 시간</div>
              <div className="text-base font-bold text-slate-900">2026.07.06 15:42</div>
            </div>
            <div className="p-4 bg-slate-50 rounded-xl">
              <div className="text-xs font-medium text-slate-500 mb-1">오늘 수신 DB</div>
              <div className="text-base font-bold text-slate-900 text-blue-600">186건</div>
            </div>
            <div className="p-4 bg-red-50 rounded-xl border border-red-100">
              <div className="text-xs font-medium text-red-600 mb-1">실패 건수 (오늘)</div>
              <div className="text-base font-bold text-red-700">2건</div>
            </div>
            <div className="p-4 bg-amber-50 rounded-xl border border-amber-100">
              <div className="text-xs font-medium text-amber-600 mb-1">재시도 대기 건수</div>
              <div className="text-base font-bold text-amber-700">1건</div>
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-2 pt-6 border-t border-slate-100">
            <button className="flex-1 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-1.5">
              <ArrowDownToLine size={16} /> 테스트 수신
            </button>
            <button className="flex-1 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
              <RefreshCcw size={16} /> 실패 재처리
            </button>
            <button className="px-3 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5" title="로그 확인">
              <FileText size={16} />
            </button>
          </div>
        </div>

      </div>

      {/* Logs Table Area */}
      <div className="flex flex-col lg:flex-row gap-6">
        
        {/* Main List */}
        <div className={`flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${selectedLog ? 'hidden lg:block lg:w-2/3' : 'w-full'}`}>
          <div className="p-6">
            
            <h3 className="font-bold text-slate-900 mb-4 flex items-center gap-2">
              <FileText size={18} className="text-slate-400" /> API 로그
            </h3>

            {/* Filters */}
            <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="Endpoint 또는 DB ID 검색" 
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-shadow"
                  />
                </div>
              </div>
              
              <div className="flex flex-wrap items-center gap-2">
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-blue-500">
                  <option>전체 연동</option>
                  <option>디비쉐어</option>
                  <option>랜딩페이지A</option>
                  <option>랜딩페이지B</option>
                </select>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-blue-500">
                  <option>전체 상태</option>
                  <option>성공</option>
                  <option>실패</option>
                  <option>중복</option>
                  <option>인증오류</option>
                  <option>검증오류</option>
                </select>
                <div className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>오늘</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">시간</th>
                    <th className="px-4 py-3 font-medium">연동명 / 방향</th>
                    <th className="px-4 py-3 font-medium">Endpoint</th>
                    <th className="px-4 py-3 font-medium">외부 DB ID / 내부코드</th>
                    <th className="px-4 py-3 font-medium text-center">결과</th>
                    <th className="px-4 py-3 font-medium">오류메시지</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {apiLogsData.map((log) => (
                    <tr 
                      key={log.id} 
                      className={`transition-colors cursor-pointer ${selectedLog === log.id ? 'bg-blue-50' : 'hover:bg-slate-50'}`}
                      onClick={() => setSelectedLog(log.id)}
                    >
                      <td className="px-4 py-4 whitespace-nowrap text-slate-600 font-medium">
                        {log.time.split(' ')[1]}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-1">{log.client}</div>
                        <StatusBadge status={log.direction} />
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <span className="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
                          {log.endpoint}
                        </span>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-xs text-slate-500 mb-0.5">Ext: {log.extId}</div>
                        <div className="text-xs font-bold text-slate-800">Int: {log.intId}</div>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={log.status} />
                        <div className="text-[10px] text-slate-400 mt-1">HTTP {log.statusCode}</div>
                      </td>
                      <td className="px-4 py-4">
                        <div className={`text-xs truncate max-w-[150px] ${log.status === '성공' ? 'text-slate-400' : 'text-red-600 font-medium'}`}>
                          {log.error}
                        </div>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors flex items-center justify-center gap-1 mx-auto">
                          <Eye size={14} /> 상세
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

          </div>
        </div>

        {/* Details Panel */}
        {activeLog && (
          <div className="lg:w-1/3 w-full flex flex-col gap-4">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-120px)] sticky top-6">
              
              <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 className="font-bold text-slate-900 flex items-center gap-2">
                  <Code size={18} className="text-slate-400" />
                  API 로그 상세
                </h3>
                <button 
                  onClick={() => setSelectedLog(null)}
                  className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="p-5 overflow-y-auto flex-1 space-y-6">
                
                {/* 처리 결과 */}
                <section>
                  <div className="flex items-center justify-between mb-3">
                    <h4 className="text-sm font-bold text-slate-900">처리 결과</h4>
                    <StatusBadge status={activeLog.status} />
                  </div>
                  <div className={`p-4 rounded-xl text-sm space-y-2 border ${activeLog.status === '성공' ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'}`}>
                    <div className="flex justify-between">
                      <span className="text-slate-500 font-medium">상태 코드</span>
                      <span className="font-bold text-slate-900">{activeLog.statusCode}</span>
                    </div>
                    {activeLog.status !== '성공' && (
                      <div className="pt-2 mt-2 border-t border-red-200/50 text-red-700 font-medium">
                        {activeLog.error}
                      </div>
                    )}
                  </div>
                </section>

                {/* Request */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <ArrowDownToLine size={16} className="text-blue-600" /> Request Body
                  </h4>
                  <div className="bg-slate-900 rounded-xl p-4 overflow-x-auto">
                    <pre className="text-xs font-mono text-slate-300">
                      {activeLog.requestBody}
                    </pre>
                  </div>
                </section>

                {/* Response */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <ArrowDownToLine size={16} className="text-emerald-600 rotate-180" /> Response Body
                  </h4>
                  <div className="bg-slate-900 rounded-xl p-4 overflow-x-auto">
                    <pre className="text-xs font-mono text-slate-300">
                      {activeLog.responseBody}
                    </pre>
                  </div>
                </section>
                
                {/* 관리자 액션 */}
                {activeLog.status !== '성공' && activeLog.status !== '중복' && (
                  <section className="pt-4 border-t border-slate-100">
                    <button className="w-full py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-1.5">
                      <RefreshCcw size={16} /> 이 요청 다시 실행하기
                    </button>
                  </section>
                )}
                
              </div>
            </div>
            
            {/* Notice Box */}
            <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-start gap-3">
              <Shield size={18} className="text-slate-500 mt-0.5 shrink-0" />
              <div className="text-xs text-slate-600 space-y-1">
                <p className="font-bold mb-1 text-slate-800">API 보안 안내</p>
                <p>• API Key와 Secret은 외부에 노출되지 않게 관리하세요.</p>
                <p>• 허용 IP를 설정하면 보안이 강화됩니다.</p>
                <p>• 개인정보가 포함된 API 로그는 자동 마스킹 처리됩니다.</p>
              </div>
            </div>
          </div>
        )}

      </div>
    </AdminLayout>
  );
}
