import { Menu, X } from "lucide-react";
import { useState } from "react";
import { cn } from "../lib/utils";

const NAV_LINKS = [
  { label: "링크커넥트", href: "#" },
  { label: "CPA", href: "#" },
  { label: "CPS", href: "#" },
  { label: "이벤트/프로모션", href: "#" },
  { label: "파트너센터", href: "#" },
  { label: "광고주센터", href: "#" },
];

export function Header() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-900">
            <span className="text-lg font-bold text-teal-400">L</span>
          </div>
          <span className="text-xl font-bold text-slate-900 tracking-tight">링크커넥트</span>
        </div>

        {/* Desktop Nav */}
        <nav className="hidden md:flex items-center gap-8">
          {NAV_LINKS.map((link) => (
            <a
              key={link.label}
              href={link.href}
              className="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
            >
              {link.label}
            </a>
          ))}
        </nav>

        {/* Desktop Actions */}
        <div className="hidden md:flex items-center gap-4">
          <button className="text-sm font-medium text-slate-600 hover:text-slate-900 px-4 py-2">
            광고주 문의하기
          </button>
          <button className="rounded-md bg-blue-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-800">
            파트너 가입하기
          </button>
        </div>

        {/* Mobile menu button */}
        <button
          className="md:hidden p-2 text-slate-600"
          onClick={() => setIsOpen(!isOpen)}
        >
          {isOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {/* Mobile Nav */}
      <div
        className={cn(
          "absolute inset-x-0 top-16 bg-white border-b border-slate-200 p-4 transition-all duration-200 ease-in-out md:hidden",
          isOpen ? "opacity-100 visible" : "opacity-0 invisible"
        )}
      >
        <nav className="flex flex-col space-y-4">
          {NAV_LINKS.map((link) => (
            <a
              key={link.label}
              href={link.href}
              className="text-base font-medium text-slate-600 px-2"
            >
              {link.label}
            </a>
          ))}
          <div className="flex flex-col gap-2 pt-4 border-t border-slate-100">
            <button className="w-full text-center px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-50 rounded-md">
              광고주 문의하기
            </button>
            <button className="w-full rounded-md bg-blue-900 px-4 py-2.5 text-sm font-medium text-white">
              파트너 가입하기
            </button>
          </div>
        </nav>
      </div>
    </header>
  );
}
