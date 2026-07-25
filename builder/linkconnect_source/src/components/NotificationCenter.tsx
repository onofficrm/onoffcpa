import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Bell, Check, Loader2 } from 'lucide-react';
import {
  LcNotification,
  LcNotificationCenter,
  fetchAdminNotifications,
  fetchMerchantNotifications,
  fetchPartnerNotifications,
  markAdminNotificationsRead,
  markMerchantNotificationsRead,
  markPartnerNotificationsRead,
} from '../lib/api';

type NotificationCenterProps = {
  center: LcNotificationCenter;
  variant?: 'dark' | 'light';
};

const typeLabels: Record<string, string> = {
  conversion: '디비',
  wallet: '광고비',
  event: '이벤트',
  campaign: '캠페인',
  notice: '공지',
  system: '시스템',
};

function fetchByCenter(center: LcNotificationCenter) {
  if (center === 'admin') return fetchAdminNotifications();
  if (center === 'merchant') return fetchMerchantNotifications();
  return fetchPartnerNotifications();
}

function markReadByCenter(center: LcNotificationCenter, id?: number) {
  if (center === 'admin') return markAdminNotificationsRead(id);
  if (center === 'merchant') return markMerchantNotificationsRead(id);
  return markPartnerNotificationsRead(id);
}

export function NotificationCenter({ center, variant = 'light' }: NotificationCenterProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [items, setItems] = useState<LcNotification[]>([]);
  const [unread, setUnread] = useState(0);
  const rootRef = useRef<HTMLDivElement>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchByCenter(center);
      setItems(data.items);
      setUnread(data.unread);
    } catch {
      setItems([]);
      setUnread(0);
    } finally {
      setLoading(false);
    }
  }, [center]);

  useEffect(() => {
    load();
    const timer = window.setInterval(load, 60000);
    return () => window.clearInterval(timer);
  }, [load]);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  const handleMarkAllRead = async () => {
    await markReadByCenter(center);
    await load();
  };

  const handleItemClick = async (item: LcNotification) => {
    if (!item.read) {
      await markReadByCenter(center, item.id);
      await load();
    }
    setOpen(false);
  };

  const buttonClass =
    variant === 'dark'
      ? 'p-2 text-slate-400 hover:text-white relative transition-colors'
      : 'w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 relative shadow-sm';

  const panelClass =
    variant === 'dark'
      ? 'absolute right-0 top-full mt-2 w-80 sm:w-96 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-[100] overflow-hidden'
      : 'absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-xl shadow-xl z-[100] overflow-hidden';

  return (
    <div className="relative" ref={rootRef}>
      <button type="button" className={buttonClass} onClick={() => setOpen((v) => !v)} aria-label="알림">
        <Bell size={20} />
        {unread > 0 ? (
          <span className="absolute top-1.5 right-1.5 min-w-[8px] h-2 px-0.5 bg-rose-500 rounded-full border border-white text-[9px] text-white font-bold flex items-center justify-center">
            {unread > 9 ? '9+' : unread}
          </span>
        ) : null}
      </button>

      {open ? (
        <div className={panelClass}>
          <div className={`px-4 py-3 border-b flex items-center justify-between ${variant === 'dark' ? 'border-slate-700' : 'border-slate-100'}`}>
            <div className={`font-bold text-sm ${variant === 'dark' ? 'text-white' : 'text-slate-900'}`}>알림센터</div>
            <button
              type="button"
              onClick={handleMarkAllRead}
              className={`text-xs font-medium flex items-center gap-1 ${variant === 'dark' ? 'text-cyan-400 hover:text-cyan-300' : 'text-cyan-600 hover:text-cyan-700'}`}
            >
              <Check size={14} /> 모두 읽음
            </button>
          </div>

          <div className="max-h-96 overflow-y-auto">
            {loading ? (
              <div className="py-10 flex justify-center text-slate-400">
                <Loader2 size={20} className="animate-spin" />
              </div>
            ) : items.length === 0 ? (
              <div className={`py-10 text-center text-sm ${variant === 'dark' ? 'text-slate-500' : 'text-slate-400'}`}>
                새 알림이 없습니다.
              </div>
            ) : (
              items.map((item) => {
                const content = (
                  <div
                    className={`px-4 py-3 border-b last:border-b-0 transition-colors ${
                      variant === 'dark'
                        ? `border-slate-800 hover:bg-slate-800/60 ${item.read ? 'opacity-60' : ''}`
                        : `border-slate-50 hover:bg-slate-50 ${item.read ? 'opacity-70' : ''}`
                    }`}
                  >
                    <div className="flex items-start gap-2">
                      {!item.read ? <span className="w-2 h-2 mt-1.5 rounded-full bg-rose-500 shrink-0" /> : <span className="w-2 shrink-0" />}
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 mb-0.5">
                          <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${variant === 'dark' ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-600'}`}>
                            {typeLabels[item.type] ?? item.type}
                          </span>
                          <span className={`text-[10px] ${variant === 'dark' ? 'text-slate-500' : 'text-slate-400'}`}>{item.createdAt.slice(0, 16)}</span>
                        </div>
                        <div className={`text-sm font-bold truncate ${variant === 'dark' ? 'text-white' : 'text-slate-900'}`}>{item.title}</div>
                        <div className={`text-xs mt-0.5 line-clamp-2 ${variant === 'dark' ? 'text-slate-400' : 'text-slate-500'}`}>{item.body}</div>
                      </div>
                    </div>
                  </div>
                );

                if (item.link) {
                  return (
                    <Link key={item.id} to={item.link} onClick={() => handleItemClick(item)}>
                      {content}
                    </Link>
                  );
                }

                return (
                  <button key={item.id} type="button" className="w-full text-left" onClick={() => handleItemClick(item)}>
                    {content}
                  </button>
                );
              })
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
}
