=== 온오프CPA 상담폼 ===
Contributors: linkconnect
Tags: lead, form, cpa, affiliate, linkconnect, widget, gtm
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later

파트너 홍보코드(lkCode)·위젯 키로 온오프CPA 상담신청 위젯을 삽입합니다.

== Description ==

1. 플러그인 활성화
2. 설정 → 온오프CPA 상담폼 에서 홍보코드·위젯 키·형태 입력
3. 페이지에 숏코드 `[linkconnect_lead]` 또는 블록 「온오프CPA 상담폼」 삽입

디자인(강조색·문구·필드)·완료 후 리다이렉트·GTM 이벤트명은 파트너센터 HTML 위젯 안내에서 저장합니다. 워드프레스 플러그인은 설치·모드만 담당합니다.

== Installation ==

1. zip 업로드 후 활성화
2. 설정에서 lkCode / widget_key 저장
3. 숏코드/블록 배치
4. (권장) 파트너센터에서 허용 도메인에 사이트 주소를 등록

== Frequently Asked Questions ==

= Google Tag Manager 전환 추적은 어떻게 하나요? =

파트너센터 위젯 설정에서「GTM dataLayer 이벤트」를 켜 두면, 상담 접수 성공 시 설치 페이지에서
`dataLayer.push({ event: 'lc_lead_submit', ... })` 가 실행됩니다.
GTM 트리거를 Custom Event / 이벤트명 `lc_lead_submit`(또는 설정한 이벤트명)으로 만들면 됩니다.
테마·헤더에 GTM 컨테이너가 이미 로드되어 있어야 합니다.

= 테마 CSS와 충돌하나요? =

기본 설치는 iframe으로 위젯을 띄워 테마 스타일 충돌을 줄입니다. 폼/버튼/전화 형태는 설정에서 고를 수 있습니다.

= 버튼·전화 문구는 어디서 바꾸나요? =

파트너센터 → HTML 위젯 안내의 디자인 설정(제출 버튼 / 버튼형 라벨 / 전화 라벨)에서 변경합니다.

== Changelog ==

= 1.1.1 =
* GTM dataLayer·테마(iframe) 안내 FAQ 보강
* 파트너센터 디자인/필드 설정과 연동되는 안내 문구 정리

= 1.1.0 =
* 위젯 키(data-widget-key) 지원
* 폼/버튼/전화 모드 선택
* iframe 프레임 URL 연결

= 1.0.0 =
* 최초 공개: 설정, 숏코드, 구텐베르크 블록
