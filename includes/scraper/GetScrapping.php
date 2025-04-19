<?php

class GetScrapping {
    private $config;
    private $db;

    public function __construct($config = []) {
        $this->config = !empty($config) ? $config : include __DIR__ . '/../config.php';
    }

    public function process($url) {
        if (!preg_match('/https?:\/\/(.+?)\/(d|e)\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $this->error("URL tidak valid");
        }

        $domain = $matches[1];
        $videoId = $matches[2];
        $timestamp = date('Y-m-d H:i:s');

        $timeout = isset($this->config['scrape_timeout']) ? (int)$this->config['scrape_timeout'] : 5;

        // Step 1: Get title from initial page
        $headHtml = $this->curlGet($url, $timeout);
        if (!$headHtml['success']) return $this->error($headHtml['error']);

        if (!preg_match('/<title>(.*?)<\/title>/is', $headHtml['body'], $titleMatch)) {
            return $this->error("Gagal mendapatkan <title>");
        }

        $title = trim($titleMatch[1]);

        // Step 2: Get stream data page
        $streamUrl = rtrim($this->config['source_url'], '/') . '/' . $this->config['stream_url_param'] . $videoId;
        $streamHtml = $this->curlGet($streamUrl, $timeout);
        if (!$streamHtml['success']) return $this->error($streamHtml['error']);

        // Step 3: Extract xstream path from player() call
        if (!preg_match('/player\(".*?",\s*"(.*?)",\s*".*?",\s*"(.*?)"\)/', $streamHtml['body'], $playerMatch)) {
            return $this->error("Gagal menemukan fungsi player() atau format tidak sesuai");
        }

        $xstreamPath = ltrim($playerMatch[2], '/');
        $xstreamUrl = rtrim($this->config['source_url'], '/') . '/' . $xstreamPath;

        $parsedUrl = parse_url($xstreamUrl);
        parse_str($parsedUrl['query'], $queryParams);

        if (isset($queryParams['filename'])) {
            $queryParams['filename'] = urlencode($queryParams['filename']);
            $parsedUrl['query'] = http_build_query($queryParams);
            $xstreamUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $parsedUrl['query'];
        }

        // Step 4: Get redirected source URL
        $decodedSrc = $this->getRedirectLocation($xstreamUrl, $timeout);
        if (!$decodedSrc['success']) return $this->error($decodedSrc['error']);

        $result = [
            "url" => $url,
            "domain_url" => $domain,
            "video_id" => $videoId,
            "video_title" => $title,
            "xstream_url" => '/' . urlencode($xstreamPath),
            "decoded_src" => $decodedSrc['location'],
            "timestamp" => $timestamp
        ];

        if ($this->config['enable_history']) {
            $this->db = new DbHandle();
            $this->db->insertHistory([
                'video_id' => $videoId,
                'domain_url' => $domain,
                'video_title' => $title,
                'decoded_src' => $decodedSrc['location'],
                'is_bulk' => 0,
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => $timestamp,
            ]);
        }

        return ["status" => "success", "message" => "Data fetched successfully", "data" => $result];
    }

    private function curlGet($url, $timeout) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP Status Code $httpCode saat mengakses $url"];
        }

        return ['success' => true, 'body' => $body];
    }

    private function getRedirectLocation($url, $timeout) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($httpCode !== 302) {
            return ['success' => false, 'error' => "Redirect gagal: HTTP Status $httpCode dengan respon $response pada $url"]; 
        }

        if (preg_match('/location:\s*(.+)/i', $response, $matches)) {
            return ['success' => true, 'location' => trim($matches[1])];
        }

        return ['success' => false, 'error' => "Header 'Location' tidak ditemukan"];
    }

    private function error($msg) {
        return ["status" => "error", "message" => $msg];
    }
}
