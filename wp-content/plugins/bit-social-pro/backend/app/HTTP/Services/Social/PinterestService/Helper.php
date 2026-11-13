<?php

namespace BitApps\SocialPro\HTTP\Services\Social\PinterestService;

use BitApps\Social\Utils\Hash;

class Helper
{
    public static function organizeToken($client_id, $client_secret, $tokenInfo)
    {
        return [
            'client_id'                => Hash::encrypt($client_id),
            'client_secret'            => Hash::encrypt($client_secret),
            'access_token'             => Hash::encrypt($tokenInfo->access_token),
            'expires_in'               => time() + $tokenInfo->expires_in,
            'refresh_token'            => Hash::encrypt($tokenInfo->refresh_token),
            'refresh_token_expires_in' => time() + $tokenInfo->refresh_token_expires_in,
        ];
    }

    public static function mimeContentType($filename)
    {
        if (\function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mimetype = @finfo_file($finfo, $filename);
                finfo_close($finfo);

                if (! empty($mimetype)) {
                    return $mimetype;
                }
            }
        }

        if (\function_exists('mime_content_type')) {
            $mimeType = @mime_content_type($filename);

            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        $mimeContentTypes = [
            'webp' => 'image/webp',
            'png'  => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpe'  => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',

            'webm' => 'video/webm',
            'mp4'  => 'video/mp4',
            'qt'   => 'video/quicktime',
            'mov'  => 'video/quicktime',
            'flv'  => 'video/x-flv',
        ];

        $mime = explode('.', $filename);

        if (empty($mime)) {
            return 'application/octet-stream';
        }

        $mime = strtolower(array_pop($mime));

        foreach ($mimeContentTypes as $ext => $contentType) {
            if (strpos($mime, $ext) === 0) {
                return $contentType;
            }
        }

        return 'application/octet-stream';
    }

    public static function webpToJpg($file)
    {
        if (! \function_exists('imagecreatefromwebp') || ! \function_exists('imagejpeg')) {
            return false;
        }

        $jpg = imagecreatefromwebp($file);

        if ($jpg === false) {
            return false;
        }

        ob_clean();
        ob_start();
        $res = imagejpeg($jpg);
        $image = ob_get_clean();

        if ($res === false) {
            return false;
        }

        return $image;
    }

    public static function cutText($text, $n = 35)
    {
        return mb_strlen($text, 'UTF-8') > $n ? mb_substr($text, 0, $n, 'UTF-8') . '...' : $text;
    }
}
