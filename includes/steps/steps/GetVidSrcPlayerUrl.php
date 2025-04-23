<?php

class GetVidSrcPlayerUrl
{
    public function __construct()
    {
    }

    public function process($post_id, $url)
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

        $result = $this->getWatchPage($post_id, $url);
        return $result;
    }

    private function getWatchPage($post_id, $RefererUrl)
    {
        $url = "https://www.metrolagu.cam/watch";
        $postFields = http_build_query(['poop' => $post_id]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Referer: {$RefererUrl}",
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);

        $html = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400 || !$html) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses halaman https://www.metrolagu.cam/watch',
                'data' => [
                    'post_id' => $post_id,
                    'http_code' => $httpcode,
                    'html' => $html
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
        $pattern = '/var videoId = \'([a-z0-9]+)\';.*?baseURL = "(https?:\/\/[^"]+)";.*?playerPath = \'([^\']+)\';.*?fullURL = baseURL \+ playerPath;/s';

        if (preg_match($pattern, $html, $m)) {
            $videoId = $m[1];
            $baseURL = $m[2];
            $playerPath = $m[3];
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
                'message' => 'Gagal mendapatkan fullURL dari halaman http://www.metrolagu.cam/watch',
                'data' => [
                    'post_id' => $post_id,
                    'html' => $html
                ],
                'step' => 3
            ];
        }
    }
}
