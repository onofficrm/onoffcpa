/**
 * onoffcpa 독립 도메인 프록시 (Cloudflare Worker)
 *
 * - Public: https://iloves.kr  (Custom Domain → HTTPS 자동 발급)
 * - Origin: https://onoffcpa.icrm.co.kr
 *
 * Cloudflare 적용:
 * 1) Workers → iloveskr → 이 코드로 교체 후 Deploy
 * 2) Settings → Domains & Routes → Custom Domains → iloves.kr 추가
 *    (www.iloves.kr 도 쓰면 동일하게 추가)
 * 3) SSL/TLS 모드는 Full 또는 Full (strict)
 */

const ORIGIN = 'https://onoffcpa.icrm.co.kr';
const ORIGIN_HOST = 'onoffcpa.icrm.co.kr';
const LEGACY_ORIGIN_HOSTS = ['onoffcpa.icrm.co.kr', 'onoffcpa.iwinv.net', 'www.onoffcpa.icrm.co.kr', 'www.onoffcpa.iwinv.net'];

export default {
  async fetch(request) {
    const incoming = new URL(request.url);
    const targetUrl = ORIGIN + incoming.pathname + incoming.search;

    const headers = new Headers(request.headers);
    headers.set('Host', ORIGIN_HOST);
    headers.set('X-Forwarded-Host', incoming.hostname);
    headers.set('X-Forwarded-Proto', 'https');
    headers.set('X-Forwarded-Port', '443');

    const visitorIp = request.headers.get('CF-Connecting-IP');
    if (visitorIp) {
      headers.set('CF-Connecting-IP', visitorIp);
      headers.set('X-Forwarded-For', visitorIp);
    }

    // hop-by-hop
    headers.delete('content-length');

    const init = {
      method: request.method,
      headers,
      redirect: 'manual',
    };
    if (request.method !== 'GET' && request.method !== 'HEAD') {
      init.body = request.body;
    }

    const upstream = await fetch(targetUrl, init);
    const outHeaders = new Headers(upstream.headers);

    const location = outHeaders.get('Location');
    if (location) {
      try {
        const loc = new URL(location, ORIGIN);
        if (LEGACY_ORIGIN_HOSTS.includes(loc.hostname.toLowerCase())) {
          loc.protocol = 'https:';
          loc.hostname = incoming.hostname;
          // origin 홈(/) 리다이렉트를 공개 도메인 루트로 바꾸면 루프 → 다시봄 랜딩으로
          const sameRoot =
            (loc.pathname === '/' || loc.pathname === '') &&
            (incoming.pathname === '/' || incoming.pathname === '');
          if (sameRoot) {
            loc.pathname = '/merchant/dasibom/';
          }
          outHeaders.set('Location', loc.toString());
        }
      } catch (_) {
        // keep original Location
      }
    }

    // 프록시 흔적 정리(선택)
    outHeaders.delete('cf-ray');

    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: outHeaders,
    });
  },
};
