<?php

class TryDetectDoodstream
{
    public function __construct() {}

    public function process($fullURL)
    {
        if (!$fullURL || !filter_var($fullURL, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'fullURL tidak valid',
                'step' => 5
            ];
        }

        return $this->curlToVideoSrc($fullURL);
    }

    private function curlToVideoSrc($fullURL)
    {
        $ch = curl_init($fullURL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Referer: https://www.metrolagu.cam/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);

        $html = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400 || !$html) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses fullURL',
                'data' => null,
                'step' => 5
            ];
        }

        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            $iframeSrc = $matches[1];
            $ch = curl_init($iframeSrc);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
                ]
            ]);

            $html = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode >= 400 || !$html) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal mengakses iframe src',
                    'data' => [
                        'iframe_src' => $iframeSrc,
                        'http_code_iframe_src' => $httpcode,
                        'html' => $html
                    ],
                    'step' => 5
                ];
            }

            // cek apakah ada string "doodstream" didalamnya
            if (stripos($html, 'doodstream') !== false) {
                return [
                    'status' => 'success',
                    'message' => 'URL Doodstream terdeteksi.',
                    'step' => 5,
                    'data' => [
                        'iframe_src' => $iframeSrc,
                        'html' => $html
                    ]
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Doodstream tidak terdeteksi. Membatalkan scraping',
                    'step' => 5,
                    'data' => [
                        'iframe_src' => $iframeSrc,
                        'html' => $html
                    ]
                ];
            }
        }
    }
}
