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
