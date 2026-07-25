const fs = require('fs');
const content = fs.readFileSync('src/components/admin/AdminShared.tsx', 'utf8');

const newBadge = `export function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    '신규접수': 'bg-slate-100 text-slate-600 border-slate-200',
    '확인중': 'bg-blue-50 text-blue-600 border-blue-200',
    '승인완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '취소요청': 'bg-orange-50 text-orange-600 border-orange-200',
    '취소/무효': 'bg-red-50 text-red-600 border-red-200',
    '보류': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '확정완료': 'bg-purple-50 text-purple-600 border-purple-200',
    '정산완료': 'bg-slate-900 text-white border-slate-800',
    'API 오류': 'bg-red-50 text-red-600 border-red-200',
    'API오류': 'bg-red-50 text-red-600 border-red-200',
    '정상': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '광고비 부족': 'bg-red-50 text-red-600 border-red-200',
    '광고비부족': 'bg-red-50 text-red-600 border-red-200',
    '충전완료': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '차감완료': 'bg-slate-800 text-slate-100 border-slate-900',
    '환급완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '진행중': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '일시중지': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '종료': 'bg-slate-100 text-slate-600 border-slate-200',
    '운영중': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '차단': 'bg-red-50 text-red-600 border-red-200',
    '지연': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '검수중': 'bg-blue-50 text-blue-600 border-blue-200',
    '승인대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '승인반려': 'bg-red-50 text-red-600 border-red-200',
    '가입대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '출금완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '답변대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '답변완료': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '경고': 'bg-orange-50 text-orange-600 border-orange-200',
    '정지': 'bg-red-50 text-red-600 border-red-200',
    '검수대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '취소승인': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '취소반려': 'bg-red-50 text-red-600 border-red-200',
    '이의신청중': 'bg-orange-50 text-orange-600 border-orange-200',
    '재확인요청': 'bg-blue-50 text-blue-600 border-blue-200',
    '검수완료': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '검수반려': 'bg-red-50 text-red-600 border-red-200',
    '충전대기': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '환급대기': 'bg-orange-50 text-orange-600 border-orange-200',
    '사용중지': 'bg-slate-100 text-slate-500 border-slate-200',
    '충전': 'bg-cyan-50 text-cyan-600 border-cyan-200',
    '가차감': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '확정차감': 'bg-purple-50 text-purple-600 border-purple-200',
    '환급': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '수동조정': 'bg-slate-100 text-slate-600 border-slate-200',
    '신청완료': 'bg-slate-100 text-slate-600 border-slate-200',
    '지급완료': 'bg-slate-900 text-white border-slate-800',
    '반려': 'bg-red-50 text-red-600 border-red-200',
    '수신': 'bg-blue-50 text-blue-600 border-blue-200',
    '송신': 'bg-purple-50 text-purple-600 border-purple-200',
    '상태동기화': 'bg-slate-100 text-slate-600 border-slate-200',
    '성공': 'bg-emerald-50 text-emerald-600 border-emerald-200',
    '실패': 'bg-red-50 text-red-600 border-red-200',
    '중복': 'bg-orange-50 text-orange-600 border-orange-200',
    '인증오류': 'bg-red-50 text-red-600 border-red-200',
    '검증오류': 'bg-yellow-50 text-yellow-600 border-yellow-200',
    '접수완료': 'bg-slate-100 text-slate-600 border-slate-200',
    '처리중': 'bg-blue-50 text-blue-600 border-blue-200',
  };

  const defaultStyle = 'bg-slate-100 text-slate-600 border-slate-200';
  const currentStyle = styles[status] || defaultStyle;

  return (
    <span className={\`inline-flex items-center px-2 py-1 text-xs font-bold rounded border \${currentStyle} whitespace-nowrap\`}>
      {status}
    </span>
  );
}
`;

const updatedContent = content.replace(/export function StatusBadge.*$/s, newBadge);
fs.writeFileSync('src/components/admin/AdminShared.tsx', updatedContent);
