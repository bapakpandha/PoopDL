<?php
require_once __DIR__ . '/steps/ValidateUrl.php';


class BulkV2 {
    private $inputUrl;
    private $config;
    private $db;
    private $results = [];
    private $logs = [];

    public function __construct($inputUrl = null, $config = []) {
        $this->inputUrl = trim($inputUrl);
        $this->config = !empty($config) ? $config : include __DIR__ . '/../config.php';
    }

    public function process() {
        $validateUrl = new ValidateUrl();
        $validationResult = $validateUrl->process($this->inputUrl);
        if ($validationResult['status'] === 'error') {
            return $this->formatError($validationResult['message']);
        }

        $html = $this->fetchHtml($this->inputUrl);
        if (!$html) {
            return $this->formatError("Gagal mengambil halaman dari URL folder");
        }

        $ids = $this->extractVideoIds($html);
        if (empty($ids)) {
            return $this->formatError("Tidak ditemukan URL /d/{id} pada halaman");
        }

        $listUrl = [];
        foreach ($ids as $id) {
            $videoUrl = $this->buildDUrl($this->inputUrl, $id);
            $listUrl[] = $videoUrl;
        }
        return [
            "status" => "success",
            "message" => "Ditemukan " . count($listUrl) . " URL video pada folder",
            "data" => $listUrl,
            "failed" => []
        ];
        
    }

    private function extractVideoIds($html) {
        preg_match_all('/\/(d|e)\/([a-zA-Z0-9]+)/', $html, $matches);
        preg_match_all('/%2F(d|e)%2F([a-zA-Z0-9]+)/i', $html, $encoded_matches);
        $all_ids = array_merge($matches[2], $encoded_matches[2]);
        $unique_ids = array_values(array_unique($all_ids));
    
        return $unique_ids;
    }
    

    private function formatError($msg) {
        return [
            "status" => "error",
            "message" => $msg,
            "data" => [],
            "failed" => []
        ];
    }

    private function fetchHtml($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($this->config['scrape_timeout']) ? $this->config['scrape_timeout'] : 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code >= 400 ? false : $html;
    }

    private function buildDUrl($baseFolderUrl, $videoId) {
        $parsed = parse_url($baseFolderUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/d/' . $videoId;
    }

}