import React, { useState } from 'react';
import { saveAdminEntityMeta } from '../lib/api';

const tagPresets = ['주의', '어뷰징의심', 'VIP', '신규', '긴급'];

type Props = {
  entityType: 'partner' | 'merchant';
  entityId: number;
  adminMemo?: string;
  tags?: string[];
  assignedTo?: string;
  onSaved?: () => void;
};

export function AdminEntityMetaPanel({ entityType, entityId, adminMemo = '', tags = [], assignedTo = '', onSaved }: Props) {
  const [memo, setMemo] = useState(adminMemo);
  const [selectedTags, setSelectedTags] = useState<string[]>(tags);
  const [assignee, setAssignee] = useState(assignedTo);
  const [saving, setSaving] = useState(false);

  const toggleTag = (tag: string) => {
    setSelectedTags((prev) => (prev.includes(tag) ? prev.filter((t) => t !== tag) : [...prev, tag]));
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await saveAdminEntityMeta({
        entityType,
        entityId,
        adminMemo: memo,
        tags: selectedTags,
        assignedTo: assignee,
      });
      onSaved?.();
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-3">
      <div>
        <label className="block text-xs font-bold text-slate-500 mb-1">내부 메모</label>
        <textarea
          value={memo}
          onChange={(e) => setMemo(e.target.value)}
          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm min-h-[80px] outline-none focus:border-cyan-500"
          placeholder="관리자 전용 메모"
        />
      </div>
      <div>
        <label className="block text-xs font-bold text-slate-500 mb-1">위험/상태 태그</label>
        <div className="flex flex-wrap gap-1.5">
          {tagPresets.map((tag) => (
            <button
              key={tag}
              type="button"
              onClick={() => toggleTag(tag)}
              className={`px-2 py-1 rounded-lg text-xs font-bold border ${
                selectedTags.includes(tag) ? 'bg-red-50 text-red-700 border-red-200' : 'bg-slate-50 text-slate-600 border-slate-200'
              }`}
            >
              {tag}
            </button>
          ))}
        </div>
      </div>
      <div>
        <label className="block text-xs font-bold text-slate-500 mb-1">담당자 (회원 ID)</label>
        <input
          value={assignee}
          onChange={(e) => setAssignee(e.target.value)}
          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500"
          placeholder="admin"
        />
      </div>
      <button
        type="button"
        disabled={saving}
        onClick={handleSave}
        className="w-full py-2 bg-slate-900 text-white rounded-xl text-sm font-bold disabled:opacity-50"
      >
        {saving ? '저장 중...' : '메모/태그 저장'}
      </button>
    </div>
  );
}
