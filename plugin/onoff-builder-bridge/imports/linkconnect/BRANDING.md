# OnOff CPA SPA branding — DO NOT overwrite

`plugin/onoff-builder-bridge/imports/linkconnect/` 는 **온오프CPA 전용** SPA 입니다.

- ✅ 갱신: `cd builder/linkconnect_source && npm run deploy:imports`
- ❌ 금지: linkconnect 저장소의 `imports/linkconnect` 를 rsync/복사
- 가드:
  - `spa-brand.onoffcpa` lock (`brand=onoffcpa`)
  - `scripts/assert-onoffcpa-spa-branding.sh` (sync·CI)
  - 런타임: `onoff_builder_assert_onoffcpa_spa_html()` — 잘못된 번들은 서빙 거부

잘못 덮이면 홈이 "링크커넥트"로 보입니다. CI·런타임이 이를 차단합니다.
