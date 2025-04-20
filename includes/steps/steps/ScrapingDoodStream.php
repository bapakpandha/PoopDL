<?php

class ScrapingDoodStream
{
    public function __construct() {}

    public function process($doodstreamURL)
    {
        if (!$doodstreamURL || !filter_var($doodstreamURL, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'fullURL tidak valid',
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                ],
                'step' => 6
            ];
        }

        return $this->curlToVideoSrc($doodstreamURL);
    }

    private function curlToVideoSrc($doodstreamURL)
    {
        if (preg_match('#https?://([^/]+)/(e|d)/([a-zA-Z0-9_-]+)#', $doodstreamURL, $matches)) {
            $domain = $matches[1];
            $type = $matches[2];
            $videoId = $matches[3];
        } else {
            return [
                'status' => 'error',
                'message' => 'Tidak bisa menemukan ID video dari URL Doodstream',
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                ],
                'step' => 6
            ];
        }

        $baseDoodstreamUrl = 'https://doodstream.com/';
        $ch = curl_init($baseDoodstreamUrl . $type . '/' . $videoId);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Referer: https://{$domain}/",
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400 || !$response) {
            return [
                'status' => 'error',
                'message' => 'Gagal melakukan curl ke Doodstream.com',
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                    'hasil_curl_doodstream' => $response,
                ],
                'step' => 6
            ];
        }

        if (preg_match('#/pass_md5/[^"\']+#', $response, $matches_pass_md5)) {
            $match_dari_preg_match_pass_md5 = $matches_pass_md5[0];
        } else {
            return [
                'status' => 'error',
                'message' => 'Gagal mendapatkan pass_md5 dari Doodstream',
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                    'hasil_curl_doodstream' => $response,
                ],
                'step' => 6
            ];
        }

        if (preg_match('#\?token=([a-z0-9]+)&expiry=#i', $response, $matches_token)) {
            $token = $matches_token[1];
            $doodstream_params_url = '?token=' . $token . '&expiry=';
        } else {
            return [
            'status' => 'error',
                'message' => 'Gagal mendapatkan token dari Doodstream',
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                    'hasil_curl_doodstream' => $response,
                ],
                'step' => 6
            ];
        }

        $ch = curl_init($domain . $match_dari_preg_match_pass_md5);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Referer: {$doodstreamURL}",
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400 || !$response) {
            return [
                'status' => 'error',
                'message' => `Gagal melakukan curl {$domain}{$match_dari_preg_match_pass_md5}`,
                'data' => [
                    'url_doodstream' => $doodstreamURL,
                    'hasil_curl_pass_md5' => $response,
                    'pass_md5_url' => $domain . $match_dari_preg_match_pass_md5,
                    'doodstream_params_url' => $doodstream_params_url,
                ],
                'step' => 6
            ];
        }

        $videoSrcUrlDoodstream = $response . $this->cloneMakePlay($token);

        return [
            'status' => 'success',
            'message' => 'Berhasil mendapatkan video dari Doodstream',
            'data' => [
                'url_doodstream' => $doodstreamURL,
                'doodstream_video_id' => $videoId,
                'pass_md5_url' => $domain . $match_dari_preg_match_pass_md5,
                'doodstream_params_url' => $doodstream_params_url,
                'hasil_curl_pass_md5' => $response,
                'final_url' => $videoSrcUrlDoodstream,
                'video_src' => $videoSrcUrlDoodstream,
            ],
            'step' => 6
        ];
    }

    private function cloneMakePlay($token) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $random = '';
        for ($i = 0; $i < 10; $i++) {
            $random .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $random . "?token={$token}&expiry=" . round(microtime(true) * 1000); // milidetik
    }
}
