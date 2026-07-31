<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_image_mime_to_ext')) {
    function lc_image_mime_to_ext($mime)
    {
        $mime = strtolower(trim((string) $mime));
        $map = array(
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        );

        return isset($map[$mime]) ? $map[$mime] : '';
    }
}

if (!function_exists('lc_image_aspect_ratio_label')) {
    /**
     * Gemini imageConfig용 가장 가까운 지원 비율.
     *
     * @return string
     */
    function lc_image_aspect_ratio_label($width, $height)
    {
        $width = (int) $width;
        $height = (int) $height;
        if ($width <= 0 || $height <= 0) {
            return '16:9';
        }

        $ratio = $width / $height;
        $candidates = array(
            '1:1'  => 1.0,
            '4:3'  => 4 / 3,
            '3:4'  => 3 / 4,
            '16:9' => 16 / 9,
            '9:16' => 9 / 16,
            '3:2'  => 3 / 2,
            '2:3'  => 2 / 3,
        );

        $best = '16:9';
        $best_diff = PHP_FLOAT_MAX;
        foreach ($candidates as $label => $value) {
            $diff = abs($ratio - $value);
            if ($diff < $best_diff) {
                $best_diff = $diff;
                $best = $label;
            }
        }

        return $best;
    }
}

if (!function_exists('lc_image_create_from_binary')) {
    /**
     * @return resource|\GdImage|false
     */
    function lc_image_create_from_binary($binary)
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        return @imagecreatefromstring($binary);
    }
}

if (!function_exists('lc_image_encode')) {
    /**
     * @param resource|\GdImage $image
     * @return array{ok:bool,binary?:string,mime?:string,ext?:string,message?:string}
     */
    function lc_image_encode($image, $preferred_mime = 'image/jpeg')
    {
        if (!is_resource($image) && !($image instanceof GdImage)) {
            return array('ok' => false, 'message' => '이미지 리소스가 유효하지 않습니다.');
        }

        $mime = strtolower(trim((string) $preferred_mime));
        ob_start();
        $ok = false;
        if ($mime === 'image/png' && function_exists('imagepng')) {
            $ok = imagepng($image, null, 6);
            $ext = 'png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            $ok = imagewebp($image, null, 85);
            $ext = 'webp';
        } else {
            if (function_exists('imagejpeg')) {
                $ok = imagejpeg($image, null, 88);
                $mime = 'image/jpeg';
                $ext = 'jpg';
            }
        }
        $binary = ob_get_clean();

        if (!$ok || $binary === false || $binary === '') {
            return array('ok' => false, 'message' => '이미지 인코딩에 실패했습니다.');
        }

        return array(
            'ok'     => true,
            'binary' => $binary,
            'mime'   => $mime,
            'ext'    => $ext,
        );
    }
}

if (!function_exists('lc_image_resize_cover')) {
    /**
     * cover 방식으로 정확한 픽셀 크기로 리사이즈/크롭.
     *
     * @return array{ok:bool,binary?:string,mime?:string,ext?:string,width?:int,height?:int,message?:string}
     */
    function lc_image_resize_cover($binary, $target_w, $target_h, $preferred_mime = 'image/jpeg')
    {
        $target_w = (int) $target_w;
        $target_h = (int) $target_h;
        if ($target_w <= 0 || $target_h <= 0) {
            return array('ok' => false, 'message' => '대상 크기가 올바르지 않습니다.');
        }

        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            return array('ok' => false, 'message' => '서버 GD 확장이 필요합니다.');
        }

        $src = lc_image_create_from_binary($binary);
        if ($src === false) {
            return array('ok' => false, 'message' => '이미지를 디코딩하지 못했습니다.');
        }

        $src_w = imagesx($src);
        $src_h = imagesy($src);
        if ($src_w <= 0 || $src_h <= 0) {
            imagedestroy($src);

            return array('ok' => false, 'message' => '원본 이미지 크기를 확인할 수 없습니다.');
        }

        $scale = max($target_w / $src_w, $target_h / $src_h);
        $crop_w = (int) round($target_w / $scale);
        $crop_h = (int) round($target_h / $scale);
        $src_x = (int) max(0, ($src_w - $crop_w) / 2);
        $src_y = (int) max(0, ($src_h - $crop_h) / 2);

        $dst = imagecreatetruecolor($target_w, $target_h);
        if ($dst === false) {
            imagedestroy($src);

            return array('ok' => false, 'message' => '리사이즈 캔버스를 만들지 못했습니다.');
        }

        // JPEG 배경을 흰색으로
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $target_w, $target_h, $crop_w, $crop_h);
        imagedestroy($src);

        $encoded = lc_image_encode($dst, $preferred_mime);
        imagedestroy($dst);
        if (empty($encoded['ok'])) {
            return $encoded;
        }

        return array(
            'ok'     => true,
            'binary' => $encoded['binary'],
            'mime'   => $encoded['mime'],
            'ext'    => $encoded['ext'],
            'width'  => $target_w,
            'height' => $target_h,
        );
    }
}

if (!function_exists('lc_image_encode_quality')) {
    /**
     * 품질을 명시해 인코딩 (화질 유지 + 용량 절감용).
     *
     * @param resource|\GdImage $image
     * @return array{ok:bool,binary?:string,mime?:string,ext?:string,message?:string}
     */
    function lc_image_encode_quality($image, $preferred_mime = 'image/jpeg', $quality = 88)
    {
        if (!is_resource($image) && !($image instanceof GdImage)) {
            return array('ok' => false, 'message' => '이미지 리소스가 유효하지 않습니다.');
        }

        $mime = strtolower(trim((string) $preferred_mime));
        $quality = (int) $quality;
        if ($quality < 60) {
            $quality = 60;
        }
        if ($quality > 95) {
            $quality = 95;
        }

        ob_start();
        $ok = false;
        $ext = 'jpg';
        if ($mime === 'image/png' && function_exists('imagepng')) {
            // PNG compression 0(무손실·큼)~9(작음). 6은 균형.
            $level = (int) round((100 - $quality) / 10);
            if ($level < 0) {
                $level = 0;
            }
            if ($level > 9) {
                $level = 9;
            }
            $ok = imagepng($image, null, $level);
            $ext = 'png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            $ok = imagewebp($image, null, $quality);
            $ext = 'webp';
        } elseif (function_exists('imagejpeg')) {
            $ok = imagejpeg($image, null, $quality);
            $mime = 'image/jpeg';
            $ext = 'jpg';
        }
        $binary = ob_get_clean();

        if (!$ok || $binary === false || $binary === '') {
            return array('ok' => false, 'message' => '이미지 인코딩에 실패했습니다.');
        }

        return array(
            'ok'     => true,
            'binary' => $binary,
            'mime'   => $mime,
            'ext'    => $ext,
        );
    }
}

if (!function_exists('lc_image_resize_contain')) {
    /**
     * contain: 비율 유지하며 목표 박스 안에 맞춤 (업스케일 안 함).
     *
     * @return array{ok:bool,binary?:string,mime?:string,ext?:string,width?:int,height?:int,message?:string}
     */
    function lc_image_resize_contain($binary, $max_w, $max_h, $preferred_mime = 'image/jpeg', $quality = 88)
    {
        $max_w = (int) $max_w;
        $max_h = (int) $max_h;
        if ($max_w <= 0 || $max_h <= 0) {
            return array('ok' => false, 'message' => '대상 크기가 올바르지 않습니다.');
        }

        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            return array('ok' => false, 'message' => '서버 GD 확장이 필요합니다.');
        }

        $src = lc_image_create_from_binary($binary);
        if ($src === false) {
            return array('ok' => false, 'message' => '이미지를 디코딩하지 못했습니다.');
        }

        $src_w = imagesx($src);
        $src_h = imagesy($src);
        if ($src_w <= 0 || $src_h <= 0) {
            imagedestroy($src);

            return array('ok' => false, 'message' => '원본 이미지 크기를 확인할 수 없습니다.');
        }

        $scale = min(1.0, min($max_w / $src_w, $max_h / $src_h));
        $dst_w = max(1, (int) round($src_w * $scale));
        $dst_h = max(1, (int) round($src_h * $scale));

        if ($dst_w === $src_w && $dst_h === $src_h) {
            $encoded = lc_image_encode_quality($src, $preferred_mime, $quality);
            imagedestroy($src);
            if (empty($encoded['ok'])) {
                return $encoded;
            }

            return array(
                'ok'     => true,
                'binary' => $encoded['binary'],
                'mime'   => $encoded['mime'],
                'ext'    => $encoded['ext'],
                'width'  => $src_w,
                'height' => $src_h,
            );
        }

        $dst = imagecreatetruecolor($dst_w, $dst_h);
        if ($dst === false) {
            imagedestroy($src);

            return array('ok' => false, 'message' => '리사이즈 캔버스를 만들지 못했습니다.');
        }

        $mime = strtolower(trim((string) $preferred_mime));
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        imagedestroy($src);

        $encoded = lc_image_encode_quality($dst, $preferred_mime, $quality);
        imagedestroy($dst);
        if (empty($encoded['ok'])) {
            return $encoded;
        }

        return array(
            'ok'     => true,
            'binary' => $encoded['binary'],
            'mime'   => $encoded['mime'],
            'ext'    => $encoded['ext'],
            'width'  => $dst_w,
            'height' => $dst_h,
        );
    }
}

if (!function_exists('lc_image_fit_max_edge')) {
    /**
     * 긴 변을 max_edge 이하로 축소 (업스케일 안 함).
     *
     * @return array{ok:bool,binary?:string,mime?:string,ext?:string,width?:int,height?:int,message?:string}
     */
    function lc_image_fit_max_edge($binary, $max_edge, $preferred_mime = 'image/jpeg', $quality = 88)
    {
        $max_edge = (int) $max_edge;
        if ($max_edge <= 0) {
            return array('ok' => false, 'message' => '대상 크기가 올바르지 않습니다.');
        }

        return lc_image_resize_contain($binary, $max_edge, $max_edge, $preferred_mime, $quality);
    }
}

if (!function_exists('lc_image_variant_allowed_widths')) {
    /**
     * @return int[]
     */
    function lc_image_variant_allowed_widths()
    {
        return array(160, 240, 320, 480, 640, 800, 960, 1200);
    }
}

if (!function_exists('lc_image_variant_snap_width')) {
    function lc_image_variant_snap_width($width)
    {
        $width = (int) $width;
        if ($width <= 0) {
            return 0;
        }

        $allowed = lc_image_variant_allowed_widths();
        $best = $allowed[0];
        foreach ($allowed as $w) {
            $best = $w;
            if ($width <= $w) {
                break;
            }
        }

        return (int) $best;
    }
}

if (!function_exists('lc_image_variant_cache_dir')) {
    function lc_image_variant_cache_dir()
    {
        return rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/image_cache';
    }
}

if (!function_exists('lc_image_variant_ensure_cache')) {
    function lc_image_variant_ensure_cache()
    {
        $base = lc_image_variant_cache_dir();
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }
        $htaccess = $base . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }

        return is_dir($base);
    }
}

if (!function_exists('lc_image_variant_parse_request')) {
    /**
     * GET 파라미터에서 안전한 변형 옵션 추출.
     * download=1 이면 원본 강제.
     *
     * @return array{w:int,h:int,fit:string,fmt:string,original:bool}
     */
    function lc_image_variant_parse_request(?array $query = null)
    {
        if ($query === null) {
            $query = $_GET;
        }

        if (!empty($query['download']) || (!empty($query['original']) && (string) $query['original'] === '1')) {
            return array('w' => 0, 'h' => 0, 'fit' => 'contain', 'fmt' => '', 'original' => true);
        }

        $w = isset($query['w']) ? (int) $query['w'] : (isset($query['width']) ? (int) $query['width'] : 0);
        $h = isset($query['h']) ? (int) $query['h'] : (isset($query['height']) ? (int) $query['height'] : 0);
        $fit = isset($query['fit']) ? strtolower(trim((string) $query['fit'])) : 'cover';
        if ($fit !== 'contain' && $fit !== 'cover') {
            $fit = 'cover';
        }

        $fmt = isset($query['fmt']) ? strtolower(trim((string) $query['fmt'])) : '';
        if ($fmt === 'jpg') {
            $fmt = 'jpeg';
        }
        if (!in_array($fmt, array('', 'webp', 'jpeg', 'png'), true)) {
            $fmt = '';
        }

        if ($w > 0) {
            $w = lc_image_variant_snap_width($w);
        }
        if ($h > 0) {
            // h도 허용폭 스냅 (동일 그리드)
            $h = lc_image_variant_snap_width($h);
        }

        return array(
            'w'        => $w,
            'h'        => $h,
            'fit'      => $fit,
            'fmt'      => $fmt,
            'original' => false,
        );
    }
}

if (!function_exists('lc_image_variant_resolve')) {
    /**
     * 원본 파일로부터 리사이즈/포맷 변형을 디스크 캐시에 생성해 경로 반환.
     * w/h/fmt 모두 비면 원본 그대로.
     *
     * @return array{ok:bool,file?:string,mime?:string,cached?:bool,message?:string}
     */
    function lc_image_variant_resolve($source_file, $source_mime, array $opts)
    {
        $source_file = (string) $source_file;
        if ($source_file === '' || !is_file($source_file)) {
            return array('ok' => false, 'message' => '원본 파일이 없습니다.');
        }

        $source_mime = strtolower(trim((string) $source_mime));
        if ($source_mime === '') {
            $source_mime = 'image/jpeg';
        }

        if (!empty($opts['original'])) {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        $w = isset($opts['w']) ? (int) $opts['w'] : 0;
        $h = isset($opts['h']) ? (int) $opts['h'] : 0;
        $fit = isset($opts['fit']) ? (string) $opts['fit'] : 'cover';
        $fmt = isset($opts['fmt']) ? (string) $opts['fmt'] : '';

        // 변형 요청이 없으면 원본
        if ($w <= 0 && $h <= 0 && $fmt === '') {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        if (!function_exists('imagecreatefromstring')) {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        if ($fmt === 'webp' && !function_exists('imagewebp')) {
            $fmt = 'jpeg';
        }

        $preferred_mime = $source_mime;
        if ($fmt === 'webp') {
            $preferred_mime = 'image/webp';
        } elseif ($fmt === 'jpeg') {
            $preferred_mime = 'image/jpeg';
        } elseif ($fmt === 'png') {
            $preferred_mime = 'image/png';
        }

        $ext = lc_image_mime_to_ext($preferred_mime);
        if ($ext === '') {
            $ext = 'jpg';
            $preferred_mime = 'image/jpeg';
        }

        if (!lc_image_variant_ensure_cache()) {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        $mtime = (int) @filemtime($source_file);
        $size = (int) @filesize($source_file);
        $key = hash('sha256', $source_file . '|' . $mtime . '|' . $size . '|' . $w . '|' . $h . '|' . $fit . '|' . $preferred_mime);
        $cache_file = lc_image_variant_cache_dir() . '/' . substr($key, 0, 2) . '/' . $key . '.' . $ext;

        if (is_file($cache_file) && filesize($cache_file) > 0) {
            return array('ok' => true, 'file' => $cache_file, 'mime' => $preferred_mime, 'cached' => true);
        }

        $binary = @file_get_contents($source_file);
        if ($binary === false || $binary === '') {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        // 크기만 포맷 변환 / 리사이즈
        $quality = 88;
        if ($preferred_mime === 'image/webp') {
            $quality = 85;
        }

        if ($w > 0 || $h > 0) {
            $tw = $w > 0 ? $w : $h;
            $th = $h > 0 ? $h : $w;
            if ($fit === 'contain') {
                $resized = lc_image_resize_contain($binary, $tw, $th, $preferred_mime, $quality);
            } else {
                // cover는 기존 helper 사용 후 품질 재인코딩이 없으므로 contain으로 긴변 맞춘 뒤
                // 정확한 cover가 필요하면 resize_cover 사용
                if ($w > 0 && $h > 0) {
                    $resized = lc_image_resize_cover($binary, $w, $h, $preferred_mime);
                } else {
                    $resized = lc_image_resize_contain($binary, $tw, $th, $preferred_mime, $quality);
                }
            }
        } else {
            // 포맷만 변환
            $resized = lc_image_resize_contain($binary, 10000, 10000, $preferred_mime, $quality);
        }

        if (empty($resized['ok']) || empty($resized['binary'])) {
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }

        $dir = dirname($cache_file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (@file_put_contents($cache_file, $resized['binary'], LOCK_EX) === false) {
            // 캐시 실패 시에도 원본 제공
            return array('ok' => true, 'file' => $source_file, 'mime' => $source_mime, 'cached' => false);
        }
        @chmod($cache_file, 0644);

        return array(
            'ok'     => true,
            'file'   => $cache_file,
            'mime'   => isset($resized['mime']) ? (string) $resized['mime'] : $preferred_mime,
            'cached' => true,
        );
    }
}

if (!function_exists('lc_image_output_resolved')) {
    /**
     * 변형 해석 후 헤더+본문 출력.
     */
    function lc_image_output_resolved($source_file, $source_mime, $cache_control = 'public, max-age=86400', ?array $opts = null)
    {
        if ($opts === null) {
            $opts = lc_image_variant_parse_request();
        }

        $resolved = lc_image_variant_resolve($source_file, $source_mime, $opts);
        if (empty($resolved['ok']) || empty($resolved['file'])) {
            return false;
        }

        $file = (string) $resolved['file'];
        $mime = (string) ($resolved['mime'] ?? $source_mime);
        $length = @filesize($file);

        if (!headers_sent()) {
            header('Content-Type: ' . $mime);
            if ($length !== false) {
                header('Content-Length: ' . (string) $length);
            }
            header('Cache-Control: ' . $cache_control);
            header('X-Content-Type-Options: nosniff');
            if (!empty($resolved['cached'])) {
                header('X-LC-Image-Cache: HIT');
            } else {
                header('X-LC-Image-Cache: MISS');
            }
        }

        readfile($file);

        return true;
    }
}
