/**
 * CPA 상담신청 임베드 위젯 (외부 홈페이지용)
 *
 * data-mode:
 *   form   (기본) 인라인 상담폼 + 전화
 *   button 버튼 클릭 시 모달 상담폼
 *   phone  전화 상담 버튼만
 *
 * 사용 예:
 * <div id="lc-lead-form"></div>
 * <script src=".../lead-embed.js" data-lk-code="CODE" data-target="#lc-lead-form" data-channel="embed" data-mode="form" async></script>
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

  function resolveWidgetKey(script, mount) {
    return (
      readUrlParam('widgetKey') ||
      readUrlParam('widget_key') ||
      readUrlParam('wgt') ||
      attr(mount, 'data-widget-key') ||
      attr(script, 'data-widget-key') ||
      ''
    ).trim();
  }

  function resolveMode(script, mount) {
    var mode = (
      attr(script, 'data-mode') ||
      attr(mount, 'data-mode') ||
      readUrlParam('lcMode') ||
      'form'
    ).toLowerCase();
    if (mode === 'modal' || mode === 'btn') mode = 'button';
    if (mode === 'call' || mode === 'tel') mode = 'phone';
    if (mode !== 'button' && mode !== 'phone') mode = 'form';
    return mode;
  }

  function digitsOnly(v) {
    return String(v || '').replace(/\D+/g, '');
  }

  function parseUtmFromUrl(url) {
    var out = { utm_source: '', utm_medium: '', utm_campaign: '' };
    try {
      var u = new URL(url || '', window.location.href);
      out.utm_source = (u.searchParams.get('utm_source') || '').trim();
      out.utm_medium = (u.searchParams.get('utm_medium') || '').trim();
      out.utm_campaign = (u.searchParams.get('utm_campaign') || '').trim();
    } catch (e) {}
    return out;
  }

  function collectTrafficMeta(opts) {
    var pageUrl = opts.pageUrl || window.location.href;
    var utm = parseUtmFromUrl(pageUrl);
    return {
      page_url: pageUrl,
      referer: opts.referer || document.referrer || '',
      utm_source: opts.utmSource || utm.utm_source || '',
      utm_medium: opts.utmMedium || utm.utm_medium || '',
      utm_campaign: opts.utmCampaign || utm.utm_campaign || '',
    };
  }

  function pushConversionEvent(payload) {
    if (!payload) return;
    try {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(payload);
    } catch (e) {}
    try {
      if (typeof window.gtag === 'function') {
        window.gtag('event', payload.event || 'lc_lead_submit', payload);
      }
    } catch (e2) {}
    try {
      window.dispatchEvent(new CustomEvent('lc-embed-success', { detail: payload }));
    } catch (e3) {}
  }

  function buildConversionPayload(config, traffic) {
    var eventName = String(config.conversionEventName || 'lc_lead_submit').trim() || 'lc_lead_submit';
    traffic = traffic || {};
    return {
      event: eventName,
      lc_source: 'embed',
      lc_lk_code: config.lkCode || '',
      lc_campaign: config.campaignTitle || config.campaignCode || '',
      lc_utm_source: traffic.utm_source || '',
      lc_utm_medium: traffic.utm_medium || '',
      lc_utm_campaign: traffic.utm_campaign || '',
      lc_page_url: traffic.page_url || '',
    };
  }

  function ensureStyles(theme) {
    var accent = (theme && theme.accent) || '#0d9488';
    var accentText = (theme && theme.accentText) || '#ffffff';
    var border = (theme && theme.border) || '#e2e8f0';
    var bg = (theme && theme.bg) || '#ffffff';
    var text = (theme && theme.text) || '#0f172a';
    var muted = (theme && theme.muted) || '#64748b';
    var call = (theme && theme.call) || '#059669';
    var radius = (theme && theme.radius) || '16px';
    var shadow = (theme && theme.shadow) || '0 8px 24px rgba(15,23,42,.06)';
    var padding = (theme && theme.padding) || '20px';
    var inputBg = (theme && theme.inputBg) || '#f8fafc';
    var css = [
      '.lc-embed{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Apple SD Gothic Neo","Noto Sans KR",sans-serif;color:' + text + ';background:' + bg + ';border:1px solid ' + border + ';border-radius:' + radius + ';padding:' + padding + ';max-width:480px;width:100%;box-shadow:' + shadow + ';}',
      '.lc-embed *,.lc-embed *::before,.lc-embed *::after{box-sizing:border-box;}',
      '.lc-embed__title{margin:0 0 4px;font-size:1.125rem;font-weight:800;letter-spacing:-.02em;}',
      '.lc-embed__sub{margin:0 0 16px;font-size:.875rem;color:' + muted + ';line-height:1.45;}',
      '.lc-embed__header{margin:0 -20px 16px;padding:18px 20px 14px;background:' + ((theme && theme.headerBg) || accent) + ';color:' + ((theme && theme.headerText) || '#fff') + ';}',
      '.lc-embed__header .lc-embed__title{margin:0;color:inherit;}',
      '.lc-embed__header .lc-embed__sub{margin:4px 0 0;color:inherit;opacity:.9;}',
      '.lc-embed__call{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 0 14px;padding:12px 14px;border-radius:12px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.22);color:' + call + ';font-weight:800;font-size:.95rem;text-decoration:none;}',
      '.lc-embed__call:hover{background:rgba(5,150,105,.14);}',
      '.lc-embed__call span{font-variant-numeric:tabular-nums;letter-spacing:.02em;}',
      '.lc-embed__call--solo{margin:0;max-width:480px;}',
      '.lc-embed__field{margin:0 0 12px;}',
      '.lc-embed__label{display:block;margin:0 0 6px;font-size:.75rem;font-weight:700;color:' + muted + ';}',
      '.lc-embed__req{color:#e11d48;margin-left:2px;}',
      '.lc-embed__input,.lc-embed__textarea{width:100%;border:1px solid ' + (border === 'transparent' ? '#e2e8f0' : border) + ';border-radius:12px;padding:11px 12px;font-size:.95rem;color:' + text + ';background:' + inputBg + ';outline:none;transition:border-color .15s,box-shadow .15s;}',
      '.lc-embed__input:focus,.lc-embed__textarea:focus{border-color:' + accent + ';box-shadow:0 0 0 3px rgba(13,148,136,.15);background:' + (inputBg === '#1e293b' ? '#1e293b' : '#fff') + ';}',
      '.lc-embed__textarea{min-height:96px;resize:vertical;}',
      '.lc-embed__hp{position:absolute!important;left:-9999px!important;height:0!important;overflow:hidden!important;opacity:0!important;}',
      '.lc-embed__privacy{display:flex;gap:8px;align-items:flex-start;margin:4px 0 14px;font-size:.8rem;color:' + muted + ';line-height:1.4;}',
      '.lc-embed__privacy input{margin-top:2px;}',
      '.lc-embed__privacy a{color:' + accent + ';font-weight:700;text-decoration:underline;}',
      '.lc-embed__btn{display:inline-flex;align-items:center;justify-content:center;width:100%;border:0;border-radius:12px;padding:13px 16px;font-size:.95rem;font-weight:800;cursor:pointer;background:' + accent + ';color:' + accentText + ';transition:opacity .15s,transform .15s;}',
      '.lc-embed__btn:hover{opacity:.92;}',
      '.lc-embed__btn:disabled{opacity:.55;cursor:not-allowed;}',
      '.lc-embed__trigger{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:12px;padding:13px 18px;font-size:.95rem;font-weight:800;cursor:pointer;background:' + accent + ';color:' + accentText + ';box-shadow:0 8px 20px rgba(13,148,136,.25);}',
      '.lc-embed__trigger:hover{opacity:.92;}',
      '.lc-embed__msg{margin:12px 0 0;font-size:.85rem;line-height:1.4;}',
      '.lc-embed__msg--err{color:#be123c;}',
      '.lc-embed__msg--ok{color:#047857;}',
      '.lc-embed__loading{padding:28px 12px;text-align:center;color:' + muted + ';font-size:.9rem;}',
      '.lc-embed__foot{margin:12px 0 0;font-size:.7rem;color:' + muted + ';text-align:center;}',
      '.lc-embed__phone-wrap{max-width:480px;}',
      '.lc-embed__phone-note{margin:10px 0 0;font-size:.8rem;color:' + muted + ';line-height:1.4;}',
      '.lc-embed--bold .lc-embed__btn{padding:15px 16px;font-size:1rem;}',
      '.lc-embed-overlay{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);}',
      '.lc-embed-overlay[hidden]{display:none!important;}',
      '.lc-embed-modal{position:relative;width:100%;max-width:480px;max-height:min(92vh,720px);overflow:auto;}',
      '.lc-embed-modal .lc-embed{max-width:none;box-shadow:0 24px 48px rgba(15,23,42,.28);}',
      '.lc-embed-modal__close{position:absolute;top:10px;right:10px;z-index:2;width:36px;height:36px;border:0;border-radius:999px;background:rgba(15,23,42,.06);color:#334155;font-size:20px;line-height:1;cursor:pointer;}',
      '.lc-embed-modal__close:hover{background:rgba(15,23,42,.12);}',
      '.lc-embed-frame{display:block;width:100%;max-width:480px;border:0;background:transparent;overflow:hidden;}',
      '.lc-embed-frame--modal{max-width:100%;width:100%;min-height:420px;border-radius:16px;background:#fff;}',
      '.lc-embed-host{max-width:480px;width:100%;}',
    ].join('');
    var style = qs('#' + STYLE_ID);
    if (!style) {
      style = document.createElement('style');
      style.id = STYLE_ID;
      document.head.appendChild(style);
    }
    style.textContent = css;
  }

  function resolvePreset(config) {
    var preset = '';
    if (config && config.theme && config.theme.preset) preset = String(config.theme.preset);
    if (!preset && config && config.preset) preset = String(config.preset);
    if (!preset && config && config.options && config.options.preset) preset = String(config.options.preset);
    preset = preset.toLowerCase();
    if (preset === 'simple' || preset === 'card' || preset === 'bold' || preset === 'soft' || preset === 'dark') {
      return preset;
    }
    return 'default';
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

  function buildCallLink(config, solo) {
    var phoneRaw = config.partnerPhone || config.trackingPhone || '';
    var phoneDisplay = config.partnerPhoneDisplay || config.trackingPhoneDisplay || phoneRaw;
    if (!config.hasPartnerPhone || !phoneRaw) return null;
    var call = el('a', 'lc-embed__call' + (solo ? ' lc-embed__call--solo' : ''), {
      href: 'tel:' + digitsOnly(phoneRaw),
      rel: 'nofollow',
    });
    call.appendChild(document.createTextNode((config.callLabel || '전화 상담') + ' '));
    call.appendChild(el('span', '', { text: phoneDisplay }));
    return call;
  }

  function bindFormSubmit(form, btn, check, msg, config, opts, fields) {
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

      var traffic = collectTrafficMeta(opts);
      var payload = {
        lkCode: config.lkCode,
        widgetKey: opts.widgetKey || config.widgetKey || '',
        channel: opts.channel || config.channel || 'embed',
        source: config.source || 'embed',
        sub_id: opts.subId || traffic.utm_campaign || '',
        page_url: traffic.page_url,
        referer: traffic.referer,
        utm_source: traffic.utm_source,
        utm_medium: traffic.utm_medium,
        utm_campaign: traffic.utm_campaign,
        name: '',
        phone: '',
        email: '',
        region: '',
        inquiry: '',
        website: '',
      };

      fields.forEach(function (field) {
        var node = form.elements.namedItem(field.name);
        if (node && 'value' in node) {
          payload[field.name] = String(node.value || '').trim();
        }
      });
      var hp = form.elements.namedItem('website');
      if (hp && 'value' in hp) payload.website = String(hp.value || '').trim();

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
            var redirect = String(config.successRedirectUrl || '').trim();
            var track = config.trackConversion !== false;
            var convPayload = track ? buildConversionPayload(config, {
              page_url: payload.page_url,
              utm_source: payload.utm_source,
              utm_medium: payload.utm_medium,
              utm_campaign: payload.utm_campaign,
            }) : null;
            if (track && convPayload && !(window.parent && window.parent !== window)) {
              pushConversionEvent(convPayload);
            }
            try {
              if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                  type: 'lc-embed-success',
                  redirect: redirect,
                  track: track,
                  conversion: convPayload,
                }, '*');
              }
            } catch (e) {}
            if (redirect) {
              setTimeout(function () {
                try {
                  if (window.top && window.top !== window) {
                    window.top.location.href = redirect;
                  } else {
                    window.location.href = redirect;
                  }
                } catch (e2) {
                  try { window.location.href = redirect; } catch (e3) {}
                }
              }, 700);
            }
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

  function buildFormCard(config, opts) {
    var preset = resolvePreset(config);
    var root = el('div', 'lc-embed lc-embed--' + preset);

    if (preset === 'bold') {
      var header = el('div', 'lc-embed__header');
      header.appendChild(el('h3', 'lc-embed__title', { text: config.title || '무료 상담 신청' }));
      if (config.subtitle) {
        header.appendChild(el('p', 'lc-embed__sub', { text: config.subtitle }));
      }
      root.appendChild(header);
    } else {
      root.appendChild(el('h3', 'lc-embed__title', { text: config.title || '무료 상담 신청' }));
      if (config.subtitle) {
        root.appendChild(el('p', 'lc-embed__sub', { text: config.subtitle }));
      }
    }

    var call = buildCallLink(config, false);
    if (call) root.appendChild(call);

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

    var hpWrap = el('div', 'lc-embed__hp', { 'aria-hidden': 'true' });
    hpWrap.appendChild(el('label', '', { text: '웹사이트', for: 'lc-embed-website-' + (config.lkCode || 'x') }));
    hpWrap.appendChild(el('input', '', {
      id: 'lc-embed-website-' + (config.lkCode || 'x'),
      type: 'text',
      name: 'website',
      tabindex: '-1',
      autocomplete: 'off',
    }));
    form.appendChild(hpWrap);

    var privacy = el('label', 'lc-embed__privacy');
    var check = el('input', '', { type: 'checkbox', name: 'privacy', required: 'required' });
    privacy.appendChild(check);
    var privacyText = el('span');
    var privacyLabel = String(config.privacyText || '개인정보 수집·이용에 동의합니다.').trim();
    privacyText.appendChild(document.createTextNode(privacyLabel + (config.privacyUrl ? ' ' : '')));
    if (config.privacyUrl) {
      privacyText.appendChild(el('a', '', {
        href: config.privacyUrl,
        target: '_blank',
        rel: 'noopener noreferrer',
        text: '개인정보처리방침',
      }));
    }
    privacy.appendChild(privacyText);
    form.appendChild(privacy);

    var btn = el('button', 'lc-embed__btn', { type: 'submit', text: config.submitLabel || '상담 신청하기' });
    form.appendChild(btn);
    var msg = el('div', 'lc-embed__msg', { role: 'status', 'aria-live': 'polite' });
    form.appendChild(msg);
    root.appendChild(form);

    if (config.brandName) {
      root.appendChild(el('p', 'lc-embed__foot', { text: config.brandName + ' 상담 위젯' }));
    }

    bindFormSubmit(form, btn, check, msg, config, opts, fields);
    return root;
  }

  function renderForm(mount, config, opts) {
    ensureStyles(config.theme || {});
    mount.innerHTML = '';
    mount.appendChild(buildFormCard(config, opts));
  }

  function renderPhone(mount, config) {
    ensureStyles(config.theme || {});
    mount.innerHTML = '';
    var wrap = el('div', 'lc-embed__phone-wrap');
    var call = buildCallLink(config, true);
    if (call) {
      wrap.appendChild(call);
      wrap.appendChild(el('p', 'lc-embed__phone-note', {
        text: '안심번호로 연결됩니다. 상담 성과는 플랫폼에서 확인할 수 있습니다.',
      }));
    } else {
      var box = el('div', 'lc-embed');
      box.appendChild(el('div', 'lc-embed__msg lc-embed__msg--err', {
        text: '배정된 안심번호가 없습니다. 파트너센터에서 콜디비 배정 후 이용해 주세요.',
      }));
      wrap.appendChild(box);
    }
    if (config.brandName) {
      wrap.appendChild(el('p', 'lc-embed__foot', { text: config.brandName + ' 전화 위젯' }));
    }
    mount.appendChild(wrap);
  }

  function renderButton(mount, config, opts) {
    ensureStyles(config.theme || {});
    mount.innerHTML = '';

    var trigger = el('button', 'lc-embed__trigger', {
      type: 'button',
      text: config.buttonLabel || config.submitLabel || '무료 상담 신청',
    });
    mount.appendChild(trigger);

    var overlay = el('div', 'lc-embed-overlay');
    overlay.setAttribute('hidden', 'hidden');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    var modal = el('div', 'lc-embed-modal');
    var closeBtn = el('button', 'lc-embed-modal__close', { type: 'button', 'aria-label': '닫기', text: '×' });
    modal.appendChild(closeBtn);
    modal.appendChild(buildFormCard(config, opts));
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    function openModal() {
      overlay.removeAttribute('hidden');
      document.documentElement.style.overflow = 'hidden';
    }
    function closeModal() {
      overlay.setAttribute('hidden', 'hidden');
      document.documentElement.style.overflow = '';
    }

    trigger.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (ev) {
      if (ev.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && !overlay.hasAttribute('hidden')) closeModal();
    });
  }

  function renderWidget(mount, config, opts) {
    var mode = opts.mode || 'form';
    if (mode === 'phone') {
      renderPhone(mount, config);
      return;
    }
    if (mode === 'button') {
      renderButton(mount, config, opts);
      return;
    }
    renderForm(mount, config, opts);
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

  function buildFrameSrc(frameUrl, opts) {
    var url = frameUrl
      + (frameUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'lkCode=' + encodeURIComponent(opts.lkCode || '')
      + '&mode=' + encodeURIComponent(opts.mode === 'button' ? 'form' : (opts.mode || 'form'))
      + '&channel=' + encodeURIComponent(opts.channel || 'embed')
      + '&page_url=' + encodeURIComponent(opts.pageUrl || window.location.href);
    if (opts.subId) {
      url += '&sub_id=' + encodeURIComponent(opts.subId);
    }
    if (opts.widgetKey) {
      url += '&widgetKey=' + encodeURIComponent(opts.widgetKey);
    }
    if (opts.referer) {
      url += '&referer=' + encodeURIComponent(opts.referer);
    }
    return url;
  }

  function bindFrameResize(iframe) {
    function onMessage(ev) {
      var data = ev && ev.data;
      if (!data || !data.type) return;
      if (!iframe || !iframe.contentWindow || ev.source !== iframe.contentWindow) return;
      if (data.type === 'lc-embed-resize') {
        var h = parseInt(data.height, 10);
        if (!h || h < 80) return;
        iframe.style.height = Math.min(Math.max(h + 8, 120), 900) + 'px';
        return;
      }
      if (data.type === 'lc-embed-success') {
        if (data.track !== false && data.conversion) {
          pushConversionEvent(data.conversion);
        }
        var redirect = String(data.redirect || '').trim();
        if (redirect) {
          try { window.location.href = redirect; } catch (e) {}
        }
      }
    }
    window.addEventListener('message', onMessage);
  }

  function mountFormIframe(mount, frameUrl, opts) {
    ensureStyles({});
    mount.innerHTML = '';
    var host = el('div', 'lc-embed-host');
    var iframe = el('iframe', 'lc-embed-frame', {
      src: buildFrameSrc(frameUrl, opts),
      title: '상담 신청',
      loading: 'lazy',
      referrerpolicy: 'no-referrer-when-downgrade',
    });
    iframe.setAttribute('allow', 'clipboard-write');
    iframe.style.height = '520px';
    host.appendChild(iframe);
    mount.appendChild(host);
    bindFrameResize(iframe);
  }

  function mountButtonIframe(mount, frameUrl, opts) {
    ensureStyles({});
    mount.innerHTML = '';
    var trigger = el('button', 'lc-embed__trigger', {
      type: 'button',
      text: opts.buttonLabel || opts.submitLabel || '무료 상담 신청',
    });
    mount.appendChild(trigger);

    var overlay = el('div', 'lc-embed-overlay');
    overlay.setAttribute('hidden', 'hidden');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    var modal = el('div', 'lc-embed-modal');
    var closeBtn = el('button', 'lc-embed-modal__close', { type: 'button', 'aria-label': '닫기', text: '×' });
    var iframe = el('iframe', 'lc-embed-frame lc-embed-frame--modal', {
      title: '상담 신청',
      loading: 'lazy',
      referrerpolicy: 'no-referrer-when-downgrade',
    });
    iframe.setAttribute('allow', 'clipboard-write');
    iframe.style.height = '560px';
    modal.appendChild(closeBtn);
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    bindFrameResize(iframe);

    function openModal() {
      if (!iframe.getAttribute('src')) {
        iframe.setAttribute('src', buildFrameSrc(frameUrl, opts));
      }
      overlay.removeAttribute('hidden');
      document.documentElement.style.overflow = 'hidden';
    }
    function closeModal() {
      overlay.setAttribute('hidden', 'hidden');
      document.documentElement.style.overflow = '';
    }

    trigger.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (ev) {
      if (ev.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && !overlay.hasAttribute('hidden')) closeModal();
    });
  }

  function bootInline(mount, script, opts) {
    var configUrl =
      attr(script, 'data-config-url') ||
      attr(mount, 'data-config-url') ||
      (opts.base ? opts.base + '/api/embed.php' : '');
    if (!configUrl) {
      mountError(mount, '설정 API 주소를 확인할 수 없습니다. lead-embed.js를 플랫폼 도메인에서 로드해 주세요.');
      return;
    }

    mountLoading(mount);
    var url = configUrl
      + (configUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'lkCode=' + encodeURIComponent(opts.lkCode)
      + '&page_url=' + encodeURIComponent(opts.pageUrl || window.location.href);
    if (opts.widgetKey) {
      url += '&widgetKey=' + encodeURIComponent(opts.widgetKey);
    }

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
        renderWidget(mount, data.data, {
          channel: opts.channel,
          subId: opts.subId,
          mode: opts.mode,
          pageUrl: opts.pageUrl,
          widgetKey: opts.widgetKey || data.data.widgetKey || '',
        });
      })
      .catch(function () {
        mountError(mount, '상담 폼을 불러오지 못했습니다.');
      });
  }

  function bootOne(scriptOrMount) {
    var isScript = !!(scriptOrMount && scriptOrMount.tagName === 'SCRIPT');
    var script = isScript ? scriptOrMount : currentScript();
    var mount = isScript ? resolveMount(script) : scriptOrMount;
    if (!mount) return;
    if (mount.getAttribute('data-lc-ready') === '1') return;
    mount.setAttribute('data-lc-ready', '1');

    var lkCode = resolveLkCode(script, mount);
    var widgetKey = resolveWidgetKey(script, mount);
    var channel = attr(script, 'data-channel') || attr(mount, 'data-channel') || 'embed';
    var subId = attr(script, 'data-sub-id') || attr(mount, 'data-sub-id') || '';
    var mode = resolveMode(script, mount);
    var base = scriptBase(script);
    var pageUrl = attr(script, 'data-page-url') || attr(mount, 'data-page-url') || window.location.href;
    var forceInline =
      attr(script, 'data-frame') === '1' ||
      attr(mount, 'data-frame') === '1' ||
      attr(script, 'data-inline') === '1' ||
      attr(mount, 'data-inline') === '1';
    var frameUrl =
      attr(script, 'data-frame-url') ||
      attr(mount, 'data-frame-url') ||
      (base ? base + '/api/embed_frame.php' : '');

    if (!lkCode) {
      mountError(mount, 'lkCode가 없습니다. data-lk-code를 설정해 주세요.');
      return;
    }

    var opts = {
      lkCode: lkCode,
      widgetKey: widgetKey,
      channel: channel,
      subId: subId,
      mode: mode,
      pageUrl: pageUrl,
      referer: attr(script, 'data-referer') || attr(mount, 'data-referer') || document.referrer || '',
      base: base,
    };

    // iframe 프레임 내부 또는 명시적 inline → 직접 렌더
    if (forceInline) {
      bootInline(mount, script, opts);
      return;
    }

    // 전화형은 가벼운 버튼만 필요 → 직접 렌더
    if (mode === 'phone') {
      bootInline(mount, script, opts);
      return;
    }

    // 호스트 페이지: iframe으로 CSS 충돌 차단
    if (!frameUrl) {
      bootInline(mount, script, opts);
      return;
    }
    if (mode === 'button') {
      // 트리거 문구는 파트너 설정(buttonLabel)을 따름
      var configUrl =
        attr(script, 'data-config-url') ||
        attr(mount, 'data-config-url') ||
        (base ? base + '/api/embed.php' : '');
      if (!configUrl) {
        mountButtonIframe(mount, frameUrl, opts);
        return;
      }
      var labelUrl = configUrl
        + (configUrl.indexOf('?') >= 0 ? '&' : '?')
        + 'lkCode=' + encodeURIComponent(opts.lkCode)
        + '&page_url=' + encodeURIComponent(opts.pageUrl || window.location.href);
      if (opts.widgetKey) {
        labelUrl += '&widgetKey=' + encodeURIComponent(opts.widgetKey);
      }
      fetch(labelUrl, { headers: { Accept: 'application/json' } })
        .then(function (res) { return res.json().catch(function () { return null; }); })
        .then(function (data) {
          if (data && data.ok && data.data) {
            opts.buttonLabel = data.data.buttonLabel || data.data.submitLabel || opts.buttonLabel;
            opts.submitLabel = data.data.submitLabel || opts.submitLabel;
            opts.widgetKey = opts.widgetKey || data.data.widgetKey || '';
          }
          mountButtonIframe(mount, frameUrl, opts);
        })
        .catch(function () {
          mountButtonIframe(mount, frameUrl, opts);
        });
      return;
    }
    mountFormIframe(mount, frameUrl, opts);
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
