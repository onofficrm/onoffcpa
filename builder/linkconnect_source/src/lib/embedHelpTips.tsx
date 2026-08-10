import type { ReactNode } from 'react';

function TipBody({ why, ifSkip, tip }: { why: string; ifSkip?: string; tip?: string }) {
  return (
    <>
      <p>{why}</p>
      {ifSkip ? <p className="text-slate-500">{ifSkip}</p> : null}
      {tip ? (
        <p className="rounded-xl bg-cyan-50 border border-cyan-100 px-3 py-2 text-cyan-950 text-xs font-medium">
          추천: {tip}
        </p>
      ) : null}
    </>
  );
}

export const EMBED_HELP: Record<string, { title: string; body: ReactNode }> = {
  overview: {
    title: '외부 상담 위젯이란?',
    body: (
      <TipBody
        why="파트너 홈페이지·블로그·워드프레스에 상담 신청 폼(또는 전화 버튼)을 넣는 기능입니다. 접수된 DB는 이 플랫폼으로 바로 들어옵니다."
        ifSkip="위젯 없이 홍보 링크만 쓰면, 고객을 플랫폼 랜딩으로 보내야 합니다. 위젯은 외부 사이트에 머문 채 상담을 받을 때 씁니다."
        tip="처음에는 HTML 폼형으로 설치한 뒤, 테스트 접수 1건이 「외부위젯」으로 보이는지 확인하세요."
      />
    ),
  },
  modes: {
    title: '위젯 형태 (폼 / 버튼 / 전화)',
    body: (
      <TipBody
        why="폼형: 페이지에 상담 양식이 바로 보입니다. 버튼형: 「상담 신청」 버튼을 누르면 모달 폼이 뜹니다. 전화형: 배정된 안심번호로 전화만 겁니다."
        ifSkip="전화형은 콜디비 안심번호가 배정된 캠페인에서만 의미가 있습니다. 번호가 없으면 전화 버튼이 숨겨질 수 있습니다."
        tip="일반 홈페이지는 폼형, 공간이 좁으면 버튼형을 권장합니다."
      />
    ),
  },
  widgetKey: {
    title: '위젯 키',
    body: (
      <TipBody
        why="설치 코드에 넣는 비밀 키입니다. 다른 사람이 홍보코드만 복사해 자기 사이트에 붙이는 것을 줄여 줍니다."
        ifSkip="키를 발급하지 않으면 예전처럼 홍보코드만으로도 동작합니다. 「위젯 키 필수」를 켜면 키가 없는 설치는 바로 멈춥니다."
        tip="사이트에 설치한 뒤 키를 발급하고, 가능하면 「키 필수」를 켜 두세요. 재발급하면 기존 코드는 즉시 무효입니다."
      />
    ),
  },
  domains: {
    title: '허용 도메인',
    body: (
      <TipBody
        why="위젯이 동작해도 되는 사이트 주소를 등록합니다. 예: example.com (www 없이도 보통 같이 인식됩니다)."
        ifSkip="비워 두면 모든 사이트에서 위젯이 동작합니다. 무단 설치가 걱정되면 반드시 등록하세요."
        tip="실제 설치할 도메인만 넣고 저장한 뒤, 미리보기로 접수가 되는지 확인하세요."
      />
    ),
  },
  design: {
    title: '디자인 템플릿 · 문구 · 필드',
    body: (
      <TipBody
        why="6가지 템플릿(기본/심플/카드/강조/소프트/다크)과 강조색·문구·필드를 고르면 우측에서 바로 미리볼 수 있습니다."
        ifSkip="설정을 저장해야 실제 설치 코드·서버 위젯에 반영됩니다. 완료 후 URL은 허용 도메인과 같아야 합니다."
        tip="업종에 맞는 템플릿을 고른 뒤 강조색만 브랜드 컬러로 맞추면 빠르게 완성됩니다."
      />
    ),
  },
  wordpress: {
    title: '워드프레스 플러그인',
    body: (
      <TipBody
        why="HTML을 직접 넣기 어려울 때 zip 플러그인을 설치하고, 설정에 홍보코드(·위젯 키)를 넣은 뒤 숏코드/블록으로 페이지에 붙입니다."
        tip="테마·캐시 플러그인이 iframe을 막지 않는지 확인하세요. GTM 전환은 dataLayer 이벤트로 잡을 수 있습니다."
      />
    ),
  },
  sourceFilter: {
    title: 'DB 출처가 뭔가요?',
    body: (
      <>
        <p>접수된 상담 DB가 어디서 들어왔는지 구분하는 필터입니다.</p>
        <ul className="space-y-2.5">
          <li className="rounded-xl border border-cyan-100 bg-cyan-50/70 px-3 py-2.5">
            <div className="text-xs font-bold text-cyan-900 mb-0.5">외부위젯</div>
            <p className="text-xs text-cyan-950/90 leading-relaxed m-0">
              파트너 홈페이지·블로그·워드프레스에 심은 상담 위젯으로 접수된 DB입니다. 설치 페이지 주소·UTM이 함께 남을 수 있습니다.
            </p>
          </li>
          <li className="rounded-xl border border-violet-100 bg-violet-50/70 px-3 py-2.5">
            <div className="text-xs font-bold text-violet-900 mb-0.5">콜디비</div>
            <p className="text-xs text-violet-950/90 leading-relaxed m-0">
              고객이 안심번호(추적 전화)로 통화했을 때 쌓이는 DB입니다. 폼 작성 없이 전화 상담으로 유입된 경우입니다.
            </p>
          </li>
          <li className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
            <div className="text-xs font-bold text-slate-800 mb-0.5">폼/링크</div>
            <p className="text-xs text-slate-600 leading-relaxed m-0">
              플랫폼 랜딩·홍보 링크·기존 상담 폼 등, 위젯·콜이 아닌 일반 경로로 접수된 DB입니다.
            </p>
          </li>
        </ul>
        <p className="rounded-xl bg-cyan-50 border border-cyan-100 px-3 py-2 text-cyan-950 text-xs font-medium">
          추천: 파트너 사이트 위젯 성과만 보려면 「외부위젯」을 고르세요. 전화 유입만 보려면 「콜디비」를 고르세요.
        </p>
      </>
    ),
  },
};
