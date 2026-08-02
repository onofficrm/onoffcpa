/**
 * LinkConnect 상담신청 임베드 위젯
 *
 * 사용 예:
 * <div id="lc-lead-form"></div>
 * <script src=".../lead-embed.js" data-lk-code="CODE" data-target="#lc-lead-form" async></script>
 */
(function () {
  'use strict';

  var STYLE_ID = 'lc-lead-embed-style';

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function attr(el, name, fallback) {
    if (!el) return fallback || '';
    var v = el.getAttribute(name);
    return v == null || v === '' ? (fallback || '') : v;
  }

  function currentScript() {
    return document.currentScript || (function () {
      var list = document.getElementsByTagName('script');
      for (var i = list.length - 1; i >= 0; i--) {
        if ((list[i].src || '').indexOf('lead-embed.js') !== -1) return list[i];
      }
      return null;
    })();
  }

  function scriptBase(script) {
    var src = (script && script.src) || '';
    try {
      var u = new URL(src, window.location.href);
      // .../assets/js/lead-embed.js → plugin root = ../../
      var path = u.pathname.replace(/\/assets\/js\/lead-embed\.js$/i, '');
      return u.origin + path;
    } catch (e) {
      return '';
    }
  }

  function readUrlParam(name) {
    try {
      var sp = new URL(window.location.href).searchParams.get(name);
      if (sp) return sp;
      var hash = window.location.hash || '';
      if (hash.indexOf('?') !== -1) {
        return new URLSearchParams(hash.split('?')[1] || '').get(name) || '';
      }
    } catch (e) {}
    return '';
  }

  function resolveLkCode(script, mount) {
    return (
      readUrlParam('lkCode') ||
      readUrlParam('lk_code') ||
      readUrlParam('code') ||
      attr(mount, 'data-lk-code') ||
      attr(script, 'data-lk-code') ||
      ''
    ).trim();
  }

  function ensureStyles(theme) {
    if (qs('#' + STYLE_ID)) return;
    var accent = (theme && theme.accent) || '#0d9488';
    var accentText = (theme && theme.accentText) || '#ffffff';
    var border = (theme && theme.border) || '#e2e8f0';
    var bg = (theme && theme.bg) || '#ffffff';
    var text = (theme && theme.text) || '#0f172a';
    var muted = (theme && theme.muted) || '#64748b';
    var css = [
      '.lc-embed{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Apple SD Gothic Neo","Noto Sans KR",sans-serif;color:' + text + ';background:' + bg + ';border:1px solid ' + border + ';border-radius:16px;padding:20px;max-width:480px;width:100%;box-shadow:0 8px 24px rgba(15,23,42,.06);}',
      '.lc-embed *,.lc-embed *::before,.lc-embed *::after{box-sizing:border-box;}',
      '.lc-embed__title{margin:0 0 4px;font-size:1.125rem;font-weight:800;letter-spacing:-.02em;}',
      '.lc-embed__sub{margin:0 0 16px;font-size:.875rem;color:' + muted + ';line-height:1.45;}',
      '.lc-embed__field{margin:0 0 12px;}',
      '.lc-embed__label{display:block;margin:0 0 6px;font-size:.75rem;font-weight:700;color:' + muted + ';}',
      '.lc-embed__req{color:#e11d48;margin-left:2px;}',
      '.lc-embed__input,.lc-embed__textarea{width:100%;border:1px solid ' + border + ';border-radius:12px;padding:11px 12px;font-size:.95rem;color:' + text + ';background:#f8fafc;outline:none;transition:border-color .15s,box-shadow .15s;}',
      '.lc-embed__input:focus,.lc-embed__textarea:focus{border-color:' + accent + ';box-shadow:0 0 0 3px rgba(13,148,136,.15);background:#fff;}',
      '.lc-embed__textarea{min-height:96px;resize:vertical;}',
      '.lc-embed__privacy{display:flex;gap:8px;align-items:flex-start;margin:4px 0 14px;font-size:.8rem;color:' + muted + ';line-height:1.4;}',
      '.lc-embed__privacy input{margin-top:2px;}',
      '.lc-embed__privacy a{color:' + accent + ';font-weight:700;text-decoration:underline;}',
      '.lc-embed__btn{display:inline-flex;align-items:center;justify-content:center;width:100%;border:0;border-radius:12px;padding:13px 16px;font-size:.95rem;font-weight:800;cursor:pointer;background:' + accent + ';color:' + accentText + ';transition:opacity .15s,transform .15s;}',
      '.lc-embed__btn:hover{opacity:.92;}',
      '.lc-embed__btn:disabled{opacity:.55;cursor:not-allowed;}',
      '.lc-embed__msg{margin:12px 0 0;font-size:.85rem;line-height:1.4;}',
      '.lc-embed__msg--err{color:#be123c;}',
      '.lc-embed__msg--ok{color:#047857;}',
      '.lc-embed__loading{padding:28px 12px;text-align:center;color:' + muted + ';font-size:.9rem;}',
    ].join('');
    var style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = css;
    document.head.appendChild(style);
  }

  function el(tag, className, attrs) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === 'text') node.textContent = attrs[k];
        else if (k === 'html') node.innerHTML = attrs[k];
        else node.setAttribute(k, attrs[k]);
      });
    }
    return node;
  }

  function renderForm(mount, config, opts) {
    ensureStyles(config.theme || {});
    mount.innerHTML = '';
    var root = el('div', 'lc-embed');
    root.appendChild(el('h3', 'lc-embed__title', { text: config.title || '상담 신청' }));
    if (config.subtitle) {
      root.appendChild(el('p', 'lc-embed__sub', { text: config.subtitle }));
    }

    var form = el('form', 'lc-embed__form', { novalidate: 'novalidate' });
    var fields = Array.isArray(config.fields) ? config.fields : [];

    fields.forEach(function (field) {
      var wrap = el('div', 'lc-embed__field');
      var label = el('label', 'lc-embed__label');
      label.textContent = field.label || field.name;
      if (field.required) {
        label.appendChild(el('span', 'lc-embed__req', { text: '*' }));
      }
      wrap.appendChild(label);

      var input;
      if (field.type === 'textarea') {
        input = el('textarea', 'lc-embed__textarea', {
          name: field.name,
          placeholder: field.placeholder || '',
          rows: '3',
        });
      } else {
        input = el('input', 'lc-embed__input', {
          type: field.type || 'text',
          name: field.name,
          placeholder: field.placeholder || '',
          autocomplete: field.name === 'phone' ? 'tel' : field.name === 'name' ? 'name' : 'on',
        });
      }
      if (field.required) input.setAttribute('required', 'required');
      wrap.appendChild(input);
      form.appendChild(wrap);
    });

    var privacy = el('label', 'lc-embed__privacy');
    var check = el('input', '', { type: 'checkbox', name: 'privacy', required: 'required' });
    privacy.appendChild(check);
    var privacyText = el('span');
    privacyText.appendChild(document.createTextNode('개인정보 수집·이용에 동의합니다. '));
    if (config.privacyUrl) {
      var a = el('a', '', { href: config.privacyUrl, target: '_blank', rel: 'noopener noreferrer', text: '개인정보처리방침' });
      privacyText.appendChild(a);
    }
    privacy.appendChild(privacyText);
    form.appendChild(privacy);

    var btn = el('button', 'lc-embed__btn', { type: 'submit', text: config.submitLabel || '상담 신청하기' });
    form.appendChild(btn);
    var msg = el('div', 'lc-embed__msg', { role: 'status', 'aria-live': 'polite' });
    form.appendChild(msg);
    root.appendChild(form);
    mount.appendChild(root);

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      msg.className = 'lc-embed__msg';
      msg.textContent = '';

      if (!form.checkValidity()) {
        msg.className = 'lc-embed__msg lc-embed__msg--err';
        msg.textContent = '필수 항목을 확인해 주세요.';
        return;
      }
      if (!check.checked) {
        msg.className = 'lc-embed__msg lc-embed__msg--err';
        msg.textContent = '개인정보 수집·이용에 동의해 주세요.';
        return;
      }

      var payload = {
        lkCode: config.lkCode,
        channel: opts.channel || config.channel || 'wordpress',
        sub_id: opts.subId || '',
        name: '',
        phone: '',
        email: '',
        region: '',
        inquiry: '',
      };

      fields.forEach(function (field) {
        var node = form.elements.namedItem(field.name);
        if (node && 'value' in node) {
          payload[field.name] = String(node.value || '').trim();
        }
      });

      if (!payload.name || !payload.phone) {
        msg.className = 'lc-embed__msg lc-embed__msg--err';
        msg.textContent = '이름과 연락처는 필수입니다.';
        return;
      }

      btn.disabled = true;
      var prev = btn.textContent;
      btn.textContent = '접수 중...';

      fetch(config.submitUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(function (res) {
          return res.text().then(function (text) {
            var data = null;
            try { data = text ? JSON.parse(text) : null; } catch (e) { data = null; }
            return { res: res, data: data };
          });
        })
        .then(function (out) {
          var data = out.data || {};
          if (out.res.ok && data.ok) {
            msg.className = 'lc-embed__msg lc-embed__msg--ok';
            msg.textContent = (data.data && data.data.message) || config.successMessage || '접수되었습니다.';
            form.reset();
            return;
          }
          var err = (data && data.error) || '접수에 실패했습니다. 잠시 후 다시 시도해 주세요.';
          msg.className = 'lc-embed__msg lc-embed__msg--err';
          msg.textContent = err;
        })
        .catch(function () {
          msg.className = 'lc-embed__msg lc-embed__msg--err';
          msg.textContent = '네트워크 오류가 발생했습니다.';
        })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = prev;
        });
    });
  }

  function mountLoading(mount) {
    ensureStyles({});
    mount.innerHTML = '';
    var root = el('div', 'lc-embed');
    root.appendChild(el('div', 'lc-embed__loading', { text: '상담 폼을 불러오는 중...' }));
    mount.appendChild(root);
  }

  function mountError(mount, text) {
    ensureStyles({});
    mount.innerHTML = '';
    var root = el('div', 'lc-embed');
    root.appendChild(el('div', 'lc-embed__msg lc-embed__msg--err', { text: text || '폼을 불러오지 못했습니다.' }));
    mount.appendChild(root);
  }

  function resolveMount(script) {
    var target = attr(script, 'data-target');
    if (target) {
      var bySel = qs(target);
      if (bySel) return bySel;
    }
    var auto = qs('[data-lc-lead]');
    if (auto) return auto;
    var holder = el('div');
    if (script && script.parentNode) {
      script.parentNode.insertBefore(holder, script.nextSibling);
    } else {
      document.body.appendChild(holder);
    }
    return holder;
  }

  function bootOne(scriptOrMount) {
    var isScript = !!(scriptOrMount && scriptOrMount.tagName === 'SCRIPT');
    var script = isScript ? scriptOrMount : currentScript();
    var mount = isScript ? resolveMount(script) : scriptOrMount;
    if (!mount) return;
    if (mount.getAttribute('data-lc-ready') === '1') return;
    mount.setAttribute('data-lc-ready', '1');

    var lkCode = resolveLkCode(script, mount);
    var channel = attr(script, 'data-channel') || attr(mount, 'data-channel') || '';
    var subId = attr(script, 'data-sub-id') || attr(mount, 'data-sub-id') || '';
    var base = scriptBase(script);
    var configUrl =
      attr(script, 'data-config-url') ||
      attr(mount, 'data-config-url') ||
      (base ? base + '/api/embed.php' : '');

    if (!lkCode) {
      mountError(mount, 'lkCode가 없습니다. data-lk-code를 설정해 주세요.');
      return;
    }
    if (!configUrl) {
      mountError(mount, '설정 API 주소를 확인할 수 없습니다. lead-embed.js를 링크커넥트 도메인에서 로드해 주세요.');
      return;
    }

    mountLoading(mount);
    var url = configUrl + (configUrl.indexOf('?') >= 0 ? '&' : '?') + 'lkCode=' + encodeURIComponent(lkCode);

    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (res) {
        return res.text().then(function (text) {
          var data = null;
          try { data = text ? JSON.parse(text) : null; } catch (e) { data = null; }
          return { res: res, data: data };
        });
      })
      .then(function (out) {
        var data = out.data;
        if (!out.res.ok || !data || !data.ok || !data.data) {
          mountError(mount, (data && data.error) || '유효하지 않은 홍보 링크입니다.');
          return;
        }
        renderForm(mount, data.data, { channel: channel, subId: subId });
      })
      .catch(function () {
        mountError(mount, '상담 폼을 불러오지 못했습니다.');
      });
  }

  function boot(scriptHint) {
    var script = scriptHint || currentScript();
    if (script && (attr(script, 'data-lk-code') || attr(script, 'data-target'))) {
      bootOne(script);
      return;
    }
    var nodes = document.querySelectorAll('[data-lc-lead]:not([data-lc-ready="1"])');
    if (nodes.length) {
      for (var i = 0; i < nodes.length; i++) {
        bootOne(nodes[i]);
      }
      return;
    }
    if (script) {
      bootOne(script);
    }
  }

  // 스크립트 태그마다 실행되므로 다중 폼도 각각 부팅
  var thisScript = currentScript();
  function start() {
    boot(thisScript);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  window.LinkConnectLeadEmbed = { boot: boot, bootOne: bootOne };
})();
// hotfix 1785670482
