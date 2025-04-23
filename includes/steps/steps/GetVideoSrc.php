<?php

class GetVideoSrc
{
    public function __construct() {}

    public function process($fullURL, $baseURL, $refererURL = null)
    {
        if (!$fullURL || !filter_var($fullURL, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'fullURL tidak valid',
                'step' => 4
            ];
            return;
        }

        return $this->curlToVideoSrc($fullURL, $baseURL, $refererURL);
    }
    public function curlToVideoSrc($fullURL, $baseURL, $refererURL)
    {
        $ch = curl_init($fullURL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Referer: {$refererURL}",
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);
        $html = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400 || !$html) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses fullURL saat mengakses fullURL',
                'data' => [
                    'fullURL' => $fullURL,
                    'http_code' => $httpcode,
                ],
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
            $videoURLSmartEncoded = $this->smartEncodeUrl($videoURL);
            $finalVideoURL = $this->checkFinalSrcUrl($videoURL, $fullURL);
            return [
                'status' => 'success',
                'message' => 'Video URL ditemukan.',
                'step' => 4,
                'data' => [
                    'video_src' => $finalVideoURL['url'],
                    'video_src_http_code' => $finalVideoURL['http_code'],
                    "header_response" => $finalVideoURL['header_response'],
                    'data_final' => $finalVideoURL,
                    'html' => $html,
                    'videoUrlBefore' => $videoURL,
                    'videoUrlSmartEncoded' => $videoURLSmartEncoded,
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
        // Pisahkan dulu bagian fragment (jika ada)
        $fragment = '';
        if (strpos($url, '#') !== false) {
            [$url, $fragment] = explode('#', $url, 2);
        }
    
        // Pisahkan query string (jika ada)
        $query = '';
        if (strpos($url, '?') !== false) {
            [$url, $query] = explode('?', $url, 2);
        }
    
        // Pisahkan scheme dan host
        if (preg_match('/^(https?:\/\/[^\/]+)(\/.*)?$/', $url, $m)) {
            $base = $m[1];
            $path = isset($m[2]) ? $m[2] : '';
        } else {
            return $url; // fallback, invalid URL
        }
    
        // Encode setiap segmen path
        $segments = explode('/', $path);
        $encodedSegments = array_map('rawurlencode', $segments);
        $encodedPath = implode('/', $encodedSegments);
    
        // Rebuild URL
        $encodedUrl = $base . $encodedPath;
    
        if ($query) {
            $encodedUrl .= '?' . $query; // biarkan query apa adanya atau bisa di-parse & encode valuenya
        }
    
        if ($fragment) {
            $encodedUrl .= '#' . rawurlencode($fragment);
        }
    
        return $encodedUrl;
    }

    private function sanitizeUrl($url) 
    {
        $parts = parse_url($url);

        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return false; // URL tidak valid
        }
    
        $sanitized = $parts['scheme'] . '://' . $parts['host'];
    
        if (isset($parts['port'])) {
            $sanitized .= ':' . $parts['port'];
        }
    
        if (isset($parts['path'])) {
            // Encode setiap segmen path
            $segments = explode('/', $parts['path']);
            $encodedSegments = array_map('rawurlencode', $segments);
            $sanitized .= implode('/', $encodedSegments);
        }
    
        if (isset($parts['query'])) {
            // Pisahkan query param, encode *value* saja
            $queryParts = explode('&', $parts['query']);
            $encodedQueryParts = [];
    
            foreach ($queryParts as $q) {
                if (strpos($q, '=') !== false) {
                    [$key, $val] = explode('=', $q, 2);
                    $encodedQueryParts[] = rawurlencode($key) . '=' . rawurlencode($val);
                } else {
                    $encodedQueryParts[] = rawurlencode($q);
                }
            }
    
            $sanitized .= '?' . implode('&', $encodedQueryParts);
        }
    
        if (isset($parts['fragment'])) {
            $sanitized .= '#' . rawurlencode($parts['fragment']);
        }
    
        return $sanitized;
    }

    private function checkFinalSrcUrl($url, $fullURL = null)
    {
        $url = $this->sanitizeUrl($url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true, // Hanya ambil header
            CURLOPT_FOLLOWLOCATION => false, // Kita handle redirect sendiri
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                "Referer: {$fullURL}",
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);
    
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            
            return [
                "url" => $url,
                "http_code" => $httpcode,
                "error" => $error,
                "errno" => $errno,
                "header_response" => null
            ];
        }
    
        if ($httpcode === 302) {
            if (preg_match('/Location:\s*(.*)/i', $response, $matches)) {
                $redirectUrl = trim($matches[1]);
                return [
                    "url" => $redirectUrl,
                    "http_code" => $httpcode,
                    "header_response" => $response,
                ];
            }
        }

        return [
            "url" => $url,
            "http_code" => $httpcode,
            "header_response" => $response,
        ];
    }
}
