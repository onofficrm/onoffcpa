/**
 * LinkConnect 플러그인 UI
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var header = document.getElementById('lcHeader');
    var toggle = document.querySelector('[data-lc-nav-toggle]');
    var mobileNav = document.querySelector('[data-lc-mobile-nav]');
    if (!toggle || !mobileNav) {
      return;
    }

    var mobileLinks = mobileNav.querySelectorAll('[data-lc-mobile-link]');
    var body = document.body;

    function setNavOpen(open) {
      if (open) {
        mobileNav.removeAttribute('hidden');
        toggle.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', '메뉴 닫기');
        body.classList.add('lc-nav-open');
      } else {
        mobileNav.setAttribute('hidden', 'hidden');
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', '메뉴 열기');
        body.classList.remove('lc-nav-open');
      }
    }

    function isNavOpen() {
      return !mobileNav.hasAttribute('hidden');
    }

    toggle.addEventListener('click', function () {
      setNavOpen(!isNavOpen());
    });

    mobileLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        setNavOpen(false);
      });
    });

    mobileNav.querySelectorAll('a.lc-btn').forEach(function (link) {
      link.addEventListener('click', function () {
        setNavOpen(false);
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isNavOpen()) {
        setNavOpen(false);
        toggle.focus();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 960 && isNavOpen()) {
        setNavOpen(false);
      }
    });

    document.querySelectorAll('[data-lc-pills]').forEach(function (wrap) {
      wrap.querySelectorAll('[data-lc-pill]').forEach(function (pill) {
        pill.addEventListener('click', function () {
          wrap.querySelectorAll('[data-lc-pill]').forEach(function (p) {
            p.classList.remove('is-active');
          });
          pill.classList.add('is-active');
        });
      });
    });

    if (header) {
      document.addEventListener('click', function (event) {
        if (!isNavOpen()) {
          return;
        }
        if (!header.contains(event.target)) {
          setNavOpen(false);
        }
      });
    }

    var partnerShell = document.querySelector('.lc-shell--partner');
    var merchantShell = document.querySelector('.lc-shell--merchant');
    var adminShell = document.querySelector('.lc-shell--admin');
    var centerShell = partnerShell || merchantShell || adminShell;
    var sidebarToggle = document.querySelector('[data-lc-sidebar-toggle]');
    var sidebarOverlay = document.querySelector('[data-lc-sidebar-overlay]');
    var sidebarLinks = document.querySelectorAll('[data-lc-sidebar-link]');

    function setSidebarOpen(open) {
      if (!centerShell) {
        return;
      }
      if (open) {
        centerShell.classList.add('lc-sidebar-open');
        if (sidebarToggle) {
          sidebarToggle.setAttribute('aria-expanded', 'true');
          sidebarToggle.setAttribute('aria-label', '사이드바 닫기');
        }
        if (sidebarOverlay) {
          sidebarOverlay.removeAttribute('hidden');
        }
        document.body.classList.add('lc-nav-open');
      } else {
        centerShell.classList.remove('lc-sidebar-open');
        if (sidebarToggle) {
          sidebarToggle.setAttribute('aria-expanded', 'false');
          sidebarToggle.setAttribute('aria-label', '사이드바 열기');
        }
        if (sidebarOverlay) {
          sidebarOverlay.setAttribute('hidden', 'hidden');
        }
        document.body.classList.remove('lc-nav-open');
      }
    }

    if (sidebarToggle && centerShell) {
      sidebarToggle.addEventListener('click', function () {
        setSidebarOpen(!centerShell.classList.contains('lc-sidebar-open'));
      });
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', function () {
        setSidebarOpen(false);
      });
    }
    sidebarLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 960) {
          setSidebarOpen(false);
        }
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && centerShell && centerShell.classList.contains('lc-sidebar-open')) {
        setSidebarOpen(false);
        if (sidebarToggle) {
          sidebarToggle.focus();
        }
      }
      if (event.key === 'Escape') {
        document.querySelectorAll('.lc-modal:not([hidden])').forEach(function (modal) {
          modal.setAttribute('hidden', 'hidden');
          modal.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('lc-nav-open');
        });
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 960 && centerShell && centerShell.classList.contains('lc-sidebar-open')) {
        setSidebarOpen(false);
      }
    });

    document.querySelectorAll('[data-lc-modal-open]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var modal = document.getElementById('lcMerchantDbModal');
        if (!modal) {
          return;
        }
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lc-nav-open');
      });
    });
    document.querySelectorAll('[data-lc-modal-close]').forEach(function (el) {
      el.addEventListener('click', function () {
        var modal = el.closest('.lc-modal');
        if (!modal) {
          document.querySelectorAll('.lc-modal').forEach(function (m) {
            m.setAttribute('hidden', 'hidden');
            m.setAttribute('aria-hidden', 'true');
          });
        } else {
          modal.setAttribute('hidden', 'hidden');
          modal.setAttribute('aria-hidden', 'true');
        }
        if (!centerShell || !centerShell.classList.contains('lc-sidebar-open')) {
          if (!document.querySelector('.lc-nav-mobile:not([hidden])')) {
            document.body.classList.remove('lc-nav-open');
          }
        }
      });
    });

    document.querySelectorAll('[data-lc-copy-tabs]').forEach(function (wrap) {
      var buttons = wrap.querySelectorAll('[data-lc-copy-tab]');
      var panels = wrap.querySelectorAll('[data-lc-copy-panel]');
      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-lc-copy-tab');
          buttons.forEach(function (b) {
            b.classList.remove('is-active');
            b.setAttribute('aria-selected', 'false');
          });
          panels.forEach(function (panel) {
            var active = panel.getAttribute('data-lc-copy-panel') === id;
            panel.classList.toggle('is-active', active);
            if (active) {
              panel.removeAttribute('hidden');
            } else {
              panel.setAttribute('hidden', 'hidden');
            }
          });
          btn.classList.add('is-active');
          btn.setAttribute('aria-selected', 'true');
        });
      });
      wrap.querySelectorAll('[data-lc-copy-btn]').forEach(function (copyBtn) {
        copyBtn.addEventListener('click', function () {
          var card = copyBtn.closest('.lc-copy-tabs__card');
          if (!card) {
            return;
          }
          var textEl = card.querySelector('.lc-copy-tabs__card-text');
          if (!textEl || !navigator.clipboard) {
            return;
          }
          navigator.clipboard.writeText(textEl.textContent || '').then(function () {
            copyBtn.textContent = '복사됨';
            setTimeout(function () {
              copyBtn.textContent = '복사';
            }, 1500);
          });
        });
      });
    });
  });
})();
