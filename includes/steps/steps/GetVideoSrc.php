<?php

class GetVideoSrc
{
    public function __construct() {}

    public function process($fullURL, $baseURL)
    {
        if (!$fullURL || !filter_var($fullURL, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'fullURL tidak valid',
                'step' => 4
            ];
            return;
        }

        return $this->curlToVideoSrc($fullURL, $baseURL);
    }

    public function curlToVideoSrc($fullURL, $baseURL)
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
                'step' => 4
            ];
        }

        // $pattern = '/player\(".*?",\s*".*?",\s*".*?",\s*"(.*?)"\)/s';
        $pattern = '/player\("([^"]*)",\s*"([^"]*)",\s*"([^"]*)",\s*"([^"]*)"\)/s';
        if (preg_match($pattern, $html, $m)) {
            $videoURL = trim($m[4]);
            if (str_starts_with($videoURL, '/')) {
                $videoURL = rtrim($baseURL, '/') . $videoURL;
            }
            // $videoURL = $this->encodeUrlParams($videoURL);
            $videoURL = $this->smartEncodeUrl($videoURL);
            return [
                'status' => 'success',
                'message' => 'Video URL ditemukan.',
                'step' => 4,
                'data' => [
                    'video_src' => $videoURL,
                    'html' => $html,
                ]
            ];
        } else {
            return [
                'status' => 'retry',
                'message' => 'Gagal mendapatkan video_src dari fullURL',
                'data' => [
                    'video_src' => null,
                    'html' => $html,
                ],  
                'step' => 4
            ];
        }
    }

    private function encodeUrlParams($url)
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        parse_str($query, $params);
        $encodedParams = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        $base = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        return $base . '?' . $encodedParams;
    }

    private function smartEncodeUrl($url)
    {
        $parts = parse_url($url);
    
        $encodedUrl = $parts['scheme'] . '://' . $parts['host'];
    
        if (isset($parts['port'])) {
            $encodedUrl .= ':' . $parts['port'];
        }
    
        if (isset($parts['path'])) {
            // Pisahkan berdasarkan slash dan encode setiap segmen path
            $segments = explode('/', $parts['path']);
            $encodedSegments = array_map('rawurlencode', $segments);
            $encodedUrl .= implode('/', $encodedSegments);
        }
    
        if (isset($parts['query'])) {
            // Pisah query param, encode valuenya saja
            $queryParts = explode('&', $parts['query']);
            $encodedQueryParts = [];
    
            foreach ($queryParts as $q) {
                if (strpos($q, '=') !== false) {
                    [$key, $val] = explode('=', $q, 2);
                    $encodedQueryParts[] = $key . '=' . rawurlencode($val);
                } else {
                    $encodedQueryParts[] = rawurlencode($q);
                }
            }
    
            $encodedUrl .= '?' . implode('&', $encodedQueryParts);
        }
    
        return $encodedUrl;
    }
    
}
