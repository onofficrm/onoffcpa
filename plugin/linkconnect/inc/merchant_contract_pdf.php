<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_contract_tcpdf_path')) {
    function lc_merchant_contract_tcpdf_path()
    {
        return LC_PLUGIN_PATH . '/lib/tcpdf/tcpdf.php';
    }
}

if (!function_exists('lc_merchant_contract_pdf_available')) {
    function lc_merchant_contract_pdf_available()
    {
        return is_file(lc_merchant_contract_tcpdf_path());
    }
}

if (!function_exists('lc_merchant_contract_template_pdf_path')) {
    function lc_merchant_contract_template_pdf_path()
    {
        $candidates = array(
            G5_PATH . '/assets/contracts/CPA_merchant_agreement_bemypiece.pdf',
            G5_PATH . '/public/assets/contracts/CPA_merchant_agreement_bemypiece.pdf',
            LC_PLUGIN_PATH . '/assets/contracts/CPA_merchant_agreement_bemypiece.pdf',
        );

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }
}

if (!function_exists('lc_merchant_contract_generate_signed_pdf')) {
    /**
     * @param array<string,mixed> $data
     * @return array{ok:bool,message:string,path:string,absolute:string,hash:string}
     */
    function lc_merchant_contract_generate_signed_pdf(array $data)
    {
        if (!lc_merchant_contract_pdf_available()) {
            return array(
                'ok'       => false,
                'message'  => 'PDF 생성 라이브러리(TCPDF)가 설치되지 않았습니다.',
                'path'     => '',
                'absolute' => '',
                'hash'     => '',
            );
        }

        require_once lc_merchant_contract_tcpdf_path();

        $mt_id = (int) ($data['mt_id'] ?? 0);
        $version = (string) ($data['contract_version'] ?? lc_merchant_contract_current_version());
        $version_dir = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $version);
        $dir = lc_merchant_contract_signed_pdf_dir($mt_id, $version_dir);
        if ($dir === '') {
            return array('ok' => false, 'message' => 'PDF 저장 경로를 준비하지 못했습니다.', 'path' => '', 'absolute' => '', 'hash' => '');
        }

        $filename = lc_merchant_contract_signed_pdf_filename($mt_id);
        if (!empty($data['unique_filename'])) {
            $filename = 'CPA_CONTRACT_' . $mt_id . '_' . date('YmdHis') . '.pdf';
        }
        $absolute = $dir . '/' . $filename;
        if (is_file($absolute)) {
            if (!empty($data['overwrite'])) {
                @unlink($absolute);
            } else {
                return array('ok' => false, 'message' => '동일한 PDF 파일이 이미 존재합니다.', 'path' => '', 'absolute' => '', 'hash' => '');
            }
        }

        $party_b = lc_merchant_contract_party_b();
        $contract_code = (string) ($data['contract_code'] ?? '');
        $signed_at = (string) ($data['signed_at'] ?? date('Y-m-d H:i:s'));
        $signed_ip = (string) ($data['signed_ip'] ?? '');
        $signer_name = (string) ($data['signer_name'] ?? '');
        $signer_position = (string) ($data['signer_position'] ?? '');
        $signer_phone = (string) ($data['signer_phone'] ?? '');
        $signer_email = (string) ($data['signer_email'] ?? '');
        $contract_html = (string) ($data['contract_html'] ?? '');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('LinkConnect');
        $pdf->SetAuthor('LinkConnect');
        $pdf->SetTitle('CPA 광고 제휴 계약서');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('cid0kr', '', 10);

        $html = '<h1 style="text-align:center;font-size:16px;">CPA 광고 제휴 계약서</h1>';
        $html .= '<p style="text-align:center;color:#555;">계약서 버전: ' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p><strong>계약번호:</strong> ' . htmlspecialchars($contract_code, ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p><strong>계약 체결일시:</strong> ' . htmlspecialchars($signed_at, ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p><strong>접속 IP:</strong> ' . htmlspecialchars($signed_ip, ENT_QUOTES, 'UTF-8') . '</p>';

        $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%"><tr>';
        $html .= '<td width="50%"><strong>갑 (광고주)</strong><br>';
        $html .= '회사명: ' . htmlspecialchars((string) ($data['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '대표자: ' . htmlspecialchars((string) ($data['representative_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '사업자등록번호: ' . htmlspecialchars((string) ($data['business_number'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '주소: ' . htmlspecialchars((string) ($data['company_address'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '연락처: ' . htmlspecialchars((string) ($data['company_phone'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td width="50%"><strong>을 (운영사)</strong><br>';
        $html .= '회사명: ' . htmlspecialchars((string) ($party_b['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '대표자: ' . htmlspecialchars((string) ($party_b['representative_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '사업자등록번호: ' . htmlspecialchars((string) ($party_b['business_number'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '주소: ' . htmlspecialchars((string) ($party_b['company_address'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '연락처: ' . htmlspecialchars((string) ($party_b['company_phone'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '</tr></table>';

        $html .= '<h3>계약 담당자</h3>';
        $html .= '<p>이름: ' . htmlspecialchars($signer_name, ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '직책: ' . htmlspecialchars($signer_position, ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '연락처: ' . htmlspecialchars($signer_phone, ENT_QUOTES, 'UTF-8') . '<br>';
        $html .= '이메일: ' . htmlspecialchars($signer_email, ENT_QUOTES, 'UTF-8') . '</p>';

        $html .= '<h3>계약 조항</h3>';
        $html .= $contract_html;

        $html .= '<h3>전자계약 동의</h3>';
        $html .= '<p>본인은 전자적 방식으로 본 계약을 체결하는 것에 동의하며, 계약 체결 후 계약서 내용을 임의로 변경할 수 없음을 확인합니다.</p>';

        $signature_path = (string) ($data['signature_absolute'] ?? '');
        if ($signature_path !== '' && is_file($signature_path)) {
            $html .= '<h3>광고주 전자서명</h3>';
            $img = '@' . base64_encode((string) file_get_contents($signature_path));
            $html .= '<img src="data:image/png;base64,' . base64_encode((string) file_get_contents($signature_path)) . '" style="width:180px;height:auto;" />';
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        $written = $pdf->Output($absolute, 'F');
        if ($written === false || !is_file($absolute)) {
            return array('ok' => false, 'message' => 'PDF 파일 생성에 실패했습니다.', 'path' => '', 'absolute' => '', 'hash' => '');
        }

        @chmod($absolute, 0640);
        $hash = hash_file('sha256', $absolute);
        $relative = lc_merchant_contract_relative_storage_path($absolute);

        return array(
            'ok'       => true,
            'message'  => 'PDF가 생성되었습니다.',
            'path'     => $relative,
            'absolute' => $absolute,
            'hash'     => $hash,
        );
    }
}
