# onoffcpa 독립 도메인 (Cloudflare Worker)

공개 도메인 → `onoffcpa.icrm.co.kr` 프록시 + HTTPS(Custom Domain 자동 인증서)

| Worker | 도메인 | 루트 랜딩 | 파일 |
|--------|--------|-----------|------|
| `iloveskr` | `iloves.kr` | `/merchant/dasibom/` | `iloveskr-worker.js` |
| `goispakr` | `goispa.kr` | `/merchant/banktupt/` | `goispakr-worker.js` |

## 공통 적용 순서

1. Worker 에디터에 해당 `*-worker.js` 붙여넣기 → **Deploy**
2. **Domains** → **+ 도메인 추가** → 서브도메인 **비움**(루트) → 도메인 추가
3. DNS에 기존 **A/CNAME(웹)** 이 있으면 삭제 후 다시 추가 (**MX/TXT 메일은 유지**)
4. 존 **SSL/TLS** = Full 또는 Full (strict)
5. onoffcpa 관리자 → 해당 상품 **홍보 링크 독립 도메인** 저장 후 확인

### goispa.kr 확인

- `https://goispa.kr/` → banktupt 랜딩
- `https://goispa.kr/r/{코드}` → 클릭 트래킹
