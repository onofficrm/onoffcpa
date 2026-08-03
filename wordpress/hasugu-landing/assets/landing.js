(function () {
  'use strict';

  function scrollToForm() {
    var target = document.querySelector('.hsg-hero__card') || document.getElementById('hsg-form');
    if (!target) return;
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function fillMessage(text) {
    var areas = document.querySelectorAll('.hsg .lc-embed__textarea, .hsg textarea');
    if (!areas.length) return false;
    var el = areas[0];
    var prefix = '증상 선택: ';
    var current = (el.value || '').trim();
    if (current.indexOf(prefix) === 0) {
      el.value = prefix + text;
    } else if (current) {
      el.value = prefix + text + '\n' + current;
    } else {
      el.value = prefix + text;
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.focus();
    return true;
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-hsg-symptom]');
    if (!btn || !btn.closest('.hsg')) return;
    var label = btn.getAttribute('data-hsg-symptom') || '';
    document.querySelectorAll('.hsg-symptom.is-active').forEach(function (el) {
      el.classList.remove('is-active');
    });
    btn.classList.add('is-active');
    scrollToForm();
    // 임베드 폼이 비동기로 붙을 수 있어 짧게 재시도
    var tries = 0;
    (function retry() {
      if (fillMessage(label) || tries > 8) return;
      tries += 1;
      setTimeout(retry, 180);
    })();
  });
})();
