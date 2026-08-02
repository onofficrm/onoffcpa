# onoffcpa 독립 도메인 (Cloudflare Worker)

`iloves.kr` → `onoffcpa.icrm.co.kr` 프록시 + HTTPS(Custom Domain 자동 인증서)

## 1. Worker 코드 배포

1. [Cloudflare Dashboard](https://dash.cloudflare.com/) → **Workers & Pages** → `iloveskr`
2. 에디터에 `iloveskr-worker.js` 내용을 붙여넣기
3. **Deploy** 클릭

## 2. Custom Domain (HTTPS 자동 발급)

1. Worker → **Settings** → **Domains & Routes**
2. **Add** → **Custom Domain**
3. `iloves.kr` 입력 후 저장  
   - `www.iloves.kr` 도 쓰면 한 번 더 추가
4. Cloudflare가 DNS 레코드 + **SSL 인증서를 자동 발급**합니다

`iloves.kr` 존이 같은 Cloudflare 계정에 있어야 합니다.  
아직 없으면 **Websites → Add a site** 로 도메인을 추가한 뒤 네임서버를 Cloudflare로 바꾸세요.

## 3. SSL 모드

해당 존 **SSL/TLS** → **Overview** → **Full** 또는 **Full (strict)**

## 4. onoffcpa 관리자

광고상품 → **홍보 링크 독립 도메인** = `https://iloves.kr` 저장

확인:

- `https://iloves.kr/` → 다시봄 랜딩
- `https://iloves.kr/r/{코드}` → 클릭 트래킹 후 랜딩
