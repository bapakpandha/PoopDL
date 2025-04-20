<?php

class GetMetrolaguPostIdAndDetail
{
    public function __construct()
    {
        // Constructor code here if needed
    }

    public function process($url)
    {
        require_once 'ValidateUrl.php';
        $validateUrl = new ValidateUrl();
        $resultValidate = $validateUrl->process($url, 2);
        $url = $resultValidate['data']['url'] ?? null;
        
        if (!$url) {
            return [
                'status' => 'error',
                'message' => 'Tautan tidak valid',
                'data' => null,
                'step' => 2
            ];
        }

        $html = $this->curlGet($url, $httpcode);
    
        if ($httpcode >= 400) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses URL (HTTP ' . $httpcode . ')',
                'data' => [
                    'url' => $url,
                    'html' => $html
                ],
                'step' => 2
            ];
        }
    
        // Parsing HTML
        $result = [
            'title' => null,
            'length' => null,
            'size' => null,
            'uploadate' => null,
            'thumbnail' => null,
            'metrolagu_post_id' => null
        ];
    
        // <title>...</title>
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $m)) {
            $result['title'] = trim($m[1]);
        }
    
        // <div class="length">...</div>
        if (preg_match('/<div class="length">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['length'] = trim($m[1]);
        }
    
        // <div class="size">...</div>
        if (preg_match('/<div class="size">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['size'] = trim($m[1]);
        }
    
        // <div class="uploadate">...</div>
        if (preg_match('/<div class="uploadate">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['uploadate'] = trim($m[1]);
        }

        if (preg_match('/#poopiframe\s*\{[^}]*background-image:\s*url\([\'"]?(.*?)[\'"]?\)/i', $html, $m)) {
            $result['thumbnail'] = trim($m[1]);
        }
    
        // Pola wajib: metrolagu_post_id
        if (preg_match('/poopiframe\'\s*,\s*\'https:\/\/berlagu\.com\/jembud\/\'\s*,\s*\'length\'\s*,\s*\'([a-z0-9]+)\'/i', $html, $m)) {
            $result['metrolagu_post_id'] = $m[1];
        } else {
            return [
                'status' => 'error',
                'message' => 'Gagal menemukan ID video (metrolagu_post_id)',
                'step' => 2
            ];
        }
    
        return [
            'status' => 'success',
            'message' => 'Sedang mengambil detail video...',
            'step' => 2,
            'data' => $result
        ];
    }
    
    public function curlGet($url, &$httpcode)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }
}