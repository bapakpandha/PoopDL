<?php

class GetVidSrcPlayerUrl
{
    public function __construct() {}

    public function process($post_id, $url, $metrolagu_url)
    {
        if (!$post_id || !preg_match('/^[a-z0-9]+$/i', $post_id)) {
            return [
                'status' => 'error',
                'message' => 'metrolagu_post_id tidak valid',
                'data' => [
                    'post_id' => $post_id
                ],
                'step' => 3
            ];
        }

        $result = $this->getWatchPage($post_id, $url, $metrolagu_url);
        return $result;
    }

    private function getWatchPage($post_id, $RefererUrl, $metrolagu_url)
    {
        $url = $metrolagu_url . $post_id;
        // $postFields = http_build_query(['poop' => $post_id]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_POST => true,
            // CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Referer: {$RefererUrl}",
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
                'sec-fetch-dest: iframe',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: same-origin'
            ],
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true
        ]);

        $html = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);

        if ($httpcode >= 400 || !$html) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses halaman https://www.metrolagu.cam/watch',
                'data' => [
                    'post_id' => $post_id,
                    'http_code' => $httpcode,
                    'html' => $html,
                    'requestheaders' => $requestHeaders
                ],
                'step' => 3
            ];
        }

        $result = $this->parseWatchPage($html, $post_id);
        return $result;
    }

    private function parseWatchPage($html, $post_id)
    {
        // Pola untuk mendapatkan videoId dan fullURL
        preg_match("/var\s+videoId\s*=\s*'([a-zA-Z0-9]+)'/", $html, $m1);
        preg_match('/baseURL\s*=\s*"(https?:\/\/[^"]+)"/', $html, $m2);
        preg_match("/playerPath\s*=\s*'([^']+)'/", $html, $m3);

        if ($m1 && $m2 && $m3) {
            $videoId = $m1[1];
            $baseURL = $m2[1];
            $playerPath = $m3[1];
            $fullURL = $baseURL . $playerPath;

            return [
                'status' => 'success',
                'message' => 'Mendapatkan URL video dari http://www.metrolagu.cam/...',
                'step' => 3,
                'data' => [
                    'video_id' => $videoId,
                    'baseURL' => $baseURL,
                    'playerPath' => $playerPath,
                    'fullURL' => $fullURL
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Gagal mendapatkan fullURL dari halaman http://www.metrolagu.cam/watch. Preg_match tidak cocok',
                'data' => [
                    'post_id' => $post_id,
                    'html' => $html,
                    'hasil_deteksi_preg_match' => [
                        'video_id' => $m1,
                        'base_url' => $m2,
                        'player_path' => $m3,
                    ]
                ],
                'step' => 3
            ];
        }
    }
}
