/**
 * onoffcpa 독립 도메인 프록시 (Cloudflare Worker)
 *
 * - Public: https://goispa.kr  (Custom Domain → HTTPS 자동 발급)
 * - Origin: https://onoffcpa.icrm.co.kr
 * - 루트 폴백 랜딩: /merchant/banktupt/
 *
 * Cloudflare 적용:
 * 1) Workers → goispakr → 이 코드로 교체 후 Deploy
 * 2) Domains → Custom Domain → goispa.kr 추가 (서브도메인 칸 비움)
 *    www 쓰면 www.goispa.kr 도 추가
 * 3) DNS에 기존 A/CNAME(웹용)이 있으면 삭제 후 추가 (MX/TXT 메일 레코드는 유지)
 * 4) SSL/TLS = Full 또는 Full (strict)
 */

const ORIGIN = 'https://onoffcpa.icrm.co.kr';
const ORIGIN_HOST = 'onoffcpa.icrm.co.kr';
const ROOT_LANDING = '/merchant/banktupt/';
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
          const sameRoot =
            (loc.pathname === '/' || loc.pathname === '') &&
            (incoming.pathname === '/' || incoming.pathname === '');
          if (sameRoot) {
            loc.pathname = ROOT_LANDING;
          }
          outHeaders.set('Location', loc.toString());
        }
      } catch (_) {
        // keep original Location
      }
    }

    outHeaders.delete('cf-ray');

    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: outHeaders,
    });
  },
};
