import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function resolveLandingUrl(url: string): string {
  const trimmed = url.trim();
  if (!trimmed) {
    return '';
  }

  return /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
}

export function openLandingPage(url: string): void {
  const href = resolveLandingUrl(url);
  if (!href) {
    return;
  }

  window.open(href, '_blank', 'noopener,noreferrer');
}
