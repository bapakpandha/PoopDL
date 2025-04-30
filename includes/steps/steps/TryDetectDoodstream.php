<?php

class TryDetectDoodstream
{
    private $fullURL = "";

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

        $this->fullURL = $fullURL;

        $html = $this->curlToVideoSrc($fullURL);
        if (!$html['status']) {
            return $html['result_message'];
        }
        $detectIframe = $this->detectIframeInHtml($html['result_message']['data']);
        if ($detectIframe['status']) {
            $html = $this->curlToVideoSrc($detectIframe['data']['iframesrc']);
            $iframeSrc = $detectIframe['data']['iframesrc'];
            $detectDood = $this->detectDoodstreamInHtml($html['result_message']['data']);
            $detectDood['data']['fullUrl'] = $iframeSrc;
            $detectDood['data']['url'] = $iframeSrc;
            $detectDood['data']['iframe_src'] = $iframeSrc;
            return $detectDood;
        }
        $detectDood = $this->detectDoodstreamInHtml($html['result_message']['data']);
        $detectDood['data']['fullUrl'] = $fullURL;
        $detectDood['data']['url'] = $fullURL;
        $detectDood['data']['iframe_src'] = $fullURL;
        return $detectDood;
    }

    private function curlToVideoSrc($URL)
    {
        $ch = curl_init($URL);
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
                'status' => false,
                'result_message' => [
                    'status' => 'error',
                    'message' => 'Gagal mengakses fullURL',
                    'data' => ['curl_to' => $URL, 'http_code' => $httpcode],
                    'step' => 5
                ],
            ];
        }

        return [
            'status' => true,
            'result_message' => [
                'data' => $html,
            ],
        ];
    }

    private function detectIframeInHtml($html)
    {
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            $iframeSrc = $matches[1];
            $parsedBase = parse_url($this->fullURL);
            $scheme = $parsedBase['scheme'] ?? 'https';
            $host = $parsedBase['host'] ?? '';
            if (strpos($iframeSrc, '/') === 0) {
                $iframeSrc = $scheme . '://' . $host . $iframeSrc;
            }
            return [
                'status' => true,
                'data' => [
                    'iframesrc' => $iframeSrc,
                ],
            ];
        }

        return [
            'status' => false,
            'data' => null,
        ];
    }

    private function detectDoodstreamInHtml($html)
    {
        // cek apakah ada string "doodstream" didalamnya
        if (stripos($html, 'doodstream') !== false) {
            return [
                'status' => 'success',
                'message' => 'URL Doodstream terdeteksi.',
                'step' => 5,
                'data' => [
                    'html' => $html
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Doodstream tidak terdeteksi. Membatalkan scraping',
                'step' => 5,
                'data' => [
                    'html' => $html
                ]
            ];
        }
    }
}
