import { useEffect, useRef, useState } from 'react';
import { Bot, MessageCircle, Send, X, Sparkles } from 'lucide-react';
import { fetchAiStatus, sendAiChat } from '../lib/api';
import { getLcAuth } from '../lib/auth';

type ChatMessage = { role: 'user' | 'assistant'; text: string };

type AiGuideChatProps = {
  page?: string;
  role?: string;
};

export function AiGuideChat({ page = '', role = '' }: AiGuideChatProps) {
  const [open, setOpen] = useState(false);
  const [available, setAvailable] = useState(false);
  const [statusMsg, setStatusMsg] = useState('');
  const [input, setInput] = useState('');
  const [messages, setMessages] = useState<ChatMessage[]>([
    { role: 'assistant', text: '안녕하세요! 링크커넥트 AI 가이드입니다. CPA, 정산, 이벤트, 파트너/광고주 이용 방법을 물어보세요.' },
  ]);
  const [loading, setLoading] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);
  const auth = getLcAuth();

  useEffect(() => {
    fetchAiStatus()
      .then((data) => {
        setAvailable(data.available);
        setStatusMsg(data.message);
      })
      .catch(() => setAvailable(false));
  }, []);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, open]);

  const resolvedRole = role || (auth.isSuperAdmin || auth.canAccessAdmin ? 'admin' : auth.isActivePartner ? 'partner' : auth.isActiveMerchant ? 'merchant' : 'guest');

  const handleSend = async () => {
    const text = input.trim();
    if (!text || loading) return;

    setInput('');
    setMessages((prev) => [...prev, { role: 'user', text }]);
    setLoading(true);

    try {
      const history = messages.slice(-8).map((m) => ({ role: m.role, text: m.text }));
      const data = await sendAiChat({
        message: text,
        history,
        context: { page, role: resolvedRole },
      });
      setMessages((prev) => [...prev, { role: 'assistant', text: data.reply }]);
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        { role: 'assistant', text: err instanceof Error ? err.message : '답변을 생성하지 못했습니다.' },
      ]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="fixed bottom-6 right-6 z-40 flex items-center gap-2 px-4 py-3 rounded-full bg-slate-900 hover:bg-slate-800 text-white shadow-xl shadow-slate-900/30 border border-slate-700 transition-all hover:scale-105"
        aria-label="AI 가이드 열기"
      >
        <Sparkles size={18} className="text-cyan-400" />
        <span className="text-sm font-bold hidden sm:inline">AI 가이드</span>
      </button>

      {open && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <button type="button" className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setOpen(false)} aria-label="닫기" />
          <div className="relative w-full sm:max-w-md h-[85vh] sm:h-[560px] bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Bot size={20} className="text-cyan-400" />
                <div>
                  <div className="font-bold text-sm">링크커넥트 AI 가이드</div>
                  <div className="text-[11px] text-slate-300">{available ? 'Gemini 연동' : 'API 키 미설정'}</div>
                </div>
              </div>
              <button type="button" onClick={() => setOpen(false)} className="p-2 rounded-lg hover:bg-white/10">
                <X size={18} />
              </button>
            </div>

            {!available && statusMsg && (
              <div className="px-4 py-2 bg-amber-50 text-amber-800 text-xs border-b border-amber-100">{statusMsg}</div>
            )}

            <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50">
              {messages.map((msg, idx) => (
                <div key={idx} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                  <div
                    className={`max-w-[85%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap ${
                      msg.role === 'user'
                        ? 'bg-cyan-600 text-white rounded-br-md'
                        : 'bg-white border border-slate-200 text-slate-700 rounded-bl-md shadow-sm'
                    }`}
                  >
                    {msg.text}
                  </div>
                </div>
              ))}
              {loading && (
                <div className="text-xs text-slate-400 flex items-center gap-2">
                  <MessageCircle size={14} className="animate-pulse" /> 답변 생성 중…
                </div>
              )}
              <div ref={bottomRef} />
            </div>

            <div className="p-4 border-t border-slate-100 bg-white">
              <div className="flex gap-2">
                <input
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), handleSend())}
                  placeholder={available ? '질문을 입력하세요…' : 'AI 기능을 사용할 수 없습니다'}
                  disabled={!available || loading}
                  className="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 disabled:bg-slate-50"
                />
                <button
                  type="button"
                  onClick={handleSend}
                  disabled={!available || loading || !input.trim()}
                  className="px-4 rounded-xl bg-slate-900 text-white disabled:opacity-40 hover:bg-slate-800 transition-colors"
                >
                  <Send size={18} />
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
