import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Briefcase, Activity, PauseCircle, AlertCircle, Coins, Percent,
  Search, Filter, ChevronDown, Download, X, ExternalLink, ShieldAlert,
  Building2, Wallet, Users, Settings, Copy, Edit3, Plus, Database, Image, Upload
, Wand2, Loader2 } from 'lucide-react';

const campaignData = [
  { code: 'CP-10023', name: '개인회생 무료상담 이벤트', advertiser: '희망법무법인', category: '법률/세무', type: 'CPA', partnerPrice: 35000, advertiserPrice: 50000, margin: 15000, totalDb: 1450, rate: '72.4%', cancelRate: '15.2%', status: '운영중' },
  { code: 'CP-10045', name: '직장인 신용대출 한도조회', advertiser: '(주)성공대부', category: '금융/대출', type: 'CPA', partnerPrice: 20000, advertiserPrice: 35000, margin: 15000, totalDb: 3200, rate: '85.0%', cancelRate: '8.5%', status: '운영중' },
  { code: 'CP-10088', name: '제주도 렌터카 최저가 비교', advertiser: '스피드렌터카', category: '여행/숙박', type: 'CPA', partnerPrice: 15000, advertiserPrice: 20000, margin: 5000, totalDb: 890, rate: '60.0%', cancelRate: '31.4%', status: '광고비부족' },
  { code: 'CP-10092', name: '종합건강검진 할인 프로모션', advertiser: '라이프보험법인', category: '건강/의료', type: 'CPA', partnerPrice: 45000, advertiserPrice: 60000, margin: 15000, totalDb: 420, rate: '77.7%', cancelRate: '11.1%', status: '운영중' },
  { code: 'CP-10105', name: '공인중개사 100% 환급반', advertiser: '에듀스터디', category: '교육/자격증', type: 'CPA', partnerPrice: 25000, advertiserPrice: 35000, margin: 10000, totalDb: 2150, rate: '68.5%', cancelRate: '20.1%', status: '일시중지' },
  { code: 'CP-10112', name: '다이어트 보조제 1개월분 증정', advertiser: '헬스플러스', category: '건강/의료', type: 'CPA', partnerPrice: 18000, advertiserPrice: 25000, margin: 7000, totalDb: 0, rate: '-', cancelRate: '-', status: '검수중' },
  { code: 'CP-10011', name: '동남아 패키지 땡처리 특가', advertiser: '투어여행사', category: '여행/숙박', type: 'CPS', partnerPrice: 50000, advertiserPrice: 70000, margin: 20000, totalDb: 85, rate: '95.0%', cancelRate: '5.0%', status: '종료' },
];

export function AdminCampaigns() {
  const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
  const [isEditMode, setIsEditMode] = useState(false);

  const handleRowClick = (campaign: any) => {
    setSelectedCampaign(campaign);
    setIsEditMode(false);
  };

  
  const fileInputRef = React.useRef<HTMLInputElement>(null);
  
  
  const [isGenerating, setIsGenerating] = useState(false);
  const handleAIGenerate = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setSelectedCampaign({ ...selectedCampaign, thumbnail: 'https://images.unsplash.com/photo-1557682250-33bd709cbe85?auto=format&fit=crop&q=80' });
      setIsGenerating(false);
    }, 2000);
  };

  const handleThumbnailClick = () => {
    if (isEditMode) {
      fileInputRef.current?.click();
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const url = URL.createObjectURL(file);
      setSelectedCampaign({ ...selectedCampaign, thumbnail: url });
    }
  };

  const handleCreateNew = () => {
    setSelectedCampaign({
      code: '신규등록',
      name: '',
      advertiser: '',
      category: '',
      type: 'CPA',
      partnerPrice: 0,
      advertiserPrice: 0,
      margin: 0,
      totalDb: 0,
      rate: '-',
      cancelRate: '-',
      status: '검수중'
    });
    setIsEditMode(true);
  };

  return (
    <AdminLayout activeMenu="campaigns" title="광고상품 관리" description="CPA 광고상품의 단가, 운영상태, 승인/취소 기준을 관리하세요.">
      
      <div className="flex justify-end mb-6">
        <button 
          onClick={handleCreateNew}
          className="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-colors shadow-sm flex items-center gap-2"
        >
          <Plus size={18} /> 새 광고상품 등록
        </button>
      </div>

      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 광고상품" value="128" suffix="개" icon={<Briefcase size={18} />} />
        <SummaryCard title="운영중 상품" value="94" suffix="개" color="emerald" highlight icon={<Activity size={18} />} />
        <SummaryCard title="일시중지 상품" value="18" suffix="개" icon={<PauseCircle size={18} />} />
        <SummaryCard title="광고비 부족 상품" value="6" suffix="개" color="red" highlight icon={<AlertCircle size={18} />} />
        <SummaryCard title="평균 파트너 단가" value="32,000" suffix="원" dark icon={<Coins size={18} />} />
        <SummaryCard title="평균 승인율" value="71.2" suffix="%" color="cyan" highlight icon={<Percent size={18} />} />
      </div>

      <div className="grid lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <div className={`flex flex-col ${selectedCampaign ? 'lg:col-span-2' : 'lg:col-span-3 xl:col-span-4'}`}>
          {/* Filter Area */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="광고상품명, 광고주명 검색" 
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 상태</option>
                  <option>운영중</option>
                  <option>일시중지</option>
                  <option>종료</option>
                  <option>광고비 부족</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>카테고리 전체</option>
                  <option>금융/대출</option>
                  <option>법률/세무</option>
                  <option>건강/의료</option>
                  <option>교육/자격증</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>신규 등록순</option>
                  <option>단가 높은순</option>
                  <option>승인율 높은순</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
              <div className="text-sm font-medium text-slate-600">총 <strong className="text-cyan-600">128</strong>개의 상품</div>
              <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                <Download size={14} /> 엑셀 다운로드
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-white border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">상품명/코드</th>
                    <th className="px-4 py-3 font-medium">광고주/유형</th>
                    <th className="px-4 py-3 font-medium text-right">파트너 단가</th>
                    <th className="px-4 py-3 font-medium text-right">광고주 단가/마진</th>
                    <th className="px-4 py-3 font-medium text-right">접수 DB</th>
                    <th className="px-4 py-3 font-medium text-right">승인율/취소율</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {campaignData.map((campaign, i) => (
                    <tr 
                      key={i} 
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedCampaign?.code === campaign.code ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => handleRowClick(campaign)}
                    >
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{campaign.name}</div>
                        <div className="text-xs text-slate-500 flex items-center gap-1.5">
                          <span>{campaign.code}</span>
                          <span className="w-0.5 h-0.5 bg-slate-300 rounded-full"></span>
                          <span>{campaign.category}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-medium text-slate-700 mb-0.5">{campaign.advertiser}</div>
                        <div className="text-xs font-bold text-cyan-600 bg-cyan-50 px-1.5 py-0.5 rounded inline-block">
                          {campaign.type}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-bold text-slate-900 text-base">{campaign.partnerPrice.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="text-sm font-medium text-slate-600">{campaign.advertiserPrice.toLocaleString()}원</div>
                        <div className="text-xs font-bold text-emerald-600 mt-0.5">+{campaign.margin.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-slate-700">
                        {campaign.totalDb.toLocaleString()}건
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-bold text-slate-900">{campaign.rate}</div>
                        <div className="text-xs text-red-500 mt-0.5">{campaign.cancelRate}</div>
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={campaign.status} />
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">관리</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar / Detail Panel */}
        {selectedCampaign && (
          <div className="lg:col-span-1 xl:col-span-2 flex flex-col h-[calc(100vh-180px)] sticky top-[100px]">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <div className="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center sticky top-0 z-20">
                <div className="flex items-center gap-3">
                  <h3 className="font-bold text-slate-900 flex items-center gap-2">
                    {isEditMode ? '광고상품 등록/수정' : '광고상품 상세'}
                  </h3>
                  {!isEditMode && <StatusBadge status={selectedCampaign.status} />}
                </div>
                <div className="flex items-center gap-2">
                  {!isEditMode && (
                    <button 
                      onClick={() => setIsEditMode(true)}
                      className="p-1.5 text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors"
                    >
                      <Edit3 size={18} />
                    </button>
                  )}
                  <button 
                    onClick={() => setSelectedCampaign(null)}
                    className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                  >
                    <X size={18} />
                  </button>
                </div>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-8 bg-white hide-scrollbar">
                
                {/* Basic Info */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">기본 정보</h4>
                  <div className="grid grid-cols-2 gap-4">
                    
                    <div className="col-span-2">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">썸네일 이미지</label>
                      <div className="flex items-start gap-4">
                        <div className="w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 relative group" onClick={handleThumbnailClick}>
                          <input type="file" ref={fileInputRef} className="hidden" accept="image/*" onChange={handleFileChange} />
                          {selectedCampaign.thumbnail ? (
                            <img src={selectedCampaign.thumbnail} alt="Thumbnail" className="w-full h-full object-cover" />
                          ) : (
                            <Image className="w-8 h-8 text-slate-300" />
                          )}
                          {isEditMode && (
                            <div className="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                              <Upload className="w-5 h-5 text-white" />
                            </div>
                          )}
                        </div>
                        <div className="flex-1">
                          {isEditMode ? (
                            <div className="flex flex-col gap-2 h-full justify-center">
                              <div className="border-2 border-dashed border-slate-200 rounded-xl p-2 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-cyan-300 transition-colors cursor-pointer flex-1" onClick={handleThumbnailClick}>
                                <Upload className="w-5 h-5 text-cyan-500 mb-1" />
                                <span className="text-xs font-medium text-slate-600">업로드</span>
                              </div>
                              <button onClick={handleAIGenerate} disabled={isGenerating} className="flex-1 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-xl flex items-center justify-center gap-1.5 text-xs font-bold transition-colors shadow-sm disabled:opacity-50">
                                {isGenerating ? <Loader2 size={14} className="animate-spin" /> : <Wand2 size={14} />} AI 생성
                              </button>
                            </div>
                          ) : (

                            <div className="h-24 flex items-center text-sm text-slate-500 bg-slate-50 rounded-xl px-4 border border-slate-100">
                              등록된 썸네일 이미지가 없습니다.
                            </div>
                          )}
                        </div>
                      </div>
                    </div>

                    <div className="col-span-2">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">광고상품명</label>
                      <input 
                        type="text" 
                        defaultValue={selectedCampaign.name}
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                        placeholder="광고상품명을 입력하세요"
                      />
                    </div>
                    <div className="col-span-1">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">광고주</label>
                      <select 
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                      >
                        <option value="">{selectedCampaign.advertiser || '광고주 선택'}</option>
                        <option>희망법무법인</option>
                        <option>(주)성공대부</option>
                      </select>
                    </div>
                    <div className="col-span-1">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">광고 유형</label>
                      <select 
                        disabled={!isEditMode}
                        defaultValue={selectedCampaign.type}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                      >
                        <option value="CPA">CPA</option>
                        <option value="CPS">CPS</option>
                      </select>
                    </div>
                    <div className="col-span-1">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">카테고리</label>
                      <select 
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                      >
                        <option value="">{selectedCampaign.category || '카테고리 선택'}</option>
                      </select>
                    </div>
                    <div className="col-span-1">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">운영 상태</label>
                      <select 
                        disabled={!isEditMode}
                        defaultValue={selectedCampaign.status}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                      >
                        <option value="검수중">검수중</option>
                        <option value="운영중">운영중</option>
                        <option value="일시중지">일시중지</option>
                        <option value="종료">종료</option>
                      </select>
                    </div>
                    <div className="col-span-2">
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">랜딩 URL</label>
                      <input 
                        type="url" 
                        defaultValue="https://example.com/landing"
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                        placeholder="https://"
                      />
                    </div>
                  </div>
                </section>

                {/* Pricing / Margin */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                    단가 및 마진 설정
                    {!isEditMode && <span className="text-xs font-normal text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">관리자 마진: {selectedCampaign.margin?.toLocaleString()}원</span>}
                  </h4>
                  
                  <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-medium text-slate-600 mb-1.5">광고주 차감 단가</label>
                        <div className="relative">
                          <input 
                            type="number" 
                            defaultValue={selectedCampaign.advertiserPrice}
                            disabled={!isEditMode}
                            className={`w-full pl-3 pr-8 py-2.5 border rounded-xl text-sm font-bold text-slate-900 ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-100 border-slate-200'}`}
                          />
                          <span className="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">원</span>
                        </div>
                        <p className="text-[11px] text-slate-400 mt-1">광고비 잔액에서 차감될 금액</p>
                      </div>
                      
                      <div>
                        <label className="block text-xs font-medium text-slate-600 mb-1.5">파트너 지급 단가</label>
                        <div className="relative">
                          <input 
                            type="number" 
                            defaultValue={selectedCampaign.partnerPrice}
                            disabled={!isEditMode}
                            className={`w-full pl-3 pr-8 py-2.5 border rounded-xl text-sm font-bold text-slate-900 ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-100 border-slate-200'}`}
                          />
                          <span className="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">원</span>
                        </div>
                        <p className="text-[11px] text-slate-400 mt-1">파트너에게 실제 지급될 금액</p>
                      </div>
                    </div>
                    
                    {isEditMode && (
                      <div className="pt-3 border-t border-slate-200 flex justify-between items-center bg-slate-900 -mx-4 -mb-4 p-4 rounded-b-xl text-white">
                        <span className="text-sm font-medium text-slate-400">자동 계산 마진 (건당)</span>
                        <div className="text-xl font-bold text-emerald-400 flex items-center gap-1">
                          + {(selectedCampaign.advertiserPrice - selectedCampaign.partnerPrice).toLocaleString()}
                          <span className="text-sm text-emerald-500/70 font-medium">원</span>
                        </div>
                      </div>
                    )}
                  </div>
                </section>

                {/* Criteria */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">기준 설정</h4>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">승인 기준 (파트너 공개)</label>
                      <textarea 
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm resize-none h-20 ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                        defaultValue="본인 통화 완료 후 정상 접수건에 한해 승인됩니다."
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">취소 기준 (파트너 공개)</label>
                      <textarea 
                        disabled={!isEditMode}
                        className={`w-full px-3 py-2 border rounded-xl text-sm resize-none h-20 ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                        defaultValue="- 3회 이상 부재중, 결번, 장난전화\n- 중복 접수, 타인 명의 도용\n- 미성년자, 해외 거주자"
                      />
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-medium text-slate-500 mb-1.5">허용 채널</label>
                        <input 
                          type="text" 
                          disabled={!isEditMode}
                          className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                          defaultValue="네이버 블로그, 카페, 지식인, 티스토리"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-slate-500 mb-1.5">금지 채널</label>
                        <input 
                          type="text" 
                          disabled={!isEditMode}
                          className={`w-full px-3 py-2 border rounded-xl text-sm ${isEditMode ? 'bg-white border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500' : 'bg-slate-50 border-slate-200 text-slate-700'}`}
                          defaultValue="당근마켓, 중고나라, 카카오톡 오픈채팅"
                        />
                      </div>
                    </div>
                  </div>
                </section>

                <div className="bg-amber-50 border border-amber-200 p-4 rounded-xl">
                  <h5 className="text-amber-900 font-bold text-sm mb-2 flex items-center gap-1.5">
                    <AlertCircle size={16} /> 광고상품 운영 주의사항
                  </h5>
                  <ul className="text-amber-800 text-xs space-y-1.5 list-disc pl-4">
                    <li>단가 변경은 파트너 수익에 직접적인 영향을 주므로 신중하게 진행해주세요.</li>
                    <li>운영중인 상품을 일시중지/종료 시 파트너 홍보 링크 접속이 즉시 차단됩니다.</li>
                    <li>승인 및 취소 기준은 파트너센터에 공개되므로 명확하게 작성해주세요.</li>
                  </ul>
                </div>

              </div>
              
              {/* Actions Footer */}
              <div className="p-4 border-t border-slate-200 bg-white grid grid-cols-2 gap-2 z-20">
                {isEditMode ? (
                  <>
                    <button 
                      onClick={() => {
                        if (selectedCampaign?.code === '신규등록') setSelectedCampaign(null);
                        else setIsEditMode(false);
                      }}
                      className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors shadow-sm"
                    >
                      취소
                    </button>
                    <button 
                      className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm"
                    >
                      저장하기
                    </button>
                  </>
                ) : (
                  <>
                    <button className="col-span-2 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                      <Copy size={14} /> 상품 복사하기
                    </button>
                    <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                      <Database size={14} /> 접수 디비보기
                    </button>
                    {selectedCampaign.status === '운영중' ? (
                      <button className="py-2.5 bg-orange-100 text-orange-700 rounded-xl text-sm font-bold hover:bg-orange-200 transition-colors shadow-sm flex justify-center items-center gap-1.5">
                        <PauseCircle size={14} /> 일시중지
                      </button>
                    ) : (
                      <button className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm flex justify-center items-center gap-1.5">
                        <Activity size={14} /> 운영 시작
                      </button>
                    )}
                  </>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
