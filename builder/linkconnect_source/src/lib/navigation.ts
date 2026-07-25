const SCROLL_KEY = 'lc-scroll-target';

export function queueScrollTo(id: string) {
  try {
    sessionStorage.setItem(SCROLL_KEY, id);
  } catch {
    /* ignore */
  }
}

export function consumeScrollTarget(): string | null {
  try {
    const value = sessionStorage.getItem(SCROLL_KEY);
    if (value) {
      sessionStorage.removeItem(SCROLL_KEY);
      return value;
    }
  } catch {
    /* ignore */
  }
  return null;
}

export function scrollToSection(id: string, behavior: ScrollBehavior = 'smooth') {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior, block: 'start' });
  }
}

export function handleSectionLink(id: string) {
  queueScrollTo(id);
}
